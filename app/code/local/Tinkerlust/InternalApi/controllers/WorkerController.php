<?php 
	
	class Tinkerlust_InternalApi_WorkerController extends Mage_Core_Controller_Front_Action
	{
		private $helper;
		protected function _construct()
	  	{
	  		$this->helper = Mage::helper('internalapi');
	  	}

	  	/* FOR VENDOR! */
		public function attributelistAction(){
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processworker/attributelist';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint,$params,'POST');
			$this->helper->returnJson($result);
		}

		public function categorylistAction(){
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processworker/categorylist';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint,$params,'POST');
			$this->helper->returnJson($result);
		}


		/* add products to a category*/
		public function randomizenewarrivalAction(){
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processworker/randomizenewarrival';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint,$params,'POST');
			$this->helper->returnJson($result);
		}

		/* add products to a category*/
		public function insertproducttocategoryAction(){
			header('Access-Control-Allow-Origin: *');
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processworker/insertproducttocategory';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint,$params,'POST');
			$this->helper->returnJson($result);
		}

	}
?>