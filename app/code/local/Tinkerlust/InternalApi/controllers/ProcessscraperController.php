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
		public function generateSkuMiddlePart($cat_id, $brand){
			$brand = preg_replace("/[^A-Za-z0-9 ]/","",$brand);
			$brand = preg_replace("/\s+/"," ",$brand);
			$brand = trim($brand);
			$bits = explode(" ",$brand);
			if (sizeof($bits) == 1){
				$acronym = substr($brand, 0,3);
			}
			else {
				$acronym = "";
				foreach ($bits as $bit){
					$acronym .= $bit[0];
				}
			}
			return strtoupper($acronym) . $cat_id;
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
                $value = preg_replace('/[^\p{L}\p{N}\s]/u', '', strtolower($instance['label']));
                $attributes[$value] = $id;
            }
            $this->helper->buildJson($attributes);
        }

		public function categoryidAction(){
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

		public function createbrandAction(){
			$params = $this->getRequest()->getParams();
			$brandOption = $params['brand_option'];
			$this->check_access_token();
			$attributeModel = Mage::getModel('catalog/resource_eav_attribute');
			$attribute = $attributeModel->loadByCode('catalog_product', 'brand');
			$attributeId = $attribute->getAttributeId();

			$option['attribute_id'] = $attributeId;
			$option['value']['any_option_name'][0] = $brandOption;

			$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
			$setup->addAttributeOption($option);
		}

		public function addimageAction() {
			$params = $this->getRequest()->getParams();
			$itemName = $params['item_name'];
			$imgCount = $params['image_count'];
			$this->check_access_token();
			$product = Mage::getModel('catalog/product')->loadByAttribute('name', $itemName);
			$name = strtolower(str_replace(' ', '_', $itemName));
			$imageFolder = $name;
			$galleryImages = array();
			for ($x=0; $x<$imgCount; $x++) {
				$galleryImages[$x] = $name . '_' . ($x+1) . '.jpg';
			}
			$product
				->setMediaGellery(array('images' => array(), 'values' => array())); // Init media gallery
			foreach ($galleryImages as $key => $img) {
				try {
					if (!file_exists(Mage::getBaseDir('media') . DS . 'import' . DS . $img)) {
						if ($key == 0) {
							$product
								->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . DS . $name . DS . $img, array('image', 'thumbnail', 'small_image'), false, false);
							$product->save();
						}
						else {
							$product
								->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . DS . $name . DS . $img, null, false, false);
							$product->save();
						}
					}
					else {
						echo 'File exists!';
					}
				} catch (Exception $e) {
					Mage::log('Caught exception: '.$e->getMessage()."\n", null, Scraper.log, true);
					// $this->helper->buildJson($e->getMessage());
				}
			}
			$this->helper->buildJson(array('result' => 'Image added successfully'));
		}

        public function createitemAction(){
			$params = $this->getRequest()->getParams();
			
			$this->check_access_token();
			// SKU
			// $brand = $params['brand'];
			$part1 = $params['sku_prefix'];
			$part2 = 'MP';
			$part3 = $this->generateSkuMiddlePart($params['category_1'], $params['brand_name']);
			$part4 = Mage::getmodel('catalog/category')->load($params['vendor'])->getProductCount() + 1;
			$sku = $part1.'-'.$part2.'-'.$part3.'-'.$part4;
			Mage::log(print_r($params, 1), null, 'scraper.log');
			// Mage::getModel('catalog/category')->getCollection();
			
			$item = Mage::getModel('catalog/product');
			$item->setStoreId(1)
				 ->setWebsiteIds(array(1))
				 ->setAttributeSetId($params['attribute_set_id'])
				 ->setTypeId('simple')
				 ->setCreatedAt(strtotime('now'))
				 ->setInitialEntryDate(strtotime('now'))

				 ->setSku($sku)
				 ->setName($params['name']) // Brand + Name
				 ->setWeight($params['weight']) // Default setting based on base categoryidAction
				 ->setStatus(2)
				 ->setTaxClassId(0)
				 ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
				 ->setColor($params['color'])
				 ->setPrice($params['price'])
				 
				//  ->setMetaTitle($params['meta_title'])
				//  ->setMetaKeyword($params['meta_keyword'])
				//  ->setMetaDescription($params['meta_description'])
				
				 ->setDescription($params['description']) //we can skip this
				 ->setShortDescription($params['short_description']) //and this too

				 ->setStockData(array(
								'use_config_manage_stock' => 1,
								'manage_stock'=>1,
								'min_sale_qty'=>1,
								'max_sale_qty'=>1,
								'qty'=>1
				 ))
				 ->setCategoryIds($params['category_1'], $params['category_2'])
				 // CUSTOM ATTRIBUTE
				 ->setBrand($params['brand_id'])
				 ->setVendor($params['vendor']) // Dropdown
				 ->setCondition($params['condition'])
				 ->setSource($params['source']);
				//  ->setFabric($params['fabric']) // Multi select
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
				// Dropdown
			try {
				$item->save();
				$status = array('status' => 'item sucessfully created');
				$this->helper->buildJson($status);
			} catch(Exception $e) {
				$this->helper->buildJson($e->getMessage());
			}			
		}

    }

?>