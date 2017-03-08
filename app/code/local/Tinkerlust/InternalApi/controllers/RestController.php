<?php 
	
	class Tinkerlust_InternalApi_RestController extends Mage_Core_Controller_Front_Action
	{
		private $helper;
		protected function _construct()
	  	{
	  		$this->helper = Mage::helper('internalapi');
	  	}

	  	private function force_request_method($method){
			if ($method == 'GET'){
				if (!$this->getRequest()->isGet()){
					$this->helper->buildJson(null,false,'Access Denied. Please use GET method for your request.');
					die();
				}
			}
			else if ($method == 'POST'){
				if (!$this->getRequest()->isPost()){
					$this->helper->buildJson(null,false,'Access Denied. Please use POST method for your request.');
					die();
				}	
			}
		}
		public function orderAction(){
			$this->force_request_method('GET');
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processrest/order';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint,$params,'POST');
			$this->helper->returnJson($result);
		}
		public function visitorAction(){
			$this->force_request_method('GET');
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processrest/visitor';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint,$params,'POST');
			$this->helper->returnJson($result);
		}

		public function lastproducturlAction(){
			$this->force_request_method('GET');
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processrest/lastproducturl';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint,$params,'POST');
			$this->helper->returnJson($result);
		}
	

		/* FOR VENDOR! */

		public function brandlistAction(){
			header('Access-Control-Allow-Origin: *');
			$this->force_request_method('POST');
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processrest/brandlist';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint,$params,'POST');
			$this->helper->returnJson($result);
		}	
	}
?>