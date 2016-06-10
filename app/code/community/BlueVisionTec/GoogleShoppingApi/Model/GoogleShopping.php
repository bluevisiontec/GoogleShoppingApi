<?php
if(file_exists(Mage::getBaseDir().'/vendor/google/apiclient/src/Google/autoload.php')) { //vendor path within magento installation dir

    require_once Mage::getBaseDir().'/vendor/google/apiclient/src/Google/autoload.php';
} elseif(file_exists(Mage::getBaseDir().'/../vendor/google/apiclient/src/Google/autoload.php')) { //vendor path outside magento installation dir

    set_include_path(get_include_path() . PATH_SEPARATOR . Mage::getBaseDir().'/../vendor/google/apiclient/src/Google/');
    require_once Mage::getBaseDir().'/../vendor/google/apiclient/src/Google/autoload.php';
} else {
    Mage::throwException('Cannot find Google Content API autoload file');
}
/**
 * @category	BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Google Shopping connector
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Model_GoogleShopping extends Varien_Object
{

	const APPNAME = 'BlueVisionTec Magento GoogleShopping';
	const PRIVATE_KEY_UPLOAD_DIR = '/var/bluevisiontec/googleshoppingapi/oauth/';

	/** 
	 * @var Google_Client
	 */
    protected $_client = null;
    /** 
	 * @var Google_Service_ShoppingContent
	 */
    protected $_shoppingService = null;

    /**
     * Google Content Config
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('googleshoppingapi/config');
    }
    
    /**
     * Redirect to OAuth2 authentication
     *
     * @param int $storeId
     */
    public function redirectToAuth($storeId,$noAuthRedirect) {
    
		if($noAuthRedirect) {
			return false;
		} else {
			header('Location: ' . Mage::getUrl("adminhtml/googleShoppingApi_oauth/auth",array('store_id'=>$storeId) ));
			exit;
		}
    }
	/**
	 * Check if client is authenticated for storeId
	 *
	 * @param int $storeId
	 *
	 * @return bool
	 */
    public function isAuthenticated($storeId) {
		if($this->getClient($storeId, true) === false) {
			return false;
		} else {
			return true;
		}
    }
    
    /**
     * Return Google Content Client Instance
     *
     * @param int $storeId
     * @param string $loginToken
     * @param string $loginCaptcha
     *
     * @return Zend_Http_Client
     */
    public function getClient($storeId, $noAuthRedirect = false)
    {
        $useServiceAccount = $this->getConfig()->getUseServiceAccount($storeId);
        
		if(isset($this->_client)) {
			if($this->_client->isAccessTokenExpired()) {
                if($useServiceAccount) {
                    $client->getAuth()->refreshTokenWithAssertion();
                } else {
                    return $this->redirectToAuth($storeId,$noAuthRedirect);
                }
			}
			return $this->_client;
		}
    
		$adminSession = Mage::getSingleton('admin/session');

 		$accessTokens = $adminSession->getGoogleOAuth2Token();

 		$clientId = $this->getConfig()->getConfigData('client_id',$storeId);
		$clientSecret = $this->getConfig()->getConfigData('client_secret',$storeId);
		$clientEmail =  $this->getConfig()->getConfigData('client_email',$storeId);
		$privateKeyFile = Mage::getBaseDir().self::PRIVATE_KEY_UPLOAD_DIR.$this->getConfig()->getConfigData('private_key_file',$storeId);
		$privateKeyPassword = $this->getConfig()->getPrivateKeyPassword($storeId);
		

        $privateKey = file_get_contents($privateKeyFile);
        $credentials = new Google_Auth_AssertionCredentials(
            $clientEmail,
            array('https://www.googleapis.com/auth/content'),
            $privateKey,
            $privateKeyPassword
        );
		
		$accessToken = $accessTokens[$clientId];

		if(!$clientId || (!$clientSecret && !$useServiceAccount) ) {
			Mage::getSingleton('adminhtml/session')->addError("Please specify Google Content API access data for this store!");
			return false;
			
 		}
 		
 		if(!$useServiceAccount) {
            if(!isset($accessToken) || empty($accessToken) ) {
                return $this->redirectToAuth($storeId,$noAuthRedirect);
            }
		}
    
		$client = new Google_Client();
		$client->setApplicationName(self::APPNAME);
		$client->setClientId($clientId);
		
		$client->setScopes('https://www.googleapis.com/auth/content');
		
		
		if($useServiceAccount) {
            $client->setClassConfig('Google_Cache_File',array('directory' => Mage::getBaseDir().'/var/cache/bluevisiontec/googleshoppingapi/googleapi/'));
            $client->setAssertionCredentials($credentials);
             if ($client->getAuth()->isAccessTokenExpired()) {
                $client->getAuth()->refreshTokenWithAssertion();
            }
		} else {
            $client->setAccessToken($accessToken);
            $client->setClientSecret($clientSecret);
            if($client->isAccessTokenExpired()) {
                return $this->redirectToAuth($storeId,$noAuthRedirect);
            }
		}
		
		$this->_client = $client;
		
		return $this->_client;
    }
    
    /**
     * @return Google_Service_ShoppingContent shopping client
     */
    public function getShoppingService($storeId = null) {
		if(isset($this->_shoppingService)) {
			return $this->_shoppingService;
		}
		
		$this->_shoppingService = new Google_Service_ShoppingContent($this->getClient($storeId));
		return $this->_shoppingService;
    }
    
    public function listProducts($storeId = null) {
		$merchantId = $this->getConfig()->getConfigData('merchant_id',$storeId);
    
		return $this->getShoppingService($storeId)->products->listProducts($merchantId);
		//$products = $this->getShoppingService($storeId)->products->listProducts($merchantId, $parameters);
		//$products->getResources();
// 		echo count($products);
// 		foreach($products as $product) {
// 			echo $product->title."<br/>";
// 		}
    }
    
    /**
     * @param string product id
     * @param integer store id
     *
     * @return Google_Service_ShoppingContent_Product product
     */
    public function getProduct($productId, $storeId = null) {
		$merchantId = $this->getConfig()->getConfigData('account_id',$storeId);
		$product = $this->getShoppingService($storeId)->products->get($merchantId,$productId);
		return $product;
		
    }
    /**
     * @param string product id
     * @param integer store id
     */
    public function deleteProduct($productId, $storeId = null) {
		$merchantId = $this->getConfig()->getConfigData('account_id',$storeId);
		$result = $this->getShoppingService($storeId)->products->delete($merchantId,$productId);
		return $result;
		
    }
    /**
     * @param Google_Service_ShoppingContent_Product product
     * @param integer store id
     *
     * @return Google_Service_ShoppingContent_Product product
     */
    public function insertProduct($product, $storeId = null) {
		$merchantId = $this->getConfig()->getConfigData('account_id',$storeId);
		$product->setChannel("online");
		$expDate = date("Y-m-d",(time()+30*24*60*60));//product expires in 30 days
		$product->setExpirationDate($expDate);
		$result = $this->getShoppingService($storeId)->products->insert($merchantId, $product);
		return $result;
		
    }
    
    /**
     * @param array products
     * @param integer store id
     *
     * @return array
     */
    public function productBatchInsert($products, $storeId = null) {
    
        $merchantId = $this->getConfig()->getConfigData('account_id',$storeId);
        
        $entries = array();

        foreach($products as $itemId => $product) {

            $product->setChannel("online");
            $expDate = date("Y-m-d",(time()+30*24*60*60));//product expires in 30 days
            $product->setExpirationDate($expDate);
            
            $entry = new Google_Service_ShoppingContent_ProductsCustomBatchRequestEntry();
            
            $entry->setBatchId($itemId);
            $entry->setMerchantId($merchantId);
            $entry->setMethod('insert');
            $entry->setProduct($product);
            
            $entries[] = $entry;
        }
        
        $batchReq = new Google_Service_ShoppingContent_ProductsCustomBatchRequest();
        $batchReq->setEntries($entries);
        
        $result = $this->getShoppingService($storeId)->products->customBatch($batchReq);

        return $result;
        
    }

    /**
     * @param array products
     * @param integer store id
     *
     * @return array
     */
    public function productBatchDelete($products, $storeId = null) {
    
        $merchantId = $this->getConfig()->getConfigData('account_id',$storeId);
        
        $entries = array();

        foreach($products as $itemId => $product) {
            $entry = new Google_Service_ShoppingContent_ProductsCustomBatchRequestEntry();
            $entry->setBatchId($itemId);
            $entry->setMerchantId($merchantId);
            $entry->setMethod('delete');
            $entry->setProductId($product);
            
            $entries[] = $entry;
        }
        $batchReq = new Google_Service_ShoppingContent_ProductsCustomBatchRequest();
        $batchReq->setEntries($entries);
        
        $result = $this->getShoppingService($storeId)->products->customBatch($batchReq);

        return $result;
        
    }
    
    /**
     * @param Google_Service_ShoppingContent_Product product
     * @param integer store id
     *
     * @return Google_Service_ShoppingContent_Product product
     */
    public function updateProduct($product, $storeId = null) {
		return $this->insertProduct($product, $storeId);
    }
    
    
}