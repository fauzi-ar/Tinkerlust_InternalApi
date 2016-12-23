<?php 
	class Tinkerlust_InternalApi_Model_Client 
		extends Mage_Core_Model_Abstract 
		implements 
			OAuth2_Storage_ClientCredentialsInterface,
			OAuth2_Storage_UserCredentialsInterface,
			OAuth2_Storage_AccessTokenInterface,
			OAuth2_Storage_RefreshTokenInterface {

		private $_magento_user_id;

		public function _construct(){
			$this->_init('internalapi/client');
		}

		public function checkClientCredentials($client_id, $client_secret = null){
			$client = Mage::getModel('oauth/consumer')->load($client_id,'key');
			if ($client != null){
				if ($client_secret == $client->getData('secret')) return true;
				else return false;
			}
			else return false;
		}

		public function isPublicClient($client_id){
			//always return false
			return false;
		}

		public function getClientDetails($client_id){
			//we don't need no client details!
			return null;
		}

		public function getClientScope($client_id){
			//we don't know what it Scope is.
			return null;
		}

		public function checkRestrictedGrantType($client_id, $grant_type){
			//asume all clients can take any grant type
			return true;
		}

		//Methods from Access Token Interface
		public function getAccessToken($access_token)
		{
		    $token = Mage::getModel('internalapi/token')->load($access_token);
		    
		    if ($token->getData('access_token') != null){
		    	$token->setData('expires',strtotime($token->getData('expires')) );
		    	return $token;
		    }
		    else {
		    	return false;
		    }
		    
		}

		public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null, $id_token = null)
		{
			$tokenModel = Mage::getModel('internalapi/token');
			$data = array('access_token' => $access_token,'client_id'=>$client_id,'user_id'=>$user_id,'expires' => $expires, 'scope' => $scope); 
			$tokenModel->setData($data);
			$tokenModel->save();
		    return true;
		}

		public function checkUserCredentials($username, $password)
		{
			$customer = Mage::getModel('customer/customer');
			$customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
			$customer->loadByEmail($username);

			if ($customer->validatePassword($password)){
				$this->_magento_user_id = $customer->getId();
				return true;
			}
			else return false;
		}	

		public function getUserDetails($username){
			$customer = Mage::getModel('customer/customer');
			$customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
			$customer->loadByEmail($username);
			return array('user_id' => $customer->getId());
		}

	    public function getRefreshToken($refresh_token)
	    {
	    	$new_refresh_token = Mage::getModel('internalapi/refresh')->load($refresh_token);
		    
		    if ($new_refresh_token->getData('refresh_token') != null){
		    	$new_refresh_token->setData('expires',strtotime($new_refresh_token->getData('expires')) );
		    	return $new_refresh_token;
		    }
		    else {
		    	return false;
		    }
	    }

	    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null)
	    {
	    	$refreshModel = Mage::getModel('internalapi/refresh');
			$data = array('refresh_token' => $refresh_token,'client_id'=>$client_id,'user_id'=>$user_id,'expires' => $expires, 'scope' => $scope); 
			$refreshModel->setData($data);
			$refreshModel->save();
		    return true;
	    }

	    public function unsetRefreshToken($refresh_token)
	    {
	    	$refreshModel = Mage::getModel('internalapi/refresh')->load($refresh_token);
			$refreshModel->delete();
		    return true;
	    }

	}
?>