<?php
/**
 * @category	BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Controller for mass opertions with items
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @author     Magento Core Team <core@magentocommerce.com>
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Model_MassOperations
{
    /**
     * Zend_Db_Statement_Exception code for "Duplicate unique index" error
     *
     * @var int
     */
    const ERROR_CODE_SQL_UNIQUE_INDEX = 23000;

    /**
     * Whether general error information were added
     *
     * @var bool
     */
    protected $_hasError = false;

    /**
     * Process locking flag
     *
     * @var BlueVisionTec_GoogleShoppingApi_Model_Flag
     */
    protected $_flag;

    /**
     * Set process locking flag.
     *
     * @param BlueVisionTec_GoogleShoppingApi_Model_Flag $flag
     * @return BlueVisionTec_GoogleShoppingApi_Model_MassOperations
     */
    public function setFlag(BlueVisionTec_GoogleShoppingApi_Model_Flag $flag)
    {
        $this->_flag = $flag;
        return $this;
    }

    /**
     * Add product to Google Content.
     *
     * @param array $productIds
     * @param int $storeId
     * 
     * @throws Mage_Core_Exception
     * @return BlueVisionTec_GoogleShoppingApi_Model_MassOperations
     */
    public function batchAddProducts($productIds, $storeId)
    {
        $this->_getLogger()->setStoreId($storeId);
        
        $totalAdded = 0;
        $totalFailed = 0;
        $itemIds = 0;

        $batchInsertProducts = array();
        $itemsCollection = array();

        $errors = array();
        if (is_array($productIds)) {
            foreach ($productIds as $productId) {
                if ($this->_flag && $this->_flag->isExpired()) {
                    break;
                }
                $product = Mage::getModel('catalog/product')
                    ->setStoreId($storeId)
                    ->load($productId);

                if ($product->getId()) {

                    $item = Mage::getModel('googleshoppingapi/item')->insertItem($product);

                    $itemsCollection[$itemIds] = $item;
                    $batchInsertProducts[$item->getStoreId()][$itemIds] = $item->getType()->convertAttributes($item->getProduct());

                    // The product was added successfully
                    $itemIds++;
                } 
            }
            if (empty($productIds)) {
                return $this;
            }

            if(count($batchInsertProducts) > 0 ) {
                foreach($batchInsertProducts as $storeId => $products) {
                    try {
                        $insertResult = Mage::getModel('googleshoppingapi/googleShopping')->productBatchInsert($products,$storeId);
                    } catch(Exception $e) {
                        $errors[] = "Failed to batch update for store ".$storeId.":".$e->getMessage();
                        Mage::logException($e);
                        Mage::log($e->getMessage());
                    }
                    $resEntries = array();
                    if($insertResult) { // update expiration dates or collect errors
                        foreach($insertResult->getEntries() as $batchEntry) {
                            $resEntries[$batchEntry->getBatchId()] = $batchEntry;
                        }
                        foreach($itemsCollection as $itemId => $item) {
                            if(isset($products[$itemId])){
                                if(!isset($resEntries[$itemId]) || !is_a($resEntries[$itemId],'Google_Service_ShoppingContent_ProductsCustomBatchResponseEntry')) {
                                    $errors[] = $item->getProduct()->getSku().' - '.$item->getProduct()->getTitle()." - missing response";
                                    continue;
                                }
                                
                                if($resErrors = $resEntries[$itemId]->getErrors()) {
                                    foreach($resErrors->getErrors() as $resError) {
                                        $totalFailed++;
                                        $errors[] = $item->getProduct()->getSku().' - '.$item->getProduct()->getTitle()." - ".$resError->getMessage();
                                    }
                                } else {
                                    
                                    $expires = $this->convertContentDateToTimestamp(
                                        $resEntries[$itemId]->getProduct()->getExpirationDate()
                                    );
                                    $item->setExpires($expires);
                                    $item->setGcontentItemId($resEntries[$itemId]->getProduct()->getId());
                                    $item->save();
                                    $totalAdded++;
                                }
                            }
                        }
                    }
                }
            }

        }

        if ($totalAdded > 0) {
            $this->_getLogger()->addSuccess(
                Mage::helper('googleshoppingapi')->__('Products were added to Google Shopping account.'),
                Mage::helper('googleshoppingapi')->__('Total of %d product(s) have been added to Google Content.', $totalAdded)
            );
        }

        if ($totalFailed > 0 || count($errors)) {
            array_unshift($errors, Mage::helper('googleshoppingapi')->__("Cannot insert %s items.", $totalFailed));
            $this->_getLogger()->addMajor(
                Mage::helper('googleshoppingapi')->__('Errors happened while adding products with Google Shopping'),
                $errors
            );
        }

        if ($this->_flag->isExpired()) {
            $this->_getLogger()->addMajor(
                Mage::helper('googleshoppingapi')->__('Operation of adding products to Google Shopping expired.'),
                Mage::helper('googleshoppingapi')->__('Some products may have not been added to Google Shopping bacause of expiration')
            );
        }

        return $this;
    }
    
    /**
     * Update Google Content items.
     *
     * @param array|BlueVisionTec_GoogleShoppingApi_Model_Resource_Item_Collection $items
     *
     * @throws Mage_Core_Exception
     * @return BlueVisionTec_GoogleShoppingApi_Model_MassOperations
     */
    public function batchSynchronizeItems($items)
    {
        $totalUpdated = 0;
        $totalDeleted = 0;
        $totalFailed = 0;
        $errors = array();
        
        $batchInsertProducts = array();
        $batchDeleteProducts = array();

        $itemsCollection = $this->_getItemsCollection($items);

        if ($itemsCollection) {
            if (count($itemsCollection) < 1) {
                return $this;
            }
            foreach ($itemsCollection as $item) {
                if ($this->_flag && $this->_flag->isExpired()) {
                    break;
                }
                $this->_getLogger()->setStoreId($item->getStoreId());
                $removeInactive = $this->_getConfig()->getConfigData('autoremove_disabled',$item->getStoreId());
                $renewNotListed = $this->_getConfig()->getConfigData('autorenew_notlisted',$item->getStoreId());
                if($removeInactive && ($item->getProduct()->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED || !$item->getProduct()->getStockItem()->getIsInStock() )) {
                    if(!isset($batchDeleteProducts[$item->getStoreId()])) {
                        $batchDeleteProducts[$item->getStoreId()] = array();
                    }
                    $batchDeleteProducts[$item->getStoreId()][$item->getId()] = $item->getGoogleShoppingItemId();
                } else {
                    if(!isset($batchInsertProducts[$item->getStoreId()])) {
                        $batchInsertProducts[$item->getStoreId()] = array();
                    }
                    $batchInsertProducts[$item->getStoreId()][$item->getId()] = $item->getType()->convertAttributes($item->getProduct());
                }
            }

            if(count($batchInsertProducts) > 0 ) {
                foreach($batchInsertProducts as $storeId => $products) {
                    try {
                        $insertResult = Mage::getModel('googleshoppingapi/googleShopping')->productBatchInsert($products,$storeId);
                    } catch(Exception $e) {
                        $errors[] = "Failed to batch update for store ".$storeId.":".$e->getMessage();
                        Mage::logException($e);
                        Mage::log($e->getMessage());
                    }
                    $resEntries = array();
                    if($insertResult) { // update expiration dates or collect errors
                        foreach($insertResult->getEntries() as $batchEntry) {
                            $resEntries[$batchEntry->getBatchId()] = $batchEntry;
                        }
                        foreach($itemsCollection as $item) {
                            if(isset($products[$item->getId()])){
                                if(!isset($resEntries[$item->getId()]) || !is_a($resEntries[$item->getId()],'Google_Service_ShoppingContent_ProductsCustomBatchResponseEntry')) {
                                    $errors[] = $item->getId()." - missing response";
                                    continue;
                                }
                                
                                if($resErrors = $resEntries[$item->getId()]->getErrors()) {
                                    foreach($resErrors->getErrors() as $resError) {
                                        $totalFailed++;
                                        $errors[] = $item->getId()." - ".$resError->getMessage();
                                    }
                                } else {
                                    
                                    $expires = $this->convertContentDateToTimestamp(
                                        $resEntries[$item->getId()]->getProduct()->getExpirationDate()
                                    );
                                    $item->setExpires($expires);
                                    $totalUpdated++;
                                }
                            }
                        }
                        $itemsCollection->save();
                    }
                }
            }

            if(count($batchDeleteProducts) > 0 ) {
                foreach($batchDeleteProducts as $storeId => $products) {
                    try {
                        $deleteResult = Mage::getModel('googleshoppingapi/googleShopping')->productBatchDelete($products,$storeId);
                    } catch(Exception $e) {
                        $errors[] = "Failed to batch delete for store ".$storeId.":".$e->getMessage();
                        Mage::logException($e);
                        Mage::log($e->getMessage());
                    }
                    $resEntries = array();
                    if($deleteResult) { // update expiration dates or collect errors
                        foreach($deleteResult->getEntries() as $batchEntry) {
                            $resEntries[$batchEntry->getBatchId()] = $batchEntry;
                        }
                        foreach($itemsCollection as $item) {
                            if(isset($products[$item->getId()])){
                                if(!isset($resEntries[$item->getId()]) || !is_a($resEntries[$item->getId()],'Google_Service_ShoppingContent_ProductsCustomBatchResponseEntry')) {
                                    $errors[] = $item->getId()." - missing response";
                                    continue;
                                }
                                
                                if($resErrors = $resEntries[$item->getId()]->getErrors()) {
                                    foreach($resErrors->getErrors() as $resError) {
                                        $totalFailed++;
                                        $errors[] = $item->getId()." - ".$resError->getMessage();
                                    }
                                } else {
                                    $item->delete();
                                    $totalDeleted++;
                                }
                            }
                        }
                        $itemsCollection->save();
                    }
                }
            }
            
        } else {
            return $this;
        }
        if($totalDeleted > 0 || $totalUpdated > 0) {
            $this->_getLogger()->addSuccess(
                Mage::helper('googleshoppingapi')->__('Product synchronization with Google Shopping completed.'),
                Mage::helper('googleshoppingapi')->__('Total of %d items(s) have been deleted; total of %d items(s) have been updated.', $totalDeleted, $totalUpdated)
            );
        }
        if ($totalFailed > 0 || count($errors)) {
            array_unshift($errors, Mage::helper('googleshoppingapi')->__("Cannot update %s items.", $totalFailed));
            $this->_getLogger()->addMajor(
                Mage::helper('googleshoppingapi')->__('Errors happened during synchronization with Google Shopping.'),
                $errors
            );
        }

        return $this;
    }
    
    /**
     * Synchronize all items of a store
     *
     * @param int $storeId
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_MassOperations
     */
    public function synchronizeStoreItems($storeId) {
        
        $items = $this->_getItemsCollectionByStore($storeId);
        $this->batchSynchronizeItems($items);
    
        return $this;
    }

    /**
     * Remove Google Content items.
     *
     * @param array|BlueVisionTec_GoogleShoppingApi_Model_Resource_Item_Collection $items
     * @return BlueVisionTec_GoogleShoppingApi_Model_MassOperations
     */
    public function batchDeleteItems($items)
    {
        $totalDeleted = 0;
        $totalFailed = 0;
        $itemsCollection = $this->_getItemsCollection($items);
        $errors = array();

        $batchDeleteProducts = array();

        if ($itemsCollection) {
            if (count($itemsCollection) < 1) {
                return $this;
            }
            foreach ($itemsCollection as $item) {
                if ($this->_flag && $this->_flag->isExpired()) {
                    break;
                }
                $this->_getLogger()->setStoreId($item->getStoreId());
                try {
                    if(!isset($batchDeleteProducts[$item->getStoreId()])) {
                        $batchDeleteProducts[$item->getStoreId()] = array();
                    }
                    $batchDeleteProducts[$item->getStoreId()][$item->getId()] = $item->getGoogleShoppingItemId();
                } catch (Exception $e) {
                    Mage::logException($e);
                    $errors[] = Mage::helper('googleshoppingapi')->__('The item "%s" hasn\'t been deleted.', $item->getProduct()->getName());
                }
            }

            if(count($batchDeleteProducts) > 0 ) {
                foreach($batchDeleteProducts as $storeId => $products) {
                    try {
                        $result = Mage::getModel('googleshoppingapi/googleShopping')->productBatchDelete($products,$storeId);
                    } catch(Exception $e) {
                        $errors[] = "Failed to batch delete for store ".$storeId.":".$e->getMessage();
                        Mage::logException($e);
                        Mage::log($e->getMessage());
                    }
                    $resEntries = array();
                    if($result) { // update expiration dates or collect errors
                        foreach($result->getEntries() as $batchEntry) {
                            $resEntries[$batchEntry->getBatchId()] = $batchEntry;
                        }
                        foreach($itemsCollection as $item) {
                            
                            if(!isset($resEntries[$item->getId()]) || !is_a($resEntries[$item->getId()],'Google_Service_ShoppingContent_ProductsCustomBatchResponseEntry')) {
                                $errors[] = $item->getId()." - missing response";
                                continue;
                            }
                            
                            if($resErrors = $resEntries[$item->getId()]->getErrors()) {
                                foreach($resErrors->getErrors() as $resError) {
                                    $totalFailed++;
                                    $errors[] = $item->getId()." - ".$resError->getMessage();
                                }
                            } else {
                                $item->delete();
                                $totalDeleted++;
                            }
                        }
                        $itemsCollection->save();
                    }
                }
            }
        } else {
            return $this;
        }

        if ($totalDeleted > 0) {
            $this->_getLogger()->addSuccess(
                Mage::helper('googleshoppingapi')->__('Google Shopping item removal process succeded.'),
                Mage::helper('googleshoppingapi')->__('Total of %d items(s) have been removed from Google Shopping.', $totalDeleted)
            );
        }
        if (count($errors)) {
            $this->_getLogger()->addMajor(
                Mage::helper('googleshoppingapi')->__('Errors happened while deleting items from Google Shopping.'),
                $errors
            );
        }

        return $this;
    }

    /**
     * Convert Google Content date format to unix timestamp
     * Ex. 2008-12-08T16:57:23Z -> 2008-12-08 16:57:23
     *
     * @param string Google Content datetime
     * @return int
     */
    public function convertContentDateToTimestamp($gContentDate)
    {
        return Mage::getSingleton('core/date')->date(null, $gContentDate);
    }
    
    /**
     * Return items collection by IDs
     *
     * @param array|BlueVisionTec_GoogleShoppingApi_Model_Resource_Item_Collection $items
     * @throws Mage_Core_Exception
     * @return null|BlueVisionTec_GoogleShoppingApi_Model_Resource_Item_Collection
     */
    protected function _getItemsCollection($items)
    {
        $itemsCollection = null;
        if ($items instanceof BlueVisionTec_GoogleShoppingApi_Model_Resource_Item_Collection) {
            $itemsCollection = $items;
        } else if (is_array($items)) {
            $itemsCollection = Mage::getResourceModel('googleshoppingapi/item_collection')
                ->addFieldToFilter('item_id', $items);
        }

        return $itemsCollection;
    }
    
    /**
     * Return items collection by StoreId
     *
     * @param int $storeId
     * @throws Mage_Core_Exception
     * @return null|BlueVisionTec_GoogleShoppingApi_Model_Resource_Item_Collection
     */
    protected function _getItemsCollectionByStore($storeId)
    {
        $itemsCollection = null;
        if (is_numeric($storeId)) {
            $itemsCollection = Mage::getResourceModel('googleshoppingapi/item_collection')
                ->addStoreFilter($storeId);
       }
        return $itemsCollection;
    }

    /**
     * Retrieve adminhtml session model object
     *
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }
    
    /**
     * Retrieve logger
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_Log
     */
    protected function _getLogger()
    {
        return Mage::getSingleton('googleshoppingapi/log');
    }

    /**
     * Provides general error information
     */
    protected function _addGeneralError()
    {
        if (!$this->_hasError) {
            $this->_getLogger()->addMajor(
                Mage::helper('googleshoppingapi')->__('Google Shopping Error'),
                Mage::helper('googleshoppingapi/category')->getMessage()
            );
            $this->_hasError = true;
        }
    }
    
    /**
     * Get Google Shopping config model
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('googleshoppingapi/config');
    }
}
