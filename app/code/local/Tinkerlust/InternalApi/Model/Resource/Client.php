<?php
class Tinkerlust_InternalApi_Model_Resource_Client extends Mage_Core_Model_Resource_Db_Abstract
{
	protected function _construct()
	{
		$this->_init('internalapi/client','id');
	}	
	
}
?>