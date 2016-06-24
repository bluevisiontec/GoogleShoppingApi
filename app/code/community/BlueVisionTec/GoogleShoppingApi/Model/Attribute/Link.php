<?php
/**
 * @category	BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Link attribute model
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @author     Magento Core Team <core@magentocommerce.com>
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Model_Attribute_Link extends BlueVisionTec_GoogleShoppingApi_Model_Attribute_Default
{
    /**
     * Set current attribute to entry (for specified product)
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Google_Service_ShoppingContent_Product $shoppingProduct
     * @return Google_Service_ShoppingContent_Product
     */
    public function convertAttribute($product, $shoppingProduct)
    {
        $url = $product->getProductUrl(false);
        if ($url) {
            $config = Mage::getSingleton('googleshoppingapi/config');
            if (!Mage::getStoreConfigFlag('web/url/use_store') 
                && $config->getAddStoreCodeToUrl()) {
                
                $urlInfo = parse_url($url);
                $store = $product->getStore()->getCode();
                
                if (isset($urlInfo['query']) && $urlInfo['query'] != '') {
                    $url .= '&___store=' . $store;
                } else {
                    $url .= '?___store=' . $store;
                }
            }
            
			if( $config->getAddUtmSrcGshopping($product->getStoreId()) ) {
				$url .= strpos($url,'?') === false ? '?' : '&';
				$url .= 'utm_source=GoogleShopping';
			}
			if( $customUrlParameters = 
					$config->getCustomUrlParameters($product->getStoreId()) ) {
				$url .= strpos($url,'?') === false ? '?' : '&';
				$url .= $customUrlParameters;
			}

            $shoppingProduct->setLink($url);
        }

        return $shoppingProduct;
    }
}
