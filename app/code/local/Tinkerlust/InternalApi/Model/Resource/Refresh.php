<?php
class Tinkerlust_InternalApi_Model_Resource_Refresh extends Mage_Core_Model_Resource_Db_Abstract
{
	protected function _construct()
	{
		$this->_init('internalapi/refresh','refresh_token');
		$this->_isPkAutoIncrement = false;
	}	
}
?>