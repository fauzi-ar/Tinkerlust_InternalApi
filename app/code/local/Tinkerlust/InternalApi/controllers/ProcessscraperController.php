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
		// public function createbrand($brand){
		// 	$brandOption = $brand;
		// 	$attributeModel = Mage::getModel('catalog/resource_eav_attribute');
		// 	$attribute = $attributeModel->loadByCode('catalog_product', 'brand');
		// 	$attributeId = $attribute->getAttributeId();

		// 	$option['attribute_id'] = $attributeId;
		// 	$option['value']['any_option_name'][0] = $brandOption;

		// 	$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
		// 	$setup->addAttributeOption($option);
		// 	$lastId = $setup->getConnection()->lastInsertId();
		// 	$attr = Mage::getModel('eav/entity_attribute_option')
		// 		->getCollection()
		// 		->setStoreFilter()
		// 		->addFieldToFilter('tsv.value_id', array('eq'=>$lastId))
		// 		->getFirstItem();
		// 	$optionId = $attr->getData('option_id');
		// 	return $optionId;
		// }

		public function createAttribute($attributeCode, $attributeName) {
			$attributeModel = Mage::getModel('catalog/resource_eav_attribute');
			$attribute = $attributeModel->loadByCode('catalog_product', $attributeCode);
			$attributeId = $attribute->getAttributeId();

			$option['attribute_id'] = $attributeId;
			$option['value']['any_option_name'][0] = $attributeName;

			$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
			try {
				$setup->addAttributeOption($option);
			} catch(Exception $e) {
				Mage::log('Caught exception: '.$e->getMessage()."\n", null, Scraper.log, true);
			} finally {
				$allOption = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeCode);
				$optionId = $allOption->getSource()->getOptionId($attributeName);
				return $optionId;
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
                $value = preg_replace('/[^\p{L}\p{N}\s]/u', '_', strtolower($instance['label']));
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

		public function addimageAction() {
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
			$params = $this->getRequest()->getParams();
			$sku = $params['sku'];
			$imgCount = $params['image_count'];
			if (empty($imgCount)) {
				$this->helper->buildJson(null,false,"There is no image count given");die();
			}
			$this->check_access_token();
			$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
			if ($product) {
				$imageFolder = $sku;
				$galleryImages = array();
				for ($x=0; $x<$imgCount; $x++) {
					$galleryImages[$x] = $sku . '_' . ($x+1) . '.jpg';
				}
				$product
					->setMediaGellery(array('images' => array(), 'values' => array())); // Init media gallery
				foreach ($galleryImages as $key => $img) {

					if (!file_exists(Mage::getBaseDir('media') . DS . 'import' . DS . $img)) {
						if ($key == 0) {
							try {
								$product->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . DS . $sku . DS . $img, array('image', 'thumbnail', 'small_image'), false, false);
							} catch (Exception $e) {
								// $this->helper->buildJson(null,false,$e->getMessage());die();
							}	
						}
						else {
							try {
								$product->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . DS . $sku . DS . $img, null, false, false);
							} catch (Exception $e) {
								// $this->helper->buildJson(null,false,$e->getMessage());die();
							}
						}
					}
					else {
						echo 'File exists!';
					}	
				}
				$product->save();
				$this->helper->buildJson(array('result' => 'Image added successfully'));
			} else {
				$this->helper->buildJson(null,false,"Product SKU is not valid");die();
			}
		}

		public function updateitemAction() {
			$params = $this->getRequest()->getParams();
			$this->check_access_token();
			$qty = $params['qty'];
			$price = $params['price'];
			$sku = $params['sku'];
			$category = $params['category'];
			$subcategory = $params['subcategory'];
			$short_description = $params['short_description'];
			$status = $params['status'];
			$fabric = $params['fabric'];
			$material = $params['material'];
			$item = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
			if ($item) {
				try {
					$item
						->setData('qty', $qty)
						->setData('price', $price)
						->setData('material', $material)
						->setData('fabric', $fabric)
						->setCategoryIds(array($category, $subcategory))
						->setData('short_description', $short_description);

					if($status) {
						$item->setData('status', 1);
					} else {
						$item->setData('status', 2);
					}
					$item->getResource()->save($item);
				} catch(Exception $e) {
					$this->helper->buildJson(null,false,$e->getMessage());die();
				} finally {
					try {
						$stockItem = Mage::getModel('cataloginventory/stock_item');
						$stockItem->assignProduct($item);
						$stockItem->setData('qty', $qty);
						$stockItem->save();
					} catch(Exception $e) {
						$this->helper->buildJson(null,false,$e->getMessage());die();
					} finally {
						$message = array('Status' => 'Item Updated!');
						$this->helper->buildJson($message);
					}
				}
			} else {
				$this->helper->buildJson(null,false,"Items not found");die();
			}
		}

		public function searchbrandAction() {
			$params = $this->getRequest()->getParams();
			$this->check_access_token();
			$brand = $params['brand'];
			$allBrand = Mage::getModel('eav/config')->getAttribute('catalog_product', 'brand');
			$brandId = $allBrand->getSource()->getOptionId($brand);
			if ($brandId) {
				$brandResult = array($brand => $brandId);
				$this->helper->buildJson($brandResult);
			}
			else {
				$brandId = $this->createAttribute('brand', $brand);
				$brandResult = array($brand => $brandId);
				$this->helper->buildJson($brandResult);
			}
		}

		public function searchmaterialAction() {
			$params = $this->getRequest()->getParams();
			$this->check_access_token();
			$material = $params['material'];
			$allMaterial = Mage::getModel('eav/config')->getAttribute('catalog_product', 'material');
			$materialId = $allMaterial->getSource()->getOptionId($material);
			if ($materialId) {
				$materialResult = array($material => $materialId);
				$this->helper->buildJson($materialResult);
			}
			else {
				$materialId = $this->createAttribute('material', $material);
				$materialResult = array($material => $materialId);
				$this->helper->buildJson($materialResult);
			}
		}

		public function createitemAction(){

			$params = $this->getRequest()->getParams();

			$map_category = array(
				'31' 	=> '1', // Dress
				'32' 	=> '2', // Atasan
				'34' 	=> '6', // Outerwear
				'35' 	=> '4', // Bawahan
				'2659' 	=> '15', // Swimsuit
				'4' 	=> '11', // Tas
				'47' 	=> '13', // Aksesoris
				'10' 	=> '12', // Sepatu
				'1564' 	=> '14', // Make Up
			);

			$map_subcategory = array(
				'97' => 'MD', // Mini Dress
				'90' => 'MI', // Midi Dress
				'96' => 'LD', // Long Dress
				'86' => 'BL', // Blouse
				'87' => 'KO', // Kaos
				'88' => 'ZW', // Sweater
				'98' => 'SV', // Sleeveless
				'99' => 'KJ', // Kemeja
				'91' => 'BZ', // Blazer
				'92' => 'JC', // Jaket
				'100' => 'CR', // Cardigan
				'101' => 'VT', // Vest
				'102' => 'CT', // Coat
				'155' => 'OW', // Outerwear

				'89' => 'SK', // Rok
				'93' => 'PA', // Celana

				'23' => 'BT', // Boots
				'24' => 'FL', // Flats
				'27' => 'HL', // Heels
				'28' => 'SD', // Sandals
				'30' => 'SR', // Sneakers
				'29' => 'WG', // Wedges

				'43' => 'WL', // Dompet
				'38' => 'BT', // Ikat Pinggang
				'44' => 'WC', // Jam Tangan
				'42' => 'SG', // Kacamata
				'45' => 'KY', // Keychain
				'39' => 'JW', // Perhiasan
				'41' => 'SS', // Scarf

				'19' => 'BP', // Backpack
				'17' => 'CL', // Clutch
				'243' => 'HB', // Handbag
				'22' => 'LT', // Travel / Luggage
				'40' => 'PC', // Pouch
				'20' => 'SA', // Satchel
				'21' => 'CB', // Sling Bag
				'18' => 'SB', // Shoulder Bag
				'16' => 'TB', // Tote Bag

				'1565' => 'SP', // Sets and Pallette
				'1566' => 'LP', // Lips
				'1567' => 'EY', // Eyes
				'1568' => 'FC', // Faces
				'1604' => 'SN', // Skin Care
				'1605' => 'TO', // Tools
				'1934' => 'FR', // Fragrance
			);
			
			$this->check_access_token();
			$part1 = strval($params['vendor_attribute']);
			// $part1 = $params['sku_prefix'];
			$part2 = 'MP';
			// $part3 = $this->generateSkuMiddlePart($params['category_1'], $params['brand_name']);
			$part3 = $map_category[strval($params['category'])] . $map_subcategory[strval($params['subcategory'])];
			$part4 = Mage::getmodel('catalog/category')->load($params['vendor_category'])->getProductCount() + 1;
			$sku = $part1.'-'.$part2.'-'.$part3.'-'.$part4;
			Mage::log(print_r($params, 1), null, 'scraper.log');
			
			$item = Mage::getModel('catalog/product');
			$item->setStoreId(1)
				 ->setWebsiteIds(array(1))
				 ->setAttributeSetId($params['attribute_set_id'])
				 ->setTypeId('simple')
				 ->setCreatedAt(strtotime('now'))
				 ->setInitialEntryDate(strtotime('now'))

				 ->setSku($sku)
				 ->setName($params['name'])
				 ->setWeight($params['weight'])
				 ->setStatus(1)
				 ->setTaxClassId(0)
				 ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
				 ->setColor($params['color'])
				 ->setPrice($params['price'])
				 
				//  ->setMetaTitle($params['meta_title'])
				//  ->setMetaKeyword($params['meta_keyword'])
				//  ->setMetaDescription($params['meta_description'])
				
				 ->setDescription($params['description']) //we can skip this
				 ->setShortDescription($params['short_description']) //and this too
				 ->setSize($params['size'])
				 ->setCategoryIds(array($params['category'], $params['subcategory'], $params['vendor_category']))
				 // CUSTOM ATTRIBUTE
				 ->setBrand($params['brand_id'])
				 ->setVendor($params['vendor_attribute']) // Dropdown
				 ->setCondition($params['condition'])
				 ->setSource($params['source']);
			try {
				$item->getResource()->save($item);
			} catch(Exception $e) {
				$this->helper->buildJson(null,false,$e->getMessage());die();
			} finally {
				try {
					$stockItem = Mage::getModel('cataloginventory/stock_item');
					$stockItem->assignProduct($item);
					$stockItem->setData('qty', 1);
					$stockItem->setData('is_in_stock', 1);
					$stockItem->setData('stock_id', 1);
					$stockItem->setData('store_id', 1);
					$stockItem->setData('manage_stock', 1);
					$stockItem->setData('use_config_manage_stock', 0);
					$stockItem->save();
				} catch(Exception $e) {
					$this->helper->buildJson(null,false,$e->getMessage());die();
				} finally {
					$status = array('sku' => $sku);
					$this->helper->buildJson($status);
				}
			}
		}
    }

?>