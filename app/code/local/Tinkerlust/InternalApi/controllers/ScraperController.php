<?php

    class Tinkerlust_InternalApi_ScraperController extends Mage_Core_Controller_Front_Action
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

       	public function attributesetAction(){
			$this->force_request_method('GET');
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processscraper/attributeset';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint, $params,'POST');
			$this->helper->returnJson($result);
		}

        public function attributeAction(){
			$this->force_request_method('GET');
            $params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processscraper/attribute';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint, $params,'POST');
			$this->helper->returnJson($result);
        }

        /* Create Item */
		public function createitemAction(){
			$this->force_request_method('POST');
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processscraper/createitem';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint, $params,'POST');
			$this->helper->returnJson($result);
		}

		// Get Category By Name
		public function categoryidAction(){
			$this->force_request_method('GET');
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processscraper/categoryid';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint, $params, 'POST');
			$this->helper->returnJson($result);
		}

		// Create New Brand Option
		public function createbrandAction(){
			$this->force_request_method('POST');
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processscraper/createbrand';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint, $params, 'POST');
			$this->helper->returnJson($result);
		}
		// Add item image
		public function addimageAction(){
			$this->force_request_method('POST');
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processscraper/addimage';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint, $params, 'POST');
			$this->helper->returnJson($result);
		}
		// Update item
		public function updateitemAction(){
			$this->force_request_method('POST');
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processscraper/updateitem';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint, $params, 'POST');
			$this->helper->returnJson($result);
		}
		// Search Brand
		public function searchbrandAction(){
			$this->force_request_method('GET');
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processscraper/searchbrand';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint, $params, 'POST');
			$this->helper->returnJson($result);
		}
    }

?>