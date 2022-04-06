<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Remarks extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html 
	 */

	// Hàm khởi tạo
	function __construct()
	{
		// Gọi đến hàm khởi tạo của cha
		parent::__construct();
		//$this->load->helper(array('url', 'form'));
		//$this->load->library('form_validation');
		$this->load->model('common_remark_condition_codes', 'condition_codes');
		$this->load->model('common_remarks', 'remarks');

		// get data default
        $this->production_line = null != get_cookie('plan_department') ? get_cookie('plan_department') : 'htl';
		$this->updated_by = null !== get_cookie('plan_loginUser') ? get_cookie('plan_loginUser') : '';
		
	}

	public function index()
	{
		$this->_data['title'] = 'Remarks Tool';
		$this->load->view('remarks/index', $this->_data );
	}

	public function readProduction($production_line) {
        // select all query
		$this->db->select('*');
		$this->db->where('production_line', $production_line);
        return $this->db->get($this->_table)->result_array();
    }
	
	//  Hiển thị table remark đã làm lệnh (hiển thị trên tờ lệnh)
	public function remark_save()
	{
		$this->load->view('remarks/remark_save');
	}
	
	//  Hiển thị table remark đã tạo (để làm lệnh kiểm tra)
	public function remark_view()
	{
		$this->load->view('remarks/views');
	}

	//  Hiển thị table điều kiện  đã tạo (để tạo remark)
	public function remark_conditions()
	{
		$this->load->view('remarks/views');
	}

	public function createConditionCode() 
	{
		
		$this->_data['title'] = 'Create Condition Codes';

		$condition_code = $this->condition_codes->getLastCode();
		if (empty($condition_code )) {
			$code = 'C0001';
		} else {
			// add + 1
			$suffCode = (int)substr($condition_code, 1,4) + 1;

			if (strlen($suffCode) == 1 ) {
				$suffCodeNew = '000' . $suffCode;
			} else if (strlen($suffCode) == 2 ) {
				$suffCodeNew = '00' . $suffCode;
			} else if (strlen($suffCode) == 3 ) {
				$suffCodeNew = '00' . $suffCode;
			} else if (strlen($suffCode) == 4 ) {
				$suffCodeNew = $suffCode;
			}

			$code = 'C' . $suffCodeNew;
		}

		return $code;

	}

	public function saveCondition() 
	{

		$this->_data['title'] = 'Save Condition';

		// post data
		$data= isset($_POST["data"]) ? $_POST["data"] : '' ;
		// $data = '{"rbo":1,"ship_to_customer":1,"bill_to_customer":1,"internal_item":0,"ordered_item":0,"order_type_name":0,"material_code":0,"ink_code":0,"packing_instr":0}';
		$data = json_decode($data,true);
		
		// check empty
		if (empty($data) ) {
			
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Save data empty'
			);
			echo json_encode($this->_data['results'],JSON_UNESCAPED_UNICODE); exit();

		} else {

			// get condition note
			$condition_note = '';
			foreach ($data as $keyCheck => $valueCheck ) {

				if ($keyCheck == 'rbo' && (int)$valueCheck == 1 ) {
					$condition_note .= '- RBO ';
				}

				if ($keyCheck == 'ship_to_customer' && (int)$valueCheck == 1 ) {
					$condition_note .= '- Ship To Customer ';
				}

				if ($keyCheck == 'bill_to_customer' && (int)$valueCheck == 1 ) {
					$condition_note .= '- Bill To Customer ';
				}

				if ($keyCheck == 'internal_item' && (int)$valueCheck == 1 ) {
					$condition_note .= '- Internal Item ';
				}

				if ($keyCheck == 'ordered_item' && (int)$valueCheck == 1 ) {
					$condition_note .= '- Ordered Item ';
				}

				if ($keyCheck == 'order_type_name' && (int)$valueCheck == 1 ) {
					$condition_note .= '- Order Type Name ';
				}

				if ($keyCheck == 'material_code' && (int)$valueCheck == 1 ) {
					$condition_note .= '- Material Code ';
				}

				if ($keyCheck == 'ink_code' && (int)$valueCheck == 1 ) {
					$condition_note .= '- Ink Code ';
				}

				if ($keyCheck == 'packing_instr' && (int)$valueCheck == 1 ) {
					$condition_note .= '- Packing Instr';
				}
				
			}

			$code = $this->createConditionCode();
			$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

			$condition_code_arr = $this->condition_codes->read();
			if (empty($condition_code_arr) ) {

				$insert = array(
					'condition_code' => $code,
					'condition_rules_json' => json_encode($data),
					'condition_note' => $condition_note,
					'updated_by' => $updated_by
				);

			} else {
				
				// Check 
				foreach ($condition_code_arr as $condition_value ) {
					
					$condition_rules_json = json_decode($condition_value['condition_rules_json']);
					
					$count = 0;
					foreach ($condition_rules_json as $k => $json_value ) {

						foreach ($data as $key => $value ) {
							if ($k == $key &&  (int)$json_value == (int)$value ) {
								$count++;
							} 
						}
					}

					if ($count == 9 ) {
						$this->_data['results'] = array(
							'status' => false,
							'message' => 'Đã tồn tại điều kiện này: ' . $condition_note
						);
						echo json_encode($this->_data['results'],JSON_UNESCAPED_UNICODE); exit();
						break;
					}
					
				}

				// inser arr
				$insert = array(
					'condition_code' => $code,
					'condition_rules_json' => json_encode($data),
					'condition_note' => $condition_note,
					'updated_by' => $updated_by
				);


			}

			if (!empty($insert) ) {
				$results = $this->condition_codes->insert($insert );
				if ($results == false ) {
					$this->_data['results'] = array(
						'status' => false,
						'message' => 'Save data error'
					);
					echo json_encode($this->_data['results'],JSON_UNESCAPED_UNICODE); exit();
				} else {
					$this->_data['results'] = array(
						'status' => true,
						'message' => 'Save Data Success'
					);
					echo json_encode($this->_data['results'],JSON_UNESCAPED_UNICODE); exit();
				}
			} else {
				$this->_data['results'] = array(
					'status' => false,
					'message' => 'Get Save Data Error'
				);
				echo json_encode($this->_data['results'],JSON_UNESCAPED_UNICODE); exit();
			}

			
		}


	}

	
	public function mainGrid($master )
    {
        // tilte default
        $this->_data['title'] = 'Load Data';

        // XML header
        header('Content-type: text/xml');

        // open
        echo "<rows>";

			if ($master == 'view_remarks' ) {

				// tilte 
                $this->_data['title'] = 'Load Remark Views';

				// header
                $header = '<head>
                            <column width="50" type="ro" align="center" sort="str">No.</column>

                            <column width="120" type="ro" align="center" sort="str">Mã Điều kiện</column>
                            <column width="250" type="ro" align="center" sort="str">Điều kiện</column>
                            <column width="*" type="ed" align="center" sort="str">Nội dung Remark (E)</column>

                            <column width="150" type="ro" align="center" sort="str">Người cập nhật</column>
                            <column width="150" type="ro" align="center" sort="str">Ngày cập nhật</column>
                            
                            <column width="70" type="acheck" align="center" sort="str">Save</column>
                            <column width="70" type="acheck" align="center" sort="str">Delete</column>
                        </head>';

                echo $header;


				if ($this->remarks->countAll() > 0 ) {

					// set data
                    $index = 0;
					$data = $this->remarks->readProductionLine($this->production_line); 
                    foreach ($data as $key => $item) {

						$conditions = json_decode($item['conditions']);
						$codition_label = '';
						$codition_label .= isset($conditions->rbo) ? (trim($conditions->rbo) . '-') : '';
						$codition_label .= isset($conditions->ship_to_customer) ? (trim($conditions->ship_to_customer) . '-') : '';
						$codition_label .= isset($conditions->bill_to_customer) ? (trim($conditions->bill_to_customer) . '-') : '';
						$codition_label .= isset($conditions->internal_item) ? (trim($conditions->internal_item) . '-') : '';
						$codition_label .= isset($conditions->ordered_item) ? (trim($conditions->ordered_item) . '-') : '';
						$codition_label .= isset($conditions->order_type_name) ? (trim($conditions->order_type_name) . '-') : '';
						$codition_label .= isset($conditions->material_code) ? (trim($conditions->material_code) . '-') : '';
						$codition_label .= isset($conditions->ink_code) ? (trim($conditions->ink_code) . '-') : '';
						$codition_label .= isset($conditions->packing_instr) ? trim($conditions->packing_instr) : '';

				
                        $index++;

                        echo '<row id="' . $key . '">';
                            echo '<cell>' . $index . '</cell>';

                            echo '<cell>' . str_replace("&", "&amp;", strtoupper($item['condition_code']) ) . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $codition_label) . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['remark']) . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['updated_by']) . '</cell>';
                            echo '<cell>' . $item['updated_date'] . '</cell>';

                            echo '<cell></cell>';
                            echo '<cell></cell>';
                        echo '</row>';
                    }

				}


			} else if ($master == 'view_conditions' ) {

				// tilte 
				$this->_data['title'] = 'Load Condition Views';

				// header
                $header = '<head>
                            <column width="50" type="ro" align="center" sort="str">No.</column>

                            <column width="140" type="ro" align="center" sort="str">Mã Điều kiện</column>
                            <column width="*" type="ro" align="center" sort="str">Nội dung Điều kiện</column>
                            <column width="180" type="ro" align="center" sort="str">Người cập nhật</column>
                            <column width="180" type="ro" align="center" sort="str">Ngày cập nhật</column>
                            
                        </head>';

                echo $header;


				if ($this->condition_codes->countAll() > 0 ) {
					

					// get data
					$index = 0;
					$data = $this->condition_codes->read();
					foreach ($data as $key => $item) {
						$index++;

                        echo '<row id="' . $key . '">';
                            echo '<cell>' . $index . '</cell>';

                            echo '<cell>' . str_replace("&", "&amp;", strtoupper($item['condition_code']) ) . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['condition_note']) . '</cell>';
                            
                            echo '<cell>' . str_replace("&", "&amp;", $item['updated_by']) . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['updated_date']) . '</cell>';

                        echo '</row>';

					}

				}


			}

		// close
        echo "</rows>";


	}


	// // public function loadRemarks() 
	// // {
		
	// // 	$this->_data['title'] = 'Load Remark Views';

	// // 	$results = array();

	// // 	$productionLine = isset($_COOKIE['plan_department']) ? strtolower($_COOKIE['plan_department']) : '';

	// // 	if ($this->remarks->countAll() == 0 || empty($productionLine) ) {
	// // 		$results = array(
	// // 			"status" => false,
	// // 			"message" => "Condition không có dữ liệu"
	// // 		);
	// // 	} else {
	// // 		$data = array();
	// // 		$dataRemark = $this->remarks->readProductionLine($productionLine); // $productionLine
	// // 		$index = 0;
	// // 		foreach ($dataRemark as $key => $item ) {
	// // 			$index++;

	// // 			$conditions = json_decode($item['conditions']);
	// // 			$codition_label = '';
	// // 			$codition_label .= isset($conditions->rbo) ? (trim($conditions->rbo) . '-') : '';
	// // 			$codition_label .= isset($conditions->ship_to_customer) ? (trim($conditions->ship_to_customer) . '-') : '';
	// // 			$codition_label .= isset($conditions->bill_to_customer) ? (trim($conditions->bill_to_customer) . '-') : '';
	// // 			$codition_label .= isset($conditions->internal_item) ? (trim($conditions->internal_item) . '-') : '';
	// // 			$codition_label .= isset($conditions->ordered_item) ? (trim($conditions->ordered_item) . '-') : '';
	// // 			$codition_label .= isset($conditions->order_type_name) ? (trim($conditions->order_type_name) . '-') : '';
	// // 			$codition_label .= isset($conditions->material_code) ? (trim($conditions->material_code) . '-') : '';
	// // 			$codition_label .= isset($conditions->ink_code) ? (trim($conditions->ink_code) . '-') : '';
	// // 			$codition_label .= isset($conditions->packing_instr) ? trim($conditions->packing_instr) : '';
				
	// // 			$data[] = [
	// // 				'id' => $index,
	// // 				'data' => [
	// // 					$index,
	// // 					$item['condition_code'],
	// // 					$codition_label,
	// // 					$item['remark'],
	// // 					$item['updated_by'],
	// // 					date('Y-m-d',strtotime($item['updated_date']) )
	// // 				]
	// // 			];
	// // 		}

	// // 		$results = array(
	// // 			"status" => true,
	// // 			"message" => "Load Data Success",
	// // 			"load" => $data
	// // 		);
	// // 	}

	// // 	echo json_encode($results, JSON_UNESCAPED_UNICODE);
	// // }

	// // public function loadCondition() 
	// // {

	// // 	$this->_data['title'] = 'Load Condition Views';

	// // 	$results = array();

	// // 	if (!$this->condition_codes->countAll() > 0 ) {
	// // 		$results = array(
	// // 			"status" => false,
	// // 			"message" => "Condition không có dữ liệu"
	// // 		);
	// // 	} else {
	// // 		$dataCondition = $this->condition_codes->read();
	// // 		$index = 0;
	// // 		foreach ($dataCondition as $key => $item ) {
	// // 			$index++;
	// // 			$data[] = [
	// // 				'id' => $index,
	// // 				'data' => [
	// // 					$index,
	// // 					$item['condition_code'],
	// // 					$item['condition_note'],
	// // 					$item['updated_by'],
	// // 					date('Y-m-d',strtotime($item['updated_date']) )
	// // 				]
	// // 			];
	// // 		}

	// // 		$results = array(
	// // 			"status" => true,
	// // 			"message" => "Load Data Success",
	// // 			"load" => $data
	// // 		);
	// // 	}

	// // 	echo json_encode($results, JSON_UNESCAPED_UNICODE);

	// // }

	public function createRemark() 
	{
		// models
			$this->load->model('automail');			
		
		// departments
			$deparments = array('woven', 'thermal', 'htl');

		// init arr
			$shipToCustomerArr = array();
			$billToCustomerArr = array();
			$orderedItemArr = array();
			$orderTypeArr = array();
			

		// get data
			// ship to
			$shipToCustomerList = $this->automail->readCol('SHIP_TO_CUSTOMER');
			foreach ($shipToCustomerList as $value ) {
				$ship_to_customer = trim($value['SHIP_TO_CUSTOMER']);
				if (!in_array($ship_to_customer, $shipToCustomerArr) && !empty($ship_to_customer) && (strpos(strtoupper($ship_to_customer),'SHIP TO CUSTOMER') == false ) ) {
					$shipToCustomerArr[] = $ship_to_customer;
				}
			}

			// bill to 
			$billToCustomerList = $this->automail->readCol('BILL_TO_CUSTOMER');
			foreach ($billToCustomerList as $value ) {
				$bill_to_customer = trim($value['BILL_TO_CUSTOMER']);
				if (!in_array($bill_to_customer, $billToCustomerArr) && !empty($bill_to_customer) && (strpos(strtoupper($bill_to_customer),'BILL TO CUSTOMER') == false ) ) {
					$billToCustomerArr[] = $bill_to_customer;
				}
			}

			//  ORDERED_ITEM
			$orderedItemList = $this->automail->readCol('ORDERED_ITEM'); 
			foreach ($orderedItemList as $value ) {
				$ordered_item = trim($value['ORDERED_ITEM']);
				if (!in_array($ordered_item, $orderedItemArr) && !empty($ordered_item) && (strpos(strtoupper($ordered_item),'ORDERED ITEM') == false ) ) {
					$orderedItemArr[] = $ordered_item;
				}
			}

			$orderTypeList = $this->automail->readCol('ORDER_TYPE_NAME');
			foreach ($orderTypeList as $value ) {
				$order_type_name = trim($value['ORDER_TYPE_NAME']);
				if (!in_array($order_type_name, $orderTypeArr) && !empty($order_type_name) && (strpos(strtoupper($order_type_name),'ORDER_TYPE_NAME') == false ) ) {
					$orderTypeArr[] = $order_type_name;
				}
			}

		// init 
			$rboArr = array();
			$internalItemArr = array();
			$materialArr = array();
		
		// get data from production line
			if ($this->production_line == 'woven' ) {

				// models
					$this->load->model('woven_master_item', 'wv_master_item');
					$this->load->model('woven_master_item_supply', 'supply');

				// data
					// RBO
					$rboList = $this->wv_master_item->readCol('rbo');
					foreach ($rboList as $rboItem ) {
						$rbo = trim($rboItem['rbo']);
						if (!in_array($rbo, $rboArr) && !empty($rbo) ) {
							$rboArr[] = $rbo;
						}
					}
					
					// internal item
					$internalItemList = $this->wv_master_item->readCol('internal_item');
					foreach ($internalItemList as $value ) {
						$internal_item = trim($value['internal_item']);
						if (!in_array($internal_item, $internalItemArr) ) {
							$internalItemArr[] = $internal_item;
						}
					}

					$materialList = $this->supply->readCol('code_name');
					foreach ($materialList as $material ) {
						$material_code = trim($material['code_name']);
						if (!in_array($material_code, $materialArr) ) {
							$materialArr [] = trim($material['code_name']);
						}
						
					}

			} else if ($this->production_line == 'thermal' ) {

				// models
					$this->load->model('thermal_master_item');
					$this->load->model('thermal_master_item_material_ink', 'thermal_supply');
				
				// data
					$rboList = $this->thermal_master_item->readCol('rbo');
					$internalItemList = $this->thermal_master_item->readCol('internal_item');
					$materialList = $this->thermal_supply->readSupply(array('code_type' => 'material') );
					foreach ($materialList as $material ) {
						$materialArr [] = trim($material['code_name']);
					}
				
			} else if ($this->production_line == 'htl' ) {

				// models
					$this->load->model('htl_master_item');

				// data
					// RBO
					$rboList = $this->automail->readCol('SOLD_TO_CUSTOMER');
					foreach ($rboList as $rboItem ) {
						$rbo = trim($rboItem['SOLD_TO_CUSTOMER']);
						if (!in_array($rbo, $rboArr) && !empty($rbo) && (strpos(strtoupper($rbo),'SOLD_TO_CUSTOMER') == false ) ) {
							$rboArr[] = $rbo;
						}
					}

					// internal item
					$internalItemList = $this->htl_master_item->readCol('internal_item');
					foreach ($internalItemList as $value ) {
						$internal_item = trim($value['internal_item']);
						if (!in_array($internal_item, $internalItemArr) ) {
							$internalItemArr[] = $internal_item;
						}
					}

					// material code
					$materialList = $this->htl_master_item->readCol('material_code');
					foreach ($materialList as $material ) {
						$material_code = trim($material['material_code']);
						if (!in_array($material_code, $materialArr) ) {
							$materialArr [] = $material_code;
						}
						
					}

			}

		// select data
		
		$selectCondition = $this->loadSelectCondition();

		if (empty($selectCondition) ) {
			$results = array(
				"status" => false,
				"message" => "Dữ liệu Điều kiện trống. Vui lòng thêm Điều kiện trước khi tạo Remark"
			);
		} else {

			// init form
			$formStruct = '';
			$formStruct .= '[{ type: "settings", position: "label-left", labelWidth: 150, inputWidth: 700 },';
			$formStruct .= '{type: "fieldset", label: "Remarks", width: 900, blockOffset: 10, offsetLeft: 30, offsetTop: 30, list: [';
			$formStruct .= '{ type: "settings", position: "label-left", labelWidth: 120, inputWidth: 700, labelAlign: "left" },';
			
			// conditions
			$formStruct .= '{ type: "combo", id: "condition", name: "condition", label: "Condition Code", style: "",  required: true, validate: "NotEmpty", options: [';
			$formStruct .= '{ value: "", text: "Choose" },';

			$countConditions = count($selectCondition);
			foreach ($selectCondition as $key => $select ) {
				$condition_code = $select["condition_code"];
				$condition_label = $select["condition_label"];

				$conditionLabelArr[] = $condition_label;
				if ($key < ($countConditions-1) ) {
					$formStruct .= '{ value: "'.$condition_code.'", text: "'.$condition_label.'" },';	
				} else {
					$formStruct .= '{ value: "'.$condition_code.'", text: "'.$condition_label.'" }';	
				}
				
			}
			
			$formStruct .= ']},';
			

			// RBO
			$formStruct .= '{ type: "combo", comboType: "checkbox", id: "rbo", name: "rbo", label: "RBO", filtering: true, options: [';
			$formStruct .= '{ value: "", text: "" },';

			$countRBOArr = count($rboArr);
			foreach ($rboArr as $key => $value ) {
				// $rbo = html_entity_decode($value,ENT_QUOTES, 'UTF-8');
				$rbo = htmlentities(trim($value), ENT_QUOTES, 'UTF-8' );
				$rbo_show = str_replace("&amp;", "&",$rbo);
				$rbo_show = str_replace("&#039;", "'",$rbo_show);
				
				if ($key < ($countRBOArr-1) ) {
					$formStruct .= '{ value: "'.$rbo.'", text: "'.$rbo_show.'" },';	
				} else {
					$formStruct .= '{ value: "'.$rbo.'", text: "'.$rbo_show.'" }';	
				}
			}
			$formStruct .= ']},';


			// ship_to_customer
			$formStruct .= '{ type: "combo", comboType: "checkbox", id: "ship_to_customer", name: "ship_to_customer", label: "Ship To Customer", filtering: true, options: [';
			$formStruct .= '{ value: "", text: "" },';
			
			$countShipto = count($shipToCustomerArr);
			foreach ($shipToCustomerArr as $key => $value ) {

				// $ship_to_customer = html_entity_decode($value,ENT_QUOTES, 'UTF-8');
				$ship_to_customer = htmlentities(trim($value), ENT_QUOTES, 'UTF-8' );
				$ship_to_customer_show = str_replace("&amp;", "&",$ship_to_customer);
				$ship_to_customer_show = str_replace("&#039;", "'",$ship_to_customer_show);

				if ($key < ($countShipto-1) ) {
					$formStruct .= '{ value: "'.$ship_to_customer.'", text: "'.$ship_to_customer_show.'" },';
				} else {
					$formStruct .= '{ value: "'.$ship_to_customer.'", text: "'.$ship_to_customer_show.'" }';
				}

				
			}
			$formStruct .= ']},';

			// Bill to customer
			$formStruct .= '{ type: "combo", comboType: "checkbox", id: "bill_to_customer", name: "bill_to_customer", label: "Bill To Customer", filtering: true, options: [';
			$formStruct .= '{ value: "", text: "" },';
			
			$countBillto = count($billToCustomerArr);
			foreach ($billToCustomerArr as $key => $value ) {
				
				// $bill_to_customer = html_entity_decode($value,ENT_QUOTES, 'UTF-8');
				$bill_to_customer = htmlentities(trim($value), ENT_QUOTES, 'UTF-8' );
				$bill_to_customer_show = str_replace("&amp;", "&",$bill_to_customer);
				$bill_to_customer_show = str_replace("&#039;", "'",$bill_to_customer_show);

				if ($key < ($countBillto-1) ) {
					$formStruct .= '{ value: "'.$bill_to_customer.'", text: "'.$bill_to_customer_show.'" },';
				} else {
					$formStruct .= '{ value: "'.$bill_to_customer.'", text: "'.$bill_to_customer_show.'" }';
				}
				
			}
			$formStruct .= ']},';
			

			// Internal item
			$formStruct .= '{ type: "combo", comboType: "checkbox", id: "internal_item", name: "internal_item", label: "Internal Item", filtering: true, options: [';
			$formStruct .= '{ value: "", text: "" },';

			$countItem = count($internalItemArr);
			foreach ($internalItemArr as $key => $value ) {

				if ($key < ($countItem-1) ) {
					$formStruct .= '{ value: "'.$value.'", text: "'.$value.'" },';
				} else {
					$formStruct .= '{ value: "'.$value.'", text: "'.$value.'" }';
				}

			}
			$formStruct .= ']},';


			// Ordered Item
			$formStruct .= '{ type: "combo", comboType: "checkbox", id: "ordered_item", name: "ordered_item", label: "Ordered Item", filtering: true, options: [';
			$formStruct .= '{ value: "", text: "" },';
			

			$countOrderItem = count($orderedItemArr);
			if ($countOrderItem > 0 ) {
				foreach ($orderedItemArr as $key => $value ) {

					// $order_type_name = html_entity_decode($value,ENT_QUOTES, 'UTF-8');
					$order_type_name = htmlentities(trim($value), ENT_QUOTES, 'UTF-8' );
					$order_type_name_show = str_replace("&amp;", "&",$order_type_name);
					$order_type_name_show = str_replace("&#039;", "'",$order_type_name_show);
					
					

					if ($key < ($countOrderItem-1) ) {
						$formStruct .= '{ value: "'.$order_type_name.'", text: "'.$order_type_name_show.'" },';
					} else {
						$formStruct .= '{ value: "'.$order_type_name.'", text: "'.$order_type_name_show.'" }';
					}
	
				}
			}
			

			$formStruct .= ']},';


			// ORDER_TYPE_NAME
			$formStruct .= '{ type: "combo", comboType: "checkbox", id: "order_type_name", name: "order_type_name", label: "Order Type", filtering: true, options: [';
			$formStruct .= '{ value: "", text: "" },';

			$countOrderType = count($orderTypeArr);
			if ($countOrderType > 0 ) {
				foreach ($orderTypeArr as $key => $value ) {
					
					if ($key < ($countOrderType-1) ) {
						$formStruct .= '{ value: "'.$value.'", text: "'.$value.'" },';
					} else {
						$formStruct .= '{ value: "'.$value.'", text: "'.$value.'" }';
					}
	
				}
			}

			$formStruct .= ']},';


			// Material Code
			$formStruct .= '{ type: "combo", comboType: "checkbox", id: "material_code", name: "material_code", label: "Material Code", filtering: true, options: [';
			$formStruct .= '{ value: "", text: "" },';
			$countMaterial = count($materialArr);
			if ($countMaterial > 0 ) {

				foreach ($materialArr as $key => $value ) {
	
					if ($key < ($countMaterial-1) ) {
						$formStruct .= '{ value: "'.$value.'", text: "'.$value.'" },';
					} else {
						$formStruct .= '{ value: "'.$value.'", text: "'.$value.'" }';
					}
				}
			}
			
			$formStruct .= ']},';

			$formStruct .= '{ type: "combo", comboType: "checkbox", id: "ink_code", name: "ink_code", label: "Ink Code", filtering: true, options: [{ value: "", text: "" }]},';
	
			$formStruct .= '{ type: "input", id: "packing_instr", name: "packing_instr", label: "Packing Instr:", icon: "icon-input", className: "" },';
			$formStruct .= '{ type: "input", id: "remark", name: "remark", label: "Remark Content:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },';
	
			$formStruct .= ']},';
			$formStruct .= '{   type: "button", id: "createRemark", name: "createRemark", value: "Add Remark", position: "label-center", width: 210, offsetLeft: 360 }';
			$formStruct .= ']';
		}
		
		$results = array(
			"status" => true,
			"message" => "Success",
			"struct" => $formStruct,
			"condition_label" => $conditionLabelArr
		);
		echo json_encode($results, JSON_UNESCAPED_UNICODE);

	

	}

	public function createRemarkSave() {
		header("Content-Type: application/json");
		$this->_data['title'] = 'Add Remark';

		$productionLine = isset($_COOKIE['plan_department']) ? strtolower($_COOKIE['plan_department']) : '';
		$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

		// get POST data 
		$dataPost = isset($_POST["data"]) ? $_POST["data"] : '';
		// $dataPost = '{"condition_code":"C0003","bill_to_customer":["3Q INTERNATIONAL LTD"],"remark":"test"}';
		$dataPost = json_decode($dataPost, true);
		// check 
		$inputError = 0;
		
		// check empty
		if (empty($dataPost) ) {
			$results = array(
				"status" => false,
				"messagge" => "Không có dữ liệu"
			);
			echo json_encode($results, JSON_UNESCAPED_UNICODE); exit();

		} else {
			
			// get data POST
				$condition_code = $dataPost['condition_code'];
				$rbo = isset($dataPost['rbo']) ? $dataPost['rbo'] : '';
				$ship_to_customer = isset($dataPost['ship_to_customer']) ? $dataPost['ship_to_customer'] : '';
				$bill_to_customer = isset($dataPost['bill_to_customer']) ? $dataPost['bill_to_customer'] : '';
				$internal_item = isset($dataPost['internal_item']) ? $dataPost['internal_item'] : '';
				$ordered_item = isset($dataPost['ordered_item']) ? $dataPost['ordered_item'] : '';
				$order_type_name = isset($dataPost['order_type_name']) ? $dataPost['order_type_name'] : '';
				$material_code = isset($dataPost['material_code']) ? $dataPost['material_code'] : '';
				$ink_code = isset($dataPost['ink_code']) ? $dataPost['ink_code'] : '';
				$packing_instr = isset($dataPost['packing_instr']) ? strtoupper(trim($dataPost['packing_instr']) ) : '';
				$remark = isset($dataPost['remark']) ? trim($dataPost['remark']) : '';

			// check 
				if ($inputError == 0 ) { 
					if (empty($remark) ) { $inputError = 1; $message = 'Remark Content Không được trống'; } 
				}
			
			// get condition codes
				$conditionCheck = $this->condition_codes->readSingle(array('condition_code' => $condition_code) );
				$condition_rules_json = json_decode($conditionCheck['condition_rules_json']);
				

			// check
				foreach ($condition_rules_json as $keyC => $condition ) {
					if ($keyC == 'rbo' && $condition == 1 && empty($rbo) ) { $inputError = 1; $message = 'RBO Không được trống'; break; }
					if ($keyC == 'ship_to_customer' && $condition == 1 && empty($ship_to_customer) ) { $inputError = 1; $message = 'Ship To Customer Không được trống'; break; }
					if ($keyC == 'bill_to_customer' && $condition == 1 && empty($bill_to_customer) ) { $inputError = 1; $message = 'Bill To Customer Không được trống'; break; } 
					if ($keyC == 'internal_item' && $condition == 1 && empty($internal_item) ) { $inputError = 1; $message = 'Internal Item Không được trống'; break; } 
					if ($keyC == 'ordered_item' && $condition == 1 && empty($ordered_item) ) { $inputError = 1; $message = 'Ordered Item Không được trống'; break; } 
					if ($keyC == 'order_type_name' && $condition == 1 && empty($order_type_name) ) { $inputError = 1; $message = 'Order Type Name Không được trống'; break; }
					if ($keyC == 'material_code' && $condition == 1 && empty($material_code) ) { $inputError = 1; $message = 'Material Code Không được trống'; break; }
					if ($keyC == 'ink_code' && $condition == 1 && empty($ink_code) ) { $inputError = 1; $message = 'Ink Code Không được trống'; break; } 
					if ($keyC == 'packing_instr' && $condition == 1 && empty($packing_instr) ) { $inputError = 1; $message = 'Packing Instr Không được trống'; break; 
					}
				}

		}

		// results
			if ($inputError == 1 ) {
				$results = array(
					"status" => false,
					"message" => $message
				);
			} else {

				// set conditions
				$conditions = array();

				// get counCheck1 & countCheck2
					$countCheck1 = 0;
					$countCheck2 = 0;
					
					if (!empty($rbo) && $countCheck1 == 0 ) { $countCheck1 = count($rbo); }

					if (!empty($ship_to_customer) ) { 
						if ($countCheck1 > 0 ) {
							$countCheck2 = count($ship_to_customer); 
						} else {
							$countCheck1 = count($ship_to_customer); 
						}
						
					}

					if (!empty($bill_to_customer) ) { 
						if ($countCheck1 > 0 ) {
							$countCheck2 = count($bill_to_customer); 
						} else {
							$countCheck1 = count($bill_to_customer); 
						}
						
					}

					if (!empty($internal_item)  ) { 
						if ($countCheck1 > 0 ) {
							$countCheck2 = count($internal_item); 
						} else {
							$countCheck1 = count($internal_item); 
						}
					}
					if (!empty($ordered_item)  ) { 
						if ($countCheck1 > 0 ) {
							$countCheck2 = count($ordered_item); 
						} else {
							$countCheck1 = count($ordered_item); 
						}
					}
					if (!empty($order_type_name) ) { 
						if ($countCheck1 > 0 ) {
							$countCheck2 = count($order_type_name); 
						} else {
							$countCheck1 = count($order_type_name); 
						}
					}
					if (!empty($material_code) ) { 
						if ($countCheck1 > 0 ) {
							$countCheck2 = count($material_code); 
						} else {
							$countCheck1 = count($material_code); 
						}
					}
					if (!empty($ink_code) ) { 
						if ($countCheck1 > 0 ) {
							$countCheck2 = count($ink_code); 
						} else {
							$countCheck1 = count($ink_code); 
						}
					}

				// get countCheck
					if ($countCheck1 > 0 ) {
						if ($countCheck2 > 0 ) {
							$countCheck = $countCheck1 * $countCheck2;
						} else {
							$countCheck = $countCheck1;
						}

						// init condition label 
							for ($i=0;$i<$countCheck;$i++ ) {
								$conditions[$i]['condition_label'] = '';
							}

						/*
							* countCheck2 > 0: Có 2 mảng tồn tại. Lúc này sẽ lưu n * m phần tử remark được tạo ra.
							* countCheck2 = 0: Chỉ có 1 điều kiện được chọn. 
						*/ 
						
							// RBO
								if (!empty($rbo) ) {
									$conditions = $this->getConditionRemarkSave($countCheck, $rbo, 'rbo', $conditions);
								}

							// Ship to customer
								if (!empty($ship_to_customer) ) {
									$conditions = $this->getConditionRemarkSave($countCheck, $ship_to_customer, 'ship_to_customer', $conditions);
								}
								

							// Bill to customer
								if (!empty($bill_to_customer) ) {
									$conditions = $this->getConditionRemarkSave($countCheck, $bill_to_customer, 'bill_to_customer', $conditions);
								}
						
							// Internal Item
								if (!empty($internal_item) ) {
									$conditions = $this->getConditionRemarkSave($countCheck, $internal_item, 'internal_item', $conditions);
								}

							// ordered_item
								if (!empty($ordered_item) ) {
									$conditions = $this->getConditionRemarkSave($countCheck, $ordered_item, 'ordered_item', $conditions);
								}
							
							// order_type
								if (!empty($order_type_name) ) {
									$conditions = $this->getConditionRemarkSave($countCheck, $order_type_name, 'order_type_name', $conditions);
								}

							// material_code
								if (!empty($material_code) ) {
									$conditions = $this->getConditionRemarkSave($countCheck, $material_code, 'material_code', $conditions);
								}

							// ink_code
								if (!empty($ink_code) ) {
									$conditions = $this->getConditionRemarkSave($countCheck, $ink_code, 'ink_code', $conditions);
								}

							// packing_instr
								if (!empty($packing_instr) ) {
									for ($index=0; $index<$countCheck; $index++) {
										$conditions[$index]['packing_instr'] = $packing_instr;
										$conditions[$index]['condition_label'] .= ($packing_instr . '-' );
									}
								}


					} else {
						// packing_instr
						if (!empty($packing_instr) ) {
							$conditions[0]['packing_instr'] = $packing_instr;
							$conditions[0]['condition_label'] = ($packing_instr . '-' );
						}
					}


				// get insert data
					$insertArr = array();
					foreach ($conditions as $condition ) {
						// check exist
						$condition_label_check = $condition['condition_label'];
						$array = array('production_line' => $productionLine, 'condition_code' => $condition_code );
						$remarkArr = $this->remarks->readItem($array);
						if (!empty($remarkArr ) ) {
							$insertCheck = 0;
							foreach ($remarkArr as $remarkCheck ) {
								$conditionCheck = json_decode($remarkCheck['conditions']);

								$condition_label = '';
								$condition_label .= isset($conditionCheck->rbo) ? (trim($conditionCheck->rbo) . '-') : '';
								$condition_label .= isset($conditionCheck->ship_to_customer) ? (trim($conditionCheck->ship_to_customer) . '-') : '';
								$condition_label .= isset($conditionCheck->bill_to_customer) ? (trim($conditionCheck->bill_to_customer) . '-') : '';
								$condition_label .= isset($conditionCheck->internal_item) ? (trim($conditionCheck->internal_item) . '-') : '';
								$condition_label .= isset($conditionCheck->ordered_item) ? (trim($conditionCheck->ordered_item) . '-') : '';
								$condition_label .= isset($conditionCheck->order_type_name) ? (trim($conditionCheck->order_type_name) . '-') : '';
								$condition_label .= isset($conditionCheck->material_code) ? (trim($conditionCheck->material_code) . '-') : '';
								$condition_label .= isset($conditionCheck->ink_code) ? (trim($conditionCheck->ink_code) . '-') : '';
								$condition_label .= isset($conditionCheck->packing_instr) ? trim($conditionCheck->packing_instr) : '';
								
								if ($condition_label_check == $condition_label ) {
									$insertCheck = 0;
									$results = array(
										"status" => false,
										"message" => "Remark đã tồn tại. " . $remarkCheck['conditions']
									);
									echo json_encode($results, JSON_UNESCAPED_UNICODE); exit();
								} else {
									$insertCheck = 1;
								}
		
							}
							// Trường hợp kiểm tra không có lưu trước đó thì insert
							if ($insertCheck == 1 ) {
								unset($condition['condition_label']);
								$insertArr[] = array(
									'production_line' => $productionLine,
									'condition_code' => $condition_code,
									'conditions' => json_encode($condition,JSON_UNESCAPED_UNICODE),
									'remark' => $remark,
									'updated_by' => $updated_by
								);
							}
						} else {
							unset($condition['condition_label']);
							$insertArr[] = array(
								'production_line' => $productionLine,
								'condition_code' => $condition_code,
								'conditions' => json_encode($condition,JSON_UNESCAPED_UNICODE),
								'remark' => $remark,
								'updated_by' => $updated_by
							);
						}
						


					}
				// insert
					$results = $this->remarks->insertBatch($this->remarks->setInsertBatch($insertArr) );
				// Check 
					if ($results !== TRUE ) {
						$results = array(
							"status" => false,
							"message" => "Save dữ liệu bị lỗi"
						);
					} else {
						$results = array(
							"status" => true,
							"message" => "Save data Success "
						);
					}

			}

			// results
			echo json_encode($results, JSON_UNESCAPED_UNICODE);

	}

	public function loadSelectCondition() 
	{
		$data = array();
		if ($this->condition_codes->countAll() > 0 ) {
			
			$dataCondition = $this->condition_codes->read();
			$index = 0;
			foreach ($dataCondition as $item ) {
				$index++;
				$condition_label = $item['condition_code'] . trim($item['condition_note']);
				$data[] = [
					'condition_code' => $item['condition_code'],
					'condition_label' => $condition_label
				];
			}

		}

		return $data;
		

	}

	public function getConditionRemarkSave($countCheck, $array, $col, $conditions ) 
	{
		$i = 0;
		$index = 0;
		$check = $countCheck / count($array);
		while ($i < $check ) {
			
			foreach ($array as $key => $value ) {
				$conditions[$index][$col] = $value;
				$conditions[$index]['condition_label'] .= ($value . '-' );
				$index++;
			}

			$i++;
		}

		return $conditions;
	}


	// save remark then the users update on the JJ
	public function updateRemarks( )
    {
        // tilte 
        $this->_data['title'] = 'Update Remarks';

        // init 
        $results = array();
        $result = false;
        $status = false;
        $message = 'Lỗi chưa xác định';

        // set post data
        $data = $this->input->post('data');
		// $data = '{"condition_code":"C0012","conditions":"BURTON SNOWBOARDS-AT108292-","remark":"test"}';
        // set get data
        $master = null !== $this->input->get('master') ? trim($this->input->get('master')) : '';
        $delConf = null !== $this->input->get('del') ? trim($this->input->get('del') ) : false;

        // check
        $data = json_decode($data, true);
        if (empty($data)) {
            $message = 'Không nhận được dữ liệu POST!!!';
        } else {

			$conditionsCheck = array('rbo', 'ship_to_customer', 'bill_to_customer', 'internal_item', 'ordered_item', 'order_type_name', 'material_code', 'ink_code', 'packing_instr');

            if ($master == 'view_remarks' ) {

                // get data
				$condition_code = $data['condition_code'];
                $remark = $data['remark'];
				$conditionTmp = trim($data['conditions']);
				$conditionArr = explode("-", $conditionTmp );

				// loại bỏ phần tử rỗng
				$conditionArr = array_filter( $conditionArr );
				$count_con = count($conditionArr);

				// get condition codes
				$conditionCheck = $this->condition_codes->readSingle(array('condition_code' => $condition_code) );
				$condition_rules_json = json_decode($conditionCheck['condition_rules_json']);
			

			// check
				$conditions = '{';
				$count_check = 0;
				foreach ($condition_rules_json as $keyC => $condition ) {


					foreach ($conditionsCheck as $con ) {

						// Nếu $keyC = $con (bằng điều kiện gì) và điều kiện đó giá trị 1 ==> có tồn tại giá trị này
						if ($keyC == $con && $condition == 1 ) { 
							$count_check++;
							
							// Nếu như tổng số điều kiện = 2 và đây là vị trí đầu tiên thì có dấu , phía cuối cùng
							if ($count_con == 2 && $count_check == 1  ) {
								$conditions .= '"'.$con.'":"'.$conditionArr[$count_check-1].'",';
							} else {
								$conditions .= '"'.$con.'":"'.$conditionArr[$count_check-1].'"';
							}

							break;
						}
					}
					
				}

				$conditions .= '}';
				
				// check
				$where = array('production_line' => $this->production_line, 'condition_code' => $condition_code, 'conditions' => $conditions);

				if ($delConf == 'del' ) {
					$del_message = ' Remark: ' . $remark;
					if ($this->remarks->isAlreadyExist($where ) ) {
						$result = $this->remarks->delete($where );
					}
				} else {
					
					// set updated by
					$data['updated_by'] = $this->updated_by;

					// check is aldready exist
					if ($this->remarks->isAlreadyExist($where ) ) {
						// update
						unset($data['production_line']);
						unset($data['condition_code']);
						unset($data['conditions']);
						$data['updated_date'] = date('Y-m-d H:i:s');
						$result = $this->remarks->update($data, $where );
						
					}

				}
                 
            }
        
        

        
        }

        // check
        if ($result == TRUE ) {

            $status = true;
            $message = 'Cập nhật dữ liệu thành công';
            if ($delConf == 'del' ) {
                $message = 'Xóa dữ liệu '.$del_message.' thành công';
            }
            
        } else {
			$message = 'Có lỗi Xử lý dữ liệu';
		}

        // result
        $results = array( 'status' => $status, 'message' => $message );

        // results
        echo json_encode($results, JSON_UNESCAPED_UNICODE); exit();
    }


	

	
}
