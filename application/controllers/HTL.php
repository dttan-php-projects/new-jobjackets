<?php
defined('BASEPATH') or exit('No direct script access allowed');
include_once APPPATH . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class HTL extends CI_Controller
{

    protected $_data;

    // Hàm khởi tạo
    function __construct()
    {

        // Gọi đến hàm khởi tạo của cha
        parent::__construct();

        date_default_timezone_set('Asia/Ho_Chi_Minh');

        $this->load->model('autoload');
        $this->load->model('automail');
        $this->load->model('automail_closed');

        // //get automail updated date
        // $automail_updated = $this->autoload->getAutomailUpdated();
        // if (!empty($automail_updated['CREATEDDATE'])) {
        //     $this->_data['automail_updated'] = $automail_updated['CREATEDDATE'];
        // } else {
        //     $this->_data['automail_updated'] = 'loading...';
        // }

        $this->_data['automail_updated'] = $this->getAutomailUpdated();

        // get data default
        $this->production_line = null != get_cookie('plan_department') ? get_cookie('plan_department') : 'htl';
        $this->updated_by = null !== get_cookie('plan_loginUser') ? get_cookie('plan_loginUser') : '';
        $this->form_type = null !== get_cookie('plan_print_form') ? get_cookie('plan_print_form') : '';

        // $this->prepress_dir = '\\\147.121.56.227\\htdocs\\avery\\auto\\planning\\file\\HTL\\prepress\\';
        $this->prepress_dir = '\\\APPDVN10\\Prepress-HTL\\InkData\\';
        // $this->prepress_dir = '\\\APPDVN10\\Prepress-HTL\\InkData\\';

        // get Form 
        $this->_data['form_type_local'] = $this->getForm();
    }

    // automail updated
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
        $title = $this->production_line . ' planning';
        $this->_data['title'] = strtoupper($title);

        if (!$this->checkLogin()) {
            $this->load->view('users/index', $this->_data);
        } else {
            if (empty($this->production_line) || $this->production_line != 'htl') {
                $this->load->view('users/index', $this->_data);
            } else {
                $this->load->view('htl/index', $this->_data);
            }
        }
    }

    public function getForm()
    {

        // tilte 
        $this->_data['title'] = 'HTL Master File';

        // load models
        $this->load->model('common_prefix_no');

        // var
        $results = '';

        // get data
        $prefix_arr = $this->common_prefix_no->readOptions(array('production_line' => $this->production_line));

        // check 
        if (empty($prefix_arr)) {
            $results = '{ value: "hfe", text: "HFE", selected:true },{ value: "htl", text: "HTL" }';
        } else {

            $count = count($prefix_arr);
            foreach ($prefix_arr as $key => $prefix) {
                if ($key == 0) {
                    $results .= '{ value: "' . trim(strtolower($prefix['module'])) . '", text: "' . trim(strtoupper($prefix['module'])) . '", selected:true },';
                } else if ($key == $count) {
                    $results .= '{ value: "' . trim(strtolower($prefix['module'])) . '", text: "' . trim(strtoupper($prefix['module'])) . '"}';
                } else {
                    $results .= '{ value: "' . trim(strtolower($prefix['module'])) . '", text: "' . trim(strtoupper($prefix['module'])) . '" },';
                }
            }
        }

        // result
        return $results;
    }

    // check login
    public function checkLogin()
    {
        return (null != get_cookie('plan_department')) ? true : false;
    }

    public function recent()
    {
        // load models
        $this->load->model('htl_save_po', 'save_po');
        $this->load->model('htl_save_po_soline', 'save_po_soline');
        $this->load->model('common_users');

        // get distance
        $from_date = null !== $this->input->get('from_date') ? trim($this->input->get('from_date')) : '';
        $to_date = null !== $this->input->get('to_date') ? trim($this->input->get('to_date')) : '';

        // get user info
        $userInfo = $this->common_users->readItem($this->updated_by);
        $account_type = isset($userInfo['account_type']) ? $userInfo['account_type'] : '';

        // get data
        if (!empty($from_date) && !empty($to_date)) {

            $from_date = date('Y-m-d H:i:s', strtotime($from_date));
            $to_date = date('Y-m-d H:i:s', strtotime($to_date));

            if ($account_type == 9) {
                $poSave = $this->save_po->readDistance('', '', $from_date, $to_date);
            } else if ($account_type == 3) {
                $poSave = $this->save_po->readDistance($this->form_type, '', $from_date, $to_date);
            } else {
                $poSave = $this->save_po->readDistance($this->form_type, $this->updated_by, $from_date, $to_date);
            }
        } else {
            if ($account_type == 9) {
                $poSave = $this->save_po->read('', '', 'DESC');
            } else if ($account_type == 3) {
                $poSave = $this->save_po->read($this->form_type, '', 'DESC');
            } else {
                $poSave = $this->save_po->read($this->form_type, $this->updated_by, 'DESC');
            }
        }

        // CHUA LAM XONG

        // get all data
        $index = 0;
        $results = array();
        foreach ($poSave as $poitem) {

            $index++;

            // get data
            $po_no = trim($poitem['po_no']);
            $po_no_print = urlencode($po_no);
            
            $orders = $po_no;
            $prefix_url = base_url('htl/');

            if ($poitem['printed'] >= 1 ) {
                $printed_c = $poitem['printed'];
                $printed = '<a style="color:#28a745;text-decoration:none;font-weight:bold;font-size:13px;" target="_blank" href="' . $prefix_url . 'printOrders?po_no=' . $po_no_print . '" title="printed" rel="follow, index">Printed ('.$printed_c.')</a>';
            } else {
                $printed = '<a style="color:#007bff;text-decoration:none;font-weight:bold;font-size:13px;" target="_blank" href="' . $prefix_url . 'printOrders?po_no=' . $po_no_print . '" title="print" rel="follow, index">Print</a>';
            }

            $edit = '<a style="color:#ffc107;text-decoration:none;font-weight:bold;font-size:13px;" href="' . $prefix_url . 'handle/?orders=' . $orders . '&edit=true" title="Edit" rel="follow, index" >Edit</a>';
            // $delete = '<span style="color:blue;font-weight:bold;font-size:13px;"><a href="' . $prefix_url . 'delete/' . $po_no . '" title="Delete" rel="follow, index" onclick="return delete_confirm(' . "'$po_no'" . ');">Delete</a></span>';
            $delete  = '<span style="color:#dc3545;font-weight:bold;font-size:13px;text-decoration:none;">Del</span>^javascript:delete_confirm("'.$po_no.'");^_self';
            // set results
            $results[] = [
                'id' => $index,
                'data' => [
                    $index,
                    strtoupper($poitem['form_type']),
                    $poitem['po_date'],
                    $poitem['po_no'],
                    $poitem['po_no_suffix'],
                    $poitem['plan_type'],
                    $poitem['qty_total'],
                    html_entity_decode($poitem['rbo'], ENT_QUOTES),
                    $poitem['internal_item'],
                    $poitem['name'],
                    $poitem['updated_date'],
                    $printed,
                    $edit,
                    $delete
                ]
            ];
        }

        // result
        echo json_encode($results, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function getSizeAutomail($string)
    {
        //init var
        $dataResults = [];
        $size = $color = $qty = $material_code = '';
        $errorCount = $check_exist = $pause = 0;
        $sizepos = $colorpos = $qtypos = $materialcodepos = $maxpos = '';

        //loại bỏ các khoảng trắng và ký tự thừa do người dùng nhập không đúng
        $string = str_replace(" ", "", $string);
        $string = str_replace(":;:", ";", $string);
        $string = str_replace(":;", ";", $string);
        $string = str_replace(";:", ";", $string);
        $string = str_replace("^^^", "^", $string);
        $string = str_replace("^^", "^", $string);

        if (strpos($string, ";Total") !== false || strpos($string, ";total") !== false) {
            // nothing
        } else if (strpos($string, "Total") !== false) {
            $string = str_replace("Total", ";Total", $string);
        } else if (strpos($string, "total") !== false) {
            $string = str_replace("total", ";Total", $string);
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
                if (strpos(strtoupper($detachedpos[$i]), "MATERIAL CODE") !== false || strpos(strtoupper($detachedpos[$i]), "MATERIALCODE") !== false) {
                    $materialcodepos = $i;
                    $maxpos = count($detachedpos);
                }
            }

            if ($materialcodepos) break;
        }

        //Nếu có data và có ký tự ^ (data k bị mất). Trường hợp ngược lại không them vào
        if (!empty($string_explode) && !$pause) {
            // // echo "\n maxpos: " . $maxpos . "\n";
            foreach ($string_explode as $key => $value) {
                $check_exist = 0;
                //get format string  detached.
                $detachedStringAll = trim($value);

                //check error. Nếu không đúng định dạng => return error
                if (substr_count($detachedStringAll, ":") < 3) { //Trường hợp min = 4 col
                    $errorCount++;
                    continue;
                }

                //tách chuỗi thành mảng bởi ký tự :
                $detachedString = explode(":", $detachedStringAll);

                //check detachedString không đúng định dạng. Dừng
                if (count($detachedString) != $maxpos) {
                    $errorCount++;
                    continue;
                }

                //get data
                if ($sizepos != $colorpos && $colorpos != $qtypos && $qtypos != $materialcodepos) {
                    //lấy dữ liệu //Trường hợp không lấy được cột data nào thì cho dữ liệu đó = rỗng.
                    $size = !empty($sizepos) ? trim($detachedString[$sizepos]) : 'NON';
                    $color = !empty($colorpos) ? trim($detachedString[$colorpos]) : 'NON';
                    $qty = !empty($qtypos) ? $detachedString[$qtypos] : 0;
                    $material_code = !empty($materialcodepos) ? trim($detachedString[$materialcodepos]) : ''; //tam thoi lay vi tri nay

                    /* *** Check trường hợp OE không nhập dấu ; trước chữ Total, dấu ^, (còn thì thêm vào ...) *** */
                    $character_error_arr = [
                        'Total',
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

                }

                if (!is_numeric($qty)) { //kiểm tra qty có phải số không
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
                                'size'             => $size,
                                'color'         => $color,
                                'qty'             => $qty,
                                'material_code' => $material_code
                            ];
                            array_push($dataResults, $get);
                        }
                    } else { //trường hợp đầu tiên
                        $get = [
                            'size'             => $size,
                            'color'         => $color,
                            'qty'             => $qty,
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

    public function getSample($packing_instr, $order_number)
    {
        $sample = 1; // mặc định đơn không có mẫu
        $packing_instr = strtoupper(trim($packing_instr));

        // Option 1: Sample = 1 => Đơn không mẫu (mặc định). 

        // Option 2:  Sample = 2 => Đơn mẫu.

        /*  Option 3: Sample = 3 => Option 3: Đơn có mẫu. Nếu lấy order_number kiểm tra cột packing_str trong automail, nếu nó có tồn tại trong dòng bất kỳ => Đây là đơn có mẫu  */
        if ($this->automail->hasSample($order_number)) {
            $sample = 3;
        } else {
            /*  Option 2: Đơn mẫu */
            // 1. Lấy 8 chữ số trong packing, Nếu tồn tại một số có 8 chữ số thì đây (đơn đang kiểm tra) là đơn mẫu (của 8 chữ số đó (SO#))
            $sample_sinnal = "";
            preg_match('/\d{8}/', $packing_instr, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches as $R) {
                $sample_sinnal = $sample_sinnal . $R[0];
            }
            if (!empty($sample_sinnal)) {
                $sample = 2;
            }
            // 2. Hoặc Tìm các từ có trong mảng dưới đây
            $sample_signal_arr = array('MAU CUA SO#', 'SAMPLE CUA SO#');
            // check option 2
            foreach ($sample_signal_arr as $signal) {
                if (strpos($packing_instr, $signal)) {
                    $sample = 2;
                    break;
                }
            }
        }

        return $sample;
    }

    public function getGPM($customer_job)
    {
        if (empty($customer_job)) {
            $gpm = '';
        } else {
            /** 1.  xử lý dữ liệu thô là cột customer_job trong table oe_soview_text */
            $raw_gpm = trim($customer_job);
            $raw_gpm = str_replace(' ', '', $raw_gpm);
            /** 2.  Lấy chiều dài từ đầu đến vị trí dấu '/' */
            if (strpos($customer_job, '/') !== false) {
                $gpm_len =  strpos($raw_gpm, '/') - 3;
            } else {
                $gpm_len = strlen($raw_gpm);
            }

            /** 3.  Lấy số ký tự chữ số là GPM */
            $gpm_clear = preg_replace('/[^0-9]/', '', $raw_gpm);
            /** 4. Kiểm tra chắc chắn đúng các ký tự dạng số chưa */
            $gpm = is_numeric(substr($gpm_clear, 0, $gpm_len)) ? substr($gpm_clear, 0, $gpm_len) : '';
        }


        return $gpm;
    }

    public function dateFormat($date, $format=null )
    {
        if ($format == null ) $format = 'Y-m-d';
        return date($format, strtotime($date));
    }

    public function minArr(array $arr)
    {
        return min(array_diff(array_map('intval', $arr), array(0)));
    }

    // get machine json
    public function getMachine()
    {
        // tilte 
        $this->_data['title'] = 'HTL Master  Machine';

        // init
        if ($this->form_type == 'hfe') {
            $machine_arr[] = array();
        } else {
            $machine_arr[] = array('value' => '', 'text' => 'Chọn Máy');
        }

        $order_type_local_arr[] = array('value' => 'NORMAL', 'text' => 'Đơn thường');

        // load models
        $this->load->model('htl_master_machine', 'machine');
        $this->load->model('htl_master_order_type_local', 'order_type_local');

        // get machine
        $machineData = $this->machine->readOptions(array('form_type' => $this->form_type));
        foreach ($machineData as $machineItem) {
            $machine_arr[] = array('value' => $machineItem['machine'], 'text' => $machineItem['machine_name']);
        }

        // get order type local
        $orderTypeLocal = $this->order_type_local->read();
        foreach ($orderTypeLocal as $local) {
            $order_type_local_arr[] = array('value' => $local['order_type_local'], 'text' => $local['descriptions']);
        }

        // result
        $this->_data['results'] = array(
            'status' => true,
            'message' => 'Success',
            'machine_json' => $machine_arr,
            'order_type_json' => $order_type_local_arr

        );

        // results
        echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE); exit();
    }

    // get machine speed
    public function getMachineSpeed()
    {
        // tilte 
        $this->_data['title'] = 'HTL Machine Speed';

        // init 
        $machine_speed = 0;

        // set post data
        $dataPost = $this->input->post('data');
        // $dataPost = '{"machine":"ATMA"}';

        // check
        $dataPost = json_decode($dataPost, true);
        if (empty($dataPost)) {
            $this->_data['results'] = array(
                "status" => false,
                "message" => "Không nhận được dữ liệu POST!!!"
            );
        } else {

            // get machine
            $machine = trim($dataPost['machine']);

            // load models
            $this->load->model('htl_master_machine', 'machine');

            // get data
            $where = array('form_type' => $this->form_type, 'machine' => $machine);
            if ($this->machine->isAlreadyExist($where)) {
                $machineItem = $this->machine->readItem($where);
                $machine_speed = $machineItem['machine_speed'];
            }
        }


        // result
        $this->_data['results'] = array(
            'status' => true,
            'message' => 'Success',
            'machine_speed' => $machine_speed
        );

        // results
        echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // get Pattern No
    public function patternData()
    {
        // tilte 
        $this->_data['title'] = 'HTL Master Pattern';

        // init 
        $machine_speed = 0;

        // set post data
        $dataPost = $this->input->post('data');
        // $dataPost = '{"machine":"ATMA"}';

        // check
        $dataPost = json_decode($dataPost, true);
        if (empty($dataPost)) {
            $this->_data['results'] = array(
                "status" => false,
                "message" => "Không nhận được dữ liệu POST!!!"
            );
        } else {

            // get machine
            $pattern_no = trim($dataPost['pattern_no']);

            // load models
            $this->load->model('htl_master_pattern', 'pattern');

            // get data
            $data = array();
            $where = array('pattern_no' => $pattern_no);
            if ($this->pattern->isAlreadyExist($where)) {
                $data = $this->pattern->readItem($where);
            }
        }


        // result
        if (empty($data)) {
            $status = false;
            $message = 'Error';
        } else {
            $status = true;
            $message = 'Success';
        }

        $this->_data['results'] = array(
            'status' => $status,
            'message' => $message,
            'patternData' => $data
        );

        // results
        echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // check exist order
    public function checkDataExist()
    {

        $results = array();
        $status = false;
        $message = 'Không check được dữ liệu';

        // set post data
        $dataPost = $this->input->post('data');
        // $dataPost = '{"input_data":"OH2202-1284"}';

        // check
        if (empty($dataPost) ) {
            $message = "Không nhận được dữ liệu POST!!!";
        } else {
            
            $dataPost = json_decode($dataPost, true);

            // models
            $this->load->model('htl_prepress_oh', 'prepress_oh');
            $this->load->model('htl_master_item', 'master_data');
            $this->load->model('htl_master_process', 'master_process');

            // get PO NO
            $input_data = trim($dataPost['input_data']);

            // get soline
            $soline_arr = $this->prepress_oh->readOptions(array('oh' => $input_data));
            if (empty($soline_arr) ) {
                $message = "Không tồn tại OH: $input_data trong hệ thống. ";
            } else {
                $material_code_check = '';
                
                foreach ($soline_arr as $soline_item) {
                    
                    // get order_number and line_number
                    $so_line = trim($soline_item['so_line']);
                    $so_line_check = explode('-', $so_line);
                    $order_number = $so_line_check[0];
                    $line_number = $so_line_check[1];
                    
                    // check 
                    $exist1 = (!$this->automail->checkSOLine($order_number, $line_number)) ? false : true;
                    $exist2 = (!$this->automail_closed->checkSOLine($order_number, $line_number)) ? false : true;
    
                    // check automail data
                    if ($exist1 == false && $exist2 == false ) {
                        $message = "SOLine " . $so_line . " KHÔNG tồn tại trong Automail";
                        break;
                    } else {
    
                        if ($exist1 == true ) {
                            $automail_item = $this->automail->readSOLine($order_number, $line_number);
                        } else {
                            $automail_item = $this->automail_closed->readSOLine($order_number, $line_number);
                        }
                        
                        $internal_item = trim($automail_item[0]['ITEM']);
                        $where = array('internal_item' => $internal_item);
                        if (!$this->master_data->isAlreadyExist($where) ) {
                            $message = "Item: $internal_item ($so_line) KHÔNG tồn tại trong Master File";
                            $status = false;
                            break;
                        } else {
                            // Kiểm tra các material code có giống nhau không
                            $masterItem = $this->master_data->readItem($where );
                            $material_code = $masterItem['material_code'];

                            // Lần đầu tiên gán $material code check = material code
                            if (empty($material_code_check) ) {
                                $material_code_check = $material_code;
                            } 

                            if ($material_code !== $material_code_check ) {
                                $message = 'Các Material Code không giống nhau ' . $material_code . ', ' . $material_code_check ;
                                $status = false;
                                break;
                            }


                            // Kiểm tra process
                            if (!$this->master_process->isAlreadyExist($where) ) {
                                $message = "Item: $internal_item ($so_line) KHÔNG tồn tại trong Master Process";
                                $status = false;
                                break;
                            } else {
                                // results
                                $status = true;
                                $message = "Check data success";
                            }
                        }
                    }
                }
            }
            
        }

        $results = array(
            'status' => $status,
            'message' => $message
        );

        // result
        echo json_encode($results, JSON_UNESCAPED_UNICODE);
    }

    // check already exist (ordered)
    public function isAlreadyExist()
    {
        // load models
        $this->load->model('htl_save_po', 'save_po');

        // set post data
        $dataPost = isset($_POST["data"]) ? $_POST["data"] : '';

        // check
        $dataPost = json_decode($dataPost, true);
        if (empty($dataPost)) {
            $this->_data['results'] = array(
                "status" => false,
                "message" => "Không nhận được dữ liệu POST!"
            );
        } else {

            $po_no = trim($dataPost['input_data']);

            // * check
            if ($this->save_po->isAlreadyExist(array('po_no' => $po_no))) {

                $this->_data['results'] = array(
                    "status" => true,
                    "message" => "OH: $po_no đã làm lệnh. Bạn có muốn chỉnh sửa?",
                    "po_no_edit" => $po_no
                );
            } else {
                // results true
                $this->_data['results'] = array(
                    "status" => false,
                    "message" => "OK. Đơn chưa làm lệnh"
                );
            }
        }

        // result
        echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
    }

    // fomular
    public function setupTime($form_type, $process_code, $setup_time_0)
    {
        $setup_time = 0;

        if ($form_type == 'htl') {
            $setup_time = $setup_time_0;
        } else if ($form_type == 'hfe') {

            // models
            $this->load->model('htl_master_setup_time', 'setup_time');

            // get type
            $type = '';
            if (stripos($process_code, 'BACK') !== false) {
                $type = 'BACK%';
            } else if (stripos($process_code, 'BLACK') !== false) {
                $type = 'BLACK';
            } else if (stripos($process_code, 'WHITE') !== false) {
                $type = 'WHITE';
            } else if (stripos($process_code, 'SILVER') !== false) {
                $type = 'SILVER';
            } else {
                $type = 'MIX';
            }

            // get data
            $where = array('ink_type like' => $type);
            if ($this->setup_time->isAlreadyExist($where)) {
                $setupTimeItem = $this->setup_time->readItem($where);
                $setup_time = $setupTimeItem['alignment_times'];
            }
        }

        return $setup_time;
    }

    public function allowanceScrap($form_type, $scrap, $qty_limit, $count_color)
    {
        $result = 0;
        if ($form_type == 'htl') {
            $result = $scrap;
        } else if ($form_type == 'hfe') {
            // models
            $this->load->model('htl_master_scrap', 'scrap');
            // scrap data
            $scrapData = $this->scrap->readOptions(array('qty_limit <=' => $qty_limit), 'qty_limit');
            foreach ($scrapData as $scrapItem) {
                if ($qty_limit <= $scrapItem['qty_limit']) {
                    if ($count_color == 1) {
                        $result = $scrapItem['scrap_color_1'];
                    } else if ($count_color == 2) {
                        $result = $scrapItem['scrap_color_2'];
                    } else if ($count_color == 3) {
                        $result = $scrapItem['scrap_color_3'];
                    } else if ($count_color == 4) {
                        $result = $scrapItem['scrap_color_4'];
                    } else if ($count_color == 5) {
                        $result = $scrapItem['scrap_color_5'];
                    }
                }
            }
        }


        return $result;
    }

    public function mathDate($original_date)
    {
        // default
        $original_date = $this->dateFormat($original_date);
        $original_date = date("Y-m-d", strtotime($original_date . "-1 days"));

        // models
        $this->load->model('holidays');

        // get year
        $year = date("Y");
        $year_check = $year . "%";

        // exist
        $where = array('holiday_date like ' => $year_check);
        $where_2 = array('holiday_date like ' => $year_check, 'holiday_name_group' => 'LunaNewYear%');
        if ($this->holidays->isAlreadyExist($where)) {
            $holiday_list = $this->holidays->readOptions($where, 'holiday_date');
            foreach ($holiday_list as $holiday) {
                $holiday_date = $this->dateFormat($holiday['holiday_date']);
                if ($original_date == $holiday_date) {
                    if (stripos($holiday['holiday_name_group'], 'LunaNewYear') !== false) {
                        $holiday_luna = $this->holidays->readItem($where_2, 'holiday_date');
                        $holiday_date = $this->dateFormat($holiday_luna['holiday_date']);
                        $original_date = date("Y-m-d", strtotime($holiday_date . "-1 days"));
                    } else {
                        $original_date = date("Y-m-d", strtotime($original_date . "-1 days"));
                    }
                }
            }
        }

        // sunday
        if (date("l", strtotime($original_date)) == "Sunday") {
            $original_date = date("Y-m-d", strtotime($original_date . "-1 days"));
        }

        // result
        return $original_date;
    }


    //handle input data
    public function handle()
    {
        // get title, production line
        $this->_data['title'] = 'HTL Orders';

        // init 
        $internalItemArr = array();
        $customerItemArr = array();

        $automailData = array();
        $formData = array();
        $masterData = array();
        $processData = array();
        $processDataOK = array();

        $ordered_date = '';
        $request_date = '';
        $promise_date = '';

        // get orders ------------------------------------------------------------------------------------------------------------
        $orders = trim($this->input->get('orders'));
        $edit = $this->input->get('edit');


        // Load models ------------------------------------------------------------------------------------------------------------
        $this->load->model('htl_master_item', 'master_item');
        $this->load->model('htl_master_process', 'master_process');
        $this->load->model('htl_save_po', 'save_po');
        $this->load->model('htl_prepress_oh', 'prepress_oh');


        // get po date ------------------------------------------------------------------------------------------------------------
        $po_date = date('Y-m-d');
        $machine = '';
        if ($edit === true) {
            $poItem = $this->save_po->readItem(array('po_no' => $orders));
            $po_date = $poItem['po_date'];

            // get data form
            $machine = $poItem['machine'];
        }

        // get soline list
        $data = $this->prepress_oh->readOptions(array('oh' => $orders));


        // get automail data
        $index = 0;
        $qty_total = 0;
        $ordered_date = '';
        $request_date = '';
        $promise_date = '';
        foreach ($data as $key => $value) {

            $index++;

            $so_line = trim($value['so_line']);
            $soline_arr = explode('-', $so_line);
            $order_number = $soline_arr[0];
            $line_number = $soline_arr[1];

            // get automail data
            $automail = $this->automail->readItem(array('ORDER_NUMBER' => $order_number, 'LINE_NUMBER' => $line_number));
            // check 
            if (empty($automail) ) {
                $automail = $this->automail_closed->readItem(array('ORDER_NUMBER' => $order_number, 'LINE_NUMBER' => $line_number));
            };
            // get item
            $internal_item = trim($automail['ITEM']);
            $customer_item = $automail['CUSTOMER_ITEM'];

            // get internal item arr
            if (!in_array($internal_item, $internalItemArr) ) {
                $internalItemArr[] = $internal_item;
            }

            // get customer item arr
            if (!in_array($customer_item, $customerItemArr)) {
                $customerItemArr[] = $customer_item;
            }

            

            // get data for FORM
            if (empty($request_date)) {
                $ordered_date = $this->dateFormat($automail['ORDERED_DATE']);
                $request_date = $this->dateFormat($automail['REQUEST_DATE']);
                $promise_date = $this->dateFormat($automail['PROMISE_DATE']);
            } else {
                $request_date_check = $this->dateFormat($automail['REQUEST_DATE']);
                if (strtotime($request_date) > strtotime($request_date_check)) {
                    $ordered_date = $this->dateFormat($automail['ORDERED_DATE']);
                    $request_date = $this->dateFormat($automail['REQUEST_DATE']);
                    $promise_date = $this->dateFormat($automail['PROMISE_DATE']);
                }
            }

            // echo "<br>ordered_date: $ordered_date -- request_date: $request_date -- promise_date: $promise_date  ";



            // get data
            $productionItem = $this->getProductionItem($internal_item);
            $UOMCost = trim($productionItem['UOMCost']);
            $qty = ($UOMCost == 'SET') ? (int)$automail['QTY'] * 2 : (int)$automail['QTY'];

            $qty_total += $qty;
            $sold_to_customer = trim($automail['SOLD_TO_CUSTOMER']);
            $ship_to_customer = trim($automail['SHIP_TO_CUSTOMER']);
            $bill_to_customer = trim($automail['BILL_TO_CUSTOMER']);
            $packing_instr = trim($automail['PACKING_INSTR']);
            $packing_instructions = trim($automail['PACKING_INSTRUCTIONS']);
            $attachment = trim($automail['VIRABLE_BREAKDOWN_INSTRUCTIONS']);

            // Encode: htmlentities($materialDes2, ENT_QUOTES, 'UTF-8'); Decode: html_entity_decode()
            $checkQUOTES = array($sold_to_customer, $ship_to_customer, $bill_to_customer, $packing_instr, $packing_instructions, $attachment);
            foreach ($checkQUOTES as $keyCheck => $valueChecked) {
                if (!empty($valueChecked)) {
                    $valueChecked = htmlentities($valueChecked, ENT_QUOTES, 'UTF-8');
                }

                if ($keyCheck == 0) {
                    $sold_to_customer = $valueChecked;
                }
                if ($keyCheck == 1) {
                    $ship_to_customer = $valueChecked;
                }
                if ($keyCheck == 2) {
                    $bill_to_customer = $valueChecked;
                }
                if ($keyCheck == 3) {
                    $packing_instr = $valueChecked;
                }
                if ($keyCheck == 4) {
                    $packing_instructions = $valueChecked;
                }
                if ($keyCheck == 5) {
                    $attachment = $valueChecked;
                }
            }

            // set data
            $automailData[] = array(
                'id' => $index,
                'data' => array(
                    $index,

                    $automail['ORDER_NUMBER'],
                    $automail['LINE_NUMBER'],
                    $so_line,
                    $qty,
                    $internal_item,

                    $automail['ORDERED_ITEM'],
                    $automail['CUSTOMER_ITEM'],
                    $sold_to_customer,
                    $this->dateFormat($automail['ORDERED_DATE']),
                    $this->dateFormat($automail['REQUEST_DATE']),

                    $this->dateFormat($automail['PROMISE_DATE']),
                    $ship_to_customer,
                    $bill_to_customer,
                    $automail['CS'],
                    $automail['ORDER_TYPE_NAME'],

                    $automail['FLOW_STATUS_CODE'],
                    $automail['PRODUCTION_METHOD'],
                    $packing_instr,
                    $packing_instructions,
                    $attachment,
                    $automail['CUSTOMER_JOB'],
                    $UOMCost
                )


            );
        }

        // get promise date finish
        $ordered_date = $this->mathDate($ordered_date);
        $request_date = $this->mathDate($request_date);
        $promise_date = $this->mathDate($promise_date);

        // check 
        if (empty($automailData)) {
            $results = array(
                'status' => false,
                'message' => 'Không có dữ liệu Automail'
            );
        } else {
            
            // set po data
            $countItem = count($internalItemArr);
            $countCustomerItem = count($customerItemArr);

            $po_internal_item = ($countItem >= 2) ? 'Item Gộp' : $internalItemArr[0];
            $po_customer_item = ($countCustomerItem >= 2) ? 'Item Gộp' : $customerItemArr[0];



            // MASTER ITEM --------------------------------------------------------------------------------------------------------------
                $indexM = 0;
                foreach ($internalItemArr as $keyI => $valueI ) {

                    $indexM++;
                    // get master data từ item đã chọn trên
                    $masterItem = $this->master_item->readItem(array('internal_item' => $valueI));
                    $masterData[] = array(
                        'id' => $indexM,
                        'data' => array(
                            $indexM,
                            $masterItem['internal_item'],
                            $masterItem['material_code'],
                            $masterItem['material_name'],
                            $masterItem['material_width'],
                            $masterItem['material_length'],
                            $masterItem['product_type'],
                            $masterItem['plan_type'],
                            $masterItem['scrap'],
                            $masterItem['remark_1'],
                            $masterItem['remark_2'],
                            $masterItem['remark_3']
                        )
                    );
                }

            // ----------------------------------------------------------------------------------------------------------------------

            

            /* 
                Đoạn code xử lý trường hợp các Item có các tiến trình hoàn toàn giống nhau ==> có 1 giao nhưng nhiều Item khác nhau 
                - Xử lý dữ liệu chỉ hiển thị 1 dao (thay vì nhiều như trước)
                - Hiển thị: Item gộp chứ k phải các Item như đã có
            */ 

                // Lấy tất cả process code của các Item
                $process_code_of_item_arr = array();
                if ($countItem >= 2 ) {
                    foreach ($internalItemArr as $item ) {
                        $processItem = $this->master_process->readOptions(array('internal_item' => $item), 'order');
                        $process_code_of_item_arr[$item] = array();
                        foreach ($processItem as $pr ) {
                            $process_code_of_item_arr[$item][] = $pr['process_code'];
                        }
                    }
                }

                $process_code_of_item_arr_2 = $process_code_of_item_arr;

                // kiểm tra xem có item nào giống tất cả process code với nhau không ==> giống nhau thì là trùng DAO, loại bỏ

                foreach ($process_code_of_item_arr as $item_p => $process_code_of_item ) {
                    
                    $count_1 = count($process_code_of_item);

                    foreach ($process_code_of_item_arr_2 as $item_p_2 => $process_code_of_item_check ) {
                        
                        $count_2 = count($process_code_of_item_check);
                        
                        // cho biến đếm, nếu đếm = count mảng thì tức là trùng
                        $count_same = 0;
                        
                        // 2 mảng cùng độ dài và khác item so sánh
                        if ( ($count_1 == $count_2) && ($item_p !== $item_p_2 ) ) {
                            
                            for ($i=0; $i<$count_1; $i++ ) {
                                // Nếu process_code_diff = true => có sự khác nhau, dừng for
                                if ($process_code_of_item[$i] == $process_code_of_item_check[$i] ) {
                                    $count_same++;
                                } else {
                                    // Có 1 trường hợp sai ==> dừng
                                    break;
                                }
                            }

                        }

                        // Đây là trường hợp trùng các process
                        if ($count_same == $count_2 ) {

                            // delete element is item_p_2 (xóa 1 item trong mảng chính)
                            // unset($process_code_of_item_arr[$item_p]);
                            unset($process_code_of_item_arr[$item_p_2]);
                            unset($process_code_of_item_arr_2[$item_p]);

                            // echo "here $process_code_diff -- item: $item_p_2 <br>";

                            // xóa trong mảng cần lấy dữ liệu
                            foreach ($internalItemArr as $keyItem => $itemVal ) {
                                if ($item_p_2 == $itemVal ) {
                                    unset($internalItemArr[$keyItem]);
                                    break;
                                };
                            }
                            
                        }


                    }
                }

            // -------------------------------------------------------------------------------------------

            // init var
            $process_pass_total = 0;
            $setup_time_total = 0;

            /* 
                Lấy ra Internal Item có số process lớn nhất làm chuẩn để chạy đầu tiên
            */ 
            
                $countProcess = 0;
                $film_number = 0;
                $internal_item_main = '';

                foreach ($internalItemArr as $key => $value) {

                    $processItem = $this->master_process->readOptions(array('internal_item' => $value));
                    $countProcess = count($processItem);

                    if ($key == 0) {
                        $internal_item_main = $value;
                        $key_main = $key;
                        // $film_number = $countProcess;
                    } else {
                        if ($countProcess > $film_number) {
                            $internal_item_main = $value;
                            $key_main = $key;
                        }
                    }

                    $film_number = $countProcess;

                }

                // xóa bỏ Item đã chọn này trong list item
                // unset($internalItemArr[$key_main]);

                

            // ----------------------------------------------------------------------------------------------------------------------
                // get master data từ item đã chọn trên
                $masterItem = $this->master_item->readItem(array('internal_item' => $internal_item_main));
            
                // get for Form
                $product_type = trim($masterItem['product_type']);
                $material_label_size = $masterItem['material_width'] . " x " . $masterItem['material_length'];
                $scrap = $masterItem['scrap'];
                $plan_type = trim(strtoupper($masterItem['plan_type']));

                // check FOD: qty_total + 50pcs
                if ($plan_type == 'FOD') {
                    $qty_total += 50;
                }

            /* 
                get Process header ------------------------------------------------------------------------------------------------
                Header Process hiển thị khi làm lệnh
            */ 
                // 1 - Thêm Header NO., PRINTING trước
                $headerSuffixArr = array('NO.', 'PRINTING');
                foreach ($headerSuffixArr as $header) {
                    $processHeader[] = $header;
                }

                // 2 - Thêm Item tiếp tục
                if (!empty($internalItemArr)) {
                    foreach ($internalItemArr as $val) {
                        $processHeader[] = $val;
                    }
                }

                // 3 - Thêm Đoạn phía sau: Pass....
                $headerSuffixArr = array('PASSES', 'FRAME', 'TIME', 'SHEET', 'INK USAGE');
                foreach ($headerSuffixArr as $header) {
                    $processHeader[] = $header;
                }


            // PROCESS ================================================================================================
                
                $indexP = 0;    
                
                // count header
                $countH = count($processHeader);

                foreach ($internalItemArr as $keyI => $valueI ) {
                    
                    $processItem = $this->master_process->readOptions(array('internal_item' => $valueI), 'order');
                    foreach ($processItem as $keyP => $valueP ) {

                        // reset
                        $processElement = array();
                        $check = false;
                        $key_change = -1;

                        // chuỗi process, frame, passes, setup time, setup sheet đều bằng nhau để so sánh
                        // $string_1 = $valueP['process'] . $valueP['frame'] . $valueP['passes'] . $valueP['setup_time'] . $valueP['setup_sheet'];
                        $string_1 = $valueP['process'] . $valueP['frame'];

                        if (empty($processData) ) {

                            foreach ($processHeader as $keyH => $valueH ) {
                                
                                if ($valueH == 'NO.' ) {

                                    $indexP++;

                                    $processElement[] = $indexP;

                                } else if ($valueH == 'PRINTING' ) {
                                    $processElement[] = $valueP['process'];
                                } else if ($valueH == 'PASSES' ) {
                                    $processElement[] = $valueP['passes'];

                                    // other total
                                    $process_pass_total += $valueP['passes'];

                                } else if ($valueH == 'FRAME' ) {
                                    $processElement[] = $valueP['frame'];
                                } else if ($valueH == 'TIME' ) {
                                    
                                    // setup time
                                    $setup_time = $this->setupTime($this->form_type, $valueP['process_code'], $valueP['setup_time']);
                                    $processElement[] = $setup_time;

                                    // other total
                                    $setup_time_total += $setup_time;

                                } else if ($valueH == 'SHEET' ) {
                                    $processElement[] = $valueP['setup_sheet'];
                                } else if ($valueH == 'INK USAGE' ) {
                                    $processElement[] = '';
                                } else {
                                    // các item
                                    if ($valueH == $valueI ) {
                                        $processElement[] = $valueP['process_code'];
                                    } else {
                                        $processElement[] = '';
                                    }
                                }


                            }

                            $processData[] = $processElement;
                            
                        } else {
                            
                            foreach ($processData as $keyD => $valueD ) {

                                // chuỗi process, frame, passes, setup time, setup sheet đều bằng nhau để so sánh
                                // $string_2 = $valueD[1] . $valueD[$countH-4] . $valueD[$countH-5] . $valueD[$countH-3] . $valueD[$countH-2];
                                $string_2 = $valueD[1] . $valueD[$countH-4];
                                if ( $string_1 == $string_2 ) {

                                    foreach ($processHeader as $keyH => $valueH ) {

                                        if ($valueH == $valueI ) {
                                            if (isset($processData[$keyD][$keyH]) && !empty($processData[$keyD][$keyH]) ) {
                                                $check = false;
                                            } else {
                                                $processData[$keyD][$keyH] = $valueP['process_code'];
                                                $check = true;
                                            }
                                            
                                            break;
                                        }

                                    }

                                    break;
                                    
                                } else {

                                    $check = false;

                                    if ($valueP['process'] == $valueD[1] ) {
                                        $key_change = $keyD+1;
                                        break;
                                    } else {
                                        $key_change = -1;
                                    }

                                    
                                }


                                    
                            }



                            // trường hợp này là Printing mới hoặc Print thứ 2 của 1 lớp (trùng)
                            // Thêm 1 dòng mới
                            if ($check == false ) {

                                $processElement = array();
                                foreach ($processHeader as $keyH => $valueH ) {
                                    if ($valueH == 'NO.' ) {
                                        
                                        $indexP++;
    
                                        $processElement[] = $indexP;
                                    } else if ($valueH == 'PRINTING' ) {
                                        $processElement[] = $valueP['process'];
                                    } else if ($valueH == 'PASSES' ) {
                                        $processElement[] = $valueP['passes'];
    
                                        // other total
                                        $process_pass_total += $valueP['passes'];
    
                                    } else if ($valueH == 'FRAME' ) {
                                        $processElement[] = $valueP['frame'];
                                    } else if ($valueH == 'TIME' ) {
                                        
                                        // setup time
                                        $setup_time = $this->setupTime($this->form_type, $valueP['process_code'], $valueP['setup_time']);
                                        $processElement[] = $setup_time;
    
                                        // other total
                                        $setup_time_total += $setup_time;
    
                                    } else if ($valueH == 'SHEET' ) {
                                        $processElement[] = $valueP['setup_sheet'];
                                    } else if ($valueH == 'INK USAGE' ) {
                                        $processElement[] = '';
                                    } else {
                                        // các item
                                        if ($valueH == $valueI ) {
                                            $processElement[] = $valueP['process_code'];
                                        } else {
                                            $processElement[] = '';
                                        }
                                    }
    
    
                                    
                                }

                                // // // Đây là trường hợp có 2 lớp trùng nhau trong 1 item
                                // // if ($key_change !== -1 ) {

                                // //     $count_process = count($processData);
                                // //     $process_item_save = array();
                                // //     $process_item_tmp = array();

                                // //     for ($count=$key_change; $count<$count_process; $count++ ) {

                                // //         $process_item_save = $processData[$count];
                                // //         if ($count == $key_change ) {
                                // //             $processData[$count] = $processElement;
                                // //         } else {
                                // //             $processData[$count] = $process_item_tmp;
                                // //         }

                                // //         $process_item_tmp = $process_item_save;

                                // //         if ($count == $count_process ) {
                                // //             $processData[$count+1] = $process_item_tmp;
                                // //         }
                                        

                                // //     }

                                // //     $key_change = -1;

                                // // } else {
                                // //     $processData[] = $processElement;
                                // // }


                                $processData[] = $processElement;
    
                                
                            }
                            

                        }
                        

                        

                    }


                    

                }


                /*
                    get files shared by prepress =======================================================
                */ 
                
                    $prepressDir = $this->prepress_dir;
                    $prepressData = $this->getPrepressData($orders, $prepressDir);
                    $prepressOHData = $prepressData['oh_data'];
                    $prepressInks = $prepressData['ink_usage'];    
                    
            
                // set processData ok
                    $indexD = 0;
                    foreach ($processData as $keyD => $valueD ) {

                        $indexD++;
                        $countD = count($valueD);

                        $valueD[$countD-1] = (isset($prepressInks['S'.$indexD])) ? $prepressInks['S'.$indexD] : '';

                        $processDataOK[] = array(
                            'id' => $indexD,
                            'data' => $valueD
                        );

                    }


            // ================================================================================================
            


            
                
            // =====================================================================================

            // cal count color, setup sheet total
            $count_color = 0;
            $setup_sheet_total = 0;
            $count_process = count($processDataOK[0]['data']) - 1;

            foreach ($processDataOK as $keyP => $valP) {

                // calculate count_color 
                if (stripos($valP['data'][1], 'INK') !== false) {
                    $count_color++;
                }

                // calculate setup_sheet_total. Do vị trí mới thêm là INK USAGE nên vị trí Sheet là count -2 (kế cuối)
                $setup_sheet = (($valP['data'][$count_process-1] == '') || ($valP['data'][$count_process-2] == null)) ? 0 :  $valP['data'][$count_process-1];
                $setup_sheet_total += $setup_sheet;
                
            }


            $count_color = ($count_color == 0) ? 1 : $count_color; // set defaul = 0
            $allowance_scrap = $this->allowanceScrap($this->form_type, $scrap, $qty_total, $count_color);
        }


        // check empty
        if (empty($automailData)) {
            $results = array(
                'status' => false,
                'message' => 'Không có dữ liệu Automail'
            );
        } else if (empty($masterData)) {
            $results = array(
                'status' => false,
                'message' => 'Không có dữ liệu Master Data'
            );
        } else if (empty($processDataOK)) {
            $results = array(
                'status' => false,
                'message' => 'Không có dữ liệu Process'
            );
        } else {

            $formData = array(
                'po_date' => $this->dateFormat($po_date),
                'po_no' => $orders,
                'product_type' => $product_type,
                'po_internal_item' => $po_internal_item,
                'po_customer_item' => $po_customer_item,

                'machine' => $machine,
                'ordered_date' => $ordered_date,
                'request_date' => $request_date,
                'promise_date' => $promise_date,
                'qty_total' => $qty_total,

                'rbo' => $sold_to_customer,
                'bill_to_customer' => $bill_to_customer,
                'ship_to_customer' => $ship_to_customer,

                'process_pass_total' => $process_pass_total,
                'film_number' => $film_number,
                'setup_time_total' => $setup_time_total,
                'color_total' => $count_color,
                'allowance_scrap' => $allowance_scrap,

                'setup_sheet_total' => $setup_sheet_total,
                'plan_type' => $plan_type,
                'uom_cost' => $UOMCost

            );

            // results
            $results = array(
                'status' => true,
                'message' => 'Success',
                'automailData' => $automailData,
                'masterData' => $masterData,
                'processData' => $processDataOK,
                'formData' => $formData,
                'processHeader' => $processHeader,
                'prepressOHData' => $prepressOHData
            );
        }


        // results
        $this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
        $this->load->view('htl/handle', $this->_data);
    }

    public function detacheLabelSize($label_size, $ups_label, $pattern_no)
    {

        $results = array();

        if ($this->form_type == 'htl') {
            if (strpos($label_size, '+') !== false) {

                $label_size_arr = explode('+', $label_size);
                $ups_label_arr = explode('+', $ups_label);
                foreach ($label_size_arr as $key => $label_size_line) {
                    // label size
                    $label_size_line_arr = explode('*', $label_size_line);
                    $width = $label_size_line_arr[0];
                    $length = $label_size_line_arr[0];

                    // ups
                    $ups_label_line_ok = $ups_label_arr[$key];
                    $ups_label_line_arr = explode('*', $ups_label_line_ok);
                    $ups_width = $ups_label_line_arr[0];
                    $ups_length = $ups_label_line_arr[1];
                    $ups = (float)$ups_width * (float)$ups_length;

                    // results
                    $results[] = array(
                        'label_size' => $label_size_line,
                        'width' => $width,
                        'length' => $length,

                        'ups_label' => $ups_label_line_ok,
                        'ups_width' => $ups_width,
                        'ups_length' => $ups_length,
                        'ups' => $ups,
                    );
                }
            } else {

                // label size
                $label_size_line_arr = explode('*', $label_size);
                $width = $label_size_line_arr[0];
                $length = $label_size_line_arr[0];

                // ups
                $ups_label_line_arr = explode('*', $ups_label);
                $ups_width = $ups_label_line_arr[0];
                $ups_length = $ups_label_line_arr[1];
                $ups = (float)$ups_width * (float)$ups_length;

                // results
                $results[] = array(
                    'label_size' => $label_size,
                    'width' => $width,
                    'length' => $length,
                    'ups_label' => $ups_label,
                    'ups_width' => $ups_width,
                    'ups_length' => $ups_length,
                    'ups' => $ups,
                );
            }
        } else if ($this->form_type == 'hfe') {

            // models
            $this->load->model('htl_master_pattern', 'pattern');
            // get data
            $patternItem = $this->pattern->readItem(array('pattern_no' => $pattern_no));
            $width = $patternItem['width'];
            $length = $patternItem['length'];
            $ups_width = $patternItem['ups_width'];
            $ups_length = $patternItem['ups_length'];
            $label_size = $patternItem['label_size'];
            $ups = $patternItem['ups'];
            $ups_label = $patternItem['ups_label'];

            // results
            $results[] = array(
                'label_size' => $label_size,
                'width' => $width,
                'length' => $length,
                'ups_label' => $ups_label,
                'ups_width' => $ups_width,
                'ups_length' => $ups_length,
                'ups' => $ups,
            );
        }

        return $results;

    }


    public function getProductionItem($internal_item)
    {

        // models
        $this->load->model('avery_tbl_productline_item', 'productline_item');

        // get data
        $data = $this->productline_item->readItem(array('Item' => $internal_item));

        // results
        return $data;

    }

    // save orders
    public function saveOrders()
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');

        // init var
        $formData = array();
        $results = array();
        $qty_total = 0;

        $save_po_data = array();
        $save_po_soline_data = array();
        $internal_item_list = array();

        $results = FALSE;
        $status = false;

        // GET DATA ------------------------------------------------------------------------------------------------------------------------------
        $allData = $_POST["data"];
        // $allData = '{"formData":{"po_date":"2022-02-23","po_no":"OH2202-1178","product_type":"AGI","customer_item":"Item Gộp","label_size":"91*20+105*22+107*22","sheet_batching":"52","ups_label":"26*3+23*1+2*23","original_need":"42.71","sheet_packing":52,"setup_sheet_total":8,"sheet_pass_total":255,"paper_compensate_total":1,"sheet_total":64,"pattern":"","machine":"ATMA","promise_date":"2022-02-22","qty_total":6278,"internal_item":"Item Gộp","film_number":2,"process_pass_total":4,"running_time":"0.7","setup_time_total":45,"color_total":1,"allowance_scrap":"0.05","designed_scrap":"0.2","setup_scrap":"0.2","scrap_total":"0.5","order_type_local":"NORMAL","ups_total":147,"plan_type":"","uom_cost":"EA","rbo":"OLD NAVY / GAP INC","bill_to_customer":"YAKJIN TRADING CORP","ship_to_customer":"YAKJIN VIETNAM CO LTD","po_remark_1":"","po_remark_2":""},"results":{"status":true,"message":"Success","automailData":[{"id":1,"data":[1,"69175848","1","69175848-1",3387,"ATV609937","ON-291336-HTL-CG1C-GLB","ON-291336-HTL-CG1C-GLB","OLD NAVY / GAP INC","2022-02-18","2022-03-02","2022-03-04","YAKJIN VIETNAM CO LTD","YAKJIN TRADING CORP","Su, Jenna","VN URG","PRODUCTION_OPEN","HTL - Printed Adhesive","","",";  Size  :  Qty ;  XS  :  168 ;  S  :  503 ;  M  :  770 ;  L  :  1049 ;  XL  :  607 ;  XXL  :  137 ;  L+  :  50 ;  XL+  :  50 ;  XXL+  :  53 ;    :  Total  :  3387 ;   ^"," ","EA"]},{"id":2,"data":[2,"69176010","1","69176010-1",102,"ATV609937","ON-291336-HTL-CG1C-GLB","ON-291336-HTL-CG1C-GLB","OLD NAVY / GAP INC","2022-02-18","2022-03-02","2022-03-04","YAKJIN VIETNAM CO LTD","YAKJIN TRADING CORP","Su, Jenna","VN SAM","PRODUCTION_OPEN","HTL - Printed Adhesive","HANG MAU SO# 69175848","HANG MAU SO# 69175848",";  Size  :  Qty(loss 3%) ;  XS  :  5 ;  S  :  15 ;  M  :  23 ;  L  :  31 ;  XL  :  18 ;  XXL  :  4 ;  L+  :  2 ;  XL+  :  2 ;  XXL+  :  2 ;    :  Total  :  102 ;   ^"," ","EA"]},{"id":3,"data":[3,"69176061","1","69176061-1",144,"ATV609937","ON-291336-HTL-CG1C-GLB","ON-291336-HTL-CG1C-GLB","OLD NAVY / GAP INC","2022-02-18","2022-03-02","2022-03-04","YAKJIN TRADING CORP","YAKJIN TRADING CORP","Su, Jenna","VN SAM","PRODUCTION_OPEN","HTL - Printed Adhesive","LAY SAMPLE 6PCS/SKU LAM TRIM CARD + 10PCS/SKU LAM SAMPLE- HANG MAU SO# 69175848","LAY SAMPLE 6PCS/SKU LAM TRIM CARD + 10PCS/SKU LAM SAMPLE- HANG MAU SO# 69175848",";  Size  :  Qty ;  XS  :  16 ;  S  :  16 ;  M  :  16 ;  L  :  16 ;  XL  :  16 ;  XXL  :  16 ;  L+  :  16 ;  XL+  :  16 ;  XXL+  :  16 ;    :  Total  :  144 ;   ^"," ","EA"]},{"id":4,"data":[4,"69197353","1","69197353-1",967,"ATV608798","ON-291323-HTL-CG1C-CDA","ON-291323-HTL-CG1C-CDA","OLD NAVY / GAP INC","2022-02-18","2022-03-04","2022-03-05","YAKJIN VIETNAM CO LTD","YAKJIN TRADING CORP","Phan, Jane","VN GEN","PRODUCTION_OPEN","HTL - Printed Adhesive","","",";  Size  :  Qty ;  XS  :  50 ;  S  :  50 ;  M  :  255 ;  L  :  236 ;  XL  :  173 ;  XXL  :  53 ;  XXL+  :  50 ;  XXXL+  :  50 ;  XXXXL+  :  50 ;    :  Total  :  967 ;   ^"," ","EA"]},{"id":5,"data":[5,"69197361","1","69197361-1",631,"ATV608798","ON-291323-HTL-CG1C-CDA","ON-291323-HTL-CG1C-CDA","OLD NAVY / GAP INC","2022-02-18","2022-03-04","2022-03-05","YAKJIN VIETNAM CO LTD","YAKJIN TRADING CORP","Phan, Jane","VN GEN","PRODUCTION_OPEN","HTL - Printed Adhesive","","",";  Size  :  Qty ;  XS  :  50 ;  S  :  81 ;  M  :  130 ;  L  :  100 ;  XL  :  70 ;  XXL  :  50 ;  XXL+  :  50 ;  XXXL+  :  50 ;  XXXXL+  :  50 ;    :  Total  :  631 ;   ^"," ","EA"]},{"id":6,"data":[6,"69197362","1","69197362-1",962,"ATV608798","ON-291323-HTL-CG1C-CDA","ON-291323-HTL-CG1C-CDA","OLD NAVY / GAP INC","2022-02-18","2022-03-04","2022-03-05","YAKJIN VIETNAM CO LTD","YAKJIN TRADING CORP","Phan, Jane","VN GEN","PRODUCTION_OPEN","HTL - Printed Adhesive","","",";  Size  :  Qty ;  XS  :  50 ;  S  :  146 ;  M  :  255 ;  L  :  186 ;  XL  :  125 ;  XXL  :  50 ;  XXL+  :  50 ;  XXXL+  :  50 ;  XXXXL+  :  50 ;    :  Total  :  962 ;   ^"," ","EA"]},{"id":7,"data":[7,"69197563","1","69197563-1",32,"ATV608798","ON-291323-HTL-CG1C-CDA","ON-291323-HTL-CG1C-CDA","OLD NAVY / GAP INC","2022-02-18","2022-03-04","2022-03-05","YAKJIN VIETNAM CO LTD","YAKJIN TRADING CORP","Phan, Jane","VN SAM","PRODUCTION_OPEN","HTL - Printed Adhesive","","",";  Size  :  Qty(loss 3%) ;  XS  :  2 ;  S  :  2 ;  M  :  8 ;  L  :  7 ;  XL  :  5 ;  XXL  :  2 ;  XXL+  :  2 ;  XXXL+  :  2 ;  XXXXL+  :  2 ;    :  Total  :  32 ;   ^"," ","EA"]},{"id":8,"data":[8,"69197966","1","69197966-1",21,"ATV608798","ON-291323-HTL-CG1C-CDA","ON-291323-HTL-CG1C-CDA","OLD NAVY / GAP INC","2022-02-18","2022-03-04","2022-03-05","YAKJIN VIETNAM CO LTD","YAKJIN TRADING CORP","Phan, Jane","VN SAM","PRODUCTION_OPEN","HTL - Printed Adhesive","","",";  Size  :  Qty(loss 3%) ;  XS  :  2 ;  S  :  2 ;  M  :  4 ;  L  :  3 ;  XL  :  2 ;  XXL  :  2 ;  XXL+  :  2 ;  XXXL+  :  2 ;  XXXXL+  :  2 ;    :  Total  :  21 ;   ^"," ","EA"]},{"id":9,"data":[9,"69198136","1","69198136-1",32,"ATV608798","ON-291323-HTL-CG1C-CDA","ON-291323-HTL-CG1C-CDA","OLD NAVY / GAP INC","2022-02-18","2022-03-04","2022-03-05","YAKJIN VIETNAM CO LTD","YAKJIN TRADING CORP","Phan, Jane","VN SAM","PRODUCTION_OPEN","HTL - Printed Adhesive","","",";  Size  :  Qty(loss 3%) ;  XS  :  2 ;  S  :  4 ;  M  :  8 ;  L  :  6 ;  XL  :  4 ;  XXL  :  2 ;  XXL+  :  2 ;  XXXL+  :  2 ;  XXXXL+  :  2 ;    :  Total  :  32 ;   ^"," ","EA"]}],"masterData":[{"id":1,"data":[1,"ATV609937","HT-A00001-550*700","PET","550","700","AGI","","0.05","","",""]},{"id":2,"data":[2,"ATV608798","HT-A00001-550*700","PET","550","700","AGI","","0.05","","",""]}],"processData":[{"id":1,"data":[1,"INK","CG1C","CG1C","2","90T","28","3","15611.245826;"]},{"id":2,"data":[2,"AG","AGI","AGI","2","48T","17","5","32570.257751;"]}],"formData":{"po_date":"2022-02-23","po_no":"OH2202-1178","product_type":"AGI","po_internal_item":"Item Gộp","po_customer_item":"Item Gộp","machine":"","ordered_date":"2022-02-08","request_date":"2022-02-19","promise_date":"2022-02-22","qty_total":6278,"rbo":"OLD NAVY / GAP INC","bill_to_customer":"YAKJIN TRADING CORP","ship_to_customer":"YAKJIN VIETNAM CO LTD","process_pass_total":4,"film_number":2,"setup_time_total":45,"color_total":1,"allowance_scrap":"0.05","setup_sheet_total":8,"plan_type":"","uom_cost":"EA"},"processHeader":["NO.","PRINTING","ATV609937","ATV608798","PASSES","FRAME","TIME","SHEET","INK USAGE"],"prepressOHData":{"oh":"OH2202-1178","sheet_batching":"52","label_size":"91*20+105*22+107*22","ups":"26*3+23*1+2*23"}}}';
        $allData = json_decode($allData, true);

        // check empty ------------------------------------------------------------------------------------------------------------------------------

        if (empty($allData)) {
            $message = "Save data empty";
        } else {

            // get data details
            $formData = $allData['formData'];
            $results = $allData['results'];
            $formData2 = $results['formData'];

            // models ------------------------------------------------------------------------------------------------------------------------------
            $this->load->model('htl_save_po', 'save_po');
            $this->load->model('htl_save_po_soline', 'save_po_soline');
            $this->load->model('htl_save_po_process', 'save_process');
            $this->load->model('htl_setting_process', 'setting_process');
            $this->load->model('htl_prepress_oh', 'prepress_oh');
            $this->load->model('htl_save_po_pattern', 'save_pattern');
            $this->load->model('htl_master_item', 'master_item');
            $this->load->model('common_users', 'users');
            $this->load->model('htl_prepress_ink_usage', 'prepress_ink_usage');
            



            // data ------------------------------------------------------------------------------------------------------------------------------
            $automailData = $results['automailData'];
            $masterData = $results['masterData'];
            $processData = $results['processData'];
            $processHeader = $results['processHeader'];

            // $countH = count($processHeader) - 1;
            // unset($processHeader[$countH]);

            // check ------------------------------------------------------------------------------------------------------------------------------
            if (empty($formData)) {
                $message = "Empty Data (PO)";
            } else if (empty($automailData)) {
                $message = "Empty Data (SO#)";
            } else if (empty($masterData)) {
                $message = "Empty Data (Master)";
            } else if (empty($processData)) {
                $message = "Empty Data (Process)";
            } else {

                // get remark save data
                $remarkCheckArr['rbo'] = $formData['rbo'];
                $remarkCheckArr['internal_item'] = $formData['internal_item'];
                $remarkCheckArr['order_type_name'] = '';
                $remarkCheckArr['ordered_item'] = $automailData[0]['data'][6];
                $remarkCheckArr['ship_to_customer'] = $formData['ship_to_customer'];
                $remarkCheckArr['bill_to_customer'] = $formData['bill_to_customer'];
                $remarkCheckArr['packing_instructions'] = $automailData[0]['data'][19];

                // set data details 
                $po_no = $formData['po_no'];
                $qty_total = (int)$formData['qty_total'];
                $setup_time_total = $formData['setup_time_total'];
                $setup_sheet_total = $formData['setup_sheet_total'];
                $plan_type = trim(strtoupper($formData['plan_type']));

                // label size data
                $label_size = $formData['label_size'];
                $ups_label = $formData['ups_label'];
                $pattern_no = $formData['pattern'];
                $patternData = $this->detacheLabelSize($label_size, $ups_label, $pattern_no);


                // get user data
                $userInfo = $this->users->readItem($this->updated_by);
                $name = $this->updated_by;
                if (!empty($userInfo)) {
                    $name = !empty($userInfo['name']) ? $userInfo['name'] : $name;
                }

                // add element to first element of arr
                // array_unshift($processData, $processHeader);
                $printing_json = array();
                $printing_json[] = $processHeader;
                foreach ($processData as $processItem) {
                    $processItemData = $processItem;

                    if (empty($processItemData[1]) || $processItemData[1] == null ) continue;

                    // Tính toán Lưu lượng mực được lấy từ file của Prepress
                    $count = count($processItemData) - 1;
                    $Mn2 = $processItemData[$count];
                    $processItemData[$count] = $this->inkUsageCal($Mn2, $formData['sheet_total'] );

                    $printing_json[] = $processItemData;
                }

                // sử dụng cho remark material
                $material_code_remark = '';
                $process_code_remark_check = false;
                $processCodeRemarkArr = array(
                    'WHITE',
                    'WHITE 8500',
                    '10A WHITE',
                    'WHITE/WHITE BACKER',
                    'WHITE 10A',
                    'WHITE BACKER',
                    'WHITE 001A',
                    'WHITE+WHITE BACKER'
                );


                // htl_save_po  ------------------------------------------------------------------------------------------------------------------------------
                // get data
                $save_po_data = array(
                    'production_line' => $this->production_line,
                    'form_type' => $this->form_type,
                    'po_no' => $po_no,
                    'po_no_suffix' => $formData['order_type_local'],
                    'count_lines' => count($automailData),

                    'customer_job' => $automailData[0]['data'][21], // CUSTOMER_JOB
                    'qty_total' => $qty_total,
                    'po_date' => $formData['po_date'],
                    'ordered_date' => $this->dateFormat($formData2['ordered_date']),
                    'request_date' => $this->dateFormat($formData2['request_date']),

                    'promise_date' => $this->dateFormat($formData['promise_date']),
                    'rbo' => $formData['rbo'],
                    'bill_to_customer' => $formData['bill_to_customer'],
                    'ship_to_customer' => $formData['ship_to_customer'],
                    'order_type_name' => '',

                    'label_size' => $formData['label_size'],
                    'film_number' => $formData['film_number'],
                    'process_pass_total' => $formData['process_pass_total'],
                    'sheet_pass_total' => $formData['sheet_pass_total'],
                    'color_total' => $formData['color_total'],

                    'ups_label' => $formData['ups_label'],
                    'ups_total' => $formData['ups_total'],
                    'setup_time_total' => $setup_time_total,
                    'setup_sheet_total' => $formData['setup_sheet_total'],
                    'original_need' => $formData['original_need'],

                    'sheet_batching' => $formData['sheet_batching'],
                    'sheet_packing' => $formData['sheet_packing'],
                    'paper_compensate_total' => $formData['paper_compensate_total'],
                    'sheet_total' => $formData['sheet_total'],
                    'allowance_scrap' => $formData['allowance_scrap'],

                    'designed_scrap' => $formData['designed_scrap'],
                    'setup_scrap' => $formData['setup_scrap'],
                    'scrap_total' => $formData['scrap_total'],
                    'running_time' => $formData['running_time'],
                    'internal_item' => $formData['internal_item'],

                    'customer_item' => $formData['customer_item'],
                    'machine' => $formData['machine'],

                    'material_code' => $masterData[0]['data'][2],
                    'material_name' => $masterData[0]['data'][3],
                    'material_width' => $masterData[0]['data'][4],
                    'material_length' => $masterData[0]['data'][5],
                    'product_type' => $masterData[0]['data'][6],

                    'plan_type' => $plan_type,
                    'scrap' => $masterData[0]['data'][8],
                    'pattern_no' => $pattern_no,
                    'printing_json' => json_encode($printing_json),
                    'uom_cost' => $formData['uom_cost'],
                    'po_remark_1' => $formData['po_remark_1'],

                    'po_remark_2' => $formData['po_remark_2'],
                    'printed' => 0,
                    'name' => $name,
                    'updated_by' => $this->updated_by,
                    'updated_date' => date('Y-m-d H:i:s')
                );

                // save po 
                $where_po = array('po_no' => $po_no);
                if ($this->save_po->isAlreadyExist($where_po)) {
                    unset($save_po_data['po_no']);
                    $results = $this->save_po->update($save_po_data, $where_po);
                } else {
                    $results = $this->save_po->insert($save_po_data);
                }

                // check save po ------------------------------------------------------------------------------------------------------------------------------
                if ($results != TRUE) {
                    $message = "Save data error (PO)";
                } else {

                    // htl_save_po_soline ------------------------------------------------------------------------------------------------------------------------------
                    foreach ($automailData as $key => $value) {

                        $automail = $value['data'];

                        // data automail
                        $order_number = $automail[1];
                        $line_number = $automail[2];
                        $so_line = $automail[3];
                        $qty_of_line = $automail[4];
                        $internal_item = $automail[5];
                        $ordered_item = $automail[6];

                        $customer_item = $automail[7];
                        $rbo = $automail[8];
                        $ordered_date = $automail[9];
                        $request_date = $automail[10];
                        $promise_date = $automail[11];

                        $ship_to_customer = $automail[12];
                        $bill_to_customer = $automail[13];
                        $cs = $automail[14];
                        $order_type_name = $automail[15];
                        $flow_status_code = $automail[16];

                        $production_method = $automail[17];
                        $packing_instr = $automail[18];
                        $packing_instructions = $automail[19];
                        $attachment = $automail[20];
                        $customer_job = $automail[21];
                        $uom_cost = $automail[22];



                        // master item
                        foreach ($masterData as $keyM => $valueM) {

                            $masterItem = $valueM['data'];

                            if ($internal_item == $masterItem[1]) {

                                // Để lấy material code cho kiểm tra remark
                                if ($keyM == 0 ) {
                                    $material_code_remark = $masterItem[2];
                                }
                                

                                // get item list
                                $internal_item_list[] = $internal_item;

                                // master data
                                $material_code = $masterItem[2];
                                $material_name = $masterItem[3];
                                $material_width = $masterItem[4];
                                $material_length = $masterItem[5];
                                $product_type = $masterItem[6];

                                $plan_type = $masterItem[7];
                                $scrap = $masterItem[8];
                                $remark_1 = $masterItem[9];
                                $remark_2 = $masterItem[10];
                                $remark_3 = $masterItem[11];

                                // set soline save
                                $save_po_soline_data = array(
                                    'po_no' => $po_no,
                                    'so_line' => $so_line,
                                    'qty_of_line' => $qty_of_line,
                                    'ordered_item' => $ordered_item,
                                    'customer_item' => $customer_item,

                                    'cust_po_number' => '',

                                    'cs' => $cs,
                                    'rbo' => $rbo,
                                    'bill_to_customer' => $bill_to_customer,
                                    'ship_to_customer' => $ship_to_customer,
                                    'ordered_date' => $ordered_date,

                                    'request_date' => $request_date,
                                    'promise_date' => $promise_date,
                                    'order_type_name' => $order_type_name,
                                    'flow_status_code' => $flow_status_code,
                                    'production_method' => $production_method,

                                    'planner_code' => '',
                                    'customer_job' => $customer_job,
                                    'packing_instr' => $packing_instr,
                                    'packing_instructions' => $packing_instructions,
                                    'attachment' => $attachment,

                                    'form_type' => $this->form_type,
                                    'internal_item' => $internal_item,
                                    'internal_item_desc' => '',
                                    'material_code' => $material_code,
                                    'material_name' => $material_name,

                                    'material_width' => $material_width,
                                    'material_length' => $material_length,
                                    'product_type' => $product_type,
                                    'plan_type' => $plan_type,
                                    'scrap' => $scrap,

                                    'uom_cost' => $uom_cost,
                                    'remark_1' => $remark_1,
                                    'remark_2' => $remark_2,
                                    'remark_3' => $remark_3,


                                );

                                // save soline 
                                $where_soline = array('po_no' => $po_no, 'so_line' => $so_line);
                                if ($this->save_po_soline->isAlreadyExist($where_soline)) {
                                    unset($save_po_soline_data['po_no']);
                                    unset($save_po_soline_data['so_line']);
                                    $results = $this->save_po_soline->update($save_po_soline_data, $where_soline);
                                } else {
                                    $results = $this->save_po_soline->insert($save_po_soline_data);
                                }

                                // check save ------------------------------------------------------------------------------------------------------------------------------
                                if ($results != TRUE) {
                                    $this->_data['results'] = array(
                                        'status' => false,
                                        'message' => 'Save data error (SOLINE)'
                                    );
                                    echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
                                    exit();
                                } else {
                                    // save process  ------------------------------------------------------------------------------------------------------------------------------
                                    // get Process

                                    // xóa trước các process đã lưu (tránh trường hợp làm lại lệnh có các process đã xóa trước trong giao diện)
                                    $where_process_del = array('po_no' => $po_no);
                                    if ($this->save_process->isAlreadyExist($where_process_del)) {
                                        $this->save_process->delete($where_process_del);
                                    }



                                    // print_r($processData);
                                    foreach ($processData as $keyP => $valueP) {

                                        $processItem = $valueP;
                                        $count_p = count($processItem);

                                        // get data
                                        $order = $processItem[0];
                                        $process = $processItem[1];
                                        $setup_sheet = $processItem[$count_p - 2];
                                        $setup_time = $processItem[$count_p - 3];
                                        $passes = $processItem[$count_p - 4];
                                        $frame = $processItem[$count_p - 5];

                                        if (empty($process) || $process == null ) continue;

                                        // Vị trí cột cuối cùng trong mảng
                                        $Mn2 = $processItem[$count_p - 1];

                                        // hfe setup time = setup time total
                                        if ($keyP == 0) {
                                            if ($this->form_type == 'hfe') {
                                                $setup_sheet = $setup_sheet_total;
                                            }
                                        }

                                        // get process name vi
                                        $process_name_vi = $process;
                                        $where_s = array('form_type' => $this->form_type, 'process' => $process);
                                        if ($this->setting_process->isAlreadyExist($where_s)) {
                                            $settingProcess = $this->setting_process->readItem(array('form_type' => $this->form_type, 'process' => $process));
                                            $process_name_vi = $settingProcess['process_name_vi'];
                                        }

                                        // get data process_code
                                        $process_code = $processItem[2];

                                        // để sử dụng cho remark material
                                        foreach ($processCodeRemarkArr as $process_code_check ) {
                                            if ($process_code == $process_code_check ) {
                                                $process_code_remark_check = true;
                                                break;
                                            }
                                        }
                                        

                                        // delete element from key
                                        // array_splice($processData[$keyP]['data'], 2, 1);

                                        // Save Lưu lượng mực in (dữ liệu từ Prepress)
                                        $ink_usage_cal = $this->inkUsageCal($Mn2, $formData['sheet_total'] );
                                        $save_prepress_ink_usage = array(
                                            'oh' => $po_no,
                                            'process' => $process,
                                            'order' => $order,
                                            'ink_usage' => $ink_usage_cal,
                                            'directory' => $this->prepress_dir,
                                            'updated_by' => $this->updated_by
                                        );

                                        // set data
                                        $save_process = array(
                                            'po_no' => $po_no,
                                            'internal_item' => $internal_item,
                                            'process' => $process,
                                            'process_code' => $process_code,
                                            'order' => $order,
                                            'process_name_vi' => $process_name_vi,
                                            'frame' => $frame,
                                            'passes' => $passes,
                                            'setup_time' => $setup_time,
                                            'setup_sheet' => $setup_sheet,
                                            'ink_usage' => $ink_usage_cal
                                        );

                                        

                                        // save 
                                        $where_process = array('po_no' => $po_no, 'internal_item' => $internal_item, 'process' => $process, 'process_code' => $process_code, 'order' => $order);
                                        if ($this->save_process->isAlreadyExist($where_process)) {
                                            unset($save_process['po_no']);
                                            unset($save_process['internal_item']);
                                            unset($save_process['process']);
                                            unset($save_process['process_code']);
                                            unset($save_process['order']);
                                            $results = $this->save_process->update($save_process, $where_process);
                                        } else {
                                            $results = $this->save_process->insert($save_process);
                                        }

                                        // check ------------------------------------------------------------------------------------------------------------------------------
                                        if ($results != TRUE) {
                                            $this->_data['results'] = array(
                                                'status' => false,
                                                'message' => 'Save data error (PROCESS)'
                                            );
                                            echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
                                            exit();
                                        } else {

                                            /*
                                                save ink usage từ prepress
                                                Không check save
                                             */
                                            
                                            $where_prepress = array('oh' => $po_no, 'process' => $process, 'order' => $order );
                                            if ($this->prepress_ink_usage->isAlreadyExist($where_prepress)) {
                                                unset($save_prepress_ink_usage['oh']);
                                                unset($save_prepress_ink_usage['process']);

                                                $save_prepress_ink_usage['updated_date'] = date('Y-m-d H:i:s');
                                                $this->prepress_ink_usage->update($save_prepress_ink_usage, $where_prepress);
                                            } else {
                                                $this->prepress_ink_usage->insert($save_prepress_ink_usage);
                                            }
                                            
                                            // save po pattern
                                            $id = 0;

                                            foreach ($patternData as $pattern) {

                                                $id++;

                                                // data
                                                $pattern_no = ($this->form_type == 'hfe') ? $pattern_no : $id;

                                                $save_pattern_data = array(
                                                    'po_no' => $po_no,
                                                    'pattern_no' => $pattern_no,
                                                    'width' => $pattern['width'],
                                                    'length' => $pattern['length'],
                                                    'label_size' => $pattern['label_size'],
                                                    'ups_width' => $pattern['ups_width'],
                                                    'ups_length' => $pattern['ups_length'],
                                                    'ups' => $pattern['ups'],
                                                    'ups_label' => $pattern['ups_label'],
                                                    'updated_by' => $this->updated_by
                                                );

                                                // save 
                                                $where_pattern = array('po_no' => $po_no, 'pattern_no' => $pattern_no);
                                                if ($this->save_pattern->isAlreadyExist($where_pattern)) {
                                                    $results = $this->save_pattern->update($save_pattern_data, $where_pattern);
                                                } else {
                                                    $results = $this->save_pattern->insert($save_pattern_data);
                                                }

                                                if ($results != TRUE) {
                                                    $this->_data['results'] = array(
                                                        'status' => false,
                                                        'message' => 'Save data error (PATTERN)'
                                                    );
                                                    echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
                                                    exit();
                                                } else {
                                                    // update status OH
                                                    $this->prepress_oh->update(array('status' => 1, 'updated_by' => $this->updated_by, 'updated_date' => date('Y-m-d H:i:s')), array('oh' => $po_no));
                                                    // results
                                                    $status = true;
                                                    $message = "Save Data Success";
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // if FOD: save and update FOD data = '' from master data. Add remark FOD
                    if ($plan_type == 'FOD') {
                        $this->master_item->update(array('plan_type' => ''), array('internal_item' => $internal_item));

                        // remark 

                    }



                    // Remark save ------------------------------------------------------------------------------------------------------------------------------
                    // remark
                    $remarkSave = $this->remark($this->production_line, $po_no, $remarkCheckArr);
                    if ($remarkSave !== TRUE) {
                        $this->_data['results'] = array(
                            'status' => false,
                            'message' => 'Save Data Error. Remark Tool '
                        );
                        echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
                        exit();
                    }

                    // Remark: KHONG KIM LOAI và save Packing Instr
                    $remarkPacking = $this->packingInstrRemark($this->production_line, $po_no, $remarkCheckArr['packing_instructions'], $remarkCheckArr['rbo']);
                    if ($remarkPacking !== TRUE) {
                        $this->_data['results'] = array(
                            'status' => false,
                            'message' => 'Save Data Error. Remark  & Packing'
                        );
                        echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
                        exit();
                    }

                    // materialRemark
                    $materialRemark = $this->materialRemark($po_no, $material_code_remark, $process_code_remark_check );
                    if ($materialRemark !== TRUE) {
                        $this->_data['results'] = array(
                            'status' => false,
                            'message' => 'Save Data Error. Remark Material'
                        );
                        echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE); exit();
                    }
                }
            }
        }


        // results to saveOrders view ------------------------------------------------------------------------------------------------------------
        $this->_data['results'] = array(
            'status' => $status,
            'message' => $message,
            'po_no' => $po_no
        );

        echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function delete($po_no)
    {

        //load models
        $this->load->model('htl_save_po', 'save_po');
        $this->load->model('htl_save_po_soline', 'save_po_soline');
        $this->load->model('htl_save_po_process', 'save_process');
        $this->load->model('htl_save_po_pattern', 'save_pattern');
        $this->load->model('htl_prepress_ink_usage', 'prepress_ink_usage');
        $this->load->model('htl_prepress_oh', 'prepress_oh');
        
        //handle all

        // where 
        $where = array('po_no' => $po_no);

        // message
        $message_suffix = "";
        $message = "PO NO: $po_no. ";
        $status = false;

        $result_1 = FALSE;
        $result_2 = FALSE;
        $result_3 = FALSE;
        $result_4 = FALSE;
        
        $result_6 = FALSE;

        // 1. save_po
        if (!$this->save_po->isAlreadyExist($where) ) {
            $message_suffix .= "Không tồn tại trong Save Po. ";
        } else {
            // del 
            $result_1 = $this->save_po->delete($where);
            // check 
            if ($result_1 !== TRUE ) {
                $message .= "[ERROR PO]. Lỗi: $result_1. ";
            }
        }


        // 2. save_po
        if (!$this->save_po_soline->isAlreadyExist($where) ) {
            $message_suffix .= "Không tồn tại trong Save SOLine. ";
        } else {
            // del 
            $result_2 = $this->save_po_soline->delete($where);
            // check 
            if ($result_2 !== TRUE ) {
                $message .= "[ERROR SOLINE]. Lỗi: $result_2. ";
            }

        }

        // 3. save_process
        if (!$this->save_process->isAlreadyExist($where) ) {
            $message_suffix .= "Không tồn tại trong Save Process. ";
        } else {
            // del 
            $result_3 = $this->save_process->delete($where);
            // check 
            if ($result_3 !== TRUE ) {
                $message .= "[ERROR PROCESS]. Lỗi: $result_3. ";
            }
        }


        // 4. save_pattern
        if (!$this->save_pattern->isAlreadyExist($where) ) {
            $message_suffix .= "Không tồn tại trong Save Pattern. ";
        } else {
            // del 
            $result_4 = $this->save_pattern->delete($where);
            // check 
            if ($result_4 !== TRUE ) {
                $message .= "[ERROR PATTERN]. Lỗi: $result_4. ";
            }
        }


        // 5. prepress_ink_usage
        if ($this->form_type == 'htl' ) {
            $where = array('oh' => $po_no );
            if (!$this->prepress_ink_usage->isAlreadyExist($where) ) {
                $message_suffix .= "Không tồn tại trong Save Lưu lượng mực (Prepress). ";
            } else {
                // del 
                $result_5 = $this->prepress_ink_usage->delete($where);
                // check 
                if ($result_5 !== TRUE ) {
                    $message .= "[ERROR PREPRESS]. Lỗi: $result_5. ";
                    $res = array( 'status' => $status, 'message' => $message );
                    $this->load->view('htl/display', $res);exit();
                }
            }
        }


        // 6. prepress_oh
        if (!$this->prepress_oh->isAlreadyExist($where) ) {
            $message_suffix .= "Không tồn tại trong OH. ";
        } else {
            // del 
            $result_6 = $this->prepress_oh->delete($where);
            // check 
            if ($result_6 !== TRUE ) {
                $message .= "[ERROR OH]. Lỗi: $result_6. ";
            }
        }

        // message
        $message .= $message_suffix;
        // result all
        if (($result_1 == TRUE) && ($result_2 == TRUE) && ($result_3 == TRUE) && ($result_4 == TRUE) && ($result_6 == TRUE) ) {
            $status = true;
            $message .= 'Xóa thành công.';
        }

        $res = array( 'status' => $status, 'message' => $message );
        $this->load->view('htl/display', $res);
        
        
    }

    // print orders
    public function printOrders()
    {

        // models ------------------------------------------------------------------------------------------------------------------------------
        $this->load->model('htl_save_po', 'save_po');
        $this->load->model('htl_save_po_soline', 'save_po_soline');
        $this->load->model('htl_save_po_process', 'save_process');
        $this->load->model('htl_prepress_oh', 'prepress_oh');
        $this->load->model('htl_save_po_pattern', 'save_pattern');
        $this->load->model('htl_master_machine', 'machine');

        $this->load->model('htl_setting_process', 'setting_process');

        $this->load->model('common_users', 'users');
        $this->load->model('common_remark_po_save', 'remark_po_save');

        // init
        $po_data = array();
        $soline_data = array();
        $process_data = array();
        $setting_process_data = array();

        $pattern_data = array();
        $remark_main_data = array();
        $others_data = array();

        // status default
        $status = false;
        $message = 'Load data error (0)';

        $po_no = null !== $this->input->get('po_no') ? trim($this->input->get('po_no')) : '';
        if (empty($po_no) ) {
            $message = 'Load data error (1)';
        } else {

            $po_no = urldecode($po_no);

            // get form_type
            $po_no_arr = explode('-', $po_no);
            $prefix_len = strlen($po_no_arr[0]) - 4;
            $prefix = substr($po_no, 0, $prefix_len);
    
            // get prefix no description
            if (!$this->common_prefix_no->checkPrefix($prefix)) {
                $message = "Không lấy được NO# (PREFIX)";
            } else {
                // prefix data
                $po_prefix_no_check = $this->common_prefix_no->readPrefix($prefix);
                $form_type_label = $po_prefix_no_check['description'];
    
                // get data po_save
                $where = array('po_no' => $po_no);
                if (!$this->save_po->isAlreadyExist($where)) {
                    $message = "Không có dữ liệu đã làm lênh của NO# (1): $po_no ";
                } else if (!$this->save_po_soline->isAlreadyExist($where)) {
                    $message = "Không có dữ liệu đã làm lênh của NO# (2): $po_no ";
                } else if (!$this->save_process->isAlreadyExist($where)) {
                    $message = "Không có dữ liệu đã làm lênh của NO# (3): $po_no ";
                } else if (!$this->save_pattern->isAlreadyExist($where)) {
                    $message = "Không có dữ liệu đã làm lênh của NO# (4): $po_no ";
                } else {
                    // po data
                    $po_data = $this->save_po->readItem($where);
                    $machine = $po_data['machine'];
                    $machineInfo = $this->machine->readItem(array('form_type' => $this->form_type, 'machine' => $machine));
                    $machine_speed = $machineInfo['machine_speed'];
                    $machine_unit = $machineInfo['machine_unit'];

                    $printed = $po_data['printed'];
    
    
                    // soline data
                    $soline_data = $this->save_po_soline->readOrders($where);
    
                    // barcode
                    $po_no_barcode = '<img style="text-align:right;width:350px; height:30px;"  src="' . base_url("assets/barcode.php?text=") . $po_no . '" />';
    
                    // process data
                    $process_data = $this->save_process->readOptions($where);
    
                    // pattern data
                    $pattern_data = $this->save_pattern->readOptions($where);
    
                    // get remark data
                    $remark_main_data = $this->remark_po_save->readPO(array('po_no' => $po_no));
    
                    // setting process
                    $setting_process_data = $this->setting_process->readOptions(array('form_type' => $this->form_type), 'process');
    
                    // others data
                    $others_data = array(
                        'form_type_label' => $form_type_label,
                        'po_no_barcode' => $po_no_barcode,
                        'machine_speed' => $machine_speed,
                        'machine_unit' => $machine_unit
                    );
    
                    // results
                    $status = true;
                    $message = 'Print Data OK';
                }
            }
    
    
            // get results
            $this->_data['results'] = array(
                'status' => $status,
                'message' => $message,
                'po_data' => $po_data,
                'soline_data' => $soline_data,
                'process_data' => $process_data,
                'setting_process_data' => $setting_process_data,
                'pattern_data' => $pattern_data,
                'remark_main_data' => $remark_main_data,
                'others_data' => $others_data
            );

            // update printed col
            if ($status == true ) {
                $this->save_po->update(array('printed' => $printed+1), array('po_no' => $po_no) );
            }
        }
        
        // return 
        $this->load->view('htl/print/printOrders', $this->_data);
    }

    // export
    public function reportOrders()
    {
        // tilte 
        $this->_data['title'] = 'HTL Reports';

        // load models
        $this->load->model('htl_save_po', 'save_po');
        $this->load->model('htl_save_po_soline', 'save_po_soline');
        $this->load->model('htl_save_po_process', 'save_process');
        // $this->load->model('htl_save_po_pattern', 'save_pattern');

        // get distance times
        $from_date = null !== $this->input->get('from_date') ? trim($this->input->get('from_date')) : date('Y-m-d');
        $to_date = null !== $this->input->get('to_date') ? trim($this->input->get('to_date')) : date('Y-m-d');

        // XML header
        header('Content-type: text/xml');

        // open
        echo "<rows>";

        // header
        $header = '<head>
                    <column width="100" type="ed" align="center" sort="str">DATE</column>

                    <column width="180" type="ed" align="center" sort="str">LENH SX</column>
                    <column width="180" type="ed" align="center" sort="str">INTERNAL ITEM</column>
                    <column width="180" type="ed" align="center" sort="str">RBO</column>
                    <column width="120" type="ed" align="center" sort="str">SOLINE</column>
                    <column width="80" type="ed" align="center" sort="str">QTY</column>

                    <column width="180" type="ed" align="center" sort="str">UPS</column>
                    <column width="180" type="ed" align="center" sort="str">VAT TU</column>
                    <column width="180" type="ed" align="center" sort="str">NGAY GIAO</column>
                    <column width="180" type="ed" align="center" sort="str">SO MAU</column>
                    <column width="180" type="ed" align="center" sort="str">TIEN TRINH</column>

                    <column width="180" type="ed" align="center" sort="str">SO LUOT</column>
                    <column width="180" type="ed" align="center" sort="str">SO KHUNG</column>
                    <column width="180" type="ed" align="center" sort="str">NEED SHEET</column>
                    <column width="180" type="ed" align="center" sort="str">SHEET BATCHING</column>
                    <column width="180" type="ed" align="center" sort="str">SHEET SETUP</column>

                    <column width="180" type="ed" align="center" sort="str">SHEET PACKING</column>
                    <column width="180" type="ed" align="center" sort="str">TOTAL SHEET</column>
                    <column width="180" type="ed" align="center" sort="str">MA VAT TU</column>
                    <column width="180" type="ed" align="center" sort="str">KICH THUOC</column>
                    <column width="180" type="ed" align="center" sort="str">SO TO BAN DAU</column>

                    <column width="180" type="ed" align="center" sort="str">SO TO DONG GOI</column>
                    <column width="180" type="ed" align="center" sort="str">SO TO IN</column>
                    <column width="180" type="ed" align="center" sort="str">CUSTOMER ITEM</column>
                    <column width="180" type="ed" align="center" sort="str">TONG LUOT IN</column>
                    <column width="180" type="ed" align="center" sort="str">TONG LUOT MAU</column>

                    <column width="180" type="ed" align="center" sort="str">CHIEU RONG</column>
                    <column width="180" type="ed" align="center" sort="str">CHIEU DAI</column>
                    <column width="180" type="ed" align="center" sort="str">RUNNING TIME</column>
                    <column width="180" type="ed" align="center" sort="str">NOTE</column>
                    <column width="180" type="ed" align="center" sort="str">TEN MAY</column>

                    <column width="180" type="ed" align="center" sort="str">BATCHING SCRAP (%)</column>
                    <column width="180" type="ed" align="center" sort="str">SETUP SCRAP (%)</column>
                    <column width="180" type="ed" align="center" sort="str">RUNNING SCRAP (%)</column>
                    <column width="180" type="ed" align="center" sort="str">TOTAL SCRAP (%)</column>
                    <column width="180" type="ed" align="center" sort="str">SO KHUNG</column>

                    <column width="180" type="ed" align="center" sort="str">SO FILM</column>
                    <column width="180" type="ed" align="center" sort="str">TG CHAY HANG</column>
                    <column width="180" type="ed" align="center" sort="str">TG CANH CHINH</column>
                    <column width="180" type="ed" align="center" sort="str">TYPE</column>
                    <column width="180" type="ed" align="center" sort="str">SO TIEN TRINH</column>

                    <column width="180" type="ed" align="center" sort="str">SCRAP (%)</column>

                </head>';

        echo $header;

        // Dữ liệu khác ngoài Item. Sau này cập nhật thêm tại đây và đếm count tổng - count này = số lượng item
        $arr = array('NO.', 'PRINTING', 'PASSES', 'FRAME', 'TIME', 'SHEET', 'INK USAGE');

        // File Excel
            // create
                $spreadsheet = new Spreadsheet();

            // set the names of header cells
                // set Header, width
                $columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP');

                // Add some data
                $spreadsheet->setActiveSheetIndex(0);

                // active and set title
                $spreadsheet->getActiveSheet()->setTitle('Reports');

                $headers = array(
                    'DATE', 'LENH SX', 'INTERNAL ITEM', 'RBO', 'SOLINE', 'QTY', 'UPS', 'VAT TU', 'NGAY GIAO', 'SO MAU',
                    'TIEN TRINH', 'SO LUOT', 'SO KHUNG', 'NEED SHEET', 'SHEET BATCHING', 'SHEET SETUP', 'SHEET PACKING', 'TOTAL SHEET', 'MA VAT TU', 'KICH THUOC', 
                    'SO TO BAN DAU', 'SO TO DONG GOI', 'SO TO IN', 'CUSTOMER ITEM', 'TONG LUOT IN', 'TONG LUOT MAU', 'CHIEU RONG', 'CHIEU DAI', 'RUNNING TIME', 'NOTE', 
                    'TEN MAY', 'BATCHING SCRAP (%)', 'SETUP SCRAP (%)', 'RUNNING SCRAP (%)', 'TOTAL SCRAP (%)', 'SO KHUNG', 'SO FILM', 'TG CHAY HANG', 'TG CANH CHINH', 'TYPE', 
                    'SO TIEN TRINH', 'SCRAP (%)'
                );
    
                $id = 0;
                foreach ($headers as $header) {
                    for ($index = $id; $index < count($headers); $index++) {
                        // width
                        $spreadsheet->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(20);
    
                        // headers
                        $spreadsheet->getActiveSheet()->setCellValue($columns[$index] . '1', $header);
    
                        $id++;
                        break;
                    }
                }
    
    
                // Font
                $spreadsheet->getActiveSheet()->getStyle('A1:AP1')->getFont()->setBold(true)->setName('Arial')->setSize(10);
                $spreadsheet->getActiveSheet()->getStyle('A1:AP1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('3399ff');
                $spreadsheet->getActiveSheet()->getStyle('A:AP')->getFont()->setName('Arial')->setSize(10);

            
        

        // data
        $index = 0;
        $rowCount = 1;

        $data = $this->save_po->readDistance($this->form_type, '', $from_date, $to_date, 'ASC');
        if (!empty($data) ) {
            foreach ($data as $value ) {

                $itemArr = array();
                $printing_json_new = array();

                // Lấy các process để tách ra hiển thị
                $printing_json = json_decode($value['printing_json']);

                // lấy header và Loại bỏ phần tử header trong $printing_json
                $printing_json_header = $printing_json[0];
                $count_header = count($printing_json_header);
                unset($printing_json[0]);

                // Tách danh sách Item bỏ vào mảng
                foreach ($printing_json_header as $keyH => $header ) {
                    if (!in_array($header, $arr) ) $itemArr[] = $header;
                    $printing_json_Shrink[] = 'Shrink';
                    
                }

                // soline
                $po_no = $value['po_no'];
                $soline_data = $this->save_po_soline->readOptions(array('po_no' => $po_no) );

                // Trường hợp printing json (process) có nhiều dòng hơn soline
                $flag_p = false;
                $count_sub = 0;
                $count_soline = count($soline_data);
                $count_passes = count($printing_json); // số khung
                if ($count_soline > $count_passes ) {
                    $flag_p = true;
                    $count_sub = $count_soline - $count_passes;
                }

                // get po data
                $format = 'd-M-y';
                $po_date = $this->dateFormat($value['po_date'], $format); 
                
                // Xử lý lại các ký tự: ', &
                $rbo = str_replace("&#039;", "'", $value['rbo']); 
                $rbo = str_replace("&amp;", "&", $value['rbo']); 

                $ups_total = $value['ups_total']; 
                $material_code = $value['material_code']; 
                $promise_date = $this->dateFormat($value['promise_date'], $format); 
                $color_total = $value['color_total']; 

                $original_need = $value['original_need']; // original_need/need sheet/số tờ ban đầu
                $sheet_batching = $value['sheet_batching']; 
                $setup_sheet_total = $value['setup_sheet_total']; 
                $sheet_packing = $value['sheet_packing']; 
                $sheet_total = $value['sheet_total']; 

                $material_size = $value['material_width'] ."*". $value['material_length']; 
                $process_pass_total = $value['process_pass_total']; 

                $running_time = $value['running_time']; 
                $machine = $value['machine']; 
                $designed_scrap = $value['designed_scrap'] * 100; // scrap batching
                $setup_scrap = $value['setup_scrap'] * 100; 
                // $allowance_scrap = $value['allowance_scrap']; 
                $scrap_total = $value['scrap_total'] * 100; 

                $film_number = $value['film_number']; 

                // tổng thời gian chạy hàng. Hỏi lại thông tin này
                if(strpos(strtoupper($machine),'SAKURAI') !==false ){
					$running_time_2 = ($process_pass_total/1200)*60;
				}else{
					$running_time_2 = ($process_pass_total/600)*60;
				}
				$running_time_2 = round($running_time_2,2);
                
                $setup_time_total = $value['setup_time_total'];

                $plan_type = $value['plan_type'];

                $scrap = $value['scrap'] * 100;
            
                // Không có dữ liệu
                $sheet_printing = ''; 
                $color_passes_total = '';
                $width = '';
                $height = '';

                $note = $this->dateFormat($value['request_date'], $format); 

                $running_scrap = '';

                $printing_json_new[] = $printing_json_Shrink;
                foreach ($printing_json as $printing ) {
                    $printing_json_new[] = $printing;
                }

                // Lấy dữ liệu hiển thị

                foreach ($printing_json_new as $key => $item ) {

                    $index++;

                    $rowCount++;

                    // Dữ liệu của đơn hàng (soline)
                    $internal_item = (isset($soline_data[$key]['internal_item'])) ? $soline_data[$key]['internal_item'] : '';
                    $so_line = (isset($soline_data[$key]['so_line'])) ? $soline_data[$key]['so_line'] : '';
                    $qty_of_line = (isset($soline_data[$key]['qty_of_line'])) ? $soline_data[$key]['qty_of_line'] : '';
                    $customer_item = (isset($soline_data[$key]['customer_item'])) ? $soline_data[$key]['customer_item'] : '';

                    // Dữ liệu của Printing Json (liên quan Process)
                    $process = $item[1]; // tiến trình
                    $count_item = count($itemArr);
                    $process_code_arr = array();
                    $process_code_arr_check = array();

                    if ($count_item > 1 ) {
                        for ($i=0; $i<$count_item; $i++ ) {
                            $process_code = $item[$i+2];
                            if(!in_array(strtoupper($process_code), $process_code_arr_check) && !empty($process_code)  ) {
                                $countCheck = count($process_code_arr) + 1;
                                $process_code_arr_check[] = strtoupper($process_code);
                                $process_code_arr[] = $process_code . "($countCheck)";
                                
                            } 
                        }
    
                        $process_code_string = implode('+', $process_code_arr );

                    } else {
                        $process_code_string = $item[2];
                    }
                    
                    $passes = $item[$count_header-5]; // số lượt
                    if ($passes == 'Shrink' ) {
                        $passes = 1;
                    }

                    $frame = $item[$count_header-4]; // số khung
                    if ($frame == 'Shrink' ) {
                        $frame = '';
                    }

                    // set data
                    echo '<row id="' . $index . '">';
                        echo '<cell>' . $po_date . '</cell>';
                        echo '<cell>' . $po_no . '</cell>';
                        echo '<cell>' . $internal_item . '</cell>';
                        echo '<cell>' . $rbo . '</cell>';
                        echo '<cell>' . $so_line . '</cell>';

                        echo '<cell>' . $qty_of_line . '</cell>';
                        echo '<cell>' . $ups_total . '</cell>';
                        echo '<cell>' . $material_code . '</cell>';
                        echo '<cell>' . $promise_date . '</cell>';
                        echo '<cell>' . $color_total . '</cell>';

                        echo '<cell>' . $process_code_string . '</cell>';
                        echo '<cell>' . $passes . '</cell>';
                        echo '<cell>' . $frame . '</cell>';
                        echo '<cell>' . $original_need . '</cell>';
                        echo '<cell>' . $sheet_batching . '</cell>';

                        echo '<cell>' . $setup_sheet_total . '</cell>';
                        echo '<cell>' . $sheet_packing . '</cell>';
                        echo '<cell>' . $sheet_total . '</cell>';
                        echo '<cell>' . $material_code . '</cell>'; // duplicate
                        echo '<cell>' . $material_size . '</cell>';

                        echo '<cell>' . $original_need . '</cell>'; // duplicate
                        echo '<cell>' . $sheet_batching . '</cell>'; // duplicate
                        echo '<cell>' . $sheet_printing . '</cell>'; // số tờ in
                        echo '<cell>' . $customer_item . '</cell>';
                        echo '<cell>' . $process_pass_total . '</cell>'; // ask ?

                        echo '<cell>' . $color_passes_total . '</cell>'; // ask ?
                        echo '<cell>' . $width . '</cell>';
                        echo '<cell>' . $height . '</cell>';
                        echo '<cell>' . $running_time . '</cell>';
                        echo '<cell>' . $note . '</cell>';

                        echo '<cell>' . $machine . '</cell>';
                        echo '<cell>' . $designed_scrap . '</cell>'; // scrap batching ??
                        echo '<cell>' . $setup_scrap . '</cell>';
                        echo '<cell>' . $running_scrap . '</cell>';
                        echo '<cell>' . $scrap_total . '</cell>';

                        echo '<cell>' . $count_passes . '</cell>'; // số khung
                        echo '<cell>' . $film_number . '</cell>';
                        echo '<cell>' . $running_time_2 . '</cell>';
                        echo '<cell>' . $setup_time_total . '</cell>'; // thời gian canh chỉnh ??
                        echo '<cell>' . $plan_type . '</cell>';

                        echo '<cell>' . $count_passes . '</cell>'; // số tiến trình ???
                        echo '<cell>' . $scrap . '</cell>';
                        
                    echo '</row>';

                    // to save to excel MACY&#039;S INC
                    $spreadsheet->getActiveSheet()->SetCellValue('A' . $rowCount, $po_date );
                    $spreadsheet->getActiveSheet()->SetCellValue('B' . $rowCount, $po_no);
                    $spreadsheet->getActiveSheet()->SetCellValue('C' . $rowCount, $internal_item);
                    $spreadsheet->getActiveSheet()->SetCellValue('D' . $rowCount, str_replace("&#039;", "'",$rbo) );
                    $spreadsheet->getActiveSheet()->SetCellValue('E' . $rowCount, $so_line);
                    $spreadsheet->getActiveSheet()->SetCellValue('F' . $rowCount, $qty_of_line);
                    $spreadsheet->getActiveSheet()->SetCellValue('G' . $rowCount, $ups_total);
                    $spreadsheet->getActiveSheet()->SetCellValue('H' . $rowCount, $material_code);
                    $spreadsheet->getActiveSheet()->SetCellValue('I' . $rowCount, $promise_date);
                    $spreadsheet->getActiveSheet()->SetCellValue('J' . $rowCount, $color_total);
                    $spreadsheet->getActiveSheet()->SetCellValue('K' . $rowCount, $process_code_string);
                    $spreadsheet->getActiveSheet()->SetCellValue('L' . $rowCount, $passes);
                    $spreadsheet->getActiveSheet()->SetCellValue('M' . $rowCount, $frame);    
                    $spreadsheet->getActiveSheet()->SetCellValue('N' . $rowCount, $original_need);    
                    $spreadsheet->getActiveSheet()->SetCellValue('O' . $rowCount, $sheet_batching);   
                    $spreadsheet->getActiveSheet()->SetCellValue('P' . $rowCount, $setup_sheet_total);   
                    $spreadsheet->getActiveSheet()->SetCellValue('Q' . $rowCount, $sheet_packing);   
                    $spreadsheet->getActiveSheet()->SetCellValue('R' . $rowCount, $sheet_total);   
                    $spreadsheet->getActiveSheet()->SetCellValue('S' . $rowCount, $material_code);   
                    $spreadsheet->getActiveSheet()->SetCellValue('T' . $rowCount, $material_size);   

                    $spreadsheet->getActiveSheet()->SetCellValue('U' . $rowCount, $original_need);   
                    $spreadsheet->getActiveSheet()->SetCellValue('V' . $rowCount, $sheet_batching);   
                    $spreadsheet->getActiveSheet()->SetCellValue('W' . $rowCount, $sheet_printing);   
                    $spreadsheet->getActiveSheet()->SetCellValue('X' . $rowCount, $customer_item);   
                    $spreadsheet->getActiveSheet()->SetCellValue('Y' . $rowCount, $process_pass_total);   
                    $spreadsheet->getActiveSheet()->SetCellValue('Z' . $rowCount, $color_passes_total);   
                    $spreadsheet->getActiveSheet()->SetCellValue('AA' . $rowCount, $width); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AB' . $rowCount, $height); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AC' . $rowCount, $running_time); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AD' . $rowCount, $note); 

                    $spreadsheet->getActiveSheet()->SetCellValue('AE' . $rowCount, $machine); 

                    $spreadsheet->getActiveSheet()->SetCellValue('AF' . $rowCount, $designed_scrap); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AG' . $rowCount, $setup_scrap); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AH' . $rowCount, $running_scrap); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AI' . $rowCount, $scrap_total); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AJ' . $rowCount, $count_passes); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AK' . $rowCount, $film_number); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AL' . $rowCount, $running_time_2); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AM' . $rowCount, $setup_time_total); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AN' . $rowCount, $plan_type); 

                    $spreadsheet->getActiveSheet()->SetCellValue('AO' . $rowCount, $count_passes); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AP' . $rowCount, $scrap); 

                }

                // Lấy dữ liệu chạy từ vị trí key (hiện tại đến vị trí còn lại trong soline (key + count sub))
                if ($flag_p == true ) {
                    
                    for ($indexS=($key+1);$indexS<($count_sub+$key); $indexS++ ) {

                        $index++;
                        $rowCount++;

                        // Dữ liệu của đơn hàng (soline)
                        $internal_item = (isset($soline_data[$indexS]['internal_item'])) ? $soline_data[$indexS]['internal_item'] : '';
                        $so_line = (isset($soline_data[$indexS]['so_line'])) ? $soline_data[$indexS]['so_line'] : '';
                        $qty_of_line = (isset($soline_data[$indexS]['qty_of_line'])) ? $soline_data[$indexS]['qty_of_line'] : '';
                        $customer_item = (isset($soline_data[$indexS]['customer_item'])) ? $soline_data[$indexS]['customer_item'] : '';

                        // Dữ liệu liên quan Process là rỗng
                        $process = ''; 
                        $process_code_string = '';
                        $passes = '';
                        $frame = '';

                        // set data
                        echo '<row id="' . $index . '">';
                            echo '<cell>' . $po_date . '</cell>';
                            echo '<cell>' . $po_no . '</cell>';
                            echo '<cell>' . $internal_item . '</cell>';
                            echo '<cell>' . $rbo . '</cell>';
                            echo '<cell>' . $so_line . '</cell>';

                            echo '<cell>' . $qty_of_line . '</cell>';
                            echo '<cell>' . $ups_total . '</cell>';
                            echo '<cell>' . $material_code . '</cell>';
                            echo '<cell>' . $promise_date . '</cell>';
                            echo '<cell>' . $color_total . '</cell>';

                            echo '<cell>' . $process_code_string . '</cell>';
                            echo '<cell>' . $passes . '</cell>';
                            echo '<cell>' . $frame . '</cell>';
                            echo '<cell>' . $original_need . '</cell>';
                            echo '<cell>' . $sheet_batching . '</cell>';

                            echo '<cell>' . $setup_sheet_total . '</cell>';
                            echo '<cell>' . $sheet_packing . '</cell>';
                            echo '<cell>' . $sheet_total . '</cell>';
                            echo '<cell>' . $material_code . '</cell>'; // duplicate
                            echo '<cell>' . $material_size . '</cell>';

                            echo '<cell>' . $original_need . '</cell>'; // duplicate
                            echo '<cell>' . $sheet_batching . '</cell>'; // duplicate
                            echo '<cell>' . $sheet_printing . '</cell>'; // số tờ in
                            echo '<cell>' . $customer_item . '</cell>';
                            echo '<cell>' . $process_pass_total . '</cell>'; // ask ?

                            echo '<cell>' . $color_passes_total . '</cell>'; // ask ?
                            echo '<cell>' . $width . '</cell>';
                            echo '<cell>' . $height . '</cell>';
                            echo '<cell>' . $running_time . '</cell>';
                            echo '<cell>' . $note . '</cell>';

                            echo '<cell>' . $machine . '</cell>';
                            echo '<cell>' . $designed_scrap . '</cell>'; // scrap batching ??
                            echo '<cell>' . $setup_scrap . '</cell>';
                            echo '<cell>' . $running_scrap . '</cell>';
                            echo '<cell>' . $scrap_total . '</cell>';

                            echo '<cell>' . $count_passes . '</cell>'; // số khung
                            echo '<cell>' . $film_number . '</cell>';
                            echo '<cell>' . $running_time_2 . '</cell>';
                            echo '<cell>' . $setup_time_total . '</cell>'; // thời gian canh chỉnh ??
                            echo '<cell>' . $plan_type . '</cell>';

                            echo '<cell>' . $count_passes . '</cell>'; // số tiến trình ???
                            echo '<cell>' . $scrap . '</cell>';
                            
                        echo '</row>';

                        // to save to excel MACY&#039;S INC
                    $spreadsheet->getActiveSheet()->SetCellValue('A' . $rowCount, $po_date );
                    $spreadsheet->getActiveSheet()->SetCellValue('B' . $rowCount, $po_no);
                    $spreadsheet->getActiveSheet()->SetCellValue('C' . $rowCount, $internal_item);
                    $spreadsheet->getActiveSheet()->SetCellValue('D' . $rowCount, str_replace("&#039;", "'",$rbo) );
                    $spreadsheet->getActiveSheet()->SetCellValue('E' . $rowCount, $so_line);
                    $spreadsheet->getActiveSheet()->SetCellValue('F' . $rowCount, $qty_of_line);
                    $spreadsheet->getActiveSheet()->SetCellValue('G' . $rowCount, $ups_total);
                    $spreadsheet->getActiveSheet()->SetCellValue('H' . $rowCount, $material_code);
                    $spreadsheet->getActiveSheet()->SetCellValue('I' . $rowCount, $promise_date);
                    $spreadsheet->getActiveSheet()->SetCellValue('J' . $rowCount, $color_total);
                    $spreadsheet->getActiveSheet()->SetCellValue('K' . $rowCount, $process_code_string);
                    $spreadsheet->getActiveSheet()->SetCellValue('L' . $rowCount, $passes);
                    $spreadsheet->getActiveSheet()->SetCellValue('M' . $rowCount, $frame);    
                    $spreadsheet->getActiveSheet()->SetCellValue('N' . $rowCount, $original_need);    
                    $spreadsheet->getActiveSheet()->SetCellValue('O' . $rowCount, $sheet_batching);   
                    $spreadsheet->getActiveSheet()->SetCellValue('P' . $rowCount, $setup_sheet_total);   
                    $spreadsheet->getActiveSheet()->SetCellValue('Q' . $rowCount, $sheet_packing);   
                    $spreadsheet->getActiveSheet()->SetCellValue('R' . $rowCount, $sheet_total);   
                    $spreadsheet->getActiveSheet()->SetCellValue('S' . $rowCount, $material_code);   
                    $spreadsheet->getActiveSheet()->SetCellValue('T' . $rowCount, $material_size);   

                    $spreadsheet->getActiveSheet()->SetCellValue('U' . $rowCount, $original_need);   
                    $spreadsheet->getActiveSheet()->SetCellValue('V' . $rowCount, $sheet_batching);   
                    $spreadsheet->getActiveSheet()->SetCellValue('W' . $rowCount, $sheet_printing);   
                    $spreadsheet->getActiveSheet()->SetCellValue('X' . $rowCount, $customer_item);   
                    $spreadsheet->getActiveSheet()->SetCellValue('Y' . $rowCount, $process_pass_total);   
                    $spreadsheet->getActiveSheet()->SetCellValue('Z' . $rowCount, $color_passes_total);   
                    $spreadsheet->getActiveSheet()->SetCellValue('AA' . $rowCount, $width); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AB' . $rowCount, $height); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AC' . $rowCount, $running_time); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AD' . $rowCount, $note); 

                    $spreadsheet->getActiveSheet()->SetCellValue('AE' . $rowCount, $machine); 

                    $spreadsheet->getActiveSheet()->SetCellValue('AF' . $rowCount, $designed_scrap); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AG' . $rowCount, $setup_scrap); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AH' . $rowCount, $running_scrap); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AI' . $rowCount, $scrap_total); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AJ' . $rowCount, $count_passes); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AK' . $rowCount, $film_number); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AL' . $rowCount, $running_time_2); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AM' . $rowCount, $setup_time_total); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AN' . $rowCount, $plan_type); 

                    $spreadsheet->getActiveSheet()->SetCellValue('AO' . $rowCount, $count_passes); 
                    $spreadsheet->getActiveSheet()->SetCellValue('AP' . $rowCount, $scrap); 

                    }
                }
                
                
            }

        }


        // close
        echo "</rows>";


        // ============== OUTPUT ==============================================

            // set filename for excel file to be exported
            // config info
            $path = 'uploads/htl/reports/';
            $filename = $path . 'HTL_Reports_' . date("Y-m-d");

            // // // header: generate excel file
            // // header('Content-Type: application/vnd.ms-excel');
            // // header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            // // header('Cache-Control: max-age=0');

            // // // writer
            // // $writer = new Xlsx($spreadsheet);
            // // $writer->save('php://output');

            // save 
            $writer = new Xlsx($spreadsheet);
            $writer->save("$filename.xlsx");


    }

    // show count all orders and count now
    public function countOrders()
    {

        $this->load->model('htl_save_po', 'save_po');
        $countAll = $this->save_po->countAll($this->form_type);
        $countNow = $this->save_po->countNow($this->form_type);
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

    // Master data --------------------------------------------------------------------------------------------------------------------------------------------------------
    // load master
    public function loadPrepressOH()
    {
        // tilte 
        $this->_data['title'] = 'HTL Master File';

        // load models
        $this->load->model('htl_prepress_oh', 'prepress_oh');

        // XML header
        header('Content-type: text/xml');

        // open
        echo "<rows>";

        // header
        $header = '<head>
                    <column width="50" type="ed" align="center" sort="str">No.</column>

                    <column width="180" type="ed" align="center" sort="str">OH/PO NO</column>
                    <column width="180" type="ed" align="center" sort="str">SO Line</column>
                    <column width="120" type="ed" align="center" sort="str">Người cập nhật</column>
                    <column width="120" type="ed" align="center" sort="str">Ngày cập nhật</column>
                    <column width="*" type="link" align="center" sort="str">Tạo Jobjackets</column>
                </head>';

        echo $header;

        // content
        if ($this->prepress_oh->countAll() <= 0) {
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

                echo '</row>';
            }
        } else {

            // get data
            $data = $this->prepress_oh->readOptions(array('form_type' => $this->form_type, 'status' => 0));

            // set data
            $index = 0;
            foreach ($data as $key => $item) {

                $index++;

                $oh = $item['oh'];

                $link  = 'Tạo Jobjacket^javascript:checkDataExist("' . $oh . '");^_self';

                echo '<row id="' . $key . '">';
                echo '<cell>' . $index . '</cell>';

                echo '<cell>' . $oh . '</cell>';
                echo '<cell>' . $item['so_line'] . '</cell>';
                echo '<cell>' . $item['updated_by'] . '</cell>';
                echo '<cell>' . $item['updated_date'] . '</cell>';
                echo '<cell>' . $link . '</cell>';
                echo '</row>';
            }
        }



        // close
        echo "</rows>";
    }


    public function masterFile()
    {
        $this->_data['title'] = 'HTL Master File';

        $this->load->view('htl/master_data/view_masterfile', $this->_data);
    }

    // load master
    public function loadMasterFile()
    {
        // tilte 
        $this->_data['title'] = 'HTL Master File';

        // load models
        $this->load->model('htl_master_item', 'master_item');

        // XML header
        header('Content-type: text/xml');

        // open
        echo "<rows>";

        // header
        $header = '<head>
                    <column width="50" type="ed" align="center" sort="str">No.</column>

                    <column width="110" type="ed" align="center" sort="str">Form Type</column>
                    <column width="140" type="ed" align="center" sort="str">Internal Item</column>
                    <column width="140" type="ed" align="center" sort="str">Material Code</column>
                    <column width="140" type="ed" align="center" sort="str">Material Name</column>
                    <column width="100" type="ed" align="center" sort="str">Material Width</column>
                    <column width="100" type="ed" align="center" sort="str">Material Length</column>
                    <column width="120" type="ed" align="center" sort="str">Product Type</column>
                    
                    <column width="120" type="ed" align="center" sort="str">Plan Type</column>
                    <column width="120" type="ed" align="center" sort="str">Scrap(%)</column>
                    <column width="120" type="ed" align="center" sort="str">Remark 1</column>
                    <column width="120" type="ed" align="center" sort="str">Remark 2</column>
                    <column width="120" type="ed" align="center" sort="str">Remark 3</column>
                    
                    <column width="120" type="ed" align="center" sort="str">Người cập nhật</column>
                    <column width="120" type="ed" align="center" sort="str">Ngày cập nhật</column>
                </head>';

        echo $header;

        // content
        if ($this->master_item->countAll() <= 0) {
            echo ("<rows></rows>");
        } else {

            // get data
            $dataMaster = $this->master_item->readOptions(array('form_type' => $this->form_type));

            // set data
            $index = 0;
            foreach ($dataMaster as $key => $item) {

                $index++;

                echo '<row id="' . $key . '">';
                echo '<cell>' . $index . '</cell>';

                echo '<cell>' . str_replace("&", "&amp;", $item['form_type']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['internal_item']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['material_code']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['material_name']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['material_width']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['material_length']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['product_type']) . '</cell>';


                echo '<cell>' . str_replace("&", "&amp;", $item['plan_type']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['scrap']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['remark_1']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['remark_2']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['remark_3']) . '</cell>';

                echo '<cell>' . $item['updated_by'] . '</cell>';
                echo '<cell>' . $item['updated_date'] . '</cell>';
                echo '</row>';
            }
        }

        // close
        echo "</rows>";
    }

    // load master
    public function loadMasterProcess()
    {
        // tilte 
        $this->_data['title'] = 'HTL Master File';

        // load models
        $this->load->model('htl_master_process', 'master_process');

        // XML header
        header('Content-type: text/xml');

        // open
        echo "<rows>";

        // header
        $header = '<head>
                    <column width="50" type="ed" align="center" sort="str">No.</column>

                    <column width="120" type="ed" align="center" sort="str">Internal Item</column>
                    <column width="120" type="ed" align="center" sort="str">Process</column>
                    <column width="140" type="ed" align="center" sort="str">Tên Process (Vi)</column>
                    <column width="120" type="ed" align="center" sort="str">Process Code</column>
                    <column width="70" type="ed" align="center" sort="str">Thứ tự</column> 

                    <column width="80" type="ed" align="center" sort="str">Khung</column>
                    <column width="80" type="ed" align="center" sort="str">Số lượt</column>
                    <column width="*" type="ed" align="center" sort="str">Thời gian canh chỉnh (HTL)</column>
                    <column width="140" type="ed" align="center" sort="str">Số tờ canh chỉnh (HTL)</column>
                    <column width="120" type="ed" align="center" sort="str">Người cập nhật</column>

                    <column width="120" type="ed" align="center" sort="str">Ngày cập nhật</column>
                </head>';

        echo $header;

        // content
        if ($this->master_process->countAll() <= 0) {
            echo ("<rows></rows>");
        } else {

            // get data
            $data = $this->master_process->readOptions(array('form_type' => $this->form_type));

            // set data
            $index = 0;
            foreach ($data as $key => $item) {

                $index++;

                echo '<row id="' . $key . '">';
                echo '<cell>' . $index . '</cell>';

                echo '<cell>' . str_replace("&", "&amp;", $item['internal_item']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['process']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['process_name_vi']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['process_code']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['order']) . '</cell>';

                echo '<cell>' . str_replace("&", "&amp;", $item['frame']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['passes']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['setup_time']) . '</cell>';
                echo '<cell>' . str_replace("&", "&amp;", $item['setup_sheet']) . '</cell>';
                echo '<cell>' . $item['updated_by'] . '</cell>';

                echo '<cell>' . $item['updated_date'] . '</cell>';
                echo '</row>';
            }
        }

        // close
        echo "</rows>";
    }

    public function masterfileGrid2($master)
    {
        // tilte default
        $this->_data['title'] = 'Master File';

        // XML header
        header('Content-type: text/xml');

        // open
        echo "<rows>";

            if ($master == 'master_setting_process' ) {

                // tilte 
                $this->_data['title'] = 'Master Process Setting';

                // load models
                $this->load->model('htl_setting_process', 'setting_process');

                // header
                $header = '<head>
                            <column width="50" type="ro" align="center" sort="str">No.</column>

                            <column width="110" type="ro" align="center" sort="str">Form Type</column>
                            <column width="140" type="ed" align="center" sort="str" imgdis="fa fa-cloud-download" img="fa fa-cloud-download">Process </column>
                            <column width="140" type="ed" align="center" sort="str">Loại Process</column>
                            <column width="140" type="ed" align="center" sort="str">Tên Process (vi)</column>
                            <column width="140" type="ed" align="center" sort="str">Tên Process (En)</column>

                            <column width="140" type="ro" align="center" sort="str">Người cập nhật</column>
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

                            echo '<cell>'.$this->form_type.'</cell>';
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
                    $data = $this->setting_process->readOptions(array('form_type' => $this->form_type), 'updated_date');

                    // set data
                    $index = 0;
                    foreach ($data as $key => $item) {

                        $index++;

                        echo '<row id="' . $key . '">';
                            echo '<cell>' . $index . '</cell>';

                            echo '<cell>' . str_replace("&", "&amp;", $item['form_type']) . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['process']) . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['type']) . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['process_name_vi']) . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['process_name_en']) . '</cell>';

                            echo '<cell>' . $item['updated_by'] . '</cell>';
                            echo '<cell>' . $item['updated_date'] . '</cell>';
                            echo '<cell></cell>';
                            echo '<cell></cell>';
                        echo '</row>';
                    }

                    $key = $key + 1;
                    for ($i = $index; $i <= ($index + 5); $i++) {

                        echo '<row id="' . $key . '">';
                            echo '<cell>' . $i . '</cell>';

                            echo '<cell>'.$this->form_type.'</cell>';
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

            } else if ($master == 'master_machine' ) {

                // tilte 
                $this->_data['title'] = 'Master Machine';

                // load models
                $this->load->model('htl_master_machine', 'machine');

                // header
                $header = '<head>
                            <column width="50" type="ro" align="center" sort="str">No.</column>

                            <column width="110" type="ro" align="center" sort="str">Form Type</column>
                            <column width="140" type="ed" align="center" sort="str">Mã Máy</column>
                            <column width="140" type="ed" align="center" sort="str">Tên Máy</column>
                            <column width="120" type="ed" align="center" sort="str">Tốc độ máy</column>
                            <column width="120" type="ed" align="center" sort="str">Đơn vị tính</column>

                            <column width="120" type="ro" align="center" sort="str">Người cập nhật</column>
                            <column width="*" type="ro" align="center" sort="str">Ngày cập nhật</column>
                            <column width="70" type="acheck" align="center" sort="str">Save</column>
                            <column width="70" type="acheck" align="center" sort="str">Delete</column>
                        </head>';

                echo $header;

                // content
                if ($this->machine->countAll() <= 0) {
                    $index = 0;
                    for ($i = 0; $i < 5; $i++) {
                        $index++;
                        echo '<row id="' . $i . '">';
                            echo '<cell>' . $index . '</cell>';

                            echo '<cell>'.$this->form_type.'</cell>';
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
                    $data = $this->machine->readOptions(array('form_type' => $this->form_type));

                    // set data
                    $index = 0;
                    foreach ($data as $key => $item) {

                        $index++;

                        echo '<row id="' . $key . '">';
                            echo '<cell>' . $index . '</cell>';

                            echo '<cell>' . str_replace("&", "&amp;", $item['form_type'])  . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['machine']) . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['machine_name']) . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['machine_speed']) . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['machine_unit']) . '</cell>';

                            echo '<cell>' . $item['updated_by'] . '</cell>';
                            echo '<cell>' . $item['updated_date'] . '</cell>';
                            echo '<cell></cell>';
                            echo '<cell></cell>';
                            
                        echo '</row>';
                    }

                    $key = $key + 1;
                    for ($i = $index; $i <= ($index + 5); $i++) {

                        echo '<row id="' . $key . '">';
                            echo '<cell>' . $i . '</cell>';

                            echo '<cell>'.$this->form_type.'</cell>';
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

            } else if ($master == 'master_order_type_local' ) {

                // tilte 
                $this->_data['title'] = 'Master Order Type Local';

                // load models
                $this->load->model('htl_master_order_type_local', 'order_type_local');

                // header
                $header = '<head>
                            <column width="50" type="ro" align="center" sort="str">No.</column>
                            <column width="200" type="ed" align="center" sort="str">Loại Đơn hàng </column>
                            <column width="*" type="ed" align="center" sort="str">Mô tả chi tiết</column>
                            <column width="140" type="ro" align="center" sort="str">Người cập nhật</column>
                            <column width="140" type="ro" align="center" sort="str">Ngày cập nhật</column>

                            <column width="70" type="acheck" align="center" sort="str">Save</column>
                            <column width="70" type="acheck" align="center" sort="str">Delete</column>
                        </head>';

                echo $header;

                // content
                if ($this->order_type_local->countAll() <= 0) {
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
                        echo '</row>';
                    }
                } else {

                    // get data
                    $data = $this->order_type_local->read('updated_date', 'DESC');

                    // set data
                    $index = 0;
                    foreach ($data as $key => $item) {

                        $index++;

                        echo '<row id="' . $key . '">';
                            echo '<cell>' . $index . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['order_type_local']) . '</cell>';
                            echo '<cell>' . str_replace("&", "&amp;", $item['descriptions']) . '</cell>';
                            echo '<cell>' . $item['updated_by'] . '</cell>';
                            echo '<cell>' . $item['updated_date'] . '</cell>';
                            echo '<cell></cell>';
                            echo '<cell></cell>';
                        echo '</row>';
                    }

                    $key = $key + 1;
                    for ($i = $index; $i <= ($index + 5); $i++) {

                        echo '<row id="' . $key . '">';
                            echo '<cell>' . $i . '</cell>';
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


            } else if ($master == 'master_pattern' ) {

                // tilte 
                $this->_data['title'] = 'Master Pattern';

                // load models
                $this->load->model('htl_master_pattern', 'pattern');

                // header
                $header = '<head>
                        <column width="40" type="ro" align="center" sort="str">No.</column>

                        <column width="90" type="ed" align="center" sort="str">Số Khung </column>
                        <column width="90" type="edn" align="center" sort="str">Rộng (Width)</column>
                        <column width="90" type="edn" align="center" sort="str">Dài (Length)</column>
                        <column width="*" type="ed" align="center" sort="str">(Width x Length)</column>
                        <column width="80" type="edn" align="center" sort="str">UPS (Width)</column>

                        <column width="90" type="edn" align="center" sort="str">UPS (Length) </column>
                        <column width="130" type="ed" align="center" sort="str">UPS (Width x Length)</column>
                        <column width="100" type="ro" align="center" sort="str">Người cập nhật</column>
                        <column width="*" type="ro" align="center" sort="str">Ngày cập nhật</column>

                        <column width="70" type="acheck" align="center" sort="str">Save</column>
                        <column width="70" type="acheck" align="center" sort="str">Delete</column>
                    </head>';

                echo $header;

                // content
                if ($this->pattern->countAll() <= 0) {
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
                            echo '<cell></cell>';

                            echo '<cell></cell>';
                            echo '<cell></cell>';
                        echo '</row>';
                    }
                } else {

                    // get data
                    $data = $this->pattern->read();

                    // set data
                    $index = 0;
                    foreach ($data as $key => $item) {

                        $index++;

                        echo '<row id="' . $key . '">';
                            echo '<cell>' . $index . '</cell>';

                            echo '<cell>' . $item['pattern_no'] . '</cell>';
                            echo '<cell>' . (float)$item['width'] . '</cell>';
                            echo '<cell>' . (float)$item['length'] . '</cell>';
                            echo '<cell>' . $item['label_size'] . '</cell>';
                            echo '<cell>' . (float)$item['ups_width'] . '</cell>';

                            echo '<cell>' . (float)$item['ups_length'] . '</cell>';
                            echo '<cell>' . (float)$item['ups'] . '</cell>';
                            echo '<cell>' . $item['updated_by'] . '</cell>';
                            echo '<cell>' . $item['updated_date'] . '</cell>';

                            echo '<cell></cell>';
                            echo '<cell></cell>';

                        echo '</row>';
                    }

                    $key = $key + 1;
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
                            echo '<cell></cell>';

                            echo '<cell></cell>';
                            echo '<cell></cell>';

                        echo '</row>';

                        $key++;
                    }
                }

            } else if ($master == 'master_scrap' ) {

                // tilte 
                $this->_data['title'] = 'Master Scrap';

                // load models
                $this->load->model('htl_master_scrap', 'scrap');

                // header
                $header = '<head>
                        <column width="50" type="ed" align="center" sort="str">No.</column>

                        <column width="140" type="ed" align="center" sort="str">Số lượng giới hạn</column>
                        <column width="110" type="ed" align="center" sort="str">Scrap Màu 1</column>
                        <column width="110" type="ed" align="center" sort="str">Scrap Màu 2</column>
                        <column width="110" type="ed" align="center" sort="str">Scrap Màu 3</column>
                        <column width="110" type="ed" align="center" sort="str">Scrap Màu 4</column>
                        
                        <column width="110" type="ed" align="center" sort="str">Scrap Màu 5</column>
                        <column width="120" type="ed" align="center" sort="str">Người cập nhật</column>
                        <column width="*" type="ed" align="center" sort="str">Ngày cập nhật</column>

                        <column width="70" type="acheck" align="center" sort="str">Save</column>
                        <column width="70" type="acheck" align="center" sort="str">Delete</column>

                    </head>';

                echo $header;

                // content
                if ($this->scrap->countAll() <= 0) {
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

                            echo '<cell></cell>';
                            echo '<cell></cell>';
                            
                        echo '</row>';
                    }
                } else {

                    // get data
                    $data = $this->scrap->read();

                    // set data
                    $index = 0;
                    foreach ($data as $key => $item) {

                        $index++;

                        echo '<row id="' . $key . '">';
                            echo '<cell>' . $index . '</cell>';

                            echo '<cell>' . $item['qty_limit'] . '</cell>';
                            echo '<cell>' . $item['scrap_color_1'] . '</cell>';
                            echo '<cell>' . $item['scrap_color_2'] . '</cell>';
                            echo '<cell>' . $item['scrap_color_3'] . '</cell>';
                            echo '<cell>' . $item['scrap_color_4'] . '</cell>';

                            echo '<cell>' . $item['scrap_color_5'] . '</cell>';
                            echo '<cell>' . $item['updated_by'] . '</cell>';
                            echo '<cell>' . $item['updated_date'] . '</cell>';

                            echo '<cell></cell>';
                            echo '<cell></cell>';
                        echo '</row>';
                    }

                    $key = $key + 1;
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

                            echo '<cell></cell>';
                            echo '<cell></cell>';

                        echo '</row>';

                        $key++;
                    }
                }


            } else if ($master == 'master_setup_time' ) {

                // tilte 
                $this->_data['title'] = 'Master Setup Time';

                // load models
                $this->load->model('htl_master_setup_time', 'setup_time');

                // header
                $header = '<head>
                        <column width="50" type="ro" align="center" sort="str">No.</column>

                        <column width="120" type="ed" align="center" sort="str">Nhóm Mực</column>
                        <column width="120" type="ed" align="center" sort="str">Loại Mực</column>
                        <column width="150" type="ed" align="center" sort="str">Thời gian canh chỉnh</column>
                        <column width="120" type="ro" align="center" sort="str">Người cập nhật</column>
                        <column width="*" type="ro" align="center" sort="str">Ngày cập nhật</column>

                        <column width="70" type="acheck" align="center" sort="str">Save</column>
                        <column width="70" type="acheck" align="center" sort="str">Delete</column>
                    </head>';

                echo $header;

                // content
                if ($this->setup_time->countAll() <= 0) {
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
                    $data = $this->setup_time->read();

                    // set data
                    $index = 0;
                    foreach ($data as $key => $item) {

                        $index++;

                        echo '<row id="' . $key . '">';
                            echo '<cell>' . $index . '</cell>';

                            echo '<cell>' . $item['ink_group'] . '</cell>';
                            echo '<cell>' . $item['ink_type'] . '</cell>';
                            echo '<cell>' . $item['alignment_times'] . '</cell>';
                            echo '<cell>' . $item['updated_by'] . '</cell>';
                            echo '<cell>' . $item['updated_date'] . '</cell>';

                            echo '<cell></cell>';
                            echo '<cell></cell>';
                        echo '</row>';
                    }

                    $key = $key + 1;
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

            }

        // close
        echo "</rows>";


    }

    // import OH
    public function importPrepressOH()
    {
        // title 
        $this->_data['title'] = 'HTL Prepress OH';

        // message 
        $message = 'Import data error';
        $status = false;

        // start
        if ($this->input->post('importfile')) {

            // init var
            $error = 0;

            // set name
            $file_name = ucfirst($this->production_line) . '_PrepressOH_' . $_SERVER['REMOTE_ADDR'] . '_' . $this->updated_by . '_' . date('Y-m-d_H-i-s') . '.xlsx';

            // config info
            $path = 'uploads/htl/';
            $config['upload_path'] = $path;
            $config['allowed_types'] = 'xlsx|xls';
            $config['remove_spaces'] = TRUE;
            $config['file_name'] = $file_name;
            $this->upload->initialize($config);
            $this->load->library('upload', $config);

            // check error (1)
            if (!$this->upload->do_upload('file')) {
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

            // Check ok
            if ($error == 0 && $import_xls_file !== 0) {

                // get file
                $inputFileName = $path . $import_xls_file;

                // init PhpSpreadsheet Xlsx
                $Reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

                // get sheet 0 (sheet 1)
                $spreadSheet = $Reader->load($inputFileName);
                $spreadSheet = $spreadSheet->getSheet(0);
                $allDataInSheet = $spreadSheet->toArray(null, true, true, true);

                // check col name exist
                $createArray = array('OH', 'SOLine');
                $makeArray = array('OH' => 'OH',  'SOLine' => 'SOLine');
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

                    // models
                    $this->load->model('htl_prepress_oh', 'prepress_oh');

                    // count check
                    $countCheck = 0;
                    $countAll = 0;

                    // load data
                    for ($i = 2; $i <= count($allDataInSheet); $i++) {
                        // get col key
                        $oh = $SheetDataKey['OH'];
                        $so_line = $SheetDataKey['SOLine'];

                        // get data 
                        $oh = filter_var(trim(strtoupper($allDataInSheet[$i][$oh])), FILTER_SANITIZE_STRING);
                        $so_line = filter_var(trim($allDataInSheet[$i][$so_line]), FILTER_SANITIZE_STRING);

                        // check empty data
                        if (empty($oh) || empty($so_line)) {
                            $countCheck++;
                            if ($countCheck >= 2) {
                                break;
                            } else {
                                continue;
                            }
                        }

                        // set data
                        $data = array(
                            'form_type' => $this->form_type,
                            'oh' => $oh,
                            'so_line' => $so_line,
                            'updated_by' => $this->updated_by
                        );

                        // check 
                        $where = array('oh' => $oh, 'so_line' => $so_line);
                        if ($this->prepress_oh->isAlreadyExist($where)) {
                            unset($data['oh']);
                            unset($data['so_line']);
                            $result = $this->prepress_oh->update($data, $where);
                        } else {
                            $result = $this->prepress_oh->insert($data);
                        }

                        // set message
                        if ($result) {
                            $countAll++;
                            $message = "Import Dữ liệu thành công $countAll dòng ";
                            $status = true;
                        } else {
                            $message = "Import Dữ liệu lỗi dòng thứ $i ";
                        }
                    }
                } else {
                    $message = "Định dạng file không đúng";
                }
            }
        }

        // result
        $this->_data['results'] = array(
            'status' => $status,
            'message' => $message
        );

        $this->load->view('htl/display', $this->_data['results']);
    }

    // import master data
    public function importMasterFile()
    {
        // title 
        $this->_data['title'] = 'Import Master File';

        // get data
        $production_line = null !== get_cookie('plan_department') ? trim(get_cookie('plan_department')) : 'htl';
        $updated_by = null !== get_cookie('plan_loginUser') ? trim(get_cookie('plan_loginUser')) : '';

        // message 
        $message = 'Import data error';
        $status = false;

        // start
        if ($this->input->post('importfile') && $this->input->get('master')) {

            // init var
            $error = 0;

            // set name
            $master = $this->input->get('master');
            $file_name = ucfirst($this->production_line) . '_' . ucfirst($master) . '_' . $_SERVER['REMOTE_ADDR'] . '_' . $this->updated_by . '_' . date('Y-m-d_H-i-s') . '.xlsx';

            // config info
            $path = 'uploads/htl/';
            $config['upload_path'] = $path;
            $config['allowed_types'] = 'xlsx|xls';
            $config['remove_spaces'] = TRUE;
            $config['file_name'] = $file_name;
            $this->upload->initialize($config);
            $this->load->library('upload', $config);

            // check error (1)
            if (!$this->upload->do_upload('file')) {
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

            // Check ok
            if ($error == 0 && $import_xls_file !== 0) {

                // get file
                $inputFileName = $path . $import_xls_file;

                // init PhpSpreadsheet Xlsx
                $Reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

                // get sheet 0 (sheet 1)
                $spreadSheet = $Reader->load($inputFileName);
                $spreadSheet = $spreadSheet->getSheet(0);
                $allDataInSheet = $spreadSheet->toArray(null, true, true, true);

                // check master
                if ($master == 'masterFile') {
                    // check col name exist
                    $createArray = array('Form Type', 'Internal Item', 'Material Code', 'Material Name', 'Product Type', 'Plan Type', 'Scrap', 'Remark 1', 'Remark 2', 'Remark 3');

                    $makeArray = array(
                        'FormType' => 'FormType',
                        'InternalItem' => 'InternalItem',
                        'MaterialCode' => 'MaterialCode',
                        'MaterialName' => 'MaterialName',
                        'ProductType' => 'ProductType',
                        'PlanType' => 'PlanType',
                        'Scrap' => 'Scrap',
                        'Remark1' => 'Remark',
                        'Remark2' => 'Remark2',
                        'Remark3' => 'Remark3'
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
                    $flag = 0;
                    $data = array_diff_key($makeArray, $SheetDataKey);
                    if (empty($data)) {
                        $flag = 1;
                    }

                    // load data
                    if ($flag == 1) {

                        // models
                        $this->load->model('htl_master_item', 'master_item');

                        // count check
                        $countCheck = 0;
                        $countAll = 0;

                        // load data
                        for ($i = 2; $i <= count($allDataInSheet); $i++) {
                            // get col key
                            $form_type = $SheetDataKey['FormType'];
                            $internal_item = $SheetDataKey['InternalItem'];
                            $material_code = $SheetDataKey['MaterialCode'];
                            $material_name = $SheetDataKey['MaterialName'];
                            $product_type = $SheetDataKey['ProductType'];

                            $plan_type = $SheetDataKey['PlanType'];
                            $scrap = $SheetDataKey['Scrap'];
                            $remark_1 = $SheetDataKey['Remark1'];
                            $remark_2 = $SheetDataKey['Remark2'];
                            $remark_3 = $SheetDataKey['Remark3'];

                            // get data 
                            $form_type = filter_var(trim(strtolower($allDataInSheet[$i][$form_type])), FILTER_SANITIZE_STRING);
                            $internal_item = trim($allDataInSheet[$i][$internal_item]);
                            // Xóa hết tất cả ký tự khác trừ: chữ cái, số, dấu "-"
                            $internal_item = preg_replace('/[^A-Za-z0-9\-]/', '', $internal_item);
                            $internal_item = filter_var(trim(strtoupper($internal_item) ), FILTER_SANITIZE_STRING);

                            // $internal_item = str_replace("﻿&#34;", "", $internal_item);
                            // $internal_item = str_replace("&#34;", "", $internal_item);
                            // $internal_item = str_replace("﻿", "", $internal_item);

                            $material_code = filter_var(trim(strtoupper($allDataInSheet[$i][$material_code])), FILTER_SANITIZE_STRING);
                            $material_name = filter_var(trim($allDataInSheet[$i][$material_name]), FILTER_SANITIZE_STRING);
                            $product_type = filter_var(trim($allDataInSheet[$i][$product_type]), FILTER_SANITIZE_STRING);

                            $plan_type = filter_var(trim($allDataInSheet[$i][$plan_type]), FILTER_SANITIZE_STRING);
                            $scrap = (float)trim($allDataInSheet[$i][$scrap]);
                            $remark_1 = filter_var(trim($allDataInSheet[$i][$remark_1]), FILTER_SANITIZE_STRING);
                            $remark_2 = filter_var(trim($allDataInSheet[$i][$remark_2]), FILTER_SANITIZE_STRING);
                            $remark_3 = filter_var(trim($allDataInSheet[$i][$remark_3]), FILTER_SANITIZE_STRING);

                            // check empty data
                            if (empty($internal_item)) {
                                $countCheck++;
                                if ($countCheck >= 2) {
                                    break;
                                } else {
                                    continue;
                                }
                            }

                            // get material width, length
                            $material_width = '';
                            $material_length = '';
                            if (!empty($material_code)) {
                                $material_code_arr = explode('-', $material_code);
                                $material_size_arr = isset($material_code_arr[2]) ? explode('*', $material_code_arr[2]) : array();
                                if (!empty($material_size_arr)) {
                                    $material_width = $material_size_arr[0];
                                    if (isset($material_size_arr[1])) {
                                        $material_length = ((strpos($material_size_arr[1], '_') !== false)) ? explode('_', $material_size_arr[1])[0] : $material_size_arr[1];
                                    }
                                }
                            }



                            // set data
                            $data = array(
                                'form_type' => $form_type,
                                'internal_item' => $internal_item,
                                'material_code' => $material_code,
                                'material_name' => $material_name,
                                'material_width' => $material_width,

                                'material_length' => $material_length,
                                'product_type' => $product_type,
                                'plan_type' => $plan_type,
                                'scrap' => $scrap,
                                'remark_1' => $remark_1,

                                'remark_2' => $remark_2,
                                'remark_3' => $remark_3,
                                'updated_by' => $updated_by
                            );

                            // save
                            $where = array('internal_item' => $internal_item);
                            if ($this->master_item->isAlreadyExist($where)) {
                                unset($data['internal_item']);
                                $result = $this->master_item->update($data, $where);
                            } else {
                                $result = $this->master_item->insert($data);
                            }

                            // check save 
                            if ($result) {
                                $countAll++;
                                $message = "(Master) Import Dữ liệu thành công $countAll dòng ";
                                $status = true;
                            } else {
                                $message = "(Master) Import Dữ liệu lỗi dòng thứ $i";
                            }
                        }
                    } else {
                        $message = "Định dạng file không đúng";
                    }
                } else if ($master == 'masterProcess') {

                    // check col name exist
                    $createArray = array('Internal Item', 'Process', 'Process Code', 'Order', 'Frame', 'Passes', 'Setup Time', 'Setup Sheet', 'Note');

                    $makeArray = array(
                        'InternalItem' => 'InternalItem',
                        'Process' => 'Process',
                        'ProcessCode' => 'ProcessCode',
                        'Order' => 'Order',
                        'Frame' => 'Frame',
                        'Passes' => 'Passes',
                        'SetupTime' => 'SetupTime',
                        'SetupSheet' => 'SetupSheet',
                        'Note' => 'Note'
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
                    $flag = 0;
                    $data = array_diff_key($makeArray, $SheetDataKey);
                    if (empty($data)) {
                        $flag = 1;
                    }

                    // load data
                    if ($flag == 1) {

                        // models
                        $this->load->model('htl_setting_process', 'setting_process');
                        $this->load->model('htl_master_process', 'master_process');

                        // count check
                        $countCheck = 0;
                        $countAll = 0;

                        // load data
                        for ($i = 2; $i <= count($allDataInSheet); $i++) {
                            // get col key
                            $internal_item = $SheetDataKey['InternalItem'];
                            $process = $SheetDataKey['Process'];
                            $process_code = $SheetDataKey['ProcessCode'];
                            $order = $SheetDataKey['Order'];

                            $frame = $SheetDataKey['Frame'];
                            $passes = $SheetDataKey['Passes'];
                            $setup_time = $SheetDataKey['SetupTime'];
                            $setup_sheet = $SheetDataKey['SetupSheet'];
                            $note = $SheetDataKey['Note'];

                            // get data 
                            $internal_item = trim($allDataInSheet[$i][$internal_item]);
                            // Xóa hết tất cả ký tự khác trừ: chữ cái, số, dấu "-"
                            $internal_item = preg_replace('/[^A-Za-z0-9\-]/', '', $internal_item);
                            $internal_item = filter_var(trim(strtoupper($internal_item) ), FILTER_SANITIZE_STRING);

                            $process = filter_var(trim(strtoupper($allDataInSheet[$i][$process])), FILTER_SANITIZE_STRING);
                            $process_code = filter_var(trim($allDataInSheet[$i][$process_code]), FILTER_SANITIZE_STRING);
                            $order = (int)trim($allDataInSheet[$i][$order]);
                            $frame = filter_var(trim($allDataInSheet[$i][$frame]), FILTER_SANITIZE_STRING);

                            $passes = (int)trim($allDataInSheet[$i][$passes]);
                            $setup_time = (int)trim($allDataInSheet[$i][$setup_time]);
                            $setup_sheet = (int)trim($allDataInSheet[$i][$setup_sheet]);
                            $note = trim($allDataInSheet[$i][$note]);

                            // check empty data
                            if (empty($internal_item) || empty($process)) {
                                $countCheck++;
                                if ($countCheck >= 2) {
                                    break;
                                } else {
                                    continue;
                                }
                            }

                            // get process name vi
                            $process_name_vi = $process;
                            $settingProcess = $this->setting_process->readItem(array('production_line' => $this->production_line, 'process' => $process));
                            if (!empty($settingProcess)) {
                                $process_name_vi = trim($settingProcess['process_name_vi']);
                            }

                            // set data
                            $data = array(
                                'form_type' => $this->form_type,
                                'internal_item' => $internal_item,
                                'process' => $process,
                                'process_code' => $process_code,
                                'process_name_vi' => $process_name_vi,
                                'order' => $order,
                                'frame' => $frame,

                                'passes' => $passes,
                                'setup_time' => $setup_time,
                                'setup_sheet' => $setup_sheet,
                                'updated_by' => $updated_by,
                                'note' => $note
                            );

                            // save
                            $where = array('internal_item' => $internal_item, 'process' => $process, 'order' => $order);
                            if ($this->master_process->isAlreadyExist($where)) {
                                unset($data['internal_item']);
                                unset($data['process']);
                                // unset($data['process_code']);
                                unset($data['order']);
                                $data['updated_date'] = date('Y-m-d H:i:s');
                                $result = $this->master_process->update($data, $where);
                            } else {
                                $result = $this->master_process->insert($data);
                            }

                            // check save 
                            if ($result) {
                                $countAll++;
                                $message = "(Process) Import Dữ liệu thành công $countAll dòng ";
                                $status = true;
                            } else {
                                $message = "(Process) Import Dữ liệu lỗi dòng thứ $i";
                            }
                        }
                    } else {
                        $message = "Định dạng file không đúng";
                    }
                }
            }
        }


        // result
        $this->_data['results'] = array(
            'status' => $status,
            'message' => $message
        );

        $this->load->view('htl/master_data/display', $this->_data['results']);
    }

    // export
    public function exportMasterFile()
    {
        // tilte 
        $this->_data['title'] = 'HTL Master Exports';

        if ($this->input->get('master') ) {

            // load models
            $this->load->model('htl_master_item', 'master_item');
            $this->load->model('htl_master_process', 'master_process');

            // create
            $spreadsheet = new Spreadsheet();

            $option = $this->input->get('master');
            if ($option == 'masterFile' || $option == 'masterProcess' ) {
                
                // ========= MASTER FILE =================================================

                    // set the names of header cells
                    // set Header, width
                    $columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO');
                    
                    // // Add new sheet
                    // $spreadsheet->createSheet();

                    // Add some data
                    $spreadsheet->setActiveSheetIndex(0);

                    // active and set title
                    $spreadsheet->getActiveSheet()->setTitle('MainMaster');

                    $headers = array(
                        'No', 'Form Type', 'Internal Item', 'Material Code', 'Material Name', 'Material Width', 'Material Length', 'Product Type', 'Plan Type', 'Scrap',
                        'Remark 1', 'Remark 2', 'Remark 3', 'Updated By', 'Updated Date'
                    );
        
                    $id = 0;
                    foreach ($headers as $header) {
                        for ($index = $id; $index < count($headers); $index++) {
                            // width
                            $spreadsheet->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(20);
        
                            // headers
                            $spreadsheet->getActiveSheet()->setCellValue($columns[$index] . '1', $header);
        
                            $id++;
                            break;
                        }
                    }
        
        
                    // Font
                    $spreadsheet->getActiveSheet()->getStyle('A1:O1')->getFont()->setBold(true)->setName('Arial')->setSize(10);
                    $spreadsheet->getActiveSheet()->getStyle('A1:O1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('3399ff');
                    $spreadsheet->getActiveSheet()->getStyle('A:O')->getFont()->setName('Arial')->setSize(10);

                    // data
                    $rowCount = 1;
                    $index = 0;
                    $data = $this->master_item->readOptions(array('form_type' => $this->form_type), 'updated_date' );
                    foreach ($data as $key => $element) {

                        $rowCount++;
                        $index++;
                        
                        // get data
                            $form_type = trim($element['form_type']);
                            $internal_item = trim($element['internal_item']);
                            $rbo = trim($element['rbo']);
                            $material_code = trim($element['material_code']);
                            $material_name = trim($element['material_name']);

                            $material_width = trim($element['material_width']);
                            $material_length = trim($element['material_length']);
                            $product_type = trim($element['product_type']);
                            $plan_type = trim($element['plan_type']);
                            $scrap = trim($element['scrap']);
                            
                            $remark_1 = trim($element['remark_1']);
                            $remark_2 = trim($element['remark_2']);
                            $remark_3 = trim($element['remark_3']);
                            $updated_by = trim($element['updated_by']);
                            $updated_date = trim($element['updated_date']);
        
                        // add to excel file
                            $spreadsheet->getActiveSheet()->SetCellValue('A' . $rowCount, $index );
                            $spreadsheet->getActiveSheet()->SetCellValue('B' . $rowCount, $form_type);
                            $spreadsheet->getActiveSheet()->SetCellValue('C' . $rowCount, $internal_item);
                            $spreadsheet->getActiveSheet()->SetCellValue('D' . $rowCount, $material_code);
                            $spreadsheet->getActiveSheet()->SetCellValue('E' . $rowCount, $material_name);
                            $spreadsheet->getActiveSheet()->SetCellValue('F' . $rowCount, $material_width);
                            $spreadsheet->getActiveSheet()->SetCellValue('G' . $rowCount, $material_length);
                            $spreadsheet->getActiveSheet()->SetCellValue('H' . $rowCount, $product_type);
                            $spreadsheet->getActiveSheet()->SetCellValue('I' . $rowCount, $plan_type);
                            $spreadsheet->getActiveSheet()->SetCellValue('J' . $rowCount, $scrap);
                            $spreadsheet->getActiveSheet()->SetCellValue('K' . $rowCount, $remark_1);
                            $spreadsheet->getActiveSheet()->SetCellValue('L' . $rowCount, $remark_2);
                            $spreadsheet->getActiveSheet()->SetCellValue('M' . $rowCount, $remark_3);    
                            $spreadsheet->getActiveSheet()->SetCellValue('N' . $rowCount, $updated_by);    
                            $spreadsheet->getActiveSheet()->SetCellValue('O' . $rowCount, $updated_date);    
                        
                    }


                // ====================== MASTER PROCESS ===========================
                    // Add new sheet
                    $spreadsheet->createSheet();

                    // Add some data
                    $spreadsheet->setActiveSheetIndex(1);

                    // active and set title
                    $spreadsheet->getActiveSheet()->setTitle('Process');

                    $headers = array(
                        'No', 'Internal Item', 'Process', 'Tên Process (Vi)', 'Process Code', 'Order', 'Frame', 'Passes', 'Setup Time', 'Setup Sheet',
                        'Note', 'Người cập nhật', 'Ngày cập nhật'
                    );
        
                    $id = 0;
                    foreach ($headers as $header) {
                        for ($index = $id; $index < count($headers); $index++) {
                            // width
                            $spreadsheet->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(20);
        
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

                    // data
                    $rowCount = 1;
                    $index = 0;
                    $data = $this->master_process->readOptions(array('form_type' => $this->form_type), 'updated_date' );
                    foreach ($data as $key => $element) {

                        $rowCount++;
                        $index++;
                        
                        // get data
                            $internal_item = trim($element['internal_item']);
                            $process = trim($element['process']);
                            $process_name_vi = trim($element['process_name_vi']);
                            $process_code = trim($element['process_code']);

                            $order = trim($element['order']);
                            $frame = trim($element['frame']);
                            $passes = trim($element['passes']);
                            $setup_time = trim($element['setup_time']);
                            $setup_sheet = trim($element['setup_sheet']);

                            $note = trim($element['note']);
                            $updated_by = trim($element['updated_by']);
                            $updated_date = trim($element['updated_date']);
        
                        // add to excel file
                            $spreadsheet->getActiveSheet()->SetCellValue('A' . $rowCount, $index );
                            $spreadsheet->getActiveSheet()->SetCellValue('B' . $rowCount, $internal_item);
                            $spreadsheet->getActiveSheet()->SetCellValue('C' . $rowCount, $process);
                            $spreadsheet->getActiveSheet()->SetCellValue('D' . $rowCount, $process_name_vi);
                            $spreadsheet->getActiveSheet()->SetCellValue('E' . $rowCount, $process_code);

                            $spreadsheet->getActiveSheet()->SetCellValue('F' . $rowCount, $order);
                            $spreadsheet->getActiveSheet()->SetCellValue('G' . $rowCount, $frame);
                            $spreadsheet->getActiveSheet()->SetCellValue('H' . $rowCount, $passes);
                            $spreadsheet->getActiveSheet()->SetCellValue('I' . $rowCount, $setup_time);
                            $spreadsheet->getActiveSheet()->SetCellValue('J' . $rowCount, $setup_sheet);

                            $spreadsheet->getActiveSheet()->SetCellValue('K' . $rowCount, $note);
                            $spreadsheet->getActiveSheet()->SetCellValue('L' . $rowCount, $updated_by);    
                            $spreadsheet->getActiveSheet()->SetCellValue('M' . $rowCount, $updated_date);    
                        
                    }

                // ============== OUTPUT ==============================================


                    // set filename for excel file to be exported
                    $filename = 'HTL_MasterFile_' . date("Y_m_d__H_i_s");

                    // header: generate excel file
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
                    header('Cache-Control: max-age=0');

                    // writer
                    $writer = new Xlsx($spreadsheet);
                    $writer->save('php://output');

                // ====== END ========================================================

            } 

            
            
        }
		
    }

    // Prepress data -------------------------------------------------------------------------------------
    // get file list from dir
    // inclusion (bao gồm)
    public function getFiles($inclusion, $dir = null )
    {
        $list = array();
        $inclusion = strtoupper($inclusion);
        if ($dir == null ) $dir = $this->prepress_dir;

        if ($handle = opendir($dir) ) {

            /* This is the correct way to loop over the directory. */
            while (false !== ($entry = readdir($handle))) {
              if (strpos(strtoupper($entry),$inclusion )!==false ) { 
                //load file vao array
                array_push($list, $entry );
              } 
            }
    
            closedir($handle);
        }

        return $list;
    }

    public function csvToXlsx($csv_file, $oh ) 
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        $csvFile = $reader->load($csv_file);
		
		$productionLine = "./fileCSV/";
        $data = $csvFile->getSheetNames();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);

		if (!empty($data) ) {
			foreach($data as $key => $value ) {
	
				
			}

            // set filename for excel file to be exported
            $filename = ucfirst($this->production_line) . '_Prepress_' . $oh . '_' . $_SERVER['REMOTE_ADDR'] . '_' . $this->updated_by . '_' . date('Y-m-d_H-i-s') . '.csv';

            // header: generate excel file
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            header('Cache-Control: max-age=0');
            // writer
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            

            return true;

		} else {
			return false;
		}
        
    }

    public function detachePrepressData($string ) 
    {
        if (!empty($string) ) {

            // Xóa vị trí đầu và cuối
            if (strpos($string, ';') !== false ) {
                $string = substr($string, 1, strlen($string) );
                $string = substr($string, 0, strlen($string)-1 );
            }

            // Thay Vị trí ở giữa bằng dấu +
            if (strpos($string, ';') !== false ) {
                $string = str_replace(';', '+', $string);
            }
        }

        return $string;
    }

    public function getPrepressData($oh, $dir = null ) 
    {
        $results = array();
        $oh_data = array();
        $ink_usage = array();

        // ob_clean(); // xóa cache
        // ini_set('auto_detect_line_endings',TRUE);

        // get files shared by prepress
        if ($dir == null ) $dir = $this->prepress_dir;
        $files = $this->getFiles('OH', $dir );
        if (!empty($files ) ) {
            foreach ($files as $key => $file ) {

                if (stripos($file, $oh) !==false ) {

                    // save file to HTL folder save
                    $file_name = ucfirst($this->production_line) . '_Prepress_' . $oh . '_' . $this->updated_by . '.csv';
                    // config info
                    $path = 'uploads/htl/';
                    // copy to save
                    copy( ($dir . $file), ($path . $file_name) );

                    // read file (get file direct from prepress htl dir) 
                    $file = $dir . $file;
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                    $reader->setDelimiter(","); // mặc định sử dụng dấu ,
                    $spreadsheet = $reader->load($file);

                    
                
                    $allDataInSheet = $spreadsheet->getActiveSheet()->toArray();


                    // check header
                        // check col name exist
                        $createArray = array( 'Ink', 'Job', 'Need sheet', 'Kich thuoc', 'UP' );
                        $makeArray = array(  'Ink' => 'Ink', 'Job' => 'Job', 'Needsheet' => 'Needsheet', 'Kichthuoc' => 'Kichthuoc', 'UP' => 'UP' );
                        $SheetDataKey = array();
                        foreach ($allDataInSheet as $dataInSheet) {
                            foreach ($dataInSheet as $key => $value) {
                                if (in_array(trim($value), $createArray)) {
                                    $value = preg_replace('/\s+/', '', $value);
                                    $SheetDataKey[trim($value)] = $key;
                                } else { }
                            }

                        }

                        // check data
                            $flag = 0;
                            $data = array_diff_key($makeArray, $SheetDataKey);
                            if (empty($data)) { $flag = 1; }

                    // check header ok
                        if ($flag == 1) {

                            // get data
                            $column_count = 0;
                            $process_max = 1;
                            $dao_count = 1;

                            $SheetDataKey2 = array(); // giữ vị trí của các process
                            $job = '';
                            $sheet_batching = '';
                            $label_size = '';
                            $ups = '';
                            $columns = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15');

                            // lấy vị trí các cột chắc chắn có trong file
                            $job = $SheetDataKey['Job']; 
                            $sheet_batching = $SheetDataKey['Needsheet']; 
                            $label_size = $SheetDataKey['Kichthuoc']; 
                            $ups = $SheetDataKey['UP']; 

                            // Lấy giá trị có các header mặc định trong file
                            $job = filter_var(trim($allDataInSheet[1][$job]), FILTER_SANITIZE_STRING);
                            $sheet_batching = filter_var(trim($allDataInSheet[1][$sheet_batching]), FILTER_SANITIZE_STRING);
                            $label_size = filter_var(trim($allDataInSheet[1][$label_size]), FILTER_SANITIZE_STRING);
                            $ups = filter_var(trim($allDataInSheet[1][$ups]), FILTER_SANITIZE_STRING);

                            $label_size = $this->detachePrepressData($label_size);
                            $ups = $this->detachePrepressData($ups);

                            $oh_data = array(
                                'oh' => $job,
                                'sheet_batching' => $sheet_batching,
                                'label_size' => $label_size,
                                'ups' => $ups
                            );

                            for ($i=0; $i<count($allDataInSheet); $i++ ) { 


                                /*
                                    TẠI DÒNG 1, 
                                    - xác định số DAO có thể có
                                    - Số process max trong tất cả các DAO
                                    - Số lượng cột sau Cột Job đến trước cột Need sheet
                                */ 
                                if ($i == 0 ) {

                                    foreach ($columns as $column ) {
                                        if (!in_array($column, $SheetDataKey) ) {
                                            if ( isset($allDataInSheet[$i][$column]) && (strpos($allDataInSheet[$i][$column], '(') !==false) && (strpos($allDataInSheet[$i][$column], ')') !==false) ) {
                                                $column_count++; // số lượng cột
                                                $SheetDataKey2[] = $column; // Thêm vị trí cột này vào mảng

                                                // Đoạn này tìm xem có bao nhiêu Dao và số process lớn nhất trong tất cả các Dao
                                                // Lấy tầm 20 cột
                                                for ($j=1; $j<=20; $j++ ) {

                                                    
                                                    $string_check = "($j)";
                                                    if ( (strpos($allDataInSheet[$i][$column], $string_check ) !==false) ) {

                                                        // Số Dao
                                                        $dao_count = ($dao_count<=$j) ? $j : $dao_count;
                                                        
                                                        // Kiểm tra trong dòng thứ 2
                                                        for ($k=1; $k<=10; $k++ ) {
                                                            
                                                            $process_k = "S$k";
                                                            if ( (trim($allDataInSheet[1][$column]) == $process_k) ) {

                                                                if (isset($ink_usage[$process_k]) ) {
                                                                    $ink_usage[$process_k] .= ($allDataInSheet[2][$column] . ";");
                                                                } else {
                                                                    $ink_usage[$process_k] = ($allDataInSheet[2][$column] . ";");
                                                                }

                                                                break; // dừng sau khi đã lấy được dữ liệu tại ô cần lấy

                                                            }
                                                        }
                                                        
                                                    }
                                                }

                                            }
                                        }
                                    }
                                } else {

                                    break;

                                }
                                
                                
                                

                                

                            }
                        }

                    
                    
                }
            }
        } else {
            $oh_data = array(
                'oh' => '',
                'sheet_batching' => '',
                'label_size' => '',
                'ups' => ''
            );
        }

        $results = array(
            'oh_data' => $oh_data,
            'ink_usage' => $ink_usage
        );

        return $results;

    }

    /*
        Công thức được cung cấp từ email: "Add lưu lượng sử dụng mực vào Job Jacket"
        7*10^(-5)*TOTAL SHEET*Mm2+IF(TOTAL SHEET<200,400,IF(AND(TOTAL SHEET>=200,TOTAL SHEET<300),500,IF(AND(TOTAL SHEET>=300,TOTAL SHEET<400),600,700)))
    */ 
    public function inkUsageCal($ink, $sheet_total ) 
    {

        $total = 0;
        $result = 0;
        $array = array();

        // Trường hợp không có dữ liệu thì mặc định = 0
        if (empty($ink) ) return 0;

        // Xóa bỏ vị trí đầu tiên nếu là dấu ;
        if (substr($ink, 0, 1 ) == ';' ) {
            $ink = substr($ink, 1, strlen($ink) );
        }

        // Xóa bỏ vị trí cuối cùng nếu là dấu ;
        if (substr($ink, strlen($ink)-1, 1 ) == ';' ) {
            $ink = substr($ink, 0, strlen($ink)-1 );
        }

        // Kiểm tra xem có bao nhiêu Lưu lượng mực để tính trung bình cộng
        if (strpos($ink, ';') !== false ) {
            $array  = explode(';', $ink);
        } else {
            $array = array(
                $ink
            );
        }

        $count = count($array);

        foreach ($array as $value ) {

            // Nếu dấu chấm thập phân được định dạng là dấu , thì đổi lại
            if (strpos($value, ',') !== false ) {
                $value = str_replace(',', '.', $value);
            }
            
            $value = (float)$value;

            $ink_const = 0;
            if ($sheet_total < 200 ) {
                $ink_const = 400;
            } else if ( ($sheet_total>=200) && ($sheet_total<300) ) {
                $ink_const = 500;
            } else if ( ($sheet_total<=300) && ($sheet_total<400) ) {
                $ink_const = 600;
            } else {
                $ink_const = 700;
            }
    
            $total += ( (7 * pow(10,-5) * $sheet_total * $value) + $ink_const );

        }

        
        // trung binh
        $result = $total / $count;

        return round($result, 0); // làm tròn 1 chữ số

    }

    // update form data (main master & process)
    public function updateMasterForm($master )
	{
		// title 
		$this->_data['title'] = 'Update Master Data';

		// init 
        $result = false;
		$status = false;
		$message = "";
        $short_message = "";
        $del = false;

		// get 
		// $data = isset($_POST["data"]) ? $_POST["data"] : '';
        $data = null !== $this->input->post('data') ? trim($this->input->post('data')) : '';
        // $data = '{"internal_item":"AT106331","process":"AG","process_code":"AG Adhesive","process_name_vi":"Lớp Keo Test","order":"4","frame":"48T","passes":"2","setup_time":"17","setup_sheet":"5"}';
        // $master = null !== $this->input->get('master') ? trim($this->input->get('master')) : '';

		// check empty
		if (empty($data)) {
			$message = "Không nhận được dữ liệu";
		} else {

			// $data = '{"event":"save","process_code":"AA","process_name_vi":"Test","process_name_en":""}';
			$data = json_decode($data, true);

            if (strpos($master, 'master_main') !== false ) {

                // load models
                $this->load->model('htl_master_item', 'master_item');
                
                // data
                $form_type = isset($data['form_type']) ? trim(strtolower($data['form_type']) ) : '';
                $internal_item = isset($data['internal_item']) ? trim($data['internal_item']) : '';
                $material_code = isset($data['material_code']) ? trim($data['material_code']) : '';
                $material_name = isset($data['material_name']) ? trim($data['material_name']) : '';
                $material_width = isset($data['material_width']) ? trim($data['material_width']) : '';

                $material_length = isset($data['material_length']) ? trim($data['material_length']) : '';
                $product_type = isset($data['product_type']) ? trim($data['product_type']) : '';
                $plan_type = isset($data['plan_type']) ? trim($data['plan_type']) : '';
                $scrap = isset($data['scrap']) ? trim($data['scrap']) : '';
                $remark_1 = isset($data['remark_1']) ? trim($data['remark_1']) : '';

                $remark_2 = isset($data['remark_2']) ? trim($data['remark_2']) : '';
                $remark_3 = isset($data['remark_3']) ? trim($data['remark_3']) : '';

                // where
                $where = array('internal_item' => $internal_item );
                // data update
                $data = array(
                    'form_type' => $form_type,
                    'material_code' => $material_code,
                    'material_name' => $material_name,
                    'material_width' => $material_width,

                    'material_length' => $material_length,
                    'product_type' => $product_type,
                    'plan_type' => $plan_type,
                    'scrap' => $scrap,
                    'remark_1' => $remark_1,

                    'remark_2' => $remark_2,
                    'remark_3' => $remark_3,
                    'updated_by' => $this->updated_by,
                    'updated_date' => date('Y-m-d H:i:s')
                );

                // short message
                $short_message = "Item: $internal_item";

                // check exist
                if ($this->master_item->isAlreadyExist($where) ) {

                    // update or delete
                    if ($master == 'update_master_main' ) {
                        
                        $result = $this->master_item->update($data, $where );

                    } else if ($master == 'delete_master_main' ) {

                        $result = $this->master_item->delete($where );
                        $del = true;
                    }
                }

            } else if (strpos($master, 'master_process') !== false ) {

                // load models
                $this->load->model('htl_master_process', 'master_process');

                $internal_item = isset($data['internal_item']) ? trim($data['internal_item']) : '';
                $process = isset($data['process']) ? trim($data['process']) : '';
                $process_code = isset($data['process_code']) ? trim($data['process_code']) : '';
                $process_name_vi = isset($data['process_name_vi']) ? trim($data['process_name_vi']) : '';
                $order = isset($data['order']) ? trim($data['order']) : '';

                $frame = isset($data['frame']) ? trim($data['frame']) : '';
                $passes = isset($data['passes']) ? trim($data['passes']) : '';
                $setup_time = isset($data['setup_time']) ? trim($data['setup_time']) : '';
                $setup_sheet = isset($data['setup_sheet']) ? trim($data['setup_sheet']) : '';

                // where
                $where = array('internal_item' => $internal_item, 'process' => $process, 'order' => $order );
                
                // data update
                $data = array(
                    'process_code' => $process_code,
                    'process_name_vi' => $process_name_vi,
                    'frame' => $frame,
                    'passes' => $passes,
                    'setup_time' => $setup_time,
                    'setup_sheet' => $setup_sheet,

                    'updated_by' => $this->updated_by,
                    'updated_date' => date('Y-m-d H:i:s')
                );

                // short message
                $short_message = "Item - Process - Order : $internal_item - $process - $order";

                if ($this->master_process->isAlreadyExist($where) ) {
                    // update or delete
                    if ($master == 'update_master_process' ) {

                        $result = $this->master_process->update($data, $where );

                    } else if ($master == 'delete_master_process' ) {

                        $result = $this->master_process->delete($where );
                        $del = true;

                    }
                } 

            }

		}

        // check result
        if ($result !== TRUE ) {
            
            $message = "Cập nhật dữ liệu lỗi.  ";
            if ($del == true ) {
                $message = "Xóa dữ liệu lỗi. ";
            }

        } else {

            $message = "Cập nhật dữ liệu thành công. ";
            if ($del == true ) {
                $message = "Xóa dữ liệu thành công. ";
            }

        }

        // set message final
        $message .= $short_message;

		// results
		$results = array('status' => $status, 'message' => $message);

		// render
		echo json_encode($results); exit();


	}

    public function updateMasterAuto( )
    {
        // tilte 
        $this->_data['title'] = 'HTL Update Master Data';

        // init 
        $results = array();
        $result = false;
        $status = false;
        $message = 'Lỗi chưa xác định';

        // set post data
        $data = $this->input->post('data');
        // $data = '{"order_type_local":"test","descriptions":"test"}';

        // set get data
        $master = null !== $this->input->get('master') ? trim($this->input->get('master')) : '';
        $delConf = null !== $this->input->get('del') ? trim($this->input->get('del') ) : false;

        // check
        $data = json_decode($data, true);
        if (empty($data)) {
            $message = 'Không nhận được dữ liệu POST!!!';
        } else {

            if ($master == 'master_setting_process' ) {

                // load models
                $this->load->model('htl_setting_process', 'setting_process');

                // get data
                $process = $data['process'];
                $where = array('production_line' => $this->production_line, 'process' => $process);

                if ($delConf == 'del' ) {
                    $del_message = ' Process: ' . $process;
                    if ($this->setting_process->isAlreadyExist($where ) ) {
                        $result = $this->setting_process->delete($where );
                    }
                } else {
                    // set updated by
                    $data['updated_by'] = $this->updated_by;

                    // check is aldready exist
                    if ($this->setting_process->isAlreadyExist($where ) ) {
                        // update
                        unset($data['production_line']);
                        unset($data['process']);
                        $data['updated_date'] = date('Y-m-d H:i:s');
                        $result = $this->setting_process->update($data, $where );
                        
                    } else {
                        // insert
                        $result = $this->setting_process->insert($data);
                    }

                }
                 
            } else if ($master == 'master_machine' ) {

                // load models
                $this->load->model('htl_master_machine', 'machine');

                // get data
                $machine = $data['machine'];
                $where = array('machine' => $machine);

                if ($delConf == 'del' ) {
                    $del_message = ' Máy: ' . $machine;
                    if ($this->machine->isAlreadyExist($where ) ) {
                        $result = $this->machine->delete($where );
                    }
                } else {
                    // set updated by
                    $data['updated_by'] = $this->updated_by;

                    // check is aldready exist
                    if ($this->machine->isAlreadyExist($where ) ) {
                        // update
                        unset($data['machine']);
                        $data['updated_date'] = date('Y-m-d H:i:s');
                        $result = $this->machine->update($data, $where );
                        
                    } else {
                        // insert
                        $result = $this->machine->insert($data);
                    }

                }

            } else if ($master == 'master_order_type_local' ) {

                // load models
                $this->load->model('htl_master_order_type_local', 'order_type_local');

                // get data
                $order_type_local = $data['order_type_local'];
                $where = array('order_type_local' => $order_type_local);

                if ($delConf == 'del' ) {
                    $del_message = ' Loại đơn: ' . $order_type_local;
                    if ($this->order_type_local->isAlreadyExist($where ) ) {
                        $result = $this->order_type_local->delete($where );
                    }
                } else {
                    // set updated by
                    $data['updated_by'] = $this->updated_by;

                    // check is aldready exist
                    if ($this->order_type_local->isAlreadyExist($where ) ) {
                        // update
                        unset($data['order_type_local']);
                        $data['updated_date'] = date('Y-m-d H:i:s');
                        $result = $this->order_type_local->update($data, $where );
                        
                    } else {
                        // insert
                        $result = $this->order_type_local->insert($data);
                    }

                }

            } else if ($master == 'master_pattern' ) {

                // load models
                $this->load->model('htl_master_pattern', 'pattern');

                // get data
                $pattern_no = $data['pattern_no'];
                $where = array('pattern_no' => $pattern_no);

                if ($delConf == 'del' ) {
                    $del_message = ' Số Khuôn: ' . $pattern_no;
                    if ($this->pattern->isAlreadyExist($where ) ) {
                        $result = $this->pattern->delete($where );
                    }
                } else {
                    // set updated by
                    $data['updated_by'] = $this->updated_by;

                    // check is aldready exist
                    if ($this->pattern->isAlreadyExist($where ) ) {
                        // update
                        unset($data['pattern_no']);
                        $data['updated_date'] = date('Y-m-d H:i:s');
                        $result = $this->pattern->update($data, $where );
                        
                    } else {
                        // insert
                        $result = $this->pattern->insert($data);
                    }

                }
                
            } else if ($master == 'master_scrap' ) {

                // models
                $this->load->model('htl_master_scrap', 'scrap');

                // get data
                $qty_limit = $data['qty_limit'];
                $where = array('qty_limit' => $qty_limit);

                if ($delConf == 'del' ) {
                    $del_message = ' Số lượng giới hạn: ' . $qty_limit;
                    if ($this->scrap->isAlreadyExist($where ) ) {
                        $result = $this->scrap->delete($where );
                    }
                } else {
                    // set updated by
                    $data['updated_by'] = $this->updated_by;

                    // check is aldready exist
                    if ($this->scrap->isAlreadyExist($where ) ) {
                        // update
                        unset($data['qty_limit']);
                        $data['updated_date'] = date('Y-m-d H:i:s');
                        $result = $this->scrap->update($data, $where );
                        
                    } else {
                        // insert
                        $result = $this->scrap->insert($data);
                    }

                }

            } else if ($master == 'master_setup_time' ) {

                // models
                $this->load->model('htl_master_setup_time', 'setup_time');

                // get data
                $ink_group = $data['ink_group'];
                $ink_type = $data['ink_type'];
                $where = array('ink_group' => $ink_group, 'ink_type' => $ink_type );

                if ($delConf == 'del' ) {
                    $del_message = ' Nhóm: ' . $ink_group . ' & Loại mực: ' . $ink_type;
                    if ($this->setup_time->isAlreadyExist($where ) ) {
                        $result = $this->setup_time->delete($where );
                    }
                } else {
                    // set updated by
                    $data['updated_by'] = $this->updated_by;

                    // check is aldready exist
                    if ($this->setup_time->isAlreadyExist($where ) ) {
                        // update
                        unset($data['ink_group']);
                        unset($data['ink_type']);
                        $data['updated_date'] = date('Y-m-d H:i:s');
                        $result = $this->setup_time->update($data, $where );
                        
                    } else {
                        // insert
                        $result = $this->setup_time->insert($data);
                    }

                }


            }
        
        

        
        }

        // check
        if ($result !== TRUE ) {
            $message = 'Có lỗi Xử lý dữ liệu';
        } else {

            $status = true;
            $message = 'Cập nhật dữ liệu thành công';
            if ($delConf == 'del' ) {
                $message = 'Xóa dữ liệu '.$del_message.' thành công';
            }
            
        }

        // result
        $results = array( 'status' => $status, 'message' => $message );

        // results
        echo json_encode($results, JSON_UNESCAPED_UNICODE); exit();
    }


    // remark --------------------------------------------------------------------------------------------------------------------------------------------------------


    /* Lưu remark: Nếu không có remark để lưu hoặc lưu thành công thì trả về TRUE, ngược lại trả về FALSE */
    public function remark($productionLine, $po_no, $remarkCheckArr)
    {
        // load models
        $this->load->model('common_remarks', 'remarks');
        $this->load->model('common_remark_po_save', 'remark_po_save');

        // xóa các remark cũ đã lưu trước đó. Tránh trường hợp một remark xóa rồi nhưng do làm lệnh trước thì vẫn còn hiển thị khi in
        $this->remark_po_save->deleteNO($po_no);
        $remarkCheck = $this->remarks->readProductionLine($productionLine);

        $result = TRUE;
        if (!empty($remarkCheck)) {
            foreach ($remarkCheck as $value) {
                // get data
                $condition_code = $value['condition_code'];
                $conditions = json_decode($value['conditions']);
                $remark = trim($value['remark']);

                // check
                $count = 0; // đếm xem có mấy điều kiện trong remark
                $count2 = 0; // đếm xem có mấy điều kiện trong dữ liệu cần kiểm tra
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
                        $updateData = array(
                            'condition_code' => $condition_code,
                            'conditions' => json_encode($conditions, JSON_UNESCAPED_UNICODE),
                            'updated_by' => $this->updated_by,
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
                            'updated_by' => $this->updated_by
                        );

                        $result = $this->remark_po_save->create($insertData);
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

    // remark packing instruction, KHONG KIM LOAI
    public function packingInstrRemark($production_line, $po_no, $packing_instr, $rbo)
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

        // save
        if (!empty($remark) || !empty($packing_instr)) {

            $where = array('production_line' => $production_line, 'po_no' => $po_no, 'remark' => $remark);

            if ($this->remark_po_save->isAlreadyExist($where)) {
                $result = $this->remark_po_save->update(array('packing_instr' => $packing_instr, 'updated_by' => $updated_by, 'updated_date' => date('Y-m-d H:i:s')), $where);
            } else {
                $result = $this->remark_po_save->create(array('production_line' => $production_line, 'po_no' => $po_no, 'remark' => $remark, 'packing_instr' => $packing_instr, 'updated_by' => $updated_by));
            }

            // result
            return ($result) ? TRUE : FALSE;
        } else {
            return TRUE;
        }
    }

    public function materialRemark($po_no, $material_code, $process_code_remark_check )
    {

        $remark = '';
        $materialArr = array(
            'HT-B00014-330*1000',
            'HT-B00014-330*1000',
            'HT-B00001-330*480',
            'HT-B00001-550*700'
        );

        if ($process_code_remark_check == true ) {
            foreach ($materialArr as $material_code_check ) {
                if ($material_code == $material_code_check ) {
                    $remark = 'SỬ DỤNG WHITE DIAOSHUN CHO MỰC ( GỐC), BACKER ( GỐC)';
                    break;
                }
                
            }
        }
        

        // save to remark table
        if (empty($remark ) ) {
            // trường hợp không có remark save
            $result = TRUE;
        } else {

            if ($this->remark_po_save->isAlreadyExist(array('production_line' => $this->production_line, 'po_no' => $po_no, 'remark' => $remark))) {
                $updateData = array(
                    'condition_code' => '',
                    'conditions' => '',
                    'updated_by' => $this->updated_by,
                    'updated_date' => date('Y-m-d H:i:s')
                );
                $result =  $this->remark_po_save->update($updateData, array('production_line' => $this->production_line, 'po_no' => $po_no, 'remark' => $remark));
            } else {

                // insert
                $insertData = array(
                    'production_line' => $this->production_line,
                    'po_no' => $po_no,
                    'remark' => $remark,
                    'condition_code' => '',
                    'conditions' => '',
                    'updated_by' => $this->updated_by
                );

                $result = $this->remark_po_save->create($insertData);
            }

            if ( ($result != TRUE) || ($result != true) ) {
                $result = FALSE;
            } 

        }

        // result
        return $result;

    }

    

}
