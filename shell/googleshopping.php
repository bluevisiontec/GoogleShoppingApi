<?php
require_once 'abstract.php';
 
class BlueVisionTec_Shell_GoogleShopping extends Mage_Shell_Abstract
{

    /**
     * @var int
     */
    protected $_storeId = null;
    /**
     * @var int
     */
    protected $categoryId = null;
    /**
     * @var int
     */
    protected $_productId = null;

    /**
     * constructor
     */
    public function __construct() {
        parent::__construct();

        // unset time limit
        set_time_limit(0);     
        
        if($this->getArg('productid')) {
           $this->_productId = $this->getArg('productid');
        }
        if($this->getArg('categoryid')) {
           $this->_categoryId = $this->getArg('categoryid');
        }
        if($this->getArg('store')) {
           $this->_storeId = $this->getArg('store');
        }
    }
    
    /**
     * constructor
     */
    public function run() {
        
        if(!$this->_productId) {
            print $this->usageHelp();
            return false;
        }
        
        $productIds = explode(",",$this->_productId);
        
        foreach($productIds as $productId) {
            if($this->_storeId) {
                Mage::app()->setCurrentStore($this->_storeId);
            }
            $product = Mage::getModel('catalog/product')->load($productId);
            if($this->_storeId) {
                $product->addStoreFilter($this->_storeId)
                         ->setStoreId($this->_storeId);
            }
            if($this->_categoryId) {
                $product->setGoogleShoppingCategory($this->_categoryId)->save();
            } else {
                print $productId.";".$product->getGoogleShoppingCategory()."\n";
            }
        }
    }
    
    /**
     * print usage information
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f googleshopping_taxonomy_mapping.php -- [options] --productid [int]
 
  productid             Id of product
  store                 Id of Store (default = all)
  categoryid            Id of GoogleShopping category
  help                  This help
 
USAGE;
    }
}

$shell = new BlueVisionTec_Shell_GoogleShopping();
$shell->run();