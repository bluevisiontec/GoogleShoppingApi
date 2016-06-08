<?php
/**
 *
 * @category    BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * GoogleShopping upgrade 0.2.5 - 0.2.6
 *
 * @category    BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 * @author      Pascal Querner - MSCG GmbH & Co.KG <pq@mscg.de>
 */
/** @var $installer Mage_Core_Model_Resource_Setup */


$setup = Mage::getResourceModel('catalog/setup', 'core_setup');
try {
    $setup->startSetup();
    $attributeCode = 'google_shopping_auto_update';
    $setup->removeAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeCode);

    $setup->addAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeCode, array(
        'type' => 'int',
        'input' => 'select',
        'label' => 'Google Shopping: Auto Update(Add/Sync)?',
        'source' => 'eav/entity_attribute_source_boolean',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'user_defined' => true,
        'required' => false,
        'group' => 'Google Shopping',
        'default' => 1,
        'default_value' => 1,
    ));

    $setup->endSetup();
} catch (Mage_Core_Exception $e) {
    print_r($e->getMessage());
}