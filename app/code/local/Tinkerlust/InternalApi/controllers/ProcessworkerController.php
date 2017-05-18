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
			
			if (!isset($params['product_ids']) || $params['product_ids'] == '' || 
				!isset($params['category_id']) || $params['category_id'] == '') {
				$this->helper->buildJson('skus or category_id is not found.',null,false);
			}
			$category = Mage::getModel('catalog/category')->load($params['category_id']);
			
			if ($category->getId()){
				$postedProducts = $category->getProductsPosition();
				$ids = $params['product_ids'];
				$this->helper->buildJson($ids);
				$counter = 0;
				foreach ($ids as $id){
					$postedProducts[$id] = ++$counter;
				}
						
				$category->setPostedProducts($postedProducts);
				$category->save();
				$this->helper->buildJson($counter . ' products have been added to category with ID=' . $params['category_id']);
			}
			else {
				$this->helper->buildJson('category does not exist.',false,null);	
			}
		}

		public function getallscrapperproductAction(){
			$this->check_access_token();
			$params = $this->getRequest()->getParams();
			$collection = Mage::getModel('catalog/product')->getCollection()
					->addAttributeToFilter('sku',array('like'=>'%-mp-%'))
					->addAttributeToSelect('sku');
			$data = [];
			foreach ($collection as $product){
				$data[$product->getSku()] = $product->getId();
			}

			$this->helper->buildJson($data);
		}

		public function cekproductavailabilitybyskuAction(){
			$this->check_access_token();
			$params = $this->getRequest()->getParams();
			$sku = $params['sku'];

			if ($sku){
				$id = Mage::getModel('catalog/product')->getResource()->getIdBySku($sku);
				if ($id){
					$this->helper->buildJson($id);
				}
				else {
					$this->helper->buildJson(null, false, "product_not_found");
				}
			}
			else {
				$this->helper->buildJson(null, false, "no sku.");
			}
		}

		public function createproductAction(){
			$this->check_access_token();
			$params = $this->getRequest()->getParams();
			$product_data = (array) json_decode($params['product']);
			$product = Mage::getModel('catalog/product')
				->setWebsiteIds(array(1))
				->setAttributeSetId( $product_data['set_id']  )
				->setTypeId('simple')
				->setSku($product_data['sku'])
				->setName($product_data['name'])
				->setBrand($product_data['brand'])
				->setColor($product_data['color'])
				->setCondition($product_data['kondisi'])
				->setVendor($product_data['vendor'])
				->setPrice($product_data['price'])
				->setStatus(0)
				->setStockData(array(
                      'use_config_manage_stock' => 0, //'Use config settings' checkbox
                      'manage_stock'=>1, //manage stock
                      'min_sale_qty'=>1, //Minimum Qty Allowed in Shopping Cart
                      'max_sale_qty'=>2, //Maximum Qty Allowed in Shopping Cart
                      'is_in_stock' => 1, //Stock Availability
                      'qty' => $product_data['qty'] //qty
					)
				)
				->setCategoryIds(array($product_data['category1'], $product_data['category2']));
			$product->save();
			$this->helper->buildJson("Success");
		}

		public function setproductstatusAction(){
			$this->check_access_token();
			$params = $this->getRequest()->getParams();
			
			if (isset($params['cat'])){
				$catId = $params['cat'];
			}

			//Just to secure the code so that it's not used to change the status of categories other then returned item,
			//override any param of category id
			$catId = 2198;

			if (isset($params['status'])){
				if ($params['status'] == 'enabled') $status = 1;
				else if ($params['status'] == 'disabled') $status = 2;
			}

			if ($status && $catId){
				//do the code
				$products = Mage::getResourceModel('catalog/product_collection')
				->joinField(
				        'category_id', 'catalog/category_product', 'category_id', 
				        'product_id = entity_id', null, 'left'
				    )
				->addAttributeToSelect('*')
				->addAttributeToFilter('category_id', array('eq' => $catId));
				 
				if ($status == 1) {
					$statusCode = Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
					$statusLabel = 'Enabled';
				}
				else if ($status == 2) {
					$statusCode = Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
					$statusLabel = 'Disabled';	
				}

				$msg = array();
				$jum = 0;
				foreach ($products as $product){
					$id = $product->getId();

					if ($statusCode){
						$jum++;
						Mage::getModel('catalog/product_status')->updateProductStatus($id, 0, $statusCode);
						$msg[] = $product->getSku() . " has been $statusLabel";
					}
				}
				$this->helper->buildJson($msg,true,$jum);
			}
			else {
				$this->helper->buildJson(null, false, "please set the desired status and category id.");
			}
		}


	}
 ?>