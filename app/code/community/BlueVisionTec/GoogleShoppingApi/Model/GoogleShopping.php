<?php

// If the class exists when we get here it means it was already autoloaded by Composer so there
// is no need to include any files.
if(!class_exists('Google_Client')) {
    if(file_exists(Mage::getBaseDir().'/vendor/google/apiclient/src/Google/autoload.php')) {
        require_once Mage::getBaseDir().'/vendor/google/apiclient/src/Google/autoload.php';
    } elseif(file_exists(Mage::getBaseDir().'/../vendor/google/apiclient/src/Google/autoload.php')) {
        $path = get_include_path() . PATH_SEPARATOR . Mage::getBaseDir().'/../vendor/google/apiclient/src/Google/';
        set_include_path($path);
        require_once Mage::getBaseDir().'/../vendor/google/apiclient/src/Google/autoload.php';
    } else {
        Mage::throwException('Cannot find Google Content API autoload file');
    }
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
     * Return Google Content Client Instance using the API Client V2.
     * @param int $storeId
     * @return bool|Google_Client
     */
    private function getClientV2($storeId) {
        $privateKeyFile = Mage::getBaseDir().self::PRIVATE_KEY_UPLOAD_DIR.$this->getConfig()->getConfigData('private_key_file',$storeId);

        if(!file_exists($privateKeyFile)) {
            Mage::getSingleton('adminhtml/session')->addError("Please specify Google Content API access data for this store!");
            return false;
        }

        // TODO Turn this into an object?
        $privateKeyJson = json_decode(file_get_contents($privateKeyFile));
        if(!$privateKeyJson->client_id) {
            Mage::getSingleton('adminhtml/session')->addError("The private key that you specified does not contain a client_id. Please make sure that you are uploading a valid private key.");
            return false;
        }

        $client = new Google_Client();
        $client->setApplicationName(self::APPNAME);
        $client->setClientId($privateKeyJson->client_id);
        $client->setSubject($privateKeyJson->client_email);
        $client->setScopes([Google_Service_ShoppingContent::CONTENT]);

        putenv('GOOGLE_APPLICATION_CREDENTIALS='.$privateKeyFile);
        $client->useApplicationDefaultCredentials();

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithAssertion();
        }

        $this->_client = $client;
        return $this->_client;
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
        // FIXME Perhaps there is a more robust way of checking for library versions. Probably yes!
        if(floatval(Google_Client::LIBVER) >= 2) {
            return $this->getClientV2($storeId);
        }

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
		try {
		$result = $this->getShoppingService($storeId)->products->insert($merchantId, $product);
		} catch(Exception $e) {
		    $this->_getLogger()->addMajor(
                        Mage::helper('googleshoppingapi')->__('Errors happened while adding products to Google Shopping.'),
                        $e->getMessage()
                    );
		}
		
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
     * @param Google_Service_ShoppingContent_Product product
     * @param integer store id
     *
     * @return Google_Service_ShoppingContent_Product product
     */
    public function updateProduct($product, $storeId = null) {
		return $this->insertProduct($product, $storeId);
    }
    
    /**
     * Retrieve logger
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_Log
     */
    protected function _getLogger()
    {
        return Mage::getSingleton('googleshoppingapi/log');
    }
}
