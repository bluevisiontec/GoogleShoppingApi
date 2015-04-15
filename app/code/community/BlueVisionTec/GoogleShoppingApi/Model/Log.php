<?php
/**
 * @category    BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Google Content Item Types Model
 *
 * @category    BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Model_Log extends Mage_Core_Model_Abstract
{

    const INFO = 0;
    const SUCCESS = 1;
    const WARNING = 2;
    const ERROR = 3;
    const CRITICAL = 4;
    
    /**
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('googleshoppingapi/log');
    }
    /**
     * Get log level name
     *
     * @param int
     *
     * @return string
     */
    public static function getLevelName($lvl) {
        $levelNames = self::getLevelNames();
        return (isset($levelNames[$lvl])) ? $levelNames[$lvl] : false;
    }
    /**
     * Get log level names
     *
     * @return array
     */
    public static function getLevelNames() {
        return array(
            self::INFO => 'info',
            self::SUCCESS => 'success',
            self::WARNING => 'warning',
            self::ERROR => 'error',
            self::CRITICAL => 'critical',
        );
    }
    
    /**
     * Save log message to db
     *
     * @param string
     * @param int
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_Log
     */
    public function log($message, $lvl = BlueVisionTec_GoogleShoppingApi_Model_Log::INFO) {
        Mage::log("STOREID".$this->getStoreId());
        $this->setLogLevel($lvl);
        $this->setMessage($message);
        $this->setCreatedAt(new Zend_Db_Expr('NOW()'));
        $this->save();     
        
        return $this;
    }
    
    /**
     * Save log message to db
     *
     * @param string
     * @param string
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_Log
     */
    public function addSuccess($title,$message) {
    
        $msg = $this->_formatMessage($title,$message);
        $this->_getSession()->addSuccess($msg);
        return $this->log($msg,BlueVisionTec_GoogleShoppingApi_Model_Log::SUCCESS);
        
    }
    /**
     * Save log message to db
     *
     * @param string
     * @param string
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_Log
     */
    public function addNotice($title,$message) {
    
        $msg = $this->_formatMessage($title,$message);
        $this->_getSession()->addSuccess($msg);
        return $this->log($msg,BlueVisionTec_GoogleShoppingApi_Model_Log::INFO);
        
    }
    /**
     * Save log message to db
     *
     * @param string
     * @param string
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_Log
     */
    public function addMajor($title,$message) {
       
       $msg = $this->_formatMessage($title,$message);
       $this->_getSession()->addSuccess($msg);
       return $this->log($msg,BlueVisionTec_GoogleShoppingApi_Model_Log::ERROR);
       
    }
    
    /**
     * Retrieve adminhtml session model object
     *
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }
    /**
     * Format message for output
     *
     * @param string
     * @param string
     *
     * @return string
     */
    protected function _formatMessage($title,$message) {
        if($message) {
            if(is_array($message)) {
                $message = implode("\n",$message);
            }
            $message = '<b>'.$title.'</b>'."\n".$message;
        } else {
            $message = $title;
        }
        return $message;
    }
}
