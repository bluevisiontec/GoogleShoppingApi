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
     * @param Mage_Catalog_Model_Resource_Product_Collection $productCollection
     * @param int $storeId
     * 
     * @throws Mage_Core_Exception
     * @return BlueVisionTec_GoogleShoppingApi_Model_MassOperations
     */
    public function addProducts(Mage_Catalog_Model_Resource_Product_Collection $productCollection, $storeId)
    {
        $this->_getLogger()->setStoreId($storeId);
        
        $totalAdded = 0;
        $errors = array();
        if ($productCollection->getSize() > 0) {
            $productCollection->setPageSize(100);
            $pages = $productCollection->getLastPageNumber();

            $currentPage = 1;
            $batchNumber = 0;

            do {

                $productCollection->setCurPage($currentPage);
                $productCollection->load();

                if ($this->_flag && $this->_flag->isExpired()) {
                    break;
                }
                try {
                    /** @var Mage_Catalog_Model_Product $product */
                    foreach ($productCollection as $product) {
                        if ($product->getId()) {
                            /** @var BlueVisionTec_GoogleShoppingApi_Model_Item $item */
                            $item = Mage::getModel('googleshoppingapi/item');
                            $item
                                ->insertItem($product)
                                ->save();
                            // The product was added successfully
                            $totalAdded++;
                        }
                    }
                } catch (Mage_Core_Exception $e) {
                    $errors[] = Mage::helper('googleshoppingapi')->__('The product "%s" cannot be added to Google Content. %s', $product->getName(), $e->getMessage());
                } catch (Exception $e) {
                    Mage::logException($e);
                    $errors[] = Mage::helper('googleshoppingapi')->__('The product "%s" hasn\'t been added to Google Content.', $product->getName());
                    $errors[] = $e->getMessage();
                }

                $productCollection->clear();
                $currentPage++;

            } while ($currentPage <= $pages);
        }

        if ($totalAdded > 0) {
            $this->_getLogger()->addSuccess(
                Mage::helper('googleshoppingapi')->__('Products were added to Google Shopping account.'),
                Mage::helper('googleshoppingapi')->__('Total of %d product(s) have been added to Google Content.', $totalAdded)
            );
        }

        if (count($errors)) {
            $this->_getLogger()->addMajor(
                Mage::helper('googleshoppingapi')->__('Errors happened while adding products to Google Shopping.'),
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
    public function synchronizeItems($items)
    {
        $totalUpdated = 0;
        $totalDeleted = 0;
        $totalFailed = 0;
        $errors = array();

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
                try {
					if($removeInactive && ($item->getProduct()->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED || !$item->getProduct()->getStockItem()->getIsInStock() )) {
						$item->deleteItem();
						$item->delete();
						$totalDeleted++;
						Mage::log("remove inactive: ".$item->getProduct()->getSku()." - ".$item->getProduct()->getName());
					} else {
						$item->updateItem();
						$item->save();
						// The item was updated successfully
						$totalUpdated++;
					}
                } catch (Mage_Core_Exception $e) {
                    $errors[] = Mage::helper('googleshoppingapi')->__('The item "%s" cannot be updated at Google Content. %s', $item->getProduct()->getName(), $e->getMessage());
                    $totalFailed++;
                } catch (Exception $e) {
                    Mage::logException($e);
                    $errors[] = Mage::helper('googleshoppingapi')->__('The item "%s" hasn\'t been updated.', $item->getProduct()->getName());
                    $errors[] = $e->getMessage();
                    $totalFailed++;
                }
            }
        } else {
            return $this;
        }
        if($totalDeleted > 0 || $totalUpdated > 0) {
            $this->_getLogger()->addSuccess(
                Mage::helper('googleshoppingapi')->__('Product synchronization with Google Shopping completed') . "\n"
                . Mage::helper('googleshoppingapi')->__('Total of %d items(s) have been deleted; total of %d items(s) have been updated.', $totalDeleted, $totalUpdated)
            );
        }
        if ($totalFailed > 0 || count($errors)) {
            array_unshift($errors, Mage::helper('googleshoppingapi')->__("Cannot update %s items.", $totalFailed));
            $this->_getLogger()->addMajor(
                Mage::helper('googleshoppingapi')->__('Errors happened during synchronization with Google Shopping'),
                $errors
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
                try {
                    if($removeInactive && ($item->getProduct()->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED || !$item->getProduct()->getStockItem()->getIsInStock() )) {
                        // TODO: batch delete
                        $item->deleteItem();
                        $item->delete();
                        $totalDeleted++;
                        Mage::log("remove inactive: ".$item->getProduct()->getSku()." - ".$item->getProduct()->getName());
                    } else {
                        if(!isset($batchInsertProducts[$item->getStoreId()])) {
                            $batchInsertProducts[$item->getStoreId()] = array();
                        }
                        $batchInsertProducts[$item->getStoreId()][$item->getId()] = $item->getType()->convertAttributes($item->getProduct());
                    }
                } catch (Mage_Core_Exception $e) {
                    $errors[] = Mage::helper('googleshoppingapi')->__('The item "%s" cannot be updated at Google Content. %s', $item->getProduct()->getName(), $e->getMessage());
                    $totalFailed++;
                } catch (Exception $e) {
                    // remove items which are not available on google content
                    if($e->getCode() == "404") {
                        $item->delete();
                        $totalDeleted++;
                        Mage::log("remove inactive: ".$item->getProduct()->getSku()." - ".$item->getProduct()->getName());
                    } else {                    
                        Mage::logException($e);
                        $errors[] = Mage::helper('googleshoppingapi')->__('The item "%s" hasn\'t been updated.', $item->getProduct()->getName());
                        $errors[] = $e->getMessage();
                        $totalFailed++;
                    }
                }
            }
            
            if(count($batchInsertProducts) > 0 ) {
                foreach($batchInsertProducts as $storeId => $products) {
                    try {
                        $result = Mage::getModel('googleshoppingapi/googleShopping')->productBatchInsert($products,$storeId);
                    } catch(Exception $e) {
                        $errors[] = "Failed to batch update for store ".$storeId.":".$e->getMessage();
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
                                
                                $expires = $this->convertContentDateToTimestamp(
                                    $resEntries[$item->getId()]->getProduct()->getExpirationDate()
                                );
                                $item->setExpires($expires);
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
                Mage::helper('googleshoppingapi')->__('Product synchronization with Google Shopping completed') . "\n"
                . Mage::helper('googleshoppingapi')->__('Total of %d items(s) have been deleted; total of %d items(s) have been updated.', $totalDeleted, $totalUpdated)
            );
        }
        if ($totalFailed > 0 || count($errors)) {
            array_unshift($errors, Mage::helper('googleshoppingapi')->__("Cannot update %s items.", $totalFailed));
            $this->_getLogger()->addMajor(
                Mage::helper('googleshoppingapi')->__('Errors happened during synchronization with Google Shopping'),
                $errors
            );
        }

        return $this;
    }
    
    /**
     * Synchronize all items of a stroe
     *
     * @param int $storeId
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_MassOperations
     */
    public function synchronizeStoreItems($storeId) {
    
        $items = $this->_getItemsCollectionByStore($storeId);
        $this->synchronizeItems($items);
    
        return $this;
    }
    
    /**
     * Synchronize all items of a store
     *
     * @param int $storeId
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_MassOperations
     */
    public function batchSynchronizeStoreItems($storeId) {

        $items = $this->_getItemsCollectionByStore($storeId);
        $this->synchronizeItems($items);

        return $this;
    }

    /**
     * Synchronize all items of a store
     *
     * @param int $storeId
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_MassOperations
     * @throws \Exception
     */
    public function batchAddStoreItems($storeId) {

        $items = $this->_getUnsyncedItemsCollectionByStore($storeId);
        if($items instanceof Mage_Catalog_Model_Resource_Product_Collection) {
            $this->addProducts($items,$storeId);
        } else {
            throw new Exception("Could not generate product collection.");
        }

        return $this;
    }

    /**
     * Remove Google Content items.
     *
     * @param array|BlueVisionTec_GoogleShoppingApi_Model_Resource_Item_Collection $items
     * @return BlueVisionTec_GoogleShoppingApi_Model_MassOperations
     */
    public function deleteItems($items)
    {
        $totalDeleted = 0;
        $itemsCollection = $this->_getItemsCollection($items);
        $errors = array();
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
                    $item->deleteItem()->delete();
                    // The item was removed successfully
                    $totalDeleted++;
                } catch (Exception $e) {
                    
                    if($e->getCode() == 404){
						$item->delete();
						$this->_getLogger()->addNotice(
							Mage::helper('googleshoppingapi')->__(
								'The item "%s" was not found on GoogleContent',
								$item->getProduct()->getName()
							)
						);
						$totalDeleted++;
                    } else {
						Mage::logException($e);
						$errors[] = Mage::helper('googleshoppingapi')->__('The item "%s" hasn\'t been deleted.', $item->getProduct()->getName());
                    }
                }
            }
        } else {
            return $this;
        }

        if ($totalDeleted > 0) {
            $this->_getLogger()->addSuccess(
                Mage::helper('googleshoppingapi')->__('Google Shopping item removal process succeded'),
                Mage::helper('googleshoppingapi')->__('Total of %d items(s) have been removed from Google Shopping.', $totalDeleted)
            );
        }
        if (count($errors)) {
            $this->_getLogger()->addMajor(
                Mage::helper('googleshoppingapi')->__('Errors happened while deleting items from Google Shopping'),
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
            /** @var BlueVisionTec_GoogleShoppingApi_Model_Resource_Item_Collection $itemsCollection */
            $itemsCollection = Mage::getResourceModel('googleshoppingapi/item_collection')
                ->addStoreFilter($storeId);
       }
        return $itemsCollection;
    }

    /**
     * Return unsynced items collection by StoreId
     *
     * @param int $storeId
     * @throws Mage_Core_Exception
     * @return null|BlueVisionTec_GoogleShoppingApi_Model_Resource_Item_Collection
     */
    protected function _getUnsyncedItemsCollectionByStore($storeId)
    {
        $productCollection = NULL;
        if (is_numeric($storeId)) {
            /** @var BlueVisionTec_GoogleShoppingApi_Helper_Product $helperModel */
            $helperModel = Mage::helper('googleshoppingapi/product');
            $productCollection = $helperModel->buildAvailableProductItems(Mage::app()->getStore($storeId));
        }
        return $productCollection;
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
