<?php
/**
 * @category    BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Products Grid to add to Google Content
 *
 * @category    BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @author      Magento Core Team <core@magentocommerce.com>
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Block_Adminhtml_Items_Product 
	extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('googleShoppingApi_selection_search_grid');
		$this->setDefaultSort('id');
		$this->setSaveParametersInSession(true);
		$this->setUseAjax(true);
	}

	/**
	 * Before rendering html, but after trying to load cache
	 *
	 * @return BlueVisionTec_GoogleShoppingApi_Block_Adminhtml_Items_Product
	 */
	protected function _beforeToHtml()
	{
		$this->setId($this->getId().'_'.$this->getIndex());
		$this->getChild('reset_filter_button')->setData('onclick', $this->getJsObjectName().'.resetFilter()');
		$this->getChild('search_button')->setData('onclick', $this->getJsObjectName().'.doFilter()');
		return parent::_beforeToHtml();
	}

	/**
	 * Prepare grid collection object
	 *
	 * @return BlueVisionTec_GoogleShoppingApi_Block_Adminhtml_Items_Product
	 */
	protected function _prepareCollection()
	{
		$collection = Mage::helper('googleshoppingapi/product')->buildAvailableProductItems($this->_getStore());
        $this->setCollection($collection);
		return parent::_prepareCollection();
	}

	/**
	 * Prepare grid columns
	 *
	 * @return BlueVisionTec_GoogleShoppingApi_Block_Adminhtml_Items_Product
	 */
	protected function _prepareColumns()
	{
		$this->addColumn('id', array(
			'header'    => Mage::helper('sales')->__('ID'),
			'sortable'  => true,
			'width'     => '60px',
			'index'     => 'entity_id'
		));
		$this->addColumn('name', array(
			'header'    => Mage::helper('sales')->__('Product Name'),
			'index'     => 'name',
			'column_css_class'=> 'name'
		));

		$sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
			->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
			->load()
			->toOptionHash();

		$this->addColumn('type',
			array(
				'header'=> Mage::helper('catalog')->__('Type'),
				'width' => '150px',
				'index' => 'type_id',
				'type'  => 'options',
				'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
		));

		$this->addColumn('set_name',
			array(
				'header'=> Mage::helper('catalog')->__('Attrib. Set Name'),
				'width' => '100px',
				'index' => 'attribute_set_id',
				'type'  => 'options',
				'options' => $sets,
		));

		$this->addColumn('sku', array(
			'header'    => Mage::helper('sales')->__('SKU'),
			'index'     => 'sku',
			'column_css_class'=> 'sku'
		));
		$this->addColumn('price', array(
			'header'    => Mage::helper('sales')->__('Price'),
			'align'     => 'center',
			'type'      => 'currency',
			'currency_code' => $this->_getStore()->getDefaultCurrencyCode(),
			'rate'      => $this->_getStore()->getBaseCurrency()->getRate($this->_getStore()->getDefaultCurrencyCode()),
			'index'     => 'price'
		));
		$this->addColumn('status', array(
			'header'    => Mage::helper('sales')->__('Status'),
			'width'     => '80px',
			'index'     => 'status',
			'type'		=> 'options',
			'options'	=> array(
				Mage_Catalog_Model_Product_Status::STATUS_ENABLED => Mage::helper('sales')->__('Enabled'),
				Mage_Catalog_Model_Product_Status::STATUS_DISABLED => Mage::helper('sales')->__('Disabled')),
			'column_css_class'=> 'status'
		));

		return parent::_prepareColumns();
	}

	/**
	 * Prepare grid massaction actions
	 *
	 * @return BlueVisionTec_GoogleShoppingApi_Block_Adminhtml_Items_Product
	 */
	protected function _prepareMassaction()
	{
		$this->setMassactionIdField('entity_id');
		$this->getMassactionBlock()->setFormFieldName('product');

		$this->getMassactionBlock()->addItem('add', array(
				'label'    => $this->__('Add to Google Content'),
				'url'      => $this->getUrl('*/*/massAdd', array('_current'=>true)),
		));
		return $this;
	}

	/**
	 * Grid url getter
	 *
	 * @return string current grid url
	 */
	public function getGridUrl()
	{
		return $this->getUrl('*/googleShoppingApi_selection/grid', array('index' => $this->getIndex(),'_current'=>true));
	}

	/**
	 * Get store model by request param
	 *
	 * @return Mage_Core_Model_Store
	 */
	protected function _getStore()
	{
		return Mage::app()->getStore($this->getRequest()->getParam('store'));
	}
}
