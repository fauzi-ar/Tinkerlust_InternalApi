<?php 
	
	class Tinkerlust_InternalApi_WorkerController extends Mage_Core_Controller_Front_Action
	{
		private $helper;
		protected function _construct()
	  	{
	  		$this->helper = Mage::helper('internalapi');
	  	}

		public function randomizenewarrivalAction(){
			$params = $this->getRequest()->getParams();
			$baseEndPoint = 'internalapi/processworker/randomizenewarrival';
			$result = $this->helper->curl(Mage::getBaseUrl() . $baseEndPoint,$params,'POST');
			$this->helper->returnJson($result);
		}

	}
?>