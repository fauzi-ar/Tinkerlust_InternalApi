<?php 
	class Tinkerlust_InternalApi_ProcessworkerController extends Mage_Core_Controller_Front_Action
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

		public function attributelistAction(){
			$this->check_access_token();
			$params = $params = $this->getRequest()->getParams();
			if (isset($params['attribute_code'])){
				$attribute = Mage::getSingleton('eav/config')
				    ->getAttribute(Mage_Catalog_Model_Product::ENTITY, $params['attribute_code']);

				if ($attribute->usesSource()) {
				    $options = $attribute->getSource()->getAllOptions(false);
				    $this->helper->buildJson($options);
				}
				else {
					$this->helper->buildJson(null,false,"Attribute '" . $params['attribute_code'] . "' does not exist, or it doesn't have any options");
				}
			}
			else {
				$this->helper->buildJson(null,false,"attribute_code isn't defined in query string.");	
			}
		}

		public function categorylistAction(){
			$this->check_access_token();
			$params = $params = $this->getRequest()->getParams();
			if (isset($params['category_parent'])){

				$cat = Mage::getModel('catalog/category')->load($params['category_parent']);
				$subCats = $cat->getChildrenCategoriesWithInactive();

				$category_json_data = [];
				foreach ($subCats as $category){
						
					$categoryData = $category->getData();
					unset($categoryData['entity_type_id']);
					unset($categoryData['attribute_set_id']);
					unset($categoryData['parent_id']);
					unset($categoryData['created_at']);
					unset($categoryData['updated_at']);
					unset($categoryData['path']);
					unset($categoryData['position']);
					unset($categoryData['level']);
					unset($categoryData['children_count']);
					unset($categoryData['request_path']);
					unset($categoryData['is_anchor']);

					$category_json_data[] = $categoryData;

				}
				$this->helper->buildJson($category_json_data);	
			}
			else {
				$this->helper->buildJson(null,false,"category_parent isn't defined in query string.");	
			}
		}


		public function randomizenewarrivalAction(){
			$this->check_access_token();
			$params = $this->getRequest()->getParams();

			if (isset($params['replace']) && $params['replace'] == true) {
				//unset current new arrival products
				$newArrivalCategory = Mage::getModel('catalog/category')->load(8);
				$newArrivalCategory->setPostedProducts(array());
				$newArrivalCategory->save();
			}
			
			$num_of_items = 
				(isset($params['numofitems']) && $params['numofitems'] > 0 && $params['numofitems'] <= 240)?
				$params['numofitems'] : 240;

			$page = 
				(isset($params['offset']) && $params['offset'] > 0)?
				($params['offset']+1) : 1;



			$productsCollection = Mage::getModel('catalog/product')->getCollection()
				->setPageSize($num_of_items)
				->setCurPage($page)
				->addAttributeToFilter('sku',array('nlike' => '%-mp-%'))
				->addAttributeToFilter('status',1)
				->addAttributeToFilter('visibility',4)
				->addAttributeToSort('entity_id','desc');
			Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($productsCollection);


			//SET NEW ARRIVAL PRODUCTS
			$counter = 0;
			$category = Mage::getModel('catalog/category')->load(8);
			$postedProducts = $category->getProductsPosition();
			foreach($productsCollection as $item){
				$postedProducts[$item->getId()] = ++$counter;
			}
			$category->setPostedProducts($postedProducts);
			$category->save();
			$this->helper->buildJson($counter . ' of products have been added to New Arrival category');
		}

		public function insertproducttocategoryAction(){
			$this->check_access_token();
			$params = $this->getRequest()->getParams();
			
			if (!isset($params['sku']) || $params['sku'] == '' || 
				!isset($params['category_id']) || $params['category_id'] == '') {
				$this->helper->buildJson('skus or category_id is not found.',null,false);
			}
			$category = Mage::getModel('catalog/category')->load($params['category_id']);
			
			if ($category->getId()){
				$postedProducts = $category->getProductsPosition();
				$sku = $params['sku'];
				$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
				if ($product && $product->getId()){
					$postedProducts[$product->getId()] = 1;
				}
				$category->setPostedProducts($postedProducts);
				$category->save();
				$this->helper->buildJson($sku . ' have been added to category with ID=' . $params['category_id']);
			}
			else {
				$this->helper->buildJson('category does not exist.',null,false);	
			}
		}

	}
 ?>