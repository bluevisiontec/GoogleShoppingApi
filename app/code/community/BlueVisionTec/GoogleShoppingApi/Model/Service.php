<?php
/**
 * @category	BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Google Content Item Types Model
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @author     Magento Core Team <core@magentocommerce.com>
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Model_Service extends Varien_Object
{
    /**
     * Client instance identifier in registry
     *
     * @var string
     */
    protected $_clientRegistryId = 'GCONTENT_HTTP_CLIENT';

    /**
     * Retutn Google Content Client Instance
     *
     * @param int $storeId
     * @param string $loginToken
     * @param string $loginCaptcha
     * @return Zend_Http_Client
     */
    public function getClient($storeId = null, $loginToken = null, $loginCaptcha = null)
    {
        $user = $this->getConfig()->getAccountLogin($storeId);
        $pass = $this->getConfig()->getAccountPassword($storeId);
        $type = $this->getConfig()->getAccountType($storeId);

        // Create an authenticated HTTP client
        $errorMsg = Mage::helper('googleshoppingapi')->__('Unable to connect to Google Content. Please, check Account settings in configuration.');
        try {
            if (!Mage::registry($this->_clientRegistryId)) {
                $client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass,
                    Varien_Gdata_Gshopping_Content::AUTH_SERVICE_NAME, null, '', $loginToken, $loginCaptcha,
                    Zend_Gdata_ClientLogin::CLIENTLOGIN_URI, $type
                );
                $configTimeout = array('timeout' => 60);
                $client->setConfig($configTimeout);
                Mage::register($this->_clientRegistryId, $client);
            }
        } catch (Zend_Gdata_App_CaptchaRequiredException $e) {
            throw $e;
        } catch (Zend_Gdata_App_HttpException $e) {
            Mage::throwException($errorMsg . Mage::helper('googleshoppingapi')->__('Error: %s', $e->getMessage()));
        } catch (Zend_Gdata_App_AuthException $e) {
            Mage::throwException($errorMsg . Mage::helper('googleshoppingapi')->__('Error: %s', $e->getMessage()));
        }

        return Mage::registry($this->_clientRegistryId);
    }

    /**
     * Set Google Content Client Instance
     *
     * @param Zend_Http_Client $client
     * @return BlueVisionTec_GoogleShoppingApi_Model_Service
     */
    public function setClient($client)
    {
        Mage::unregister($this->_clientRegistryId);
        Mage::register($this->_clientRegistryId, $client);
        return $this;
    }

    /**
     * Return Google Content Service Instance
     *
     * @param int $storeId
     * @return Varien_Gdata_Gshopping_Content
     */
    public function getService($storeId = null)
    {
        if (!$this->_service) {
            $this->_service = $this->_connect($storeId);

            if ($this->getConfig()->getIsDebug($storeId)) {
                $this->_service
                    ->setLogAdapter(Mage::getModel('core/log_adapter', 'googleshoppingapi.log'), 'log')
                    ->setDebug(true);
            }
        }
        return $this->_service;
    }

    /**
     * Set Google Content Service Instance
     *
     * @param Varien_Gdata_Gshopping_Content $service
     * @return BlueVisionTec_GoogleShoppingApi_Model_Service
     */
    public function setService($service)
    {
        $this->_service = $service;
        return $this;
    }

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
     * Authorize Google Account
     *
     * @param int $storeId
     * @return Varien_Gdata_Gshopping_Content service
     */
    protected function _connect($storeId = null)
    {
        $accountId = $this->getConfig()->getAccountId($storeId);
        $client = $this->getClient($storeId);
        $service = new Varien_Gdata_Gshopping_Content($client, $accountId);
        return $service;
    }
}
