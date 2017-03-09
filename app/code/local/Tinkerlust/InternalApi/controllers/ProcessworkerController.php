<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	class Tinkerlust_InternalApi_ProcessworkerController extends Mage_Core_Controller_Front_Action
	{

		private $_server;
		private $_storage;

		public function _construct(){
			$this->_storage = Mage::getModel('internalapi/client');
			$this->_server = new OAuth2_Server($this->_storage,['access_lifetime' => 3600,'id_lifetime' => 3600 , 'allow_public_clients' => false]);
			$this->helper = Mage::helper('internalapi');
		}
		public function check_access_token(){
			if (!$this->_server->verifyResourceRequest(OAuth2_Request::createFromGlobals())) {
				$this->helper->buildJson(null,false,"Access denied: access_token is invalid or not found in the request");die();
			}
		}

		public function randomizenewarrivalAction(){
			$this->check_access_token();
			
			//unset current new arrival products
			$newArrivalCategory = Mage::getModel('catalog/category')->load(8);
			$newArrivalCategory->setPostedProducts(array());
			$newArrivalCategory->save();

			$productsCollection = Mage::getModel('catalog/product')->getCollection()
				->setPageSize(240)
				->setCurPage(3)
				->addAttributeToFilter('sku',array('nlike' => '%-mp-%'))
				->addAttributeToFilter('status',1)
				->addAttributeToFilter('visibility',4)
				->addAttributeToSort('entity_id','desc');
			Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($productsCollection);


			//SET NEW ARRIVAL PRODUCTS
			$counter = 1;
			$category = Mage::getModel('catalog/category')->load(8);
			$postedProducts = $category->getProductsPosition();
			foreach($productsCollection as $item){
				$postedProducts[$item->getId()] = $counter++;
			}
			$category->setPostedProducts($postedProducts);
			$category->save();
			$this->helper->buildJson($counter .' products have been added to category new arrival');
		}

	}
 ?>