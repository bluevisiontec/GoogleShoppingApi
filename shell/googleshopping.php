<?php
require_once 'abstract.php';
 
class BlueVisionTec_Shell_GoogleShopping extends Mage_Shell_Abstract
{

    /**
     * @var int
     */
    protected $_storeId = NULL;
    /**
     * @var int
     */
    protected $categoryId = NULL;
    /**
     * @var int
     */
    protected $_productIds = NULL;
    /**
     * @var string
     */
    protected $_action = NULL;


    /**
     * constructor
     */
    public function __construct()
    {
        parent::__construct();

        // unset time limit
        set_time_limit(0);

        if ($this->getArg('action')) {
            $this->_action = $this->getArg('action');
        }
        if ($this->getArg('productids')) {
            $this->_productIds = explode(",", $this->getArg('productids'));
        }
        if ($this->getArg('categoryid')) {
            $this->_categoryId = $this->getArg('categoryid');
        }
        if ($this->getArg('store')) {
            $this->_storeId = $this->getArg('store');
        }
        if ($this->getArg('storeid')) {
            $this->_storeId = $this->getArg('storeid');
        }

    }

    /**
     * constructor
     */
    public function run()
    {


        switch (strtolower($this->_action)) {
            case 'getcategory':
                return $this->getCategory();
                break;
            case 'setcategory':
                return $this->setCategory();
                break;
            case 'syncitems':
                return $this->syncItems();
                break;
            case 'additems':
                return $this->additems();
                break;
            default:
                print $this->usageHelp();
                return FALSE;
        }

    }

    /**
     * sync items
     */
    protected function syncItems()
    {

        $start = time();

        $flag = $this->_getFlag();

        if ($flag->isLocked()) {
            echo "flag locked - synchronization process running\n";
            return FALSE;
        }

        if ($this->_storeId) {
            $stores = array(
                $this->_storeId => Mage::getModel('core/store')->load($this->_storeId)
            );
        } else {
            $stores = Mage::app()->getStores();
        }


        foreach ($stores as $_storeId => $_store) {
            if (!$this->getConfig()->getConfigData('enable_autosync', $_storeId)) {
                continue;
            }
            try {
                $flag->lock();
                Mage::getModel('googleshoppingapi/massOperations')
                    ->setFlag($flag)
                    ->batchSynchronizeStoreItems($_storeId);
            } catch (Exception $e) {
                $flag->unlock();
//                $this->_getLogger()->addMajor(
//                    Mage::helper('googleshoppingapi')->__('An error has occured while syncing products with google shopping account.'),
//                    Mage::helper('googleshoppingapi')->__('One or more products were not synced to google shopping account. Refer to the log file for details.')
//                );
                Mage::logException($e);
                Mage::log($e->getMessage());
                return;
            }
            $flag->unlock();

        }

        $duration = time() - $start;

        echo "Sync took $duration seconds\n";
    }

    /**
     * print category of products
     */
    protected function getCategory()
    {

        if ($this->_storeId) {
            Mage::app()->setCurrentStore($this->_storeId);
        }
        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('google_shopping_category');

        if ($this->_productIds) {
            $productCollection->addAttributeToFilter('entity_id', array('in' => $this->_productIds));
        }
        if ($this->_storeId) {
            $productCollection->addStoreFilter($this->_storeId);
        }

        foreach ($productCollection as $product) {

            print $product->getId() . ";" . Mage::getModel('catalog/product')->load($product->getId())->getGoogleShoppingCategory() . "\n";
        }
    }

    /**
     * set GoogleShopping category ids
     */
    protected function setCategory()
    {
        if (!$this->_productIds || !$this->_categoryId) {
            print $this->usageHelp();
            return FALSE;
        }

        foreach ($this->_productIds as $productId) {
            if ($this->_storeId) {
                Mage::app()->setCurrentStore($this->_storeId);
            }
            $product = Mage::getModel('catalog/product')->load($productId);
            if ($this->_storeId) {
                $product->addStoreFilter($this->_storeId)
                    ->setStoreId($this->_storeId);
            }
            $product->setGoogleShoppingCategory($this->_categoryId)->save();
        }
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
     * Retrieve synchronization process mutex
     *
     * @return BlueVisionTec_GoogleShoppingApi_Model_Flag
     */
    protected function _getFlag()
    {
        return Mage::getSingleton('googleshoppingapi/flag')->loadSelf();
    }

    /**
     * Parse .htaccess file and apply php settings to shell script
     * //Hack to look into /shell folder instead of magento root dir
     */
    protected function _applyPhpVariables()
    {
        $htaccess = getcwd() . DS . '.htaccess';
        if (file_exists($htaccess)) {
            // parse htaccess file
            $data = file_get_contents($htaccess);
            $matches = array();
            preg_match_all('#^\s+?php_value\s+([a-z_]+)\s+(.+)$#siUm', $data, $matches, PREG_SET_ORDER);
            if ($matches) {
                foreach ($matches as $match) {
                    @ini_set($match[1], str_replace("\r", '', $match[2]));
                }
            }
            preg_match_all('#^\s+?php_flag\s+([a-z_]+)\s+(.+)$#siUm', $data, $matches, PREG_SET_ORDER);
            if ($matches) {
                foreach ($matches as $match) {
                    @ini_set($match[1], str_replace("\r", '', $match[2]));
                }
            }
        }
    }

    /**
     * Adds/Insert products based on custom attribute 'google_shopping_auto_update' to google
     *
     * @return bool|void
     */
    protected function additems()
    {
        $start = time();

        $flag = $this->_getFlag();

        if ($flag->isLocked()) {
            echo "flag locked - synchronization process running\n";
            return FALSE;
        }

        if ($this->_storeId) {
            $stores = array(
                $this->_storeId => Mage::getModel('core/store')->load($this->_storeId)
            );
        } else {
            $stores = Mage::app()->getStores();
        }


        foreach ($stores as $_storeId => $_store) {
            if (!$this->getConfig()->getConfigData('enable_autosync', $_storeId)) {
                echo "Auto add is disabled for store id " . $_storeId;
                continue;
            }
            try {
                $flag->lock();
                /** @var BlueVisionTec_GoogleShoppingApi_Model_MassOperations */
                Mage::getModel('googleshoppingapi/massOperations')
                    ->setFlag($flag)
                    ->batchAddStoreItems($_storeId);
            } catch (Exception $e) {
                $flag->unlock();
//                $this->_getLogger()->addMajor(
//                    Mage::helper('googleshoppingapi')->__('An error has occured while adding products with google shopping account.'),
//                    Mage::helper('googleshoppingapi')->__('One or more products were not added to google shopping account. Refer to the log file for details.')
//                );
                Mage::logException($e);
                Mage::log($e->getMessage());
                return;
            }
            $flag->unlock();
        }

        $duration = time() - $start;

        echo "Adding products took $duration seconds\n";
    }

    /**
     * print usage information
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f googleshopping.php -- --action <actionname> [options]
 
  action                (s|g)etcategory|syncitems|deleteitems|additems
  options:
      productids            Comma separated Ids of products or single product id
      store                 Id of Store (default = all)
      categoryid            Id of GoogleShopping category
  help                  This help
USAGE;
    }
}

$shell = new BlueVisionTec_Shell_GoogleShopping();
$shell->run();