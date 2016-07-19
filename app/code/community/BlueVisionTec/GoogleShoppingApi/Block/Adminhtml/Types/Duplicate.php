<?php
/**
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml Google Content Types Mapping form block
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @author      Magento Core Team <core@magentocommerce.com>
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */

class BlueVisionTec_GoogleShoppingApi_Block_Adminhtml_Types_Duplicate extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'googleshoppingapi';
        $this->_controller = 'adminhtml_types';
        $this->_mode = 'duplicate';
        $model = Mage::registry('current_item_type');
        $this->_removeButton('reset');
        $this->_removeButton('delete');
        $this->_updateButton('save', 'label', $this->__('Save Mapping'));
        $this->_updateButton('save', 'id', 'save_button');

    }

    /**
     * Get init JavaScript for form
     *
     * @return string
     */
    public function getFormInitScripts()
    {
        return $this->getLayout()->createBlock('core/template')
            ->setTemplate('googleshoppingapi/types/duplicate.phtml')
            ->toHtml();
    }

    /**
     * Get header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        return $this->__('Duplicate attribute set mapping');
    }

    /**
     * Get css class name for header block
     *
     * @return string
     */
    public function getHeaderCssClass()
    {
        return 'icon-head head-customer-groups';
    }

}
