<?php
/**
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml Google Content Item Types Mapping grid
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @author      Magento Core Team <core@magentocommerce.com>
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Block_Adminhtml_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('log_grid');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * Prepare grid collection object
     *
     * @return BlueVisionTec_GoogleShoppingApi_Block_Adminhtml_Log_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('googleshoppingapi/log_collection');
        $collection->setOrder('id','DESC');
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    /**
     * Prepare grid colunms
     *
     * @return BlueVisionTec_GoogleShoppingApi_Block_Adminhtml_Log_Grid
     */
    protected function _prepareColumns()
    {
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => Mage::helper('sales')->__('Purchased From (Store)'),
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view'=> true,
                'display_deleted' => true,
            ));
        }
        $this->addColumn('created_at',
            array(
                'header'    => $this->__('Created at'),
                'index'     => 'created_at',
                'type'      => 'datetime',
        ));
        $this->addColumn('log_level',
            array(
                'header'    => $this->__('Log Level'),
                'index'     => 'log_level',
                'type'      => 'text',
                'renderer' => 'BlueVisionTec_GoogleShoppingApi_Block_Adminhtml_Log_Renderer_Loglevel'
        ));
        $this->addColumn('message',
            array(
                'header'    => $this->__('Message'),
                'index'     => 'message',
                'type'      => 'text',
                'nl2br'     => true
        ));

        return parent::_prepareColumns();
    }

    /**
     * Return row url for js event handlers
     *
     * @param Varien_Object
     * @return string
     */
    public function getRowUrl($row)
    {
        return false;
    }

    /**
     * Grid url getter
     *
     * @return string current grid url
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
