<?php 
	class Tinkerlust_InternalApi_ProcessrestController extends Mage_Core_Controller_Front_Action
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

		function validateDate($date)
		{
			$date_array = explode(" ",$date);
			//klo ga ada jam, asume jam 0		
			if (sizeof($date_array) == 1) {
				$date = $date_array[0] . ' 00:00:00';
			}
			$d = DateTime::createFromFormat('d-m-Y H:i:s', $date);
		    return $d && $d->format('d-m-Y H:i:s') === $date;
		}

		public function orderAction(){
			//return false if token is invalid
			$this->check_access_token();
			$params = $this->getRequest()->getParams();
			$salesModel=Mage::getModel("sales/order");
			$salesCollection = $salesModel->getCollection();
			if (isset($params['from'])) {
				if ($this->validateDate($params['from'])){
					$salesCollection->addFieldToFilter('created_at', array('gteq' => date("Y-m-d H:i:s", strtotime($params['from']))));
				}
				else {
					//wrong date format
					$this->helper->buildJson(null,false,"ERROR: FROM date format is invalid.");die();
				}				
			}

			if (isset($params['to'])) {
				if ($this->validateDate($params['to'])){
					$salesCollection->addFieldToFilter('created_at', array('lteq' => date("Y-m-d H:i:s", strtotime($params['to']))));	
				}
				else {
					//wrong date format
					$this->helper->buildJson(null,false,"ERROR: TO date format is invalid.");die();
				}				
			}
			$salesArray = array('num_of_order' => sizeof($salesCollection));
			$this->helper->buildJson($salesArray);
		}

		public function visitorAction(){
			$this->check_access_token();
			$onlinevisitor = Mage::getModel('log/visitor_online')->getCollection()->addCustomerData();
			//$onlinevisitor->addFieldToFilter('customer_id', array('notnull' => true));
			$returnArray = array('online_visitor' => sizeof($onlinevisitor));
			$this->helper->buildJson($returnArray);
		}

		public function lastproducturlAction(){
			$this->check_access_token();
			$products = Mage::getModel('catalog/product')->getCollection();
			$products->addAttributeToSort('entity_id', 'desc');
			Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($products);
			$products->addUrlRewrite();
			$url = $products->getFirstItem()->getProductUrl();
			$returnArray = array("url",$url);
			$this->helper->buildJson($url);
		}

		public function brandlistAction(){
			$this->check_access_token();
			
			$attribute = Mage::getSingleton('eav/config')
			    ->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'brand');

			if ($attribute->usesSource()) {
			    $options = $attribute->getSource()->getAllOptions(false);
			}

			$this->helper->buildJson($options);
		}


	}
 ?>