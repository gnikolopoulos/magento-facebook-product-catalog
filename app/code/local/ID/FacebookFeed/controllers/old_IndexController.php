<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class ID_FacebookFeed_IndexController extends Mage_Core_Controller_Front_Action {

  private $oProducts;
  private $oProdudctIds;
  private $oProductModel;
  private $store_name;
  private $xml_file_name;
  private $xml_path;
  private $file;
  private $excluded;
  private $xml;
  private $xmlContents;

  private $BadChars = array('"',"\r\n","\n","\r","\t");
  private $ReplaceChars = array(""," "," "," ","");

  private $notAllowed = array('Νο', 'Όχι');

  private $mappings;

  private function init()
  {
    $this->store_name = Mage::getStoreConfig('facebookfeed/feed/store_name');
    $this->xml_file_name = Mage::getStoreConfig('facebookfeed/feed/xml_file_name');
    $this->xml_path = Mage::getStoreConfig('facebookfeed/feed/feed_path');
    $this->file = $this->xml_path . $this->xml_file_name;

    $this->show_outofstock = Mage::getStoreConfig('facebookfeed/collection/show_unavailable');
    $this->excluded = explode(',', Mage::getStoreConfig('facebookfeed/collection/excluded_cats'));

    $this->instock_msg = Mage::getStoreConfig('facebookfeed/messages/in_stock');
    $this->nostock_msg = Mage::getStoreConfig('facebookfeed/messages/out_of_stock');
    $this->backorder_msg = Mage::getStoreConfig('facebookfeed/messages/backorder');
    $this->condition_msg = Mage::getStoreConfig('facebookfeed/messages/condition');

    $this->mappings = array(
      ['Camera'] => 'Cameras & Optics > Camera & Optic Accessories',
      ['Goggles'] => 'Sporting Goods > Outdoor Recreation > Winter Sports & Activities > Skiing & Snowboarding > Ski & Snowboard Goggles',
      ['Headwear'] => 'Apparel & Accessories > Clothing Accessories > Headwear',
      ['Helmet'] => 'Sporting Goods > Outdoor Recreation > Winter Sports & Activities > Skiing & Snowboarding > Ski & Snowboard Helmets',
      ['Hoodie'] => '',
      ['Ski'] => 'Sporting Goods > Outdoor Recreation > Winter Sports & Activities > Skiing & Snowboarding > Skis',
      ['Ski-Snowboard Boots'] => 'Sporting Goods > Outdoor Recreation > Winter Sports & Activities > Skiing & Snowboarding > Ski Boots',
      ['Snowboard'] => 'Sporting Goods > Outdoor Recreation > Winter Sports & Activities > Skiing & Snowboarding > Snowboards',
      ['T-Shirt'] => '',
      ['Αδιάβροχο'] => 'Apparel & Accessories > Clothing > Outerwear > Rain Suits',
      ['Αμάνικο'] => '',
      ['Αντιανεμικό'] => 'Apparel & Accessories > Clothing > Outerwear > Coats & Jackets',
      ['Αξεσουάρ'] => 'Apparel & Accessories',
      ['Βαρόμετρο-Υψόμετρο'] => 'Hardware > Tools > Measuring Tools & Sensors > Barometers',
      ['Βερμούδα'] => 'Apparel & Accessories > Clothing > Shorts',
      ['Βηματομετρητής'] => 'Electronics > GPS Accessories',
      ['Βίβλιο-Περιοδικό'] => 'Media > Books',
      ['Γάντια'] => 'Apparel & Accessories > Clothing Accessories > Gloves & Mittens',
      ['Γιλέκο'] => 'Apparel & Accessories > Clothing > Outerwear > Vests',
      ['Γυαλιά'] => 'Apparel & Accessories > Clothing Accessories > Sunglasses',
      ['Δέστρα'] => 'Sporting Goods > Outdoor Recreation > Winter Sports & Activities > Skiing & Snowboarding > Ski Binding Parts',
      ['Εσώρουχο'] => 'Apparel & Accessories > Clothing > Underwear & Socks > Underwear',
      ['Ζακέτα'] => 'Apparel & Accessories > Clothing > Outerwear > Coats & Jackets',
      ['Ζώνη'] => 'Apparel & Accessories > Clothing Accessories > Belts',
      ['Θερμός'] => 'Home & Garden > Kitchen & Dining > Food & Beverage Carriers > Thermoses',
      ['Ιμαντας'] => 'Apparel & Accessories > Clothing Accessories > Belts',
      ['Ισοθερμικό'] => 'Apparel & Accessories > Clothing Accessories > Leg Warmers',
      ['Κάλτσες'] => 'Apparel & Accessories > Clothing > Underwear & Socks > Socks',
      ['Καπέλο'] => 'Apparel & Accessories > Clothing Accessories > Hats',
      ['Κολάν'] => '',
      ['Κραμπον'] => 'Sporting Goods > Outdoor Recreation > Climbing > Climbing Apparel & Accessories > Crampons',
      ['Μαγιό'] => 'Apparel & Accessories > Clothing > Swimwear',
      ['Μπατόν'] => 'Sporting Goods > Outdoor Recreation > Camping & Hiking > Hiking Poles',
      ['Μπατόν Ski'] => 'Sporting Goods > Outdoor Recreation > Winter Sports & Activities > Skiing & Snowboarding > Ski Poles',
      ['Μπατόν Ορειβασίας'] => 'Sporting Goods > Outdoor Recreation > Camping & Hiking > Hiking Poles',
      ['Μπατόν Πεζοπορίας'] => 'Sporting Goods > Outdoor Recreation > Camping & Hiking > Hiking Poles',
      ['Μπικίνι'] => 'Apparel & Accessories > Clothing > Swimwear',
      ['Μπλούζα'] => '',
      ['Μπότες Ski'] => 'Sporting Goods > Outdoor Recreation > Winter Sports & Activities > Skiing & Snowboarding > Ski Boots',
      ['Μπότες Snowboard'] => 'Sporting Goods > Outdoor Recreation > Winter Sports & Activities > Skiing & Snowboarding > Snowboard Boots',
      ['Μπότες-Μποτάκια'] => 'Apparel & Accessories > Shoes',
      ['Μποτριέ'] => 'Sporting Goods > Outdoor Recreation > Climbing > Climbing Harnesses',
      ['Μπουστάκι'] => 'Apparel & Accessories > Clothing > Underwear & Socks > Bras',
      ['Μπούστο'] => 'Apparel & Accessories > Clothing > Underwear & Socks > Bras',
      ['Μπουφάν'] => 'Apparel & Accessories > Clothing > Outerwear > Coats & Jackets',
      ['Ομπρέλα'] => 'Home & Garden > Lawn & Garden > Outdoor Living > Outdoor Umbrellas & Sunshades',
      ['Παγούρι'] => 'Home & Garden > Kitchen & Dining > Food & Beverage Carriers > Canteens',
      ['Παντελόνι'] => 'Apparel & Accessories > Clothing > Pants',
      ['Παπούτσι'] => 'Apparel & Accessories > Shoes',
      ['Πετσέτα'] => 'Home & Garden > Linens & Bedding > Towels',
      ['Πιολε'] => '',
      ['Πουκάμισο'] => 'Apparel & Accessories > Clothing > Shirts & Tops',
      ['Προστασία'] => 'Business & Industrial > Work Safety Protective Gear',
      ['Ρολόϊ'] => 'Apparel & Accessories > Jewelry > Watches',
      ['Ρούχο'] => 'Apparel & Accessories > Clothing',
      ['Σαγιονάρες-Παντόφλες'] => 'Apparel & Accessories > Shoes',
      ['Σανδάλια'] => 'Apparel & Accessories > Shoes',
      ['Σκηνή'] => 'Sporting Goods > Outdoor Recreation > Camping & Hiking > Tents',
      ['Σκίαστρο'] => 'Sporting Goods > Outdoor Recreation > Camping & Hiking > Tent Accessories > Tent Vestibules',
      ['Σκουφάκι'] => 'Apparel & Accessories > Clothing Accessories > Hats',
      ['Σόρτς'] => 'Apparel & Accessories > Clothing > Shorts',
      ['Στολή Ski'] => 'Sporting Goods > Outdoor Recreation > Winter Sports & Activities > Skiing & Snowboarding',
      ['Σχοινί'] => 'Sporting Goods > Outdoor Recreation > Climbing > Climbing Rope',
      ['Τρόφημα'] => 'Food, Beverages & Tobacco > Food Items',
      ['Τσάντα-Σακίδιο'] => 'Apparel & Accessories > Handbags, Wallets & Cases',
      ['Υδροδοχείο-Παγούρι'] => 'Home & Garden > Kitchen & Dining > Food & Beverage Carriers > Canteens',
      ['Υπνόσακος'] => 'Sporting Goods > Outdoor Recreation > Camping & Hiking > Sleeping Bags',
      ['Υπόστρωμα'] => 'Sporting Goods > Outdoor Recreation > Camping & Hiking > Camp Furniture > Air Mattress & Sleeping Pad Accessories',
      ['Φακός'] => 'Hardware > Tools > Flashlights & Headlamps',
      ['Φανέλα'] => 'Apparel & Accessories > Clothing > Sleepwear & Loungewear',
      ['Φλασκί'] => 'Home & Garden > Kitchen & Dining > Food & Beverage Carriers > Flasks',
      ['Φόρεμα'] => 'Apparel & Accessories > Clothing > Dresses',
      ['Φόρμα'] => 'Apparel & Accessories > Clothing > Uniforms',
      ['Φούστα'] => 'Apparel & Accessories > Clothing > Skirts',
      ['Φούτερ'] => '',
      ['Χιονορακετα'] => 'Sporting Goods > Outdoor Recreation > Winter Sports & Activities > Snowshoeing > Snowshoes',
    );
  }

  public function indexAction() {

    $this->init();

    $this->getProducts();
    $this->createXML();

    $this->openXML();

    $base_node = $this->xml->getElementsByTagName('feed')->item(0);

    foreach ($this->oProducts as $oProduct) {
      @set_time_limit(0);

      //$oProduct = Mage::getModel('catalog/product');
      //$oProduct ->load($iProduct);
      $stockItem = $oProduct->isAvailable();
      $skroutz = $oProduct->getData('skroutz');
      if($stockItem == 1 && $skroutz == 1) {
        $p = $this->getProductData($oProduct->getId());
        if( substr( $p['image_link_large'], -4 ) == '.jpg' ) {

          $product = $this->xml->createElement("entry");
          $base_node->appendChild( $product );

          $product->appendChild ( $this->xml->createElement('g:id', $p['id']) );
          $product->appendChild ( $this->xml->createElement('g:mpn', $p['mpn']) );
          $product->appendChild ( $this->xml->createElement('g:condition', $this->condition_msg) );
          $product->appendChild ( $this->xml->createElement('g:brand', $p['brand']) );
          $product->appendChild ( $this->xml->createElement('g:title', $p['title']) );

          $description = $product->appendChild($this->xml->createElement('g:description'));
          $description->appendChild($this->xml->createCDATASection( $p['description'] ));

          $product->appendChild ( $this->xml->createElement('g:price', $p['price']) );
          $product->appendChild ( $this->xml->createElement('g:link', $p['link']) );
          $product->appendChild ( $this->xml->createElement('g:image_link', $p['image_link_large']) );
          $product->appendChild ( $this->xml->createElement('g:availability', $p['stock_descrip']) );
          $product->appendChild ( $this->xml->createElement('g:custom_label_0', $p['product_category']) );
          $product->appendChild ( $this->xml->createElement('g:google_product_category', $p['google_product_category']) );

          $category = $product->appendChild($this->xml->createElement('g:product_type'));
          $category->appendChild($this->xml->createCDATASection( $p['category'] ));

          if( $p['color'] != '' && !in_array($p['color'], $this->notAllowed) ) {
            $product->appendChild ( $this->xml->createElement('g:color', $p['color']) );
          }

          if( $p['gender'] != '' && !in_array($p['gender'], $this->notAllowed) && !in_array($p['gender'], array('Παιδικό - Εφηβικό','Bebe')) ) {
            if( $p['gender'] == 'Άνδρας' ) {
              $product->appendChild ( $this->xml->createElement('g:gender', 'male') );
            } elseif( $p['gender'] == 'Γυναίκα' ) {
              $product->appendChild ( $this->xml->createElement('g:gender', 'female') );
            } else {
              $product->appendChild ( $this->xml->createElement('g:gender', $p['gender']) );
            }
          }
        }

        $this->xml->formatOutput = true;
        $this->xml->save($this->file);

      } // endif

    } // endforeach

    echo 'OK';

  }

  private function createXML() {
    $dom = new DomDocument("1.0", "utf-8");
    $dom->formatOutput = true;

    $root = $dom->createElement('feed');

    $xmlns = $dom->createAttribute('xmlns:g');
    $xmlns->value = 'http://base.google.com/ns/1.0';
    $root->appendChild($xmlns);

    $xmlns2 = $dom->createAttribute('xmlns');
    $xmlns2->value = 'http://www.w3.org/2005/Atom';
    $root->appendChild($xmlns2);

    $title = $dom->createElement('title', $this->store_name );
    $root->appendChild($title);

    $link = $dom->createElement('link');
    $root->appendChild($link);

    $linkhref = $dom->createAttribute('href');
    $linkhref->value = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
    $link->appendChild($linkhref);

    $linkrel = $dom->createAttribute('rel');
    $linkrel->value = 'self';
    $link->appendChild($linkrel);

    $dom->appendChild($root);

    $dom->save($this->file);
  }

  private function openXML() {
    $this->xml = new DOMDocument();
    $this->xml->formatOutput = true;
    $this->xml->load($this->file);
  }

  private function sanitize($data) {
    $sanitized = array();
    foreach($data as $k=>$val){
      $sanitized[$k] = str_replace($this->BadChars,$this->ReplaceChars,$val);
    }
    return $sanitized;
  }

  private function getProducts() {
    $this->oProducts = Mage::getModel('catalog/product')->getCollection();
    $this->oProducts->addAttributeToFilter('status', 1); //enabled
    $this->oProducts->addAttributeToFilter('visibility', 4); //catalog, search
    $this->oProducts->addAttributeToFilter(
      array(
        array('attribute'=>'skroutz', 'eq' => '1'),
      )
    ); //skroutz products only
    $this->oProducts->addAttributeToSelect('*');
    if( !$this->show_outofstock ) {
      $this->oProducts->joinField('qty',
                   'cataloginventory/stock_item',
                   'qty',
                   'product_id=entity_id',
                   '{{table}}.stock_id=1',
                   'left');
      $this->oProducts->addAttributeToFilter('qty', array("gt" > 0));
    }
    //$this->oProdudctIds = $this->oProducts->getAllIds();
  }

  private function getProductData($iProduct) {
    $oProduct = Mage::getModel('catalog/product');
    $oProduct->load($iProduct);

    $aCats = $this->getCategories($oProduct);

    $aData = array();

    $aData['id']=$oProduct->getId();
    $aData['mpn']=mb_substr($oProduct->getSku(),0,99,'UTF-8');

    $aData['brand']= strtoupper( @mb_substr($oProduct->getResource()->getAttribute('manufacturer')->getFrontend()->getValue($oProduct),0,99,'UTF-8') );

    $_finalPrice = $oProduct->getFinalPrice();
    $catalogRulePrice = Mage::getModel('catalogrule/rule')->calcProductPriceRule($oProduct, $_finalPrice);
    $aData['title']= $aData['brand'] . ' ' . mb_substr($oProduct->getName(),0,299,'UTF-8') . ' - ' . $aData['mpn'];

    $aData['description']= ucwords(strtolower(mb_substr(strip_tags($oProduct->getShortDescription()),0,5000,'UTF-8')));

    if($catalogRulePrice) {
      $aData['price'] = number_format($catalogRulePrice, 2, '.', '');
    } else{
      $aData['price'] = number_format($_finalPrice, 2, '.', '');
    }

    $aData['link']=mb_substr($oProduct->getProductUrl(),0,299,'UTF-8');
    $aData['image_link_large']= mb_substr(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$oProduct->getImage(),0,399,'UTF-8');

    $inventory =  Mage::getModel('cataloginventory/stock_item')->loadByProduct($oProduct);

    if( $oProduct->isAvailable() && $inventory->getBackorders() == 0 ) {
      $aData['stock_descrip'] = $this->instock_msg;
    } elseif( $oProduct->isAvailable() && $inventory->getBackorders() != 0 ) {
      $aData['stock_descrip'] = $this->backorder_msg;
    } elseif( !$oProduct->isAvailable() ) {
      $aData['stock_descrip'] = $this->nostock_msg;
    }

    $aData['categoryid'] = $aCats['cid'];
    $aData['category'] = $aCats['bread'];

    $aData['color'] = @mb_substr($oProduct->getResource()->getAttribute('color')->getFrontend()->getValue($oProduct),0,99,'UTF-8');
    $aData['gender'] = @mb_substr($oProduct->getResource()->getAttribute('gender')->getFrontend()->getValue($oProduct),0,99,'UTF-8');
    $aData['product_category'] = @mb_substr($oProduct->getResource()->getAttribute('product_category')->getFrontend()->getValue($oProduct),0,99,'UTF-8');

    $dData['google_product_category '] = $this->mappings[$aData['product_category']];

    return $aData;
  }

  private function getCategories($oProduct) {
    $aIds = $oProduct->getCategoryIds();
    $aCategories = array();
    $catPath = array();
    $aCategories['bread'] = '';

    foreach($aIds as $iCategory){
      if (!in_array($iCategory, $this->excluded)) {
      $aCategories['bread'] = '';
        $oCategory = Mage::getModel('catalog/category')->load($iCategory);
        $aCategories['cid'] = $oCategory->getId();
        $aCategories['catpath'] = $oCategory->getPath();
        $catPath = explode('/', $aCategories['catpath']);
        foreach($catPath as $cpath){
          $pCategory = Mage::getModel('catalog/category')->load($cpath);
          if($pCategory->getName() !='Root Catalog' && $pCategory->getName()!='Default Category'&& $pCategory->getName()!='ΚΑΤΗΓΟΡΙΕΣ'&& $pCategory->getName()!=''){
            if (!in_array($pCategory->getId(), $this->excluded)) {
              $aCategories['bread'] .= $pCategory->getName() . ' > ';
            }
          }
        }
        $aCategories['bread'] = mb_substr(trim(substr($aCategories['bread'],0,-3)),0,299,'UTF-8');
      }
    }

    return $aCategories;
  }

}