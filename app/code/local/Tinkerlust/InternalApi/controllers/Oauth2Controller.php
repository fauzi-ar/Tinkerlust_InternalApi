<?php 
	class Tinkerlust_InternalApi_Oauth2Controller extends Mage_Core_Controller_Front_Action
	{
		private $helper;

		protected function _construct()
	  	{
	  		$this->helper = Mage::helper('internalapi');
	  	}		

		private function force_request_method($method){
			if ($method == 'GET'){
				if (!$this->getRequest()->isGet()){
					$this->helper->buildJson('Access Denied. Please use GET method for your request.',false);
					die();
				}
			}
			else if ($method == 'POST'){
				if (!$this->getRequest()->isPost()){
					$this->helper->buildJson('Access Denied. Please use POST method for your request.',false);
					die();
				}	
			}
		}
	
		public function puntenAction(){
			$this->force_request_method('POST');
			$params = $this->getRequest()->getParams();
			$params['grant_type'] = 'client_credentials';
			$baseEndPoint = 'internalapi/processoauth2/unlock';
			$restData = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint,$params,'POST');
			$this->helper->returnJson($restData);
		}
		public function refreshAction(){
			$params = $this->getRequest()->getParams();
			$params['grant_type'] = 'refresh_token';
			$baseEndPoint = 'internalapi/processoauth2/refresh';
			$restData = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint,$params,'POST');
			$this->helper->returnJson($restData);
		}
	}
 ?>