<?php 
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
			$params = $this->getRequest()->getParams();

			if (isset($params['replace']) && $params['replace'] == true) {
				//unset current new arrival products
				$newArrivalCategory = Mage::getModel('catalog/category')->load(8);
				$newArrivalCategory->setPostedProducts(array());
				$newArrivalCategory->save();
			}
			
			$num_of_items = 
				(isset($params['numofitems']) && $params['numofitems'] > 0 && $params['numofitems'] <= 240)?
				$params['numofitems'] : 240;

			$page = 
				(isset($params['offset']) && $params['offset'] > 0)?
				($params['offset']+1) : 1;



			$productsCollection = Mage::getModel('catalog/product')->getCollection()
				->setPageSize($num_of_items)
				->setCurPage($page)
				->addAttributeToFilter('sku',array('nlike' => '%-mp-%'))
				->addAttributeToFilter('status',1)
				->addAttributeToFilter('visibility',4)
				->addAttributeToSort('entity_id','desc');
			Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($productsCollection);


			//SET NEW ARRIVAL PRODUCTS
			$counter = 0;
			$category = Mage::getModel('catalog/category')->load(8);
			$postedProducts = $category->getProductsPosition();
			foreach($productsCollection as $item){
				$postedProducts[$item->getId()] = ++$counter;
			}
			$category->setPostedProducts($postedProducts);
			$category->save();
			$this->helper->buildJson($counter . ' of products have been added to New Arrival category');
		}

	}
 ?>