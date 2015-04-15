<?php
/**
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Adminhtml Google Shopping Item Loglevel Renderer
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @author      Magento Core Team <core@magentocommerce.com>
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Block_Adminhtml_Log_Renderer_Loglevel
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * Renders Google Shopping Item Id
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {

        $lvl = $row->getData($this->getColumn()->getIndex());
       
        $lvlName = BlueVisionTec_GoogleShoppingApi_Model_Log::getLevelName($lvl);
       
        $color = 'black';
        switch($lvl) {
            case BlueVisionTec_GoogleShoppingApi_Model_Log::INFO:
                $color = 'grey';
                break;
            case BlueVisionTec_GoogleShoppingApi_Model_Log::SUCCESS:
                $color = 'green';
                break;
            case BlueVisionTec_GoogleShoppingApi_Model_Log::WARNING:
                $color = 'orange';
                break;
            case BlueVisionTec_GoogleShoppingApi_Model_Log::ERROR:
                $color = 'red';
                break;
            case BlueVisionTec_GoogleShoppingApi_Model_Log::CRITICAL:
                $color = 'red';
                break;
        }

        return sprintf('<span style="font-weight: bold;color: %s">%s</span>', $color, $lvlName);
    }
}
