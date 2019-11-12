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
  private $base_node;

  private $notAllowed = array('Νο', 'Όχι');

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

    $this->base_url = Mage::app()->getStore(1)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
    $this->media_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product';
  }

  public function indexAction()
  {

    $time_start = microtime(true);

    $this->init();
    $this->createXML();
    $this->openXML();

    $this->base_node = $this->xml->getElementsByTagName('feed')->item(0);

    $this->getProducts();

    $this->xml->formatOutput = true;
    $this->xml->save($this->file);

    echo 'XML Feed generated in: ' . number_format((microtime(true) - $time_start), 2) . ' seconds';

  }

  private function createXML() {
    $dom = new DomDocument();
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

  private function getProducts() {
    $this->oProducts = Mage::getModel('catalog/product')->getCollection();
    $this->oProducts->addAttributeToFilter('status', 1); //enabled
    $this->oProducts->addAttributeToFilter('visibility', 4); //catalog, search
    $this->oProducts->addAttributeToSelect(['entity_id','sku','material_value','season_value', 'activity_value','product_gender_value','product_category','product_category_value','name','manufacturer_value','final_price','short_description','url_path','small_image','color_value','type_id']);
    if( !$this->show_outofstock ) {
      $this->oProducts->joinField('qty',
          'cataloginventory/stock_item',
          'qty',
          'product_id=entity_id',
          '{{table}}.stock_id=1',
          'left'
        );
      $this->oProducts->joinTable('cataloginventory/stock_item', 'product_id=entity_id', array('stock_status' => 'is_in_stock'));
      $this->oProducts->addAttributeToFilter('stock_status', 1);
    }
    $this->oProducts->addFinalPrice();
    $this->oProducts->addAttributeToSort('entity_id', Varien_Data_Collection::SORT_ORDER_DESC);

    Mage::getSingleton('core/resource_iterator')->walk(
      $this->oProducts->getSelect(),
      array(array($this, 'productCallback')),
      array('store_id' => 0)
    );
  }

  public function productCallback($args) {
    $oProduct = Mage::getModel('catalog/product')->setData($args['row']);

    $aCats = $this->getCategories($oProduct);

    $aData = array();

    $aData['id'] = $oProduct->entity_id;
    $aData['mpn'] = mb_substr($oProduct->sku,0,99,'UTF-8');

    $aData['brand'] = strtoupper( @mb_substr($oProduct->manufacturer_value,0,99,'UTF-8') );

    $_finalPrice = $oProduct->final_price;
    $aData['title'] = $aData['brand'] . ' ' . mb_substr($oProduct->name,0,299,'UTF-8') . ' - ' . $aData['mpn'];

    if( strlen($oProduct->short_description) > 60 ) {
      $aData['description']= ucwords(strip_tags($oProduct->short_description));
    } else {
      $aData['description']= ucwords( strip_tags( $oProduct->product_category_value . ' ' . $oProduct->manufacturer_value . ' ' . $oProduct->short_description ) );
    }
    $aData['price'] = preg_replace('/,/', '.', Mage::helper('tax')->getPrice($oProduct, $_finalPrice, true));

    $aData['link'] = mb_substr($this->base_url . $oProduct->url_path,0,299,'UTF-8');
    $aData['image_link_large'] = mb_substr($this->media_url.$oProduct->small_image,0,399,'UTF-8');

    if( $this->show_outofstock ) {
      if( $oProduct->isAvailable() ) {
        $aData['stock'] = 'Y';
        $aData['stock_descrip'] = $this->instock_msg;
      } else {
        $aData['stock'] = 'N';
        $aData['stock_descrip'] = $this->nostock_msg;
      }
    } else {
      $aData['stock'] = 'Y';
      $aData['stock_descrip'] = $this->instock_msg;
    }

    $aData['categoryid'] = array_key_exists('cid', $aCats) ? $aCats['cid'] : '';
    $aData['category'] = array_key_exists('bread', $aCats) ? $aCats['bread'] : '';

    $aData['color'] = @mb_substr($oProduct->color_value,0,99,'UTF-8');
    $aData['gender'] = @mb_substr($oProduct->product_gender_value,0,99,'UTF-8');
    $aData['material'] = @mb_substr($oProduct->material_value,0,99,'UTF-8');
    $aData['pattern'] = @mb_substr($oProduct->season_value,0,99,'UTF-8');

    $aData['product_category'] = @mb_substr($oProduct->product_category_value,0,99,'UTF-8');
    $aData['google_product_category'] = Mage::getStoreConfig('facebookfeed/googleproducttaxonomy/category_'.$oProduct->product_category);
    $aData['item_group_id'] = @mb_substr($oProduct->activity_value,0,99,'UTF-8');

    $this->appendXML($aData);
  }

  private function appendXML($p) {
    if( substr( $p['image_link_large'], -4 ) == '.jpg' ) {

      $product = $this->xml->createElement("entry");
      $this->base_node->appendChild( $product );

      $product->appendChild ( $this->xml->createElement('g:id', $p['id']) );
      $product->appendChild ( $this->xml->createElement('g:mpn', $p['mpn']) );
      $product->appendChild ( $this->xml->createElement('g:condition', $this->condition_msg) );
      $product->appendChild ( $this->xml->createElement('g:brand', htmlspecialchars($p['brand'])) );
      $product->appendChild ( $this->xml->createElement('g:title', strtoupper(filter_var($p['title'], FILTER_SANITIZE_STRING))) );

      $description = $product->appendChild($this->xml->createElement('g:description'));
      $description->appendChild($this->xml->createCDATASection( $p['description'] ));

      $product->appendChild ( $this->xml->createElement('g:price', $p['price']) );
      $product->appendChild ( $this->xml->createElement('g:link', $p['link']) );
      $product->appendChild ( $this->xml->createElement('g:image_link', $p['image_link_large']) );
      $product->appendChild ( $this->xml->createElement('g:google_product_category', htmlspecialchars($p['google_product_category'])) );
      $product->appendChild ( $this->xml->createElement('g:custom_label_0', htmlspecialchars($p['category'])) );
      $product->appendChild ( $this->xml->createElement('g:availability', $p['stock_descrip']) );
      $product->appendChild ( $this->xml->createElement('g:product_type', $p['product_category']) );
      $product->appendChild ( $this->xml->createElement('g:item_group_id', $p['item_group_id']) );
      $product->appendChild ( $this->xml->createElement('g:pattern', $p['pattern']) );

      if( $p['color'] != '' && !in_array($p['color'], $this->notAllowed) ) {
        $product->appendChild ( $this->xml->createElement('g:color', $p['color']) );
      }

      if( $p['material'] != '' && !in_array($p['material'], $this->notAllowed) ) {
        $product->appendChild ( $this->xml->createElement('g:material', $p['material']) );
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