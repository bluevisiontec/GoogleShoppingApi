<?php
require_once 'abstract.php';
 
class BlueVisionTec_Shell_GoogleShoppingTaxonomyUpdate extends Mage_Shell_Abstract
{
    protected $_createMap = false;
    protected $_categoryIdMap = array();
    protected $_lang = null;
    protected $_storeId = null;
    protected $_prepare = false;
 
    public function __construct() {
        parent::__construct();

        // unset time limit
        set_time_limit(0);     
        
        
        if($this->getArg('prepare')) {
           $this->_prepare = $this->getArg('prepare');
        }
        if($this->getArg('createmap')) {
            $this->_createMap = true;
        }
        if($this->getArg('lang')) {
           $this->_lang = $this->getArg('lang');
        }
        if($this->getArg('store')) {
           $this->_storeId = $this->getArg('store');
        }
        
        if($this->getArg('oldcat')) {
            $this->_oldCategories = $this->getArg('oldcat');
        }
        if($this->getArg('newcat')) {
            $this->_newCategories = $this->getArg('newcat');
        }
        
        if($this->getArg('categoryidmap')) {
            if(!$this->_lang && !$this->_storeId) {
                echo "You have to specify language or StoreView id when using a category id map\n";
                return false;
            }
           $this->_categoryIdMap = $this->parseCategoryIdMap($this->getArg('categoryidmap'));
        }
    }
    
    protected function parseCategoryIdMap($file) {
        $map = array();

        if(($fh = fopen($file,"r")) !== FALSE) {
            while($mapRow = fgetcsv($fh, 1000, ";")) {
                $map[$mapRow[0]] = $mapRow[1];
            }
            fclose($fh);
        }
        if(!count($map)) {
            echo "Could not read any mapping from file!\n";
        }
        return $map;
    }
 
    protected function getStoreIds() {
    
        if($this->_storeId) {
            return array($this->_storeId);
        }
    
        $storeIds = array();
        $stores = Mage::app()->getStores();
        
        foreach($stores as $storeId => $store) {
            $storeIds[] = $storeId;
        }
        return $storeIds;
    }
    
    protected function _prepareValues() {
        $storeIds = $this->getStoreIds();
        
        foreach($storeIds as $storeId) {
            Mage::app()->setCurrentStore($storeId);
            echo "Preparing Store: ".$storeId."\n";
            $products = Mage::getModel('catalog/product')->getCollection()
                ->addStoreFilter($storeId)
                ->setStoreId($storeId)
                ->addAttributeToSelect(array('name','google_shopping_category'));
                
            foreach($products as $product) {
                $negVal = $product->getGoogleShoppingCategory()*-1;
                $product->setGoogleShoppingCategory($negVal)->save();
            }
        }
    }
 
    protected function _createMap() {
        if(!$this->_oldCategories || !$this->_newCategories) {
            echo "Please specify old and new category paths\n";
            return;
        }
        $oldCatFH = fopen($this->_oldCategories,"r");
        $newCats = file($this->_newCategories);
        $newCatData = explode($newCats,"\n");
        
        if($oldCatFH === FALSE || $newCatFH === FALSE) { 
            echo "Failed to open category file paths\n";
            return;
        }
        
        $newCat = array();
        $lineNr = 0;
        print "#Old Category Id; New Category Id\n";
        while( ($line = fgets($oldCatFH, 1024)) !== FALSE ) {
            $lineNr++; // count every line!
            if(substr($line,0,1) == '#') {
                continue;
            }
            $match = preg_grep("/.*".preg_quote($line,'/')."$/",$newCats);
            if($match && count($match)) {
                $newCatLine = reset ( $match );
                $newCatId = substr($newCatLine, 0,strpos($newCatLine, ' '));
                if(!$newCatId){continue;}
                print $lineNr.";".$newCatId."\n";
            }
        }
    }

 
    public function run() {

        if($this->_prepare) {
            return $this->_prepareValues();
        }
        
        if($this->_createMap) {
            return $this->_createMap();
        }
    
        $storeIds = $this->getStoreIds();
            
        foreach($storeIds as $storeId) {
            Mage::app()->setCurrentStore($storeId);
            $storeLang = Mage::getStoreConfig('general/locale/code', Mage::app()->getStore()->getId());
            if(!$this->_storeId && ($storeLang != $this->_lang)) {
                // skip stores with wrong language
                continue;
            }
        
            echo "Updating products in store: ".$storeId."\n";

            
            $products = Mage::getModel('catalog/product')->getCollection()
                ->addStoreFilter($storeId)
                ->setStoreId($storeId)
                ->addAttributeToSelect(array('name','google_shopping_category'));
                
            foreach($products as $product) {
                $oldCatId = $product->getGoogleShoppingCategory();
                $newCatId = null;
                $catUpdated = "";
                if($oldCatId && $oldCatId > 0) {
                    // skip not prepared categories
                    continue;
                }
                $oldCatId = $oldCatId*-1;
                
                $map = $this->_categoryIdMap;
                
                if(isset($map[$oldCatId])) {
                    $newCatId = $map[$oldCatId];
                }
                
                if($newCatId) {
                    $product->setGoogleShoppingCategory($newCatId)->save();
                    $catUpdated = "updated";
                }

                print  $product->getId().';"'.$product->getName().'";'.$oldCatId.';'.$newCatId.";".$catUpdated."\n";
            }
        }
        
    }
 
    /**
     * print usage information
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f googleshopping_taxonomy_mapping.php -- [options]
 
  prepare               Sets all category ids to its negative value
  store                 Id of Store (default = all)
  lang                  Lanaguage code like de_DE
  categoryidmap         Path to file with category mapping
  
  createmap             Creates category map for exactly matching category strings
  oldcat                Path to old categories file
  newcat                Path to new categories file
 
  help                  This help
 
USAGE;
    }
}

$shell = new BlueVisionTec_Shell_GoogleShoppingTaxonomyUpdate();
$shell->run();