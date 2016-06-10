<?php
/**
 * @category	BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Google Shopping Observer
 *
 * @category	BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @author      Magento Core Team <core@magentocommerce.com>
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Model_Observer
{

    /**
     * Google Content Config
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('googleshoppingapi/config');
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
     * Retrieve synchronization process mutex
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_Flag
     */
    protected function _getFlag()
    {
        return Mage::getSingleton('googleshoppingapi/flag')->loadSelf();
    }
    
	/**
	 * Update product item in Google Content
	 *
	 * @param Varien_Object $observer
	 * @return BlueVisionTec_GoogleShoppingApi_Model_Observer
	 */
	public function saveProductItem($observer)
	{
		$product = $observer->getEvent()->getProduct();
		$items = $this->_getItemsCollection($product);

		Mage::getModel('googleshoppingapi/massOperations')
			->batchSynchronizeItems($items);

		return $this;
	}

	/**
	 * Delete product item from Google Content
	 *
	 * @param Varien_Object $observer
	 * @return BlueVisionTec_GoogleShoppingApi_Model_Observer
	 */
	public function deleteProductItem($observer)
	{
		$product = $observer->getEvent()->getProduct();
		$items = $this->_getItemsCollection($product);

		Mage::getModel('googleshoppingapi/massOperations')
			->batchDeleteItems($items);

		return $this;
	}

	/**
	 * Get items which are available for update/delete when product is saved
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return BlueVisionTec_GoogleShoppingApi_Model_Mysql4_Item_Collection
	 */
	protected function _getItemsCollection($product)
	{
		$items = Mage::getResourceModel('googleshoppingapi/item_collection')
			->addProductFilterId($product->getId());
		if ($product->getStoreId()) {
			$items->addStoreFilter($product->getStoreId());
		}
		$clientAuthenticated = true;
		foreach ($items as $item) {
			if (!Mage::getStoreConfigFlag('bvt_googleshoppingapi_config/settings/observed', $item->getStoreId())) {
				$items->removeItemByKey($item->getId());
			} else {
				$service = Mage::getModel('googleshoppingapi/googleShopping');
				if(!$service->isAuthenticated($item->getStoreId())) {
					$items->removeItemByKey($item->getId());
					$clientAuthenticated =false;
				}
			}
			
		}
		if(!$clientAuthenticated) {
			Mage::getSingleton('adminhtml/session')->addWarning(
				Mage::helper('googleshoppingapi')->__('Product was not updated on GoogleShopping for at least one store. Please authenticate and save the product again or update manually.')
			); 
		}
		
		return $items;
	}

	/**
	 * Check if synchronize process is finished and generate notification message
	 *
	 * @param  Varien_Event_Observer $observer
	 * @return BlueVisionTec_GoogleShoppingApi_Model_Observer
	 */
	public function checkSynchronizationOperations(Varien_Event_Observer $observer)
	{
		$flag = Mage::getSingleton('googleshoppingapi/flag')->loadSelf();
		if ($flag->isExpired()) {
			Mage::getModel('adminnotification/inbox')->addMajor(
				Mage::helper('googleshoppingapi')->__('Google Shopping operation has expired.'),
				Mage::helper('googleshoppingapi')->__('One or more google shopping synchronization operations failed because of timeout.')
			);
			$flag->unlock();
		}
		return $this;
	}
	
	/**
	 * sync products
	 *
	 * @return BlueVisionTec_GoogleShoppingApi_Model_Observer
	 */
	public function syncProducts() {
	
        $flag = $this->_getFlag();

        if ($flag->isLocked()) {
            return $this;
        }
	
        $stores = Mage::app()->getStores();
        foreach($stores as $_storeId => $_store) {
            if(!$this->getConfig()->getConfigData('enable_autosync',$_storeId)) {
                continue;
            }
            try {
                $flag->lock();
                Mage::getModel('googleshoppingapi/massOperations')
                    ->setFlag($flag)
                    ->synchronizeStoreItems($_storeId);
            } catch (Exception $e) {
                $flag->unlock();
                $this->_getLogger()->addMajor(
                    Mage::helper('googleshoppingapi')->__('An error has occured while syncing products with google shopping account.'),
                    Mage::helper('googleshoppingapi')->__('One or more products were not synced to google shopping account. Refer to the log file for details.')
                );
                Mage::logException($e);
                Mage::log($e->getMessage());
                return;
            }
            $flag->unlock();
            
        }
        
        return $this;
	}
}
