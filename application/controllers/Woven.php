<?php
defined('BASEPATH') or exit('No direct script access allowed');
include_once APPPATH . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// use PhpOffice\PhpSpreadsheet\Reader\Csv;
class Woven extends CI_Controller
{

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
		$this->load->model('autoload');
		$this->load->model('automail');
		$this->load->model('automail_closed');
		$this->load->model('common_setting_process', 'setting_process');

		// // //get automail updated date
		// // $automail_updated = $this->autoload->getAutomailUpdated();
		// // if (!empty($automail_updated['CREATEDDATE'])) {
		// // 	$this->_data['automail_updated'] = $automail_updated['CREATEDDATE'];
		// // } else {
		// // 	$this->_data['automail_updated'] = 'loading...';
		// // }

		$this->_data['automail_updated'] = $this->getAutomailUpdated();

		$this->production_line = null != get_cookie('plan_department') ? get_cookie('plan_department') : 'woven';
		$this->updated_by = null !== get_cookie('plan_loginUser') ? get_cookie('plan_loginUser') : '';


		$this->_data['count_process'] = $this->setting_process->countAll();
		$this->_data['setting_process'] = $this->setting_process->read('process_order');
		$this->_data['setting_process_basic'] = $this->setting_process->readOptions(array('process_order < ' => 50), 'process_order');
	}

	public function getAutomailUpdated()
	{
		// default
		$result = 'loading...';

		//get automail updated date
		$data = $this->autoload->getLastUpdated();
		
		if (!empty($data) ) {
			$created_date = $data['CREATEDDATE'];
			$status = $data['STATUS'];
			$filename = $data['LOGDATA'];

			if ($status == 'OK' ) {
				$result = (!empty($created_date) ) ? $created_date : $result;
			} else {
				$dataOK = $this->autoload->getLastUpdatedOK();
				$created_date_OK = '';
				
				if (!empty($dataOK) ) {
					$created_date_OK = $dataOK['CREATEDDATE'];
					
				}
				// 01: Không save được
				if ($status == 'ERR_01' ) {
					$result = "$created_date_OK. (ERR 01 (UPDATE) lúc $created_date)";
				} else if ($status == 'ERR_02' ) { // có rỗng dữ liệu PACKING,...
					$result = "$created_date_OK. (ERR 02 (EMPTY DATA) lúc $created_date)";
				} else if ($status == 'ERR_03' ) { // File không đọc được
					$result = "$created_date_OK. (ERR 03 (File Lỗi) lúc $created_date)";
				} 
			}
	
		}

		return $result;
		
	}


	public function index()
	{
		$department = isset($_COOKIE['plan_department']) ? $_COOKIE['plan_department'] : '';
		$title = $department . ' planning';
		$this->_data['title'] = strtoupper($title);

		if (!$this->checkLogin()) {
			$this->load->view('users/index', $this->_data);
		} else {
			if (empty($department) || $department != 'woven') {
				$this->load->view('users/index', $this->_data);
			} else {
				$this->load->view('woven/index', $this->_data);
			}
		}
	}
	// check login
	public function checkLogin()
	{
		return isset($_COOKIE['plan_loginUser']) ? true : false;
	}
	// show count all orders and count now
	public function countOrders()
	{

		$this->load->model('woven_po_save');
		$countAll = $this->woven_po_save->countAll();
		$countNow = $this->woven_po_save->countNow();
		$date = date('Y-m-d');

		$this->_data['results'] = array(
			"status" => true,
			"countAll" => $countAll,
			"countNow" => $countNow,
			'now' => $date
		);

		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
		exit();
	}
	// show order lists (main view)
	public function recent()
	{
		$this->load->model('woven_po_save');
		$this->load->model('woven_po_soline_save');

		$fromDate = null !== $this->input->get('from_date') ? trim($this->input->get('from_date')) : '';
		$toDate = null !== $this->input->get('to_date') ? trim($this->input->get('to_date')) : '';

		$results = array();

		if ($this->woven_po_save->countAll() > 0) {
			$po_save = $this->woven_po_save->readNow('desc', $fromDate, $toDate);
			$index = 0;
			foreach ($po_save as $item) {
				$index++;
				$so_line = '';
				$item_length = $item['internal_item'] . $item['length_btp'];
				$po_no = $item['po_no'];
				$plan_order_type = $item['type'];
				$internal_item = $item['internal_item'];
				$qty_total = $item['qty_total'];
				$batch_no = $item['batch_no'];
				$rbo = html_entity_decode($item['rbo'], ENT_QUOTES); // decode RBO

				$type_show = '';
				if (trim($item['type']) == 'non_batching') {
					$type_show = 'NON BATCHING';
				} else if (trim($item['type']) == 'common') {
					$type_show = 'BATCHING';
				} else {
					$type_show = trim(strtoupper($item['type']));
				}

				$prefix_url = base_url('woven/');

				if ($item['printed'] == 1) {

					$printed = '<span style="color:red;font-weight:bold;font-size:13px;"><a target="_blank" href="' . $prefix_url . 'printOrders/' . $po_no . '" title="printed" rel="follow, index">Printed</a></span>';
				} else {
					$printed = '<span style="color:blue;font-weight:bold;font-size:13px;"><a target="_blank" href="' . $prefix_url . 'printOrders/' . $po_no . '" title="print" rel="follow, index">Print</a></span>';
				}

				// $edit = '<span style="color:blue;font-weight:bold;font-size:13px;"><a href="' . $prefix_url .'loadData/?so_line=&item=&po_no_edit='. $po_no .'" title="Edit" rel="follow, index" >Edit</a></span>';
				$edit = '<span style="color:blue;font-weight:bold;font-size:13px;"><a href="" title="Edit" rel="follow, index" >Edit</a></span>';

				$delete = '<span style="color:blue;font-weight:bold;font-size:13px;"><a href="' . $prefix_url . 'delete/' . $po_no . '" title="Delete" rel="follow, index" onclick="return delete_confirm(' . "'$po_no'" . ');">Delete</a></span>';
				if (!$this->woven_po_soline_save->checkPO($item['po_no'])) {

					$results[] = [
						'id' => $index,
						'data' => [
							$item['order_type'],
							$item['po_date'],
							$item['po_no'],
							$type_show,
							$so_line,
							$item['qty_total'],
							$rbo,
							$item_length,
							$item['updated_by'],
							$item['updated_date'],
							$printed,
							$edit,
							$delete
						]
					];
				} else {
					$po_soline_save = $this->woven_po_soline_save->readPoSOLines($item['po_no']);
					$so_line = $po_soline_save[0]['so_line'];
					if ($plan_order_type == 'common' || $plan_order_type == 'non_batching') {
						$so_line = $item['batch_no'];
					}

					if ($so_line == $item['po_no']) {
						$edit = '<span style="color:blue;font-weight:bold;font-size:13px;"><a href="' . $prefix_url . 'loadData/?so_line=&item=' . $internal_item . '&po_no_edit=' . $po_no . '" title="Edit" rel="follow, index" >Edit</a></span>';
					} else {
						$edit = '<span style="color:blue;font-weight:bold;font-size:13px;"><a href="' . $prefix_url . 'loadData/?so_line=' . $so_line . '&item=' . $internal_item . '&po_no_edit=' . $po_no . '" title="Edit" rel="follow, index" >Edit</a></span>';
					}

					// if ($plan_order_type == 'common' || $plan_order_type == 'non_batching' ) {
					// 	$edit = '<span style="color:blue;font-weight:bold;font-size:13px;"><a href="' . $prefix_url .'commonOrder/?batch_no='. $batch_no .'&po_no_edit='. $po_no .'" title="Edit" rel="follow, index" >Edit</a></span>';
					// }

					$edit = '<span style="color:blue;font-weight:bold;font-size:13px;"><a href="" title="Edit" rel="follow, index" >Edit</a></span>';

					$results[] = [
						'id' => $index,
						'data' => [
							$item['order_type'],
							$item['po_date'],
							$item['po_no'],
							$type_show,
							$so_line,
							$qty_total,
							$rbo,
							$item_length,
							$item['updated_by'],
							$item['updated_date'],
							$printed,
							$edit,
							$delete
						]
					];
				}
			}
		}

		if (empty($results)) {
			$results = array(
				'id' => 1,
				'data' => [
					'', '', '', '', '', '', '', '', '', ''
				]
			);
		}


		echo json_encode($results, JSON_UNESCAPED_UNICODE);
		exit();
		// $this->load->view('woven/recent', $this->_data);
	}

	// check exist soline in save (done)
	public function checkSOLineExist()
	{
		// load models
		$this->load->model('woven_po_soline_save');

		// set post data (order_number and line_number)
		$dataPost = isset($_POST["data"]) ? $_POST["data"] : '';
		// $dataPost = '{"order_number":"44417666","line_number":"1"}';
		$dataPost = json_decode($dataPost, true);
		if (empty($dataPost)) {
			$this->_data['results'] = array(
				"status" => false,
				"message" => "Không nhận được dữ liệu POST!"
			);
		} else {
			// * check order in vnso_zero or vnso_closed
			if (!$this->automail->checkSOLine($dataPost['order_number'], $dataPost['line_number']) && !$this->automail_closed->checkSOLine($dataPost['order_number'], $dataPost['line_number']) ) {
				$this->_data['results'] = array(
					"status" => false,
					"message" => "SOLine không có trong automail (vnso). Vui lòng kiểm tra lại!"
				);
			} else {
				$this->_data['results'] = array(
					"status" => true,
					"message" => "Nhấn OK để tạo Đơn hàng Bù (CCR), CANCEL để hủy bỏ"
				);
			}
		}

		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
		exit();
	}
	// check exist item in master data
	public function checkItemExist()
	{
		// load models
		$this->load->model('woven_master_item', 'wv_master_item');

		// set post data
		$dataPost = isset($_POST["data"]) ? $_POST["data"] : '';
		// $dataPost = '{"item":"WX601710A"}';
		$dataPost = json_decode($dataPost, true);
		if (empty($dataPost)) {
			$this->_data['results'] = array(
				"status" => false,
				"message" => "Không nhận được dữ liệu POST!"
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		// * check
		if (!$this->wv_master_item->checkItem($dataPost['item'])) {
			$this->_data['results'] = array(
				"status" => false,
				"message" => "Item " . $dataPost['item'] . "không tồn tại trong Master data"
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		// results true
		$this->_data['results'] = array(
			"status" => true,
			"message" => "Item hợp lệ"
		);
		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
		exit();
	}
	// check exist batching order
	public function checkBatchingExist()
	{
		// load models
		$this->load->model('woven_po_save');

		// set post data
		$dataPost = isset($_POST["data"]) ? $_POST["data"] : '';
		// $dataPost = '{"batch_no":"0254002"}';

		$dataPost = json_decode($dataPost, true);
		if (empty($dataPost)) {
			$this->_data['results'] = array(
				"status" => false,
				"message" => "Không nhận được dữ liệu POST!"
			);
		} else {
			// * check
			if ($this->woven_po_save->checkBatching($dataPost['batch_no'])) {

				$this->_data['results'] = array(
					"status" => false,
					"message" => "Batch number " . $dataPost['batch_no'] . " đã làm lệnh. Bạn có muốn chỉnh sửa?"
				);
			} else {
				// results true
				$this->_data['results'] = array(
					"status" => true,
					"message" => "Batching hợp lệ"
				);
			}
		}

		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
	}

	// Kiểm tra đơn non batching đã làm lệnh chưa.
	public function checkNonBatchingExist()
	{
		// load models
		$this->load->model('woven_po_save');
		$this->load->model('woven_ppc_so_line');
		// $this->load->model('woven_prepress_batching', 'batching');

		// set post data
		$dataPost = isset($_POST["data"]) ? $_POST["data"] : '';
		// $dataPost = '{"batch_no":"0254002"}';

		$dataPost = json_decode($dataPost, true);
		if (empty($dataPost)) {
			$this->_data['results'] = array(
				"status" => false,
				"message" => "Không nhận được dữ liệu POST!"
			);
		} else {

			// lấy số batch_no trong 
			$where = array('so_line' => trim($dataPost['batch_no']));
			if (!$this->woven_ppc_so_line->isAlreadyExist($where)) {
				$this->_data['results'] = array(
					"status" => false,
					"message" => "Không có dữ liệu NON Batching"
				);
			} else {
				$ppcItem = $this->woven_ppc_so_line->readItem($where);
				$batch_no = trim($ppcItem['batch_no']);

				// * check
				if ($this->woven_po_save->checkBatching($batch_no)) {
					$this->_data['results'] = array(
						"status" => false,
						"message" => "Batch number " . $batch_no . " đã làm lệnh. Bạn có muốn chỉnh sửa?",
						"batch_no" => $batch_no
					);
				} else {
					// results true
					$this->_data['results'] = array(
						"status" => true,
						"message" => "Batching hợp lệ",
						"batch_no" => $batch_no
					);
				}
			}
		}

		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
	}

	// detach soline
	public function detached($so_line)
	{

		$data = array();

		$so_line_arr = explode('-', $so_line);
		$order_number = $so_line_arr[0];
		$line_number = $so_line_arr[1];

		if (isset($order_number) && isset($line_number)) {
			$data = array('order_number' => $order_number, 'line_number' => $line_number);
		}

		return $data;
	}
	// format date
	public function dateFormat($date)
	{
		return date('Y-m-d', strtotime($date));
	}

	// public function floorp($val, $precision)
	// {
	// 	$mult = pow(10, $precision); // Can be cached in lookup table
	// 	return floor($val * $mult) / $mult;
	// }

	public function scrap($scrap)
	{
		if (empty($scrap) || $scrap == 0) {
			$scrap = 1;
		} else {
			$scrap = (float)$scrap;
			$scrap = $scrap / 100;
		}

		return $scrap;
	}

	public function checkNumber($number, $defaul)
	{
		if (empty($number) || $number == 0) {
			$number = $defaul;
		} else {
			$number = (float)$number;
		}
	}

	// // // get so cai
	// // public function so_cai($so_cai_arr)
	// // {
	// // 	$qty_of_size = 0;
	// // 	$wire_number = 0;
	// // 	$sonic_number = 0;
	// // 	$scrap = 1;
	// // 	foreach ($so_cai_arr as $key => $value) {
	// // 		if ($key == 'qty_of_size') {
	// // 			$qty_of_size = $this->checkNumber($value, 0);
	// // 		} else if ($key == 'wire_number') {
	// // 			$wire_number = $this->checkNumber($value, 0);
	// // 		} else if ($key == 'sonic_number') {
	// // 			$sonic_number = $this->checkNumber($value, 0);
	// // 		} else if ($key == 'scrap') {
	// // 			$scrap = $this->checkNumber($this->scrap($value), 1);
	// // 		}
	// // 		break;
	// // 	}

	// // 	return ceil((($qty_of_size / $wire_number) + $sonic_number) * (1 + $scrap));
	// // }

	// chiều dài chỉ (từng code vật tư)
	public function thread_length($supply_code, $density)
	{

		// init 
			$length = 0;

		// density
			if (empty($density) || $density == 0) {
				$density = 1;
			} else {
				$density = (int)$density;
			}

		// Giá trị mặc định
			$length = round(9000000 / $density);

		/* 
			Kiểm tra code vật tư đặc biệt để lấy chiều dài chỉ phù hợp
		*/
		
			// load models
			$this->load->model('woven_master_item_supply_special', 'supply_special');

			$where = array('supply_code' => $supply_code) ;
			if ($this->supply_special->isAlreadyExist($where ) ) {
				$supplySpecialItem = $this->supply_special->readItem($where );
				$length = $supplySpecialItem['length_weft'];
			}
			
		
		return $length;

	}

	// get size data
	public function getSize($so_line)
	{
		$this->load->model('avery_vnso_size', 'vnso_size');
		$this->load->model('avery_vnso_size_oe', 'vnso_size_oe');
		// $so_line = '45289316-2';
		$so_line_arr = $this->detached($so_line);

		$vnsoSize = array();
		$dataResults = array();
		$error = 0;
		$check_exist = 0;

		if ($this->vnso_size->checkSOLine($so_line_arr['order_number'], $so_line_arr['line_number'])) {
			$vnsoSize = $this->vnso_size->readSOLine($so_line_arr['order_number'], $so_line_arr['line_number']);
		} else {
			if ($this->vnso_size_oe->checkSOLine($so_line_arr['order_number'], $so_line_arr['line_number'])) {
				$vnsoSize = $this->vnso_size_oe->readSOLine($so_line_arr['order_number'], $so_line_arr['line_number']);
			}
		}


		if (empty($vnsoSize)) {
			$dataResults = array();
		} else {
			foreach ($vnsoSize as $sizeItem) {

				$size = !empty($sizeItem['SIZE']) ? $sizeItem['SIZE'] : '';
				$color = !empty($sizeItem['COLOR']) ? $sizeItem['COLOR'] : '';
				$qty = !empty($sizeItem['QTY']) ? $sizeItem['QTY'] : '';
				$material_code = !empty($sizeItem['MATERIAL']) ? $sizeItem['MATERIAL'] : '';

				if (empty($size) && empty($qty)) {
					$error++;
					break;
				}

				if (!empty($dataResults)) {
					foreach ($dataResults as $key => $value) {

						if ($value['size'] == $size && $value['color'] == $color && $value['material_code'] == $material_code) {
							$dataResults[$key]['qty'] += $qty; //cộng thêm vào
							$check_exist = 1;
						} else {
							$check_exist = 0;
						}
					}

					//Không tồn tại thì thêm vào mảng kết quả
					if ($check_exist == 0) {
						$get = [
							'size' 			=> $size,
							'color' 		=> $color,
							'qty' 			=> $qty,
							'material_code' => $material_code
						];
						array_push($dataResults, $get);
					}
				} else { //trường hợp đầu tiên
					$get = [
						'size' 			=> $size,
						'color' 		=> $color,
						'qty' 			=> $qty,
						'material_code' => $material_code
					];
					array_push($dataResults, $get);
				}
			}
		}

		// check
		if ($error > 0) {
			$dataResults = array();
		}

		return $dataResults;
	}
	// get size automail
	public function getSizeAutomail($string)
	{
		//init var
		$dataResults = [];
		$size = $color = $qty = $material_code = '';
		$errorCount = $check_exist = $pause = 0;
		$sizepos = $colorpos = $qtypos = $materialcodepos = $maxpos = -1;

		//loại bỏ các khoảng trắng và ký tự thừa do người dùng nhập không đúng
		$string = str_replace(" ", "", $string);
		$string = str_replace(":;:", ";", $string);
		$string = str_replace(":;", ";", $string);
		$string = str_replace(";:", ";", $string);
		$string = str_replace(";:;", ";", $string);
		$string = str_replace(";;", ";", $string);
		$string = str_replace("^^^", "^", $string);
		$string = str_replace("^^", "^", $string);

		if (strpos(strtoupper($string), ";TOTAL") !== false) {
			$string = str_ireplace(";Total", ";TOTAL", $string);
		} else if (strpos($string, "Total") !== false) {
			$string = str_ireplace("Total", ";TOTAL", $string);
		}

		if (strpos(strtoupper($string), "SIZE") !== false) {
			$string = str_ireplace("SIZE", "SIZE", $string);
		}

		if (strpos(strtoupper($string), "QUANTITY") !== false) {
			$string = str_ireplace("QUANTITY", "QUANTITY", $string);
		}

		if (strpos(strtoupper($string), "QTY") !== false) {
			$string = str_ireplace("QTY", "QUANTITY", $string);
		}

		if (strpos(strtoupper($string), "Q'TY") !== false) {
			$string = str_ireplace("Q'TY", "QUANTITY", $string);
		}

		if (strpos(strtoupper($string), "COLOR") !== false) {
			$string = str_ireplace("COLOR", "COLOR", $string);
		}

		if (strpos(strtoupper($string), "MATERIAL CODE") !== false) {
			$string = str_ireplace("MATERIAL CODE", "MATERIALCODE", $string);
		}

		if (strpos(strtoupper($string), "MATERIALCODE") !== false) {
			$string = str_ireplace("MATERIALCODE", "MATERIALCODE", $string);
		}

		//Lấy Ký tự cuối check xem phải là ký tự: ^ hay k, k phải thì trả về lỗi
		$check = substr($string,  strlen($string) - 1, 1);
		if ($check !== '^') {
			$pause = 1;
		}

		//Tách chuỗi thành mảng, mỗi phần tử cần lấy các nội dung: Size, color, qty, material_code
		$string_explode = explode(";", $string);

		//Đoạn code xác định vị trí size, color, qty, material_code.
		foreach ($string_explode as $stringpos) {
			$detachedpos = explode(":", $stringpos);

			for ($i = 0; $i < count($detachedpos); $i++) {
				if (strpos(strtoupper($detachedpos[$i]), "SIZE") !== false) {
					$sizepos = $i;
					$maxpos = count($detachedpos);
				}
				if (strpos(strtoupper($detachedpos[$i]), "COLOR") !== false) {
					$colorpos = $i;
					$maxpos = count($detachedpos);
				}
				if (strpos(strtoupper($detachedpos[$i]), "QUANTITY") !== false) {
					$qtypos = $i;
					$maxpos = count($detachedpos);
				}
				if (strpos(strtoupper($detachedpos[$i]), "MATERIALCODE") !== false) {
					$materialcodepos = $i;
					$maxpos = count($detachedpos);
				}
			}
		}

		//Nếu có data và có ký tự ^ (data k bị mất). Trường hợp ngược lại không them vào
		if (!empty($string_explode) && !$pause) {
			// echo "\n maxpos: " . $maxpos . "\n";
			foreach ($string_explode as $key => $value) {
				$check_exist = 0;
				//get format string  detached.
				$detachedStringAll = trim($value);

				//check error. Nếu không đúng định dạng => return error
				if (substr_count($detachedStringAll, ":") < 1) { //Trường hợp min = 4 col
					$errorCount++;
					continue;
				}

				//tách chuỗi thành mảng bởi ký tự :
				$detachedString = explode(":", $detachedStringAll);

				//check detachedString không đúng định dạng. Dừng
				if (count($detachedString) != $maxpos || $maxpos < 2) {
					$errorCount++;
					continue;
				}

				//get data
				if ($sizepos != $colorpos && $colorpos != $qtypos && $qtypos != $materialcodepos) {
					//lấy dữ liệu //Trường hợp không lấy được cột data nào thì cho dữ liệu đó = rỗng
					$size = ($sizepos != -1) ? trim($detachedString[$sizepos]) : 'NON';
					if ($size == 'SIZE' || $size == 'TOTAL') {
						continue;
					}
					$color = ($colorpos != -1) ? trim($detachedString[$colorpos]) : 'NON';
					$qty = ($qtypos != -1) ? $detachedString[$qtypos] : 0;
					$material_code = ($materialcodepos != -1) ? trim($detachedString[$materialcodepos]) : ''; //tam thoi lay vi tri nay

					/* *** Check trường hợp OE không nhập dấu ; trước chữ Total, dấu ^, (còn thì thêm vào ...) *** */
					$character_error_arr = [
						'TOTAL',
						'^'
					];

					//Tìm các dữ liệu thừa để tách chuỗi thành mảng từ ký tự đó và lấy ra phần tử dữ liệu đã tách.
					foreach ($character_error_arr as $key => $value) {
						if (strpos(strtoupper($size), strtoupper($value)) !== false) {
							$detached_tmp = explode($value, $size);
							$size = $detached_tmp[0];
						}

						if (strpos(strtoupper($color), strtoupper($value)) !== false) {
							$detached_tmp = explode($value, $color);
							$color = $detached_tmp[0];
						}

						if (strpos(strtoupper($qty), strtoupper($value)) !== false) {
							$detached_tmp = explode($value, $qty);
							$qty = $detached_tmp[0];
						}

						if (strpos(strtoupper($material_code), strtoupper($value)) !== false) {
							$detached_tmp = explode($value, $material_code);
							$material_code = $detached_tmp[0];
						}
					} //end for

				} else {
					$qty = 0;
				}

				if (!is_numeric($qty)) { //kiểm tra qty có phải số không
					$errorCount++;
				} else if (empty($size) && empty($qty)) {
					$errorCount++;
				} else {
					//check data ton tai chua, neu ton tai => cong them vao qty
					if (!empty($dataResults)) {
						foreach ($dataResults as $key => $value) {

							if ($value['size'] == $size && $value['color'] == $color && $value['material_code'] == $material_code) {
								$dataResults[$key]['qty'] += $qty; //cộng thêm vào
								$check_exist = 1;
							}
						}

						//Không tồn tại thì thêm vào mảng kết quả
						if ($check_exist == 0) {
							$get = [
								'size' 			=> $size,
								'color' 		=> $color,
								'qty' 			=> $qty,
								'material_code' => $material_code
							];
							array_push($dataResults, $get);
						}
					} else { //trường hợp đầu tiên
						$get = [
							'size' 			=> $size,
							'color' 		=> $color,
							'qty' 			=> $qty,
							'material_code' => $material_code
						];
						array_push($dataResults, $get);
					}
				}
			}

			//return result data
			return $dataResults;
		}
	}

	// create prefix no
	public function createPrefixNo($production_line, $module)
	{
		$production_line = (strpos($production_line, ' ') !== false) ? str_replace(' ', '', $production_line) : $production_line;
		$production_line = strtolower($production_line);
		$module = (strpos($module, ' ') !== false) ? str_replace(' ', '', $module) : $module;

		// load models
		$this->load->model('woven_po_save');
		$this->load->model('common_prefix_no');

		// result array
		$po_date_new = '';
		$prefix_new = '';
		$suffix_new = '';

		// check
		if (empty($production_line) || empty($module)) {
			return false;
		}

		/*
            | ------------------------------------------------------------------------------------------------------------
            | 1. LẤY GIÁ TRỊ PREFIX TRONG BẢNG common_prefix_no
            | ------------------------------------------------------------------------------------------------------------
		*/
		$prefix = '';
		if ($this->common_prefix_no->isAlreadyExist($production_line, $module)) {
			$common_prefix_no_item = $this->common_prefix_no->readSingle($production_line, $module);
			$prefix = $common_prefix_no_item['prefix'];
		}

		// check
		if (empty($prefix)) return false;
		// to query
		$prefix_like = "%$prefix%";

		/*
            | ------------------------------------------------------------------------------------------------------------
            | 2. SET CÁC GIÁ TRỊ THỜI GIAN HIỆN TẠI VÀ PREFIX HIỆN TẠI
            | ------------------------------------------------------------------------------------------------------------
        */
		// $po_date_time = date('d-m-Y');
		// get po date
		$dateCheck = getdate();
		$day = $dateCheck['mday'];
		$mon = $dateCheck['mon'];
		$year = $dateCheck['year'];
		$hours = $dateCheck['hours'];

		$po_date_time = date_create("$day-$mon-$year");
		if ($hours >= 12) {
			date_add($po_date_time, date_interval_create_from_date_string("1 days"));
		}
		$po_date_time = date_format($po_date_time, "d-m-Y");

		$YearMonth = date('ym', strtotime($po_date_time)); // Lấy năm, tháng của hệ thống, trả về dạng: 2002
		// set giá trị tiền tố mới, so sánh với tiền tố PO_NO trong bảng (vừa lấy). // Nếu giống thì chỉ cần tăng hậu tố lên 1 đơn vị, ngược lại thì lấy tiền tố nào lớn
		$prefix_time = $prefix . $YearMonth;

		/*
            | ------------------------------------------------------------------------------------------------------------
            | 3. LẤY PO_NO MỚI NHẤT DỰA THEO MODULE (PREFIX)
            | ------------------------------------------------------------------------------------------------------------
        */
		$LastNO = $this->woven_po_save->getLastNO($production_line, $prefix_like);
		if (!empty($LastNO)) {
			/**  ------------------------- CREATE PO DATE ------------------------- -------------------------   */
			$po_date_cur = $LastNO['po_date'];
			// Nếu ngày po trong bảng >= ngày hệ thống thì lấy ngày hiện tại
			if (strtotime($po_date_cur) >= strtotime($po_date_time)) {
				$po_date_new = date('d-m-Y', strtotime($po_date_cur));
			} else {
				$po_date_new = date('d-m-Y', strtotime($po_date_time));
			}

			/**  ------------------------- CREATE PREFIX PO NO ------------------------- -------------------------   */
			//Tách PO_NO trong bảng save vừa lấy thành mảng
			$lastNO_arr = explode('-', $LastNO['po_no']);
			$prefix_cur = $lastNO_arr[0];
			$suffix_cur = (int)$lastNO_arr[1]; // Chuyển đổi thành kiểu số

			// So sánh hai tiền tố với nhau.
			// Trường hợp 1: Nếu prefix từ bảng save > prefix tháng năm hiện tại hoặc bằng => User đã fix tăng lên, nên lấy prefix trong bảng save
			if (strcmp($prefix_cur, $prefix_time) >= 0) {
				$prefix_new = $prefix_cur;

				//Sau khi có
				$suffix_new_tmp = $suffix_cur + 1;
				// Đếm số ký tự có hậu tố để thêm vào các dãy số 0 cho đúng định dạng
				$suffix_length = strlen((string)$suffix_new_tmp);
				// fix đúng định dạng
				if ($suffix_length == 1) {
					$suffix_new = '0000' . $suffix_new_tmp;
				} else if ($suffix_length == 2) {
					$suffix_new = '000' . $suffix_new_tmp;
				} else if ($suffix_length == 3) {
					$suffix_new = '00' . $suffix_new_tmp;
				} else if ($suffix_length == 4) {
					$suffix_new = '0' . $suffix_new_tmp;
				} else if ($suffix_length == 5) {
					$suffix_new = $suffix_new_tmp;
				}
			} else { //Trường hợp prefix hiện tại < prefix tháng năm => lấy prefix tháng năm (tăng lên theo tháng thực tế) mới, bắt đầu = 00001 (5 chữ số)
				$prefix_new = $prefix_time;
				$suffix_new = '-00001';
			}
		} else { // Trường hợp không tìm thấy dạng tiền tố truy vấn có trong po_save
			$po_date_new = date('d-m-Y', strtotime($po_date_time));
			// set po_no prefix
			$prefix_new = $prefix_time;
			$suffix_new = '-00001';
		}

		// result
		return array('po_date_new' => $po_date_new, 'prefix_new' => $prefix_new, 'suffix_new' => $suffix_new);
	}

	// create no
	public function createNoCCR($prefix)
	{
		$po_no_new = ''; // result

		// load models
		$this->load->model('woven_po_save');
		$this->load->model('common_prefix_no');

		$prefix = (strpos($prefix, ' ') !== false) ? str_replace(' ', '', $prefix) : $prefix; // NO2006 format

		$prefix_check = substr($prefix, 0, (strlen($prefix) - 4));
		$prefix_item = $this->common_prefix_no->readPrefix($prefix_check);
		if (empty($prefix_item)) {
			return false;
		} else {
			// to query
			$prefix_like = "%$prefix%";
			$production_line = strtolower($prefix_item['production_line']);
			$LastNO = $this->woven_po_save->getLastNO($production_line, $prefix_like);
			if (!empty($LastNO)) {

				//Tách PO_NO trong bảng save vừa lấy thành mảng
				$lastNO_arr = explode('-', $LastNO['po_no']);
				// $prefix_cur = $lastNO_arr[0];
				$suffix_cur = (int)$lastNO_arr[1]; // Chuyển đổi thành kiểu số

				//Sau khi có
				$suffix_new_tmp = $suffix_cur + 1;
				// Đếm số ký tự có hậu tố để thêm vào các dãy số 0 cho đúng định dạng
				$suffix_length = strlen((string)$suffix_new_tmp);
				// fix đúng định dạng
				if ($suffix_length == 1) {
					$suffix_new = '0000' . $suffix_new_tmp;
				} else if ($suffix_length == 2) {
					$suffix_new = '000' . $suffix_new_tmp;
				} else if ($suffix_length == 3) {
					$suffix_new = '00' . $suffix_new_tmp;
				} else if ($suffix_length == 4) {
					$suffix_new = '0' . $suffix_new_tmp;
				} else if ($suffix_length == 5) {
					$suffix_new = $suffix_new_tmp;
				}

				// set po_no new
				$po_no_new = $prefix . '-' . $suffix_new;
			} else {
				// set po_no new
				$suffix_new = '-00001';
				$po_no_new = $prefix . $suffix_new;
			}
		}

		return $po_no_new;
	}

	// get order type: ccr, buildstock, common (đơn batching)
	public function getType($batch_no, $so_line)
	{
		if (empty($batch_no)) {
			if (empty($so_line)) {
				setcookie('plan_order_type', 'buildstock', time() + 2592000, "/"); //1 month = time() + (86400 * 30) = 2592000
			} else {
				setcookie('plan_order_type', 'ccr', time() + 2592000, "/"); //1 month = time() + (86400 * 30) = 2592000
			}
		} else {
			setcookie('plan_order_type', 'common', time() + 2592000, "/"); //1 month = time() + (86400 * 30) = 2592000
		}

		$plan_order_type = isset($_COOKIE['plan_order_type']) ? strtolower($_COOKIE['plan_order_type']) : '';

		return trim($plan_order_type);
	}

	public function getPOShow($po_no, $po_no_suffix)
	{
		$suffix_arr = array('FOD', 'CCR');
		$po_no_suffix = strtoupper($po_no_suffix);
		$po_no_show = (in_array($po_no_suffix, $suffix_arr)) ? ($po_no . '-' . $po_no_suffix) : $po_no;

		return $po_no_show;
	}

	// loadOrders: ccr order and buildstock order
	public function loadData()
	{

		$prefixNoData = array();
		$automail_item = array();
		$automailData = array();
		$sizeData = array();
		$master_arr = array();
		$masterData = array();
		$supply_item = array();
		$supplyData = array();
		$processData = array();
		$formDataEdit = array();
		$batchingData = array();
		$error = 0;

		$batchingData = array(
			'socai_group_total' => '',
			'running_time_total' => '',
			'machine_type' => ''
		);

		// get data
		$so_line = null !== $this->input->get('so_line') ? trim($this->input->get('so_line')) : '';
		$item = null !== $this->input->get('item') ? trim($this->input->get('item')) : '';
		// po_no_edit
		$po_no_edit = null !== $this->input->get('po_no_edit') ? trim($this->input->get('po_no_edit')) : '';

		// get order type: ccr, buildstock, batching
		$plan_order_type = $this->getType('', $so_line);
		if ($plan_order_type == 'ccr') {
			$this->_data['title'] = 'Woven CCR Orders';
		} else if ($plan_order_type == 'buildstock') {
			$this->_data['title'] = 'Woven BuildStock Orders';
		} else {
			$this->_data['title'] = 'Woven Common Orders';
		}

		$production_line = isset($_COOKIE['plan_department']) ? strtolower($_COOKIE['plan_department']) : 'woven';

		// load models
		$this->load->model('woven_master_item');
		$this->load->model('woven_master_item_supply', 'supply');
		$this->load->model('woven_master_item_process', 'process');
		$this->load->model('common_setting_process', 'setting_process');

		$this->load->model('common_size_save', 'size_save');
		$this->load->model('woven_po_save');

		// if po_no_edit exist, get edit data
		if (!empty($po_no_edit)) {
			if ($this->woven_po_save->isAlreadyExist($po_no_edit)) {
				$po_no_item = $this->woven_po_save->readSingle($po_no_edit);
				$formDataEdit = array(
					'order_type' => $po_no_item['order_type'],
					'ordered_date' => $this->dateFormat($po_no_item['ordered_date']),
					'request_date' => $this->dateFormat($po_no_item['request_date']),
					'promise_date' => $this->dateFormat($po_no_item['promise_date']),
					'qty_total' => $po_no_item['qty_total']
				);
			}
		}

		// CHECK ITEM
		if (empty($item)) {
			$results = array(
				"status" => false,
				"message" => "Không nhận được dữ liệu!"
			);

			$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
			$this->load->view('woven/loadData', $this->_data);
		}


		// Nếu có nhập so_line => đơn bù, ngược lại => đơn buildstock
		if (empty($so_line)) {

			// đơn buildstock
			$automailData = [];
		} else {

			// automail
			$so_line_arr = $this->detached($so_line);
			if (empty($so_line_arr)) {
				$error++;
			} else {
				if ($this->automail->checkSOLine($so_line_arr['order_number'], $so_line_arr['line_number'])) {
					$automail_item = $this->automail->readSOLine($so_line_arr['order_number'], $so_line_arr['line_number']);
				} else if ($this->automail_closed->checkSOLine($so_line_arr['order_number'], $so_line_arr['line_number'])) {
					$automail_item = $this->automail_closed->readSOLine($so_line_arr['order_number'], $so_line_arr['line_number']);
				}
			}

			if (empty($automail_item)) {

				$results = array(
					"status" => false,
					"message" => "Không nhận được dữ liệu automail!"
				);
				$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
				$this->load->view('woven/loadData', $this->_data);
			}

			// get value
			$ship_to_customer = isset($automail_item[0]['SHIP_TO_CUSTOMER']) ? $automail_item[0]['SHIP_TO_CUSTOMER'] : '';
			$bill_to_customer = isset($automail_item[0]['BILL_TO_CUSTOMER']) ? $automail_item[0]['BILL_TO_CUSTOMER'] : '';
			$packing_instr = isset($automail_item[0]['PACKING_INSTRUCTIONS']) ? $automail_item[0]['PACKING_INSTRUCTIONS'] : '';
			$attachment = isset($automail_item[0]['VIRABLE_BREAKDOWN_INSTRUCTIONS']) ? $automail_item[0]['VIRABLE_BREAKDOWN_INSTRUCTIONS'] : '';

			$qty = isset($automail_item[0]['QTY']) ? $automail_item[0]['QTY'] : '';
			$ordered_item = isset($automail_item[0]['ORDERED_ITEM']) ? $automail_item[0]['ORDERED_ITEM'] : '';
			$order_type_name = isset($automail_item[0]['ORDER_TYPE_NAME']) ? $automail_item[0]['ORDER_TYPE_NAME'] : '';
			$ordered_date = isset($automail_item[0]['ORDERED_DATE']) ? $this->dateFormat($automail_item[0]['ORDERED_DATE']) : '';
			$request_date = isset($automail_item[0]['REQUEST_DATE']) ? $this->dateFormat($automail_item[0]['REQUEST_DATE']) : '';
			$promise_date = isset($automail_item[0]['PROMISE_DATE']) ? $this->dateFormat($automail_item[0]['PROMISE_DATE']) : '';
			$cs = isset($automail_item[0]['CS']) ? $automail_item[0]['CS'] : '';


			// Encode: htmlentities($materialDes2, ENT_QUOTES, 'UTF-8'); Decode: html_entity_decode()
			$checkQUOTES = array($ship_to_customer, $bill_to_customer, $packing_instr, $attachment);
			foreach ($checkQUOTES as $keyCheck => $valueChecked) {
				if (!empty($valueChecked)) {
					$valueChecked = htmlentities($valueChecked, ENT_QUOTES, 'UTF-8');
				}

				if ($keyCheck == 0) {
					$SHIP_TO_CUSTOMER = $valueChecked;
				}
				if ($keyCheck == 1) {
					$BILL_TO_CUSTOMER = $valueChecked;
				}
				if ($keyCheck == 2) {
					$PACKING_INSTR = $valueChecked;
				}
				if ($keyCheck == 3) {
					$ATTACHMENT = $valueChecked;
				}
			}

			$automailData[] = [
				'id' => 1,
				'data' => [
					1,
					$so_line,
					$item,
					$qty,
					$ordered_item,
					$order_type_name,
					$ordered_date,
					$request_date,
					$promise_date,
					$SHIP_TO_CUSTOMER,
					$BILL_TO_CUSTOMER,
					$cs,
					$PACKING_INSTR,
					$ATTACHMENT,
					''
				]
			];
		}


		$prefix_new = '';

		// get po date
		$dateCheck = getdate();
		$day = $dateCheck['mday'];
		$mon = $dateCheck['mon'];
		$year = $dateCheck['year'];
		$hours = $dateCheck['hours'];

		$po_date_new = date_create("$day-$mon-$year");
		if ($hours >= 12) {
			date_add($po_date_new, date_interval_create_from_date_string("1 days"));
		}

		$prefixNo_arr = $this->createPrefixNo($production_line, 'NO');

		if ($prefixNo_arr != false) {

			// edit
			if (!empty($po_no_edit)) {
				$po_no_item_check = '';
				if ($this->woven_po_save->isAlreadyExist($po_no_edit)) {
					$po_no_item_check = $this->woven_po_save->readSingle($po_no_edit);
					$prefix_new = $po_no_edit;
					$po_date_new = date('d-m-Y', strtotime($po_no_item_check['po_date']));
				}
			} else {
				$prefix_new = $prefixNo_arr['prefix_new'];
				$po_date_new = $prefixNo_arr['po_date_new'];
			}

			$prefixNoData = [
				'prefix_new' => $prefix_new,
				'po_date_new' => $po_date_new
			];
		} else {
			$prefixNoData = [
				'prefix_new' => 'NO' . date('ym'),
				'po_date_new' => $po_date_new
			];
		}

		// master data
		if (!$this->woven_master_item->checkItem($item)) {

			$results = array(
				"status" => false,
				"message" => "Load master data error. Check Master data, please (1) "
			);

			$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
			$this->load->view('woven/loadData', $this->_data);
		}

		// get master data
		$master_arr = $this->woven_master_item->readSingle($item);

		// Size:
		$keySize = 1;
		if (!empty($po_no_edit)) {

			// edit production order
			if ($this->size_save->isAlreadyExist(array('no_number' => $po_no_edit))) {
				$size_item = $this->size_save->readPO($po_no_edit);
				foreach ($size_item as $size_item_value) {
					$sizeData[] = [
						'id' => $keySize,
						'data' => [1, $size_item_value['size'], $size_item_value['so_cai_size'], $size_item_value['qty']]
					];

					$keySize++;
				}
			} else {
				$sizeData = [];
			}
		} else {

			// new production order
			if (empty($so_line)) {
				if ($master_arr[0]['cbs'] == 1) {
					$sizeData = [];
				} else {
					$sizeData[] = [
						'id' => $keySize,
						'data' => [1, 'non', '', '']
					];
				}
			} else {
				if ($master_arr[0]['cbs'] == 1) {

					// getSize
					// $sizeCheck = $this->getSize($so_line);
					// if (empty($sizeCheck)) {
					// 	$sizeCheck = $this->getSizeAutomail($attachment);
					// }

					$sizeCheck = $this->getSizeAutomail($attachment);
					// print_r($sizeCheck);
					if (!empty($sizeCheck)) {
						foreach ($sizeCheck as $size_item) {
							$sizeData[] = [
								'id' => $keySize,
								'data' => [0, $size_item['size'], '', '']
							];

							$keySize++;
						}
					} else {
						$sizeData = [];
					}
				} else { // no size

					$sizeData[] = [
						'id' => $keySize,
						'data' => [1, 'non', '', $automail_item[0]['QTY']]
					];
				}
			}
		}

		// MASTER FILE ALL
		$masterId = 0;
		$supplyIndex = 0;
		foreach ($master_arr as $master_item) {
			$masterId++;

			$process = $master_item['process'];
			$folding_cut_type = trim($master_item['folding_cut_type']);

			// MASTER DATA
			$masterData[] = [
				'id' => $masterId,
				'data' => [
					strtoupper($master_item['machine_type']),
					$master_item['length_btp'],
					$item,
					htmlentities($master_item['rbo'], ENT_QUOTES, 'UTF-8'),
					$master_item['wire_number'],
					$master_item['vertical_thread_type'],
					$master_item['folding_cut_type'],
					$master_item['pattern'],
					$master_item['gear_density'],
					$master_item['length_tp'],
					$master_item['width_tp'],
					$master_item['cbs'],
					$master_item['scrap'],
					$master_item['sawing_method'],
					$master_item['cw_specification'],
					$master_item['heat_weaving'],
					$master_item['meter_number_per_machine'],
					$master_item['water_glue_rate'],

					$master_item['so_cai_min'],
					$master_item['taffeta_satin'],
					$master_item['textile_size_number'],
					$master_item['new_wire_number'],

					htmlentities($master_item['remark_1'], ENT_QUOTES, 'UTF-8'),
					htmlentities($master_item['remark_2'], ENT_QUOTES, 'UTF-8'),
					htmlentities($master_item['remark_3'], ENT_QUOTES, 'UTF-8'),
					// $master_item['remark_1'],
					// $master_item['remark_2'],
					// $master_item['remark_3'],
					$master_item['updated_by'],
					$master_item['updated_date'],
					$master_item['width_btp'],
					$master_item['special_item_remark'],
					$process
				]
			];

			// SUPPLY
			$where = array('internal_item' => $item, 'length_btp' => $master_item['length_btp']);
			if ($this->supply->checkMasterItem($where)) {

				if ($this->supply->checkMasterItem($where)) {
					$supply_item = $this->supply->readSingle($where);
					$supplyId = 0;
					$glueId = 0;
					$supplyIndex++;
					$supplyCheck = array();
					$glueCheck = array();

					foreach ($supply_item as $supply_data) {

						if ($supply_data['code_type'] == 'glue') {
							$glueId++;
							$glueCheck[] = [
								'id' => $glueId,
								'data' => [
									$supply_data['order'],
									$supply_data['code_name']
								]
							];
						} else {
							$supplyId++;
							$supplyCheck[] = [
								'id' => $supplyId,
								'data' => [
									$supply_data['order'],
									$supply_data['code_name'],
									$supply_data['density'],
									$supply_data['pick_number'],
									$this->thread_length($supply_data['code_name'], $supply_data['density']),
									''
								]
							];
						}
					}

					$supplyData[] = [
						'index' => $supplyIndex,
						'machine_type' => $master_item['machine_type'],
						'internal_item' => $item,
						'length_btp' => $master_item['length_btp'],
						'supply' => $supplyCheck,
						'glue' => $glueCheck
					];
				}
			} else {
				continue;
			}
		} // for master data


		// PROCESS
		$process_arr = explode('-', $process);
		if (!empty($process_arr)) {

			$processIndex = 0;
			foreach ($process_arr as $process_code) {

				if (!$this->setting_process->isAlreadyExist($this->production_line, $process_code)) {

					$results = array(
						"status" => false,
						"message" => "Load master data error. Check Master data, please (3) "
					);

					$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
					$this->load->view('woven/loadData', $this->_data);
				} else {
					$where_setting_process = array('production_line' => $this->production_line, 'process_code' => $process_code);
					$setting_process = $this->setting_process->readSingle($where_setting_process);

					$process_name = $process_code . "-" . trim($setting_process['process_name_vi']);

					if ($process_code == 'CG') {
						$process_name .= " $folding_cut_type";
					}

					$processIndex++;
					$processData[] = array(
						'process_code' => $process_code,
						'process_name' => $process_name,
						'process_order' => $processIndex
					);
				}
			}
		}


		// results OK
		$results = array(
			"status" => true,
			"message" => "Load data success",
			"prefixNoData" => $prefixNoData,
			"automailData" => $automailData,
			"masterData" => $masterData,
			"supplyData" => $supplyData,
			"processData" => $processData,
			"sizeData" => $sizeData,
			"formDataEdit" => $formDataEdit,
			"batchingData" => $batchingData
		);

		// render
		$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);

		$this->load->view('woven/loadData', $this->_data);
	}

	// commonOrder: đơn batching
	public function commonOrder()
	{
		// init var
		$prefixNoData = array();
		$automail_item = array();
		$automailData = array();
		$sizeData = array();
		$master_arr = array();
		$masterData = array();
		$supply_item = array();
		$supplyData = array();
		$processData = array();
		$formDataEdit = array();
		$batchingData = array();

		// get GET method
		$batch_no = null !== $this->input->get('batch_no') ? trim($this->input->get('batch_no')) : '';
		// po_no_edit
		$po_no_edit = null !== $this->input->get('po_no_edit') ? trim($this->input->get('po_no_edit')) : '';



		// load models
		$this->load->model('woven_master_item');
		$this->load->model('woven_master_item_supply', 'supply');
		$this->load->model('woven_master_item_process', 'process');
		$this->load->model('common_setting_process', 'setting_process');

		$this->load->model('common_size_save', 'size_save');
		$this->load->model('woven_po_save');

		$this->load->model('woven_prepress_batching', 'batching');
		$this->load->model('woven_ppc_so_line');


		// check batch_no empty
		if (empty($batch_no)) {
			$results = array(
				"status" => false,
				"message" => "Không nhận được dữ liệu batching!"
			);
			$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
			$this->load->view('woven/loadData', $this->_data); // exit();
		}

		// check batching exist
		if (!$this->woven_ppc_so_line->isAlreadyExist(array('batch_no' => $batch_no))) {
			$results = array(
				"status" => false,
				"message" => "Dữ liệu batching không tìm thấy!"
			);
			$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
			$this->load->view('woven/loadData', $this->_data); //exit();
		}

		if (!$this->batching->isAlreadyExist(array('batch_no' => $batch_no))) {
			$results = array(
				"status" => false,
				"message" => "Dữ liệu batching không tìm thấy trong bảng prepress batching!"
			);
			$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
			$this->load->view('woven/loadData', $this->_data); //exit();
		}

		// get production line data
		$production_line = isset($_COOKIE['plan_department']) ? strtolower($_COOKIE['plan_department']) : 'woven';
		// get order type 
		$plan_order_type = $this->getType($batch_no, '');
		if ($plan_order_type == 'ccr') {
			$this->_data['title'] = 'Woven CCR Orders';
		} else if ($plan_order_type == 'buildstock') {
			$this->_data['title'] = 'Woven BuildStock Orders';
		} else if ($plan_order_type == 'common') {
			$this->_data['title'] = 'Woven Batching Orders';
			if (isset($_COOKIE['non_batching']) && !empty($_COOKIE['non_batching'])) {
				$this->_data['title'] = 'Woven Non Batching Orders';
			}
		}


		// get data from prd_plan_ppc_so_line table
		$ppc_soline = $this->woven_ppc_so_line->readSOLineDistinct($batch_no);

		// check batching
		$batching_status = (int)$ppc_soline[0]['status'];
		if ($batching_status !== 2) {
			$results = array(
				"status" => false,
				"message" => "Đơn chưa batching! (Trạng thái $batching_status)"
			);
			$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
			$this->load->view('woven/loadData', $this->_data); //exit();
		}

		// get prefix NO: NO, NS
		$prefix = isset($ppc_soline[0]['sol_sts']) ? trim($ppc_soline[0]['sol_sts']) : '';
		$prefix = strtolower($prefix);
		// get FOD order
		$fod_sts = isset($ppc_soline[0]['fod_sts']) ? trim($ppc_soline[0]['fod_sts']) : '';
		$fod_sts = (int)$fod_sts;
		// get item code (from batching data)
		$item = isset($ppc_soline[0]['item_code']) ? trim($ppc_soline[0]['item_code']) : '';

		/* ================= AUTOMAIL DATA ========================================================================================== */
		$indexS = 0;
		$indexSC = 1;
		foreach ($ppc_soline as $ppc_soline_item) {

			// detache
			$indexS++;
			$so_line = trim($ppc_soline_item['so_line']);
			$so_line_arr = $this->detached($so_line);

			if (empty($so_line_arr)) {
				$automailData = [];
			} else {
				if ($this->automail->checkSOLine($so_line_arr['order_number'], $so_line_arr['line_number'])) {
					$automail_item = $this->automail->readSOLine($so_line_arr['order_number'], $so_line_arr['line_number']);
				} else if ($this->automail_closed->checkSOLine($so_line_arr['order_number'], $so_line_arr['line_number'])) {
					$automail_item = $this->automail_closed->readSOLine($so_line_arr['order_number'], $so_line_arr['line_number']);
				}
			}

			if (empty($automail_item)) {

				$results = array(
					"status" => false,
					"message" => "Không nhận được dữ liệu automail!"
				);
				$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
				$this->load->view('woven/loadData', $this->_data);
				break;
			}

			$ship_to_customer = isset($automail_item[0]['SHIP_TO_CUSTOMER']) ? $automail_item[0]['SHIP_TO_CUSTOMER'] : '';
			$bill_to_customer = isset($automail_item[0]['BILL_TO_CUSTOMER']) ? $automail_item[0]['BILL_TO_CUSTOMER'] : '';
			$packing_instr = isset($automail_item[0]['PACKING_INSTRUCTIONS']) ? $automail_item[0]['PACKING_INSTRUCTIONS'] : '';
			$attachment = isset($automail_item[0]['VIRABLE_BREAKDOWN_INSTRUCTIONS']) ? $automail_item[0]['VIRABLE_BREAKDOWN_INSTRUCTIONS'] : '';

			$item = isset($automail_item[0]['ITEM']) ? $automail_item[0]['ITEM'] : '';
			$qty = isset($automail_item[0]['QTY']) ? $automail_item[0]['QTY'] : '';
			$ordered_item = isset($automail_item[0]['ORDERED_ITEM']) ? $automail_item[0]['ORDERED_ITEM'] : '';
			$order_type_name = isset($automail_item[0]['ORDER_TYPE_NAME']) ? $automail_item[0]['ORDER_TYPE_NAME'] : '';

			$ordered_date = isset($automail_item[0]['ORDERED_DATE']) ? $this->dateFormat($automail_item[0]['ORDERED_DATE']) : '';
			$request_date = isset($automail_item[0]['REQUEST_DATE']) ? $this->dateFormat($automail_item[0]['REQUEST_DATE']) : '';
			$promise_date = isset($automail_item[0]['PROMISE_DATE']) ? $this->dateFormat($automail_item[0]['PROMISE_DATE']) : '';

			$cs = isset($automail_item[0]['CS']) ? $automail_item[0]['CS'] : '';

			// Encode: htmlentities($materialDes2, ENT_QUOTES, 'UTF-8'); Decode: html_entity_decode()
			$checkQUOTES = array($ship_to_customer, $bill_to_customer, $packing_instr, $attachment);
			foreach ($checkQUOTES as $keyCheck => $valueChecked) {
				if (!empty($valueChecked)) {
					$valueChecked = htmlentities($valueChecked, ENT_QUOTES, 'UTF-8');
				}

				if ($keyCheck == 0) {
					$SHIP_TO_CUSTOMER = $valueChecked;
				}
				if ($keyCheck == 1) {
					$BILL_TO_CUSTOMER = $valueChecked;
				}
				if ($keyCheck == 2) {
					$PACKING_INSTR = $valueChecked;
				}
				if ($keyCheck == 3) {
					$ATTACHMENT = $valueChecked;
				}
			}

			$automailData[] = [
				'id' => $indexS,
				'data' => [
					$indexSC,
					$so_line,
					$item,
					$qty,
					$ordered_item,
					$order_type_name,
					$ordered_date,
					$request_date,
					$promise_date,
					$SHIP_TO_CUSTOMER,
					$BILL_TO_CUSTOMER,
					$cs,
					$PACKING_INSTR,
					$ATTACHMENT,
					$batch_no
				]
			];

			// $indexSC = ($plan_order_type == 'common' ) ? $indexS++ : 1;

			if ($plan_order_type == 'common') {
				$indexSC++;
			}
		}

		/* ================= IF EDIT ORDER ========================================================================================== */
		if (!empty($po_no_edit)) {
			if ($this->woven_po_save->isAlreadyExist($po_no_edit)) {
				$po_no_item = $this->woven_po_save->readSingle($po_no_edit);
				$formDataEdit = array(
					'order_type' => $po_no_item['order_type'],
					'ordered_date' => $this->dateFormat($po_no_item['ordered_date']),
					'request_date' => $this->dateFormat($po_no_item['request_date']),
					'promise_date' => $this->dateFormat($po_no_item['promise_date']),
					'qty_total' => $po_no_item['qty_total']
				);
			}
		}

		/* ================= GET PO_NO PREFIX ========================================================================================== */
		$prefix_new = '';
		$po_date_new = date('d-m-Y');
		$prefixNo_arr = $this->createPrefixNo($production_line, $prefix);

		if ($prefixNo_arr != false) {

			// edit
			if (!empty($po_no_edit)) {
				$po_no_item_check = '';
				if ($this->woven_po_save->isAlreadyExist($po_no_edit)) {
					$po_no_item_check = $this->woven_po_save->readSingle($po_no_edit);
					$prefix_new = $po_no_edit;
					$po_date_new = date('d-m-Y', strtotime($po_no_item_check['po_date']));
				}
			} else {
				$prefix_new = $prefixNo_arr['prefix_new'];
				$po_date_new = $prefixNo_arr['po_date_new'];
			}


			$prefixNoData = [
				'prefix_new' => $prefix_new,
				'po_date_new' => $po_date_new,
				'fod' => $fod_sts
			];
		} else {
			$prefixNoData = [
				'prefix_new' => 'NO' . date('ym'),
				'po_date_new' => $po_date_new,
				'fod' => $fod_sts
			];
		}

		/* ================= GET BATCHING DATA AND SIZE DATA ========================================================================================== */
		$batching_data = $this->batching->readSingle2($batch_no);
		$length_btp_1 = (string)$batching_data[0]['label_length'];

		$length_btp_arr = explode('.', $length_btp_1);
		// $length_btp = $length_btp_arr[0];
		$length_btp = (float)$length_btp_1; // sử dụng kiểu dữ liệu float


		$keySize = 1;
		$group_num_check = 999;
		$socai_group_total = 0;

		foreach ($batching_data as $batching_item) {
			$sizeData[] = [
				'id' => $keySize,
				'data' => [1, $batching_item['size'], $batching_item['socai_scrap'], $batching_item['qty']]
			];

			// get machine type
			$machine_type = trim(strtolower($batching_item['machine']));
			// get socai_group total && running time total
			$group_num = (int)$batching_item['group_num'];
			$running_time_total = $batching_item['running_time_total'];
			if ($group_num !== $group_num_check) {
				$socai_group_total = $socai_group_total +  (int)$batching_item['socai_group'];
			} else {
			}

			$group_num_check = $group_num;
			$keySize++;
		}



		// tính toán lại giá trị số lượng từng so_line nếu là đơn NS
		$ns_so_line_arr = array();
		foreach ($ppc_soline as $kSo => $soCheck) {
			$ns_so_line_qty = 0;
			$ns_so_line = trim($soCheck['so_line']);
			if ($kSo == 0) $soCheckTmp = trim($ns_so_line);

			foreach ($batching_data as $batching_item) {
				// check so_line qty (NS orders)
				if (trim($soCheck['so_line']) == $soCheckTmp) {
					$ns_so_line_qty += $batching_item['qty'];
				}
			}

			if (strpos(strtoupper($prefix_new), 'NS') !== false) {
				$ns_so_line_arr[] = array(
					'so_line' => $ns_so_line,
					'qty' => $ns_so_line_qty
				);
			}
		}


		// Nếu là đơn NS, lấy số lượng từng SOLine trong dữ liệu batching, Nếu đơn NO thì lấy trong automail
		if (strpos(strtoupper($prefix_new), 'NS') !== false && !empty($ns_so_line_arr)) {
			foreach ($ns_so_line_arr as $ns_so_line_item) {
				foreach ($automailData as $kA => $autoItem) {
					if ($ns_so_line_item['so_line'] == $autoItem['data'][1]) {
						$automailData[$kA]['data'][3] = $ns_so_line_item['qty'];
						break;
					}
				}
			}
		}

		$batchingData = array(
			'socai_group_total' => $socai_group_total,
			'running_time_total' => $running_time_total,
			'machine_type' => $machine_type
		);

		/* ================= MASTER DATA ========================================================================================== */
		if (!$this->woven_master_item->checkItemLength($item, $length_btp)) {
			$results = array(
				"status" => false,
				"message" => "Master Data không có Item: $item và length: $length_btp !"
			);
			$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
			$this->load->view('woven/loadData', $this->_data); // exit();
		}

		$master_arr = $this->woven_master_item->readItemLength(array('internal_item' => $item, 'length_btp' => $length_btp));

		$masterId = 0;
		$supplyIndex = 0;
		$process = '';
		$folding_cut_type = '';
		foreach ($master_arr as $master_item) {
			$masterId++;

			$process = trim($master_item['process']);
			$folding_cut_type = trim($master_item['folding_cut_type']);

			// Master data
			$masterData[] = [
				'id' => $masterId,
				'data' => [
					strtoupper($master_item['machine_type']),
					$master_item['length_btp'],
					$item,
					htmlentities($master_item['rbo'], ENT_QUOTES, 'UTF-8'),
					$master_item['wire_number'],
					$master_item['vertical_thread_type'],
					$master_item['folding_cut_type'],
					$master_item['pattern'],
					$master_item['gear_density'],
					$master_item['length_tp'],
					$master_item['width_tp'],
					$master_item['cbs'],
					$master_item['scrap'],
					$master_item['sawing_method'],
					$master_item['cw_specification'],
					$master_item['heat_weaving'],
					$master_item['meter_number_per_machine'],
					$master_item['water_glue_rate'],

					$master_item['so_cai_min'],
					$master_item['taffeta_satin'],
					$master_item['textile_size_number'],
					$master_item['new_wire_number'],

					htmlentities($master_item['remark_1'], ENT_QUOTES, 'UTF-8'),
					htmlentities($master_item['remark_2'], ENT_QUOTES, 'UTF-8'),
					htmlentities($master_item['remark_3'], ENT_QUOTES, 'UTF-8'),
					// $master_item['remark_1'],
					// $master_item['remark_2'],
					// $master_item['remark_3'],
					$master_item['updated_by'],
					$master_item['updated_date'],
					$master_item['width_btp'],
					$master_item['special_item_remark'],
					$process
				]
			];

			$where = array('internal_item' => $item, 'length_btp' => $master_item['length_btp']);
			if ($this->supply->checkMasterItem($where)) {

				// supply
				if ($this->supply->checkMasterItem($where)) {
					$supply_item = $this->supply->readSingle($where);
					$supplyId = 0;
					$glueId = 0;
					$supplyIndex++;
					$supplyCheck = array();
					$glueCheck = array();

					foreach ($supply_item as $supply_data) {

						if ($supply_data['code_type'] == 'glue') {
							$glueId++;
							$glueCheck[] = [
								'id' => $glueId,
								'data' => [
									$supply_data['order'],
									$supply_data['code_name']
								]
							];
						} else {
							$supplyId++;
							$supplyCheck[] = [
								'id' => $supplyId,
								'data' => [
									$supply_data['order'],
									$supply_data['code_name'],
									$supply_data['density'],
									$supply_data['pick_number'],
									$this->thread_length($supply_data['code_name'], $supply_data['density']),
									''
								]
							];
						}
					}

					$supplyData[] = [
						'index' => $supplyIndex,
						'machine_type' => $master_item['machine_type'],
						'internal_item' => $item,
						'length_btp' => $master_item['length_btp'],
						'supply' => $supplyCheck,
						'glue' => $glueCheck
					];
				}
			} else {
				continue;
			}
		} // for


		// PROCESS
		$process_arr = explode('-', $process);
		if (!empty($process_arr)) {

			$processIndex = 0;
			foreach ($process_arr as $process_code) {

				if (!$this->setting_process->isAlreadyExist($this->production_line, $process_code)) {

					$results = array(
						"status" => false,
						"message" => "Load master data error. Check Master data, please (3) "
					);

					$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
					$this->load->view('woven/loadData', $this->_data);
				} else {
					$where_setting_process = array('production_line' => $this->production_line, 'process_code' => $process_code);
					$setting_process = $this->setting_process->readSingle($where_setting_process);

					$process_name = $process_code . "-" . trim($setting_process['process_name_vi']);

					if ($process_code == 'CG') {
						$process_name .= " $folding_cut_type";
					}

					$processIndex++;
					$processData[] = array(
						'process_code' => $process_code,
						'process_name' => $process_name,
						'process_order' => $processIndex
					);
				}
			}
		}


		/* ================= RESULTS ========================================================================================== */
		$results = array(
			"status" => true,
			"message" => "Load data success",
			"prefixNoData" => $prefixNoData,
			"automailData" => $automailData,
			"masterData" => $masterData,
			"supplyData" => $supplyData,
			"processData" => $processData,
			"sizeData" => $sizeData,
			"formDataEdit" => $formDataEdit,
			"batchingData" => $batchingData
		);

		$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
		$this->load->view('woven/loadData', $this->_data);
	}

	// edit batching order
	public function batchingOrderEdit()
	{
		$dataPost = isset($_POST["data"]) ? $_POST["data"] : '';

		$dataPost = json_decode($dataPost, true);
		if (empty($dataPost)) {
			$this->_data['results'] = array(
				"status" => false,
				"message" => "Không nhận được dữ liệu POST!"
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		// load models
		$this->load->model('woven_po_soline_save');
		$this->load->model('woven_po_save');
		$this->load->model('woven_ppc_so_line');

		$po_no_edit = '';

		$batch_no = trim($dataPost['batch_no']);

		// đơn non batching
		if (strpos($batch_no, '-') !== false) {
			// lấy số batch_no trong bảng batching
			$where = array('so_line' => $batch_no);
			if (!$this->woven_ppc_so_line->isAlreadyExist($where)) {
				$this->_data['results'] = array(
					"status" => false,
					"message" => "Không có dữ liệu NON Batching"
				);
			} else {
				$ppcItem = $this->woven_ppc_so_line->readItem($where);
				$batch_no = trim($ppcItem['batch_no']);
			}
		}

		if (!$this->woven_po_save->checkBatching($batch_no)) {
			$this->_data['results'] = array(
				"status" => false,
				"message" => "Không tìm thấy Batch number $batch_no trong po_save table "
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		// get po_no_edit
		$po_no_item = $this->woven_po_save->readItem(array('batch_no' => $batch_no));
		$po_no_edit = $po_no_item['po_no'];

		// results
		$this->_data['results'] = array(
			"status" => true,
			"message" => "Success",
			"batch_no" => $batch_no,
			"po_no_edit" => $po_no_edit
		);
		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
		exit();
	}

	// số khổ
	public function textile_size_number($taffeta_satin, $machine_type)
	{
		$taffeta_satin = (strpos($taffeta_satin, ' ') !== false) ? str_replace(' ', '', $taffeta_satin) : $taffeta_satin;
		$taffeta_satin = strtolower($taffeta_satin);

		$machine_type = (strpos($machine_type, ' ') !== false) ? str_replace(' ', '', $machine_type) : $machine_type;
		$machine_type = strtolower($machine_type);

		$textile_size_number = 0;

		if ($taffeta_satin == 'taffeta') {
			if ($machine_type == 'wv') {
				$textile_size_number = 5;
			} else {
				// cw, lb
				$textile_size_number = 6;
			}
		} else if ($taffeta_satin == 'satin') {
			$textile_size_number = 10;
		}

		return $textile_size_number;
	}

	// save order data
	public function saveOrders()
	{
		/*
            | ------------------------------------------------------------------------------------------------------------
            | 1.  GET DATA POST METHOD
            | ------------------------------------------------------------------------------------------------------------
        */
		// data POST
		$data = isset($_POST["data"]) ? $_POST["data"] : '';
		// $data = '{"poDataSave":{"production_line":"woven","machine_type":"wv","po_no":"NO2106","type":"common","batch_no":"1233052","po_no_suffix":"NORMAL","order_type":"VN GEN - VAT","count_lines":2,"po_date":"6/4/2021, 12:00:00 AM","ordered_date":"2021-05-31T17:00:00.000Z","request_date":"2021-06-15T17:00:00.000Z","promise_date":"2021-06-15T17:00:00.000Z","qty_total":1096,"pick_number_total":1070,"thread_length_total":360000,"need_horizontal_thread_total":1.1,"count_supply":3,"need_vertical_thread_number":0.13,"warp_yarn_number":114,"meters_per_roll":0,"so_cai_total":95,"running_time_total":"3.75","printed":0,"internal_item":"WY520378A","length_btp":"52","width_btp":"19","rbo":"EXPRESS","wire_number":"48","vertical_thread_type":"50-SW0","folding_cut_type":"CL","pattern":"1S","gear_density":"34.3T","length_tp":"26","width_tp":"19","cbs":"1","scrap":"15","cut_type":"Sonic/CL","sawing_method":"xẻ khổ","cw_specification":"0","heat_weaving":"145","meter_number_per_machine":"1.3","water_glue_rate":"","so_cai_min":"15","taffeta_satin":"satin","textile_size_number":"10","new_wire_number":"3","remark_1":"","remark_2":"","remark_3":"","special_item_remark":"","updated_by":"updated_by"},"processDataSave":[{"po_no":"NO2106","machine_type":"wv","internal_item":"WY520378A","length_btp":"52","process_code":"wv_01","status":1},{"po_no":"NO2106","machine_type":"wv","internal_item":"WY520378A","length_btp":"52","process_code":"wv_02","status":1},{"po_no":"NO2106","machine_type":"wv","internal_item":"WY520378A","length_btp":"52","process_code":"wv_03","status":0},{"po_no":"NO2106","machine_type":"wv","internal_item":"WY520378A","length_btp":"52","process_code":"wv_04","status":0},{"po_no":"NO2106","machine_type":"wv","internal_item":"WY520378A","length_btp":"52","process_code":"wv_05","status":1},{"po_no":"NO2106","machine_type":"wv","internal_item":"WY520378A","length_btp":"52","process_code":"wv_06","status":0},{"po_no":"NO2106","machine_type":"wv","internal_item":"WY520378A","length_btp":"52","process_code":"wv_07","status":1},{"po_no":"NO2106","machine_type":"wv","internal_item":"WY520378A","length_btp":"52","process_code":"wv_08","status":0},{"po_no":"NO2106","machine_type":"wv","internal_item":"WY520378A","length_btp":"52","process_code":"wv_09","status":1}],"supplyDataSave":[{"po_no":"NO2106","machine_type":"wv","internal_item":"WY520378A","length_btp":"52","code_name":"LY075120006948","code_type":"supply","density":"75D","pick_number":371,"order":1,"thread_length":120000,"need_horizontal_thread":0.38},{"po_no":"NO2106","machine_type":"wv","internal_item":"WY520378A","length_btp":"52","code_name":"LY075120006948","code_type":"supply","density":"75D","pick_number":357,"order":2,"thread_length":120000,"need_horizontal_thread":0.37},{"po_no":"NO2106","machine_type":"wv","internal_item":"WY520378A","length_btp":"52","code_name":"30001629","code_type":"supply","density":"75D","pick_number":342,"order":3,"thread_length":120000,"need_horizontal_thread":0.35}],"solineDataSave":[{"po_no":"NO2106","so_line":"53515120-2","internal_item":"WY520378A","length_btp":"52","qty_of_line":"1076","running_time":"3.68","count_size":5,"warp_yarn":"warp_yarn","ordered_item":"UPW-0051SCO","order_type_name":"VN GEN - VAT","ship_to_customer":"KHO PHUOC LONG CUA PHONG PHU","bill_to_customer":"CONG TY CO PHAN QUOC TE PHONG PHU","cs":"Ngo, Kalina","packing_instr":"","attachment":";  SIZE  :  QTY ;  XS (XS)  :  188 ;  S (S)  :  312 ;  M (M)  :  291 ;  L (L)  :  185 ;  XL (XL)  :  100 ;   ^"},{"po_no":"NO2106","so_line":"53515129-2","internal_item":"WY520378A","length_btp":"52","qty_of_line":"20","running_time":"0.07","count_size":5,"warp_yarn":"warp_yarn","ordered_item":"UPW-0051SCO","order_type_name":"VN SAM","ship_to_customer":"KHO PHUOC LONG CUA PHONG PHU","bill_to_customer":"CONG TY CO PHAN QUOC TE PHONG PHU","cs":"Ngo, Kalina","packing_instr":"","attachment":";  SIZE  :  QTY ;  M (M)  :  20 ;   ^"}],"sizeDataSave":[{"up_user":"","production_line":"woven","no_number":"","so_line":"53515120-2","size":"L (L)","color":"non","qty":"185","material_code":"","so_cai_size":20,"target_qty":960},{"up_user":"","production_line":"woven","no_number":"","so_line":"53515120-2","size":"XL (XL)","color":"non","qty":"100","material_code":"","so_cai_size":18,"target_qty":864},{"up_user":"","production_line":"woven","no_number":"","so_line":"53515120-2","size":"XS (XS)","color":"non","qty":"188","material_code":"","so_cai_size":20,"target_qty":960},{"up_user":"","production_line":"woven","no_number":"","so_line":"53515120-2","size":"M (M)","color":"non","qty":"311","material_code":"","so_cai_size":23,"target_qty":1104},{"up_user":"","production_line":"woven","no_number":"","so_line":"53515120-2","size":"S (S)","color":"non","qty":"312","material_code":"","so_cai_size":23,"target_qty":1104}]}';
		// $data = '{"poDataSave":{"production_line":"woven","machine_type":"wv","po_no":"NO2110","type":"buildstock","batch_no":"","po_no_suffix":"BUILDSTOCK","order_type":"NORMAL","count_lines":1,"po_date":"10/6/2021,+12:00:00+AM","ordered_date":"2021-10-04T20:54:00.000Z","request_date":"2021-10-04T17:00:00.000Z","promise_date":"2021-10-04T17:00:00.000Z","qty_total":12,"pick_number_total":2020,"thread_length_total":600000,"need_horizontal_thread_total":0.14,"count_supply":2,"need_vertical_thread_number":0.02,"warp_yarn_number":114,"meters_per_roll":0,"so_cai_total":15,"running_time_total":"0.94","printed":0,"internal_item":"WX079980A","length_btp":"84","width_btp":"20","rbo":"EDDIE+BAUER","wire_number":"45","vertical_thread_type":"50-W0","folding_cut_type":"CL","pattern":"1T","gear_density":"39.6T","length_tp":"42","width_tp":"20","cbs":"1","scrap":"12","cut_type":"Sonic/CL","sawing_method":"xẻ+khổ","cw_specification":"0","heat_weaving":"145","meter_number_per_machine":"1.3","water_glue_rate":"","so_cai_min":"15","taffeta_satin":"taffeta","textile_size_number":"5","new_wire_number":"7","remark_1":"","remark_2":"","remark_3":"","special_item_remark":"","process":"DE-XS-ND-CG-DG","updated_by":"updated_by"},"processDataSave":[{"po_no":"NO2110","machine_type":"wv","internal_item":"WX079980A","length_btp":"84","process_code":"DE","process_name":"Dệt","process_order":1,"status":1},{"po_no":"NO2110","machine_type":"wv","internal_item":"WX079980A","length_btp":"84","process_code":"XS","process_name":"Xẻ+Sonic","process_order":2,"status":1},{"po_no":"NO2110","machine_type":"wv","internal_item":"WX079980A","length_btp":"84","process_code":"ND","process_name":"Nối+Đầu","process_order":3,"status":1},{"po_no":"NO2110","machine_type":"wv","internal_item":"WX079980A","length_btp":"84","process_code":"CG","process_name":"Cắt+Gấp","process_order":4,"status":1},{"po_no":"NO2110","machine_type":"wv","internal_item":"WX079980A","length_btp":"84","process_code":"DG","process_name":"Đóng+Gói","process_order":5,"status":1}],"supplyDataSave":[{"po_no":"NO2110","machine_type":"wv","internal_item":"WX079980A","length_btp":"84","code_name":"LY0301111-000224","code_type":"supply","density":"30D","pick_number":1010,"order":1,"thread_length":300000,"need_horizontal_thread":0.07},{"po_no":"NO2110","machine_type":"wv","internal_item":"WX079980A","length_btp":"84","code_name":"LY0301111-000112","code_type":"supply","density":"30D","pick_number":1010,"order":2,"thread_length":300000,"need_horizontal_thread":0.07}],"solineDataSave":[{"po_no":"NO2110","so_line":"non","internal_item":"WX079980A","length_btp":"84","qty_of_line":12,"running_time":"0.94","count_size":1,"warp_yarn":"warp_yarn","ordered_item":"","order_type_name":"","ship_to_customer":"","bill_to_customer":"","cs":"","packing_instr":"","attachment":""}],"sizeDataSave":[{"up_user":"","production_line":"woven","no_number":"","so_line":"","size":"None","color":"non","qty":"12","material_code":"","so_cai_size":15,"target_qty":675}]}';
		$data = json_decode($data, true);
		// check empty
		if (empty($data)) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Save data empty (1)'
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		// set data orders info and lines check ok
		$poDataSave = isset($data['poDataSave']) ? $data['poDataSave'] : '';
		$processDataSave = isset($data['processDataSave']) ? $data['processDataSave'] : '';
		$supplyDataSave = isset($data['supplyDataSave']) ? $data['supplyDataSave'] : '';
		$solineDataSave = isset($data['solineDataSave']) ? $data['solineDataSave'] : '';
		$sizeDataSave = isset($data['sizeDataSave']) ? $data['sizeDataSave'] : '';

		// check empty
		if (empty($poDataSave)  || empty($processDataSave) || empty($supplyDataSave) || empty($solineDataSave) || empty($sizeDataSave)) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Save data empty (2)'
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		// if edit, get po_no, else create po_no
		if (strpos($poDataSave['po_no'], ' ') !== false) {
			$poDataSave['po_no'] = str_replace(' ', '', $poDataSave['po_no']);
		}
		if (strlen($poDataSave['po_no']) >= 12) {
			$po_no = $poDataSave['po_no'];
		} else {
			$po_no = $this->createNoCCR($poDataSave['po_no']);
		}

		// type: ccr. buildstock, common
		$plan_order_type = $this->getType($poDataSave['batch_no'], $solineDataSave[0]['so_line']);

		$poDataSave['po_no'] = $po_no;
		$poDataSave['po_date'] = $this->dateFormat($poDataSave['po_date']);
		$poDataSave['ordered_date'] = $this->dateFormat($poDataSave['ordered_date']);
		$poDataSave['request_date'] = $this->dateFormat($poDataSave['request_date']);
		$poDataSave['promise_date'] = $this->dateFormat($poDataSave['promise_date']);

		$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';
		$poDataSave['updated_by'] = $updated_by;
		$poDataSave['updated_date'] = date('Y-m-d H:i:s');

		$batch_no = trim($poDataSave['batch_no']);
		$qty_total = $poDataSave['qty_total'];

		// get remark save data
		$remarkCheckArr['rbo'] = $poDataSave['rbo'];
		$remarkCheckArr['internal_item'] = $poDataSave['internal_item'];
		$remarkCheckArr['order_type_name'] = $poDataSave['order_type'];
		$remarkCheckArr['ordered_item'] = $solineDataSave[0]['ordered_item'];
		$remarkCheckArr['ship_to_customer'] = $solineDataSave[0]['ship_to_customer'];
		$remarkCheckArr['bill_to_customer'] = $solineDataSave[0]['bill_to_customer'];
		$remarkCheckArr['packing_instr'] = $solineDataSave[0]['packing_instr'];

		$remarkSupplySave = $supplyDataSave; // sử dụng cho check GRS

		/*
            | ------------------------------------------------------------------------------------------------------------
            | 2. SAVE DATA
            | ------------------------------------------------------------------------------------------------------------
		*/
		// load models
		$this->load->model('woven_po_save');
		$this->load->model('woven_po_soline_save');
		$this->load->model('woven_master_item_supply_save', 'supply_save');
		$this->load->model('woven_master_item_process_save', 'process_save');
		$this->load->model('common_size_save', 'size_save');

		$this->load->model('woven_prepress_batching', 'batching');
		$this->load->model('woven_ppc_so_line');
		// ==============================================================

		/* RUNNING TIME TOTAL BATCH NO */


		/* ************************** PO DATA SAVE ************************** */
		if ($this->woven_po_save->isAlreadyExist($po_no)) {
			unset($poDataSave['po_no']); // delete primary key in array
			$po_save = $this->woven_po_save->update($poDataSave, $po_no);
		} else {
			$po_save = $this->woven_po_save->create($poDataSave);
		}

		// check po_save
		if ($po_save == FALSE) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Save Data Error. po_save table'
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		$so_cai_total = $poDataSave['so_cai_total'];

		// ==============================================================

		/* ************************** SUPPLY - VẬT TƯ  SAVE ************************** */
		// xóa các code vật tư cũ đã lưu trước đó. Tránh trường hợp một số vật tư xóa rồi nhưng do làm lệnh trước thì vẫn còn hiển thị khi in
		$this->supply_save->delete($po_no);

		foreach ($supplyDataSave as $key => $supply_item) {
			$supplyDataSave[$key]['po_no'] = $po_no;
			$supply_item['po_no'] = $po_no;
			$supply_check = array('po_no' => $po_no, 'internal_item' => $supply_item['internal_item'], 'length_btp' => $supply_item['length_btp'], 'code_name' => $supply_item['code_name'], 'order' => $supply_item['order']);
			if ($this->supply_save->isAlreadyExist($supply_check)) {
				// unset($supplyDataSave[$key]); // delete row in array
				unset($supply_item['po_no']); // delete primary key in array
				unset($supply_item['internal_item']); // delete primary key in array
				unset($supply_item['length_btp']); // delete primary key in array
				unset($supply_item['code_name']); // delete primary key in array
				unset($supply_item['order']); // delete primary key in array
				$po_supply_save = $this->supply_save->update($supply_item, $supply_check);
			} else {
				$po_supply_save = $this->supply_save->create($supply_item);
			}

			if ($po_supply_save == FALSE) {
				$this->_data['results'] = array(
					'status' => false,
					'message' => 'Save Data Error. po_supply_save table'
				);
				echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
				exit();
			}
		}
		// ==============================================================

		/* ************************** PROCESS SAVE ************************** */
		// xóa các process cũ đã lưu trước đó. Tránh trường hợp một process xóa rồi nhưng do làm lệnh trước thì vẫn còn hiển thị khi in
		$this->process_save->delete($po_no);

		foreach ($processDataSave as $key => $process_item) {
			$processDataSave[$key]['po_no'] = $po_no;
			$process_item['po_no'] = $po_no;
			$process_check = array('po_no' => $po_no, 'internal_item' => $process_item['internal_item'], 'process_code' => $process_item['process_code']);
			if ($this->process_save->isAlreadyExist($process_check)) {
				unset($processDataSave[$key]); // delete row in array
				unset($process_item['po_no']); // delete primary key in array
				unset($process_item['internal_item']); // delete primary key in array
				unset($process_item['process_code']); // delete primary key in array
				$po_process_save = $this->process_save->update($process_item, $process_check);
				if ($po_process_save == FALSE) {
					$this->_data['results'] = array(
						'status' => false,
						'message' => 'Save Data Error. po_process_save table'
					);
					echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
					exit();
				}
			}
		}

		if (!empty($processDataSave)) {
			$po_process_save = $this->process_save->insertBatch($this->process_save->setInsertBatch($processDataSave));
			if ($po_process_save == FALSE) {
				$this->_data['results'] = array(
					'status' => false,
					'message' => 'Save Data Error. po_process_save table'
				);
				echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
				exit();
			}
		}
		// ==============================================================

		/* ************************** SIZE ************************** */
		$target_total = 0;

		if ($plan_order_type == 'common') {
			if (!$this->woven_ppc_so_line->isAlreadyExist(array('batch_no' => $batch_no)) || !$this->batching->isAlreadyExist(array('batch_no' => $batch_no))) {
				$this->_data['results'] = array(
					'status' => false,
					'message' => 'Save Data Error. ppc_so_line table/batching table'
				);
				echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
				exit();
			} else {
				$sizeDataSave = array();
				$ppc_soline = $this->woven_ppc_so_line->readSingle($batch_no);
				$batching_check = $this->batching->readSingle($batch_no);

				// target total
				if (!empty($batching_check)) {
					foreach ($batching_check as $bat) {
						$target_total += $bat['target_qty'];
					}
				}

				// insert size
				foreach ($ppc_soline as $ppc_soline_item) {
					// get target
					$target_qty = 0;
					// $target_qty = $batching_check[0]['target_qty'];
					// delete 1 element 0
					array_splice($batching_check, 0, 1);

					$sizeDataSave[] = array(
						'up_date' => date('Y-m-d H:i:s'),
						'up_user' => $updated_by,
						'production_line' => 'woven',
						'no_number' => $po_no,
						'so_line' => $ppc_soline_item['so_line'],
						'size' => $ppc_soline_item['size'],
						'color' => 'non',
						'qty' => $ppc_soline_item['qty'],
						'material_code' => '',
						'so_cai_size' => 0, //@@@@@@@@@@@@
						'target_qty' => $target_qty

					);
				}
			}
		}

		foreach ($sizeDataSave as $key => $size_item) {
			$sizeDataSave[$key]['no_number'] = $po_no;
			$sizeDataSave[$key]['up_user'] = $updated_by;

			if ($sizeDataSave[$key]['so_line'] == '') {
				$sizeDataSave[$key]['so_line'] = $po_no;
				$size_item['so_line'] = $po_no;
			}

			$size_item['no_number'] = $po_no;
			$size_item['up_user'] = $updated_by;
			$size_item['up_date'] = date('Y-m-d H:i:s');

			if ($plan_order_type !== 'common') {
				$target_qty = ceil((float)$size_item['target_qty']);
				$target_total += $target_qty;
			}

			$size_check = array('so_line' => $size_item['so_line'], 'size' => $size_item['size'], 'color' => $size_item['color']);
			if ($this->size_save->isAlreadyExist($size_check)) {
				unset($sizeDataSave[$key]); // delete row in array
				unset($size_item['so_line']); // delete primary key in array
				unset($size_item['size']); // delete primary key in array
				unset($size_item['color']); // delete primary key in array
				$po_size_save = $this->size_save->update($size_item, $size_check);
				if ($po_size_save == FALSE) {
					$this->_data['results'] = array(
						'status' => false,
						'message' => 'Save Data Error. po_size_save table'
					);
					echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
					exit();
				}
			}
		}

		// update target total to po_save table
		$po_save_update_targer_total = array('target_total' => $target_total);
		$this->woven_po_save->update($po_save_update_targer_total, $po_no);

		// update
		if (!empty($sizeDataSave)) {
			$po_size_save = $this->size_save->insertBatch($this->size_save->setInsertBatch($sizeDataSave));
			if ($po_size_save == FALSE) {
				$this->_data['results'] = array(
					'status' => false,
					'message' => 'Save Data Error. po_size_save table'
				);
				echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
				exit();
			}
		}
		// ==============================================================

		/* ************************** SOLINE SAVE ************************** */
		$target_qty = 0;
		foreach ($solineDataSave as $key => $soline_item) {
			$solineDataSave[$key]['po_no'] = $po_no;
			$soline_item['po_no'] = $po_no;
			$so_line = $soline_item['so_line'];
			$qty_of_line = $soline_item['qty_of_line'];

			// Lấy thông tin target của từng line (để report)
			$target_qty = round((($target_total * ($qty_of_line / $qty_total * 100)) / 100), 2);
			$solineDataSave[$key]['target_of_line'] = $target_qty;
			$soline_item['target_of_line'] = $target_qty;

			// Lấy thông tin số cái của từng line (để report)
			$so_cai = round((($so_cai_total * ($qty_of_line / $qty_total * 100)) / 100), 2);
			$solineDataSave[$key]['so_cai_of_line'] = $so_cai;
			$soline_item['so_cai_of_line'] = $so_cai;

			// Trường hợp đơn buildstock
			if ($solineDataSave[$key]['so_line'] == 'non') {
				$so_line = $po_no;
				$solineDataSave[$key]['so_line'] = $po_no;
				$soline_item['so_line'] = $po_no;
			}

			$update_check = array('po_no' => $soline_item['po_no'], 'so_line' => $so_line);
			if ($this->woven_po_soline_save->isAlreadyExist($soline_item['po_no'], $so_line)) {

				// unset($solineDataSave[$key]); // delete row in array
				unset($soline_item['po_no']); // delete primary key in array
				unset($soline_item['so_line']); // delete primary key in array

				$po_soline_save = $this->woven_po_soline_save->update($soline_item, $update_check);
				if ($po_soline_save == FALSE) {
					$this->_data['results'] = array(
						'status' => false,
						'message' => 'Save Data Error. po_soline_save table'
					);
					echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
					exit();
				}
			} else {
				$po_soline_save = $this->woven_po_soline_save->create($soline_item);
				if ($po_soline_save == FALSE) {
					$this->_data['results'] = array(
						'status' => false,
						'message' => 'Save Data Error. po_soline_save table'
					);
					echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
					exit();
				}
			}
		}

		// if (!empty($solineDataSave) ) {
		// 	$po_soline_save = $this->woven_po_soline_save->insertBatch($this->woven_po_soline_save->setInsertBatch($solineDataSave));
		// 	if ($po_soline_save == FALSE ) {
		// 		$this->_data['results'] = array(
		// 			'status' => false,
		// 			'message' => 'Save Data Error. po_soline_save table'
		// 		);
		// 		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE); exit();
		// 	}
		// }
		// ==============================================================

		/* ************************** REMARK SAVE ************************** */


		// remark
		$remarkSave = $this->remark('woven', $po_no, $remarkCheckArr);
		if ($remarkSave !== TRUE) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Save Data Error. Remark Tool ' . $remarkSave
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		// Remark: KHONG KIM LOAI và save Packing Instr
		// xử lý sau, hỏi lại xem đơn CCR và đơn buildstock có dò kim loại không
		// packing instr nhiều đơn thì bắt như thế nào?
		$rbo = trim($poDataSave['rbo']);
		$remarkPacking = $this->packingInstrRemark('woven', $po_no, $remarkCheckArr['packing_instr'], $rbo);
		if ($remarkPacking !== TRUE) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Save Data Error. Remark KKL & Packing'
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		// Xử lý remark đặc biệt được nhập vào từ file đến bảng: 
		$specialTableRemark = $this->specialTableRemark('woven', $po_no, $poDataSave['internal_item']);
		if ($specialTableRemark !== TRUE) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Save Data Error. Remark (Special Table): ' . $specialTableRemark
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		// Xử lý các remark dựa vào danh sách item đặc biệt (nhiều item) nên không sử dụng remark tool mà xử lý tại đây. 
		// Planning sẽ update item và remark từ phần mềm
		$specialItemRemark = $this->specialItemRemark('woven', $po_no, $poDataSave['special_item_remark']);
		if ($specialItemRemark !== TRUE) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Save Data Error. Remark (Special Item): ' . $specialItemRemark
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		// supply GRS remark
		$supplyGRS = $this->supplyGRS($po_no, $poDataSave['internal_item'], $remarkSupplySave);
		if ($supplyGRS !== TRUE) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Save Data Error. Supply GRS: ' . $supplyGRS
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		// supply SAME remark
		$supplySameRemark = $this->supplySameRemark('woven', $po_no, $remarkSupplySave);
		if ($supplySameRemark !== TRUE) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Save Data Error. Supply GRS: ' . $supplySameRemark
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		// GYCG2
		// // Cập nhật các trường hợp code vật tư cũ được thêm sau khi đã update GYCG2
		// updateOldGycg2Remark($remarkSupplySave);

		$gycg2Remark = $this->gycg2Remark('woven', $po_no, $remarkSupplySave);
		if ($gycg2Remark !== TRUE) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Save Data Error. GYCG2: ' . $gycg2Remark
			);
			echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
			exit();
		}


		// ==============================================================

		/* ************************** RESULTS ************************** */
		$this->_data['results'] = array(
			'status' => true,
			'message' => 'Save Data Success. are you print? ',
			'po_no' => $po_no
		);
		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
		exit();
		// ==============================================================

	}

	// print order, view layout
	public function printOrders($po_no)
	{
		// load models
		$this->load->model('common_prefix_no');
		$this->load->model('common_setting_process', 'setting_process');
		$this->load->model('woven_po_save');
		$this->load->model('woven_po_soline_save');
		$this->load->model('woven_master_item_supply_save', 'supply_save');
		$this->load->model('woven_master_item_process_save', 'process_save');
		$this->load->model('common_size_save', 'size_save');

		$this->load->model('woven_master_item', 'wv_master_item');


		$this->load->model('woven_prepress_batching', 'batching');
		$this->load->model('woven_ppc_so_line');

		$this->load->model('common_remark_po_save', 'remark_po_save');

		// update printed column set 1
		$printed_update_save = array('printed' => 1);
		$this->woven_po_save->update($printed_update_save, $po_no);


		// get form_type
		$po_no_arr = explode('-', $po_no);
		$prefix_len = strlen($po_no_arr[0]) - 4;
		$prefix = substr($po_no, 0, $prefix_len);

		// get prefix no description
		$common_prefix_no_check = $this->common_prefix_no->readPrefix($prefix);
		$production_line = isset($_COOKIE['plan_department']) ? strtolower($_COOKIE['plan_department']) : 'woven';
		$form_type = $common_prefix_no_check['module'];
		$form_type_label = $common_prefix_no_check['description'];

		// init result data array
		$this->_data['results'] = array();
		$poDataPrint = array();
		$supplyDataPrint = array();
		$processDataPrint = array();
		$solineDataPrint = array();
		$sizeDataPrint = array();
		$remarkDataPrint = array();

		// get data po_save
		if (!$this->woven_po_save->isAlreadyExist($po_no)) {
			$this->_data['results'] = array('status' => false, 'message' => "NO# $po_no does not exist.");
			$this->load->view('woven/print/printOrders', $this->_data);
		}

		// get data order details
		$poDataPrint = $this->woven_po_save->readSingle($po_no);
		// $po_no_suffix = strtoupper($poDataPrint['po_no_suffix']);
		// $suffix_arr = array('FOD', 'CCR');
		// $po_no_show = (in_array($po_no_suffix, $suffix_arr) ) ? ($po_no . '-' . $po_no_suffix) : $po_no;

		$po_no_show = $this->getPOShow($po_no, $poDataPrint['po_no_suffix']);
		$po_no_barcode = '<img style="text-align:right;width:200px; height:30px;"  src="' . base_url("assets/barcode.php?text=") . $po_no_show . '" />';

		$batch_no = $poDataPrint['batch_no'];
		$qty_total = $poDataPrint['qty_total'];

		$gear_density_wv = '';
		$gear_density_cw = '';
		$gear_density_lb = '';
		$wire_number_wv = '';
		$wire_number_cw = '';
		$wire_number_lb = '';
		if ($this->wv_master_item->checkItem($poDataPrint['internal_item'])) {
			$master_data_check = $this->wv_master_item->readSingle($poDataPrint['internal_item']);
			//  print_r($master_data_item);
			foreach ($master_data_check as $master_data_item) {
				if (strtolower($master_data_item['machine_type']) == 'wv') {
					$gear_density_wv = $master_data_item['gear_density'];
					$wire_number_wv = $master_data_item['wire_number'];
				} else if (strtolower($master_data_item['machine_type']) == 'cw') {
					$gear_density_cw = $master_data_item['gear_density'];
					$wire_number_cw = $master_data_item['wire_number'];
				} else if (strtolower($master_data_item['machine_type']) == 'lb') {
					$gear_density_lb = $master_data_item['gear_density'];
					$wire_number_lb = $master_data_item['wire_number'];
				}
			}
		}

		$qty_total_barcode = '<img style="text-align:right;width:150px; height:14px;"  src="' . base_url("assets/barcode.php?text=") . $poDataPrint['qty_total'] . '" />';


		$orderDetail = array(
			'form_type_label' => $form_type_label,
			// 'po_no' => $poDataPrint['po_no'],
			'po_no' => $po_no_show,
			'po_no_barcode' => $po_no_barcode,
			'machine_type' => $poDataPrint['machine_type'],
			'type' => $poDataPrint['type'],
			'batch_no' => $poDataPrint['batch_no'],
			'po_no_suffix' => $poDataPrint['po_no_suffix'],
			'order_type' => $poDataPrint['order_type'],
			'count_lines' => $poDataPrint['count_lines'],
			'po_date' => $poDataPrint['po_date'],
			'ordered_date' => $poDataPrint['ordered_date'],
			'request_date' => $poDataPrint['request_date'],
			'promise_date' => $poDataPrint['promise_date'],
			'qty_total' => $poDataPrint['qty_total'],
			'pick_number_total' => $poDataPrint['pick_number_total'],
			'thread_length_total' => $poDataPrint['thread_length_total'],
			'need_horizontal_thread_total' => $poDataPrint['need_horizontal_thread_total'],
			'count_supply' => $poDataPrint['count_supply'],
			'need_vertical_thread_number' => $poDataPrint['need_vertical_thread_number'],
			'warp_yarn_number' => $poDataPrint['warp_yarn_number'],
			'meters_per_roll' => $poDataPrint['meters_per_roll'],
			'so_cai_total' => $poDataPrint['so_cai_total'],
			'printed' => $poDataPrint['printed'],

			'internal_item' => $poDataPrint['internal_item'],
			'length_btp' => $poDataPrint['length_btp'],
			'width_btp' => $poDataPrint['width_btp'],
			'rbo' => html_entity_decode($poDataPrint['rbo'], ENT_QUOTES),
			'wire_number' => $poDataPrint['wire_number'],
			'vertical_thread_type' => $poDataPrint['vertical_thread_type'],
			'folding_cut_type' => $poDataPrint['folding_cut_type'],
			'pattern' => $poDataPrint['pattern'],
			'gear_density' => $poDataPrint['gear_density'],
			'length_tp' => $poDataPrint['length_tp'],
			'width_tp' => $poDataPrint['width_tp'],
			'cbs' => $poDataPrint['cbs'],
			'scrap' => $poDataPrint['scrap'],
			'cut_type' => $poDataPrint['cut_type'],
			'sawing_method' => $poDataPrint['sawing_method'],
			'cw_specification' => $poDataPrint['cw_specification'],
			'heat_weaving' => $poDataPrint['heat_weaving'],
			'meter_number_per_machine' => $poDataPrint['meter_number_per_machine'],
			'water_glue_rate' => $poDataPrint['water_glue_rate'],
			'so_cai_min' => $poDataPrint['so_cai_min'],
			'taffeta_satin' => $poDataPrint['taffeta_satin'],
			'textile_size_number' => $poDataPrint['textile_size_number'],
			'new_wire_number' => $poDataPrint['new_wire_number'],
			'remark_1' => $poDataPrint['remark_1'],
			'remark_2' => $poDataPrint['remark_2'],
			'remark_3' => $poDataPrint['remark_3'],
			'created_date' => $poDataPrint['created_date'],
			'updated_by' => $poDataPrint['updated_by'],
			'updated_date' => $poDataPrint['updated_date'],

			'gear_density_wv' => $gear_density_wv,
			'gear_density_cw' => $gear_density_cw,
			'gear_density_lb' => $gear_density_lb,
			'wire_number_wv' => $wire_number_wv,
			'wire_number_cw' => $wire_number_cw,
			'wire_number_lb' => $wire_number_lb,
			'qty_total_barcode' => $qty_total_barcode
		);

		// get data supply
		if (!$this->supply_save->isAlreadyExist(array('po_no' => $po_no))) {
			$this->_data['results'] = array('status' => false, 'message' => "NO# $po_no does not exist in Supply Data.");
			$this->load->view('woven/print/printOrders', $this->_data);
		}

		$supplyDataPrint = $this->supply_save->readSingle(array('po_no' => $po_no));


		// get data process
		if (!$this->process_save->isAlreadyExist(array('po_no' => $po_no))) {
			$this->_data['results'] = array('status' => false, 'message' => "NO# $po_no does not exist in Process Data.");
			$this->load->view('woven/print/printOrders', $this->_data);
		}

		$processDataPrint[] = array(
			'order' => 'Stt',
			'process_code' => 'Process',
			'process_name' => 'Process',
			'name' => 'Tên',
			'date' => 'Ngày'
		);

		$orderCount = 0;
		$processData = $this->process_save->readSingle(array('po_no' => $po_no), 'process_order');
		foreach ($processData as $process_item) {

			if ($process_item['status'] == 1) {

				$orderCount++;

				$order = $process_item['process_order'];
				if (empty($order)) {
					$order = $orderCount;
				}

				$processDataPrint[] = array(
					'order' => $order,
					'process_code' => $process_item['process_code'],
					'process_name' => $process_item['process_name'],
					'name' => '',
					'date' => ''
				);
			}
		}


		// get soline data
		if (!$this->woven_po_soline_save->checkPO($po_no)) {
			$this->_data['results'] = array('status' => false, 'message' => "NO# $po_no does not exist in SOLine Data.");
			$this->load->view('woven/print/printOrders', $this->_data);
		}

		$solineData = $this->woven_po_soline_save->readPoSOLines($po_no);

		// add to orderDetail
		$orderDetail['ship_to_customer'] = $solineData[0]['ship_to_customer'];
		$orderDetail['cs'] = $solineData[0]['cs'];
		$orderDetail['count_size'] = $solineData[0]['count_size'];

		$suffix_arr = array('CCR', 'FOD');
		foreach ($solineData as $soline_item) {

			// $running_time = round((($soline_item['running_time'] * ($soline_item['qty_of_line']/$qty_total*100)) / 100), 2);

			$po_no = $soline_item['po_no'];
			$poItem = $this->woven_po_save->readItem(array('po_no' => $po_no));
			$po_no_suffix = trim(strtoupper($poItem['po_no_suffix']));
			$po_no_show = (in_array($po_no_suffix, $suffix_arr)) ? ($po_no . '-' . $po_no_suffix) : $po_no;


			// Kiểm tra xem So line có giống NO hay không. Nếu giống là đơn Buildstock. Thì cho so_line (hiển thị) bằng po_no (có suffix)
			// Trường hợp không giống thì cho bình thường bằng so_line
			$so_line = $soline_item['so_line'];
			$so_line_show = ($so_line == $po_no) ? $po_no_show : $so_line;

			$soline_barcode = '<img style="text-align:right;width:150px; height:14px;"  src="' . base_url("assets/barcode.php?text=") . $so_line_show . '" />';



			$solineDataPrint[] = array(
				'po_no' => $po_no_show,
				'so_line' => $so_line_show,
				'internal_item' => $soline_item['internal_item'],
				'length_btp' => $soline_item['length_btp'],
				'qty_of_line' => $soline_item['qty_of_line'],
				'running_time_total' => $soline_item['running_time'],
				'running_time' => $soline_item['running_time'],
				'count_size' => $soline_item['count_size'],
				'warp_yarn' => $soline_item['warp_yarn'],
				'ordered_item' => $soline_item['ordered_item'],
				'order_type_name' => $soline_item['order_type_name'],
				'ship_to_customer' => $soline_item['ship_to_customer'],
				'bill_to_customer' => $soline_item['bill_to_customer'],
				'cs' => $soline_item['cs'],
				'packing_instr' => $soline_item['packing_instr'],
				'attachment' => $soline_item['attachment'],
				'soline_barcode' => $soline_barcode
			);
		}

		if ($poDataPrint['type'] == 'common' || $poDataPrint['type'] == 'non_batching') {

			// update job_date in prd_plan_ppc_so_line table
			$job_date_update_save = array('job_date' => date('Y-m-d H:i:s', strtotime($poDataPrint['updated_date'])));
			$this->woven_ppc_so_line->update($job_date_update_save, $batch_no);

			// check
			if (!$this->batching->isAlreadyExist(array('batch_no' => $batch_no))) {
				$this->_data['results'] = array('status' => false, 'message' => "NO# $batch_no does not exist in Batching table.");
				$this->load->view('woven/print/printOrders', $this->_data);
			}

			// check
			if (!$this->woven_ppc_so_line->isAlreadyExist(array('batch_no' => $batch_no))) {
				$this->_data['results'] = array('status' => false, 'message' => "NO# $batch_no does not exist in ppc_so_line table.");
				$this->load->view('woven/print/printOrders', $this->_data);
			}

			// get size Data sum
			$sizeData = $this->batching->readSingle($batch_no);
			// get soline list
			$ppc_soline = $this->woven_ppc_so_line->readSOLineDistinct($batch_no);
			// $idCheck = 1;
			// foreach ($ppc_soline as $check ) {
			// 	${'qty_soline_'.$idCheck} = '';
			// 	$idCheck++;
			// }

			$target = 0;
			$scrap_size = 0;
			$count_ppc_soline = count($ppc_soline);

			// loop for size data from batching table
			// with this size: get soline from ppc soline table => get size per soline from ppc soline table
			$keyLoadSize = 0;
			foreach ($sizeData as $keyS => $size_item) {

				$keyLoadSize = $keyS + 1;

				$size_1 = trim($size_item['size']);
				$target = $size_item['target_qty'];
				$qty_size_total = $size_item['qty'];
				$group_num = $size_item['group_num'];
				$so_cai = $size_item['socai_scrap'];

				// Xử lý trường hợp đơn hàng non batching và số cái < 15 thì lấy số cái = socai_group trong dữ liệu prepress
				if (($poDataPrint['type'] == 'non_batching') && ($so_cai < 15)) {
					$so_cai = $size_item['socai_group'];
				}

				$scrap_size =   (($target - $qty_size_total) / $target) * 100;
				$scrap_size = round($scrap_size, 2);

				$index_ppc = 0;
				foreach ($ppc_soline as $soline_item) {

					$index_ppc++;
					$qty_soline_key = 'qty_soline_' . $index_ppc;

					$so_line = $soline_item['so_line'];

					$soline_size = $this->woven_ppc_so_line->readItem(array('so_line' => $so_line, 'size' => $size_1));
					if (empty($soline_size) ) {
						$soline_size['qty'] = 0;
					}

					if ($keyS == 0) {

						if (empty($sizeDataPrint[$keyS])) {

							$dataSize = array(
								'index' => 'Stt',
								'size' => 'Size',
								'qty_size_total' => 'Tổng SL',
								'target' => 'Target',
								'scrap_size' => '%Scrap',
								'count_line' => $count_ppc_soline,
								$qty_soline_key => $so_line,
								'group_num' => $group_num,
								'so_cai' => 'Số Cái'
							);

							array_push($sizeDataPrint, $dataSize);

							$keyTilte = 1;
						} else {
							// $sizeDataPrint[$keyS][$qty_soline_key] = $soline_item['so_line'];
							$sizeDataPrint[$keyS][$qty_soline_key] = $so_line;
						}

						if (empty($sizeDataPrint[$keyLoadSize])) {

							$dataSize = array(
								'index' => $index_ppc,
								'size' => $size_1,
								'qty_size_total' => $qty_size_total,
								'target' => $target,
								'scrap_size' => $scrap_size,
								'count_line' => $count_ppc_soline,
								$qty_soline_key => $soline_size['qty'],
								'group_num' => $group_num,
								'so_cai' => $so_cai
							);

							array_push($sizeDataPrint, $dataSize);

							$keyTilte = 1;
						} else {
							$sizeDataPrint[$keyLoadSize][$qty_soline_key] = $soline_size['qty'];
						}



						// print_r($sizeDataPrint[$keyS]); echo "<br />\n";

					} else {

						if (empty($sizeDataPrint[$keyLoadSize])) {

							$dataSize = array(
								'index' => $index_ppc,
								'size' => $size_1,
								'qty_size_total' => $qty_size_total,
								'target' => $target,
								'scrap_size' => $scrap_size,
								'count_line' => $count_ppc_soline,
								$qty_soline_key => $soline_size['qty'],
								'group_num' => $group_num,
								'so_cai' => $so_cai
							);

							array_push($sizeDataPrint, $dataSize);
						} else {
							$sizeDataPrint[$keyLoadSize][$qty_soline_key] = $soline_size['qty'];
						}
					}
				}
			}
		} else {

			// get size data
			if (!$this->size_save->isAlreadyExist(array('no_number' => $po_no))) {
				$this->_data['results'] = array('status' => false, 'message' => "NO# $po_no does not exist in Size Data.");
				$this->load->view('woven/print/printOrders', $this->_data);
			}

			$sizeData = $this->size_save->readPO($po_no);

			$index = 0;
			$target = 0;
			$suffix_arr = array('CCR', 'FOD');
			foreach ($sizeData as $size_item) {
				$index++;

				$po_no = $size_item['no_number'];
				$so_line = $size_item['so_line'];
				if ($so_line == $po_no) {
					$poItem = $this->woven_po_save->readItem(array('po_no' => $po_no));
					$po_no_suffix = trim(strtoupper($poItem['po_no_suffix']));
					$so_line_show = (in_array($po_no_suffix, $suffix_arr)) ? ($po_no . '-' . $po_no_suffix) : $po_no;
				} else {
					$so_line_show = $so_line;
				}


				if ($index == 1) {
					$sizeDataPrint[] = array(
						'index' => 'Stt',
						'size' => 'Size',
						'qty_size_total' => 'Tổng SL',
						// 'qty_soline' => $size_item['so_line'],
						'qty_soline' => $so_line_show,

						'target' => 'Target',
						'tong_so_cai_day' => 'Tổng số cái/Dây',
						'scrap_size' => '%Scrap'
					);
				}
				$target = $size_item['so_cai_size'] * $poDataPrint['wire_number'];
				$scrap_size =   (($target - $size_item['qty']) / $target) * 100;
				$scrap_size = round($scrap_size, 2);
				$scrap_size = $scrap_size . ' %';
				$sizeDataPrint[] = array(
					'index' => $index,
					'size' => $size_item['size'],
					'qty_size_total' => $size_item['qty'],
					'qty_soline' => $size_item['qty'],
					'target' => $target,
					'tong_so_cai_day' => $size_item['so_cai_size'],
					'scrap_size' => $scrap_size
				);
			}
		}

		// Remark data print
		$remarkDataPrint = $this->remark_po_save->readPO(array('production_line' => 'woven', 'po_no' => $po_no));

		// results
		$this->_data['results'] = array(
			'status' => true,
			'orderDetail' => $orderDetail,
			'supplyDataPrint' => $supplyDataPrint,
			'processDataPrint' => $processDataPrint,
			'solineDataPrint' => $solineDataPrint,
			'sizeDataPrint' => $sizeDataPrint,
			'remarkDataPrint' => $remarkDataPrint
		);

		$this->load->view('woven/print/printOrders', $this->_data);
	}

	// delete order
	public function delete($po_no)
	{
		//load models
		$this->load->model('woven_po_save');
		$this->load->model('woven_po_soline_save');
		$this->load->model('woven_master_item_supply_save', 'supply_save');
		$this->load->model('woven_master_item_process_save', 'process_save');
		$this->load->model('common_size_save', 'size_save');

		// $po_no = null !== $this->input->get('po_no') ? trim($this->input->get('so_line')) : '';

		//handle all
		//del po_save
		if (
			!$this->woven_po_save->isAlreadyExist($po_no) || !$this->woven_po_soline_save->checkPO($po_no) || !$this->supply_save->checkPO($po_no) ||
			!$this->process_save->checkPO($po_no) || !$this->size_save->isAlreadyExist(array('no_number' => $po_no))
		) {

			$this->_data['results'] = array(
				'status' => false,
				'message' => $po_no . ' not exist.'
			);
			$this->load->view('woven/deleteOrder', $this->_data['results']);
		}

		// del woven_po_save table
		$result = $this->woven_po_save->delete($po_no);
		if ($result != TRUE) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Delete fail. Error: ' . $result
			);
			$this->load->view('woven/deleteOrder', $this->_data['results']);
		}

		//del woven_po_soline_save table
		$result = $this->woven_po_soline_save->delete($po_no);
		if ($result != TRUE) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Delete fail. Error: ' . $result
			);
			$this->load->view('woven/deleteOrder', $this->_data['results']);
		}

		//del supply save
		$result = $this->supply_save->delete($po_no);
		if ($result != TRUE) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Delete fail. Error: ' . $result
			);
			$this->load->view('woven/deleteOrder', $this->_data['results']);
		}

		//del process save table
		$result = $this->process_save->delete($po_no);
		if ($result != TRUE) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Delete fail. Error: ' . $result
			);
			$this->load->view('woven/deleteOrder', $this->_data['results']);
		}

		//del process save table
		$result = $this->size_save->deletePO($po_no);
		if ($result != TRUE) {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Delete fail. Error: ' . $result
			);
			$this->load->view('woven/deleteOrder', $this->_data['results']);
		}

		if ($result == TRUE) {
			$this->_data['results'] = array(
				'status' => true,
				'message' => 'Delete Success NO# ' . $po_no
			);
			$this->load->view('woven/deleteOrder', $this->_data['results']);
		}
	}

	// for export data
	public function cellColor($objPHPExcel, $cells, $color)
	{
		$objPHPExcel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
			'type' => PHPExcel_Style_Fill::FILL_SOLID,
			'startcolor' => array(
				'rgb' => $color
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			),
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN,
					'color' => array('rgb' => '7094db')
				)
			)
		));
	}

	// import excel data
	public function importMasterData()
	{
		$this->_data['title'] = 'Import Master Data';

		// load models
		$this->load->library('excel');
		$this->load->model('woven_master_item', 'wv_master_item');
		$this->load->model('woven_master_item_supply', 'supply');
		$this->load->model('woven_master_item_process', 'process');
		$this->load->model('common_setting_process', 'setting_process');

		$productionLine = isset($_COOKIE['plan_department']) ? strtolower($_COOKIE['plan_department']) : '';

		if ($this->input->post('importfile')) {

			$this->_data['message'] = 'OK';

			$path = 'uploads/';
			$config['upload_path'] = $path;
			$config['allowed_types'] = 'xlsx|xls';
			$config['remove_spaces'] = TRUE;
			$this->upload->initialize($config);
			$this->load->library('upload', $config);
			if (!$this->upload->do_upload('masterfile')) {
				$error = array('error' => $this->upload->display_errors());
			} else {
				$data = array('upload_data' => $this->upload->data());
			}

			if (!empty($data['upload_data']['file_name'])) {
				$import_xls_file = $data['upload_data']['file_name'];
			} else {
				$import_xls_file = 0;
			}
			$inputFileName = $path . $import_xls_file;

			try {
				$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
				$objReader = PHPExcel_IOFactory::createReader($inputFileType);
				$objPHPExcel = $objReader->load($inputFileName);
			} catch (Exception $e) {
				$this->_data['results'] = array(
					'status' => false,
					'message' => 'Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME) . '": ' . $e->getMessage()
				);
				echo json_encode($this->_data['results']);
				exit();
				//die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME) . '": ' . $e->getMessage());
			}

			// $sheetnum=$objPHPExcel->getSheetCount();

			// init array insert and update data all
			$masterDataInsert = array();
			$supplyInsert = array();
			$processInsert = array();

			$department = isset($_COOKIE['plan_department']) ? strtolower($_COOKIE['plan_department']) : '';
			$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';
			$updated_date = date('Y-m-d H:i:s');

			// Lấy từng sheet để import
			$Main_Master = $objPHPExcel->getSheetByName('Main_Master'); // Theo tên
			$Material = $objPHPExcel->getSheetByName('Material'); // Theo tên
			$Process = $objPHPExcel->getSheetByName('Process'); // Theo tên

			$rowCount_1 = !empty($Main_Master) ? count($Main_Master->toArray(null, true, true, true)) : 0;
			$rowCount_2 = !empty($Material) ? count($Material->toArray(null, true, true, true)) : 0;
			$rowCount_3 = !empty($Process) ? count($Process->toArray(null, true, true, true)) : 0;


			// echo "RowCout Main:  $rowCount_1 ";
			// echo "RowCout Material:  $rowCount_2 ";
			// echo "RowCout Process:  $rowCount_3 ";
			// exit();

			// $objPHPExcel = $objPHPExcel->getSheet(0); // Sử dụng hàm này khi muốn lấy sheet theo thứ tự 0,1,2,...
			// $objPHPExcel = $objPHPExcel->getSheetByName('Main_Master'); // Theo tên
			if (isset($Main_Master) && $rowCount_1 > 1) {

				$flag = 0;
				$errorCount = "Error check on rows: ";
				$allDataInSheet = $Main_Master->toArray(null, true, true, true);

				$createArray = array(
					'machine_type', 'internal_item', 'length_btp', 'width_btp', 'rbo', 'so_day', 'loai_chi_doc', 'loai_cat_gap', 'pattern', 'mat_do_banh_rang', 'length_tp', 'width_tp', 'cbs', 'scrap', 'cut_type',
					'phuong_phap_xe', 'tskt_cw', 'nhiet_det', 'so_met_tung_may', 'ti_le_qua_ho_nuoc', 'so_cai_min', 'taffeta_satin', 'so_kho', 'so_day_moi', 'scrap_sonic', 'remark_1', 'remark_2', 'remark_3'
				);
				$makeArray = array(
					'machine_type' => 'machine_type',
					'internal_item' => 'internal_item',
					'length_btp' => 'length_btp',
					'width_btp' => 'width_btp',
					'rbo' => 'rbo',
					'so_day' => 'so_day',
					'loai_chi_doc' => 'loai_chi_doc',
					'loai_cat_gap' => 'loai_cat_gap',
					'pattern' => 'pattern',
					'mat_do_banh_rang' => 'mat_do_banh_rang',

					'length_tp' => 'length_tp',
					'width_tp' => 'width_tp',
					'cbs' => 'cbs',
					'scrap' => 'scrap',
					'cut_type' => 'cut_type',
					'phuong_phap_xe' => 'phuong_phap_xe',
					'tskt_cw' => 'tskt_cw',
					'nhiet_det' => 'nhiet_det',
					'so_met_tung_may' => 'so_met_tung_may',
					'ti_le_qua_ho_nuoc' => 'ti_le_qua_ho_nuoc',

					'so_cai_min' => 'so_cai_min',
					'taffeta_satin' => 'taffeta_satin',
					'so_kho' => 'so_kho',
					'so_day_moi' => 'so_day_moi',
					'scrap_sonic' => 'scrap_sonic',
					'remark_1' => 'remark_1',
					'remark_2' => 'remark_2',
					'remark_3' => 'remark_3'
				);

				$SheetDataKey = array();
				foreach ($allDataInSheet as $dataInSheet) {
					foreach ($dataInSheet as $key => $value) {
						if (in_array(trim($value), $createArray)) {
							$value = preg_replace('/\s+/', '', $value);
							$SheetDataKey[trim($value)] = $key;
						} else {
						}
					}
				}

				// check data
				$data = array_diff_key($makeArray, $SheetDataKey);
				if (empty($data)) {
					$flag = 1;
				}
				if ($flag == 1) {
					for ($i = 2; $i <= count($allDataInSheet); $i++) {

						$machine_type = $SheetDataKey['machine_type'];
						$internal_item = $SheetDataKey['internal_item'];
						$length_btp = $SheetDataKey['length_btp'];
						$width_btp = $SheetDataKey['width_btp'];
						$rbo = $SheetDataKey['rbo'];
						$wire_number = $SheetDataKey['so_day'];
						$vertical_thread_type = $SheetDataKey['loai_chi_doc'];
						$folding_cut_type = $SheetDataKey['loai_cat_gap'];
						$pattern = $SheetDataKey['pattern'];
						$gear_density = $SheetDataKey['mat_do_banh_rang'];

						$length_tp = $SheetDataKey['length_tp'];
						$width_tp = $SheetDataKey['width_tp'];
						$cbs = $SheetDataKey['cbs'];
						$scrap = $SheetDataKey['scrap'];
						$cut_type = $SheetDataKey['cut_type'];
						$sawing_method = $SheetDataKey['phuong_phap_xe'];
						$cw_specification = $SheetDataKey['tskt_cw'];
						$heat_weaving = $SheetDataKey['nhiet_det'];
						$meter_number_per_machine = $SheetDataKey['so_met_tung_may'];
						if (stripos($machine_type, 'wv') !== false) {
							$meter_number_per_machine = 1.3;
						} else if (stripos($machine_type, 'cw') !== false) {
							$meter_number_per_machine = 1.7;
						} else if (stripos($machine_type, 'lb') !== false) {
							$meter_number_per_machine = 1.7;
						}

						$water_glue_rate = $SheetDataKey['ti_le_qua_ho_nuoc'];

						$so_cai_min = $SheetDataKey['so_cai_min'];
						$taffeta_satin = $SheetDataKey['taffeta_satin'];
						// $textile_size_number = $SheetDataKey['so_kho']; // số khổ

						$new_wire_number = $SheetDataKey['so_day_moi'];
						$scrap_sonic = $SheetDataKey['scrap_sonic'];
						$remark1 = $SheetDataKey['remark_1'];
						$remark2 = $SheetDataKey['remark_2'];
						$remark3 = $SheetDataKey['remark_3'];

						$machine_type = filter_var(trim(strtolower($allDataInSheet[$i][$machine_type])), FILTER_SANITIZE_STRING);
						$internal_item = filter_var(trim($allDataInSheet[$i][$internal_item]), FILTER_SANITIZE_STRING);
						$length_btp = filter_var(trim($allDataInSheet[$i][$length_btp]), FILTER_SANITIZE_STRING);
						// $length_btp = (float)$length_btp;
						$width_btp = filter_var(trim($allDataInSheet[$i][$width_btp]), FILTER_SANITIZE_STRING);

						$rbo = filter_var(trim($allDataInSheet[$i][$rbo]), FILTER_SANITIZE_STRING);

						$wire_number = filter_var(trim($allDataInSheet[$i][$wire_number]), FILTER_SANITIZE_STRING);

						$vertical_thread_type = filter_var(trim($allDataInSheet[$i][$vertical_thread_type]), FILTER_SANITIZE_STRING);
						$folding_cut_type = filter_var(trim($allDataInSheet[$i][$folding_cut_type]), FILTER_SANITIZE_STRING);
						$pattern = filter_var(trim($allDataInSheet[$i][$pattern]), FILTER_SANITIZE_STRING);
						$gear_density = filter_var(trim($allDataInSheet[$i][$gear_density]), FILTER_SANITIZE_STRING);
						$length_tp = filter_var(trim($allDataInSheet[$i][$length_tp]), FILTER_SANITIZE_STRING);
						$width_tp = filter_var(trim($allDataInSheet[$i][$width_tp]), FILTER_SANITIZE_STRING);

						$cbs = filter_var(trim($allDataInSheet[$i][$cbs]), FILTER_SANITIZE_STRING);
						$scrap = filter_var(trim($allDataInSheet[$i][$scrap]), FILTER_SANITIZE_STRING);

						// $scrap = (strpos($allDataInSheet[$i][$scrap],' ') != false ) ? str_replace(' ', '',$allDataInSheet[$i][$scrap]) : $allDataInSheet[$i][$scrap];
						// $scrap = is_float($scrap) ? (int)$scrap : 0;

						$cut_type = filter_var(trim($allDataInSheet[$i][$cut_type]), FILTER_SANITIZE_STRING);
						$sawing_method = filter_var(trim(strtolower($allDataInSheet[$i][$sawing_method])), FILTER_SANITIZE_STRING);
						$cw_specification = filter_var(trim($allDataInSheet[$i][$cw_specification]), FILTER_SANITIZE_STRING);
						$heat_weaving = filter_var(trim(strtolower($allDataInSheet[$i][$heat_weaving])), FILTER_SANITIZE_STRING);
						$meter_number_per_machine = filter_var(trim(strtolower($allDataInSheet[$i][$meter_number_per_machine])), FILTER_SANITIZE_STRING);

						$water_glue_rate = filter_var(trim(strtolower($allDataInSheet[$i][$water_glue_rate])), FILTER_SANITIZE_STRING);
						$so_cai_min = filter_var(trim(strtolower($allDataInSheet[$i][$so_cai_min])), FILTER_SANITIZE_STRING);
						$taffeta_satin = filter_var(trim(strtolower($allDataInSheet[$i][$taffeta_satin])), FILTER_SANITIZE_STRING);
						//$textile_size_number = filter_var(trim(strtolower($allDataInSheet[$i][$textile_size_number])), FILTER_SANITIZE_STRING);

						$textile_size_number = $this->textile_size_number($taffeta_satin, $machine_type);

						$new_wire_number = filter_var(trim(strtolower($allDataInSheet[$i][$new_wire_number])), FILTER_SANITIZE_STRING);

						// số scrap sonic
						$scrap_sonic = filter_var(trim($allDataInSheet[$i][$scrap_sonic]), FILTER_SANITIZE_STRING);

						$remark1 = filter_var(trim($allDataInSheet[$i][$remark1]), FILTER_SANITIZE_STRING);
						$remark2 = filter_var(trim($allDataInSheet[$i][$remark2]), FILTER_SANITIZE_STRING);
						$remark3 = filter_var(trim($allDataInSheet[$i][$remark3]), FILTER_SANITIZE_STRING);

						// Check EMPTY
						if (empty($machine_type) && empty($internal_item) && ($length_btp == '' || $length_btp == 0) && $rbo == '') {
							break;
						} else if (empty($machine_type) || empty($internal_item) || $length_btp == '' || $length_btp == 0) {
							$errorCount .= $i . ", ";
							continue;
						}

						// check duplicate
						$check_master_data = 0;
						if (!empty($masterDataInsert)) {
							foreach ($masterDataInsert as $master_duplicate) {
								if ($machine_type == $master_duplicate['machine_type'] && $internal_item == $master_duplicate['internal_item'] && $length_btp == $master_duplicate['length_btp']) {
									$check_master_data = 1;
									break;
								}
							}
						}
						if ($check_master_data == 1) continue;

						$masterDataInsert[] = array(
							'machine_type' => $machine_type,
							'internal_item' => $internal_item,
							'length_btp' => $length_btp,
							'width_btp' => $width_btp,
							'rbo' => $rbo,
							'wire_number' => (int)$wire_number,
							'vertical_thread_type' => $vertical_thread_type,
							'folding_cut_type' => $folding_cut_type,
							'pattern' => $pattern,
							'gear_density' => $gear_density,
							'length_tp' => (float)$length_tp,
							'width_tp' => (float)$width_tp,
							'cbs' => (int)$cbs,
							'scrap' => (float)$scrap,
							'cut_type' => $cut_type,
							'sawing_method' => $sawing_method,
							'cw_specification' => $cw_specification,
							'heat_weaving' => $heat_weaving,
							'meter_number_per_machine' => (float)$meter_number_per_machine,
							'water_glue_rate' => $water_glue_rate,
							'so_cai_min' => $so_cai_min,
							'taffeta_satin' => $taffeta_satin,
							'textile_size_number' => (float)$textile_size_number,
							'new_wire_number' => (int)$new_wire_number,
							'scrap_sonic' => (int)$scrap_sonic,
							'remark_1' => $remark1,
							'remark_2' => $remark2,
							'remark_3' => $remark3,
							'updated_by' => $updated_by,
							'updated_date' => $updated_date
						);
					} // end for master data

					if (!empty($masterDataInsert)) {
						foreach ($masterDataInsert as $key => $main_master_value) {
							$machine_type = $main_master_value['machine_type'];
							$internal_item = $main_master_value['internal_item'];
							$length_btp = $main_master_value['length_btp'];

							if ($this->wv_master_item->isAlreadyExist($machine_type, $internal_item, $length_btp)) {

								$masterDataCheck = array('machine_type' => $machine_type, 'internal_item' => $internal_item, 'length_btp' => $length_btp);
								// set update data
								$masterDataUpdate = $masterDataInsert[$key];

								// delete element upId
								unset($masterDataInsert[$key]);

								// delete machine, item, length for update data
								unset($masterDataUpdate['machine_type']);
								unset($masterDataUpdate['internal_item']);
								unset($masterDataUpdate['length_btp']);

								$result_1 = $this->wv_master_item->update($masterDataUpdate, $masterDataCheck);
								if ($result_1 != TRUE) {
									$this->_data = array(
										'status' => false,
										'message' => 'Error 1.1. Import data to Master Data error: ' . $result_1
									);
									echo json_encode($this->_data['results']);
									exit();
								}
							}
						}
					}

					// 1. Insert Master Data Table
					if (!empty($masterDataInsert)) {
						$this->wv_master_item->setInsertBatch($masterDataInsert);
						$result_1 = $this->wv_master_item->insertBatch();
						if ($result_1 != TRUE) {
							$this->_data['results'] = array(
								'status' => false,
								'message' => 'Error 1.2. Import data to Master Data error: ' . $result_1
							);
							echo json_encode($this->_data['results']);
							exit();
						}
					}
				} // end flag

			}

			if (isset($Material)  && $rowCount_2 > 1) {
				$flag = 0;
				$errorCount = "Error check on rows: ";
				$allDataInSheet = $Material->toArray(null, true, true, true);

				$createArray = array(
					'machine_type', 'internal_item', 'length_btp', 'material_code', 'material_type', 'mat_do', 'so_pick', 'thu_tu'
				);
				$makeArray = array(
					'machine_type' => 'machine_type',
					'internal_item' => 'internal_item',
					'length_btp' => 'length_btp',
					'material_code' => 'material_code',
					'material_type' => 'material_type',
					'mat_do' => 'mat_do',
					'so_pick' => 'so_pick',
					'thu_tu' => 'thu_tu'
				);
				$SheetDataKey = array();
				foreach ($allDataInSheet as $dataInSheet) {
					foreach ($dataInSheet as $key => $value) {
						if (in_array(trim($value), $createArray)) {
							$value = preg_replace('/\s+/', '', $value);
							$SheetDataKey[trim($value)] = $key;
						} else {
						}
					}
				}

				// check data
				$data = array_diff_key($makeArray, $SheetDataKey);
				if (empty($data)) {
					$flag = 1;
				}
				if ($flag == 1) {
					for ($i = 2; $i <= count($allDataInSheet); $i++) {

						$machine_type = $SheetDataKey['machine_type'];
						$internal_item = $SheetDataKey['internal_item'];
						$length_btp = $SheetDataKey['length_btp'];
						$material_code = $SheetDataKey['material_code'];
						$material_type = $SheetDataKey['material_type'];
						$mat_do = $SheetDataKey['mat_do'];
						$so_pick = $SheetDataKey['so_pick'];
						$thu_tu = $SheetDataKey['thu_tu'];

						$machine_type = filter_var(trim(strtolower($allDataInSheet[$i][$machine_type])), FILTER_SANITIZE_STRING);
						$internal_item = filter_var(trim($allDataInSheet[$i][$internal_item]), FILTER_SANITIZE_STRING);
						$length_btp = filter_var(trim($allDataInSheet[$i][$length_btp]), FILTER_SANITIZE_STRING);
						$code_name = filter_var(trim($allDataInSheet[$i][$material_code]), FILTER_SANITIZE_STRING);
						$code_type = filter_var(trim(strtolower($allDataInSheet[$i][$material_type])), FILTER_SANITIZE_STRING);
						$density = filter_var(trim($allDataInSheet[$i][$mat_do]), FILTER_SANITIZE_STRING);
						$pick_number = filter_var(trim($allDataInSheet[$i][$so_pick]), FILTER_SANITIZE_STRING);
						$order = filter_var(trim($allDataInSheet[$i][$thu_tu]), FILTER_SANITIZE_STRING);

						// Check EMPTY
						if (empty($internal_item) && (empty($length_btp) || $length_btp == 0) && empty($code_name)) {
							break;
						} else if (empty($internal_item) || empty($length_btp) || $length_btp == 0 || empty($code_name)) {
							$errorCount .= $i . ", ";
							continue;
						}

						// check duplicate
						$check_supply = 0;
						if (!empty($supplyInsert)) {
							foreach ($supplyInsert as $supply_duplicate) {
								if ($internal_item == $supply_duplicate['internal_item'] && $length_btp == $supply_duplicate['length_btp'] && $code_name == $supply_duplicate['code_name'] && $order == $supply_duplicate['order']) {
									$check_supply = 1;
									break;
								}
							}
						}
						if ($check_supply == 1) continue;

						$supplyInsert[] = array(
							'machine_type' => '',
							'internal_item' => $internal_item,
							'length_btp' => $length_btp,
							'code_name' => $code_name,
							'code_type' => $code_type,
							'density' => $density,
							'pick_number' => $pick_number,
							'order' => $order
						);
					} //end for supply

					// check update
					if (!empty($supplyInsert)) {
						foreach ($supplyInsert as $key => $supply) {
							$internal_item = $supply['internal_item'];
							$length_btp = $supply['length_btp'];
							$code_name = $supply['code_name'];
							$order = $supply['order'];

							if ($this->supply->isAlreadyExist($internal_item, $length_btp, $code_name, $order)) {

								$supplyDataCheck = array('internal_item' => $internal_item, 'length_btp' => $length_btp, 'code_name' => $code_name, 'order' => $order);
								// set update data
								$supplyDataUpdate = $supplyInsert[$key];

								// delete element upId
								unset($supplyInsert[$key]);

								// delete primary key for update data
								unset($supplyDataUpdate['internal_item']);
								unset($supplyDataUpdate['length_btp']);
								unset($supplyDataUpdate['code_name']);
								unset($supplyDataUpdate['order']);

								$result_2 = $this->supply->update($supplyDataUpdate, $supplyDataCheck);
								if ($result_2 != TRUE) {
									$this->_data = array(
										'status' => false,
										'message' => 'Error 2.1. Import data error: ' . $result_2
									);
									echo json_encode($this->_data['results']);
									exit();
								}
							}
						}
					}


					// print_r($supplyInsert); exit();
					// 2. Insert Data Table
					if (!empty($supplyInsert)) {
						$this->supply->setInsertBatch($supplyInsert);
						$result_2 = $this->supply->insertBatch();
						if ($result_2 != TRUE) {
							$this->_data['results'] = array(
								'status' => false,
								'message' => 'Error 2.2. Import data error: ' . $result_2
							);
							echo json_encode($this->_data['results']);
							exit();
						}
					}
				} // end flag

			}

			if (isset($Process)  && $rowCount_3 > 1) {
				$flag = 0;
				$errorCount = "Error check on rows: ";
				$allDataInSheet = $Process->toArray(null, true, true, true);

				$createArray = array(
					'machine_type', 'internal_item', 'length_btp', 'det', 'xe_sonic', 'qua_ho', 'qua_nuoc', 'noi_dau', 'dan_keo', 'cat_gap', 'cat_laser', 'dong_goi'
				);
				$makeArray = array(
					'machine_type' => 'machine_type',
					'internal_item' => 'internal_item',
					'length_btp' => 'length_btp',
					'det' => 'det',
					'xe_sonic' => 'xe_sonic',
					'qua_ho' => 'qua_ho',
					'qua_nuoc' => 'qua_nuoc',
					'noi_dau' => 'noi_dau',
					'dan_keo' => 'dan_keo',
					'cat_gap' => 'cat_gap',
					'cat_laser' => 'cat_laser',
					'dong_goi' => 'dong_goi'
				);
				$SheetDataKey = array();
				foreach ($allDataInSheet as $dataInSheet) {
					foreach ($dataInSheet as $key => $value) {
						if (in_array(trim($value), $createArray)) {
							$value = preg_replace('/\s+/', '', $value);
							$SheetDataKey[trim($value)] = $key;
						} else {
						}
					}
				}

				// check data
				$data = array_diff_key($makeArray, $SheetDataKey);
				if (empty($data)) {
					$flag = 1;
				}
				if ($flag == 1) {
					for ($i = 2; $i <= count($allDataInSheet); $i++) {

						$machine_type = $SheetDataKey['machine_type'];
						$internal_item = $SheetDataKey['internal_item'];
						$length_btp = $SheetDataKey['length_btp'];
						$det = $SheetDataKey['det'];
						$xe_sonic = $SheetDataKey['xe_sonic'];
						$qua_ho = $SheetDataKey['qua_ho'];
						$qua_nuoc = $SheetDataKey['qua_nuoc'];
						$noi_dau = $SheetDataKey['noi_dau'];
						$dan_keo = $SheetDataKey['dan_keo'];
						$cat_gap = $SheetDataKey['cat_gap'];
						$cat_laser = $SheetDataKey['cat_laser'];
						$dong_goi = $SheetDataKey['dong_goi'];

						$machine_type = filter_var(trim(strtolower($allDataInSheet[$i][$machine_type])), FILTER_SANITIZE_STRING);
						$internal_item = filter_var(trim($allDataInSheet[$i][$internal_item]), FILTER_SANITIZE_STRING);
						$length_btp = filter_var(trim($allDataInSheet[$i][$length_btp]), FILTER_SANITIZE_STRING);
						$wv_01 = filter_var(trim($allDataInSheet[$i][$det]), FILTER_SANITIZE_STRING);
						$wv_02 = filter_var(trim(strtolower($allDataInSheet[$i][$xe_sonic])), FILTER_SANITIZE_STRING);
						$wv_03 = filter_var(trim(strtolower($allDataInSheet[$i][$qua_ho])), FILTER_SANITIZE_STRING);
						$wv_04 = filter_var(trim(strtolower($allDataInSheet[$i][$qua_nuoc])), FILTER_SANITIZE_STRING);
						$wv_05 = filter_var(trim(strtolower($allDataInSheet[$i][$noi_dau])), FILTER_SANITIZE_STRING);
						$wv_06 = filter_var(trim(strtolower($allDataInSheet[$i][$dan_keo])), FILTER_SANITIZE_STRING);
						$wv_07 = filter_var(trim(strtolower($allDataInSheet[$i][$cat_gap])), FILTER_SANITIZE_STRING);
						$wv_08 = filter_var(trim(strtolower($allDataInSheet[$i][$cat_laser])), FILTER_SANITIZE_STRING);
						$wv_09 = filter_var(trim(strtolower($allDataInSheet[$i][$dong_goi])), FILTER_SANITIZE_STRING);

						$proces_status = array(
							'wv_01' => (int)$wv_01,
							'wv_02' => (int)$wv_02,
							'wv_03' => (int)$wv_03,
							'wv_04' => (int)$wv_04,
							'wv_05' => (int)$wv_05,
							'wv_06' => (int)$wv_06,
							'wv_07' => (int)$wv_07,
							'wv_08' => (int)$wv_08,
							'wv_09' => (int)$wv_09
						);

						// Check EMPTY
						if (empty($internal_item) && (empty($length_btp) || $length_btp == 0)) {
							break;
						} else if (empty($internal_item) || $length_btp == '' || $length_btp == 0) {
							$errorCount .= $i . ", ";
							continue;
						}



						foreach ($proces_status as $key => $process_value) {

							$process_code = $key;
							// check duplicate
							$check_process = 0;
							if (!empty($processInsert)) {
								foreach ($processInsert as $process_duplicate) {
									if ($internal_item == $process_duplicate['internal_item'] && $length_btp == $process_duplicate['length_btp'] && $process_code == $process_duplicate['process_code']) {
										$check_process = 1;
										break;
									}
								}
							}
							if ($check_process == 1) continue;

							$processInsert[] = array(
								'machine_type' => $machine_type,
								'internal_item' => $internal_item,
								'length_btp' => $length_btp,
								'process_code' => $process_code,
								'status' => $process_value
							);
						}
					}

					// update
					if (!empty($processInsert)) {
						foreach ($processInsert as $key => $process_item) {
							$internal_item = $process_item['internal_item'];
							$length_btp = $process_item['length_btp'];
							$process_code = $process_item['process_code'];

							if ($this->process->isAlreadyExist($internal_item, $length_btp, $process_code)) {

								$processDataCheck = array('internal_item' => $internal_item, 'length_btp' => $length_btp, 'process_code' => $process_code);
								// set update data
								$processDataUpdate = $processInsert[$key];

								// delete element key
								unset($processInsert[$key]);

								// delete primary key for update data
								unset($processDataUpdate['internal_item']);
								unset($processDataUpdate['length_btp']);
								unset($processDataUpdate['process_code']);

								$result_3 = $this->process->update($processDataUpdate, $processDataCheck);
								if ($result_3 != TRUE) {
									$this->_data = array(
										'status' => false,
										'message' => 'Error 3.1. Import data error: ' . $result_3
									);
									echo json_encode($this->_data['results']);
									exit();
								}
							}
						}
					}

					// 1.3Insert Data Table
					if (!empty($processInsert)) {
						$this->process->setInsertBatch($processInsert);
						$result_3 = $this->process->insertBatch();
						if ($result_3 != TRUE) {
							$this->_data['results'] = array(
								'status' => false,
								'message' => 'Error 3.2. Import data error: ' . $result_3
							);
							echo json_encode($this->_data['results']);
							exit();
						}
					}
				} // end flag
			}

			$message = 'Success. ';
			$resultsOK = FALSE;
			if (isset($result_1) && $result_1 == TRUE) {
				$message .= 'Main Master imported. ';
				$resultsOK = TRUE;
			};

			if (isset($result_2) && $result_2 == TRUE) {
				$message .= 'Material imported. ';
				$resultsOK = TRUE;
			}

			if (isset($result_3) && $result_3 == TRUE) {
				$message .= 'Process imported. ';
				$resultsOK = TRUE;
			}

			if ($resultsOK == TRUE) {
				// success
				$this->_data['results'] = array(
					'status' => true,
					'message' => $message
				);
			} else {
				// error
				$this->_data['results'] = array(
					'status' => false,
					'message' => 'Import data error (*). '
				);
			}
		} else {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Import data error'
			);
		}

		$this->load->view('woven/masterData/display', $this->_data);
	}

	// tạo mới xử lý import cho nhanh hơn
	public function importSpecialItem()
	{
		$this->_data['title'] = 'Import Special Item Remarks';
		$productionLine = isset($_COOKIE['plan_department']) ? strtolower($_COOKIE['plan_department']) : '';

		if ($this->input->post('importfile')) {

			// init var
			$result = TRUE;
			$message = ' lines updated data successfully ';
			$error = 0;
			$errorArr = array();
			$specialItemRemarks = array();

			// config info
			$path = 'uploads/';
			$config['upload_path'] = $path;
			$config['allowed_types'] = 'xlsx|xls';
			$config['remove_spaces'] = TRUE;
			$this->upload->initialize($config);
			$this->load->library('upload', $config);

			// check error (1)
			if (!$this->upload->do_upload('masterfile')) {
				$error = array('error' => $this->upload->display_errors());
			} else {
				$data = array('upload_data' => $this->upload->data());
			}

			// check file (2)
			if (!empty($data['upload_data']['file_name'])) {
				$import_xls_file = $data['upload_data']['file_name'];
			} else {
				$import_xls_file = 0;
			}

			// // // test file
			// $import_xls_file = 'NIKE_FT.xlsx';
			// // $path = 'uploads/';

			// Check ok
			if ($error == 0 && $import_xls_file !== 0) {
				// get file
				$inputFileName = $path . $import_xls_file;
				// init PhpSpreadsheet Xlsx
				$Reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
				// get sheet 0 (sheet 1)
				$spreadSheet = $Reader->load($inputFileName); // ->getActiveSheet(0);
				$spreadSheet = $spreadSheet->getSheet(0); // Theo tên
				$allDataInSheet = $spreadSheet->toArray(null, true, true, true);

				// print_r($allDataInSheet); exit();

				// check col name exist
				$createArray = array('Item', 'Remark');
				$makeArray = array('Item' => 'Item', 'Remark' => 'Remark');
				$SheetDataKey = array();
				foreach ($allDataInSheet as $dataInSheet) {
					foreach ($dataInSheet as $key => $value) {
						if (in_array(trim($value), $createArray)) {
							$value = preg_replace('/\s+/', '', $value);
							$SheetDataKey[trim($value)] = $key;
						} else {
						}
					}
				}

				// check data
				$flag = 0;
				$data = array_diff_key($makeArray, $SheetDataKey);
				if (empty($data)) {
					$flag = 1;
				}

				// load data
				if ($flag == 1) {

					for ($i = 2; $i <= count($allDataInSheet); $i++) {
						// get col key
						$item = $SheetDataKey['Item'];
						$remark = $SheetDataKey['Remark'];

						// get data 
						$item = filter_var(trim(strtoupper($allDataInSheet[$i][$item])), FILTER_SANITIZE_STRING);
						$remark = filter_var(trim($allDataInSheet[$i][$remark]), FILTER_SANITIZE_STRING);

						// check empty data
						if (empty($item) || empty($remark)) continue;

						// get data
						$specialItemRemarks[] = array('item' => $item, 'remark' => $remark);
					}
				}

				/* === check data from master item table, update grs col to this table ===================== */
				// load models
				$this->load->model('woven_master_item', 'wv_master_item');
				$masterData = $this->wv_master_item->read();
				$count = 0;
				if (!empty($specialItemRemarks) && !empty($masterData)) {
					foreach ($masterData as $row) {
						$machine_type = trim($row['machine_type']);
						$internal_item = trim(strtoupper($row['internal_item']));
						$length_btp = trim($row['length_btp']);

						foreach ($specialItemRemarks as $value) {
							if ($value['item'] == $internal_item) {

								$where = array('machine_type' => $machine_type, 'internal_item' => $internal_item, 'length_btp' => $length_btp);

								$result = $this->wv_master_item->update(array('special_item_remark' => $remark), $where);
								// count error
								if (!$result) $errorArr = array($item);
								else $count++;
							}
						}
					}
				}

				// result
				$message = empty($errorArr) ? ($count . $message) : ($message . ' Error Item: ' . implode(' && ', $errorArr));
				$this->_data['results'] = array(
					'status' => true,
					'message' => $message
				);
			}
		} else {
			$message = 'Import data error';
			$this->_data['results'] = array(
				'status' => false,
				'message' => $message
			);
		}

		$this->load->view('woven/masterData/specialItemRemarks', $this->_data);
	}

	// view master data
	public function viewMasterFile()
	{
		$this->_data['title'] = 'Woven Master File';

		$this->load->view('woven/masterData/view_masterfile', $this->_data);
	}

	// load master data (main)
	public function loadMasterFile()
	{
		$this->_data['title'] = 'Master File';

		$results = array();

		// load models
		$this->load->model('woven_master_item', 'wv_master_item');

		if (!$this->wv_master_item->countAll() > 0) {
			$results = array(
				"status" => false,
				"message" => "Master Item không có dữ liệu"
			);
		} else {
			$dataMaster = $this->wv_master_item->read();
			$index = 1;
			foreach ($dataMaster as $key => $item) {
				//$i = $key + 1;
				$data[] = [
					'id' => $index,
					'data' => [
						$index,
						$item['machine_type'],
						$item['internal_item'],
						$item['length_btp'],
						$item['width_btp'],
						$item['rbo'],
						$item['wire_number'],
						$item['vertical_thread_type'],
						$item['folding_cut_type'],
						$item['pattern'],
						$item['gear_density'],

						$item['length_tp'],
						$item['width_tp'],
						$item['cbs'],
						$item['scrap'],
						$item['cut_type'],
						$item['sawing_method'],
						$item['cw_specification'],
						$item['heat_weaving'],
						$item['meter_number_per_machine'],
						$item['water_glue_rate'],

						$item['so_cai_min'],
						$item['taffeta_satin'],
						$item['textile_size_number'],
						$item['new_wire_number'],
						$item['scrap_sonic'],
						// $item['pick_number_total'],
						$item['remark_1'],
						$item['remark_2'],
						$item['remark_3'],
						$item['updated_by'],
						date('Y-m-d', strtotime($item['updated_date'])),
						$item['special_item_remark'],
						$item['process'],

					]
				];

				$index++;
			}

			$results = array(
				"status" => true,
				"message" => "Load Data Success",
				"dataMaster" => $data
			);
		}

		echo json_encode($results, JSON_UNESCAPED_UNICODE);
	}

	// load master data (supply)
	public function loadMasterFileSupply()
	{

		$this->_data['title'] = 'Supply';

		$results = array();

		// load models
		$this->load->model('woven_master_item_supply', 'supply');

		if (!$this->supply->countAll() > 0) {
			$results = array(
				"status" => false,
				"message" => "Supply Master Item không có dữ liệu"
			);
		} else {
			$dataMaster = $this->supply->read();
			$index = 1;
			foreach ($dataMaster as $key => $item) {
				$data[] = [
					'id' => $index,
					'data' => [
						$index,
						// $item['machine_type'],
						$item['internal_item'],
						$item['length_btp'],
						$item['code_name'],
						$item['code_type'],
						$item['density'],
						$item['pick_number'],
						$item['order']
					]
				];

				$index++;
			}

			$results = array(
				"status" => true,
				"message" => "Load Data Success",
				"dataMaster" => $data
			);
		}

		echo json_encode($results, JSON_UNESCAPED_UNICODE);
	}


	// export master data
	public function exportMasterData()
	{
		$this->_data['title'] = 'Export Master Data';
		$option = null !== $this->input->get('option') ? trim($this->input->get('option')) : '';

		// load excel library
		$this->load->library('excel');
		$this->load->model('woven_master_item', 'wv_master_item');
		$this->load->model('woven_master_item_supply', 'supply');
		$this->load->model('woven_master_item_process', 'process');
		$this->load->model('common_setting_process', 'setting_process');

		$productionLine = isset($_COOKIE['plan_department']) ? strtolower($_COOKIE['plan_department']) : '';

		// get data from database
		$masterData = $this->wv_master_item->read();
		$supplyData = $this->supply->read();
		$processData = $this->process->read();

		$processExport = array();

		// Add new sheet
		$objPHPExcel = new PHPExcel();
		$nameEx = '';
		if ($option == 'exportMaster') {
			/** =========MAIN_MASTER================================================================================ */
			$nameEx = 'Main_Master_';
			// Add new sheet
			$objPHPExcel->createSheet();

			// Add some data
			$objPHPExcel->setActiveSheetIndex(0);

			// active and set title
			$objPHPExcel->getActiveSheet()->setTitle('Main_Master');

			// set Header, width
			$array_az = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE');
			// set format
			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(7);
			foreach ($array_az as $key_c => $column) {
				$this->cellColor($objPHPExcel, $column . '1', '80ccff');
				if ($key_c == 0) {
					continue;
				}
				$objPHPExcel->getActiveSheet()->getColumnDimension($column)->setWidth(20);
			}

			// font-weigth
			$objPHPExcel->getActiveSheet()->getStyle("A1:AE1")->getFont()->setBold(true);

			// color cell
			$this->cellColor($objPHPExcel, 'A1', '80ccff');
			// set header value
			$objPHPExcel->getActiveSheet()->SetCellValue('A1', 'tt');

			$objPHPExcel->getActiveSheet()->SetCellValue('B1', 'machine_type');
			$objPHPExcel->getActiveSheet()->SetCellValue('C1', 'internal_item');
			$objPHPExcel->getActiveSheet()->SetCellValue('D1', 'length_btp');
			$objPHPExcel->getActiveSheet()->SetCellValue('E1', 'width_btp');
			$objPHPExcel->getActiveSheet()->SetCellValue('F1', 'rbo');
			$objPHPExcel->getActiveSheet()->SetCellValue('G1', 'so_day');
			$objPHPExcel->getActiveSheet()->SetCellValue('H1', 'loai_chi_doc');
			$objPHPExcel->getActiveSheet()->SetCellValue('I1', 'loai_cat_gap');
			$objPHPExcel->getActiveSheet()->SetCellValue('J1', 'pattern');
			$objPHPExcel->getActiveSheet()->SetCellValue('K1', 'mat_do_banh_rang');

			$objPHPExcel->getActiveSheet()->SetCellValue('L1', 'length_tp');
			$objPHPExcel->getActiveSheet()->SetCellValue('M1', 'width_tp');
			$objPHPExcel->getActiveSheet()->SetCellValue('N1', 'cbs');
			$objPHPExcel->getActiveSheet()->SetCellValue('O1', 'scrap');
			$objPHPExcel->getActiveSheet()->SetCellValue('P1', 'cut_type');
			$objPHPExcel->getActiveSheet()->SetCellValue('Q1', 'phuong_phap_xe');
			$objPHPExcel->getActiveSheet()->SetCellValue('R1', 'tskt_cw');
			$objPHPExcel->getActiveSheet()->SetCellValue('S1', 'nhiet_det');
			$objPHPExcel->getActiveSheet()->SetCellValue('T1', 'so_met_tung_may');
			$objPHPExcel->getActiveSheet()->SetCellValue('U1', 'ti_le_qua_ho_nuoc');

			$objPHPExcel->getActiveSheet()->SetCellValue('V1', 'so_cai_min');
			$objPHPExcel->getActiveSheet()->SetCellValue('W1', 'taffeta_satin');
			$objPHPExcel->getActiveSheet()->SetCellValue('X1', 'so_kho');
			$objPHPExcel->getActiveSheet()->SetCellValue('Y1', 'so_day_moi');
			$objPHPExcel->getActiveSheet()->SetCellValue('Z1', 'scrap_sonic');
			$objPHPExcel->getActiveSheet()->SetCellValue('AA1', 'remark_1');
			$objPHPExcel->getActiveSheet()->SetCellValue('AB1', 'remark_2');
			$objPHPExcel->getActiveSheet()->SetCellValue('AC1', 'remark_3');
			$objPHPExcel->getActiveSheet()->SetCellValue('AD1', 'updated_by');
			$objPHPExcel->getActiveSheet()->SetCellValue('AE1', 'updated_date');

			// // $objPHPExcel->getActiveSheet()->SetCellValue('AE1', 'group');
			// // $objPHPExcel->getActiveSheet()->SetCellValue('AF1', 'security');
			// // $objPHPExcel->getActiveSheet()->SetCellValue('AG1', 'fg_ipp');
			// // $objPHPExcel->getActiveSheet()->SetCellValue('AH1', 'pcs_set');
			// // $objPHPExcel->getActiveSheet()->SetCellValue('AI1', 'chieu_in_thuc_te');
			// // $objPHPExcel->getActiveSheet()->SetCellValue('AJ1', 'material_code_2');
			// // $objPHPExcel->getActiveSheet()->SetCellValue('AK1', 'material_des_2');
			// // $objPHPExcel->getActiveSheet()->SetCellValue('AL1', 'material_uom_2');
			// // $objPHPExcel->getActiveSheet()->SetCellValue('AM1', 'ink_code_2');
			// // $objPHPExcel->getActiveSheet()->SetCellValue('AN1', 'ink_des_2');
			// // $objPHPExcel->getActiveSheet()->SetCellValue('AO1', 'layout_prepress');


			$index = 0;
			$rowCount = 1;
			foreach ($masterData as $element) {

				$rowCount++;
				$index++;

				$objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, $index);

				$objPHPExcel->getActiveSheet()->SetCellValue('B' . $rowCount, $element['machine_type']);
				$objPHPExcel->getActiveSheet()->SetCellValue('C' . $rowCount, $element['internal_item']);
				$objPHPExcel->getActiveSheet()->SetCellValue('D' . $rowCount, $element['length_btp']);
				$objPHPExcel->getActiveSheet()->SetCellValue('E' . $rowCount, $element['width_btp']);
				// Do khi import dữ liệu được mã theo mã html nên cần giải mã html_entity_decode
				$objPHPExcel->getActiveSheet()->SetCellValue('F' . $rowCount, html_entity_decode($element['rbo']));

				$objPHPExcel->getActiveSheet()->SetCellValue('G' . $rowCount, $element['wire_number']);
				$objPHPExcel->getActiveSheet()->SetCellValue('H' . $rowCount, $element['vertical_thread_type']);
				$objPHPExcel->getActiveSheet()->SetCellValue('I' . $rowCount, $element['folding_cut_type']);
				$objPHPExcel->getActiveSheet()->SetCellValue('J' . $rowCount, $element['pattern']);
				$objPHPExcel->getActiveSheet()->SetCellValue('K' . $rowCount, $element['gear_density']);

				$objPHPExcel->getActiveSheet()->SetCellValue('L' . $rowCount, $element['length_tp']);
				$objPHPExcel->getActiveSheet()->SetCellValue('M' . $rowCount, $element['width_tp']);
				$objPHPExcel->getActiveSheet()->SetCellValue('N' . $rowCount, $element['cbs']);
				$objPHPExcel->getActiveSheet()->SetCellValue('O' . $rowCount, $element['scrap']);
				$objPHPExcel->getActiveSheet()->SetCellValue('P' . $rowCount, ltrim($element['cut_type'], "/")); // $cut_type = ltrim($element['cut_type'],"/");

				$objPHPExcel->getActiveSheet()->SetCellValue('Q' . $rowCount, $element['sawing_method']);
				$objPHPExcel->getActiveSheet()->SetCellValue('R' . $rowCount, $element['cw_specification']);
				$objPHPExcel->getActiveSheet()->SetCellValue('S' . $rowCount, $element['heat_weaving']);
				$objPHPExcel->getActiveSheet()->SetCellValue('T' . $rowCount, $element['meter_number_per_machine']);
				$objPHPExcel->getActiveSheet()->SetCellValue('U' . $rowCount, $element['water_glue_rate']);

				$objPHPExcel->getActiveSheet()->SetCellValue('V' . $rowCount, $element['so_cai_min']);
				$objPHPExcel->getActiveSheet()->SetCellValue('W' . $rowCount, $element['taffeta_satin']);
				$objPHPExcel->getActiveSheet()->SetCellValue('X' . $rowCount, $element['textile_size_number']);
				$objPHPExcel->getActiveSheet()->SetCellValue('Y' . $rowCount, $element['new_wire_number']);
				$objPHPExcel->getActiveSheet()->SetCellValue('Z' . $rowCount, $element['scrap_sonic']);

				$objPHPExcel->getActiveSheet()->SetCellValue('AA' . $rowCount, $element['remark_1']);
				$objPHPExcel->getActiveSheet()->SetCellValue('AB' . $rowCount, $element['remark_2']);
				$objPHPExcel->getActiveSheet()->SetCellValue('AC' . $rowCount, $element['remark_3']);
				$objPHPExcel->getActiveSheet()->SetCellValue('AD' . $rowCount, $element['updated_by']);
				$objPHPExcel->getActiveSheet()->SetCellValue('AE' . $rowCount, $element['updated_date']);
			} // for
		} else if ($option == 'exportMasterSupply') {
			/** ========MATERIAL================================================================================= */
			$$nameEx = 'Supply_';
			// Add new sheet
			$objPHPExcel->createSheet();

			// Add some data
			$objPHPExcel->setActiveSheetIndex(0);

			// active and set title
			$objPHPExcel->getActiveSheet()->setTitle('Material');

			// set Header, width
			$array_az = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
			// set format
			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(7);
			foreach ($array_az as $key_c => $column) {
				$this->cellColor($objPHPExcel, $column . '1', '80ccff');
				if ($key_c == 0) {
					continue;
				}
				$objPHPExcel->getActiveSheet()->getColumnDimension($column)->setWidth(20);
			}

			// font-weigth
			$objPHPExcel->getActiveSheet()->getStyle("A1:I1")->getFont()->setBold(true);

			// color cell
			$this->cellColor($objPHPExcel, 'A1', '80ccff');
			// set header value
			$objPHPExcel->getActiveSheet()->SetCellValue('A1', 'tt');

			$objPHPExcel->getActiveSheet()->SetCellValue('B1', 'machine_type');
			$objPHPExcel->getActiveSheet()->SetCellValue('C1', 'internal_item');
			$objPHPExcel->getActiveSheet()->SetCellValue('D1', 'length_btp');
			$objPHPExcel->getActiveSheet()->SetCellValue('E1', 'material_code');
			$objPHPExcel->getActiveSheet()->SetCellValue('F1', 'material_type');
			$objPHPExcel->getActiveSheet()->SetCellValue('G1', 'mat_do');
			$objPHPExcel->getActiveSheet()->SetCellValue('H1', 'so_pick');
			$objPHPExcel->getActiveSheet()->SetCellValue('I1', 'thu_tu');

			$index = 0;
			$rowCount = 1;
			foreach ($supplyData as $element) {

				$rowCount++;
				$index++;

				$objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, $index);

				$objPHPExcel->getActiveSheet()->SetCellValue('B' . $rowCount, $element['machine_type']);
				$objPHPExcel->getActiveSheet()->SetCellValue('C' . $rowCount, $element['internal_item']);
				$objPHPExcel->getActiveSheet()->SetCellValue('D' . $rowCount, $element['length_btp']);
				$objPHPExcel->getActiveSheet()->SetCellValue('E' . $rowCount, $element['code_name']);
				$objPHPExcel->getActiveSheet()->SetCellValue('F' . $rowCount, $element['code_type']);

				$objPHPExcel->getActiveSheet()->SetCellValue('G' . $rowCount, $element['density']);
				$objPHPExcel->getActiveSheet()->SetCellValue('H' . $rowCount, $element['pick_number']);
				$objPHPExcel->getActiveSheet()->SetCellValue('I' . $rowCount, $element['order']);
			} // for
		}

		/** ========================================================================================= */

		// Khởi tạo đối tượng PHPExcel_IOFactory để thực hiện ghi file
		// ở đây mình lưu file dưới dạng excel2007
		header('Content-type: application/vnd.ms-excel');
		$filename = "WOVEN_Master_File_" . $nameEx . date("d_m_Y__H_i_s");
		header('Content-type: application/vnd.ms-excel;charset=utf-8');
		header('Content-Encoding: UTF-8');
		header("Cache-Control: no-store, no-cache");
		header("Content-Disposition: attachment; filename=$filename.xlsx");

		PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007')->save('php://output');
	}

	// export master data
	public function exportMasterData2()
	{
		$this->_data['title'] = 'Export Master Data';
		ini_set('default_charset', "UTF-8");

		$this->load->model('woven_master_item', 'wv_master_item');
		$this->load->model('woven_master_item_supply', 'supply');
		$this->load->model('woven_master_item_process', 'process');
		$this->load->model('common_setting_process', 'setting_process');

		$productionLine = isset($_COOKIE['plan_department']) ? strtolower($_COOKIE['plan_department']) : '';

		// get data from database
		$masterData = $this->wv_master_item->read();
		$supplyData = $this->supply->read();
		$processData = $this->process->read();

		$processExport = array();

		// Add new sheet
		$objPHPExcel = new Spreadsheet();

		/** =========MAIN_MASTER================================================================================ */
		// Add new sheet
		$objPHPExcel->createSheet();

		// Add some data
		$objPHPExcel->setActiveSheetIndex(0);

		// active and set title
		$objPHPExcel->getActiveSheet()->setTitle('Main_Master');

		// set Header, width
		$columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE');

		// columns
		$header1 = array(
			'tt', 'machine_type', 'internal_item', 'length_btp', 'width_btp', 'rbo', 'so_day', 'loai_chi_doc', 'loai_cat_gap', 'pattern',
			'mat_do_banh_rang', 'length_tp', 'width_tp', 'cbs', 'scrap', 'cut_type', 'phuong_phap_xe', 'tskt_cw', 'nhiet_det', 'so_met_tung_may',
			'ti_le_qua_ho_nuoc', 'so_cai_min', 'taffeta_satin', 'so_kho', 'so_day_moi', 'scrap_sonic', 'remark_1', 'remark_2', 'remark_3', 'updated_by', 'updated_date'
		);

		$id = 0;
		foreach ($header1 as $header) {
			for ($index = $id; $index < count($header1); $index++) {
				// width
				$objPHPExcel->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(20);

				// headers
				$objPHPExcel->getActiveSheet()->setCellValue($columns[$index] . '1', $header);

				$id++;
				break;
			}
		}


		// Font
		$objPHPExcel->getActiveSheet()->getStyle('A1:AE1')->getFont()->setBold(true)->setName('Arial')->setSize(10);
		$objPHPExcel->getActiveSheet()->getStyle('A1:AE1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('3399ff');
		$objPHPExcel->getActiveSheet()->getStyle('A:AE')->getFont()->setName('Arial')->setSize(10);

		$index = 0;
		$rowCount = 1;

		$masterData = $this->wv_master_item->read();
		foreach ($masterData as $element) {

			$rowCount++;
			$index++;

			$objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, $index);

			$objPHPExcel->getActiveSheet()->SetCellValue('B' . $rowCount, $element['machine_type']);
			$objPHPExcel->getActiveSheet()->SetCellValue('C' . $rowCount, $element['internal_item']);
			$objPHPExcel->getActiveSheet()->SetCellValue('D' . $rowCount, $element['length_btp']);
			$objPHPExcel->getActiveSheet()->SetCellValue('E' . $rowCount, $element['width_btp']);
			// Do khi import dữ liệu được mã theo mã html nên cần giải mã html_entity_decode
			$objPHPExcel->getActiveSheet()->SetCellValue('F' . $rowCount, html_entity_decode($element['rbo']));

			$objPHPExcel->getActiveSheet()->SetCellValue('G' . $rowCount, $element['wire_number']);
			$objPHPExcel->getActiveSheet()->SetCellValue('H' . $rowCount, $element['vertical_thread_type']);
			$objPHPExcel->getActiveSheet()->SetCellValue('I' . $rowCount, $element['folding_cut_type']);
			$objPHPExcel->getActiveSheet()->SetCellValue('J' . $rowCount, $element['pattern']);
			$objPHPExcel->getActiveSheet()->SetCellValue('K' . $rowCount, $element['gear_density']);

			$objPHPExcel->getActiveSheet()->SetCellValue('L' . $rowCount, $element['length_tp']);
			$objPHPExcel->getActiveSheet()->SetCellValue('M' . $rowCount, $element['width_tp']);
			$objPHPExcel->getActiveSheet()->SetCellValue('N' . $rowCount, $element['cbs']);
			$objPHPExcel->getActiveSheet()->SetCellValue('O' . $rowCount, $element['scrap']);
			$objPHPExcel->getActiveSheet()->SetCellValue('P' . $rowCount, $element['cut_type']);

			$objPHPExcel->getActiveSheet()->SetCellValue('Q' . $rowCount, $element['sawing_method']);
			$objPHPExcel->getActiveSheet()->SetCellValue('R' . $rowCount, $element['cw_specification']);
			$objPHPExcel->getActiveSheet()->SetCellValue('S' . $rowCount, $element['heat_weaving']);
			$objPHPExcel->getActiveSheet()->SetCellValue('T' . $rowCount, $element['meter_number_per_machine']);
			$objPHPExcel->getActiveSheet()->SetCellValue('U' . $rowCount, $element['water_glue_rate']);

			$objPHPExcel->getActiveSheet()->SetCellValue('V' . $rowCount, $element['so_cai_min']);
			$objPHPExcel->getActiveSheet()->SetCellValue('W' . $rowCount, $element['taffeta_satin']);
			$objPHPExcel->getActiveSheet()->SetCellValue('X' . $rowCount, $element['textile_size_number']);
			$objPHPExcel->getActiveSheet()->SetCellValue('Y' . $rowCount, $element['new_wire_number']);
			$objPHPExcel->getActiveSheet()->SetCellValue('Z' . $rowCount, $element['scrap_sonic']);

			$objPHPExcel->getActiveSheet()->SetCellValue('AA' . $rowCount, $element['remark_1']);
			$objPHPExcel->getActiveSheet()->SetCellValue('AB' . $rowCount, $element['remark_2']);
			$objPHPExcel->getActiveSheet()->SetCellValue('AC' . $rowCount, $element['remark_3']);
			$objPHPExcel->getActiveSheet()->SetCellValue('AD' . $rowCount, $element['updated_by']);
			$objPHPExcel->getActiveSheet()->SetCellValue('AE' . $rowCount, $element['updated_date']);
		} // for


		/** ========MATERIAL================================================================================= */
		// Add new sheet
		$objPHPExcel->createSheet();

		// Add some data
		$objPHPExcel->setActiveSheetIndex(1);

		// active and set title
		$objPHPExcel->getActiveSheet()->setTitle('Material');

		// set Header, width
		$columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');

		// columns
		$header2 = array(
			'tt', 'machine_type', 'internal_item', 'length_btp', 'material_code', 'material_type', 'mat_do', 'so_pick', 'thu_tu'
		);

		$id = 0;
		foreach ($header2 as $header) {
			for ($index = $id; $index < count($header2); $index++) {
				// width
				$objPHPExcel->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(20);

				// headers
				$objPHPExcel->getActiveSheet()->setCellValue($columns[$index] . '1', $header);

				$id++;
				break;
			}
		}

		// Font
		$objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getFont()->setBold(true)->setName('Arial')->setSize(10);
		$objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('3399ff');
		$objPHPExcel->getActiveSheet()->getStyle('A:I')->getFont()->setName('Arial')->setSize(10);

		$index = 0;
		$rowCount = 1;
		$supplyData = $this->supply->read();

		foreach ($supplyData as $element) {

			$rowCount++;
			$index++;

			$objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, $index);

			$objPHPExcel->getActiveSheet()->SetCellValue('B' . $rowCount, trim($element['machine_type']));
			$objPHPExcel->getActiveSheet()->SetCellValue('C' . $rowCount, trim($element['internal_item']));
			$objPHPExcel->getActiveSheet()->SetCellValue('D' . $rowCount, trim($element['length_btp']));
			$objPHPExcel->getActiveSheet()->SetCellValue('E' . $rowCount, trim($element['code_name']));
			$objPHPExcel->getActiveSheet()->SetCellValue('F' . $rowCount, trim($element['code_type']));

			$objPHPExcel->getActiveSheet()->SetCellValue('G' . $rowCount, trim($element['density']));
			$objPHPExcel->getActiveSheet()->SetCellValue('H' . $rowCount, trim($element['pick_number']));
			$objPHPExcel->getActiveSheet()->SetCellValue('I' . $rowCount, trim($element['order']));
		} // for


		/** =======PROCESS================================================================================== */

		// Add new sheet
		$objPHPExcel->createSheet();

		// Add some data
		$objPHPExcel->setActiveSheetIndex(2);

		// active and set title
		$objPHPExcel->getActiveSheet()->setTitle('Process');

		// set Header, width
		$columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M');

		// columns
		$header3 = array(
			'tt', 'machine_type', 'internal_item', 'length_btp', 'det', 'xe_sonic', 'qua_ho', 'qua_nuoc', 'noi_dau', 'dan_keo', 'cat_gap', 'cat_laser', 'dong_goi'
		);

		$id = 0;
		foreach ($header3 as $header) {
			for ($index = $id; $index < count($header3); $index++) {
				// width
				$objPHPExcel->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(20);

				// headers
				$objPHPExcel->getActiveSheet()->setCellValue($columns[$index] . '1', $header);

				$id++;
				break;
			}
		}

		// Font
		$objPHPExcel->getActiveSheet()->getStyle('A1:M1')->getFont()->setBold(true)->setName('Arial')->setSize(10);
		$objPHPExcel->getActiveSheet()->getStyle('A1:M1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('3399ff');
		$objPHPExcel->getActiveSheet()->getStyle('A:M')->getFont()->setName('Arial')->setSize(10);

		$index = 0;
		$rowCount = 1;

		// execute process data
		$internal_item_tmp = '';
		$length_btp_tmp = '';
		foreach ($processData as $process) {

			$machine_type = trim($process['machine_type']);
			$internal_item = trim($process['internal_item']);
			$length_btp = trim($process['length_btp']);

			if ($internal_item == $internal_item_tmp && $length_btp == $length_btp_tmp) {
				continue;
			} else {

				$det = $xe_sonic = $qua_ho = $qua_nuoc = $noi_dau = $dan_keo = $cat_gap = $cat_laser = $dong_goi = 0;

				$process_item = $this->process->readSingle(array('internal_item' => $internal_item));

				foreach ($process_item as $check) {
					// 9 process
					if ($check['process_code'] == 'wv_01') {
						$det = $check['status'];
					} else if ($check['process_code'] == 'wv_02') {
						$xe_sonic = $check['status'];
					} else if ($check['process_code'] == 'wv_03') {
						$qua_ho = $check['status'];
					} else if ($check['process_code'] == 'wv_04') {
						$qua_nuoc = $check['status'];
					} else if ($check['process_code'] == 'wv_05') {
						$noi_dau = $check['status'];
					} else if ($check['process_code'] == 'wv_06') {
						$dan_keo = $check['status'];
					} else if ($check['process_code'] == 'wv_07') {
						$cat_gap = $check['status'];
					} else if ($check['process_code'] == 'wv_08') {
						$cat_laser = $check['status'];
					} else if ($check['process_code'] == 'wv_09') {
						$dong_goi = $check['status'];
					}
				}

				// set 1 line
				$processExport[] = array(
					'machine_type' => $machine_type,
					'internal_item' => $internal_item,
					'length_btp' => $length_btp,
					'det' => $det,
					'xe_sonic' => $xe_sonic,
					'qua_ho' => $qua_ho,
					'qua_nuoc' => $qua_nuoc,
					'noi_dau' => $noi_dau,
					'dan_keo' => $dan_keo,
					'cat_gap' => $cat_gap,
					'cat_laser' => $cat_laser,
					'dong_goi' => $dong_goi
				);
			}

			// check item
			$internal_item_tmp = $internal_item;
			$length_btp = $length_btp_tmp;
		}

		foreach ($processExport as $element) {

			$rowCount++;
			$index++;

			$objPHPExcel->getActiveSheet()->SetCellValue('A' . $rowCount, $index);

			$objPHPExcel->getActiveSheet()->SetCellValue('B' . $rowCount, $element['machine_type']);
			$objPHPExcel->getActiveSheet()->SetCellValue('C' . $rowCount, $element['internal_item']);
			$objPHPExcel->getActiveSheet()->SetCellValue('D' . $rowCount, $element['length_btp']);
			$objPHPExcel->getActiveSheet()->SetCellValue('E' . $rowCount, $element['det']);
			$objPHPExcel->getActiveSheet()->SetCellValue('F' . $rowCount, $element['xe_sonic']);

			$objPHPExcel->getActiveSheet()->SetCellValue('G' . $rowCount, $element['qua_ho']);
			$objPHPExcel->getActiveSheet()->SetCellValue('H' . $rowCount, $element['qua_nuoc']);
			$objPHPExcel->getActiveSheet()->SetCellValue('I' . $rowCount, $element['noi_dau']);
			$objPHPExcel->getActiveSheet()->SetCellValue('J' . $rowCount, $element['dan_keo']);
			$objPHPExcel->getActiveSheet()->SetCellValue('K' . $rowCount, $element['cat_gap']);

			$objPHPExcel->getActiveSheet()->SetCellValue('L' . $rowCount, $element['cat_laser']);
			$objPHPExcel->getActiveSheet()->SetCellValue('M' . $rowCount, $element['dong_goi']);
		} // for


		/* ========================= OUT PUT ==============================================================*/

		// set filename for excel file to be exported
		$filename = 'WOVEN_Master_File_' . date("Y_m_d__H_i_s");

		// header: generate excel file
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
		header('Cache-Control: max-age=0');
		// writer
		$writer = new Xlsx($objPHPExcel);
		$writer->save('php://output');
	}

	// report data (option 1: all data, option 2: distance )
	public function reports()
	{

		// load models
		$this->load->model('woven_po_save');
		$this->load->model('woven_po_soline_save');
		$this->load->model('woven_master_item_supply_save', 'supply_save');
		$this->load->model('woven_master_item_process_save', 'process_save');
		$this->load->model('common_size_save', 'size_save');
		// get distance times
		$fromDate = null !== $this->input->get('from_date') ? trim($this->input->get('from_date')) : '';
		$toDate = null !== $this->input->get('to_date') ? trim($this->input->get('to_date')) : '';

		// create
		$spreadsheet = new Spreadsheet();

		// set the names of header cells
		// set Header, width
		$columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO');

		if ($this->woven_po_save->countAll() > 0 && $this->woven_po_soline_save->countAll() > 0) {

			/* ========================= PO DETAIL SHEET ==============================================================*/

			// Add new sheet
			$spreadsheet->createSheet();

			// Add some data
			$spreadsheet->setActiveSheetIndex(0);

			// active and set title
			$spreadsheet->getActiveSheet()->setTitle('Woven_PO');

			$header1 = array(
				'DATE', 'SO-Line', 'Item Code', 'Qty', 'Material 1', 'Qty Request 1', 'Material 2', 'Qty Request 2', 'Material 3', 'Qty Request 3',
				'Material 4', 'Qty Request 4', 'Material 5', 'Qty Request 5', 'Material 6', 'Qty Request 6', 'Material 7', 'Qty Request 7', 'Material 8', 'Qty Request 8',
				'Keo 1', 'Qty Can 1', 'Keo 2', 'Qty Can 2', 'No. Of Picks', 'No. Of Ribbon', 'Running Time', 'Label Length', 'Warp Yarn', 'Machine Type',
				'NO-JobJacket', 'No. Of Size', 'Parttern Number', 'Pcs/Ribbon', 'Cut Type', 'Warp Yarn (kg)', 'Cần Dệt', 'Type Machine', 'updated_by', 'updated_date', 'Order Type'
			);

			$id = 0;
			foreach ($header1 as $header) {
				for ($index = $id; $index < count($header1); $index++) {
					// width
					$spreadsheet->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(20);

					// headers
					$spreadsheet->getActiveSheet()->setCellValue($columns[$index] . '1', $header);

					$id++;
					break;
				}
			}


			// Font
			$spreadsheet->getActiveSheet()->getStyle('A1:AO1')->getFont()->setBold(true)->setName('Arial')->setSize(10);
			$spreadsheet->getActiveSheet()->getStyle('A1:AO1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('3399ff');
			$spreadsheet->getActiveSheet()->getStyle('A:AO')->getFont()->setName('Arial')->setSize(10);


			// get data
			if (!empty($fromDate) && !empty($toDate)) {
				$poSave = $this->woven_po_save->readDistance($fromDate, $toDate);
			} else {
				$poSave = $this->woven_po_save->readReport();
			}

			// set data
			$rowCount = 1;
			$solineData = array();
			$glueData = array();

			foreach ($poSave as $element) {

				$rowCount++;

				$po_no = trim($element['po_no']);
				// $po_no_suffix = trim(strtoupper($element['po_no_suffix']) );
				// $suffix_arr = array('CCR', 'FOD');
				// $po_no_show = (in_array($po_no_suffix, $suffix_arr) ) ? ($po_no . '-' . $po_no_suffix) : $po_no; 

				$po_no_show = $this->getPOShow($po_no, $element['po_no_suffix']);

				$machine_type = trim($element['machine_type']);
				$internal_item = trim($element['internal_item']);
				$length_btp = (int)trim($element['length_btp']);

				$qty_total = $element['qty_total'];

				$folding_cut_type = trim(strtoupper($element['folding_cut_type']));

				// handle cut type
				$cut_type = '';
				$processCheck = $this->process_save->readSingle(array('po_no' => $po_no));
				if (empty($processCheck)) {
					$cut_type = trim($element['cut_type']);
				} else {
					// $processCutType = array('wv_07', 'wv_02', 'wv_03', 'wv_04', 'wv_06', 'wv_08' );
					$processCutType = array('CG', 'XS', 'QH', 'QN', 'DK', 'LS');
					foreach ($processCutType as $cutType) {
						foreach ($processCheck as $process) {
							if ($process['process_code'] == $cutType && $process['status'] == 1) {
								if ($process['process_code'] == 'CG') {
									$cut_type .= $folding_cut_type;
								} else if ($process['process_code'] == 'XS') { // sonic
									$cut_type .= "/Sonic";
								} else if ($process['process_code'] == 'QH') { // qua hồ
									$cut_type .= "/Coating";
								} else if ($process['process_code'] == 'QN') { // qua nước
									$cut_type .= "/Qua Nuoc";
								} else if ($process['process_code'] == 'DK') { // dán keo
									$cut_type .= "/Dan Keo";
								} else if ($process['process_code'] == 'LS') { // Cắt Laser
									$cut_type .= "/Laser";
								}
							}
						}
					}

					$cut_type = ltrim($cut_type, "/");
				}


				// get soline data
				$soline_item = $this->woven_po_soline_save->readPoSOLines($po_no);
				

				// get data for SOLine sheet
				if (!empty($soline_item) ) {
					
					$count_size = (int)$soline_item[0]['count_size'];

					$running_time_total = (float)$soline_item[0]['running_time'];

					$countS = count($soline_item);
					foreach ($soline_item as $keyS => $sol) {
						$so_line = trim($sol['so_line']);
						$qty_of_line = (int)$sol['qty_of_line'];

						// Kiểm tra xem so_line = no hay không (trường hợp Buildstock)
						$so_line_show = ($so_line == $po_no) ? $po_no_show : $so_line;
						// get data array
						$solineData[] = array(
							'so_line' => $so_line_show,
							'internal_item' => $internal_item,
							'qty_of_line' => $qty_of_line,
							'po_no' => $po_no_show
						);

						if ($keyS == 0) {
							$rowCount = $rowCount;
						} else {
							$rowCount += 1;
						}


						// created PO date
						$spreadsheet->getActiveSheet()->SetCellValue('A' . $rowCount, $this->dateFormat($element['po_date']));
						// PO Number
						$spreadsheet->getActiveSheet()->SetCellValue('B' . $rowCount, $so_line_show);
						// Item
						$spreadsheet->getActiveSheet()->SetCellValue('C' . $rowCount, trim($internal_item));
						// PO qty total
						$spreadsheet->getActiveSheet()->SetCellValue('D' . $rowCount, $qty_of_line);

						// get supply
						$supplySave = $this->supply_save->readSingle(array('po_no' => $po_no));
						$materialColsCheck = array('E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'w', 'X');

						// set qty supply = 0;
						$supplyIdCheck = 1;
						while ($supplyIdCheck < count($materialColsCheck)) {
							// if(!isset($materialColsCheck[$supplyIdCheck]) ) break;
							// $next = $supplyIdCheck + 1;
							$spreadsheet->getActiveSheet()->SetCellValue($materialColsCheck[$supplyIdCheck] . $rowCount, 0);
							$supplyIdCheck += 2;
						}

						$materialCols = array('E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'w', 'X');
						$materialCols_2 = array('U', 'V', 'w', 'X');

						$supplyId = 0;
						foreach ($supplySave as $supply) {

							if (strtolower($supply['code_type']) == 'supply') {
								while ($supplyId < count($materialCols)) {
									if (!isset($materialCols[$supplyId])) break;
									$next = $supplyId + 1;
									// echo "Code: " . $materialCols[$key] . $rowCount . " --- Value: " . trim($supply['code_name']);
									// echo " --- Qty: " . $materialCols[$next] . $rowCount . " --- Value: " . $supply['need_horizontal_thread'] . "<br />";
									$need_horizontal_thread = $supply['need_horizontal_thread'];
									$nedd_horizontal = round((($need_horizontal_thread * (($qty_of_line / $qty_total) * 100)) / 100), 2);
									$spreadsheet->getActiveSheet()->SetCellValue($materialCols[$supplyId] . $rowCount, trim($supply['code_name']));
									$spreadsheet->getActiveSheet()->SetCellValue($materialCols[$next] . $rowCount, $nedd_horizontal);

									break;
								}
							} else {
								$glue_qty = ceil($element['target_total'] * $length_btp);
								$supplyId_2 = 0;
								while ($supplyId_2 < $materialCols_2) {

									if (!isset($materialCols_2[$supplyId_2])) break;
									$next = $supplyId_2 + 1;

									// Chia thêm 1000 để đồng bộ với đơn vị trên lệnh sản xuất
									$nedd_horizontal = round( (($glue_qty * ($qty_of_line / ($qty_total) * 100) ) / 100)/1000, 2 );

									$spreadsheet->getActiveSheet()->SetCellValue($materialCols_2[$supplyId_2] . $rowCount, trim($supply['code_name']));
									$spreadsheet->getActiveSheet()->SetCellValue($materialCols_2[$next] . $rowCount, $nedd_horizontal);
									array_splice($materialCols_2, 0, 2);
									break;
								}
							}

							$supplyId += 2;
						}


						// // running time
						// $running_time = round((($running_time_total * ($qty_of_line/$qty_total*100)) / 100), 2);
						// // target
						// $target_total = $element['target_total'];
						// $target_qty = round((($target_total * ($qty_of_line/$qty_total*100)) / 100), 2);
						// // so cai
						// $so_cai_total = $element['so_cai_total'];
						// $so_cai = round((($so_cai_total * ($qty_of_line/$qty_total*100)) / 100), 2);
						// running time
						$running_time = (!empty($sol['running_time']) || $sol['running_time'] !== 0) ? (float)$sol['running_time'] : 0;
						if ($running_time == 0) {
							$running_time = round((($running_time_total * ($qty_of_line / $qty_total * 100)) / 100), 2);
						}
						// target
						$target_qty = (!empty($sol['target_of_line']) || $sol['target_of_line'] !== 0)  ? (int)$sol['target_of_line'] : 0;
						if ($target_qty == 0) {
							$target_total = $element['target_total'];
							$target_qty = round((($target_total * ($qty_of_line / $qty_total * 100)) / 100), 2);
						}

						// so cai
						$so_cai = (!empty($sol['so_cai_of_line']) || $sol['so_cai_of_line'] !== 0) ? (int)$sol['so_cai_of_line'] : 0;
						if ($so_cai == 0) {
							$so_cai_total = $element['so_cai_total'];
							$so_cai = round((($so_cai_total * ($qty_of_line / $qty_total * 100)) / 100), 2);
						}

						// chỉ dọc cần
						$need_vertical_thread_number_total = $element['need_vertical_thread_number'];
						$need_vertical_thread_number = round((($need_vertical_thread_number_total * ($qty_of_line / $qty_total * 100)) / 100), 2);

						// pick number (total) / số pick
						$spreadsheet->getActiveSheet()->SetCellValue('Y' . $rowCount, $element['pick_number_total']);
						// số dây / ribbon
						$spreadsheet->getActiveSheet()->SetCellValue('Z' . $rowCount, $element['wire_number']);
						// running time / thời gian chạy máy
						$spreadsheet->getActiveSheet()->SetCellValue('AA' . $rowCount, $running_time);
						// length btp / chiều dài con nhãn bán thành phẩm
						$spreadsheet->getActiveSheet()->SetCellValue('AB' . $rowCount, (float)$element['length_btp']);
						// vertical thread type / loại chỉ dọc
						$spreadsheet->getActiveSheet()->SetCellValue('AC' . $rowCount, trim($element['vertical_thread_type']));
						// taffeta satin / loại taffeta hoặc satin
						$spreadsheet->getActiveSheet()->SetCellValue('AD' . $rowCount, trim($element['taffeta_satin']));
						// count size PO / số lượng size của đơn hàng
						$spreadsheet->getActiveSheet()->SetCellValue('AE' . $rowCount, $po_no_show); // xử lý hiển thị hậu tố cho PO_NO
						// count size PO / số lượng size của đơn hàng
						$spreadsheet->getActiveSheet()->SetCellValue('AF' . $rowCount, $count_size);
						// pattern / để trống
						$spreadsheet->getActiveSheet()->SetCellValue('AG' . $rowCount, '');
						// tổng số cái
						$spreadsheet->getActiveSheet()->SetCellValue('AH' . $rowCount, (int)$so_cai);
						// cut type / Loại cắt
						$spreadsheet->getActiveSheet()->SetCellValue('AI' . $rowCount, $cut_type);
						// need vertical thread number / số chỉ dọc cần (đơn vị kg)
						$spreadsheet->getActiveSheet()->SetCellValue('AJ' . $rowCount, $need_vertical_thread_number);
						// target total / toàn bộ target
						$spreadsheet->getActiveSheet()->SetCellValue('AK' . $rowCount, (int)$target_qty);
						// machine type / loại máy
						$spreadsheet->getActiveSheet()->SetCellValue('AL' . $rowCount, $element['machine_type']);
						// update by / date
						$spreadsheet->getActiveSheet()->SetCellValue('AM' . $rowCount, $element['updated_by']);
						$spreadsheet->getActiveSheet()->SetCellValue('AN' . $rowCount, $element['updated_date']);
						$order_type_show = strtoupper($element['type']);
						if ($order_type_show == 'COMMON') {
							$order_type_show = 'BATCHING';
						} else if ($order_type_show == 'NON_BATCHING') {
							$order_type_show = 'NON BATCHING';
						}
						$spreadsheet->getActiveSheet()->SetCellValue('AO' . $rowCount, $order_type_show);
					}
				}
			}

			/* ========================= SOLINE SHEET ==============================================================*/
			// Add new sheet
			$spreadsheet->createSheet();

			// Add some data
			$spreadsheet->setActiveSheetIndex(1);

			// active and set title
			$spreadsheet->getActiveSheet()->setTitle('SOLine');

			$header2 = array('SOL', 'Item Code', 'Qty', 'NO-JobJacket');

			$id = 0;
			foreach ($header2 as $header) {
				for ($index = $id; $index < count($header2); $index++) {
					// width
					$spreadsheet->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(20);

					// headers
					$spreadsheet->getActiveSheet()->setCellValue($columns[$index] . '1', $header);

					$id++;
					break;
				}
			}


			// Font
			$spreadsheet->getActiveSheet()->getStyle('A1:D1')->getFont()->setBold(true)->setName('Arial')->setSize(10);
			$spreadsheet->getActiveSheet()->getStyle('A:D')->getFont()->setName('Arial')->setSize(10);
			// $spreadsheet->getActiveSheet()->getStyle('B2')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
			$spreadsheet->getActiveSheet()->getStyle('A1:D1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('3399ff');

			// set data
			$rowCount = 1;
			$suffix_arr = array('CCR', 'FOD');

			foreach ($solineData as $element) {
				$rowCount++;

				// // get suffix
				// 	$po_no = $element['po_no'];
				// 	$poItem = $this->woven_po_save->readItem(array('po_no' => $po_no) );
				// 	$po_no_suffix = trim(strtoupper($poItem['po_no_suffix']) );
				// 	$po_no_show = (in_array($po_no_suffix, $suffix_arr) ) ? ($po_no . '-' . $po_no_suffix) : $po_no; 


				// so_line
				$spreadsheet->getActiveSheet()->SetCellValue('A' . $rowCount, trim($element['so_line']));
				// item
				$spreadsheet->getActiveSheet()->SetCellValue('B' . $rowCount, trim($element['internal_item']));
				// qty of line
				$spreadsheet->getActiveSheet()->SetCellValue('C' . $rowCount, $element['qty_of_line']);
				// PO Number
				$spreadsheet->getActiveSheet()->SetCellValue('D' . $rowCount, $element['po_no']);
			}
		}

		// print_r($spreadsheet->getActiveSheet()); exit();
		/* ========================= OUT PUT ==============================================================*/

		// set filename for excel file to be exported
		$filename = 'Woven_PO_Report_' . date("Y_m_d__H_i_s");

		// header: generate excel file
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
		header('Cache-Control: max-age=0');
		// writer
		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');
	}

	// create master item: include main master, supply (vat tu), process
	public function createMasterItem()
	{

		header("Content-Type: application/json");
		$this->_data['title'] = 'Add Master Item';

		$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

		// check empty
		if (empty($_POST)) {
			$results = array(
				'status' => false,
				'messagge' => 'Không có dữ liệu'
			);
			echo json_encode($results);
			exit();
		} else {

			// models
			$this->load->model('woven_master_item', 'wv_master_item');
			$this->load->model('woven_master_item_supply', 'supply');
			// $this->load->model('woven_master_item_process', 'process');

			// Get Master Data
			// 1 - 5
			$machine_type = isset($_POST['machine_type']) ? trim($_POST['machine_type']) : '';
			$internal_item = isset($_POST['internal_item']) ? trim(strtoupper($_POST['internal_item'])) : '';
			$length_btp = isset($_POST['length_btp']) ? trim($_POST['length_btp']) : '';
			$width_btp = isset($_POST['width_btp']) ? trim($_POST['width_btp']) : '';
			// decode: html_entity_decode($rbo,ENT_QUOTES)
			$rbo = isset($_POST['rbo']) ? htmlentities(trim($_POST['rbo']), ENT_QUOTES, 'UTF-8') : '';
			// 6 - 10
			// int
			$wire_number = isset($_POST['wire_number']) ? (int)$_POST['wire_number'] : 0;
			$vertical_thread_type = isset($_POST['vertical_thread_type']) ? trim($_POST['vertical_thread_type']) : '';
			$folding_cut_type = isset($_POST['folding_cut_type']) ? trim($_POST['folding_cut_type']) : '';
			$pattern = isset($_POST['pattern']) ? trim($_POST['pattern']) : '';
			$gear_density = isset($_POST['gear_density']) ? trim($_POST['gear_density']) : '';
			// 11 - 15
			// float
			$length_tp = isset($_POST['length_tp']) ? (float)$_POST['length_tp'] : '';
			// float
			$width_tp = isset($_POST['width_tp']) ? (float)$_POST['width_tp'] : '';
			// int
			$cbs = isset($_POST['cbs']) ? (int)$_POST['cbs'] : 0;
			// int
			$scrap = isset($_POST['scrap']) ? (int)$_POST['scrap'] : 0;
			$cut_type = isset($_POST['cut_type']) ? trim($_POST['cut_type']) : '';
			// 16 - 20
			$sawing_method = isset($_POST['sawing_method']) ? trim($_POST['sawing_method']) : '';
			// int
			$cw_specification = isset($_POST['cw_specification']) ? (int)$_POST['cw_specification'] : 0;
			$heat_weaving = isset($_POST['heat_weaving']) ? trim($_POST['heat_weaving']) : '';
			// float
			$meter_number_per_machine = isset($_POST['meter_number_per_machine']) ? (float)$_POST['meter_number_per_machine'] : 0;
			$water_glue_rate = isset($_POST['water_glue_rate']) ? trim($_POST['water_glue_rate']) : '';
			// 21 - 26

			// int
			$textile_size_number = isset($_POST['textile_size_number']) ? (int)$_POST['textile_size_number'] : 0;
			// int
			if ($length_btp <= 10) {
				$so_cai_min = ((1.5 * 1000) / $length_btp) * $textile_size_number;
			} else {
				$so_cai_min = 15;
			}

			// int
			$new_wire_number = isset($_POST['new_wire_number']) ? (int)$_POST['new_wire_number'] : 0;

			$remark_1 = isset($_POST['remark_1']) ? trim($_POST['remark_1']) : '';
			$remark_2 = isset($_POST['remark_2']) ? trim($_POST['remark_2']) : '';
			$remark_3 = isset($_POST['remark_3']) ? trim($_POST['remark_3']) : '';

			// process 
			$process = isset($_POST['process']) ? trim($_POST['process']) : '';

			// check item exist
			if ($this->wv_master_item->isAlreadyExist($machine_type, $internal_item, $length_btp)) {
				$results = array(
					"status" => false,
					"messagge" => "Item $internal_item đã tồn tại "
				);
				echo json_encode($results);
				exit();
			} else {

				// Lấy scrap sonic
				$scrap_sonic = (stripos($process, 'XS') !== false) ? 5 : 3;

				// set insert data
				$master_item_insert = array(
					// 1 - 5
					'machine_type' => $machine_type,
					'internal_item' => $internal_item,
					'length_btp' => $length_btp,
					'width_btp' => $width_btp,
					'rbo' => $rbo,
					// 6 - 10
					'wire_number' => $wire_number,
					'vertical_thread_type' => $vertical_thread_type,
					'folding_cut_type' => $folding_cut_type,
					'pattern' => $pattern,
					'gear_density' => $gear_density,
					// 11 - 15
					'length_tp' => $length_tp,
					'width_tp' => $width_tp,
					'cbs' => $cbs,
					'scrap' => $scrap,
					'cut_type' => $cut_type,
					// 16 - 20
					'sawing_method' => $sawing_method,
					'cw_specification' => $cw_specification,
					'heat_weaving' => $heat_weaving,
					'meter_number_per_machine' => $meter_number_per_machine,
					'water_glue_rate' => $water_glue_rate,
					// 21 - 28
					'so_cai_min' => $so_cai_min,
					'textile_size_number' => $textile_size_number,
					'new_wire_number' => $new_wire_number,
					// 'pick_number_total' => $pick_number_total,
					'remark_1' => $remark_1,
					'remark_2' => $remark_2,
					'remark_3' => $remark_3,
					'updated_by' => $updated_by,

					// scrap sonic
					'scrap_sonic' => $scrap_sonic,
					// process
					'process' => $process

				);

				// insert
				$masterItemResult = $this->wv_master_item->insert($master_item_insert);

				// check
				if ($masterItemResult == FALSE) {
					$results = array(
						"status" => false,
						"messagge" => "Lưu dữ liệu master data lỗi "
					);
					echo json_encode($results);
					exit();
				} else {
					// material (supply)
					$supply_count = 8;
					$supplyData = array();
					for ($supplyId = 1; $supplyId <= $supply_count; $supplyId++) {
						// uppercase
						$code_name = isset($_POST['supply_code_' . $supplyId]) ? trim(strtoupper($_POST['supply_code_' . $supplyId])) : '';
						$density = isset($_POST['density_' . $supplyId]) ? trim($_POST['density_' . $supplyId]) : '';
						$pick_number = isset($_POST['pick_number_' . $supplyId]) ? trim($_POST['pick_number_' . $supplyId]) : '';
						$code_type = 'supply';

						// check
						if (empty($code_name)) {
							continue;
						} else {
							if (empty($density) || empty($pick_number)) {
								// delete
								$this->wv_master_item->delete($machine_type, $internal_item, $length_btp);
								// error results
								$results = array(
									"status" => false,
									"messagge" => " Mật độ và số pick không được trống "
								);
								echo json_encode($results);
								exit();
							} else {
								// check exist
								if ($this->supply->isAlreadyExist($internal_item, $length_btp, $code_name, $supplyId)) {
									// delete
									$this->wv_master_item->delete($machine_type, $internal_item, $length_btp);
									// return error
									$results = array(
										"status" => false,
										"messagge" => "Error. Vật tư: $machine_type - $internal_item - $code_name đã tồn tại "
									);
									echo json_encode($results);
									exit();
								}

								// set supply data insert
								$supplyData[] = array(
									'machine_type' => '',
									'internal_item' => $internal_item,
									'length_btp' => $length_btp,
									'code_name' => $code_name,
									'code_type' => $code_type,
									'density' => $density,
									'pick_number' => (int)$pick_number,
									'order' => $supplyId
								);
							}
						}
					}

					// start - glue.
					$supply_count = 2;
					for ($supplyId = 1; $supplyId <= $supply_count; $supplyId++) {
						$code_name = isset($_POST['glue_' . $supplyId]) ? trim(strtoupper($_POST['glue_' . $supplyId])) : '';
						$density = '';
						$pick_number = 0;
						$code_type = 'glue';

						if (empty($code_name)) {
							continue;
						}

						// check exist
						if ($this->suppy->isAlreadyExist($internal_item, $length_btp, $code_name)) {
							// delete
							$this->wv_master_item->delete($machine_type, $internal_item, $length_btp);
							// return error
							$results = array(
								"status" => false,
								"messagge" => "Error. Vật tư: $internal_item - $length_btp - $code_name đã tồn tại "
							);
							echo json_encode($results);
							exit();
						}
						// set supply data insert
						$supplyData[] = array(
							'machine_type' => '', // machine type is empty
							'internal_item' => $internal_item,
							'length_btp' => $length_btp,
							'code_name' => $code_name,
							'code_type' => $code_type,
							'density' => $density,
							'pick_number' => (int)$pick_number,
							'order' => $supplyId
						);
					}
					// end - glue.

					// save supply data
					if (!empty($supplyData)) {

						$supplyResults = $this->supply->insertBatch($this->supply->setInsertBatch($supplyData));

						// check supply
						if ($supplyResults == FALSE) {
							// Nếu save không thành công, xóa bỏ dữ liệu đã save trước đó
							// delete
							$this->wv_master_item->delete($machine_type, $internal_item, $length_btp);

							// return error
							$results = array(
								"status" => false,
								"messagge" => "Lưu dữ liệu Vật tư lỗi "
							);
							echo json_encode($results);
							exit();
						} else {

							// // Lấy scrap sonic
							// 	$scrap_sonic = (stripos($process, 'XS') !== false ) ? 5 : 3;
							// 	$this->wv_master_item->update(
							// 		array('scrap_sonic' => $scrap_sonic), 
							// 		array('machine_type' => $machine_type, 'internal_item' => $internal_item, 'length_btp' => $length_btp) 
							// 	);

							// success
							$results = array(
								"status" => true,
								"messagge" => "Lưu dữ liệu thành công. Chọn OK để load lại trang "
							);
							echo json_encode($results);
							exit();
						}
					}
				}
			}
		}
	}

	// update Master Data (main master )
	public function updateMainMaster()
	{

		header("Content-Type: application/json");
		$this->_data['title'] = 'Update Master Item';

		$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

		// check empty
		if (empty($_POST)) {
			$results = array(
				"status" => false,
				"messagge" => "Không có dữ liệu"
			);
		} else {
			// models
			$this->load->model('woven_master_item', 'wv_master_item');
			$this->load->model('woven_master_item_supply', 'supply');
			// $this->load->model('woven_master_item_process', 'process');
			// get data
			$machine_type = isset($_POST['machine_type']) ? trim(strtoupper($_POST['machine_type'])) : '';
			$internal_item = isset($_POST['internal_item']) ? trim(strtoupper($_POST['internal_item'])) : '';
			$length_btp = isset($_POST['length_btp']) ? trim($_POST['length_btp']) : '';
			$width_btp = isset($_POST['width_btp']) ? trim($_POST['width_btp']) : '';
			$rbo = isset($_POST['rbo']) ? trim($_POST['rbo']) : '';
			$wire_number = isset($_POST['wire_number']) ? (int)$_POST['wire_number'] : '';

			$vertical_thread_type = isset($_POST['vertical_thread_type']) ? trim($_POST['vertical_thread_type']) : '';
			$folding_cut_type = isset($_POST['folding_cut_type']) ? trim($_POST['folding_cut_type']) : '';
			$pattern = isset($_POST['pattern']) ? trim($_POST['pattern']) : '';
			$gear_density = isset($_POST['gear_density']) ? trim($_POST['gear_density']) : '';
			$length_tp = isset($_POST['length_tp']) ? (float)$_POST['length_tp'] : '';

			$width_tp = isset($_POST['width_tp']) ? (float)$_POST['width_tp'] : '';
			$cbs = isset($_POST['cbs']) ? (int)$_POST['cbs'] : '';
			$scrap = isset($_POST['scrap']) ? (int)$_POST['scrap'] : '';
			$cut_type = isset($_POST['cut_type']) ? trim($_POST['cut_type']) : '';
			$sawing_method = isset($_POST['sawing_method']) ? trim($_POST['sawing_method']) : '';

			$cw_specification = isset($_POST['sawing_method']) ? (int)$_POST['cw_specification'] : 0;
			$heat_weaving = isset($_POST['heat_weaving']) ? trim($_POST['heat_weaving']) : '';
			$meter_number_per_machine = isset($_POST['meter_number_per_machine']) ? (float)$_POST['meter_number_per_machine'] : '';
			$water_glue_rate = isset($_POST['water_glue_rate']) ? trim($_POST['water_glue_rate']) : '';
			$so_cai_min = isset($_POST['so_cai_min']) ? (int)$_POST['so_cai_min'] : '';

			$taffeta_satin = isset($_POST['taffeta_satin']) ? trim($_POST['taffeta_satin']) : '';
			$textile_size_number = isset($_POST['textile_size_number']) ? (int)$_POST['textile_size_number'] : '';
			$new_wire_number = isset($_POST['new_wire_number']) ? (int)$_POST['new_wire_number'] : '';
			$scrap_sonic = isset($_POST['scrap_sonic']) ? (int)$_POST['scrap_sonic'] : '';

			// $pick_number_total = isset($_POST['pick_number_total']) ? (int)$_POST['pick_number_total'] : '';

			$remark_1 = isset($_POST['remark_1']) ? trim($_POST['remark_1']) : '';
			$remark_2 = isset($_POST['remark_2']) ? trim($_POST['remark_2']) : '';
			$remark_3 = isset($_POST['remark_3']) ? trim($_POST['remark_3']) : '';

			$special_item_remark = isset($_POST['special_item_remark']) ? trim($_POST['special_item_remark']) : '';

			// process
			$process = isset($_POST['process']) ? trim($_POST['process']) : '';

			// check
			if (!$this->wv_master_item->isAlreadyExist($machine_type, $internal_item, $length_btp)) {
				$results = array(
					"status" => false,
					"message" => "Item: $internal_item chưa tồn tại. Vui lòng thêm Item mới "
				);
			} else {
				$where = array(
					'machine_type' => $machine_type,
					'internal_item' => $internal_item,
					'length_btp' => $length_btp
				);

				$updateData = array(
					'width_btp' => $width_btp,
					'rbo' => $rbo,

					'wire_number' => $wire_number,
					'vertical_thread_type' => $vertical_thread_type,
					'folding_cut_type' => $folding_cut_type,
					'pattern' => $pattern,
					'gear_density' => $gear_density,

					'length_tp' => $length_tp,
					'width_tp' => $width_tp,
					'cbs' => $cbs,
					'scrap' => $scrap,
					'cut_type' => $cut_type,

					'sawing_method' => $sawing_method,
					'cw_specification' => $cw_specification,
					'heat_weaving' => $heat_weaving,
					'meter_number_per_machine' => $meter_number_per_machine,
					'water_glue_rate' => $water_glue_rate,

					'so_cai_min' => $so_cai_min,
					'taffeta_satin' => $taffeta_satin,
					'textile_size_number' => $textile_size_number,
					'new_wire_number' => $new_wire_number,
					'scrap_sonic' => $scrap_sonic,

					// 'pick_number_total' => $pick_number_total,

					'remark_1' => $remark_1,
					'remark_2' => $remark_2,
					'remark_3' => $remark_3,
					'special_item_remark' => $special_item_remark,
					'updated_by' => $updated_by,
					'updated_date' => date('Y-m-d H:i:s'),

					// process
					'process' => $process


				);

				$check = $this->wv_master_item->update($updateData, $where);
				if ($check == FALSE) {
					$results = array(
						"status" => false,
						"message" => "Cập nhật Item: $internal_item lỗi "
					);
				} else {

					$results = array(
						"status" => true,
						"message" => "Cập nhật Item: $internal_item thành công."
					);
				}
			}
		}

		echo json_encode($results);
		exit();
	}

	// update Master Data (Supply - vat tu )
	public function updateMainMasterSupply()
	{
		// dang lam
		header("Content-Type: application/json");
		$this->_data['title'] = 'Update Master Data (Supply)';

		// check empty
		if (empty($_POST)) {
			$results = array(
				"status" => false,
				"messagge" => "Update Data Post is Empty"
			);
		} else {
			// models
			$this->load->model('woven_master_item_supply', 'supply');

			// get data
			// get machine type is empty.
			$machine_type = '';
			$internal_item = isset($_POST['internal_item']) ? trim(strtoupper($_POST['internal_item'])) : '';
			$length_btp = isset($_POST['length_btp']) ? trim($_POST['length_btp']) : '';
			$code_name = isset($_POST['code_name']) ? trim($_POST['code_name']) : '';
			$code_type = isset($_POST['code_type']) ? trim($_POST['code_type']) : '';
			$density = isset($_POST['density']) ? trim($_POST['density']) : '';

			$pick_number = isset($_POST['pick_number']) ? (int)$_POST['pick_number'] : '';
			$order = isset($_POST['order']) ? trim($_POST['order']) : '';

			// check
			if (!$this->supply->isAlreadyExist($internal_item, $length_btp, $code_name, $order)) {
				$results = array(
					"status" => false,
					"message" => "Item: $internal_item is not exist. Add to the master Item, Please "
				);
			} else {
				$where = array(
					'internal_item' => $internal_item,
					'length_btp' => $length_btp,
					'code_name' => $code_name,
					'order' => $order
				);

				$updateData = array(
					'machine_type' => $machine_type,
					'code_type' => $code_type,
					'density' => $density,
					'pick_number' => $pick_number
				);

				$check = $this->supply->update($updateData, $where);
				if ($check == FALSE) {
					$results = array(
						"status" => false,
						"message" => "Update Item: $internal_item Error "
					);
				} else {
					$results = array(
						"status" => true,
						"messagge" => "Updated Item: $internal_item Success (Material)."
					);
				}
			}
		}

		echo json_encode($results);
		exit();
	}

	// update Master Data (Process) -- 2021.09.29 Không còn sử dụng theo cách mới
	public function updateMainMasterProcess()
	{
		// dang lam
		header("Content-Type: application/json");
		$this->_data['title'] = 'Update Master Data (Process)';

		// check empty
		if (empty($_POST)) {
			$results = array(
				"status" => false,
				"messagge" => "Update Data Post is Empty"
			);
		} else {

			// $results = array(
			// 	"status" => true,
			// 	"messagge" => $_POST
			// );
			// echo json_encode($results); exit();

			// models
			$this->load->model('woven_master_item_process', 'process');
			$this->load->model('woven_master_item', 'wv_master_item');
			// get data
			// get machine type is empty.
			$machine_type = isset($_POST['machine_type']) ? trim(strtoupper($_POST['machine_type'])) : '';
			$internal_item = isset($_POST['internal_item']) ? trim(strtoupper($_POST['internal_item'])) : '';
			$length_btp = isset($_POST['length_btp']) ? trim($_POST['length_btp']) : '';

			$processDataCutType = array();
			$countProcess = 9;
			for ($index = 1; $index <= $countProcess; $index++) {

				$process_code = 'wv_0' . $index;
				$status = isset($_POST[$process_code]) ? trim($_POST[$process_code]) : 0;
				// check
				if (!$this->process->isAlreadyExist($internal_item, $length_btp, $process_code)) {
					$results = array(
						"status" => false,
						"message" => "Item: $internal_item is not exist. Add to the master Item, Please "
					);
					echo json_encode($results);
					exit();
				} else {

					$processDataCutType[] = array(
						'internal_item' => $internal_item,
						'length_btp' => $length_btp,
						'process_code' => $process_code,
						'status' => $status
					);

					$where = array('internal_item' => $internal_item, 'length_btp' => $length_btp, 'process_code' => $process_code);
					$updateData = array('machine_type' => $machine_type, 'status' => $status);

					$check = $this->process->update($updateData, $where);
					if ($check == FALSE) {
						$results = array(
							"status" => false,
							"message" => "Update Item: $internal_item Error "
						);
						echo json_encode($results);
						exit();
					}
				}
			}

			// get cut_type
			$cut_type = '';
			$whereM = array('internal_item' => $internal_item, 'length_btp' => $length_btp);
			$machineItem = $this->wv_master_item->readItem($whereM);
			$folding_cut_type = !empty($machineItem) ? $machineItem['folding_cut_type'] : '';

			if (empty($processDataCutType)) {
				$cut_type = !empty($machineItem) ? $machineItem['cut_type'] : '';
			} else {
				// $processCutType = array('wv_07', 'wv_02', 'wv_03', 'wv_04', 'wv_06', 'wv_08' );
				$processCutType = array('CG', 'XS', 'QH', 'QN', 'DK', 'LS');
				foreach ($processCutType as $cutType) {
					foreach ($processDataCutType as $process) {
						if ($process['process_code'] == $cutType && $process['status'] == 1) {
							if ($process['process_code'] == 'CG') {
								$cut_type = $folding_cut_type;
							} else if ($process['process_code'] == 'XS') { // sonic
								$cut_type .= "/Sonic";
							} else if ($process['process_code'] == 'QH') { // qua hồ
								$cut_type .= "/Coating";
							} else if ($process['process_code'] == 'QN') { // qua nước
								$cut_type .= "/Qua Nuoc";
							} else if ($process['process_code'] == 'DK') { // dán keo
								$cut_type .= "/Dan Keo";
							} else if ($process['process_code'] == 'LS') { // Cắt Laser
								$cut_type .= "/Laser";
							}
						}
					}
				}

				$cut_type = ltrim($cut_type, "/");
			}

			// update cut type
			$this->wv_master_item->update(array('cut_type' => $cut_type), $whereM);

			// result
			$results = array(
				"status" => true,
				"messagge" => "Updated Item: $internal_item Success (Process). $process_code - $status "
			);
			echo json_encode($results);
			exit();
		}
	}

	// delete master data (main master or supply (vật tư) or process )
	public function deleteMasterData()
	{
		// data POST
		$data = isset($_POST["data"]) ? $_POST["data"] : '';
		$data = json_decode($data, true);
		// check empty
		if (empty($data)) {
			$results = array(
				"status" => false,
				"messagge" => "Data Post is Empty"
			);
		} else {

			$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

			// get del type, xác định main master data, supply (vật tư) hay process muốn xóa
			$del_type = isset($data['del_type']) ? trim($data['del_type']) : '';

			if ($del_type == 'mainMaster') {
				// models
				$this->load->model('woven_master_item', 'wv_master_item');
				$this->load->model('woven_master_item_backup', 'wv_master_item_backup');

				$machine_type = isset($data['machine_type']) ? trim(strtoupper($data['machine_type'])) : '';
				$internal_item = isset($data['internal_item']) ? trim(strtoupper($data['internal_item'])) : '';
				$length_btp = isset($data['length_btp']) ? trim($data['length_btp']) : '';

				// check
				if (!$this->wv_master_item->isAlreadyExist($machine_type, $internal_item, $length_btp)) {
					$results = array(
						"status" => false,
						"message" => "Error: $machine_type - $internal_item - $length_btp chưa tồn tại. "
					);
				} else {
					$array = array('machine_type' => $machine_type, 'internal_item' => $internal_item, 'length_btp' => $length_btp,);
					// get data to backup before delete
					$backupData = $this->wv_master_item->readItem($array);

					$result = $this->wv_master_item->delete($machine_type, $internal_item, $length_btp);
					if ($result == TRUE) {
						// save backup
						unset($backupData['updated_date']); // delete updated date (to update new day now)
						$backupData['updated_by'] = $updated_by; // add update by to insert data
						$this->wv_master_item_backup->insert($backupData); // insert
						// result
						$results = array(
							"status" => true,
							"message" => "Delete: $machine_type - $internal_item - $length_btp success"
						);
					} else {
						$results = array(
							"status" => false,
							"message" => "Delete: $machine_type - $internal_item - $length_btp Error "
						);
					}
				}
			} else if ($del_type == 'supply') {

				// models
				$this->load->model('woven_master_item_supply', 'supply');
				$this->load->model('woven_master_item_supply_backup', 'supply_backup');
				// get data
				$internal_item = isset($data['internal_item']) ? trim(strtoupper($data['internal_item'])) : '';
				$length_btp = isset($data['length_btp']) ? trim($data['length_btp']) : '';
				$code_name = isset($data['code_name']) ? trim(strtoupper($data['code_name'])) : '';
				$order = isset($data['order']) ? trim(strtoupper($data['order'])) : '';

				// check
				if (!$this->supply->isAlreadyExist($internal_item, $length_btp, $code_name, $order)) {
					$results = array(
						"status" => false,
						"message" => "Error: $internal_item - $length_btp - $code_name - $order chưa tồn tại. "
					);
				} else {
					$array = array('internal_item' => $internal_item, 'length_btp' => $length_btp, 'code_name' => $code_name, 'order' => $order);
					// get data to backup before delete
					$backupData = $this->supply->readItem($array);

					$result = $this->supply->delete($internal_item, $length_btp, $code_name, $order);
					if ($result == TRUE) {
						// save backup
						$backupData['updated_by'] = $updated_by; // add update by to insert data
						$this->supply_backup->insert($backupData); // insert
						// result
						$results = array(
							"status" => true,
							"message" => "Delete: $internal_item - $length_btp - $code_name - $order success"
						);
					} else {
						$results = array(
							"status" => false,
							"message" => "Delete: $internal_item - $length_btp - $code_name - $order Error "
						);
					}
				}
			} else if ($del_type == 'process') {
				// models
				$this->load->model('woven_master_item_process', 'process');
				$this->load->model('woven_master_item_process_backup', 'process_backup');
				// get data
				$internal_item = isset($data['internal_item']) ? trim(strtoupper($data['internal_item'])) : '';
				$length_btp = isset($data['length_btp']) ? trim($data['length_btp']) : '';

				// check
				if (!$this->process->checkMasterItem(array('internal_item' => $internal_item, 'length_btp' => $length_btp))) {
					$results = array(
						"status" => false,
						"message" => "Error: $internal_item - $length_btp chưa tồn tại. "
					);
				} else {
					$array = array('internal_item' => $internal_item, 'length_btp' => $length_btp);
					// get data to backup before delete
					$backupData = $this->process->readSingle($array);

					$result = $this->process->deleteItemLength($internal_item, $length_btp);
					if ($result == TRUE) {
						// save backup
						for ($i = 0; $i < 9; $i++) {
							$backupData[$i]['updated_by'] = $updated_by;
						}
						$this->process_backup->insertBatch($this->process_backup->setInsertBatch($backupData)); // insert
						// result
						$results = array(
							"status" => true,
							"message" => "Delete: $internal_item - $length_btp success"
						);
					} else {
						$results = array(
							"status" => false,
							"message" => "Delete: $internal_item - $length_btp Error "
						);
					}
				}
			}
		}

		// result
		echo json_encode($results);
		exit();
	}

	// download master data sample file
	public function downloadSampleFile()
	{

		// Add new sheet
		$spreadsheet = new Spreadsheet();

		/** =========MAIN_MASTER================================================================================ */
		// Add new sheet
		$spreadsheet->createSheet();

		// Add some data
		$spreadsheet->setActiveSheetIndex(0);

		// active and set title
		$spreadsheet->getActiveSheet()->setTitle('Main_Master');

		// set Header, width
		$columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF');

		// columns
		$header1 = array(
			'tt', 'machine_type', 'internal_item', 'length_btp', 'width_btp', 'rbo', 'so_day', 'loai_chi_doc', 'loai_cat_gap', 'pattern',
			'mat_do_banh_rang', 'length_tp', 'width_tp', 'cbs', 'scrap', 'cut_type', 'phuong_phap_xe', 'tskt_cw', 'nhiet_det', 'so_met_tung_may',
			'ti_le_qua_ho_nuoc', 'so_cai_min', 'taffeta_satin', 'so_kho', 'so_day_moi', 'scrap_sonic', 'remark_1', 'remark_2', 'remark_3', 'updated_by', 'updated_date'
		);

		$id = 0;
		foreach ($header1 as $header) {
			for ($index = $id; $index < count($header1); $index++) {
				// width
				if ($index == 0) $spreadsheet->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(7);
				else $spreadsheet->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(20);

				// headers
				$spreadsheet->getActiveSheet()->setCellValue($columns[$index] . '1', $header);

				$id++;
				break;
			}
		}


		// Font
		$spreadsheet->getActiveSheet()->getStyle('A1:AE1')->getFont()->setBold(true)->setName('Arial')->setSize(10);
		$spreadsheet->getActiveSheet()->getStyle('A1:AE1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('3399ff');
		$spreadsheet->getActiveSheet()->getStyle('A:AE')->getFont()->setName('Arial')->setSize(10);

		/** ========MATERIAL================================================================================= */
		// Add new sheet
		$spreadsheet->createSheet();

		// Add some data
		$spreadsheet->setActiveSheetIndex(1);

		// active and set title
		$spreadsheet->getActiveSheet()->setTitle('Material');

		// set Header, width
		$columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');

		// columns
		$header2 = array(
			'tt', 'machine_type', 'internal_item', 'length_btp', 'material_code', 'material_type', 'mat_do', 'so_pick', 'thu_tu'
		);

		$id = 0;
		foreach ($header2 as $header) {
			for ($index = $id; $index < count($header2); $index++) {
				// width
				if ($index == 0) $spreadsheet->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(7);
				else $spreadsheet->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(20);

				// headers
				$spreadsheet->getActiveSheet()->setCellValue($columns[$index] . '1', $header);

				$id++;
				break;
			}
		}

		// Font
		$spreadsheet->getActiveSheet()->getStyle('A1:I1')->getFont()->setBold(true)->setName('Arial')->setSize(10);
		$spreadsheet->getActiveSheet()->getStyle('A1:I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('3399ff');
		$spreadsheet->getActiveSheet()->getStyle('A:I')->getFont()->setName('Arial')->setSize(10);

		/** =======PROCESS================================================================================== */

		// Add new sheet
		$spreadsheet->createSheet();

		// Add some data
		$spreadsheet->setActiveSheetIndex(2);

		// active and set title
		$spreadsheet->getActiveSheet()->setTitle('Process');

		// set Header, width
		$columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M');

		// columns
		$header3 = array(
			'tt', 'machine_type', 'internal_item', 'length_btp', 'det', 'xe_sonic', 'qua_ho', 'qua_nuoc', 'noi_dau', 'dan_keo', 'cat_gap', 'cat_laser', 'dong_goi'
		);

		$id = 0;
		foreach ($header3 as $header) {
			for ($index = $id; $index < count($header3); $index++) {
				// width
				if ($index == 0) $spreadsheet->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(7);
				else $spreadsheet->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(20);

				// headers
				$spreadsheet->getActiveSheet()->setCellValue($columns[$index] . '1', $header);

				$id++;
				break;
			}
		}

		// Font
		$spreadsheet->getActiveSheet()->getStyle('A1:M1')->getFont()->setBold(true)->setName('Arial')->setSize(10);
		$spreadsheet->getActiveSheet()->getStyle('A1:M1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('3399ff');
		$spreadsheet->getActiveSheet()->getStyle('A:M')->getFont()->setName('Arial')->setSize(10);


		/* ========================= OUT PUT ==============================================================*/

		// set filename for excel file to be exported
		$filename = 'Woven_Master_Data_' . date("Y_m_d__H_i_s");

		// header: generate excel file
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
		header('Cache-Control: max-age=0');
		// writer
		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');

		/* ========================= END OUT PUT ==============================================================*/
	}

	// remark packing instruction, KHONG KIM LOAI
	function packingInstrRemark($production_line, $po_no, $packing_instr, $rbo)
	{
		$this->load->model('common_remark_po_save', 'remark_po_save');

		$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

		$remark = '';
		$result = '';

		if (strpos(strtoupper($packing_instr), 'KHONG KIM LOAI') !== false) {
			$remark = 'KHONG KIM LOAI';
		} else {
			$rboArr = array('ADIDAS', 'UNIQLO');
			foreach ($rboArr as $rboCheck) {
				if (strpos(strtoupper($rbo), $rboCheck) !== false) {
					$remark = 'KHONG KIM LOAI';
					break;
				}
			}
		}

		// if (strpos(strtoupper($rbo), 'ADIDAS') !==false || strpos(strtoupper($rbo), 'UNIQLO') !==false || strpos(strtoupper($rbo), 'TARGET') !==false  ) {
		// 	$remark = 'KHONG KIM LOAI';
		// } else {
		// 	if (strpos(strtoupper($packing_instr), 'KHONG KIM LOAI') !==false ) {
		// 		$remark = 'KHONG KIM LOAI';
		// 	}
		// }

		// save
		if (!empty($remark) || !empty($packing_instr)) {
			$where = array('production_line' => $production_line, 'po_no' => $po_no, 'remark' => $remark);
			if ($this->remark_po_save->isAlreadyExist($where)) {
				$result = $this->remark_po_save->update(array('packing_instr' => $packing_instr, 'updated_by' => $updated_by, 'updated_date' => date('Y-m-d H:i:s')), $where);
			} else {
				$result = $this->remark_po_save->insert(array('production_line' => $production_line, 'po_no' => $po_no, 'remark' => $remark, 'packing_instr' => $packing_instr, 'updated_by' => $updated_by));
			}

			// result
			return $result;
		} else {
			return TRUE;
		}
	}

	/* Lưu remark: Nếu không có remark để lưu hoặc lưu thành công thì trả về TRUE, ngược lại trả về FALSE */
	public function remark($productionLine, $po_no, $remarkCheckArr)
	{

		// load models
		$this->load->model('woven_po_save');
		$this->load->model('woven_po_soline_save');
		$this->load->model('woven_master_item_supply_save', 'supply_save');
		$this->load->model('woven_master_item_process_save', 'process_save');
		$this->load->model('common_size_save', 'size_save');

		$this->load->model('common_remarks', 'remarks');
		$this->load->model('common_remark_po_save', 'remark_po_save');

		// xóa các remark cũ đã lưu trước đó. Tránh trường hợp một remark xóa rồi nhưng do làm lệnh trước thì vẫn còn hiển thị khi in
		$this->remark_po_save->deleteNO($po_no);

		// get data
		$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

		$remarkCheck = $this->remarks->readProductionLine($productionLine);
		// $remarkSaveCheck = $this->remark_po_save->readProductionLine($productionLine);

		$result = TRUE;
		if (!empty($remarkCheck)) {
			foreach ($remarkCheck as $value) {
				// get data
				$condition_code = $value['condition_code'];
				$conditions = json_decode($value['conditions']);
				$remark = trim($value['remark']);
				// check
				$count = 0;
				$count2 = 0;
				$error = 0;
				foreach ($conditions as $key => $cond) {
					$count++;
					foreach ($remarkCheckArr as $key2 => $cond2) {
						if ($key == $key2) {
							// Nếu như là packing thì kiểm tra xem ký tự có nằm trong packing hay không
							if ($key2 == 'packing_instr') {
								if (stripos($cond2, $cond) !== false) {
									$count2++;
								} else {
									$error = 1;
								}
							} else { // ngược lại thì so sánh bằng
								if (trim($cond) == trim($cond2)) {
									$count2++;
								} else {
									$error = 1;
								}
							}

							break;
						}
					}

					if ($error == 1) break;
				}

				// save
				if ($error == 0 && ($count == $count2)) {
					if ($this->remark_po_save->isAlreadyExist(array('production_line' => $productionLine, 'po_no' => $po_no, 'remark' => $remark))) {
						// update (user, date)
						$updateData = array(
							'condition_code' => $condition_code,
							'conditions' => json_encode($conditions, JSON_UNESCAPED_UNICODE),
							'updated_by' => $updated_by,
							'updated_date' => date('Y-m-d H:i:s')
						);
						$result =  $this->remark_po_save->update($updateData, array('production_line' => $productionLine, 'po_no' => $po_no, 'remark' => $remark));
					} else {
						// insert
						$insertData = array(
							'production_line' => $productionLine,
							'po_no' => $po_no,
							'remark' => $remark,
							'condition_code' => $condition_code,
							'conditions' => json_encode($conditions, JSON_UNESCAPED_UNICODE),
							'updated_by' => $updated_by
						);

						$result = $this->remark_po_save->insert($insertData);
					}
				} else {
					// Trường hợp không có remark để lưu
					$result = TRUE;
				}
			}
		}

		// return
		return $result;
	}

	// special item remark: Các remark điều kiện từ danh sách Item đặc biệt (nhiều item)
	public function specialItemRemark($productionLine, $po_no, $remark)
	{

		// model
		$this->load->model('woven_master_item', 'wv_master_item');
		$this->load->model('common_remark_po_save', 'remark_po_save');

		// get data
		$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

		// save
		if (!empty($remark)) {
			if ($this->remark_po_save->isAlreadyExist(array('production_line' => $productionLine, 'po_no' => $po_no, 'remark' => $remark))) {
				// update (user, date)
				$updateData = array(
					'updated_by' => $updated_by,
					'updated_date' => date('Y-m-d H:i:s')
				);
				$result =  $this->remark_po_save->update($updateData, array('production_line' => $productionLine, 'po_no' => $po_no, 'remark' => $remark));
			} else {
				// insert
				$insertData = array(
					'production_line' => $productionLine,
					'po_no' => $po_no,
					'remark' => $remark,
					'updated_by' => $updated_by
				);

				$result = $this->remark_po_save->insert($insertData);
			}
		} else {
			$result = TRUE;
		}

		return $result;
	}

	// supply GRS remark
	public function supplyGRS($po_no, $internal_item, $remarkSupplySave)
	{
		// Mặc định result TRUE (không có update supply)
		$result = TRUE;
		// model
		$this->load->model('common_remark_po_save', 'remark_po_save');
		$this->load->model('woven_master_item_supply_save', 'supply_save');

		// get data
		$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

		$supplyArr = array(
			'EM100220002469',
			'EM100220002755',
			'EM100220002758',
			'LY001823',
			'LY001856',
			'LY0020-003786-50',
			'LY002342',
			'LY002459',
			'LY002476',
			'LY002662',
			'LY002815',
			'LY002899',
			'LY002900',
			'LY002901',
			'LY002923',
			'LY002982',
			'LY003118',
			'LY003258',
			'LY003435',
			'LY003558',
			'LY003573',
			'LY003577',
			'LY003578',
			'LY003579',
			'LY003637',
			'LY003639',
			'LY0100-003807-75',
			'LY0100-003866-50',
			'LY0110-003814-75',
			'LY050220003933',
			'LY070220001067',
			'LY070220001081',
			'LY070220003150',
			'LY070220004343',
			'LY070220004530',
			'LY070220005028',
			'LY0751211-000001',
			'LY0751211-000049',
			'LY0751211-000062',
			'LY0751211-000341',
			'LY0751211-004043',
			'LY0751211-004052',
			'LY0751211-004053',
			'LY0751211-004093',
			'LY0751211-004132',
			'LY0751211-004213',
			'LY0751211-004318',
			'LY0751211-004368',
			'LY0751211-004540',
			'LY0751211-004544',
			'LY0751211-004629',
			'LY0751211-004637',
			'LY0751211-004649',
			'LY0751211-004658',
			'LY0751211-004827',
			'LY0751211-004916',
			'LY0751211-004934',
			'LY0751211-004952',
			'LY0751211-005032',
			'LY0751211-005146',
			'LY0751211-005181',
			'LY0751211-005296',
			'LY0751211-005297',
			'LY0751211-005298',
			'LY0751211-005320',
			'LY0751211-005323',
			'LY0751211-005363',
			'LY0751211-005397',
			'LY0751211-005398',
			'LY0751211-005400',
			'LY0751211-005402',
			'LY0751211-005429',
			'LY0751213-000040',
			'LY0751213-004316',
			'LY0751213-004428',
			'LY0751213-004533',
			'LY0751213-004732',
			'LY0751213-005399',
			'LY0751221-004523',
			'LY0751221-004525',
			'LY0751221-004527',
			'LY0751231-004193',
			'LY0751231-004333',
			'LY0751241-005237',
			'LY0751241-005393',
			'LY075220004143',
			'LY075220004226',
			'LY075220004370',
			'LY075220004504',
			'LY075220005429',
			'LY075220006400',
			'LY1006221-004065',
			'LY1006221-004076',
			'LY1006221-004505',
			'LY1006221-004507',
			'LY1006221-004953',
			'LY1206221-004885',
			'LY1206221-004886',
			'LY1206221-004917',
			'LY1206221-005138',
			'LY1206221-005484',
			'YRCP07001076',
			'YRCP07001082',
			'YRCP07005004',
			'YRCP07005011',
			'YRCP07009002',
			'YRCP07010088',
			'YRCP07010090',
			'YRCP07011083',
			'YRCP07011086',
			'LY0751231-004580',
			'YRCP07007033',
			'LY0751213-004457',
			'LY1206221-005222',
			'LY003555',
			'LY070220004117',
			'LY1206221-005328',
			'LY0751211-005325',
			'LY1206221-005327',
			'LY0751211-004498',
			'LY1206221-005483',
			'LY1006221-004464',
			'LY0756221-005161',
			'LY1206221-005386',
			'LY1206221-005385',
			'LY075220004466',
			'LY0751213-005203',
			'LY0751211-005392',
			'LY0751211-005326',
			'LY1206221-005329',
			'LY0501211-004425',
			'LY1206221-005635',
			'LY003570',
			'LY0751211-005288',
			'LY002481',
			'LY0751211-004436',
			'LY0751211-004939',
			'LY1206121-004472',
			'LY0501211-000103',
			'LY1206221-005223',
			'LY1206221-005092',
			'LY002922',
			'LY0100-003921-75',
			'LY0751213-004534',
			'LY0751211-004547',
			'LY0751211-005486',
			'LY0751241-005487',
			'LY0501211-004981',
			'LY0501221-005047',
			'LY0751213-004597',
			'LY0751211-004982',
			'LY0751211-000367',
			'LY0751213-005723',
			'LY002992',
			'LY0751211-005497',
			'YRCP07008048'
		);

		$updated_date = date('Y-m-d H:i:s');
		$grs = 'GRS';
		$note = "Cập nhật GRS bởi: $updated_by lúc: $updated_date ";
		$supplyUpdate = array('grs' => $grs, 'note' => $note);

		foreach ($remarkSupplySave as $supply) {
			$internal_item = $supply['internal_item'];
			$length_btp = $supply['length_btp'];
			$code_name = $supply['code_name'];
			$order = $supply['order'];

			$supplyW = array('po_no' => $po_no, 'internal_item' => $internal_item, 'length_btp' => $length_btp, 'code_name' => $code_name, 'order' => $order);
			foreach ($supplyArr as $supplyCheck) {
				if ($supplyCheck == $code_name) {
					// kiểm tra tồn tại của code vật tư
					if ($this->supply_save->isAlreadyExist($supplyW)) {
						$result = $this->supply_save->update($supplyUpdate, $supplyW);
					}

					break;
				}
			}
		}


		// result
		return $result;
	}

	// special item remark table: Các remark điều kiện từ bảng: woven_special_item_remarks
	public function specialTableRemark($production_line, $po_no, $internal_item)
	{

		$result = TRUE; // mặc định

		// model
		$this->load->model('common_special_item_remarks', 'special_item_remarks');
		$this->load->model('common_remark_po_save', 'remark_po_save');

		// get data
		$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

		// save
		if (!empty($production_line) && !empty($internal_item)) {
			if ($this->special_item_remarks->isAlreadyExist(array('production_line' => $production_line, 'internal_item' => $internal_item))) {
				$specialTableRemark = $this->special_item_remarks->readItem(array('internal_item' => $internal_item));
				$remark = strtoupper(trim($specialTableRemark['remark']));
				$implemented_date = date('Y-m-d', strtotime($specialTableRemark['implemented_date']));

				$check = false;
				if (!empty($remark)) {
					if ($remark == 'GRS') {
						$count = $this->subDistanceDate(date('Y-m-d'), $implemented_date);
						if ($count >= 0) {
							// Nếu implemented_date lớn hơn hoặc bằng ngày hiện tại thì hiển thị remark GRS.
							$check = true;
						}
					} else {
						// Trường hợp không phải GRS (một remark khác)
						$check = true;
					}
				}

				// Nếu check = true (dữ liệu cần lưu)
				if ($check == true) {
					if ($this->remark_po_save->isAlreadyExist(array('production_line' => $production_line, 'po_no' => $po_no, 'remark' => $remark))) {
						// update (user, date)
						$updateData = array(
							'updated_by' => $updated_by,
							'updated_date' => date('Y-m-d H:i:s')
						);
						$result =  $this->remark_po_save->update($updateData, array('production_line' => $production_line, 'po_no' => $po_no, 'remark' => $remark));
					} else {
						// insert
						$insertData = array(
							'production_line' => $production_line,
							'po_no' => $po_no,
							'remark' => $remark,
							'updated_by' => $updated_by
						);

						$result = $this->remark_po_save->insert($insertData);
					}
				}
			}
		}

		return $result;
	}

	// import vào bảng woven_special_item_remarks
	public function importSpecialTable()
	{
		$this->_data['title'] = 'Import Special Table';
		$production_line = isset($_COOKIE['plan_department']) ? strtolower($_COOKIE['plan_department']) : '';
		$updated_by = isset($_COOKIE['plan_loginUser']) ? strtolower($_COOKIE['plan_loginUser']) : '';

		if ($this->input->post('importfile')) {

			// init var
			$specialItemRemarks = array();

			// config info
			$path = 'uploads/';
			$config['upload_path'] = $path;
			$config['allowed_types'] = 'xlsx|xls';
			$config['remove_spaces'] = TRUE;
			$this->upload->initialize($config);
			$this->load->library('upload', $config);

			// check error (1)
			if (!$this->upload->do_upload('masterfile')) {
				$error = array('error' => $this->upload->display_errors());
			} else {
				$data = array('upload_data' => $this->upload->data());
			}

			// check file (2)
			if (!empty($data['upload_data']['file_name'])) {
				$import_xls_file = $data['upload_data']['file_name'];
			} else {
				$import_xls_file = 0;
			}

			// // // test file
			// // $import_xls_file = 'The_Special_Item_Remarks.xlsx';
			// // $path = 'uploads/';

			// Check ok
			if ($import_xls_file !== 0) {
				// get file
				$inputFileName = $path . $import_xls_file;
				// init PhpSpreadsheet Xlsx
				$Reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
				// get sheet 0 (sheet 1)
				$spreadSheet = $Reader->load($inputFileName);
				$spreadSheet = $spreadSheet->getSheet(0); // Theo sheet
				$allDataInSheet = $spreadSheet->toArray(null, true, true, true);

				// check col name exist
				$createArray = array('Item', 'Remark', 'Implemented_Date');
				$makeArray = array('Item' => 'Item', 'Remark' => 'Remark', 'Implemented_Date' => 'Implemented_Date');
				$SheetDataKey = array();
				foreach ($allDataInSheet as $dataInSheet) {
					foreach ($dataInSheet as $key => $value) {
						if (in_array(trim($value), $createArray)) {
							$value = preg_replace('/\s+/', '', $value);
							$SheetDataKey[trim($value)] = $key;
						} else {
						}
					}
				}

				// check data
				$flag = 0;
				$data = array_diff_key($makeArray, $SheetDataKey);
				if (empty($data)) {
					$flag = 1;
				}

				// load data
				if ($flag == 1) {

					for ($i = 2; $i <= count($allDataInSheet); $i++) {
						// get col key
						$item = $SheetDataKey['Item'];
						$remark = $SheetDataKey['Remark'];
						$implemented_date = $SheetDataKey['Implemented_Date'];


						// get data 
						$item = filter_var(trim(strtoupper($allDataInSheet[$i][$item])), FILTER_SANITIZE_STRING);
						$remark = filter_var(trim($allDataInSheet[$i][$remark]), FILTER_SANITIZE_STRING);
						$implemented_date = trim($allDataInSheet[$i][$implemented_date]);
						if (!empty($implemented_date)) {
							$implemented_date = date('Y-m-d', strtotime($implemented_date));
						}

						// check empty data
						if (empty($item) || empty($remark)) continue;

						// get data
						$specialItemRemarks[] = array(
							'production_line' => $production_line,
							'internal_item' => $item,
							'remark' => $remark,
							'implemented_date' => $implemented_date,
							'updated_by' => $updated_by
						);
					}
				}

				// print_r($specialItemRemarks); exit();

				/* === update data to this table ===================== */
				// load models
				$this->load->model('common_special_item_remarks', 'special_item_remarks');
				// check update
				$count = 0;
				if (!empty($specialItemRemarks)) {
					foreach ($specialItemRemarks as $key => $value) {
						$internal_item = $value['internal_item'];
						$remark = $value['remark'];
						$implemented_date = !empty($value['implemented_date']) ? date('Y-m-d', strtotime($value['implemented_date'])) : '1970-01-01';

						if (stripos($remark, 'GRS') !== false) {
							if ($implemented_date == '1970-01-01') {
								$message = 'Implemented Date Type wrong the format. The Correct format is Date. Please check import file';
								$this->_data['results'] = array(
									'status' => false,
									'message' => $message
								);
								$this->load->view('woven/masterData/specialTableRemark', $this->_data);
							} else {
								// update
								$where = array('production_line' => $production_line, 'internal_item' => $internal_item, 'remark' => $remark);
								if ($this->special_item_remarks->isAlreadyExist($where)) {
									$updateData = $specialItemRemarks[$key];
									unset($specialItemRemarks[$key]); // xóa phần tử khỏi mảng (vị trí $key)
									unset($updateData['production_line']);
									unset($updateData['internal_item']);
									unset($updateData['remark']);
									$updateData['updated_date'] = date('Y-m-d H:i:s');
									$result = $this->special_item_remarks->update($updateData, $where);

									if ($result != TRUE) {
										$message = "Import (Update) Data failed. Please check import file";
										$this->_data['results'] = array('status' => false, 'message' => $message);
										$this->load->view('woven/masterData/specialTableRemark', $this->_data);
									}
								}
							}
						} else {
							// update
							$where = array('production_line' => $production_line, 'internal_item' => $internal_item, 'remark' => $remark);
							if ($this->special_item_remarks->isAlreadyExist($where)) {
								$updateData = $specialItemRemarks[$key];
								unset($specialItemRemarks[$key]); // xóa phần tử khỏi mảng (vị trí $key)
								unset($updateData['production_line']);
								unset($updateData['internal_item']);
								unset($updateData['remark']);
								$updateData['updated_date'] = date('Y-m-d H:i:s');
								$result = $this->special_item_remarks->update($updateData, $where);

								if ($result != TRUE) {
									$message = "Import (Update) Data failed. Please check import file";
									$this->_data['results'] = array('status' => false, 'message' => $message);
									$this->load->view('woven/masterData/specialTableRemark', $this->_data);
								}
							}
						}
					}
				}

				// insert
				if (!empty($specialItemRemarks)) {
					$result = $this->special_item_remarks->insertBatch($this->special_item_remarks->setInsertBatch($specialItemRemarks));
					if ($result != TRUE) {
						$message = "Import (Insert) Data failed. Please check import file";
						$this->_data['results'] = array('status' => false, 'message' => $message);
						$this->load->view('woven/masterData/specialTableRemark', $this->_data);
					}
				}
			}
		} else {
			$message = 'Import data error';
			$this->_data['results'] = array(
				'status' => false,
				'message' => $message
			);
			$this->load->view('woven/masterData/specialTableRemark', $this->_data);
		}

		// success
		$message = 'Import Data Success';
		$this->_data['results'] = array(
			'status' => true,
			'message' => $message
		);

		$this->load->view('woven/masterData/specialTableRemark', $this->_data);
	}

	// Tính khoảng cách 2 ngày, Trả về một số count, Nếu count > 0 (ngày 1 lớn hơn ngày 2) và ngược lại
	public function subDistanceDate($date1, $date2)
	{
		$count = 0;
		if (!empty($date1) && !empty($date2)) {
			$date1 = date('Y-m-d', strtotime($date1));
			$date2 = date('Y-m-d', strtotime($date2));

			$count = abs(strtotime($date1) - strtotime($date2));
		}

		return $count;
	}

	/* 	
		Updated: 20201127: Xử lý hiển thị remark các code vật tư giống nhau.
		ví dụ: TT058SA GIỐNG LY003263.
		Nếu các code khác thì thêm vào mảng check
	*/
	public function supplySameRemark($productionLine, $po_no, $remarkSupplySave)
	{
		// init
		$remark = '';
		$insertData = array();
		$result = TRUE;

		// sử dụng mảng để check 
		$supplySameArr = array();
		array_push($supplySameArr, array('supply' => 'LY003263', 'supplySame' => 'TT058SA'));

		// model
		$this->load->model('common_remark_po_save', 'remark_po_save');

		// get data
		$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

		// Kiểm tra remark
		if (!empty($remarkSupplySave)) {
			foreach ($remarkSupplySave as $supply) {
				// code vật tư save
				$code_name = trim($supply['code_name']);

				foreach ($supplySameArr as $supplyCheck) {
					$supplyC = trim($supplyCheck['supply']);
					$supplySame = trim($supplyCheck['supplySame']);
					// trường hợp code vật tư check giống với code vật tư trong đơn đang làm
					if ($supplyC == $code_name) {
						$remark = "$supplySame GIỐNG $supplyC";
						// lấy dữ liệu để check và save
						$where = array('production_line' => $productionLine, 'po_no' => $po_no, 'remark' => $remark);
						$insertData = array(
							'production_line' => $productionLine,
							'po_no' => $po_no,
							'remark' => $remark,
							'updated_by' => $updated_by
						);
						break;
					}
				}
			}

			if (!empty($insertData)) {
				if ($this->remark_po_save->isAlreadyExist($where)) {
					// update (user, date)
					$updateData = array(
						'updated_by' => $updated_by,
						'updated_date' => date('Y-m-d H:i:s')
					);
					$result =  $this->remark_po_save->update($updateData, $where);
				} else {
					$result = $this->remark_po_save->insert($insertData);
				}
			}
		}

		// result 
		return $result;
	}

	// Cập nhật Code vật tư vào bảng (woven_gycg2_yarn_code_matching): GYCG2 để thay thế code vật tư hiện tại trong hệ thống
	public function importGYCG2()
	{
		$this->_data['title'] = 'Import GYCG2 Table';
		$updated_by = isset($_COOKIE['plan_loginUser']) ? strtolower($_COOKIE['plan_loginUser']) : '';

		if ($this->input->post('importfile')) {

			// init var
			$importData = array();

			// config info
			$path = 'uploads/';
			$config['upload_path'] = $path;
			$config['allowed_types'] = 'xlsx|xls';
			$config['remove_spaces'] = TRUE;
			$this->upload->initialize($config);
			$this->load->library('upload', $config);

			// check error (1)
			if (!$this->upload->do_upload('masterfile')) {
				$error = array('error' => $this->upload->display_errors());
			} else {
				$data = array('upload_data' => $this->upload->data());
			}

			// check file (2)
			$import_xls_file = 0;
			if (!empty($data['upload_data']['file_name'])) {
				$nameTmp = $data['upload_data']['file_name'];
				$nameTmp = str_replace('.xlsx', '', $nameTmp);
				$import_xls_file_tmp = $nameTmp . '_' . getenv("REMOTE_ADDR") . '_' . $updated_by . '_' .  date('Y-m-d_H-i-s') . '.xlsx';

				if (rename($path . $data['upload_data']['file_name'], $path . $import_xls_file_tmp)) {
					$import_xls_file = $import_xls_file_tmp;
				}
			}

			// // // test file
			// // 	$import_xls_file = 'gycg2.xlsx';
			// // 	$path = 'uploads/';

			// Check ok
			if ($import_xls_file !== 0) {
				// get file
				$inputFileName = $path . $import_xls_file;
				// init PhpSpreadsheet Xlsx
				$Reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
				// get sheet 0 (sheet 1)
				$spreadSheet = $Reader->load($inputFileName);
				$spreadSheet = $spreadSheet->getSheet(0); // Theo sheet
				$allDataInSheet = $spreadSheet->toArray(null, true, true, true);


				// check col name exist Existing_Code GYCG2_Code
				$createArray = array('ExistingCode', 'GYCG2Code');
				$makeArray = array('ExistingCode' => 'ExistingCode', 'GYCG2Code' => 'GYCG2Code');
				$SheetDataKey = array();
				foreach ($allDataInSheet as $dataInSheet) {
					foreach ($dataInSheet as $key => $value) {
						if (in_array(trim($value), $createArray)) {
							$value = preg_replace('/\s+/', '', $value);
							$SheetDataKey[trim($value)] = $key;
						} else {
						}
					}
				}

				// check data
				$flag = 0;
				$data = array_diff_key($makeArray, $SheetDataKey);
				if (empty($data)) {
					$flag = 1;
				}

				// load data
				if ($flag == 1) {

					for ($i = 2; $i <= count($allDataInSheet); $i++) {
						// get col key
						$existingCode = $SheetDataKey['ExistingCode'];
						$gycg2Code = $SheetDataKey['GYCG2Code'];


						// get data 
						$existing_supply_code = filter_var(trim(strtoupper($allDataInSheet[$i][$existingCode])), FILTER_SANITIZE_STRING);
						$gycg2_supply_code = filter_var(trim(strtoupper($allDataInSheet[$i][$gycg2Code])), FILTER_SANITIZE_STRING);
						$remark = "$existing_supply_code = $gycg2_supply_code";

						// check empty data
						if (empty($existing_supply_code) || empty($gycg2_supply_code)) continue;

						// get data
						$importData[] = array(
							'gycg2_supply_code' => $gycg2_supply_code,
							'existing_supply_code' => $existing_supply_code,
							'remark' => $remark,
							'updated_by' => $updated_by
						);
					}
				}

				/* === update data to this table ===================== */
				// load models
				$this->load->model('woven_gycg2_yarn_code_matching', 'gycg2');
				$this->load->model('woven_master_item_supply', 'supply');
				// $this->load->model('woven_master_item_supply_test', 'supply_test');
				// check update
				$count = 0;
				if (!empty($importData)) {
					foreach ($importData as $key => $value) {
						$gycg2_supply_code = $value['gycg2_supply_code'];
						$existing_supply_code = $value['existing_supply_code'];
						$remark = $value['remark'];

						// update
						$where = array('gycg2_supply_code' => $gycg2_supply_code);

						if ($this->gycg2->isAlreadyExist($gycg2_supply_code, $existing_supply_code)) {
							$updateData = $importData[$key];
							unset($importData[$key]); // xóa phần tử khỏi mảng (vị trí $key)
							unset($updateData['gycg2_supply_code']);
							unset($updateData['existing_supply_code']);
							$updateData['updated_date'] = date('Y-m-d H:i:s');
							$result = $this->gycg2->update($updateData, $where);

							if ($result != TRUE) {
								$message = "Import (Update) Data failed. Please check import file";
								$this->_data['results'] = array('status' => false, 'message' => $message);
								$this->load->view('woven/masterData/view_gycg2', $this->_data);
							}
						}
					}
				}

				// insert gycg2
				if (!empty($importData)) {
					$result = $this->gycg2->insertBatch($this->gycg2->setInsertBatch($importData));
					if ($result != TRUE) {
						$message = "Import (Insert) Data failed. Please check import file";
						$this->_data['results'] = array('status' => false, 'message' => $message);
						$this->load->view('woven/masterData/view_gycg2', $this->_data);
					}
				}

				// if update gycg2 data success, update material code
				if ($result) {
					// Get gycg2 data in gycg2 table to check 
					if ($this->gycg2->countAll() > 0) {
						$gycg2Data = $this->gycg2->read();
						foreach ($gycg2Data as $gycg2Item) {
							$gycg2_supply_code = $gycg2Item['gycg2_supply_code'];
							$existing_supply_code = $gycg2Item['existing_supply_code'];
							$remark = $gycg2Item['remark'];
							$where = array('code_name' => $existing_supply_code);

							// Kiểm tra code vật tư có tồn tại không. Nếu có thì cập nhật code vật tư hiện tại bằng code GYCG2
							if ($this->supply->checkMasterItem($where)) {
								$result = $this->supply->update(array('code_name' => $gycg2_supply_code, 'note' => $remark), $where);
								if ($result != TRUE) {
									$message = "Import (Update Supply) Data failed. Please check import file";
									$this->_data['results'] = array('status' => false, 'message' => $message);
									$this->load->view('woven/masterData/view_gycg2', $this->_data);
								}
							}
						}
					}
				}
			}
		} else {
			$message = 'Import data error';
			$this->_data['results'] = array(
				'status' => false,
				'message' => $message
			);
			$this->load->view('woven/masterData/view_gycg2', $this->_data);
		}

		// success
		$message = 'Import Data Success';
		$this->_data['results'] = array(
			'status' => true,
			'message' => $message
		);

		$this->load->view('woven/masterData/view_gycg2', $this->_data);
	}

	// 1. Cập nhật code vật tư hiện tại bằng code vật tư mới (GYCG2)
	// 2. Hiển thị remark code GYCG2 = (Code vật tư bị thay thế)
	// 3. Cập nhật cho chương trình cũ (Thay thế code vật tư, bảng lấy chung)
	public function gycg2Remark($productionLine, $po_no, $remarkSupplySave)
	{
		// init
		$remark = '';
		$insertData = array();
		$result = TRUE;

		// sử dụng mảng để check 
		$supplyArr = array();

		// model
		$this->load->model('woven_master_item_supply', 'supply');
		$this->load->model('common_remark_po_save', 'remark_po_save');

		// get data
		$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

		// Kiểm tra remark
		if (!empty($remarkSupplySave)) {
			foreach ($remarkSupplySave as $supply) {
				// code vật tư save
				$internal_item = trim($supply['internal_item']);
				$length_btp = trim($supply['length_btp']);
				$code_name = trim($supply['code_name']);
				$order = trim($supply['order']);

				$array = array(
					'internal_item' => $internal_item,
					'length_btp' => $length_btp,
					'code_name' => $code_name,
					'order' => $order
				);

				if ($this->supply->isAlreadyExist($internal_item, $length_btp, $code_name, $order)) {
					// Đọc code vật tư và lấy ra note
					$supplyItem = $this->supply->readItem($array);
					$remark = $supplyItem['note'];
					// Trường hợp có dữ liệu note thì hiển thị thông tin lên đơn hàng và lưu lại remark
					if (!empty($remark)) {
						// lấy dữ liệu để check và save
						$where = array('production_line' => $productionLine, 'po_no' => $po_no, 'remark' => $remark);
						$insertData = array(
							'production_line' => $productionLine,
							'po_no' => $po_no,
							'remark' => $remark,
							'updated_by' => $updated_by
						);

						// update po save remark
						if ($this->remark_po_save->isAlreadyExist($where)) {
							// update (user, date)
							$updateData = array(
								'updated_by' => $updated_by,
								'updated_date' => date('Y-m-d H:i:s')
							);
							$result =  $this->remark_po_save->update($updateData, $where);
						} else {
							$result = $this->remark_po_save->insert($insertData);
						}
					}
				}
			}
		}

		// result 
		return $result;
	}

	/* 
		- Đối với code vật tư đã được thay thế từ code GYCG2 sau khi cập nhật lại vào chương trình thì không hiển thị remark code supply = code gycg2
		- Hàm hiển thị remark này
		- Hàm này phải thực thi trước hàm gycg2Remark (cần cập nhật dữ liệu trong cột note của bảng supply trước)
	*/
	public function updateOldGycg2Remark($remarkSupplySave)
	{
		// load models
		$this->load->model('woven_gycg2_yarn_code_matching', 'gycg2');
		$this->load->model('woven_master_item_supply', 'supply');
		// result 
		$result = TRUE;

		// Kiểm tra remark
		if (!empty($remarkSupplySave)) {
			foreach ($remarkSupplySave as $supply) {
				// code vật tư save
				$code_name = trim($supply['code_name']);

				if ($this->gycg2->checkCode($code_name)) {
					$gycg2Item = $this->gycg2->readItem(array('existing_supply_code' => $code_name));
					$remark = $gycg2Item['remark'];

					if (!empty($remark)) {

						$where = array('code_name' => $code_name);
						// Kiểm tra code vật tư có tồn tại không. Nếu có thì cập nhật code vật tư hiện tại bằng code GYCG2
						if ($this->supply->checkMasterItem($where)) {
							$result = $this->supply->update(array('note' => $remark), $where);
						}
					}
				}
			}
		}

		return $result;
	}

	public function loadFileNameData()
	{
		// tilte 
		$this->_data['title'] = 'File Name Master';

		// load models
		$this->load->model('woven_master_item_filename', 'filename');

		// XML header
		header('Content-type: text/xml');

		// open
		echo "<rows>";

		// header
		$header = '<head>
                    <column width="50" type="ro" align="center" sort="str">No.</column>

                    <column width="120" type="ed" align="center" sort="str">Existing code</column>
                    <column width="120" type="coro" align="center" sort="str">GYCG2 code</column>
                    <column width="150" type="edn" align="center" sort="str">Remark</column>
                    <column width="120" type="ro" align="center" sort="str">Người cập nhật</column>
                    <column width="*" type="ro" align="center" sort="str">Ngày cập nhật</column>
                </head>';

		echo $header;

		// content
		if ($this->filename->countAll() <= 0) {
			$index = 0;
			for ($i = 0; $i < 5; $i++) {
				$index++;
				echo '<row id="' . $i . '">';
				echo '<cell>' . $index . '</cell>';

				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';


				echo '</row>';
			}
		} else {

			// get data
			$data = $this->filename->read();

			// set data
			$index = 0;
			foreach ($data as $key => $item) {

				$index++;

				echo '<row id="' . $key . '">';
				echo '<cell>' . $index . '</cell>';

				echo '<cell>' . $item['file_name'] . '</cell>';
				echo '<cell>' . $item['taffeta_satin'] . '</cell>';
				echo '<cell>' . $item['gear_density_limit'] . '</cell>';
				echo '<cell>' . $item['updated_by'] . '</cell>';
				echo '<cell>' . $item['updated_date'] . '</cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '</row>';
			}

			$key = $index - 1;
			for ($i = $index; $i <= ($index + 5); $i++) {

				echo '<row id="' . $key . '">';
				echo '<cell>' . $i . '</cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';

				echo '</row>';

				$key++;
			}
		}



		// close
		echo "</rows>";
	}

	public function saveFileName()
	{
		// title 
		$this->_data['title'] = 'Save Auto';

		// POST method
		$dataPost = $this->input->post('data');

		// default
		$result = false;
		$status = false;
		$message = 'Chưa lưu được dữ liệu';

		// check
		$dataPost = json_decode($dataPost, true);
		if (empty($dataPost)) {
			$message = "Không nhận được dữ liệu!!!";
		} else {

			// models
			$this->load->model('woven_master_item_filename', 'filename');

			// get data
			$file_name = filter_var(trim($dataPost['file_name']), FILTER_SANITIZE_STRING);
			$taffeta_satin = filter_var(trim($dataPost['taffeta_satin']), FILTER_SANITIZE_STRING);
			$gear_density_limit = filter_var(trim($dataPost['gear_density_limit']), FILTER_SANITIZE_STRING);

			// check 
			if (empty($file_name) || empty($taffeta_satin) || empty($gear_density_limit)) {
				$message = "Có dữ liệu rỗng. Vui lòng nhập lại";
			} else {
				// set data
				$data = array(
					'file_name' => $file_name,
					'taffeta_satin' => $taffeta_satin,
					'gear_density_limit' => $gear_density_limit,
					'updated_by' => $this->updated_by
				);

				$where = array('file_name' => $file_name);

				// check exist
				if ($this->filename->isAlreadyExist($where)) {
					unset($data['file_name']);
					$result = $this->filename->update($data, $where);
				} else {
					$result = $this->filename->insert($data);
				}

				// set message
				if ($result) {
					$message = "Lưu dữ liệu thành công ";
					$status = true;
				} else {
					$message = "Lỗi lưu dữ liệu";
				}
			}
		}


		// result
		$this->_data['results'] = array(
			'status' => $status,
			'message' => $message
		);

		// render
		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
		exit();
	}

	public function deleteFileName()
	{
		// title 
		$this->_data['title'] = 'Delete Auto';

		// POST method
		$dataPost = $this->input->post('data');

		// default
		$result = false;
		$status = false;
		$message = 'Chưa cập nhật được dữ liệu';

		// check
		$dataPost = json_decode($dataPost, true);
		if (empty($dataPost)) {
			$message = "Không nhận được dữ liệu!!!";
		} else {

			// models
			$this->load->model('woven_master_item_filename', 'filename');

			// get data
			$file_name = filter_var(trim($dataPost['file_name']), FILTER_SANITIZE_STRING);

			// check 
			if (empty($file_name)) {
				$message = "Dữ liệu rỗng";
			} else {

				$where = array('file_name' => $file_name);
				if ($this->filename->isAlreadyExist($where)) {
					$result = $this->filename->delete($where);
					if ($result) {
						$message = "Xóa dữ liệu thành công ";
						$status = true;
					} else {
						$message = "Lỗi xóa dữ liệu";
					}
				}
			}
		}

		// result
		$this->_data['results'] = array(
			'status' => $status,
			'message' => $message
		);

		// render
		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
		exit();
	}


	public function getProcess()
	{

		// title 
		$this->_data['title'] = 'Woven Load Process';

		// code
		$internal_item = null !== $this->input->get('internal_item') ? $this->input->get('internal_item') : '';
		$code = null !== $this->input->get('code') ? $this->input->get('code') : '';

		// form
		$formStruct = '[
			{
				type: "settings", position: "label-left", labelWidth: 150, inputWidth: 200
			},
			{
				type: "fieldset", label: "Process", width: 1048, blockOffset: 10, offsetLeft: 50, offsetTop: 30,
				list: [
					{   type: "settings", position: "label-left", labelWidth: 200, inputWidth: 300, labelAlign: "left" },
					
					{   type: "input", id: "internal_item", name: "internal_item", label: "Item:", icon: "icon-input", required: true, validate: "NotEmpty", value: "' . $internal_item . '", readonly:true },
					{   type: "input", id: "process", name: "process", label: "Code:", icon: "icon-input", value: "' . $code . '", readonly:true },';




		$setting_process = $this->setting_process->read('process_order');

		// Lấy các process có thể có (trong setting process) để tạo danh sách process
		$index = 0;
		$count = count($setting_process);

		$code_arr = explode('-', $code);

		$count_code = count($code_arr);
		foreach ($code_arr as $code_value) {

			$index++;
			$process_arr = '[{value: "none", text: "None"},';

			foreach ($setting_process as $processItem) {

				$process_code = trim($processItem['process_code']);
				$process_name = $process_code . '-' . trim($processItem['process_name_vi']);

				if ($code_value == $process_code) {
					$process_arr .= '{ value: "' . $process_code . '", text: "' . $process_name . '", selected: true },';
				} else {
					$process_arr .= '{ value: "' . $process_code . '", text: "' . $process_name . '" },';
				}
			}

			$process_arr .= ']';

			$order = ($index <= 9) ? '0' . $index : $index;
			$formStruct .= '{   type: "combo", id: "process_' . $order . '", name: "process_' . $order . '", label: "Process ' . $order . ':", style: "color:blue; ", filtering: true, options: ' . $process_arr . '},';
		}


		for ($index = ($count_code + 1); $index <= $count; $index++) {

			$process_arr = '[{value: "none", text: "None"},';

			foreach ($setting_process as $processItem) {

				$process_code = trim($processItem['process_code']);
				$process_name = $process_code . '-' . trim($processItem['process_name_vi']);

				$process_arr .= '{ value: "' . $process_code . '", text: "' . $process_name . '" },';
			}

			$process_arr .= ']';

			$order = ($index <= 9) ? '0' . $index : $index;
			$formStruct .= '{   type: "combo", id: "process_' . $order . '", name: "process_' . $order . '", label: "Process ' . $order . ':", style: "color:blue; ", filtering: true, options: ' . $process_arr . '},';
		}

		$formStruct .= '{   type: "fieldset", label: "Chọn chức năng", width: 1048, blockOffset: 10, offsetLeft: 0, offsetTop: 50,
							list: [
								{   type: "settings", position: "label-left", labelWidth: 200, inputWidth: 200, labelAlign: "left" },
								{   type: "button", id: "update", name: "update", value: "Update", position: "label-center", width: 150, offsetLeft: 50, tooltip: "Cập nhật thay đổi" },
					
								{   type: "newcolumn", "offset": 10 }, 
								{   type: "button", id: "normal", name: "normal", value: "Normal Process", position: "label-center", width: 150, offsetLeft: 50, tooltip: "Dệt - Xẻ Sonic - Nối đầu - Cắt gấp - Đóng gói" },

								{   type: "newcolumn", "offset": 10 }, 
								{   type: "button", id: "default", name: "default", value: "Set Default", position: "label-center", width: 150, offsetLeft: 50, tooltip: "Đặt mặc định sử dụng cho các Item mới" }
							]
						}]
					}]';


		$this->_data['results'] = array(
			'status' => true,
			'message' => 'Success',
			'process_json' => $formStruct
		);


		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
		exit();
	}


	// save process
	public function saveProcess()
	{

		// header("Content-Type: application/json");
		$this->_data['title'] = 'Woven Process Loading';

		// init 
		$status = false;
		$message = "";
		$process = "";

		// GET method
		$event = null !== $this->input->get('name') ? $this->input->get('name') : '';

		// check empty
		if (empty($this->input->post()) || empty($event)) {
			$message = "Empty input data";
		} else {

			// models
			$this->load->model('woven_master_item', 'wv_master_item');

			// get data
			$internal_item = null !== $this->input->post('internal_item') ? $this->input->post('internal_item') : '';

			for ($index = 0; $index < $this->_data['count_process']; $index++) {

				$indexCheck = ($index < 10) ? '0' . $index : $index;

				$process_code = null !== $this->input->post('process_' . $indexCheck) ? $this->input->post('process_' . $indexCheck) : '';
				if ($process_code != 'none') {

					if (empty($process)) {
						$process = $process_code;
					} else {

						if (strpos($process, $process_code) !== false) {
							$message = "Process trùng lặp. Vui lòng kiểm tra lại";
							$status = false;
							break;
						} else {
							$process .= ("-" . $process_code);
							$status = true;
						}
					}
				}
			}

			// check 
			if ($status == true) {

				// update 
				if ($event == 'update') {

					$update = array(
						'process' => $process,
						'updated_by' => $this->updated_by,
						'updated_date' => date('Y-m-d H:i:s'),
						'note' => 'Đã cập nhật Process'
					);
					$where = array('internal_item' => $internal_item);

					$check = $this->wv_master_item->update($update, $where);
					if ($check == TRUE) {
						$message = "Update data success";
					} else {
						$status = false;
						$message = "Update data error, Item: $internal_item";
					}
				} else if ($event == 'default') {

					$data = explode('-', $process);

					// update all process_order = 99
					$check = $this->setting_process->update(array('process_order' => 99), array('process_code !=' => ''));

					$index = 0;
					foreach ($data as $process_code) {
						$index++;

						$check = $this->setting_process->update(array('process_order' => $index), array('process_code' => $process_code));
						if ($check == TRUE) {
							$message = "Update data success";
						} else {
							$status = false;
							$message = "Update data error, Item: $internal_item";
							break;
						}
					}
				}
			}
		}

		$results = array(
			'status' => $status,
			'message' => $message
		);


		echo json_encode($results);
		exit();
	}

	public function getProcessItem()
	{
		// title 
		$this->_data['title'] = 'Woven Get Process';

		// internal item
		$internal_item = null !== $this->input->get('internal_item') ? $this->input->get('internal_item') : '';

		// init 
		$status = false;
		$message = "";
		$process_string = "";

		// check 
		if (empty($internal_item)) {
			$message = "Không lấy được Internal Item";
		} else {

			// models
			$this->load->model('woven_master_item', 'wv_master_item');

			// get process
			if (!$this->wv_master_item->checkItem($internal_item)) {
				$message = "Internal Item không tồn tại trong Master data";
			} else {

				$masterItem = $this->wv_master_item->readItem(array('internal_item' => $internal_item));
				$process_string = trim($masterItem['process']);
			}
		}

		// check empty
		if (empty($process_string)) {
			$message = "Internal Item: $internal_item có Process rỗng";
		} else {
			$status = true;
			$message = "Success";
		}

		// results
		$results = array('status' => $status, 'message' => $message, 'process_string' => $process_string);

		// render
		echo json_encode($results);
		exit();
	}


	public function loadMasterProcess()
	{
		// tilte 
		$this->_data['title'] = 'Master Process';

		// XML header
		header('Content-type: text/xml');

		// open
		echo "<rows>";

		// header
		$header = '<head>
                    <column width="50" type="ro" align="center" sort="str">No.</column>

                    <column width="120" type="ed" align="center" sort="str">Process Code</column>
                    <column width="150" type="ed" align="center" sort="str">Process Name (Vi)</column>
					<column width="150" type="ed" align="center" sort="str">Process Name (En)</column>
					<column width="120" type="ro" align="center" sort="str">Default Order</column>
                    <column width="120" type="ro" align="center" sort="str">Người cập nhật</column>
                    <column width="*" type="ro" align="center" sort="str">Ngày cập nhật</column>
					<column width="70" type="acheck" align="center" sort="str">Save</column>
                    <column width="70" type="acheck" align="center" sort="str">Delete</column>
                </head>';

		echo $header;

		// content
		if ($this->setting_process->countAll() <= 0) {
			$index = 0;
			for ($i = 0; $i < 5; $i++) {
				$index++;
				echo '<row id="' . $i . '">';
				echo '<cell>' . $index . '</cell>';

				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';

				echo '</row>';
			}
		} else {

			// get data
			$data = $this->setting_process->read('process_order');

			// set data
			$index = 0;
			foreach ($data as $key => $item) {

				$index++;
				$process_order = ($item['process_order'] == 99) ? ('Not Default (' . $item['process_order'] . ')') : $item['process_order'];

				echo '<row id="' . $key . '">';
				echo '<cell>' . $index . '</cell>';

				echo '<cell>' . $item['process_code'] . '</cell>';
				echo '<cell>' . $item['process_name_vi'] . '</cell>';
				echo '<cell>' . $item['process_name_en'] . '</cell>';
				echo '<cell>' . $process_order . '</cell>';
				echo '<cell>' . $item['updated_by'] . '</cell>';
				echo '<cell>' . $item['updated_date'] . '</cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '</row>';
			}

			$key = $index - 1;
			for ($i = $index; $i <= ($index + 5); $i++) {

				echo '<row id="' . $key . '">';
				echo '<cell>' . $i . '</cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';
				echo '<cell></cell>';

				echo '</row>';

				$key++;
			}
		}



		// close
		echo "</rows>";
	}

	public function updateMasterProcess()
	{
		// title 
		$this->_data['title'] = 'Update Master Process';

		// init 
		$status = false;
		$message = "";

		// get 
		$data = isset($_POST["data"]) ? $_POST["data"] : '';

		// check empty
		if (empty($data)) {
			$message = "Không nhận được dữ liệu";
		} else {

			// $data = '{"event":"save","process_code":"AA","process_name_vi":"Test","process_name_en":""}';
			$data = json_decode($data, true);

			// get data
			$event = isset($data['event']) ? $data['event'] : '';
			$process_code = isset($data['process_code']) ? trim($data['process_code']) : '';
			$process_name_vi = isset($data['process_name_vi']) ? trim($data['process_name_vi']) : '';
			$process_name_en = isset($data['process_name_en']) ? trim($data['process_name_en']) : '';

			// check
			if (empty($process_code) || empty($process_name_vi)) {
				$message = "Vui lòng không để trống dữ liệu Process code hoặc Process name (Vi)";
			} else {
				if ($event == 'save') {

					$data = array(
						'production_line' => $this->production_line,
						'process_code' => $process_code,
						'process_name_vi' => $process_name_vi,
						'process_name_en' => $process_name_en,
						'process_order' => 99,
						'updated_by' => $this->updated_by,
						'updated_date' => date('Y-m-d H:i:s')
					);

					$where = array(
						'production_line' => $this->production_line,
						'process_code' => $process_code
					);

					if ($this->setting_process->isAlreadyExist($this->production_line, $process_code)) {
						// update
						unset($data['production_line']);
						unset($data['process_code']);
						$result = $this->setting_process->update($data, $where);
					} else {
						$result = $this->setting_process->insert($data);
					}

					if ($result != TRUE) {
						$message = "LƯU dữ liệu không thành công. Vui lòng liên hệ Admin";
					} else {
						$message = "LƯU dữ liệu thành công. Process code mới không thuộc các process mặc định";
						$status = true;
					}
				} else if ($event == 'delete') {
					if ($this->setting_process->isAlreadyExist($this->production_line, $process_code)) {
						$result = $this->setting_process->delete($this->production_line, $process_code);
					} else {
						$result = FALSE;
					}

					if ($result != TRUE) {
						$message = "XÓA dữ liệu không thành công. Vui lòng liên hệ Admin";
					} else {
						$message = "XÓA dữ liệu thành công";
						$status = true;
					}
				}
			}
		}


		// results
		$results = array('status' => $status, 'message' => $message);

		// render
		echo json_encode($results);
		exit();
	}

	// create master item
	public function createMasterItem2()
	{
		$this->_data['title'] = 'Woven Create Item';

		$this->load->view('woven/masterData/create_masterfile', $this->_data);
	}


	// Các  item cập nhật công đoạn Kiểm 100% trước công đoạn Đóng gói
	public function updateItemKiem100()
	{
		// title
		$this->_data['title'] = 'Update Item Kiem 100%';

		// init
		$status = false;
		$message = 'No data changes';

		// chekc post
		if ($this->input->post('importfile')) {

			// init var
			$importData = array();

			// config info
			$path = 'uploads/';
			$config['upload_path'] = $path;
			$config['allowed_types'] = 'xlsx|xls';
			$config['remove_spaces'] = TRUE;
			$this->upload->initialize($config);
			$this->load->library('upload', $config);

			// check error (1)
			if (!$this->upload->do_upload('masterfile')) {
				$error = array('error' => $this->upload->display_errors());
			} else {
				$data = array('upload_data' => $this->upload->data());
			}

			// check file (2)
			$import_xls_file = 0;
			if (!empty($data['upload_data']['file_name'])) {
				// $nameTmp = $data['upload_data']['file_name'];
				// $nameTmp = str_replace('.xlsx', '', $nameTmp);
				$nameTmp = 'ItemKiem100';
				$import_xls_file_tmp = $nameTmp . '_' . getenv("REMOTE_ADDR") . '_' . $this->updated_by . '_' .  date('Y-m-d_H-i-s') . '.xlsx';

				if (rename($path . $data['upload_data']['file_name'], $path . $import_xls_file_tmp)) {
					$import_xls_file = $import_xls_file_tmp;
				}
			}

			// // // test file
			// // 	$import_xls_file = 'gycg2.xlsx';
			// // 	$path = 'uploads/';

			// Check ok
			if ($import_xls_file !== 0) {
				// get file
				$inputFileName = $path . $import_xls_file;
				// init PhpSpreadsheet Xlsx
				$Reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
				// get sheet 0 (sheet 1)
				$spreadSheet = $Reader->load($inputFileName);
				$spreadSheet = $spreadSheet->getSheet(0); // Theo sheet
				$allDataInSheet = $spreadSheet->toArray(null, true, true, true);


				// check col name exist Existing_Code GYCG2_Code
				$createArray = array('Item');
				$makeArray = array('Item' => 'Item');
				$SheetDataKey = array();
				foreach ($allDataInSheet as $dataInSheet) {
					foreach ($dataInSheet as $key => $value) {
						if (in_array(trim($value), $createArray)) {
							$value = preg_replace('/\s+/', '', $value);
							$SheetDataKey[trim($value)] = $key;
						} else {
						}
					}
				}

				// check data
				$flag = 0;
				$data = array_diff_key($makeArray, $SheetDataKey);
				if (empty($data)) {
					$flag = 1;
				}

				// load data
				if ($flag == 1) {

					for ($i = 2; $i <= count($allDataInSheet); $i++) {
						// get col key
						$internal_item = $SheetDataKey['Item'];

						// get data 
						$internal_item = filter_var(trim(strtoupper($allDataInSheet[$i][$internal_item])), FILTER_SANITIZE_STRING);

						// check empty data
						if (empty($internal_item)) continue;

						// get data
						$importData[] = array(
							'internal_item' => $internal_item,
							'note' => 'Cập nhật Công đoạn Kiểm 100% vào trước Đóng gói',
							'updated_by' => $this->updated_by
						);
					}
				}

				/* === update data to this table ===================== */

				// load models
				$this->load->model('woven_master_item', 'wv_master_item');
				// check update
				$count = 0;
				if (!empty($importData)) {
					foreach ($importData as $key => $value) {

						// get data
						$internal_item = $value['internal_item'];
						$note = $value['note'];

						// update
						$where = array('internal_item' => $internal_item);

						// check exist
						if ($this->wv_master_item->checkItem($internal_item)) {

							// get process
							$masterItem = $this->wv_master_item->readItem($where);
							$process = trim($masterItem['process']);

							// check 
							if (!empty($process)) {

								// Kiểm tra xem nếu có công đoạn KH (Kiểm 100%) thì bỏ qua không cập nhật
								if (stripos($process, 'KH') !== false) continue;

								// Thay thế -DG thành -KH-DG (Kiểm 100% và Đóng gói)
								$process = str_replace('-DG', '-KH-DG', $process);

								// Cập nhật process vào trong mảng 
								$updateData = array(
									'process' => $process,
									'note' => $note,
									'updated_by' => $this->updated_by,
									'updated_date' => date('Y-m-d H:i:s'),
								);

								// save 
								$result = $this->wv_master_item->update($updateData, $where);

								// check ok
								if ($result != TRUE) {
									$message = "Import (Update) Data failed. Please check import file. Item: $internal_item ";
									$this->_data['results'] = array('status' => false, 'message' => $message);
									$this->load->view('woven/masterData/import_results', $this->_data);
								} else {
									$count++;
									$message = "Import Data Success $count lines ";
									$status = true;
								}
							}
						}
					}
				}
			}
		}

		// result
		$this->_data['results'] = array(
			'status' => $status,
			'message' => $message
		);

		// render
		$this->load->view('woven/masterData/import_results', $this->_data);
	}

	// Cập nhật chiều dài chỉ đặc biệt (Để mục đích chương trình kiểm tra code vật tư đặc biệt sẽ lấy chiều dài chỉ)
	public function uploadThreadLength()
	{
		// title
		$this->_data['title'] = 'Update Length - Supply code';

		// init
		$status = false;
		$message = 'No data changes';

		// chekc post
		if ($this->input->post('importfile')) {

			// init var
			$importData = array();

			// config info
			$path = 'uploads/';
			$config['upload_path'] = $path;
			$config['allowed_types'] = 'xlsx|xls';
			$config['remove_spaces'] = TRUE;
			$this->upload->initialize($config);
			$this->load->library('upload', $config);

			// check error (1)
			if (!$this->upload->do_upload('masterfile')) {
				$error = array('error' => $this->upload->display_errors());
			} else {
				$data = array('upload_data' => $this->upload->data());
			}

			// check file (2)
			$import_xls_file = 0;
			if (!empty($data['upload_data']['file_name'])) {
				// $nameTmp = $data['upload_data']['file_name'];
				// $nameTmp = str_replace('.xlsx', '', $nameTmp);
				$nameTmp = 'SupplyCode_Length';
				$import_xls_file_tmp = $nameTmp . '_' . getenv("REMOTE_ADDR") . '_' . $this->updated_by . '_' .  date('Y-m-d_H-i-s') . '.xlsx';

				if (rename($path . $data['upload_data']['file_name'], $path . $import_xls_file_tmp)) {
					$import_xls_file = $import_xls_file_tmp;
				}
			}

			// // // test file
			// // 	$import_xls_file = 'gycg2.xlsx';
			// // 	$path = 'uploads/';

			// Check ok
			if ($import_xls_file !== 0) {
				// get file
				$inputFileName = $path . $import_xls_file;
				// init PhpSpreadsheet Xlsx
				$Reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
				// get sheet 0 (sheet 1)
				$spreadSheet = $Reader->load($inputFileName);
				$spreadSheet = $spreadSheet->getSheet(0); // Theo sheet
				$allDataInSheet = $spreadSheet->toArray(null, true, true, true);


				// check col name exist Existing_Code GYCG2_Code
				$createArray = array('Item Code', 'Length Weft' );
				$makeArray = array('ItemCode' => 'ItemCode', 'LengthWeft' => 'LengthWeft' );
				$SheetDataKey = array();
				foreach ($allDataInSheet as $dataInSheet) {
					foreach ($dataInSheet as $key => $value) {
						if (in_array(trim($value), $createArray)) {
							$value = preg_replace('/\s+/', '', $value);
							$SheetDataKey[trim($value)] = $key;
						} else {
						}
					}
				}

				// check data
				$flag = 0;
				$data = array_diff_key($makeArray, $SheetDataKey);
				if (empty($data)) {
					$flag = 1;
				}

				// load data
				if ($flag == 1) {

					for ($i = 2; $i <= count($allDataInSheet); $i++) {
						// get col key
						$supply_code = $SheetDataKey['ItemCode'];
						$length_weft = $SheetDataKey['LengthWeft'];

						// get data 
						$supply_code = filter_var(trim(strtoupper($allDataInSheet[$i][$supply_code]) ), FILTER_SANITIZE_STRING);
						$length_weft = filter_var(trim($allDataInSheet[$i][$length_weft]), FILTER_SANITIZE_STRING);
						
						// xử lý chiều dài chỉ cho phù hợp, chỉ lấy số (trường hợp người dùng định dạng chữ có dấu , hoặc .)
						$length_weft = preg_replace("/[^0-9]/", "", $length_weft );
						$length_weft = (int)$length_weft;

						if (!is_int($length_weft) ) {
							$message = "Chiều dài: $length_weft (Code vật tư: $supply_code) không phải là Số";
							$this->_data['results'] = array('status' => false, 'message' => $message);
							$this->load->view('woven/masterData/import_results', $this->_data);
							break;
						}

						// check empty data
						if (empty($supply_code)) continue;

						// get data
						$importData[] = array(
							'supply_code' => $supply_code,
							'length_weft' => $length_weft
						);
					}
				}

				/* === update data to this table ===================== */

				// load models
				$this->load->model('woven_master_item_supply_special', 'supply_special');
				// check update
				$count = 0;
				if (!empty($importData)) {
					foreach ($importData as $key => $value) {

						// get data
						$supply_code = $value['supply_code'];
						// data
						$updateData = array(
							'supply_code' => $supply_code,
							'length_weft' => $value['length_weft'],
							'note' =>  'Cập nhật chiều dài chỉ',
							'updated_by' => $this->updated_by
						);

						// where
						$where = array('supply_code' => $supply_code);

						// check exist
						if ($this->supply_special->isAlreadyExist($where ) ) {

							unset($updateData['supply_code']);
							$updateData['updated_date'] = date('Y-m-d H:i:s');

							// save 
							$result = $this->supply_special->update($updateData, $where);

						} else {
							$result = $this->supply_special->insert($updateData);
						}

						// check ok
						if ($result != TRUE) {
							$message = "Import (Update) Data failed. Please check import file. Item Code: $supply_code ";
							$this->_data['results'] = array('status' => false, 'message' => $message);
							$this->load->view('woven/masterData/import_results', $this->_data);
						} else {
							$count++;
							$message = "Import Data Success $count lines ";
							$status = true;
						}


					}
				}
			}
		}

		// result
		$this->_data['results'] = array(
			'status' => $status,
			'message' => $message
		);

		// render
		$this->load->view('woven/masterData/import_results', $this->_data);
	}

	// KHÔNG DÀNH CHO USER ===============================================

	// Dùng để thêm cột Process trong master Item, không dành cho user
	public function processAnalysis()
	{
		$this->load->model('woven_master_item', 'wv_master_item');
		$this->load->model('woven_master_item_process', 'process');

		$process_arr[] = array('process_code' => 'wv_01', 'process_basic' => 'DE');
		$process_arr[] = array('process_code' => 'wv_02', 'process_basic' => 'XS');
		$process_arr[] = array('process_code' => 'wv_03', 'process_basic' => 'QH');
		$process_arr[] = array('process_code' => 'wv_04', 'process_basic' => 'QN');
		$process_arr[] = array('process_code' => 'wv_05', 'process_basic' => 'ND');
		$process_arr[] = array('process_code' => 'wv_06', 'process_basic' => 'DK');
		$process_arr[] = array('process_code' => 'wv_07', 'process_basic' => 'CG');
		$process_arr[] = array('process_code' => 'wv_08', 'process_basic' => 'LS');
		$process_arr[] = array('process_code' => 'wv_09', 'process_basic' => 'DG');


		// get data
		$masterData = $this->wv_master_item->read();

		$check = 0;
		$internal_item_tmp = '';
		foreach ($masterData as $key => $value) {

			$process = '';

			$internal_item = trim($value['internal_item']);
			$length_btp = trim($value['length_btp']);

			// check exist
			if ($internal_item == $internal_item_tmp) {
				continue;
			}

			// get process data
			$processItem = $this->process->readOptions(array('internal_item' => $internal_item, 'length_btp' => $length_btp), 'process_code');

			$count = count($processItem);
			if ($count == 9) {
				foreach ($processItem as $keyP => $valueP) {
					$process_code = trim($valueP['process_code']);
					$status = $valueP['status'];
					if ($status) {
						foreach ($process_arr as $valueB) {
							$process_code_check = $valueB['process_code'];
							$process_basic = $valueB['process_basic'];
							if ($process_code == $process_code_check) {
								$process_code = $process_basic;
								break;
							}
						}

						if ($keyP == ($count - 1)) {
							$process .= "$process_code";
						} else {
							$process .= $process_code . "-";
						}
					}
				}

				// save 
				$dataUpdate = array('process' => $process);
				$where = array('internal_item' => $internal_item);
				$result = $this->wv_master_item->update($dataUpdate, $where);
				if (!$result) {
					echo "Save data Error, Item: $internal_item ; <br>\n";
				} else {
					$check++;
					echo "Save data Success, Item: $internal_item ; <br>\n";
				}
			} else {
				echo "Dont save data, item: $internal_item && count = $count <br>\n";
			}


			$internal_item_tmp = $internal_item;
		}


		echo "<br>\n<br>\n<br>\n Save success: $check";
	}

	// Dùng để thêm cột Process trong PO Save, không dành cho user
	public function processPOSave()
	{

		$this->load->model('woven_po_save');
		$this->load->model('woven_master_item', 'wv_master_item');

		// get data
		$poSave = $this->woven_po_save->read('asc');

		$check = 0;
		$checkErr = 0;
		$internal_item_tmp = '';
		foreach ($poSave as $key => $value) {

			$process = '';

			$internal_item = trim($value['internal_item']);

			// check exist
			if ($internal_item == $internal_item_tmp) {
				continue;
			}

			// get process data
			$masterItem = $this->wv_master_item->readItem(array('internal_item' => $internal_item));
			if (empty($masterItem)) {
				echo "internal item empty <br>\n";
			} else {
				$process = $masterItem['process'];
				// save 
				$dataUpdate = array('process' => $process);
				$where = array('internal_item' => $internal_item);
				$result = $this->woven_po_save->update2($dataUpdate, $where);
				if (!$result) {
					$checkErr++;
					echo "$checkErr. Save data Error, Item: $internal_item ; <br>\n";
				} else {
					$check++;
					echo "$check. Save data Success, Item: $internal_item ; <br>\n";
				}
			}

			$internal_item_tmp = $internal_item;
		}

		echo "<br>\n<br>\n<br>\n Save success: $check && Save Err: $checkErr ";
	}





}
