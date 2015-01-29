<?php
/**
 * @category	BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Google Data Api destination states
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @author     Magento Core Team <core@magentocommerce.com>
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Model_Source_Destinationstates
{
    /**
     * Retrieve option array with destinations
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => Varien_Gdata_Gshopping_Extension_Control::DEST_MODE_DEFAULT,  'label' => Mage::helper('googleshoppingapi')->__('Default')),
            array('value' => Varien_Gdata_Gshopping_Extension_Control::DEST_MODE_REQUIRED, 'label' => Mage::helper('googleshoppingapi')->__('Required')),
            array('value' => Varien_Gdata_Gshopping_Extension_Control::DEST_MODE_EXCLUDED, 'label' => Mage::helper('googleshoppingapi')->__('Excluded'))
        );
    }
}
