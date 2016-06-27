<?php
/**
 * Magento Module BlueVisionTec_GoogleShoppingApi
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Product helper
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @author     BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Helper_Product extends Mage_Core_Helper_Abstract
{
    /**
     * Product attributes cache
     *
     * @var array
     */
    protected $_productAttributes;

    /**
     * Attribute labels by store ID
     *
     * @var array
     */
    protected $_attributeLabels;

    /**
     * Return Product attribute by attribute's ID
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $attributeId
     * @return null|Mage_Catalog_Model_Entity_Attribute Product's attribute
     */
    public function getProductAttribute(Mage_Catalog_Model_Product $product, $attributeId)
    {
        if (!isset($this->_productAttributes[$product->getId()])) {
            $attributes = $product->getAttributes();
            foreach ($attributes as $attribute) {
                $this->_productAttributes[$product->getId()][$attribute->getAttributeId()] = $attribute;
            }
        }

        return isset($this->_productAttributes[$product->getId()][$attributeId])
            ? $this->_productAttributes[$product->getId()][$attributeId]
            : null;
    }

    /**
     * Return Product Attribute Store Label
     * Set attribute name like frontend lable for custom attributes (which wasn't defined by Google)
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @param int $storeId Store View Id
     * @return string Attribute Store View Label or Attribute code
     */
    public function getAttributeLabel($attribute, $storeId)
    {
        $attributeId = $attribute->getId();
        $frontendLabel = $attribute->getFrontend()->getLabel();

        if (is_array($frontendLabel)) {
            $frontendLabel = array_shift($frontendLabel);
        }
        if (!isset($this->_attributeLabels[$attributeId])) {
            $this->_attributeLabels[$attributeId] = $attribute->getStoreLabels();
        }

        if (isset($this->_attributeLabels[$attributeId][$storeId])) {
            return $this->_attributeLabels[$attributeId][$storeId];
        } else if (!empty($frontendLabel)) {
            return $frontendLabel;
        } else {
            return $attribute->getAttributeCode();
        }
    }

    /**
     * Builds the collection of products which are okay to insert/add to google
     * (based on custom attribute 'google_shopping_auto_update')
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return \Mage_Catalog_Model_Resource_Product_Collection
     */
    public function buildAvailableProductItems(Mage_Core_Model_Store $store)
    {
        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('*')
        ->addFieldtoFilter('google_shopping_auto_update', array('eq'=>'1'));

        if ($store->getId()) {
            $collection->setStore($store);
            $collection->addStoreFilter($store);
        }

        $excludeIds = $this->_getGoogleShoppingProductIds($store);
        if ($excludeIds) {
            $collection->addIdFilter($excludeIds, true);
        }

        Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($collection);
//        $sql = (string)$collection->getSelect();
        return $collection;
    }

    /**
     * Get array with product ids, which was exported to Google Content
     *
     * @param Mage_Core_Model_Store $store
     * @return array
     */
    protected function _getGoogleShoppingProductIds(Mage_Core_Model_Store $store)
    {
        $collection = Mage::getResourceModel('googleshoppingapi/item_collection')
            ->addStoreFilter($store->getId())
            ->load();
        $productIds = array();
        foreach ($collection as $item) {
            $productIds[] = $item->getProductId();
        }
        return $productIds;
    }
}
