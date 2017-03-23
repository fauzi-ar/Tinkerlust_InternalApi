<?php

    class Tinkerlust_InternalApi_ProcessscraperController extends Mage_Core_Controller_Front_Action
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

        public function attributesetAction(){
            $this->check_access_token();
            $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection')->load();
            $categories = array();

            foreach ($attributeSetCollection as $id=>$attributeSet) {
                $entityTypeId = $attributeSet->getAttributeSetId();
                $name = $attributeSet->getAttributeSetName();
                $categories[$name] = $entityTypeId;
            }
            $this->helper->buildJson($categories);
        }

        public function attributeAction(){
            $params = $this->getRequest()->getParams();
            $code = $params['code'];
            $this->check_access_token();
            $attributes = array();
            $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $code);
            $allOptions = $attribute->getSource()->getAllOptions(true, true);
            foreach ($allOptions as $instance) {
                $id = $instance['value'];
                $value = $instance['label'];
                $attributes[$value] = $id;
            }
            $this->helper->buildJson($attributes);
        }

		public function getcategoryAction(){
			$params = $this->getRequest()->getParams();
			$categoryName = $params['category_name'];
			$this->check_access_token();
			$category = Mage::getResourceModel('catalog/category_collection')
				->addFieldToFilter('name', $categoryName)
				->getFirstItem();
			$categoryPath = $category->getPath();
			$ids = explode('/', $categoryPath);
			$ids = array_slice($ids, 2);
			// print_r(array_slice($input, 2, -1));
			$this->helper->buildJson($ids);
		}

        public function createitemAction(){
			$params = $this->getRequest()->getParams();
			$this->check_access_token();
			$item = Mage::getModel('catalog/product');
			try {
				$item->setStoreId(1)
				 ->setWebsiteIds(array(1))
				 ->setAttributeSetId($params['attribute_set_id'])
				 ->setTypeId('simple')
				 ->setCreatedAt(strtotime('now'))

				 ->setSku($params['sku'])
				 ->setName($params['name'])
				//  ->setWeight($params['weight'])
				 ->setStatus(2)
				 ->setTaxClassId(0)
				 ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
				 ->setColor($params['color'])
				 ->setPrice($params['price'])
				 
				//  ->setMetaTitle($params['meta_title'])
				//  ->setMetaKeyword($params['meta_keyword'])
				//  ->setMetaDescription($params['meta_description'])
				 ->setDescription($params['description'])
				 ->setShortDescription($params['short_description'])

				 ->setStockData(array(
								'use_config_manage_stock' => 1,
								'manage_stock'=>1,
								'min_sale_qty'=>1,
								'max_sale_qty'=>1,
								'qty'=>1
				 ))
				 ->setCategoryIds(array(1019))
				 // CUSTOM ATTRIBUTE
				 ->setVendor($params['vendor']) // Dropdown
				 ->setFabric($params['fabric']) // Multi select
				//  // ATASAN
				//  ->setTopSize($params['top_size']) // Dropdown
				//  ->setTopChest($params['top_chest']) // Free text
				//  ->setTopHip($params['top_hip']) // Free text
				//  ->setTopHeight($params['top_height']) // Free Text
				//  // BAWAHAN
				//  ->setBottomSize($params['bottom_size']) // Dropdown
				//  ->setBottomHip($params['bottom_hip']) // Free text
				//  ->setBottomWaist($params['bottom_waist']) // Free text
				//  ->setBottomHeight($params['bottom_height']) // Free text

				//  ->setShoeSizeUs() // Dropdown
				//  ->setShoeSizeEU() // Dropdown
				//  ->setMakeUpSize()	// Fee text	 
				 ->setCondition($params['condition']); // Dropdown

				$item->save();
			} catch(Exception $e) {
				Mage::log($e->getMessage());
			}			
		}

    }

?>