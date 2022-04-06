<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include_once APPPATH . "/vendor/autoload.php";
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Thermal extends CI_Controller {

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
    
        //get automail updated date
		$this->_data['automail_updated'] = $this->getAutomailUpdated();

        
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
		$department = isset($_COOKIE['plan_department']) ? $_COOKIE['plan_department'] : '';
		$title = $department . ' planning';
		$this->_data['title'] = strtoupper($title);

		if (!$this->checkLogin() ) {
			$this->load->view('users/index', $this->_data);
		} else {
			if (empty($department) || $department != 'thermal' ) {
				$this->load->view('users/index', $this->_data);
			} else {
				$this->load->view('thermal/index', $this->_data);
			}

		}

    }

    // check login
	public function checkLogin()
	{
		return isset($_COOKIE['plan_loginUser']) ? true : false;
	}

    public function recent() 
    {
        // load models
            $this->load->model('thermal_save_po', 'save_po');
            $this->load->model('thermal_save_po_soline','save_po_soline');
            $this->load->model('common_users');

        // get distance
            $updated_by = null !== get_cookie('plan_loginUser') ? get_cookie('plan_loginUser') : '';
            $form_type = null !== get_cookie('print_type_thermal') ? get_cookie('print_type_thermal') : '';
            $from_date = null !== $this->input->get('from_date') ? trim($this->input->get('from_date')) : '';
            $to_date = null !== $this->input->get('to_date') ? trim($this->input->get('to_date')) : '';

        // get user info
            $userInfo = $this->common_users->readItem($updated_by);
            $account_type = isset($userInfo['account_type']) ? $userInfo['account_type'] : '';

        // get SOLine save data
            if (!empty($from_date) && !empty($to_date) ) {
                if ($account_type == 3 || $account_type == 9 ) {
                    $solineData = $this->save_po_soline->readDistance('', '', $from_date, $to_date );
                } else {
                    $solineData = $this->save_po_soline->readDistance($form_type, $updated_by, $from_date, $to_date );
                }
                
            } else {
                if ($account_type == 3 || $account_type == 9 ) {
                    $solineData = $this->save_po_soline->read('', '', 'DESC');
                } else {
                    $solineData = $this->save_po_soline->read($form_type, $updated_by, 'DESC');
                }
                
            }
            

        // get all data
        $index = 0;
            $results = array();
		foreach ($solineData as $item ) {

            $index++;

            // get data
                $po_no = trim($item['po_no']);
                $so_line = trim($item['so_line']);
                $orders = $so_line;

                $solineItem = $this->save_po_soline->readSOLine($po_no);
                if (count($solineItem) >=2 ) {
                    $so_line_arr = explode('-',$so_line );
                    $orders = $so_line_arr[0];
                }

            // get po data save
                $poData = $this->save_po->readSingle($po_no);
            // check 
            if (!empty($poData) ) {
                $prefix_url = base_url('thermal/');

                if ($poData['printed'] == 1 ) {  
                    $printed = '<span style="color:red;font-weight:bold;font-size:13px;"><a target="_blank" href="' . $prefix_url .'printOrders/'. $po_no .'" title="printed" rel="follow, index">Printed</a></span>';
                } else {
                    $printed = '<span style="color:blue;text-decoration:none;font-weight:bold;font-size:13px;"><a target="_blank" href="' . $prefix_url .'printOrders/'. $po_no .'" title="print" rel="follow, index">Print</a></span>';
                }

                $edit = '<span style="color:blue;font-weight:bold;font-size:13px;"><a href="' . $prefix_url .'handle/?orders='. $orders .'&po_no_edit='. $po_no .'" title="Edit" rel="follow, index" >Edit</a></span>';
                $delete = '<span style="color:blue;font-weight:bold;font-size:13px;"><a href="' . $prefix_url .'delete/'. $po_no .'" title="Delete" rel="follow, index" onclick="return delete_confirm('."'$po_no'".');">Delete</a></span>';
                // set results
                $results[] = [
                    'id' => $index,
                    'data' => [
                        $poData['form_type'],
                        $poData['po_date'],
                        $item['po_no'],
                        $poData['po_no_suffix'],
                        $so_line,
                        $item['qty_of_line'],
                        html_entity_decode($item['rbo'],ENT_QUOTES),
                        $item['internal_item'],
                        $poData['updated_by'],
                        $poData['updated_date'],
                        $printed,
                        $edit,
                        $delete
                    ]
                ];

            }
            
        }
            
        // result
            echo json_encode($results, JSON_UNESCAPED_UNICODE); exit();

    }

    public function getSizeAutomail($string) 
    {
        //init var
        $dataResults = [];
        $size = $color = $qty = $material_code = '';
        $errorCount = $check_exist = $pause = 0;
        $sizepos = $colorpos = $qtypos = $materialcodepos = $maxpos = '';

        //loại bỏ các khoảng trắng và ký tự thừa do người dùng nhập không đúng
        $string = str_replace(" ", "",$string);
        $string = str_replace(":;:", ";",$string);
        $string = str_replace(":;", ";",$string);
        $string = str_replace(";:", ";",$string);
        $string = str_replace("^^^", "^",$string);
        $string = str_replace("^^", "^",$string);

        if (strpos($string, ";Total")!==false || strpos($string, ";total")!==false ) {
            // nothing
        } else if (strpos($string,"Total")!==false ) {
            $string = str_replace("Total", ";Total",$string);
        } else if (strpos($string,"total")!==false ) {
            $string = str_replace("total", ";Total",$string);
        }

        //Lấy Ký tự cuối check xem phải là ký tự: ^ hay k, k phải thì trả về lỗi
        $check = substr( $string,  strlen($string)-1, 1 );
        if ($check !== '^') {$pause = 1;}

        //Tách chuỗi thành mảng, mỗi phần tử cần lấy các nội dung: Size, color, qty, material_code
        $string_explode = explode(";",$string);
        
        //Đoạn code xác định vị trí size, color, qty, material_code.
        foreach ($string_explode as $stringpos) {
            $detachedpos = explode(":",$stringpos);
            
            for ($i=0;$i<count($detachedpos);$i++) {
                if (strpos(strtoupper($detachedpos[$i]),"SIZE")!==false ) { $sizepos=$i; $maxpos = count($detachedpos);}
                if (strpos(strtoupper($detachedpos[$i]),"COLOR")!==false) { $colorpos=$i; $maxpos = count($detachedpos); }
                if (strpos(strtoupper($detachedpos[$i]),"QUANTITY")!==false){ $qtypos=$i; $maxpos = count($detachedpos);}
                if (strpos(strtoupper($detachedpos[$i]),"MATERIAL CODE")!==false || strpos(strtoupper($detachedpos[$i]),"MATERIALCODE")!==false){ $materialcodepos=$i; $maxpos = count($detachedpos); }

                
            }

            if ($materialcodepos ) break;
        }
	
        //Nếu có data và có ký tự ^ (data k bị mất). Trường hợp ngược lại không them vào
        if(!empty($string_explode) && !$pause){
            // // echo "\n maxpos: " . $maxpos . "\n";
            foreach ($string_explode as $key => $value) {
                $check_exist=0;
                //get format string  detached.
                $detachedStringAll = trim($value);

                //check error. Nếu không đúng định dạng => return error
                if(substr_count($detachedStringAll,":")<3){//Trường hợp min = 4 col
                    $errorCount++; continue;
                }

                //tách chuỗi thành mảng bởi ký tự :
                $detachedString = explode(":",$detachedStringAll);
                
                //check detachedString không đúng định dạng. Dừng
                if (count($detachedString) !=$maxpos) {$errorCount++; continue;}

                //get data
                if ( $sizepos!=$colorpos && $colorpos!=$qtypos && $qtypos!=$materialcodepos ) {
                    //lấy dữ liệu //Trường hợp không lấy được cột data nào thì cho dữ liệu đó = rỗng.
                    $size = !empty($sizepos) ? trim($detachedString[$sizepos]) : 'NON';
                    $color = !empty($colorpos) ? trim($detachedString[$colorpos]) : 'NON';
                    $qty = !empty($qtypos) ? $detachedString[$qtypos] : 0;
                    $material_code = !empty($materialcodepos) ? trim($detachedString[$materialcodepos]): ''; //tam thoi lay vi tri nay
                   
                    /* *** Check trường hợp OE không nhập dấu ; trước chữ Total, dấu ^, (còn thì thêm vào ...) *** */
                    $character_error_arr = [
                        'Total',
                        '^'
                    ];

                    //Tìm các dữ liệu thừa để tách chuỗi thành mảng từ ký tự đó và lấy ra phần tử dữ liệu đã tách.
                    foreach ($character_error_arr as $key => $value) {
                        if (strpos(strtoupper($size),strtoupper($value))!==false) {
                            $detached_tmp = explode($value,$size);
                            $size = $detached_tmp[0];
                        }
        
                        if (strpos(strtoupper($color),strtoupper($value))!==false) {
                            $detached_tmp = explode($value,$color);
                            $color = $detached_tmp[0];
                        }
        
                        if (strpos(strtoupper($qty),strtoupper($value))!==false) {
                            $detached_tmp = explode($value,$qty);
                            $qty = $detached_tmp[0];
                        }
        
                        if (strpos(strtoupper($material_code),strtoupper($value))!==false) {
                            $detached_tmp = explode($value,$material_code);
                            $material_code = $detached_tmp[0];
                        }
                    } //end for

                }

                if(!is_numeric($qty)){//kiểm tra qty có phải số không
                    $errorCount++;
                } else {
                    //check data ton tai chua, neu ton tai => cong them vao qty
                    if (!empty($dataResults)) {
                        foreach($dataResults as $key => $value){

                            if( $value['size']==$size && $value['color']==$color && $value['material_code']==$material_code ){
                                $dataResults[$key]['qty'] += $qty;//cộng thêm vào
                                $check_exist = 1;
                            }
                        }

                        //Không tồn tại thì thêm vào mảng kết quả
                        if($check_exist==0){
                            $get = [
                                'size' 			=> $size,
                                'color' 		=> $color,
                                'qty' 			=> $qty,
                                'material_code' => $material_code
                            ];
                            array_push($dataResults,$get);

                        }


                    } else {//trường hợp đầu tiên
                        $get = [
                            'size' 			=> $size,
                            'color' 		=> $color,
                            'qty' 			=> $qty,
                            'material_code' => $material_code
                        ];
                        array_push($dataResults,$get);
                    }

                }

            }

            //return result data
            return $dataResults;

        }
    }

    public function getSample($packing_instr, $order_number ) 
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
            foreach($matches as $R){$sample_sinnal = $sample_sinnal . $R[0];}
            if (!empty($sample_sinnal)) {
                $sample = 2;
            }
            // 2. Hoặc Tìm các từ có trong mảng dưới đây
            $sample_signal_arr = array('MAU CUA SO#','SAMPLE CUA SO#');
            // check option 2
            foreach ($sample_signal_arr as $signal ) {
                if (strpos($packing_instr, $signal )) {
                    $sample = 2; 
                    break;
                }
            }
        }

        return $sample;
    }

    public function getGPM($customer_job ) 
    {
        if (empty($customer_job)) {
            $gpm = '';
        } else {
            /** 1.  xử lý dữ liệu thô là cột customer_job trong table oe_soview_text */ 
            $raw_gpm = trim($customer_job);
            $raw_gpm = str_replace(' ', '',$raw_gpm);
            /** 2.  Lấy chiều dài từ đầu đến vị trí dấu '/' */ 
            if (strpos($customer_job,'/')!== false) {
                $gpm_len =  strpos($raw_gpm,'/') - 3;
            } else {
                $gpm_len = strlen($raw_gpm); 
            }
            
            /** 3.  Lấy số ký tự chữ số là GPM */ 
            $gpm_clear = preg_replace('/[^0-9]/', '', $raw_gpm);
            /** 4. Kiểm tra chắc chắn đúng các ký tự dạng số chưa */
            $gpm = is_numeric(substr($gpm_clear,0,$gpm_len))?substr($gpm_clear,0,$gpm_len):'';
        }
		
        
        return $gpm;
    }

    // create prefix no
	public function createPrefixNo($production_line )
	{

        $module = isset($_COOKIE['module']) ? $_COOKIE['module'] : '';
		$production_line = (strpos($production_line, ' ') !== false) ? str_replace(' ', '', $production_line) : $production_line;
		$production_line = strtolower($production_line );
		$module = (strpos($module, ' ') !== false) ? str_replace(' ', '', $module) : $module;

		// load models
        $this->load->model('thermal_save_po', 'save_po');
        $this->load->model('common_prefix_no');

        // result array
        $po_date_new = '';
		$prefix_new = '';
		$suffix_new = '';

        // check
        if (empty($production_line) || empty($module) ) { return false; }

        /*
            | ------------------------------------------------------------------------------------------------------------
            | 1. LẤY GIÁ TRỊ PREFIX TRONG BẢNG common_prefix_no
            | ------------------------------------------------------------------------------------------------------------
		*/
			$prefix = '';
			if ($this->common_prefix_no->isAlreadyExist($production_line, $module) ) {
				$common_prefix_no_item = $this->common_prefix_no->readSingle($production_line, $module);
				$prefix = $common_prefix_no_item['prefix'];
			}

			// check
			if (empty($prefix) ) return false;
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

            $po_date_time = date('Y-m-d');
            $YearMonth = date('ym');

			// // $po_date_time=date_create("$day-$mon-$year");
			// // if ($hours >= 12 ) {
			// // 	date_add($po_date_time, date_interval_create_from_date_string("1 days"));
			// // }
			// // $po_date_time = date_format($po_date_time,"Y-m-d");

            // // $YearMonth = date('ym', strtotime($po_date_time) ); // Lấy năm, tháng của hệ thống, trả về dạng: 2002
            // set giá trị tiền tố mới, so sánh với tiền tố PO_NO trong bảng (vừa lấy). // Nếu giống thì chỉ cần tăng hậu tố lên 1 đơn vị, ngược lại thì lấy tiền tố nào lớn
            $prefix_time = $prefix . $YearMonth;

        /*
            | ------------------------------------------------------------------------------------------------------------
            | 3. LẤY PO_NO MỚI NHẤT DỰA THEO MODULE (PREFIX)
            | ------------------------------------------------------------------------------------------------------------
        */
            $LastNO = $this->save_po->getLastNO($production_line, $prefix_like);
            if (!empty($LastNO)) {
                /**  ------------------------- CREATE PO DATE ------------------------- -------------------------   */
                $po_date_cur = $LastNO['po_date'];
                // Nếu ngày po trong bảng >= ngày hệ thống thì lấy ngày hiện tại
                if (strtotime($po_date_cur) >= strtotime($po_date_time)) {
                    $po_date_new = date('Y-m-d', strtotime($po_date_cur));
                } else {
					$po_date_new = date('Y-m-d', strtotime($po_date_time));
                }

                /**  ------------------------- CREATE PREFIX PO NO ------------------------- -------------------------   */
                //Tách PO_NO trong bảng save vừa lấy thành mảng
                $lastNO_arr = explode('-',$LastNO['po_no'] );
                $prefix_cur = $lastNO_arr[0];
                $suffix_cur = (int)$lastNO_arr[1]; // Chuyển đổi thành kiểu số

                // So sánh hai tiền tố với nhau.
                // Trường hợp 1: Nếu prefix từ bảng save > prefix tháng năm hiện tại hoặc bằng => User đã fix tăng lên, nên lấy prefix trong bảng save
                if (strcmp($prefix_cur, $prefix_time)>=0) {
					$prefix_new = $prefix_cur;

					//Sau khi có
                    $suffix_new_tmp = $suffix_cur + 1;
                    // Đếm số ký tự có hậu tố để thêm vào các dãy số 0 cho đúng định dạng
                    $suffix_length = strlen((string)$suffix_new_tmp);
                    // fix đúng định dạng
                    if ( $suffix_length == 1 ) {
                        $suffix_new = '0000'.$suffix_new_tmp;
                    } else if ( $suffix_length == 2 ) {
                        $suffix_new = '000'.$suffix_new_tmp;
                    } else if ( $suffix_length == 3 ) {
                        $suffix_new = '00'.$suffix_new_tmp;
                    } else if ( $suffix_length == 4 ) {
                        $suffix_new = '0'.$suffix_new_tmp;
                    } else if ( $suffix_length == 5 ) {
                        $suffix_new = $suffix_new_tmp;
                    }

                } else { //Trường hợp prefix hiện tại < prefix tháng năm => lấy prefix tháng năm (tăng lên theo tháng thực tế) mới, bắt đầu = 00001 (5 chữ số)
					$prefix_new = $prefix_time;
					$suffix_new = '-00001';
                }

            } else { // Trường hợp không tìm thấy dạng tiền tố truy vấn có trong po_save
                $po_date_new = date('Y-m-d', strtotime($po_date_time));
                // set po_no prefix
				$prefix_new = $prefix_time;
				$suffix_new = '-00001';
            }

        // result
        return array('po_date_new' => $po_date_new, 'prefix_new' => $prefix_new, 'suffix_new' => $suffix_new );

	}

    // create no
	public function createNo($prefix )
	{
		$po_no_new = ''; // result

		// load models
        $this->load->model('thermal_save_po', 'save_po');
		$this->load->model('common_prefix_no');

		$prefix = (strpos($prefix, ' ') !== false) ? str_replace(' ', '', $prefix) : $prefix ; // NO2006 format

		$prefix_check = substr($prefix, 0, (strlen($prefix) - 4) );
		$prefix_item = $this->common_prefix_no->readPrefix($prefix_check );
		if (empty($prefix_item ) ) {
			return false;
		} else {
			// to query
			$prefix_like = "%$prefix%";
			$production_line = strtolower($prefix_item['production_line']);
			$LastNO = $this->save_po->getLastNO($production_line, $prefix_like );
			if (!empty($LastNO) ) {

				//Tách PO_NO trong bảng save vừa lấy thành mảng
				$lastNO_arr = explode('-',$LastNO['po_no'] );
				// $prefix_cur = $lastNO_arr[0];
				$suffix_cur = (int)$lastNO_arr[1]; // Chuyển đổi thành kiểu số

				//Sau khi có
				$suffix_new_tmp = $suffix_cur + 1;
				// Đếm số ký tự có hậu tố để thêm vào các dãy số 0 cho đúng định dạng
				$suffix_length = strlen((string)$suffix_new_tmp);
				// fix đúng định dạng
				if ( $suffix_length == 1 ) {
					$suffix_new = '0000'.$suffix_new_tmp;
				} else if ( $suffix_length == 2 ) {
					$suffix_new = '000'.$suffix_new_tmp;
				} else if ( $suffix_length == 3 ) {
					$suffix_new = '00'.$suffix_new_tmp;
				} else if ( $suffix_length == 4 ) {
					$suffix_new = '0'.$suffix_new_tmp;
				} else if ( $suffix_length == 5 ) {
					$suffix_new = $suffix_new_tmp;
				}

				// set po_no new
				$po_no_new = $prefix.'-'.$suffix_new;

			} else {
				// set po_no new
				$suffix_new = '-00001';
				$po_no_new = $prefix . $suffix_new;
			}
		}

        return $po_no_new;

	}

    public function createNoDate()
    {
        //get department, module
        $production_line = isset($_COOKIE['department']) ? strtolower($_COOKIE['department']) : '';
        $module = isset($_COOKIE['module']) ? $_COOKIE['module'] : '';
        // load models
        $this->load->model('thermal_save_po', 'save_po');
        $this->load->model('common_prefix_no');

        // result array
        $result = array();
        $po_date_new = '';
        $po_no_new = '';

        // check
        if (empty($production_line) || empty($module)) { return false; }

        /*
            | ------------------------------------------------------------------------------------------------------------
            | 1. LẤY GIÁ TRỊ PREFIX TRONG BẢNG PO_PREFIX_NO
            | ------------------------------------------------------------------------------------------------------------
        */
            $po_prefix_no_item = $this->po_prefix_no->readSingle($production_line, $module);
            $prefix = strtoupper($po_prefix_no_item['prefix']);
            $prefix_like = "%$prefix%";

        /*
            | ------------------------------------------------------------------------------------------------------------
            | 2. SET CÁC GIÁ TRỊ THỜI GIAN HIỆN TẠI VÀ PREFIX HIỆN TẠI
            | ------------------------------------------------------------------------------------------------------------
        */
            $po_date_time = date('Y-m-d');
            $YearMonth = date('ym'); // Lấy năm, tháng của hệ thống, trả về dạng: 2002
            // set giá trị tiền tố mới, so sánh với tiền tố PO_NO trong bảng (vừa lấy). // Nếu giống thì chỉ cần tăng hậu tố lên 1 đơn vị, ngược lại thì lấy tiền tố nào lớn
            $prefix_time = $prefix . $YearMonth;

        /*
            | ------------------------------------------------------------------------------------------------------------
            | 3. LẤY PO_NO MỚI NHẤT DỰA THEO MODULE (PREFIX)
            | ------------------------------------------------------------------------------------------------------------
        */
            $LastNO = $this->save_po->getLastNO($production_line, $prefix_like);
            if (!empty($LastNO)) {
                /**  ------------------------- CREATE PO DATE ------------------------- -------------------------   */
                $po_date_cur = $LastNO['po_date'];
                // Nếu ngày po trong bảng >= ngày hệ thống thì lấy ngày hiện tại
                if (strtotime($po_date_cur) >= strtotime($po_date_time)) { 
                    $po_date_new = date('Y-m-d', strtotime($po_date_cur));
                } else {
                    $po_date_new = date('Y-m-d', strtotime($po_date_time));
                }

                /**  ------------------------- CREATE PO NO ------------------------- -------------------------   */
                //Tách PO_NO trong bảng save vừa lấy thành mảng
                $lastNO_arr = explode('-',$LastNO['po_no']);
                $prefix_cur = $lastNO_arr[0];
                $suffix_cur = (int)$lastNO_arr[1]; // Chuyển đổi thành kiểu số

                // So sánh hai tiền tố với nhau. 
                // Trường hợp 1: Nếu prefix từ bảng save > prefix tháng năm hiện tại hoặc bằng => User đã fix tăng lên, nên lấy prefix trong bảng save
                if (strcmp($prefix_cur, $prefix_time)>=0) {
                    $prefix_new = $prefix_cur;
                    //Sau khi có 
                    $suffix_new_tmp = $suffix_cur + 1;
                    // Đếm số ký tự có hậu tố để thêm vào các dãy số 0 cho đúng định dạng
                    $suffix_length = strlen((string)$suffix_new_tmp);
                    // fix đúng định dạng
                    if ( $suffix_length == 1 ) {
                        $suffix_new = '0000'.$suffix_new_tmp;
                    } else if ( $suffix_length == 2 ) {
                        $suffix_new = '000'.$suffix_new_tmp;
                    } else if ( $suffix_length == 3 ) {
                        $suffix_new = '00'.$suffix_new_tmp;
                    } else if ( $suffix_length == 4 ) {
                        $suffix_new = '0'.$suffix_new_tmp;
                    } else if ( $suffix_length == 5 ) {
                        $suffix_new = $suffix_new_tmp;
                    }
                    // set po_no new
                    $po_no_new = $prefix_new.'-'.$suffix_new;
                } else { //Trường hợp prefix hiện tại < prefix tháng năm => lấy prefix tháng năm (tăng lên theo tháng thực tế) mới, bắt đầu = 00001 (5 chữ số)
                    $prefix_new = $prefix_time;
                    // set po_no new
                    $po_no_new = $prefix_new . '-00001';
                }
                
            } else { // Trường hợp không tìm thấy dạng tiền tố truy vấn có trong po_save
                $po_date_new = date('Y-m-d', strtotime($po_date_time));
                // set po_no new
                $prefix_new = $prefix_time;
                $po_no_new = $prefix_new . '-00001';
            }

        // result
        $result = array('po_date_new' => $po_date_new, 'po_no_new' => $po_no_new, 'prefix_new' => $prefix_new );
        return $result;
        
    }

    public function dateFormat($date) 
    {
        return date('Y-m-d', strtotime($date));
    }

    // check exist order
	public function checkDataExist()
	{

		// set post data
		    $dataPost = isset($_POST["data"]) ? $_POST["data"] : '';
		    // $dataPost = '{"input_value":"12345678-1"}';

        // check
            $dataPost = json_decode($dataPost, true);
            if (empty($dataPost)) {
                $this->_data['results'] = array(
                    "status" => false,
                    "message" => "Không nhận được dữ liệu POST!"
                );

            } else {

                // models
                    $this->load->model('thermal_master_item', 'master_data');

                // get soline
                    $input_value = trim($dataPost['input_value']);

                // check SO# or SOLine
                    $line_number = '';
                    if (strpos($input_value, '-') !== false ) {
                        $so_line_arr = explode('-', $input_value );
                        $order_number = $so_line_arr[0];
                        $line_number = $so_line_arr[1];

                        $where = array('order_number' => $order_number, 'line_number' => $line_number );
                        
                        $exist = (!$this->automail->checkSOLine($order_number, $line_number ) ) ? false : true;

                    } else { // Input SO# (not line)
                        $where = array('order_number' => $input_value);
                        $exist = (!$this->automail->checkSO($input_value ) ) ? false : true;
                    }

                // check 
                    if ($exist == false ) {
                        $this->_data['results'] = array(
                            "status" => false,
                            "message" => "SOLine " . $input_value . " KHÔNG tồn tại trong Automail. "
                        );
                    } else {
                        // get ITEM
                        $automailItem = $this->automail->readItem($where);
                        $internal_item = $automailItem['ITEM'];
                        
                        if (!$this->master_data->isAlreadyExist($internal_item) ) {
                            $this->_data['results'] = array(
                                "status" => false,
                                "message" => "Internal Item: $internal_item KHÔNG tồn tại trong Master Data."
                            );
                        } else {
                            $this->_data['results'] = array(
                                "status" => true,
                                "message" => "Check data success"
                            );
                        }
                    }
                
                
            }

        // result
		    echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);


	}

    // check already exist (ordered)
	public function isAlreadyExist()
	{
		// load models
		    $this->load->model('thermal_save_po_soline','save_po_soline');

		// set post data
		    $dataPost = isset($_POST["data"]) ? $_POST["data"] : '';

        // check
            $dataPost = json_decode($dataPost, true);
            if (empty($dataPost)) {
                $this->_data['results'] = array(
                    "status" => false,
                    "message" => "Không nhận được dữ liệu POST!",
                    "edit" => false
                );

            } else {
                
                // * check
                if ($this->save_po_soline->isAlreadyExist($dataPost['so_line'] ) ) {
                    $so_line_arr = explode('-',$dataPost['so_line']);
                    $order_number = $so_line_arr[0];
                    $line_number = isset($so_line_arr[1]) ? $so_line_arr[1] : '';
                    $solineItem = $this->save_po_soline->readPoNo($order_number, $line_number);
                    $po_no_edit = $solineItem['po_no'];

                    $this->_data['results'] = array(
                        "status" => false,
                        "message" => "SOLine " . $dataPost['so_line'] . " đã làm lệnh. Bạn có muốn chỉnh sửa?",
                        "edit" => true,
                        "po_no_edit" => $po_no_edit
                    );

                } else {
                    // results true
                    $this->_data['results'] = array(
                        "status" => true,
                        "message" => "OK. Đơn chưa làm lệnh",
                        "edit" => false
                    );
                }
            }

        // result
		    echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);


	}

    /* ------- TÍNH SỐ LƯỢNG VẬT TƯ -----------------------------------------------------------------------------
        
        1. form_type: Loại form muốn tính số lượng
        2. qty: Số lượng con nhãn
        3. material_roll_qty: số roll vật tư (có bao nhiêu roll vật tư), mặc định = 1
        4. material_qty_on_roll: Một roll vật tư có số lượng, mặc định = 1

    ------------------------------------------------------------------------------------------------------------- */
    public function calculateMaterial($internal_item, $material_code, $qty, $scrap, $pcs_set )
    {
        // init 
            $material_qty = 0;

        // check empty or zero input
            $scrap = (empty($scrap) ) ? 1 : (int)$scrap;
            $pcs_set = (empty($pcs_set) ) ? 1 : (int)$pcs_set;

        // special internal item
            $special_item_list = array( '2-111696-000-00', '2-340177-000-SHT', '2-340183-000-SHT', '2-378861-000-SHT', '2-378862-000-SHT' );
        
        // special material code
            $material_list = array( 'TH03572', 'TH06297' );
        
        // default
            $material_qty = $qty * $scrap * $pcs_set;

        // special 
            // special internal item 
                if (in_array($internal_item, $special_item_list) ) {
                    $material_qty = $qty;
                }

            // special material code
                if (in_array($material_code, $material_list) ) {
                    $material_qty = ($qty * $scrap ) / 35;
                }

        // result
            return ceil($material_qty);

    }

    /* -----TÍNH SỐ LƯỢNG MỰC IN --------------------------------------------------------------------------------
        1. form_type: Loại form muốn tính số lượng
        2. qty: Số lượng con nhãn
        3. ink_roll_qty: số roll mực (có bao nhiêu roll mực), mặc định = 1
        4. ink_qty_on_roll: Một roll mực có số lượng, mặc định = 1

    ------------------------------------------------------------------------------------------------------------- */
    public function calculateInk($form_type, $qty, $length, $gap, $scrap, $pcs_set, $ups, $roll_qty_per_kit )
    {
        // init 
            $ink_qty = 0;

        // check empty or zero input
            $pcs_set = (empty($pcs_set) ) ? 1 : (int)$pcs_set;
            $ups = (empty($ups) ) ? 1 : (int)$ups;
            $scrap = (empty($scrap) ) ? 1 : (float)$scrap;
            $roll_qty_per_kit = (empty($roll_qty_per_kit) ) ? 1 : (int)$roll_qty_per_kit;

        // calculate 1
            $ink_qty_1 = ($qty * ($length + $gap) * $scrap) / 1000;

        // calculate ink qty
            $ink_qty = ( ( $ink_qty_1 * $pcs_set ) / $ups );    

        // special options
            if ( ($form_type == 'IPPS') || ($form_type == 'FG') ) {
                $ink_qty = $qty * $roll_qty_per_kit;
            }
        
        // result
            return ceil($ink_qty);

    }

    //handle input data
    public function handle() 
    {
        // get title, production line
            $this->_data['title'] = 'Thermal Orders';
            $production_line = isset($_COOKIE['plan_department']) ? $_COOKIE['plan_department'] : 'thermal';

        // init 
            $order_details = array();
            $size_details = array();
            $supply_details = array();
            $results = array();
            
        // Load models ------------------------------------------------------------------------------------------------------------
            $this->load->model('thermal_master_item', 'master_item');
            $this->load->model('thermal_master_item_material_ink', 'supply');
            $this->load->model('thermal_save_po', 'save_po');
            $this->load->model('thermal_save_po_soline', 'save_po_soline');

        // GET ORDERS: so# (and line) ------------------------------------------------------------------------------------------------------------
            $this->_data['orders'] = trim($this->input->get('orders'));
        
        // GET PO NO & PO DATE ------------------------------------------------------------------------------------------------------------
            $po_no_new = '';
            $po_date_new = '';
            $po_no_edit = null !== $this->input->get('po_no_edit') ? $this->input->get('po_no_edit') : '';

            if (!empty($po_no_edit) ) {
                // Trường hợp sửa lệnh sản xuất
                $po_no_new = $po_no_edit;
                $po_no_item = $this->save_po->readSingle($po_no_new);
                $po_date_new = $po_no_item['po_date'];
            } else { // create po_no
                
                // Trường hợp này: Kiểm tra so_line có từng làm lệnh chưa?? Nếu có thì lấy ra po_no, po_date đã lưu và sửa lệnh
                $so_line_arr_check = explode('-',$this->_data['orders']);
                $line_check = isset($so_line_arr_check[1]) ? $so_line_arr_check[1] : '';
                if ($this->save_po_soline->checkSOLineExist($so_line_arr_check[0], $line_check)) {
                    $po_soline_save_item = $this->save_po_soline->readPoNo($so_line_arr_check[0], $line_check);
                    $po_no_new = $po_soline_save_item['po_no'];
                    // Sau khi đã có po_no, lấy ra po_date
                    $po_no_item_check = $this->save_po->readSingle($po_no_new);
                    $po_date_new = $po_no_item_check['po_date'];
                } else {
                    // Trường hợp chưa làm lệnh so_line này => tạo mới po_no và po_date
                    $create_NO_Date = $this->createPrefixNo($production_line);
                    if (!empty($create_NO_Date) || $create_NO_Date != false) {
                        // để save lấy po_no để tránh nhiều người dùng làm lệnh bị trùng
                        $po_no_new = $create_NO_Date['prefix_new'];
                        $po_date_new = $create_NO_Date['po_date_new'];
                    } else {
                        
                        $po_no_new = ''; 
                        $po_date_new = date('Y-m-d');
                    }
                } 

            }

        // get data with input orders in automail ------------------------------------------------------------------------------------------------------------
            
            // explore
                $so_line_arr = explode('-',$this->_data['orders']);

            //set so and line
                $order_number = isset($so_line_arr[0]) ? $so_line_arr[0] : '';
                $line_number = isset($so_line_arr[1]) ? $so_line_arr[1] : '';
                $line_number_last = isset($so_line_arr[2]) ? $so_line_arr[2] : '';

            // Trường hợp này là khi chọn edit, lúc đó so_line có dạng 12345678-1-2
                if (!empty($line_number_last)) {
                    $line_number = '';
                    $this->_data['orders'] = $order_number;
                }
            
            // check line
                if (empty($line_number) ) {
                    // Kiểm tra đơn hàng có trong vnso không, nếu không có thì vào vnso_total
                    if ($this->automail->checkSO($order_number) ) {
                        $this->_data['automail'] = $this->automail->readSO($order_number);
                    } else {
                        $this->_data['automail'] = $this->automail_closed->readSO($order_number);
                    }
                } else {
                    // Kiểm tra đơn hàng có trong vnso không, nếu không có thì vào vnso_total
                    if ($this->automail->checkSOLine($order_number, $line_number)) {
                        $this->_data['automail'] = $this->automail->readSOLine($order_number, $line_number);
                    } else {
                        $this->_data['automail'] = $this->automail_closed->readSOLine($order_number, $line_number);
                    }
                }

        // get data with automail ------------------------------------------------------------------------------------------------------------
            $qty_total = 0;
            $size_qty_total = 0;
            $material_qty_total = 0;
            $ink_qty_total = 0;

            $line_check = '';
            $so_line_check = '';
            $index=0;
            foreach ( $this->_data['automail'] as $keyA => $automail_item ) {
                
                // check duplicate data in automail
                    if ($line_check == $automail_item['LINE_NUMBER']) { continue; }

                // soline 
                    $so_line = $automail_item['ORDER_NUMBER'] .'-'. $automail_item['LINE_NUMBER'];

                // index
                    $index++;

                // Sample
                    $sample = $this->getSample($automail_item['PACKING_INSTRUCTIONS'], $automail_item['ORDER_NUMBER']);           

                /* START: master item ------------------------------------------------------------------------------------ */
                    
                    $internal_item = $automail_item['ITEM'];
                    if (!$this->master_item->isAlreadyExist($internal_item) ) {
                        $results = array(
                            "status" => false,
                            "message" => "Item: $internal_item KHÔNG có trong Master Data ",
                        );

                    } else {
                        // QTY
                            $qty = $automail_item['QTY'];
                            $qty_total += $qty;
                        
                        // all master data
                            $master_data = $this->master_item->readItem($internal_item);
                            $material_data = $this->supply->readSupply(array('internal_item' => $internal_item, 'code_type' => 'material') );
                            $ink_data = $this->supply->readSupply(array('internal_item' => $internal_item, 'code_type' => 'ink') );

                            $cbs = $master_data['cbs'];

                        // data from master data
                            // string 
                            $form_type = trim($master_data['form_type']);
                            $length = trim($master_data['length']);

                        // save cookie for create NO#
                            setcookie('module', '', time() + 0, "/");// clear current
                            setcookie('module', $form_type, time() + 3600, "/");// 60*60 minute
                        
                        //numeral
                            $gap = $master_data['gap'];
                            $pcs_set = $master_data['pcs_set'];
                            $scrap = $master_data['scrap'];
                            $ups = $master_data['ups'];

                        // Check CBS - SIZE ------------------------------------------------------------------------------------ 
                            if ( $cbs == 1 ) {
                                $size_data = array();
                                $size_automail_result = $this->getSizeAutomail($automail_item['VIRABLE_BREAKDOWN_INSTRUCTIONS']);

                                if (empty($size_automail_result)) {
                                    $results = array(
                                        "status" => false,
                                        "message" => "SO# " . $this->_data['orders'] . "KHÔNG lấy được size. Kiểm tra lại Automail trước lúc " . date('Y-m-d H:i:s'),
                                    );
                                } else {
                                    foreach ($size_automail_result as $size_item) {
                                        $size_data[] = array(
                                            'so_line' => $automail_item['ORDER_NUMBER'] .'-'. $automail_item['LINE_NUMBER'],
                                            'size' => $size_item['size'],
                                            'color' => $size_item['color'],
                                            'qty' => $size_item['qty'],
                                            'material_code' => $size_item['material_code']
                                        );
                                    }
                                } 


                                if (!empty($size_data) ) {

                                    // supply 
                                        $material_item = $material_data[0];
                                        $ink_item = $ink_data[0];
        
                                    // material (origin)
                                        $material_code = trim($material_item['code_name']);
                                        $material_desc = trim($material_item['descriptions']);
                                        $material_order = trim($material_item['order']);
                                        $material_uom = trim($material_item['uom']);
                                        $material_roll_qty_per_kit = trim($material_item['roll_qty_per_kit']);
                                        $material_base_roll = trim($material_item['base_roll']);
        
                                    // ink (origin)
                                        $ink_code = trim($ink_item['code_name']);
                                        $ink_desc = trim($ink_item['descriptions']);
                                        $ink_order = trim($ink_item['order']);
                                        $ink_uom = trim($ink_item['uom']);
                                        $ink_roll_qty_per_kit = trim($ink_item['roll_qty_per_kit']);
                                        $ink_base_roll = trim($ink_item['base_roll']);
        
                                    // get size data
                                        $indexS = 0;
                                        foreach ($size_data as $key => $value ) {
        
                                            $indexS++;
                                            // get data
                                                $size_material_code = trim($value['material_code']);
                                                $material_desc = '';
                                                $size_qty = $value['qty'];
                                                $size_qty_total += $size_qty;
        
                                            // calculate qty
                                                $size_material_qty = $this->calculateMaterial($internal_item, $material_code, $size_qty, $scrap, $pcs_set );
                                                $material_qty_total += $size_material_qty;
        
                                                $ink_qty = $this->calculateInk($form_type, $size_qty, $length, $gap, $scrap, $pcs_set, $ups, $ink_roll_qty_per_kit);
                                                $ink_qty_total += $ink_qty;
        
                                            // set data
                                                $size_details[] = array( 
                                                    
                                                    'index' => $indexS,
                                                    'so_line' => $so_line,
                                                    'size' => $value['size'],
                                                    'color' => $value['color'],    
                                                    'size_qty' => $size_qty,
                                                    'size_material_code' => $size_material_code,
                                                    'size_material_qty' => $size_material_qty,
                                                    'size_material_desc' => $material_desc,
                                                    'size_ink_code' => $ink_code,
                                                    'size_ink_desc' => $ink_desc,
                                                    'size_ink_qty' => $ink_qty
                                                );
                                                
                                        }
        
        
                                    // supply data
                                        $supply_details[] = array(
                                            'so_line' => $so_line,
                                            'internal_item' => $internal_item,
                                            'material_code' => $material_code,
                                            'material_desc' => $material_desc,
                                            'material_qty' => $material_qty_total,
                                            'material_order' => $material_order,
                                            'material_roll_qty_per_kit' => $material_roll_qty_per_kit,
                                            'material_base_roll' => $material_base_roll,
                                            'material_uom' => $material_uom,
        
                                            'ink_code' => $ink_code,
                                            'ink_desc' => $ink_desc,
                                            'ink_qty' => $ink_qty_total,
                                            'ink_order' => $ink_order,
                                            'ink_roll_qty_per_kit' => $ink_roll_qty_per_kit,
                                            'ink_base_roll' => $ink_base_roll,
                                            'ink_uom' => $ink_uom
                                        );
                                }
                                
                            } else {
                                // !IMPORTANT. check material or ink. 
                                    if (count($material_data) >= count($ink_data) ) {
                                        $supply_data = $material_data;
                                        $supply_data_2 = $ink_data;
                                    } else {
                                        $supply_data = $ink_data;
                                        $supply_data_2 = $material_data;
                                    }

                                // get data
                                    $indexM = 0;
                                    foreach ($supply_data as $key => $value ) {

                                        $indexM++;

                                        // check supply data is material or ink to calculate qty
                                            $roll_qty_per_kit = 0;
                                            if ($value['code_type'] == 'material' ) {
                                                $material_code = trim($value['code_name']);
                                                $material_desc = trim($value['descriptions']);
                                                $material_order = trim($value['order']);
                                                $material_roll_qty_per_kit = $value['roll_qty_per_kit'];
                                                $material_base_roll = $value['base_roll'];
                                                $material_qty = $this->calculateMaterial($internal_item, $material_code, $qty, $scrap, $pcs_set );
                                            } else {
                                                $ink_code = trim($value['code_name']);
                                                $ink_desc = trim($value['descriptions']);
                                                $ink_order = trim($value['order']);
                                                $ink_roll_qty_per_kit = $value['roll_qty_per_kit'];
                                                $ink_base_roll = $value['base_roll'];
                                                $ink_qty = $this->calculateInk($form_type, $qty, $length, $gap, $scrap, $pcs_set, $ups, $roll_qty_per_kit);
                                            }

                                        // so reverse that is reverse and calculate qty
                                            if (isset($supply_data_2[$key]) ) {
                                                if ($value['code_type'] !== 'material' ) { // !IMPORTANT reverse
                                                    $material_code = trim($supply_data_2[$key]['code_name']);
                                                    $material_desc = trim($supply_data_2[$key]['descriptions']);
                                                    $material_order = trim($supply_data_2[$key]['order']);
                                                    $material_roll_qty_per_kit = $supply_data_2[$key]['roll_qty_per_kit'];
                                                    $material_base_roll = $supply_data_2[$key]['base_roll'];
                                                    $material_qty = $this->calculateMaterial($internal_item, $material_code, $qty, $scrap, $pcs_set );
                                                } else {
                                                    $ink_code = trim($supply_data_2[$key]['code_name']);
                                                    $ink_desc = trim($supply_data_2[$key]['descriptions']);
                                                    $ink_order = trim($supply_data_2[$key]['order']);
                                                    $ink_roll_qty_per_kit = $supply_data_2[$key]['roll_qty_per_kit'];
                                                    $ink_base_roll = $supply_data_2[$key]['base_roll'];
                                                    $ink_qty = $this->calculateInk($form_type, $qty, $length, $gap, $scrap, $pcs_set, $ups, $roll_qty_per_kit);
                                                }
                                            } else {
                                                if ($value['code_type'] !== 'material' ) { // !IMPORTANT reverse
                                                    $material_code = '';
                                                    $material_desc = '';
                                                    $material_order = '';
                                                    $material_roll_qty_per_kit = '';
                                                    $material_base_roll = '';
                                                    $material_qty = 0;
                                                    $material_uom = '';
                                                } else {
                                                    $ink_code = '';
                                                    $ink_desc = '';
                                                    $ink_order = '';
                                                    $ink_roll_qty_per_kit = '';
                                                    $ink_base_roll = '';
                                                    $ink_qty = 0;
                                                    $ink_uom = '';
                                                }
                                            }

                                        // check. If duplicate so_line then material_qty and ink_qty = 0 
                                            if ($so_line_check == $so_line ) {
                                                $material_qty = 0;
                                                $ink_qty = 0;
                                            }

                                        // sum total
                                            $material_qty_total += $material_qty;
                                            $ink_qty_total += $ink_qty;

                                        // supply data
                                            $supply_details[] = array(
                                                'so_line' => $so_line,
                                                'internal_item' => $internal_item,
                                                'material_code' => $material_code,
                                                'material_desc' => $material_desc,
                                                'material_qty' => $material_qty,
                                                'material_order' => $material_order,
                                                'material_roll_qty_per_kit' => $material_roll_qty_per_kit,
                                                'material_base_roll' => $material_base_roll,
                                                'material_uom' => $material_uom,

                                                'ink_code' => $ink_code,
                                                'ink_desc' => $ink_desc,
                                                'ink_qty' => $ink_qty,
                                                'ink_order' => $ink_order,
                                                'ink_roll_qty_per_kit' => $ink_roll_qty_per_kit,
                                                'ink_base_roll' => $ink_base_roll,
                                                'ink_uom' => $ink_uom,
                                            );

                                    }

                                // so_line_check 
                                    $so_line_check = $so_line;
                            }
                        // po_no_suffix
                            $po_no_suffix = ''; 
                            
                        // order details data
                            $order_details[] = array( 
                                                
                                'index' => $index,
                                // 0. create
                                    'po_no_prefix' => $po_no_new,
                                    'po_date' => $po_date_new,
                                    'data_received' => date('Y-m-d'),
                                    'po_no_suffix' => $po_no_suffix,

                                // 1. automail
                                    'order_number' => $automail_item['ORDER_NUMBER'],
                                    'line_number' => $automail_item['LINE_NUMBER'],
                                    'qty' => $automail_item['QTY'],
                                    'item' => $automail_item['ITEM'],
                                    'item_desc' => $automail_item['ITEM_DESC'],
                                    'ordered_item' => $automail_item['ORDERED_ITEM'],
                                    'cust_po_number' => $automail_item['CUST_PO_NUMBER'],
                                    'customer_item' => $automail_item['CUSTOMER_ITEM'],
                                    'cs' => $automail_item['CS'],
                                    'bill_to_customer' => htmlspecialchars(trim($automail_item['BILL_TO_CUSTOMER']),ENT_QUOTES, 'UTF-8' ),
                                    'ship_to_customer' => htmlspecialchars(trim($automail_item['SHIP_TO_CUSTOMER']),ENT_QUOTES, 'UTF-8' ),
                                    'ordered_date' => date('Y-m-d',strtotime($automail_item['ORDERED_DATE'])),
                                    'request_date' => date('Y-m-d',strtotime($automail_item['REQUEST_DATE'])),
                                    'promise_date' => date('Y-m-d',strtotime($automail_item['PROMISE_DATE'])),
                                    'order_type_name' => $automail_item['ORDER_TYPE_NAME'],
                                    'flow_status_code' => $automail_item['FLOW_STATUS_CODE'],
                                    'production_method' => $automail_item['PRODUCTION_METHOD'],
                                    'planner_code' => $automail_item['PLANNER_CODE'],
                                    'customer_job' => $automail_item['CUSTOMER_JOB'],
                                    'packing_instructions' => htmlspecialchars(trim($automail_item['PACKING_INSTRUCTIONS']),ENT_QUOTES, 'UTF-8' ),
                                    'packing_instr' => htmlspecialchars(trim($automail_item['PACKING_INSTR']),ENT_QUOTES, 'UTF-8' ),
                                    'attachment' => htmlspecialchars(trim($automail_item['VIRABLE_BREAKDOWN_INSTRUCTIONS']),ENT_QUOTES, 'UTF-8' ),

                                // master data
                                    'form_type' => trim($master_data['form_type']),
                                    'rbo' => trim($master_data['rbo']),
                                    'rbo_remark' => trim($master_data['rbo_remark']),
                                    'kind_of_label' => trim($master_data['kind_of_label']),
                                    'length' => trim($master_data['length']),
                                    'width' => trim($master_data['width']),
                                    'unit' => trim($master_data['unit']),
                                    'ups' => $master_data['ups'],
                                    'cbs' => $master_data['cbs'],
                                    'gap' => $master_data['gap'],
                                    'site_printing' => $master_data['site_printing'],
                                    'machine' => trim($master_data['machine']),
                                    'format' => trim($master_data['format']),
                                    'standard_speed' => trim($master_data['standard_speed']),
                                    'speed_unit' => trim($master_data['speed_unit']),
                                    'cutter' => trim($master_data['cutter']),
                                    'security' => trim($master_data['security']),
                                    'fg_ipps' => trim($master_data['fg_ipps']),
                                    'pcs_set' => $master_data['pcs_set'],
                                    'scrap' => $master_data['scrap'],
                                    'chieu_in_thuc_te' => $master_data['chieu_in_thuc_te'],
                                    'layout_prepress' => $master_data['layout_prepress'],
                                    'remark_1' => trim($master_data['remark_1']),
                                    'remark_2' => trim($master_data['remark_2']),
                                    'remark_3' => trim($master_data['remark_3']),
                                    'remark_4' => trim($master_data['remark_4']),

                                // others
                                    'sample' =>$sample,
                                    'qty_total' =>$qty_total,
                                    'material_qty_total' =>$material_qty_total,
                                    'ink_qty_total' =>$ink_qty_total
                            );

                        // results 
                        $results = array(
                            'status' => true,
                            'message' => "Load data success",
                            'order_details' => $order_details,
                            'supply_details' => $supply_details,
                            'size_details' => $size_details
                        );

                        if ($cbs == 1 ) {
                            if ($qty_total != $size_qty_total ) {
                                $results = array(
                                    'status' => false,
                                    'message' => "Số lượng đơn hàng: $qty_total khác số lượng size: $size_qty_total "
        
                                );
                            }
                        }
                        
                    }

                /* END: master item ------------------------------------------------------------------------------------ */

                // set line check
                    $line_check = $automail_item['LINE_NUMBER'];
            }

        // results
            $this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
            $this->load->view('thermal/handle', $this->_data);

    }

    public function delete($po_no) 
    {
        //load models
        $this->load->model('po_save');
        $this->load->model('po_detail_save');
        $this->load->model('po_soline_save');
        $this->load->model('po_size_save');
        $this->load->model('remark_save');
        //handle all
        //del po_save
        if ($this->po_save->isAlreadyExist($po_no)) {
            // Lấy soline (có thể nhiều line) để tìm xóa trong bảng detail, size
            $soline_item = $this->po_soline_save->readSOLine($po_no);
            $po_save_item_del = $this->po_save->readSingle($po_no);
            $production_line_del = $po_save_item_del['production_line'];
            // del 
            $res_po = $this->po_save->delete($po_no);
            if (!$res_po) {
                $this->session->set_flashdata("flash_mess", "[ERROR PO] Xóa thất bại lệnh sản xuất $po_no . Lỗi: $res_po ");
                redirect(base_url());
                return false;
            }

            //del po_soline_save
            $res_soline = $this->po_soline_save->delete($po_no);
            if (!$res_soline) {
                $this->session->set_flashdata("flash_mess", "[ERROR SOLINE] Xóa thất bại lệnh sản xuất $po_no . Lỗi: $res_soline");
                redirect(base_url());
                return false;
            }

            // lặp qua tất cả các so_line
            foreach ($soline_item as $so_line) {
                //del po_detail_save, size
                $res_detail = $this->po_detail_save->delete($so_line['so_line']);
                if (!$res_detail) {
                    $this->session->set_flashdata("flash_mess", "[ERROR DETAIL] Xóa thất bại lệnh sản xuất $po_no . Lỗi: $res_detail");
                    redirect(base_url());
                    return false;
                }

                //del size. check soline nếu có thì xóa
                if ($this->po_size_save->checkSOLine($so_line['so_line'])) {
                    $res_size = $this->po_size_save->delete($so_line['so_line']);
                    if (!$res_size) {
                        $this->session->set_flashdata("flash_mess", "[ERROR SIZE] Xóa thất bại lệnh sản xuất $po_no . Lỗi: $res_size");
                        redirect(base_url());
                        return false;
                    }
                }
            }

            //del po_soline_save
            $res_remark_del = $this->remark_save->delete($production_line_del, $po_no);
            if (!$res_remark_del) {
                $this->session->set_flashdata("flash_mess", "[ERROR REMARK] Xóa thất bại lệnh sản xuất $po_no . Lỗi: $res_remark_del");
                redirect(base_url());
                return false;
            }
            

            

            //show message success
            $this->session->set_flashdata("flash_mess", "Xóa thành công lệnh sản xuất $po_no");
            redirect(base_url(). "thermal");

            
        } else {
            // Trường hợp không tồn tại po_no trong database
            $this->session->set_flashdata("flash_mess", "[ERROR] Không tồn tại lệnh sản xuất $po_no");
            redirect(base_url());
            return false;
        }

    }

    // save orders
    public function saveOrders() 
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');

        // init var
            $formData = array();
            $order_details = array();
            $supply_details = array();
            $size_details = array();

            $qty_total = 0;
            $material_qty_total = 0;
            $ink_qty_total = 0;

            $save_po_data = array();
            $save_po_soline_data = array();
            $save_po_material_ink_data = array();
            $size_save_data = array();

        // GET DATA ------------------------------------------------------------------------------------------------------------------------------
            $allData = $_POST["data"];
            // $allData = '{"formData":{"po_no_prefix":"THPA2104","po_date":"2021-04-27","promise_date":"2021-05-08","sample":"1","po_no_suffix":"normal","data_received":"2021-04-27","po_file":"1","remark_1":"","remark_2":"","remark_3":"","remark_4":""},"results":{"status":true,"message":"Load data success","order_details":[{"index":1,"po_no_prefix":"THPA2104","po_date":"2021-04-27","data_received":"2021-04-27","po_no_suffix":"","order_number":"52585171","line_number":"2","qty":"3262","item":"1-254440-000-00","item_desc":"NIKE INC.IM#681929.NIKE NON-RFID UPC All EMEA cou......","ordered_item":"IM#681929","cust_po_number":"4507886294-10/ATYC2-I2113671","customer_item":"IM#681929","cs":"Do, Keva","bill_to_customer":"TUN YUN TEXTILE CO., LTD","ship_to_customer":"PAPILLION TEXTILE (CAMBODIA ) CO., LTD","ordered_date":"2021-04-22","request_date":"2021-05-05","promise_date":"2021-05-08","order_type_name":"VN GEN","flow_status_code":"PRODUCTION_OPEN","production_method":"Thermal Ticket Center","planner_code":"VNTMA800","customer_job":"4878632","packing_instructions":"","packing_instr":"","attachment":";  STYLE#-COLOR#  DD4894-010               ;  SQ#  :  Size  :  Color  :  Quantity  :  Material Code ;  0001  :  S  :  YELLOW  :  409  :  6-254440-YEL-00 ;  0002  :  M  :  BLUE  :  1630  :  6-254440-BLU-00 ;  0003  :  L  :  RED  :  1223  :  6-254440-RED-00         ;  Total Quantity  :  3262    ^","form_type":"paxar","rbo":"NIKE INC","rbo_remark":"NIKE INC DEESC","kind_of_label":"","length":"45","width":"35","unit":"PCS","ups":"1","cbs":"1","gap":"1","site_printing":null,"machine":"","format":"","standard_speed":"","speed_unit":"","cutter":"","security":"","fg_ipps":"","pcs_set":null,"scrap":null,"chieu_in_thuc_te":null,"layout_prepress":null,"remark_1":"","remark_2":"","remark_3":"","remark_4":"","sample":1,"qty_total":3262,"material_qty_total":3262,"ink_qty_total":151}],"supply_details":[{"so_line":"52585171-2","internal_item":"1-254440-000-00","material_code":"6-016261-UPC-00","material_desc":"","material_qty":3262,"material_order":"1","material_roll_qty_per_kit":"1","material_base_roll":"1","material_uom":"EA","ink_code":"9V000414-090-00","ink_desc":"ink desc","ink_qty":151,"ink_order":"1","ink_roll_qty_per_kit":"1","ink_base_roll":"1","ink_uom":"EA"}],"size_details":[{"index":1,"so_line":"52585171-2","size":"S","color":"YELLOW","size_qty":"409","size_material_code":"6-254440-YEL-00","size_material_qty":409,"size_material_desc":"","size_ink_code":"9V000414-090-00","size_ink_desc":"ink desc","size_ink_qty":19},{"index":2,"so_line":"52585171-2","size":"M","color":"BLUE","size_qty":"1630","size_material_code":"6-254440-BLU-00","size_material_qty":1630,"size_material_desc":"","size_ink_code":"9V000414-090-00","size_ink_desc":"ink desc","size_ink_qty":75},{"index":3,"so_line":"52585171-2","size":"L","color":"RED","size_qty":"1223","size_material_code":"6-254440-RED-00","size_material_qty":1223,"size_material_desc":"","size_ink_code":"9V000414-090-00","size_ink_desc":"ink desc","size_ink_qty":57}]}}';
            $allData = json_decode($allData,true);

            // get cookie
                $updated_by = get_cookie('plan_loginUser');
                $production_line = get_cookie('plan_department');

        // check empty ------------------------------------------------------------------------------------------------------------------------------
            if (empty($allData) ) {
                $this->_data['results'] = array(
                    'status' => false,
                    'message' => 'Save data empty (1)'
                );
                echo json_encode($this->_data['results'],JSON_UNESCAPED_UNICODE); exit();
            } else { // get data details
                $formData = $allData['formData'];
                $results = $allData['results'];
                $order_details = $results['order_details'];
                $supply_details = $results['supply_details'];
                $size_details = $results['size_details'];

            }

        // models ------------------------------------------------------------------------------------------------------------------------------
            $this->load->model('thermal_save_po', 'save_po');
            $this->load->model('thermal_save_po_soline', 'save_po_soline');
            $this->load->model('thermal_save_po_material_ink', 'save_material_ink');
            $this->load->model('common_size_save', 'size_save');

        // Create PO_NO ------------------------------------------------------------------------------------------------------------------------------
            $prefix = trim($formData['po_no_prefix']);
            if (strlen($prefix) > 12 ) {
                $po_no = $prefix;
            } else {
                $po_no = $this->createNo($prefix);
            }


        // get data save: thermal_save_po ------------------------------------------------------------------------------------------------------------------------------
            // get first data
                $poItem = $order_details[0];
                $cbs = (int)$poItem['cbs'];

                $qty_total = $poItem['qty_total'];
                $material_qty_total = $poItem['material_qty_total'];
                $ink_qty_total = $poItem['ink_qty_total'];

            // get remark save data
				$remarkCheckArr['rbo'] = $poItem['rbo'];
				$remarkCheckArr['internal_item'] = $poItem['item'];
				$remarkCheckArr['order_type_name'] = $poItem['order_type_name'];
				$remarkCheckArr['ordered_item'] = $poItem['ordered_item'];
				$remarkCheckArr['ship_to_customer'] = $poItem['ship_to_customer'];
				$remarkCheckArr['bill_to_customer'] = $poItem['bill_to_customer'];
				$remarkCheckArr['packing_instructions'] = $poItem['packing_instructions'];

            // thermal_save_po 
                $save_po_data = array(
                    'production_line' => $production_line,
                    'form_type' => $poItem['form_type'],
                    'po_no' => $po_no,
                    'po_no_suffix' => $formData['po_no_suffix'],
                    'count_line' => count($order_details),
                    'customer_job' => $poItem['customer_job'],
                    'qty_total' => $qty_total,
                    'material_qty_total' => $material_qty_total,
                    'ink_qty_total' => $ink_qty_total,
                    'po_date' => $formData['po_date'],
                    'ordered_date' => $poItem['ordered_date'],
                    'request_date' => $poItem['request_date'],
                    'promise_date' => $formData['promise_date'],
                    'order_type_name' => $poItem['order_type_name'],
                    'label_size' => $poItem['length'] . ' x ' . $poItem['width'],
                    'po_file' => $formData['po_file'],
                    'sample' => $formData['sample'],
                    'data_received' => $formData['data_received'],
                    'printed' => 0,
                    'updated_by' => $updated_by,
                    'updated_date' => date('Y-m-d H:i:s')
                );

            // get save data to thermal_save_po_soline, thermal_save_po_material_ink
                foreach ($order_details as $value ) {
                    $so_line = $value['order_number'] . '-' . $value['line_number'];
                    // thermal_save_po_soline        
                    $save_po_soline_data[] = array(

                        'po_no' => $po_no,
                        'so_line' => $so_line,
                        'qty_of_line' => $value['qty'],

                        'ordered_item' => $value['ordered_item'],
                        'cust_po_number' => $value['cust_po_number'],
                        'cs' => $value['cs'],
                        'bill_to_customer' => $value['bill_to_customer'],
                        'ship_to_customer' => $value['ship_to_customer'],
                        'ordered_date' => $value['ordered_date'],
                        'request_date' => $value['request_date'],
                        'promise_date' => $value['promise_date'],
                        'order_type_name' => $value['order_type_name'],
                        'flow_status_code' => $value['flow_status_code'],
                        'production_method' => $value['production_method'],
                        'planner_code' => $value['planner_code'],
                        'customer_job' => $value['customer_job'],
                        'packing_instructions' => $value['packing_instructions'],
                        'packing_instr' => $value['packing_instr'],
                        'attachment' => $value['attachment'],

                        'internal_item' => $value['item'],
                        'internal_item_desc' => $value['item_desc'],
                        'form_type' => $value['form_type'],
                        'rbo' => $value['rbo'],
                        'rbo_remark' => $value['rbo_remark'],
                        'kind_of_label' => $value['kind_of_label'],
                        'length' => $value['length'],
                        'width' => $value['width'],
                        'unit' => $value['unit'],
                        'ups' => $value['ups'],

                        'cbs' => $value['cbs'],
                        'gap' => $value['gap'],
                        'site_printing' => $value['site_printing'],
                        'machine' => $value['machine'],
                        'format' => $value['format'],
                        'standard_speed' => $value['standard_speed'],
                        'speed_unit' => $value['speed_unit'],
                        'cutter' => $value['cutter'],
                        'security' => $value['security'],

                        'fg_ipps' => $value['fg_ipps'],
                        'pcs_set' => $value['pcs_set'],
                        'scrap' => $value['scrap'],
                        'chieu_in_thuc_te' => $value['chieu_in_thuc_te'],
                        'layout_prepress' => $value['layout_prepress'],
                        'remark_1' => $value['remark_1'],
                        'remark_2' => $value['remark_2'],
                        'remark_3' => $value['remark_3'],
                        'remark_4' => $value['remark_4']

                    );

                }

            // get material & ink: thermal_save_po_material_ink table 
                foreach ($supply_details as $supply ) {

                    $save_po_material_ink_data[] = array(
                        'po_no' => $po_no,
                        'so_line' => $supply['so_line'],
                        'internal_item' => $supply['internal_item'],
                        'code_name' => $supply['material_code'],
                        'order' => $supply['material_order'],
                        'descriptions' => $supply['material_desc'],
                        'qty' => $supply['material_qty'],
                        'code_type' => 'material',
                        'uom' =>$supply['material_uom'],
                        'roll_qty_per_kit' =>$supply['material_roll_qty_per_kit'],
                        'base_roll' =>$supply['material_base_roll']
                    );

                    $save_po_material_ink_data[] = array(
                        'po_no' => $po_no,
                        'so_line' => $supply['so_line'],
                        'internal_item' => $supply['internal_item'],
                        'code_name' => $supply['ink_code'],
                        'order' => $supply['ink_order'],
                        'descriptions' => $supply['ink_desc'],
                        'qty' => $supply['ink_qty'],
                        'code_type' => 'ink',
                        'uom' =>$supply['ink_uom'],
                        'roll_qty_per_kit' =>$supply['ink_roll_qty_per_kit'],
                        'base_roll' =>$supply['ink_base_roll']
                    );

                }

        // save data to table ------------------------------------------------------------------------------------------------------------------------------
            $status = false;
            if (empty($save_po_data) ) {
                $message = 'Empty Data (PO)';
            } else if (empty($save_po_soline_data) ) {
                $message = 'Empty Data (SOLINE)';
            } else if (empty($save_po_material_ink_data) ) {
                $message = 'Empty Data (Material & Ink)';
            } else {

                if($this->save_po->isAlreadyExist($po_no) ) {
                    unset($save_po_data['po_no']);
                    $results = $this->save_po->update($save_po_data, array('po_no' => $po_no) );
                } else {
                    $results = $this->save_po->insert($save_po_data);
                }

                if ($results != TRUE ) {
                    $message = "Save data error (PO)";
                } else {
                    foreach ($save_po_soline_data as $solineItem ) {
                        // get data
                            $po_no = trim($solineItem['po_no']);
                            $so_line = trim($solineItem['so_line']);

                        // check exist and save
                            if($this->save_po_soline->isAlreadyExist($so_line) ) {
                                // unset primary key
                                unset($solineItem['po_no']);
                                unset($solineItem['so_line']);

                                $results = $this->save_po_soline->update($solineItem, array('so_line' => $so_line) );
                            } else {
                                $results = $this->save_po_soline->insert($solineItem);
                            }

                        // check save
                            if ($results != TRUE ) {
                                $this->_data['results'] = array(
                                    'status' => false,
                                    'message' => 'Save data error (SOLINE)'
                                );
                                echo json_encode($this->_data['results'],JSON_UNESCAPED_UNICODE); exit();
                            } else {
                                $status = true;
                                $message = 'Lưu dữ liệu thành công! Bạn có muốn IN không?';
                            }
                    }

                    // check soline save
                        if ($results == TRUE ) {

                            foreach ($save_po_material_ink_data as $material_ink_item ) {
                                // where 
                                    $whereMK = array(
                                        'so_line' => $material_ink_item['so_line'],
                                        'internal_item' => $material_ink_item['internal_item'],
                                        'code_name' => $material_ink_item['code_name'],
                                        'order' => $material_ink_item['order']
                                    );

                                // check exist and save
                                    if ($this->save_material_ink->isAlreadyExist($whereMK) ) {
                                        // unset primary key 
                                            unset($material_ink_item['so_line']);
                                            unset($material_ink_item['internal_item']);
                                            unset($material_ink_item['code_name']);
                                            unset($material_ink_item['order']);
                                        // update
                                            $results = $this->save_material_ink->update($material_ink_item, $whereMK);
                                    } else {
                                        $results = $this->save_material_ink->insert($material_ink_item);
                                    }

                                // check save
                                    if ($results != TRUE ) {
                                        if ($results != TRUE ) {
                                            $this->_data['results'] = array(
                                                'status' => false,
                                                'message' => 'Save data error (SOLINE)'
                                            );
                                            echo json_encode($this->_data['results'],JSON_UNESCAPED_UNICODE); exit();
                                        }
                                    } else {
                                        $
                                        $status = true;
                                        $message = 'Lưu dữ liệu thành công! Bạn có muốn IN không?';
                                    }
                                
                            }
                        }

                    // save size data: common_size_save ------------------------------------------------------------------------------------------------------------------------------
                        $material_uom = trim($supply_details[0]['material_uom']);
                        $ink_uom = trim($supply_details[0]['material_uom']);

                        if ( ($cbs == 1) && !empty($size_details) ) {
                            foreach ($size_details as $keyS => $sizeItem ) {
                                // get data
                                    $so_line = trim($sizeItem['so_line']);
                                    $size = trim($sizeItem['size']);
                                    $color = trim($sizeItem['color']);
                                
                                // save data
                                    $size_save_data = array(
                                        'up_date' => date('Y-m-d H:i:s'),
                                        'up_user' => $updated_by,
                                        'production_line' => $production_line,
                                        'no_number' => $po_no,
                                        'so_line' => $so_line,
                                        'size' => $size,
                                        'color' => $color,
                                        'qty' => $sizeItem['size_qty'],
                                        'material_code' => $sizeItem['size_material_code'],
                                        'material_desc' => $sizeItem['size_material_desc'],
                                        'material_qty' => $sizeItem['size_material_qty'],
                                        'material_uom' => $material_uom,
                                        'ink_code' => $sizeItem['size_ink_code'],
                                        'ink_desc' => $sizeItem['size_ink_desc'],
                                        'ink_qty' => $sizeItem['size_ink_qty'],
                                        'ink_uom' => $ink_uom
                                    );

                                // where 
                                    $whereSize = array( 'so_line' => $so_line, 'size' => $size, 'color' => $color );

                                // check exist
                                    if ($this->size_save->isAlreadyExist($whereSize) ) {
                                        // unset primary key
                                            unset($size_save_data['so_line']);
                                            unset($size_save_data['size']);
                                            unset($size_save_data['color']);
                                        // update
                                            $results = $this->size_save->update($size_save_data, $whereSize );
                                    } else {
                                        $results = $this->size_save->create($size_save_data );
                                    }

                                // check save
                                    if ($results != TRUE ) {
                                        $this->_data['results'] = array(
                                            'status' => false,
                                            'message' => 'Save data error (SIZE)'
                                        );
                                        echo json_encode($this->_data['results'],JSON_UNESCAPED_UNICODE); exit();
                                    } else {
                                        $status = true;
                                        $message = 'Lưu dữ liệu thành công! Bạn có muốn IN không?';
                                    }
                            }
                        }

                    // Remark save ------------------------------------------------------------------------------------------------------------------------------
                        // remark
                            $remarkSave = $this->remark($production_line, $po_no, $remarkCheckArr );
                            if ($remarkSave !== TRUE ) {
                                $this->_data['results'] = array(
                                    'status' => false,
                                    'message' => 'Save Data Error. Remark Tool ' . $remarkSave
                                );
                                echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE); exit();
                            }
                            
                        // Remark: KHONG KIM LOAI và save Packing Instr
                            $remarkPacking = $this->packingInstrRemark($production_line, $po_no, $remarkCheckArr['packing_instructions'], $remarkCheckArr['rbo']);
                            if ($remarkPacking !== TRUE ) {
                                $this->_data['results'] = array(
                                    'status' => false,
                                    'message' => 'Save Data Error. Remark KKL & Packing'
                                );
                                echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE); exit();
                            }

                }

                
            }
            
        // results to saveOrders view ------------------------------------------------------------------------------------------------------------
            $this->_data['results'] = array(
                'status' => $status,
                'message' => $message,
                'PO_NO' => $po_no
            );

            echo json_encode($this->_data['results'],JSON_UNESCAPED_UNICODE); exit();

    }

    // print orders
    public function printOrders($po_no) 
    {
        // init result data array
            $print_data = array();

        // models ------------------------------------------------------------------------------------------------------------------------------
            $this->load->model('thermal_save_po', 'save_po');
            $this->load->model('thermal_save_po_soline', 'save_po_soline');
            $this->load->model('thermal_save_po_material_ink', 'save_material_ink');
            $this->load->model('common_size_save', 'save_size');
            $this->load->model('common_prefix_no');

            $this->load->model('common_remark_po_save', 'remark_po_save');

        // init
            $po_no_print = array();
            $so_line_print = array();
            $remark_top_print = array();
            $remark_main_print = array();

        // status default
            $status = false;
            $message = '';

        // get form_type
            $po_no_arr = explode('-',$po_no );
            $prefix_len = strlen($po_no_arr[0]) - 4;
            $prefix = substr($po_no, 0,$prefix_len );

        // get prefix no description
            if (!$this->common_prefix_no->checkPrefix($prefix) ) {
                $message = "Không lấy được NO# (PREFIX)";
            } else {
                // prefix data
                    $po_prefix_no_check = $this->common_prefix_no->readPrefix($prefix);
                    // $production_line = null !== get_cookie('plan_department') ? get_cookie('plan_department') : 'thermal' ;
                    $production_line = (isset($po_prefix_no_check['production_line']) ) ? $po_prefix_no_check['production_line'] : 'thermal';
                    $form_type = $po_prefix_no_check['module'];
                    $form_type_label = $po_prefix_no_check['description'];

                // get data po_save
                    if (!$this->save_po->isAlreadyExist($po_no) ) {
                        $message = "Không có dữ liệu đã làm lênh của NO# (1): $po_no ";
                    } else {
                        // po data
                            $po_save_item = $this->save_po->readSingle($po_no);

                        // check soline data
                        if (!$this->save_po_soline->checkPO($po_no) ) {
                            $message = "Không có dữ liệu đã làm lênh của NO# (2): $po_no ";
                        } else {

                            // get data soline
                                $po_soline_save = $this->save_po_soline->readSOLine($po_no);
                                $so_line = $po_soline_save[0]['so_line'];
                                $cbs = $po_soline_save[0]['cbs'];
                                $cbs_show = ($cbs == 1 ) ? 'SIZE' : 'FIX';
                        
                            // so_line barcode
                                if (count($po_soline_save) >= 2 ) {
                                    $so_line_arr = explode('-', $so_line);
                                    $so_line_barcode_value = $so_line_arr[0];
                                } else {
                                    $so_line_barcode_value = $so_line;
                                }

                                $so_line_barcode = '<img style="text-align:right;width:250px; height:40px;"  src="'. base_url("assets/barcode.php?text=") .$so_line_barcode_value.'" />';

                            // po no data
                                $po_no_print = array(
                                    'form_type_label' => $form_type_label,
                                    'po_date' => $po_save_item['po_date'],
                                    'cbs_show' => $cbs_show,
                                    'updated_by' => $po_save_item['updated_by'],
                                    'count_line' => $po_save_item['count_line'],
                                    'so_line_barcode' => $so_line_barcode,
                                    'po_no' => $po_save_item['po_no'],
                                    'po_no_suffix' => $po_save_item['po_no_suffix'],
                                    'ordered_date' => $po_save_item['ordered_date'],
                                    'request_date' => $po_save_item['request_date'],
                                    'promise_date' => $po_save_item['promise_date'],

                                    'qty_total' => $po_save_item['qty_total'],
                                    'material_qty_total' => $po_save_item['material_qty_total'],
                                    'ink_qty_total' => $po_save_item['ink_qty_total'],

                                    'po_file' => $po_save_item['po_file'],
                                    'sample' => $po_save_item['sample'],
                                    'data_received' => $po_save_item['data_received'],

                                    'rbo' => $po_soline_save[0]['rbo'],
                                    'ship_to_customer' => $po_soline_save[0]['ship_to_customer'],
                                    'cs' => $po_soline_save[0]['cs'],
                                    'cbs' => $po_soline_save[0]['cbs']
                                );

                            // get data
                                if ($cbs == 1 ) {

                                    $index = 0;
                                    foreach ($po_soline_save as $soline_item) {
                                            
                                        // data
                                            $so_line = trim($soline_item['so_line']);
                                            $internal_item = trim($soline_item['internal_item']);

                                        // check
                                        if (!$this->save_size->checkSOLine($so_line) ) {
                                            $status = false;
                                            $message = 'Không có SIZE trong dữ liệu save';
                                        } else {
    
                                            // get size data
                                                $size_save_data = $this->save_size->readSingle($so_line);
                                                foreach ($size_save_data as $keySize => $size_item ) {

                                                    $index++;

                                                    $so_line_print[] = array(
                                                        // so_line data
                                                            'index' => $index,
                                                            'so_line' => $soline_item['so_line'],
                                                            'ordered_item' => $soline_item['ordered_item'],
                                                            'internal_item' => $soline_item['internal_item'],
                                                            'internal_item_desc' => $soline_item['internal_item_desc'],
                                                            'qty' => $soline_item['qty_of_line'],
                                                            'length' => $soline_item['length'],
                                                            'width' => $soline_item['width'],
                                                            'machine' => $soline_item['machine'],
                                                        // size
                                                            'size' => $size_item['size'],
                                                        // material and ink
                                                            'material_code' => $size_item['material_code'],
                                                            'material_desc' => $size_item['material_desc'],
                                                            'material_qty' => $size_item['material_qty'],
                                                            'material_uom' => $size_item['material_uom'],
                                                            'ink_code' => $size_item['ink_code'],
                                                            'ink_desc' => $size_item['ink_desc'],
                                                            'ink_qty' => $size_item['ink_qty'],
                                                            'ink_uom' => $size_item['ink_uom']
                                                    );
                                                    
                                                }
    
                                        }

                                    }
                                    
                                } else {
                                    // soline data
                                        $index = 0;
                                        foreach ($po_soline_save as $soline_item) {
                                            
                                            // index
                                                $index++;
                                            
                                            // data
                                                $so_line = trim($soline_item['so_line']);
                                                $internal_item = trim($po_save_item['internal_item']);

                                            // material ink data
                                                $where_material = array('so_line' => $so_line, 'internal_item' => $internal_item, 'code_type' => 'material' );
                                                $where_ink = array('so_line' => $so_line, 'internal_item' => $internal_item, 'code_type' => 'ink' );

                                                if ($this->save_material_ink->isAlreadyExist($where_material) && $this->save_material_ink->isAlreadyExist($where_ink) ) {
                                                    $material_data = $this->save_material_ink->readSingle($where_material);
                                                    $ink_data = $this->save_material_ink->readSingle($where_ink);

                                                    if (count($material_data) >= count($ink_data) ) {
                                                        $supply_main = $material_data;
                                                        $supply_main_2 = $ink_data;
                                                        $checkSup = 'material';
                                                    } else {
                                                        $checkSup = 'ink';
                                                        $supply_main = $ink_data;
                                                        $supply_main_2 = $material_data;
                                                    }

                                                    foreach ($supply_main as $keyS => $supply ) {

                                                        // soline print
                                                        if (!isset($supply_main_2[$keyS] ) ) {

                                                            if ($checkSup == 'material' ) {
                                                                $so_line_print = array(
                                                                    // so_line data
                                                                        'index' => $index,
                                                                        'so_line' => $soline_item['so_line'],
                                                                        'ordered_item' => $soline_item['ordered_item'],
                                                                        'internal_item' => $po_save_item['internal_item'],
                                                                        'internal_item_desc' => $po_save_item['internal_item_desc'],
                                                                        'qty' => $soline_item['qty_of_line'],
                                                                        'length' => $soline_item['length'],
                                                                        'width' => $soline_item['width'],
                                                                        'machine' => $soline_item['machine'],
    
                                                                    // material
                                                                        'material_code' => $supply['code_name'],
                                                                        'material_desc' => $supply['descriptions'],
                                                                        'material_order' => $supply['order'],
                                                                        'material_qty' => $supply['qty'],
                                                                        'material_remark' => $supply['remark'],
                                                                        'material_uom' => $supply['uom'],
                                                                        'material_roll_qty_per_kit' => $supply['roll_qty_per_kit'],
                                                                        'material_base_roll' => $supply['base_roll']
                                                                );
                                                            } else {
                                                                $so_line_print = array(
                                                                    // so_line data
                                                                        'index' => $index,
                                                                        'so_line' => $soline_item['so_line'],
                                                                        'ordered_item' => $soline_item['ordered_item'],
                                                                        'internal_item' => $po_save_item['internal_item'],
                                                                        'internal_item_desc' => $po_save_item['internal_item_desc'],
                                                                        'qty' => $soline_item['qty_of_line'],
                                                                        'length' => $soline_item['length'],
                                                                        'width' => $soline_item['width'],
                                                                        'machine' => $soline_item['machine'],
    
                                                                    // material
                                                                        'ink_code' => $supply['code_name'],
                                                                        'ink_desc' => $supply['descriptions'],
                                                                        'ink_order' => $supply['order'],
                                                                        'ink_qty' => $supply['qty'],
                                                                        'ink_remark' => $supply['remark'],
                                                                        'ink_uom' => $supply['uom'],
                                                                        'ink_roll_qty_per_kit' => $supply['roll_qty_per_kit'],
                                                                        'ink_base_roll' => $supply['base_roll']
                                                                );
                                                            }
                                                            
                                                        } else {
                                                            $so_line_print[] = array(
                                                                // so_line data
                                                                    'index' => $index,
                                                                    'so_line' => $soline_item['so_line'],
                                                                    'ordered_item' => $soline_item['ordered_item'],
                                                                    'internal_item' => $po_save_item['internal_item'],
                                                                    'internal_item_desc' => $po_save_item['internal_item_desc'],
                                                                    'qty' => $soline_item['qty_of_line'],
                                                                    'length' => $soline_item['length'],
                                                                    'width' => $soline_item['width'],
                                                                    'machine' => $soline_item['machine'],
                                                                    
                                                                // material
                                                                    'material_code' => $supply['code_name'],
                                                                    'material_desc' => $supply['descriptions'],
                                                                    'material_order' => $supply['order'],
                                                                    'material_qty' => $supply['qty'],
                                                                    'material_remark' => $supply['remark'],
                                                                    'material_uom' => $supply['uom'],
                                                                    'material_roll_qty_per_kit' => $supply['roll_qty_per_kit'],
                                                                    'material_base_roll' => $supply['base_roll'],

                                                                // ink
                                                                    'ink_code' => $supply_main_2[$keyS]['code_name'],
                                                                    'ink_desc' => $supply_main_2[$keyS]['descriptions'],
                                                                    'ink_order' => $supply_main_2[$keyS]['order'],
                                                                    'ink_qty' => $supply_main_2[$keyS]['qty'],
                                                                    'ink_remark' => $supply_main_2[$keyS]['remark'],
                                                                    'ink_uom' => $supply_main_2[$keyS]['uom'],
                                                                    'ink_roll_qty_per_kit' => $supply_main_2[$keyS]['roll_qty_per_kit'],
                                                                    'ink_base_roll' => $supply_main_2[$keyS]['base_roll']
                                                            );
                                                        }

                                                    
                                                    }

                                                }

                                        }
                                }


                            // get remark data
                                $remark_main_print = $this->remark_po_save->readPO(array('po_no' => $po_no) );


                            // check empty
                                if (!empty($po_no_print) && !empty($so_line_print) ) {
                                    // update printed column set 1
                                    $printed_update_save = array('printed' => 1);
                                    $this->save_po->update( $printed_update_save, array('po_no' => $po_no) );
                                    
                                    $status = true;
                                    $message = 'Print Data OK';
                                }
                        }
                    }


            }

        

        // get results
            $this->_data['results'] = array(
                'status' => $status,
                'message' => $message,
                'po_no_print' => $po_no_print,
                'so_line_print' => $so_line_print,
                'remark_top_print' => $remark_top_print,
                'remark_main_print' => $remark_main_print
            );

        // return 
            $this->load->view('thermal/print/printOrders', $this->_data);

    }

    // export
    public function reportOrders() 
    {

        // models ------------------------------------------------------------------------------------------------------------------------------
            $this->load->model('thermal_save_po', 'save_po');
            $this->load->model('thermal_save_po_soline', 'save_po_soline');
            $this->load->model('thermal_save_po_material_ink', 'save_material_ink');
            $this->load->model('common_size_save', 'save_size');

        // get distance times
			$from_date = null !== $this->input->get('from_date') ? trim($this->input->get('from_date')) : '';
			$to_date = null !== $this->input->get('to_date') ? trim($this->input->get('to_date')) : '';
            $form_type = null !== $this->input->get('form_type') ? trim($this->input->get('form_type')) : '';

		// create
        	$spreadsheet = new Spreadsheet();

		// set the names of header cells
			$columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W');

        // production line
            $production_line = null !== get_cookie('plan_department') ? trim(get_cookie('plan_department')) : 'thermal';

        // trimcard, hang dac biet
            $trimcardArr = array('trim card', 'trimcard', 'trim-card', 'trim/card' );
            $securityArr = array('HÀNG ĐẶC BIỆT', 'HANG DAC BIET' );

        //check empty
        if ($this->save_po->countAll() > 0 && $this->save_po_soline->countAll() > 0  ) {

            // Add new sheet
                $spreadsheet->createSheet();

            // Add some data
                $spreadsheet->setActiveSheetIndex(0);

            // active and set title
                $spreadsheet->getActiveSheet()->setTitle('Report_PO');

                $header1 = array(
                    'NGÀY LÀM ĐƠN', 'NO#', 'SO#', 'REQUEST DATE', 'PROMISE DATE', 'INTERNAL ITEM', 'RBO', 'ORDERED ITEM', 'SỐ LƯỢNG', 'MÃ VẬT TƯ',
                    'MÔ TẢ VẬT TƯ', 'SỐ LƯỢNG VẬT TƯ', 'ĐƠN VỊ VẬT TƯ', 'CHIỀU DÀI CON NHÃN', 'CHIỀU RỘNG CON NHÃN', 'MÃ MỰC', 'MÔ TẢ MỰC', 'SỐ LƯỢNG MỰC', 'SỐ UP', 'NGƯỜI LÀM ĐƠN',
                    'SỐ KIT', 'NOTE 1', 'NOTE 2'
                );

                $id = 0;
                foreach ($header1 as $header ) {
                    for ($index = $id; $index < count($header1); $index++ ) {
                        // width
                        $spreadsheet->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(20);

                        // headers
                        $spreadsheet->getActiveSheet()->setCellValue($columns[$index] . '1', $header );

                        $id++;
                        break;
                    }
                }


            // Font
                $spreadsheet->getActiveSheet()->getStyle('A1:W1')->getFont()->setBold(true)->setName('Arial')->setSize(10);
                $spreadsheet->getActiveSheet()->getStyle('A1:W1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('3399ff');
                $spreadsheet->getActiveSheet()->getStyle('A:W')->getFont()->setName('Arial')->setSize(10);

            // get data
                $poSave = $this->save_po->readReport($form_type, $from_date, $to_date );
                
                $rowCount = 1;
                
                foreach ($poSave as $key => $value ) {

                    $po_no = $value['po_no'];
                    $solineSave = $this->save_po_soline->readSOLine($po_no );

                    foreach ($solineSave as $k => $element ) {

                        $so_line = $element['so_line'];
                        $internal_item = $element['internal_item'];
                        $cbs = $element['cbs'];
                        $packing_instructions = $element['packing_instructions'];
                        $security = $element['security'];
                        if ($cbs == 1 ) {
                            $supplySave = $this->save_size->readSingle($so_line );

                            // get data
                            foreach ($supplySave as $keyS => $supply ) {

                                $rowCount++; // count rows

                                $spreadsheet->getActiveSheet()->SetCellValue('A' . $rowCount, $this->dateFormat($value['po_date']) );
                                $spreadsheet->getActiveSheet()->SetCellValue('B' . $rowCount, $po_no );
                                $spreadsheet->getActiveSheet()->SetCellValue('C' . $rowCount, $so_line );
                                $spreadsheet->getActiveSheet()->SetCellValue('D' . $rowCount, $this->dateFormat($element['request_date']) );
                                $spreadsheet->getActiveSheet()->SetCellValue('E' . $rowCount, $this->dateFormat($element['promise_date']) );


                                $spreadsheet->getActiveSheet()->SetCellValue('F' . $rowCount, $element['internal_item'] );
                                $spreadsheet->getActiveSheet()->SetCellValue('G' . $rowCount, $element['rbo'] );
                                $spreadsheet->getActiveSheet()->SetCellValue('H' . $rowCount, $element['ordered_item'] );
                                $spreadsheet->getActiveSheet()->SetCellValue('I' . $rowCount, $supply['qty'] );
                                $spreadsheet->getActiveSheet()->SetCellValue('J' . $rowCount, $supply['material_code'] );

                                $spreadsheet->getActiveSheet()->SetCellValue('K' . $rowCount, $supply['material_desc'] );
                                $spreadsheet->getActiveSheet()->SetCellValue('L' . $rowCount, $supply['material_qty'] );
                                $spreadsheet->getActiveSheet()->SetCellValue('M' . $rowCount, $supply['material_uom'] );
                                $spreadsheet->getActiveSheet()->SetCellValue('N' . $rowCount, $element['length'] );
                                $spreadsheet->getActiveSheet()->SetCellValue('O' . $rowCount, $element['width'] );

                                $spreadsheet->getActiveSheet()->SetCellValue('P' . $rowCount, $supply['ink_code'] );
                                $spreadsheet->getActiveSheet()->SetCellValue('Q' . $rowCount, $supply['ink_desc'] );
                                $spreadsheet->getActiveSheet()->SetCellValue('R' . $rowCount, $supply['ink_qty'] );
                                $spreadsheet->getActiveSheet()->SetCellValue('S' . $rowCount, $element['ups'] );
                                $spreadsheet->getActiveSheet()->SetCellValue('T' . $rowCount, $value['updated_by'] );
                                
                                // check KIT, NOTE_1, NOTE_2
                                    $kit = '';
                                    $note_1 = '';
                                    $note_2 = '';
                                    if ($form_type == 'ipps' ) {
                                        $kit = number_format($supply['qty']);
                                        $note_1 = 'IPPS';

                                        foreach ($securityArr as $SecurityCheck ) {
                                            if  (stripos($security, $SecurityCheck) !== false ) {
                                                $note_1 .= ' - HÀNG ĐẶC BIỆT';
                                                break;
                                            } 
                                        }

                                        foreach ($trimcardArr as $trimcard ) {
                                            if (stripos($packing_instructions, $trimcard) !== false ) {
                                                $note_2 = 'TRIM CARD';
                                                break;
                                            }
                                            
                                        }
                                        
                                    } 
                                    
                                $spreadsheet->getActiveSheet()->SetCellValue('U' . $rowCount, $kit );
                                $spreadsheet->getActiveSheet()->SetCellValue('V' . $rowCount, $note_1 );
                                $spreadsheet->getActiveSheet()->SetCellValue('W' . $rowCount, $note_2 );

                            }

                        } else {

                            $materialSave = $this->supply->readSupply(array('internal_item' => $internal_item, 'so_line' => $so_line, 'code_type' => 'material' ));
                            $inkSave = $this->supply->readSupply(array('internal_item' => $internal_item, 'so_line' => $so_line, 'code_type' => 'ink' ));

                            if (count($materialSave) >= count($inkSave) ) {
                                $supply_1 = $materialSave;
                                $supply_2 = $inkSave;
                                $checkSupp = 'material';
                            } else {
                                $supply_1 = $inkSave;
                                $supply_2 = $materialSave;
                                $checkSupp = 'ink';
                            }

                            // get data
                                foreach ($supply_1 as $keyS => $supply ) {

                                    $rowCount++; // count rows

                                    $spreadsheet->getActiveSheet()->SetCellValue('A' . $rowCount, $this->dateFormat($value['po_date']) );
                                    $spreadsheet->getActiveSheet()->SetCellValue('B' . $rowCount, $po_no );
                                    $spreadsheet->getActiveSheet()->SetCellValue('C' . $rowCount, $so_line );
                                    $spreadsheet->getActiveSheet()->SetCellValue('D' . $rowCount, $this->dateFormat($element['request_date']) );
                                    $spreadsheet->getActiveSheet()->SetCellValue('E' . $rowCount, $this->dateFormat($element['promise_date']) );


                                    $spreadsheet->getActiveSheet()->SetCellValue('F' . $rowCount, $element['internal_item'] );
                                    $spreadsheet->getActiveSheet()->SetCellValue('G' . $rowCount, $element['rbo'] );
                                    $spreadsheet->getActiveSheet()->SetCellValue('H' . $rowCount, $element['ordered_item'] );
                                    $spreadsheet->getActiveSheet()->SetCellValue('I' . $rowCount, $element['qty_of_line'] );

                                    // check supply 2
                                    if (!isset($supply_2[$keyS]) ) {

                                        if ($checkSupp == 'material' ) {

                                            $material_code = $supply['code_name'];
                                            $material_desc = $supply['descriptions'];
                                            $material_qty = $supply['qty'];
                                            $material_uom = $supply['uom'];
                                            
                                            $ink_code = '';
                                            $ink_desc = '';
                                            $ink_qty = '';

                                        } else {
                                            $material_code = '';
                                            $material_desc = '';
                                            $material_qty = '';
                                            $material_uom = '';

                                            $ink_code = $supply['code_name'];
                                            $ink_desc = $supply['descriptions'];
                                            $ink_qty = $supply['qty'];
                                        }

                                    } else {
                                        if ($checkSupp == 'material' ) {
                                            $material_code = $supply['code_name'];
                                            $material_desc = $supply['descriptions'];
                                            $material_qty = $supply['qty'];
                                            $material_uom = $supply['uom'];

                                            $ink_code = $supply_2[$keyS]['code_name'];
                                            $ink_desc = $supply_2[$keyS]['descriptions'];
                                            $ink_qty = $supply_2[$keyS]['qty'];

                                        } else {

                                            $material_code = $supply_2[$keyS]['code_name'];
                                            $material_desc = $supply_2[$keyS]['descriptions'];
                                            $material_qty = $supply_2[$keyS]['qty'];
                                            $material_uom = $supply_2[$keyS]['uom'];

                                            $ink_code = $supply['code_name'];
                                            $ink_desc = $supply['descriptions'];
                                            $ink_qty = $supply['qty'];
                                            
                                        }

                                    }
                                    
                                    $spreadsheet->getActiveSheet()->SetCellValue('J' . $rowCount, $material_code );

                                    $spreadsheet->getActiveSheet()->SetCellValue('K' . $rowCount, $material_desc );
                                    $spreadsheet->getActiveSheet()->SetCellValue('L' . $rowCount, $material_qty );
                                    $spreadsheet->getActiveSheet()->SetCellValue('M' . $rowCount, $material_uom );
                                    $spreadsheet->getActiveSheet()->SetCellValue('N' . $rowCount, $element['length'] );
                                    $spreadsheet->getActiveSheet()->SetCellValue('O' . $rowCount, $element['width'] );

                                    $spreadsheet->getActiveSheet()->SetCellValue('P' . $rowCount, $ink_code );
                                    $spreadsheet->getActiveSheet()->SetCellValue('Q' . $rowCount, $ink_desc );
                                    $spreadsheet->getActiveSheet()->SetCellValue('R' . $rowCount, $ink_qty );
                                    $spreadsheet->getActiveSheet()->SetCellValue('S' . $rowCount, $element['ups'] );
                                    $spreadsheet->getActiveSheet()->SetCellValue('T' . $rowCount, $value['updated_by'] );

                                    // check KIT, NOTE_1, NOTE_2
                                        $kit = '';
                                        $note_1 = '';
                                        $note_2 = '';
                                        if ($form_type == 'ipps' ) {
                                            $kit = number_format($supply['qty']);
                                            $note_1 = 'IPPS';

                                            foreach ($securityArr as $SecurityCheck ) {
                                                if  (stripos($security, $SecurityCheck) !== false ) {
                                                    $note_1 .= ' - HÀNG ĐẶC BIỆT';
                                                    break;
                                                } 
                                            }

                                            foreach ($trimcardArr as $trimcard ) {
                                                if (stripos($packing_instructions, $trimcard) !== false ) {
                                                    $note_2 = 'TRIM CARD';
                                                    break;
                                                }
                                                
                                            }
                                            
                                        } 

                                        
                                    

                                    $spreadsheet->getActiveSheet()->SetCellValue('U' . $rowCount, $kit );
                                    $spreadsheet->getActiveSheet()->SetCellValue('V' . $rowCount, $note_1 );
                                    $spreadsheet->getActiveSheet()->SetCellValue('W' . $rowCount, $note_2 );
                                    

                                }

                        }

                    }

                }

        }

        // clear cache (IMPORTANT)
            ob_clean();


        // print_r($spreadsheet->getActiveSheet()->toArray(null, true, true, true)); exit();
		/* ========================= OUT PUT ==============================================================*/

			// set filename for excel file to be exported
			    $filename = 'Thermal_PO_Report_' . date("Y_m_d__H_i_s") . '.xlsx';

			// header: generate excel file
				header('Content-type: application/vnd.ms-excel');
				header('Content-disposition: attachment;filename="'.$filename.'"');
				header('Cache-Control: max-age=0');

                // writer
                    $writer = new Xlsx($spreadsheet);
                    $writer->save('php://output');

                

			

            
    }

    // show count all orders and count now
    public function countOrders() 
    {

		$this->load->model('thermal_save_po', 'save_po');
		$countAll = $this->save_po->countAll();
		$countNow = $this->save_po->countNow();
		$date = date('Y-m-d');

		$this->_data['results'] = array(
			"status" => true,
			"countAll" => $countAll,
			"countNow" => $countNow,
			'now' => $date
		);

		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE); exit();

    }

    // Master data --------------------------------------------------------------------------------------------------------------------------------------------------------
    
    public function masterFile() 
    {    
        $this->_data['title'] = 'Thermal Master File';

        $this->load->view('thermal/master_data/view_masterfile', $this->_data);
    }

    // load master data (main)
	public function loadMasterFile()
	{
        // tilte 
		    $this->_data['title'] = 'Thermal Master File';

		// load models
            $this->load->model('thermal_master_item', 'master_item');
            $this->load->model('thermal_master_item_material_ink', 'supply');

        // XML header
            header('Content-type: text/xml');

        // open
            echo "<rows>";

            // header
                $header = '<head>
                    <column width="50" type="ed" align="center" sort="str">No.</column>

                    <column width="110" type="ed" align="center" sort="str">Form Type</column>
                    <column width="140" type="ed" align="center" sort="str">Internal Item</column>
                    <column width="140" type="ed" align="center" sort="str">RBO</column>
                    <column width="140" type="ed" align="center" sort="str">RBO Remark</column>
                    <column width="120" type="ed" align="center" sort="str">Loại Con Nhãn</column>
                    
                    <column width="120" type="ed" align="center" sort="str">Dài (Length)</column>
                    <column width="120" type="ed" align="center" sort="str">Rộng (Width)</column>
                    <column width="120" type="ed" align="center" sort="str">Đơn Vị</column>
                    <column width="120" type="ed" align="center" sort="str">UPS</column>
                    <column width="120" type="ed" align="center" sort="str">CBS</column>

                    <column width="120" type="ed" align="center" sort="str">GAP</column>
                    <column width="120" type="ed" align="center" sort="str">Số Mặt In</column>
                    <column width="120" type="ed" align="center" sort="str">Máy</column>
                    <column width="120" type="ed" align="center" sort="str">Format</column>
                    <column width="120" type="ed" align="center" sort="str">Standard Speed</column>

                    <column width="120" type="ed" align="center" sort="str">Speed Unit</column>
                    <column width="120" type="ed" align="center" sort="str">Cutter</column>
                    <column width="120" type="ed" align="center" sort="str">Security</column>
                    <column width="120" type="ed" align="center" sort="str">FG IPPS</column>
                    <column width="120" type="ed" align="center" sort="str">PCS SET</column>

                    <column width="120" type="ed" align="center" sort="str">Scrap</column>
                    <column width="120" type="ed" align="center" sort="str">Chiều In Thực Tế</column>
                    <column width="120" type="ed" align="center" sort="str">Layout Prepress</column>

                    <column width="120" type="ed" align="center" sort="str">Material Code</column>
                    <column width="120" type="ed" align="center" sort="str">Material Desc</column>
                    <column width="120" type="ed" align="center" sort="str">Thứ tự</column>
                    <column width="120" type="ed" align="center" sort="str">Material UOM</column>
                    <column width="120" type="ed" align="center" sort="str">Material Số Roll/KIT</column>
                    
                    <column width="120" type="ed" align="center" sort="str">Material Baseroll</column>
                    <column width="120" type="ed" align="center" sort="str">Ink Code</column>
                    <column width="120" type="ed" align="center" sort="str">Ink Desc</column>
                    <column width="120" type="ed" align="center" sort="str">Thứ tự</column>
                    <column width="120" type="ed" align="center" sort="str">Ink UOM</column>

                    <column width="120" type="ed" align="center" sort="str">Ink MT/KIT</column>
                    <column width="120" type="ed" align="center" sort="str">Ink Baseroll</column>
                    <column width="120" type="ed" align="center" sort="str">Remark 1</column>
                    <column width="120" type="ed" align="center" sort="str">Remark 2</column>
                    <column width="120" type="ed" align="center" sort="str">Remark 3</column>
                    
                    <column width="120" type="ed" align="center" sort="str">Remark 4</column>
                    <column width="120" type="ed" align="center" sort="str">Người cập nhật</column>
                    <column width="120" type="ed" align="center" sort="str">Ngày cập nhật</column>
                </head>';

                echo $header;
            // content
                if ($this->master_item->countAll() == 0 ) {
                    echo '<row id="">';
                        // empty
                    echo "</rows>";
                } else {
                    // get data
                        $dataMaster = $this->master_item->read();

                    // set data
                        $index = 0;
                        foreach ($dataMaster as $key => $item ) {

                            $index++;

                            $internal_item = $item['internal_item'];
                            
                            // material
                                $where = array('internal_item' => $internal_item, 'code_type' => 'material');
                                if ($this->supply->check($where ) ) {
                                    $supplyItem = $this->supply->readSupply($where )[0];
                                    $material_code = $supplyItem['code_name'];
                                    $material_desc = $supplyItem['descriptions'];
                                    $material_order = $supplyItem['order'];
                                    $material_uom = $supplyItem['uom'];
                                    $material_roll_qty_per_kit = $supplyItem['roll_qty_per_kit'];
                                    $material_base_roll = $supplyItem['base_roll'];
                                } else {
                                    $material_code = '';
                                    $material_desc = '';
                                    $material_order = '';
                                    $material_uom = '';
                                    $material_roll_qty_per_kit = '';
                                    $material_base_roll = '';
                                }

                            // ink 
                                $where = array('internal_item' => $internal_item, 'code_type' => 'ink');
                                if ($this->supply->check($where ) ) {
                                    $supplyItem = $this->supply->readSupply($where )[0];
                                    $ink_code = $supplyItem['code_name'];
                                    $ink_desc = $supplyItem['descriptions'];
                                    $ink_order = $supplyItem['order'];
                                    $ink_uom = $supplyItem['uom'];
                                    $ink_roll_qty_per_kit = $supplyItem['roll_qty_per_kit'];
                                    $ink_base_roll = $supplyItem['base_roll'];
                                } else {
                                    $ink_code = '';
                                    $ink_desc = '';
                                    $ink_order = '';
                                    $ink_uom = '';
                                    $ink_roll_qty_per_kit = '';
                                    $ink_base_roll = '';
                                }
                            
                            echo '<row id="'. $key .'">';
                                echo '<cell>'. $index .'</cell>';

                                echo '<cell>'. str_replace("&","&amp;",strtoupper($item['form_type'])) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$internal_item) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['rbo']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['rbo_remark']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['kind_of_label']) .'</cell>';


                                echo '<cell>'. str_replace("&","&amp;",$item['length']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['width']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['unit']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['ups']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['cbs']) .'</cell>';

                                echo '<cell>'. str_replace("&","&amp;",$item['gap']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['site_printing']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['machine']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['format']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['standard_speed']) .'</cell>';

                                echo '<cell>'. str_replace("&","&amp;",$item['speed_unit']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['cutter']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['security']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['fg_ipps']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['pcs_set']) .'</cell>';

                                echo '<cell>'. str_replace("&","&amp;",$item['scrap']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['chieu_in_thuc_te']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['layout_prepress']) .'</cell>';
                                

                                echo '<cell>'. str_replace("&","&amp;",$material_code) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$material_desc) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$material_order) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$material_uom) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$material_roll_qty_per_kit) .'</cell>';

                                echo '<cell>'. str_replace("&","&amp;",$material_base_roll) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$ink_code) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$ink_desc) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$ink_order) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$ink_uom) .'</cell>';

                                echo '<cell>'. str_replace("&","&amp;",$ink_roll_qty_per_kit) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$ink_base_roll) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['remark_1']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['remark_2']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['remark_3']) .'</cell>';

                                echo '<cell>'. str_replace("&","&amp;",$item['remark_4']) .'</cell>';
                                echo '<cell>'. $item['updated_by'].'</cell>';
                                echo '<cell>'. $item['updated_date'].'</cell>';
                            echo '</row>';
                            
                        }
                    
                    
                }

        // close
            echo "</rows>";

	}

    public function loadMasterMaterial()
    {
        // tilte 
            $this->_data['title'] = 'Thermal Master File';

        // load models
            $this->load->model('thermal_master_item_material_ink', 'supply');

        // XML header
            header('Content-type: text/xml');

        // open
            echo "<rows>";

            // header
                $header = '<head>
                    <column width="50" type="ed" align="center" sort="str">No.</column>

                    <column width="110" type="ed" align="center" sort="str">Internal Item</column>
                    <column width="140" type="ed" align="center" sort="str">Tên Vật tư/Mực</column>
                    <column width="140" type="ed" align="center" sort="str">Thứ tự</column>
                    <column width="140" type="ed" align="center" sort="str">Mô tả</column>
                    <column width="120" type="ed" align="center" sort="str">Loại</column>
                    
                    <column width="120" type="ed" align="center" sort="str">UOM</column>
                    <column width="150" type="ed" align="center" sort="str">Số Roll/KIT (MT/KIT)</column>
                    <column width="120" type="ed" align="center" sort="str">Baseroll</column>
                    
                    <column width="120" type="ed" align="center" sort="str">Người cập nhật</column>
                    <column width="*" type="ed" align="center" sort="str">Ngày cập nhật</column>

                </head>';

                echo $header;
            // content
                if ($this->supply->countAll() == 0 ) {
                    echo '<row id="">';
                        // empty
                    echo "</rows>";
                } else {
                    // get data
                        $data = $this->supply->read();

                    // set data
                        $index = 0;
                        foreach ($data as $key => $item ) {

                            $index++;

                            
                            echo '<row id="'. $key .'">';
                                echo '<cell>'. $index .'</cell>';

                                echo '<cell>'. str_replace("&","&amp;",strtoupper($item['internal_item'])) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['code_name']) .'</cell>';
                                echo '<cell>'. $item['order'] .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['descriptions']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['code_type']) .'</cell>';


                                echo '<cell>'. str_replace("&","&amp;",$item['uom']) .'</cell>';
                                echo '<cell>'. $item['roll_qty_per_kit'] .'</cell>';
                                echo '<cell>'. $item['base_roll'] .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['updated_by']) .'</cell>';
                                echo '<cell>'. str_replace("&","&amp;",$item['updated_date']) .'</cell>';

                            echo '</row>';
                            
                        }
                    
                    
                }

        // close
            echo "</rows>";
    }

    // import master data
	public function importMasterFile()
	{
        // title 
		    $this->_data['title'] = 'Thermal Import Master File';

		// get data
            $production_line = null !== get_cookie('plan_department') ? trim(get_cookie('plan_department')) : 'thermal';
            $updated_by = null !== get_cookie('plan_loginUser') ? trim(get_cookie('plan_loginUser')) : '';

        // message 
            $message = 'Import data error';
            $status = false;

        // start
		if ($this->input->post('importfile')) {
			
			// init var
                $result = TRUE;
                $message = ' lines updated data successfully ';
                $error = 0;

            // set name
                $file_name = ucfirst($production_line) . '_MasterFile_' . $_SERVER['REMOTE_ADDR'] . '_' . $updated_by . '_' . date('Y-m-d_H-i-s') . '.xlsx';

			// config info
				$path = 'uploads/thermal/';
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
			if ($error == 0 && $import_xls_file !== 0 ) {
				
                // get file
					$inputFileName = $path . $import_xls_file;

				// init PhpSpreadsheet Xlsx
					$Reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

				// get sheet 0 (sheet 1)
					$spreadSheet = $Reader->load($inputFileName); 
					$spreadSheet = $spreadSheet->getSheet(0); 
					$allDataInSheet = $spreadSheet->toArray(null, true, true, true);
				
				// check col name exist
					$createArray = array(
                        'Form_Type', 'Internal_Item', 'RBO', 'RBO_Remark', 'Loai_Con_Nhan', 'Length', 'Width', 'Unit', 'UPS', 'CBS', 
                        'GAP', 'So_Mat_In', 'Machine', 'Format', 'Standard_Speed','Speed_Unit', 'Cutter', 'Security', 'FG_IPPS', 'PCS_SET', 
                        'Scrap', 'Chieu_In_Thuc_Te', 'Layout_Prepress', 'Material_Code', 'Material_Desc', 'Material_Order', 'Material_UOM', 'Material_Roll_Per_KIT', 'Material_Baseroll', 'Ink_Code', 
                        'Ink_Desc', 'Ink_Order', 'Ink_UOM', 'Ink_MT_Per_KIT', 'Ink_Baseroll', 'Remark_1', 'Remark_2', 'Remark_3', 'Remark_4'
                    );

					$makeArray = array( 
                        'Form_Type' => 'Form_Type', 
                        'Internal_Item' => 'Internal_Item',
                        'RBO' => 'RBO',
                        'RBO_Remark' => 'RBO_Remark',
                        'Loai_Con_Nhan' => 'Loai_Con_Nhan',
                        'Length' => 'Length',
                        'Width' => 'Width',
                        'Unit' => 'Unit',
                        'UPS' => 'UPS',
                        'CBS' => 'CBS',

                        'GAP' => 'GAP', 
                        'So_Mat_In' => 'So_Mat_In',
                        'Machine' => 'Machine',
                        'Format' => 'Format',
                        'Standard_Speed' => 'Standard_Speed',
                        'Speed_Unit' => 'Speed_Unit',
                        'Cutter' => 'Cutter',
                        'Security' => 'Security',
                        'FG_IPPS' => 'FG_IPPS',
                        'PCS_SET' => 'PCS_SET',

                        'Scrap' => 'Scrap', 
                        'Chieu_In_Thuc_Te' => 'Chieu_In_Thuc_Te',
                        'Layout_Prepress' => 'Layout_Prepress',
                        'Material_Code' => 'Material_Code',
                        'Material_Desc' => 'Material_Desc',
                        'Material_Order' => 'Material_Order',
                        'Material_UOM' => 'Material_UOM',
                        'Material_So_Roll_Per_KIT' => 'Material_So_Roll_Per_KIT',
                        'Material_Baseroll' => 'Material_Baseroll',
                        'Ink_Code' => 'Ink_Code',

                        'Ink_Desc' => 'Ink_Desc', 
                        'Ink_Order' => 'Ink_Order',
                        'Ink_UOM' => 'Ink_UOM',
                        'Ink_MT_Per_KIT' => 'Ink_MT_Per_KIT',
                        'Ink_Baseroll' => 'Ink_Baseroll',
                        'Remark_1' => 'Remark_1',
                        'Remark_2' => 'Remark_2',
                        'Remark_3' => 'Remark_3',
                        'Remark_4' => 'Remark_4'
                    );
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
				
				// load data
					if ($flag == 1) {

                        // models
                            $this->load->model('thermal_master_item', 'master_item');
                            $this->load->model('thermal_master_item_material_ink', 'supply');
                            $this->load->model('thermal_master_machine', 'machine');

                        // count check
                            $countCheck = 0;
                            $countAll = 0;
						
                        // load data
						for ($i = 2; $i <= count($allDataInSheet); $i++) {
							// get col key
								$Form_Type = $SheetDataKey['Form_Type']; 
								$Internal_Item = $SheetDataKey['Internal_Item']; 
                                $RBO = $SheetDataKey['RBO']; 
                                $RBO_Remark = $SheetDataKey['RBO_Remark']; 
                                $Loai_Con_Nhan = $SheetDataKey['Loai_Con_Nhan']; 
                                $Length = $SheetDataKey['Length']; 
                                $Width = $SheetDataKey['Width']; 
                                $Unit = $SheetDataKey['Unit']; 
                                $UPS = $SheetDataKey['UPS']; 
                                $CBS = $SheetDataKey['CBS']; 

                                $GAP = $SheetDataKey['GAP']; 
                                $So_Mat_In = $SheetDataKey['So_Mat_In']; 
                                $Machine = $SheetDataKey['Machine']; 
                                $Format = $SheetDataKey['Format']; 
                                $Standard_Speed = $SheetDataKey['Standard_Speed']; 
                                $Speed_Unit = $SheetDataKey['Speed_Unit']; 
                                $Cutter = $SheetDataKey['Cutter']; 
                                $Security = $SheetDataKey['Security']; 
                                $FG_IPPS = $SheetDataKey['FG_IPPS']; 
                                $PCS_SET = $SheetDataKey['PCS_SET']; 

                                $Scrap = $SheetDataKey['Scrap']; 
                                $Chieu_In_Thuc_Te = $SheetDataKey['Chieu_In_Thuc_Te']; 
                                $Layout_Prepress = $SheetDataKey['Layout_Prepress']; 
                                $Material_Code = $SheetDataKey['Material_Code']; 
                                $Material_Desc = $SheetDataKey['Material_Desc']; 
                                $Material_Order = $SheetDataKey['Material_Order']; 
                                $Material_UOM = $SheetDataKey['Material_UOM']; 
                                $Material_Roll_Per_KIT = $SheetDataKey['Material_Roll_Per_KIT']; 
                                $Material_Baseroll = $SheetDataKey['Material_Baseroll']; 
                                $Ink_Code = $SheetDataKey['Ink_Code']; 

                                $Ink_Desc = $SheetDataKey['Ink_Desc']; 
                                $Ink_Order = $SheetDataKey['Ink_Order']; 
                                $Ink_UOM = $SheetDataKey['Ink_UOM']; 
                                $Ink_MT_Per_KIT = $SheetDataKey['Ink_MT_Per_KIT']; 
                                $Ink_Baseroll = $SheetDataKey['Ink_Baseroll']; 
                                $Remark_1 = $SheetDataKey['Remark_1']; 
                                $Remark_2 = $SheetDataKey['Remark_2']; 
                                $Remark_3 = $SheetDataKey['Remark_3']; 
                                $Remark_4 = $SheetDataKey['Remark_4']; 

							
							// get data 
								$form_type = filter_var(trim(strtolower($allDataInSheet[$i][$Form_Type]) ), FILTER_SANITIZE_STRING);
								$internal_item = filter_var(trim(strtoupper($allDataInSheet[$i][$Internal_Item])), FILTER_SANITIZE_STRING);
                                $rbo = filter_var(trim(strtoupper($allDataInSheet[$i][$RBO]) ), FILTER_SANITIZE_STRING);
                                $rbo_remark = filter_var(trim($allDataInSheet[$i][$RBO_Remark]), FILTER_SANITIZE_STRING);
                                $kind_of_label = filter_var(trim($allDataInSheet[$i][$Loai_Con_Nhan] ), FILTER_SANITIZE_STRING);
								$length = filter_var(trim($allDataInSheet[$i][$Length]), FILTER_SANITIZE_STRING);
                                $width = filter_var(trim($allDataInSheet[$i][$Width] ), FILTER_SANITIZE_STRING);
								$unit = filter_var(trim($allDataInSheet[$i][$Unit]), FILTER_SANITIZE_STRING);
                                $ups = filter_var(trim($allDataInSheet[$i][$UPS] ), FILTER_SANITIZE_STRING);
								$cbs = filter_var(trim($allDataInSheet[$i][$CBS]), FILTER_SANITIZE_STRING);

                                $gap = filter_var(trim($allDataInSheet[$i][$GAP]), FILTER_SANITIZE_STRING);
                                $site_printing = filter_var(trim($allDataInSheet[$i][$So_Mat_In]), FILTER_SANITIZE_STRING);
                                $machine = filter_var(trim($allDataInSheet[$i][$Machine]), FILTER_SANITIZE_STRING);
                                $format = filter_var(trim($allDataInSheet[$i][$Format]), FILTER_SANITIZE_STRING);
                                $standard_speed = filter_var(trim($allDataInSheet[$i][$Standard_Speed]), FILTER_SANITIZE_STRING);
                                $speed_unit = filter_var(trim($allDataInSheet[$i][$Speed_Unit]), FILTER_SANITIZE_STRING);
                                $cutter = filter_var(trim($allDataInSheet[$i][$Cutter]), FILTER_SANITIZE_STRING);
                                $security = filter_var(trim($allDataInSheet[$i][$Security]), FILTER_SANITIZE_STRING);
                                $fg_ipps = filter_var(trim($allDataInSheet[$i][$FG_IPPS]), FILTER_SANITIZE_STRING);
                                $pcs_set = filter_var(trim($allDataInSheet[$i][$PCS_SET]), FILTER_SANITIZE_STRING);

                                $scrap = filter_var(trim($allDataInSheet[$i][$Scrap]), FILTER_SANITIZE_STRING);
                                $chieu_in_thuc_te = filter_var(trim($allDataInSheet[$i][$Chieu_In_Thuc_Te]), FILTER_SANITIZE_STRING);
                                $layout_prepress = filter_var(trim($allDataInSheet[$i][$Layout_Prepress]), FILTER_SANITIZE_STRING);
                                $material_code = filter_var(trim($allDataInSheet[$i][$Material_Code]), FILTER_SANITIZE_STRING);
                                $material_desc = filter_var(trim($allDataInSheet[$i][$Material_Desc]), FILTER_SANITIZE_STRING);
                                $material_order = filter_var(trim($allDataInSheet[$i][$Material_Order]), FILTER_SANITIZE_STRING);
                                $material_uom = filter_var(trim($allDataInSheet[$i][$Material_UOM]), FILTER_SANITIZE_STRING);
                                $material_roll_per_kit = filter_var(trim($allDataInSheet[$i][$Material_Roll_Per_KIT]), FILTER_SANITIZE_STRING);
                                $material_baseroll = filter_var(trim($allDataInSheet[$i][$Material_Baseroll]), FILTER_SANITIZE_STRING);
                                $ink_code = filter_var(trim($allDataInSheet[$i][$Ink_Code]), FILTER_SANITIZE_STRING);

                                $ink_desc = filter_var(trim($allDataInSheet[$i][$Ink_Desc]), FILTER_SANITIZE_STRING);
                                $ink_order = filter_var(trim($allDataInSheet[$i][$Ink_Order]), FILTER_SANITIZE_STRING);
                                $ink_uom = filter_var(trim($allDataInSheet[$i][$Ink_UOM]), FILTER_SANITIZE_STRING);
                                $ink_mt_per_kit = filter_var(trim($allDataInSheet[$i][$Ink_MT_Per_KIT]), FILTER_SANITIZE_STRING);
                                $ink_baseroll = filter_var(trim($allDataInSheet[$i][$Ink_Baseroll]), FILTER_SANITIZE_STRING);
                                $remark_1 = filter_var(trim($allDataInSheet[$i][$Remark_1]), FILTER_SANITIZE_STRING);
                                $remark_2 = filter_var(trim($allDataInSheet[$i][$Remark_2]), FILTER_SANITIZE_STRING);
                                $remark_3 = filter_var(trim($allDataInSheet[$i][$Remark_3]), FILTER_SANITIZE_STRING);
                                $remark_4 = filter_var(trim($allDataInSheet[$i][$Remark_4]), FILTER_SANITIZE_STRING);


							
							// check empty data
								if (empty($internal_item) ) {
                                    $countCheck++;
                                    if ($countCheck >= 2 ) {
                                        break;
                                    } else {
                                        continue;
                                    }
                                    
                                } 

                            // // clear state cache
                            //     clearstatcache();

                            // set data
                                // master data (main)
                                    $masterData = array(
                                        'form_type' => $form_type,
                                        'internal_item' => $internal_item,
                                        'rbo' => $rbo,
                                        'rbo_remark' => $rbo_remark,
                                        'kind_of_label' => $kind_of_label,
                                        'length' => $length,
                                        'width' => $width,
                                        'unit' => $unit,
                                        'ups' => $ups,
                                        'cbs' => $cbs,

                                        'gap' => $gap,
                                        'site_printing' => $site_printing,
                                        'machine' => $machine,
                                        'format' => $format,
                                        'standard_speed' => $standard_speed,
                                        'speed_unit' => $speed_unit,
                                        'cutter' => $cutter,
                                        'security' => $security,
                                        'fg_ipps' => $fg_ipps,
                                        'pcs_set' => $pcs_set,

                                        'scrap' => $scrap,
                                        'chieu_in_thuc_te' => $chieu_in_thuc_te,
                                        'layout_prepress' => $layout_prepress,
                                        'remark_1' => $remark_1,
                                        'remark_2' => $remark_2,
                                        'remark_3' => $remark_3,
                                        'remark_4' => $remark_4,
                                        'updated_by' => $updated_by
                                    );

                                // material
                                    $MaterialData = array(
                                        'internal_item' => $internal_item,
                                        'code_name' => $material_code,
                                        'order' => $material_order,
                                        'descriptions' => $material_desc,
                                        'code_type' => 'material',
                                        'uom' => $material_uom,
                                        'roll_qty_per_kit' => $material_roll_per_kit,
                                        'base_roll' => $material_baseroll,
                                        'updated_by' => $updated_by
                                    );

                                // ink
                                    $inkData = array(
                                        'internal_item' => $internal_item,
                                        'code_name' => $ink_code,
                                        'order' => $ink_order,
                                        'descriptions' => $ink_desc,
                                        'code_type' => 'ink',
                                        'uom' => $ink_uom,
                                        'roll_qty_per_kit' => $ink_mt_per_kit,
                                        'base_roll' => $ink_baseroll,
                                        'updated_by' => $updated_by
                                    );

                            // check exist: delete
                                // mater data (main)
                                    if ($this->master_item->isAlreadyExist($internal_item) ) {
                                        $this->master_item->delete($internal_item);
                                    }
                                
                                // supply
                                    // material
                                    $whereMaterial = array('internal_item' => $internal_item, 'code_name' => $material_code, 'order' => $material_order );
                                    if ($this->supply->check($whereMaterial) ) {
                                        $this->supply->delete($whereMaterial);
                                    }

                                    // ink 
                                        $whereInk = array('internal_item' => $internal_item, 'code_name' => $ink_code, 'order' => $ink_order );
                                        if ($this->supply->check($whereInk) ) {
                                            $this->supply->delete($whereInk);
                                        }


                            // insert
                                $result = $this->master_item->insert($masterData);
                                if ($result ) {
                                    $result = $this->supply->insert($MaterialData);
                                    if ($result) {
                                        $result = $this->supply->insert($inkData);
                                        if ($result ) {
                                            $countAll++;
                                            $message = "Import Dữ liệu thành công $countAll dòng ";
                                            $status = true;

                                            // update machine
                                                if (!$this->machine->isAlreadyExist(array('machine' => $machine) ) ) {
                                                    $this->machine->insert(array('machine' => $machine, 'updated_by' => $updated_by ) );
                                                } 

                                        } else {
                                            $message = "Import Dữ liệu lỗi dòng thứ $i (INK)";
                                        }
                                    } else {
                                        $message = "Import Dữ liệu lỗi dòng thứ $i (MATERIAL)";
                                    }
                                } else {
                                    $message = "Import Dữ liệu lỗi dòng thứ $i (MAIN)";
                                }

							
							
						}
					} else {
                        $message = "Định dạng file không đúng";
                    }

				
					
					// result
						$this->_data['results'] = array(
							'status' => $status,
							'message' => $message
						);
					
			}
			
		} else {
			
			$this->_data['results'] = array(
				'status' => $status,
				'message' => $message
			);
		}

		$this->load->view('thermal/master_data/display', $this->_data['results']);
		

	}

    // remark --------------------------------------------------------------------------------------------------------------------------------------------------------


    /* Lưu remark: Nếu không có remark để lưu hoặc lưu thành công thì trả về TRUE, ngược lại trả về FALSE */
	public function remark($productionLine, $po_no, $remarkCheckArr )
	{
		// load models
			$this->load->model('common_remarks', 'remarks');
			$this->load->model('common_remark_po_save', 'remark_po_save');

        // xóa các remark cũ đã lưu trước đó. Tránh trường hợp một remark xóa rồi nhưng do làm lệnh trước thì vẫn còn hiển thị khi in
            $this->remark_po_save->deleteNO($po_no );

		// get data
			$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

			$remarkCheck = $this->remarks->readProductionLine($productionLine);

			$result = TRUE;
			if (!empty($remarkCheck ) ) {
				foreach ($remarkCheck as $value ) {
					// get data
						$condition_code = $value['condition_code'];
						$conditions = json_decode($value['conditions']);
						$remark = trim($value['remark']);

					// check
						$count = 0; // đếm xem có mấy điều kiện trong remark
						$count2 = 0; // đếm xem có mấy điều kiện trong dữ liệu cần kiểm tra
						$error = 0;
						foreach ($conditions as $key => $cond ) {
							$count++;

							foreach ($remarkCheckArr as $key2 => $cond2 ) {

								if ($key == $key2 ) {
									// Nếu như là packing thì kiểm tra xem ký tự có nằm trong packing hay không
									if ($key2 == 'packing_instr' ) {
										if (stripos($cond2, $cond) !==false ) {
											$count2++;
										} else {
											$error = 1;
										}
									} else { // ngược lại thì so sánh bằng
										if (trim($cond) == trim($cond2) ) {
											$count2++;
										} else {
											$error = 1;
										}
									}

									break;
								}
							}

							if ($error == 1 ) break;

						}
					
					// save
						if ($error == 0 && ($count == $count2 ) ) {

							if ($this->remark_po_save->isAlreadyExist(array('production_line' => $productionLine, 'po_no' => $po_no, 'remark' => $remark) ) ) {
								$updateData = array(
									'condition_code' => $condition_code,
									'conditions' => json_encode($conditions, JSON_UNESCAPED_UNICODE),
									'updated_by' => $updated_by,
									'updated_date' => date('Y-m-d H:i:s')
								);
								$result =  $this->remark_po_save->update($updateData, array('production_line' => $productionLine, 'po_no' => $po_no, 'remark' => $remark) );
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
	public function packingInstrRemark($production_line, $po_no, $packing_instr, $rbo  )
	{
		$this->load->model('common_remark_po_save', 'remark_po_save');

		$updated_by = isset($_COOKIE['plan_loginUser']) ? $_COOKIE['plan_loginUser'] : '';

		$remark = '';
		$result = '';

		if (strpos(strtoupper($packing_instr), 'KHONG KIM LOAI') !==false ) {
			$remark = 'KHONG KIM LOAI';
		} else {
			$rboArr = array('ADIDAS', 'UNIQLO' );
			foreach ($rboArr as $rboCheck ) {
				if (strpos(strtoupper($rbo), $rboCheck ) !==false ) {
					$remark = 'KHONG KIM LOAI';
					break;
				}
			}
		}

		// save
		if (!empty($remark) || !empty($packing_instr) ) {
            
			$where = array( 'production_line' => $production_line, 'po_no' => $po_no, 'remark' => $remark );

			if ($this->remark_po_save->isAlreadyExist($where) ) {
				$result = $this->remark_po_save->update(array('packing_instr' => $packing_instr, 'updated_by' => $updated_by, 'updated_date' => date('Y-m-d H:i:s') ), $where );
			} else {
				$result = $this->remark_po_save->create(array('production_line' => $production_line, 'po_no' => $po_no, 'remark' => $remark, 'packing_instr' => $packing_instr, 'updated_by' => $updated_by ) );
			}

			// result
			return ($result) ? TRUE : FALSE; 

		} else {
			return TRUE;
		}

	}

    // get machine json
    public function getMachine()
    {
        // tilte 
		    $this->_data['title'] = 'Thermal Master File';
        
        // init
            $machine_arr[] = array('value' => '', 'text' => '');

        // load models
            $this->load->model('thermal_master_machine', 'machine');
        // get data
            $machineData = $this->machine->read();
            foreach ($machineData as $machineItem ) {
                $machine_arr[] = array('value' => $machineItem['machine'], 'text' => $machineItem['machine'] );
            }

        // result
            $this->_data['results'] = array(
                'status' => true,
                'message' => 'Success',
                'machine_json' => $machine_arr
            );

        // results
            echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE); exit();
    }

    public function updateMasterFile()
    {
        // header
            header("Content-Type: application/json");

        // title
		    $this->_data['title'] = 'Update Master File';

        // user
            $updated_by = null !== get_cookie('plan_loginUser') ? trim(get_cookie('plan_loginUser')) : '';
            $updated_date = date('Y-m-d H:i:s');

        // default status
            $status = false;
            $message = "Không có dữ liệu";

        // check empty
            if (!empty($_POST) ) {
                
                // models
                    $this->load->model('thermal_master_item', 'master_item');
                    $this->load->model('thermal_master_item_material_ink', 'supply');

                // get data
                    // master file
                        $form_type = filter_var(trim(strtolower($_POST['form_type']) ), FILTER_SANITIZE_STRING);
                        $internal_item = filter_var(trim(strtoupper($_POST['internal_item'])), FILTER_SANITIZE_STRING);
                        $rbo = filter_var(trim(strtoupper($_POST['rbo']) ), FILTER_SANITIZE_STRING);
                        $rbo_remark = filter_var(trim($_POST['rbo_remark']), FILTER_SANITIZE_STRING);
                        $kind_of_label = filter_var(trim($_POST['kind_of_label'] ), FILTER_SANITIZE_STRING);
                        $length = filter_var(trim($_POST['length']), FILTER_SANITIZE_STRING);
                        $width = filter_var(trim($_POST['width'] ), FILTER_SANITIZE_STRING);
                        $unit = filter_var(trim($_POST['unit']), FILTER_SANITIZE_STRING);
                        $ups = filter_var(trim($_POST['ups'] ), FILTER_SANITIZE_STRING);
                        $cbs = filter_var(trim($_POST['cbs']), FILTER_SANITIZE_STRING);

                        $gap = filter_var(trim($_POST['gap']), FILTER_SANITIZE_STRING);
                        $site_printing = filter_var(trim($_POST['site_printing']), FILTER_SANITIZE_STRING);
                        $machine = filter_var(trim($_POST['machine']), FILTER_SANITIZE_STRING);
                        $format = filter_var(trim($_POST['format']), FILTER_SANITIZE_STRING);
                        $standard_speed = filter_var(trim($_POST['standard_speed']), FILTER_SANITIZE_STRING);
                        $speed_unit = filter_var(trim($_POST['speed_unit']), FILTER_SANITIZE_STRING);
                        $cutter = filter_var(trim($_POST['cutter']), FILTER_SANITIZE_STRING);
                        $security = filter_var(trim($_POST['security']), FILTER_SANITIZE_STRING);
                        $fg_ipps = filter_var(trim($_POST['fg_ipps']), FILTER_SANITIZE_STRING);
                        $pcs_set = filter_var(trim($_POST['pcs_set']), FILTER_SANITIZE_STRING);

                        $scrap = filter_var(trim($_POST['scrap']), FILTER_SANITIZE_STRING);
                        $chieu_in_thuc_te = filter_var(trim($_POST['chieu_in_thuc_te']), FILTER_SANITIZE_STRING);
                        $layout_prepress = filter_var(trim($_POST['layout_prepress']), FILTER_SANITIZE_STRING);
                        $remark_1 = filter_var(trim($_POST['remark_1']), FILTER_SANITIZE_STRING);
                        $remark_2 = filter_var(trim($_POST['remark_2']), FILTER_SANITIZE_STRING);
                        $remark_3 = filter_var(trim($_POST['remark_3']), FILTER_SANITIZE_STRING);
                        $remark_4 = filter_var(trim($_POST['remark_4']), FILTER_SANITIZE_STRING);

                    // material
                        $material_code = filter_var(trim($_POST['material_code']), FILTER_SANITIZE_STRING);
                        $material_desc = filter_var(trim($_POST['material_desc']), FILTER_SANITIZE_STRING);
                        $material_order = filter_var(trim($_POST['material_order']), FILTER_SANITIZE_STRING);
                        $material_uom = filter_var(trim($_POST['material_uom']), FILTER_SANITIZE_STRING);
                        $material_roll_qty_per_kit = filter_var(trim($_POST['material_roll_qty_per_kit']), FILTER_SANITIZE_STRING);
                        $material_baseroll = filter_var(trim($_POST['material_baseroll']), FILTER_SANITIZE_STRING);

                    // ink
                        $ink_code = filter_var(trim($_POST['ink_code']), FILTER_SANITIZE_STRING);
                        $ink_desc = filter_var(trim($_POST['ink_desc']), FILTER_SANITIZE_STRING);
                        $ink_order = filter_var(trim($_POST['ink_order']), FILTER_SANITIZE_STRING);
                        $ink_uom = filter_var(trim($_POST['ink_uom']), FILTER_SANITIZE_STRING);
                        $ink_roll_qty_per_kit = filter_var(trim($_POST['ink_roll_qty_per_kit']), FILTER_SANITIZE_STRING);
                        $ink_baseroll = filter_var(trim($_POST['ink_baseroll']), FILTER_SANITIZE_STRING);

                // save
                    // master file
                        $where = array( 'internal_item' => $internal_item );
                        $updateData = array(
                            'form_type' => $form_type,
                            'rbo' => $rbo,
                            'rbo_remark' => $rbo_remark,
                            'kind_of_label' => $kind_of_label,
                            'length' => $length,
                            'width' => $width,
                            'unit' => $unit,
                            'ups' => $ups,
                            'cbs' => $cbs,

                            'gap' => $gap,
                            'site_printing' => $site_printing,
                            'machine' => $machine,
                            'format' => $format,
                            'standard_speed' => $standard_speed,
                            'speed_unit' => $speed_unit,
                            'cutter' => $cutter,
                            'security' => $security,
                            'fg_ipps' => $fg_ipps,
                            'pcs_set' => $pcs_set,

                            'scrap' => $scrap,
                            'chieu_in_thuc_te' => $chieu_in_thuc_te,
                            'layout_prepress' => $layout_prepress,
                            'remark_1' => $remark_1,
                            'remark_2' => $remark_2,
                            'remark_3' => $remark_3,
                            'remark_4' => $remark_4,
                            'updated_by' => $updated_by,
                            'updated_date' => $updated_date

                        );

                        $result = $this->master_item->update($updateData, $where);
                        if ($result != TRUE ) {
                            $message = "Cập nhật Item: $internal_item lỗi";
                        } else {
                            $where2 = array( 'internal_item' => $internal_item, 'code_name' => $material_code, 'order' => $material_order );
                            $updateData2 = array(
                                'descriptions' => $material_desc,
                                'code_type' => 'material',
                                'uom' => $material_uom,
                                'roll_qty_per_kit' => $material_roll_qty_per_kit,
                                'base_roll' => $material_baseroll,
                                'updated_by' => $updated_by,
                                'updated_date' => $updated_date
                            );

                            $result = $this->supply->update($updateData2, $where2);
                            if ($result != TRUE ) {
                                $message = "Cập nhật Item: $internal_item lỗi (Material)";
                            } else {
                                $where3 = array( 'internal_item' => $internal_item, 'code_name' => $ink_code, 'order' => $ink_order );
                                $updateData3 = array(
                                    'descriptions' => $ink_desc,
                                    'uom' => $ink_uom,
                                    'code_type' => 'ink',
                                    'roll_qty_per_kit' => $ink_roll_qty_per_kit,
                                    'base_roll' => $ink_baseroll,
                                    'updated_by' => $updated_by,
                                    'updated_date' => $updated_date
                                );

                                $result = $this->supply->update($updateData3, $where3);
                                if ($result != TRUE ) {
                                    $message = "Cập nhật Item: $internal_item lỗi (Ink)";
                                } else {
                                    $status = true;
                                    $message = "Cập nhật Item: $internal_item thành công";
                                }

                            }


                            
                        }

                    
            }

        // results
            $results = array( "status" => $status, "message" => $message );

        // render
            echo json_encode($results); exit();
    }


    public function updateMasterMaterial()
    {
        // header
            header("Content-Type: application/json");

        // title
		    $this->_data['title'] = 'Update Master Material';

        // user
            $updated_by = null !== get_cookie('plan_loginUser') ? trim(get_cookie('plan_loginUser')) : '';
            $updated_date = date('Y-m-d H:i:s');

        // default status
            $status = false;
            $message = "Không có dữ liệu";

        // check empty
            if (!empty($_POST) ) {
                
                // models
                    $this->load->model('thermal_master_item_material_ink', 'supply');

                // get data
                    $internal_item = filter_var(trim(strtoupper($_POST['internal_item'])), FILTER_SANITIZE_STRING);
                    $code_name = filter_var(trim($_POST['code_name']), FILTER_SANITIZE_STRING);
                    $descriptions = filter_var(trim($_POST['descriptions']), FILTER_SANITIZE_STRING);
                    $code_type = filter_var(trim($_POST['code_type']), FILTER_SANITIZE_STRING);
                    $order = filter_var(trim($_POST['order']), FILTER_SANITIZE_STRING);
                    $uom = filter_var(trim($_POST['uom']), FILTER_SANITIZE_STRING);
                    $roll_qty_per_kit = filter_var(trim($_POST['roll_qty_per_kit']), FILTER_SANITIZE_STRING);
                    $base_roll = filter_var(trim($_POST['base_roll']), FILTER_SANITIZE_STRING);


                // save
                    // master file
                        $where = array( 'internal_item' => $internal_item, 'code_name' => $code_name, 'order' => $order );
                        $updateData = array(
                            'descriptions' => $descriptions,
                            'code_type' => $code_type,
                            'uom' => $uom,
                            'roll_qty_per_kit' => $roll_qty_per_kit,
                            'base_roll' => $base_roll,
                            'updated_by' => $updated_by,
                            'updated_date' => $updated_date

                        );

                        $result = $this->supply->update($updateData, $where);
                        if ($result != TRUE ) {
                            $message = "Cập nhật Item: $internal_item lỗi";
                        } else {
                            $status = true;
                            $message = "Cập nhật $internal_item - $code_name - $order thành công";
                        }

                    
            }

        // results
            $results = array( "status" => $status, "message" => $message );

        // render
            echo json_encode($results); exit();
    }


    // create 
    public function createMasterFile()
    {
        // header
            header("Content-Type: application/json");

        // title
		    $this->_data['title'] = 'Create Master File';

        // user
            $updated_by = null !== get_cookie('plan_loginUser') ? trim(get_cookie('plan_loginUser')) : '';
            $updated_date = date('Y-m-d H:i:s');

        // default status
            $status = false;
            $message = "Không có dữ liệu";

        // check empty
            if (!empty($_POST) ) {
                
                // models
                    $this->load->model('thermal_master_item', 'master_item');
                    $this->load->model('thermal_master_item_material_ink', 'supply');

                // get data
                    // master file
                        $form_type = filter_var(trim(strtolower($_POST['form_type']) ), FILTER_SANITIZE_STRING);
                        $internal_item = filter_var(trim(strtoupper($_POST['internal_item'])), FILTER_SANITIZE_STRING);
                        $rbo = filter_var(trim(strtoupper($_POST['rbo']) ), FILTER_SANITIZE_STRING);
                        $rbo_remark = filter_var(trim($_POST['rbo_remark']), FILTER_SANITIZE_STRING);
                        $kind_of_label = filter_var(trim($_POST['kind_of_label'] ), FILTER_SANITIZE_STRING);
                        $length = $_POST['length'];
                        $width = $_POST['width'];
                        $unit = filter_var(trim($_POST['unit']), FILTER_SANITIZE_STRING);
                        $ups = $_POST['ups'];
                        $cbs = $_POST['cbs'];

                        $gap = $_POST['gap'];
                        $site_printing = filter_var(trim($_POST['site_printing']), FILTER_SANITIZE_STRING);
                        $machine = filter_var(trim($_POST['machine']), FILTER_SANITIZE_STRING);
                        $format = filter_var(trim($_POST['format']), FILTER_SANITIZE_STRING);
                        $standard_speed = filter_var(trim($_POST['standard_speed']), FILTER_SANITIZE_STRING);
                        $speed_unit = filter_var(trim($_POST['speed_unit']), FILTER_SANITIZE_STRING);
                        $cutter = filter_var(trim($_POST['cutter']), FILTER_SANITIZE_STRING);
                        $security = filter_var(trim($_POST['security']), FILTER_SANITIZE_STRING);
                        $fg_ipps = filter_var(trim($_POST['fg_ipps']), FILTER_SANITIZE_STRING);
                        $pcs_set = $_POST['pcs_set'];

                        $scrap = $_POST['scrap'];
                        $chieu_in_thuc_te = $_POST['chieu_in_thuc_te'];
                        $layout_prepress = $_POST['layout_prepress'];
                        $remark_1 = filter_var(trim($_POST['remark_1']), FILTER_SANITIZE_STRING);
                        $remark_2 = filter_var(trim($_POST['remark_2']), FILTER_SANITIZE_STRING);
                        $remark_3 = filter_var(trim($_POST['remark_3']), FILTER_SANITIZE_STRING);
                        $remark_4 = filter_var(trim($_POST['remark_4']), FILTER_SANITIZE_STRING);

                    // material
                        $material_code = filter_var(trim(strtoupper($_POST['material_code']) ), FILTER_SANITIZE_STRING);
                        $material_desc = filter_var(trim($_POST['material_desc']), FILTER_SANITIZE_STRING);
                        $material_order = 1;
                        $material_uom = filter_var(trim($_POST['material_uom']), FILTER_SANITIZE_STRING);
                        $material_roll_qty_per_kit = $_POST['material_roll_qty_per_kit'];
                        $material_baseroll = $_POST['material_baseroll'];

                    // ink
                        $ink_code = filter_var(trim(strtoupper($_POST['ink_code']) ), FILTER_SANITIZE_STRING);
                        $ink_desc = filter_var(trim($_POST['ink_desc']), FILTER_SANITIZE_STRING);
                        $ink_order = 1;
                        $ink_uom = filter_var(trim($_POST['ink_uom']), FILTER_SANITIZE_STRING);
                        $ink_roll_qty_per_kit = $_POST['ink_roll_qty_per_kit'];
                        $ink_baseroll = $_POST['ink_baseroll'];

                // save
                    // value
                        $where = array( 'internal_item' => $internal_item );
                        $insertData = array(
                            'form_type' => $form_type,
                            'internal_item' => $internal_item,
                            'rbo' => $rbo,
                            'rbo_remark' => $rbo_remark,
                            'kind_of_label' => $kind_of_label,
                            'length' => $length,
                            'width' => $width,
                            'unit' => $unit,
                            'ups' => $ups,
                            'cbs' => $cbs,

                            'gap' => $gap,
                            'site_printing' => $site_printing,
                            'machine' => $machine,
                            'format' => $format,
                            'standard_speed' => $standard_speed,
                            'speed_unit' => $speed_unit,
                            'cutter' => $cutter,
                            'security' => $security,
                            'fg_ipps' => $fg_ipps,
                            'pcs_set' => $pcs_set,

                            'scrap' => $scrap,
                            'chieu_in_thuc_te' => $chieu_in_thuc_te,
                            'layout_prepress' => $layout_prepress,
                            'remark_1' => $remark_1,
                            'remark_2' => $remark_2,
                            'remark_3' => $remark_3,
                            'remark_4' => $remark_4,
                            'updated_by' => $updated_by

                        );

                    // query
                        if ($this->master_item->isAlreadyExist($internal_item) ) {
                            unset($insertData['internal_item']);
                            $insertData['updated_date'] = $updated_date;
                            $result = $this->master_item->update($insertData, $where);
                        } else {
                            $result = $this->master_item->insert($insertData);
                        }

                    // check 
                        if ($result != TRUE ) {
                            $message = "Thêm mới Item: $internal_item lỗi (Master)";
                        } else {
                            // value
                                $where2 = array( 'internal_item' => $internal_item, 'code_name' => $material_code, 'order' => $material_order );
                                $insertData2 = array(
                                    'internal_item' => $internal_item, 
                                    'code_name' => $material_code, 
                                    'order' => $material_order,
                                    'descriptions' => $material_desc,
                                    'code_type' => 'material',
                                    'uom' => $material_uom,
                                    'roll_qty_per_kit' => $material_roll_qty_per_kit,
                                    'base_roll' => $material_baseroll,
                                    'updated_by' => $updated_by
                                );

                            // query
                                if ($this->supply->isAlreadyExist($where2) ) {
                                    unset($insertData2['internal_item']);
                                    unset($insertData2['code_name']);
                                    unset($insertData2['order']);
                                    $insertData2['updated_date'] = $updated_date;
                                    $result = $this->supply->update($insertData2, $where2);
                                } else {
                                    $result = $this->supply->insert($insertData2);
                                }

                            // check 
                                if ($result != TRUE ) {
                                    $message = "Thêm mới Item: $internal_item lỗi (Material)";
                                } else {

                                    // value
                                        $where3 = array( 'internal_item' => $internal_item, 'code_name' => $ink_code, 'order' => $ink_order );
                                        $insertData3 = array(
                                            'internal_item' => $internal_item, 
                                            'code_name' => $ink_code, 
                                            'order' => $ink_order,
                                            'descriptions' => $ink_desc,
                                            'uom' => $ink_uom,
                                            'code_type' => 'ink',
                                            'roll_qty_per_kit' => $ink_roll_qty_per_kit,
                                            'base_roll' => $ink_baseroll,
                                            'updated_by' => $updated_by
                                        );

                                    // query
                                        if ($this->supply->isAlreadyExist($where3) ) {
                                            unset($insertData3['internal_item']);
                                            unset($insertData3['code_name']);
                                            unset($insertData3['order']);
                                            $insertData3['updated_date'] = $updated_date;
                                            $result = $this->supply->update($insertData3, $where3);
                                        } else {
                                            $result = $this->supply->insert($insertData3);
                                        }

                                    // check 
                                        if ($result != TRUE ) {
                                            $message = "Thêm mới Item: $internal_item lỗi (Ink)";
                                        } else {
                                            $status = true;
                                            $message = "Thêm mới Item: $internal_item thành công";
                                        }
                                }
                            
                        }

            }

        // results
            $results = array( "status" => $status, "message" => $message );

        // render
            echo json_encode($results); exit();
    }


    // create 
    public function createMasterMaterial()
    {
        // header
            header("Content-Type: application/json");

        // title
		    $this->_data['title'] = 'Create Master Material';

        // user
            $updated_by = null !== get_cookie('plan_loginUser') ? trim(get_cookie('plan_loginUser')) : '';
            $updated_date = date('Y-m-d H:i:s');

        // default status
            $status = false;
            $message = "Không có dữ liệu";

        // check empty
            if (!empty($_POST) ) {
                
                // models
                    $this->load->model('thermal_master_item_material_ink', 'supply');

                // get data
                        $internal_item = filter_var(trim(strtoupper($_POST['internal_item'])), FILTER_SANITIZE_STRING);
                        $code_name = filter_var(trim(strtoupper($_POST['code_name']) ), FILTER_SANITIZE_STRING);
                        $descriptions = filter_var(trim($_POST['descriptions']), FILTER_SANITIZE_STRING);
                        $order = $_POST['order'];
                        $code_type = filter_var(trim($_POST['code_type']), FILTER_SANITIZE_STRING);
                        $uom = filter_var(trim($_POST['uom']), FILTER_SANITIZE_STRING);
                        $roll_qty_per_kit = $_POST['roll_qty_per_kit'];
                        $base_roll = $_POST['base_roll'];


                // save
                    // value
                        $where = array( 'internal_item' => $internal_item, 'code_name' => $code_name, 'order' => $order );
                        $insertData = array(
                            'internal_item' => $internal_item, 
                            'code_name' => $code_name, 
                            'order' => $order,
                            'descriptions' => $descriptions,
                            'code_type' => $code_type,
                            'uom' => $uom,
                            'roll_qty_per_kit' => $roll_qty_per_kit,
                            'base_roll' => $base_roll,
                            'updated_by' => $updated_by
                        );

                    // query
                        if ($this->supply->isAlreadyExist($where ) ) {
                            unset($insertData['internal_item']);
                            unset($insertData['code_name']);
                            unset($insertData['order']);
                            $insertData['updated_date'] = $updated_date;
                            $result = $this->supply->update($insertData, $where);
                        } else {
                            $result = $this->supply->insert($insertData);
                        }

                    // check 
                        if ($result != TRUE ) {
                            $message = "Thêm mới: $internal_item - $code_name - $order lỗi (Master)";
                        } else {
                            $status = true;
                            $message = "Thêm mới: $internal_item - $code_name - $order thành công";
                        }

            }

        // results
            $results = array( "status" => $status, "message" => $message );

        // render
            echo json_encode($results); exit();
    }


    // export data from old master data to new master data format
    public function exportOldMasterData()
	{

		// load models
            $this->load->model('thermal_master_item', 'master_item');
            $this->load->model('thermal_master_item_material_ink', 'supply');
            $this->load->model('thermal_master_item_old', 'master_item_old');
            $this->load->model('avery_tbl_productline_item', 'productline_item');
		// create
        	$spreadsheet = new Spreadsheet();

		// set the names of header cells
			// set Header, width
			$columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN');

        // check 
            if ($this->master_item_old->countAll() > 0 ) {

                // Add new sheet
                    $spreadsheet->createSheet();

                // Add some data
                    $spreadsheet->setActiveSheetIndex(0);

                // active and set title
                    $spreadsheet->getActiveSheet()->setTitle('Master_Data');

                    $header1 = array(
                        'No.', 'Form_Type', 'Internal_Item', 'RBO', 'RBO_Remark', 'Loai_Con_Nhan', 'Length', 'Width', 'Unit', 'UPS',
                        'CBS', 'GAP', 'So_Mat_In', 'Machine', 'Format', 'Standard_Speed', 'Speed_Unit', 'Cutter', 'Security', 'FG_IPPS',
                        'PCS_SET', 'Scrap', 'Chieu_In_Thuc_Te', 'Layout_Prepress', 'Material_Code', 'Material_Desc', 'Material_Order', 'Material_UOM', 'Material_Roll_Per_KIT', 'Material_Baseroll',
                        'Ink_Code', 'Ink_Desc', 'Ink_Order', 'Ink_UOM', 'Ink_MT_Per_KIT', 'Ink_Baseroll', 'Remark_1', 'Remark_2', 'Remark_3', 'Remark_4'
                    );

                    $id = 0;
                    foreach ($header1 as $header ) {
                        for ($index = $id; $index < count($header1); $index++ ) {
                            // width
                            $spreadsheet->getActiveSheet()->getColumnDimension($columns[$index])->setWidth(20);
                            // headers
                            $spreadsheet->getActiveSheet()->setCellValue($columns[$index] . '1', $header );

                            $id++;
                            break;
                        }
                    }

                // Font
                    $spreadsheet->getActiveSheet()->getStyle('A1:AN1')->getFont()->setBold(true)->setName('Arial')->setSize(10);
                    $spreadsheet->getActiveSheet()->getStyle('A1:AN1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('3399ff');
                    $spreadsheet->getActiveSheet()->getStyle('A:AN')->getFont()->setName('Arial')->setSize(10);

                
                // get data
                    $data = $this->master_item_old->read();

                // set data
                    $rowCount = 1;
                    foreach ($data as $element ) {

                        $rowCount++;

                        $form_type = $element['NHOM'];
                        $internal_item = trim($element['INTERNAL_ITEM']);
                        
                        $unit = '';
                        if ($this->production_item->isAlreadyExist(array('Item' => $internal_item)) ) {
                            $productionItem = $this->production_item->readItem(array('Item' => $internal_item));
                            $unit = trim($productionItem['UOMCost']);
                        }

                        $cbs = trim($element['COLOR_BY_SIZE']);
                        if (stripos($cbs, 'Yes') !== false ) {
                            $cbs = 1;
                        } else if (stripos($cbs, 'No') !== false ) {
                            $cbs = 0;
                        }

                        // standard speed and speed unit
                        $standard_speed = '';
                        $speed_unit = '';
                        $standard_speed_inch = trim($element['STANDARD_SPEED_INCH']);
                        if (!empty($standard_speed_inch) ) {
                            $standard_speed = $standard_speed_inch;
                            $speed_unit = 'inch';
                        }
                        $standard_speed_pcs = trim($element['STANDARD_SPEED_PCS']);
                        if (!empty($standard_speed_pcs) ) {
                            $standard_speed = $standard_speed_pcs;
                            $speed_unit = 'pcs';
                        }

                        // CHIEU_IN_NHAN_THUC_TE
                        $chieu_in_thuc_te = trim($element['CHIEU_IN_NHAN_THUC_TE']);
                        if (empty($chieu_in_thuc_te) || $chieu_in_thuc_te == 0  ) {
                            $chieu_in_thuc_te = 0;
                        } else {
                            if (is_int($chieu_in_thuc_te) ) {
                                $chieu_in_thuc_te = (int)$chieu_in_thuc_te;
                            } else {
                                $chieu_in_thuc_te = 99;
                            }
                        }

                        // LAYOUT_PREPRESS
                        $layout_prepress = trim($element['LAYOUT_PREPRESS']);
                        if (stripos($layout_prepress, 'Yes') !== false ) {
                            $layout_prepress = 1;
                        } else if (stripos($layout_prepress, 'No') !== false ) {
                            $layout_prepress = 0;
                        }

                        // @@@ Dang lam tai day 20210702

                        $spreadsheet->getActiveSheet()->SetCellValue('A' . $rowCount, $rowCount );
                        $spreadsheet->getActiveSheet()->SetCellValue('B' . $rowCount, $form_type );
                        $spreadsheet->getActiveSheet()->SetCellValue('C' . $rowCount, $internal_item );
                        $spreadsheet->getActiveSheet()->SetCellValue('D' . $rowCount, trim($element['RBO']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('E' . $rowCount, '' );

                        $spreadsheet->getActiveSheet()->SetCellValue('F' . $rowCount, trim($element['LOAI_VAT_TU']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('G' . $rowCount, trim($element['CHIEU_DAI']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('H' . $rowCount, trim($element['CHIEU_RONG']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('I' . $rowCount, $unit );
                        $spreadsheet->getActiveSheet()->SetCellValue('J' . $rowCount, trim($element['UPS']) );

                        $spreadsheet->getActiveSheet()->SetCellValue('K' . $rowCount, $cbs );
                        $spreadsheet->getActiveSheet()->SetCellValue('L' . $rowCount, trim($element['GAP']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('M' . $rowCount, trim($element['ONE_TWO_SITE_PRINTING']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('N' . $rowCount, trim($element['MACHINE']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('O' . $rowCount, trim($element['FORMAT']) );

                        $spreadsheet->getActiveSheet()->SetCellValue('P' . $rowCount, $standard_speed );
                        $spreadsheet->getActiveSheet()->SetCellValue('Q' . $rowCount, $speed_unit );
                        $spreadsheet->getActiveSheet()->SetCellValue('R' . $rowCount, trim($element['CUTTER']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('S' . $rowCount, trim($element['SECURITY']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('T' . $rowCount, trim($element['FG_IPPS']) );

                        $spreadsheet->getActiveSheet()->SetCellValue('U' . $rowCount, trim($element['PCS_SET']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('V' . $rowCount, '' );
                        $spreadsheet->getActiveSheet()->SetCellValue('W' . $rowCount, $chieu_in_thuc_te );
                        $spreadsheet->getActiveSheet()->SetCellValue('X' . $rowCount, $layout_prepress );
                        $spreadsheet->getActiveSheet()->SetCellValue('Y' . $rowCount, trim($element['MATERIAL_CODE']) );



                        $spreadsheet->getActiveSheet()->SetCellValue('Z' . $rowCount, trim($element['MATERIAL_DES']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('AA' . $rowCount, 1 );
                        $spreadsheet->getActiveSheet()->SetCellValue('AB' . $rowCount, trim($element['MATERIAL_UOM']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('AC' . $rowCount, trim($element['MATERIAL_DES']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('AD' . $rowCount, trim($element['MATERIAL_DES']) );

                        $spreadsheet->getActiveSheet()->SetCellValue('AE' . $rowCount, trim($element['MATERIAL_DES']) ); // xử lý hiển thị hậu tố cho PO_NO
                        $spreadsheet->getActiveSheet()->SetCellValue('AF' . $rowCount, trim($element['MATERIAL_DES']) );
                        $spreadsheet->getActiveSheet()->SetCellValue('AG' . $rowCount, '' );
                        $spreadsheet->getActiveSheet()->SetCellValue('AH' . $rowCount, (int)$so_cai );
                        $spreadsheet->getActiveSheet()->SetCellValue('AI' . $rowCount, $cut_type );
                        $spreadsheet->getActiveSheet()->SetCellValue('AJ' . $rowCount, $need_vertical_thread_number );
                        $spreadsheet->getActiveSheet()->SetCellValue('AK' . $rowCount, (int)$target_qty );
                        $spreadsheet->getActiveSheet()->SetCellValue('AL' . $rowCount, $element['machine_type'] );
                        $spreadsheet->getActiveSheet()->SetCellValue('AM' . $rowCount, $element['updated_by'] );
                        $spreadsheet->getActiveSheet()->SetCellValue('AN' . $rowCount, $element['updated_date'] );

                    }

            }

		/* ========================= OUT PUT ==============================================================*/
			// set filename for excel file to be exported
			    $filename = 'Thermal_MasterItem_Old_Report_' . date("Y_m_d__H_i_s");

			// header: generate excel file
				header('Content-Type: application/vnd.ms-excel');
				header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
				header('Cache-Control: max-age=0');
			// writer
				$writer = new Xlsx($spreadsheet);
				$writer->save('php://output');

	}


}

?>

