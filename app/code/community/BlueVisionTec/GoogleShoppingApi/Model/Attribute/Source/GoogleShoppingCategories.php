<?php
/**
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml GoogleShopping Store Switcher
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @author      Magento Core Team <core@magentocommerce.com>
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Model_Attribute_Source_GoogleShoppingCategories extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    const TAXONOMY_FILE_PATH = "/var/bluevisiontec/googleshoppingapi/data/";

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        $taxonomyPath = Mage::getBaseDir() . self::TAXONOMY_FILE_PATH;

        $lang = Mage::getStoreConfig('general/locale/code',Mage::app()->getRequest()->getParam('store', 0));
        $taxonomyFile = $taxonomyPath . "taxonomy-with-ids.".$lang.".txt";
        
        if(!file_exists($taxonomyFile)) {
			$taxonomyFile = $taxonomyPath . "taxonomy-with-ids.en_US.txt";
        }
        
        if (is_null($this->_options)) {
        
            $this->_options = array();
			$this->_options[0] = array(
				'value' => 0,
				'label' => "0 Other"
			);
        
            if(($fh = fopen($taxonomyFile,"r")) !== false) {
                $line = 0;
                while (($category = fgets($fh)) !== false) {
                    $line++;
                    if($line === 1) {continue;} // skip first line
                    
                    $option = explode(' - ',$category);
                    $this->_options[] = array(
                        'value' => $option[0],
                        'label' => $category
                    );
                }
            }
        }
        
        return $this->_options;
    }
}
