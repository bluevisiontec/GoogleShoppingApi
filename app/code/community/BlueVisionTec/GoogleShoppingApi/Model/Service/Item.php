<?php
/**
 * @category	BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Google Content Item Model
 *
 * @category	BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @author      Magento Core Team <core@magentocommerce.com>
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Model_Service_Item extends BlueVisionTec_GoogleShoppingApi_Model_Service
{
    /**
     * Return Store level Service Instance
     *
     * @param int $storeId
     * @return Varien_Gdata_Gshopping_Content
     */
    public function getService($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getStoreId();
        }
        return parent::getService($storeId);
    }

    /**
     * Insert Item into Google Content
     *
     * @param BlueVisionTec_GoogleShoppingApi_Model_Item $item
     * @return BlueVisionTec_GoogleShoppingApi_Model_Service_Item
     */
    public function insert($item)
    {

        $service = Mage::getModel('googleshoppingapi/googleShopping');
        
        $product = $item->getType()->convertAttributes($item->getProduct());

        $shoppingProduct = $service->insertProduct($product,$item->getStoreId());
        $published = now();

        $item->setGcontentItemId($shoppingProduct->getId())
            ->setPublished($published);

        Mage::log($shoppingProduct);
            
        $expires = $shoppingProduct->getExpirationDate();
        
        if ($expires) {
            $expires = $this->convertContentDateToTimestamp($expires);
            $item->setExpires($expires);
        }
        return $this;
    }

    /**
     * Update Item data in Google Content
     *
     * @param BlueVisionTec_GoogleShoppingApi_Model_Item $item
     * @return BlueVisionTec_GoogleShoppingApi_Model_Service_Item
     */
    public function update($item)
    {
		$service = Mage::getModel('googleshoppingapi/googleShopping');
		
		$gItemId = $item->getGoogleShoppingItemId();

// 		$product = $service->getProduct($gItemId,$item->getStoreId());
		
		$product = $item->getType()->convertAttributes($item->getProduct());
		
		$shoppingProduct = $service->updateProduct($product,$item->getStoreId());
		Mage::log($shoppingProduct);
		$expires = $shoppingProduct->getExpirationDate();
        
        if ($expires) {
            $expires = $this->convertContentDateToTimestamp($expires);
            $item->setExpires($expires);
        }
        
        return $this;
    }

    /**
     * Delete Item from Google Content
     *
     * @param BlueVisionTec_GoogleShoppingApi_Model_Item $item
     * @return BlueVisionTec_GoogleShoppingApi_Model_Service_Item
     */
    public function delete($item)
    {
        
        $gItemId = $item->getGoogleShoppingItemId();
        $service = Mage::getModel('googleshoppingapi/googleShopping');
        $service->deleteProduct($gItemId,$item->getStoreId());

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
     * Return Google Content Item Attribute Value
     *
     * @param Varien_Gdata_Gshopping_Entry $entry
     * @param string $name Google Content attribute name
     * @return string|null Attribute value
     */
    protected function _getAttributeValue($entry, $name)
    {
        $attribute = $entry->getContentAttributeByName($name);
        return ($attribute instanceof Varien_Gdata_Gshopping_Extension_Attribute)
            ? $attribute->text
            : null;
    }

    /**
     * Retrieve item query for Google Content
     *
     * @param BlueVisionTec_GoogleShoppingApi_Model_Item $item
     * @return Varien_Gdata_Gshopping_ItemQuery
     */
    protected function _buildItemQuery($item)
    {
		//TODO: remove
        $storeId = $item->getStoreId();
        $service = $this->getService($storeId);

        $countryInfo = $this->getConfig()->getTargetCountryInfo($storeId);
        $itemId = Mage::helper('googleshoppingapi')->buildContentProductId($item->getProductId(), $item->getStoreId());

        $query = $service->newItemQuery()
            ->setId($itemId)
            ->setTargetCountry($this->getConfig()->getTargetCountry($storeId))
            ->setLanguage($countryInfo['language']);

        return $query;
    }

    /**
     * Return item stats array based on Zend Gdata Entry object
     *
     * @param Varien_Gdata_Gshopping_Entry $entry
     * @return array
     */
    protected function _getEntryStats($entry)
    {
		//TODO: remove
        $result = array();
        $expirationDate = $entry->getContentAttributeByName('expiration_date');
        if ($expirationDate instanceof Varien_Gdata_Gshopping_Extension_Attribute) {
            $result['expires'] = $this->convertContentDateToTimestamp($expirationDate->text);
        }

        return $result;
    }
}
