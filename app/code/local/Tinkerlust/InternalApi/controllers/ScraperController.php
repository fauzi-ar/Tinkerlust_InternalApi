<?php

    class Tinkerlust_InternalApi_ScraperController extends Mage_Core_Controller_Front_Action
    {
        private $helper;
        protected function _construct()
        {
            $this->helper = Mage::helper('internalapi');
        }

       	public function attributesetAction(){
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processscraper/attributeset';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint, $params,'POST');
			$this->helper->returnJson($result);
		}

        public function attributeAction(){
            $params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processscraper/attribute';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint, $params,'POST');
			$this->helper->returnJson($result);
        }

        /* Create Item */
		public function createitemAction(){
			$this->force_request_method('POST');
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processrest/createitem';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint, $params,'POST');
			$this->helper->returnJson($result);
		}
    }

?>