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
 * GoogleShopping upgrade 0.1.0 - 0.2.0
 *
 * @category    BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
/** @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;

$installer->startSetup();

$connection = $installer->getConnection();

$table = $connection->newTable($this->getTable('googleshoppingapi/log'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
        'identity'  => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true
        ), 'Id')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, 5, array(
        'nullable' => false,
        'unsigned' => true,
        'default' => 0,
        ), 'StoreId')
    ->addColumn('log_level', Varien_Db_Ddl_Table::TYPE_INTEGER, 2, array(
        'nullable' => false
        ), 'Log level')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, 30, array(
        'nullable' => false
        ), 'Log created at')
    ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable' => true
        ), 'Log message')
    ->setComment('GoogleShopping log');
    
$installer->getConnection()->createTable($table);