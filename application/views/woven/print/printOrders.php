<?php

	function khongKimLoaiRemark($remarkArr) 
	{
		$result = '';
		if (!empty($remarkArr) ) {
			foreach ($remarkArr as $remark ) {
				if (stripos($remark, 'KHONG KIM LOAI') !==false ) {
					$result = 'KHONG KIM LOAI';
					break;
				}
			}
		}
		
		return $result;
	}

	function grsRemark($remarkArr) 
	{
		$result = '';
		if (!empty($remarkArr) ) {
			foreach ($remarkArr as $key => $remark ) {
				if (stripos($remark, 'GRS') !==false ) {
					$result = '<div style="border: red 1px #000066;background-color:#000066;color:white;font-size:22px;text-align:center;padding:0px;letter-spacing:20px;border-radius: 10px;">GRS</div>';
					unset($remarkArr[$key]);
					break;
				}
			}
		}
		
		return $result;
	}

	if (empty($results) || !isset($results)) { echo "Không lấy được thông tin đơn hàng"; die(); }
	if ($results['status'] == false ) {
		

		$htmls = '';
		$htmls .= '<!DOCTYPE html>';
		$htmls .= '<html>';
		$htmls .= '<head>';
			$htmls .= '<meta charset="utf-8">';
			$htmls .= '<meta name="google" content="notranslate" />';
			$htmls .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
			$htmls .= '<link rel="icon" href="'.base_url('woven/assets/media/images/Logo.ico').'" type="image/x-icon">';
			$htmls .= '<title>Printer Orders</title>';
			// <!-- Font Awesome -->
			$htmls .= '<link rel="stylesheet" href="'. base_url("assets/font-awesome/css/font-awesome.min.css") . '">';
			$htmls .= '<link rel="stylesheet" href="'. base_url("assets/css/print/printHorizontal.css") . '">';
			$htmls .= '<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">';
			$htmls .= '<script src="' . base_url("assets/js/jquery.min.js") . '" ></script>';

			$htmls .= '<script type="text/javascript">';
			$htmls .= 'window.onload = function() {
							window.print();
							setTimeout(
								function() { window.close();},
								1000
							);
						};';
			$htmls .= "try {
							const po = new PerformanceObserver((list) => {
								for (const entry of list.getEntries()) {
									console.log('Server Timing', entry.serverTiming);
								}
							});
							po.observe({type: 'navigation', buffered: true});
						} catch (e) {
							// Do nothing if the browser doesn't support this API.
						}";
			$htmls .= '</script>';
		$htmls .= '</head>';
		$htmls .= '<body>';
			$htmls .= $results['message'];
		$htmls .= '</body>';
		$htmls .= '</html>';
		
		echo $htmls; exit();
	} else {

		// get, set data

			$orderDetail = $results['orderDetail'];
			$supplyDataPrint[] = $results['supplyDataPrint'];

			$processDataPrint = $results['processDataPrint'];
			$solineDataPrint = $results['solineDataPrint'];
			$sizeDataPrint = $results['sizeDataPrint'];
			$remarkDataPrint = $results['remarkDataPrint'];

			$packing_instr = '';
			$remarkArr = array();
			if (!empty($remarkDataPrint) ) {
				foreach ($remarkDataPrint as $remark ) {
					$remarkArr[] = trim($remark['remark']);
					$packing_instr = !empty($remark['packing_instr']) ? trim($remark['packing_instr']) : '';
				}
			}
			
			// print_r($remarkDataPrint);
			
			// $packing_instr = $solineDataPrint[0]['packing_instr'];
			$group_num = isset($sizeDataPrint[0]['group_num']) ? $sizeDataPrint[0]['group_num'] : 0 ;

			// Set value
			$po_no_suffix = trim(strtoupper($orderDetail['po_no_suffix']));
			$form_type_label = $orderDetail['form_type_label'];

			
			$type =  $orderDetail['type'];
			$type_show =  ucwords($orderDetail['type']);

			$po_no = $orderDetail['po_no'];

			// $suffix_arr = array('FOD', 'CCR');
			// $po_no_show = (in_array($po_no_suffix, $suffix_arr) ) ? ($po_no . '-' . $po_no_suffix) : $po_no; 
			$po_no_show =  $po_no;
			$po_no_barcode = $orderDetail['po_no_barcode'];
			$item = $orderDetail['internal_item'];
			$po_date = $orderDetail['po_date'];

			$qty_total = (int)$orderDetail['qty_total'];
			$qty_total_barcode = $orderDetail['qty_total_barcode'];

			$order_type = $orderDetail['order_type'];

			$batch_no = $orderDetail['batch_no'];

			$ordered_date = date('d-m-Y',strtotime($orderDetail['ordered_date']));
			$request_date = date('d-m-Y',strtotime($orderDetail['request_date']));
			$promise_date = date('d-m-Y',strtotime($orderDetail['promise_date']));
			
			$ship_to_customer = $orderDetail['ship_to_customer'];

			$rbo = $orderDetail['rbo'];
			$cs = $orderDetail['cs'];
			$sawing_method = $orderDetail['sawing_method'];
			// $sawing_method = 'XẺ KHỔ';

			$count_size = $orderDetail['count_size'];
			$pick_number_total = $orderDetail['pick_number_total'];
			$textile_size_number = $orderDetail['textile_size_number'];
			$vertical_thread_type = $orderDetail['vertical_thread_type'];
			$folding_cut_type = $orderDetail['folding_cut_type'];
			$need_vertical_thread_number = $orderDetail['need_vertical_thread_number'];
			$length_btp = $orderDetail['length_btp'];
			$length_tp = $orderDetail['length_tp'];
			$heat_weaving = $orderDetail['heat_weaving'];
			$width_btp = $orderDetail['width_btp'];
			$width_tp = $orderDetail['width_tp'];
			$machine_type = strtoupper($orderDetail['machine_type']);
			$wire_number = $orderDetail['wire_number'];
			$gear_density = $orderDetail['gear_density'];

			$cw_specification = ($orderDetail['cw_specification'] != 0 ) ? $orderDetail['cw_specification'] : "" ;
			
			$gear_density_wv = $orderDetail['gear_density_wv'];
			$gear_density_cw = $orderDetail['gear_density_cw'];
			$gear_density_lb = $orderDetail['gear_density_lb'];

			$wire_number_wv = $orderDetail['wire_number_wv'];
			$wire_number_cw = $orderDetail['wire_number_cw'];
			$wire_number_lb = $orderDetail['wire_number_lb'];

			$remark_1 = $orderDetail['remark_1'];
			$remark_2 = $orderDetail['remark_2'];
			$remark_3 = $orderDetail['remark_3'];


			$scrap = $orderDetail['scrap'];
			$cbs = $orderDetail['cbs'];
			if ($cbs == 1 ) {
				$size = 'SIZE';
			} else {
				$size = 'FIX';
			}

			$count_lines = $orderDetail['count_lines'];

		// set htmls
			$htmls = '';
			$htmls .= '<!DOCTYPE html>';
			$htmls .= '<html>';
			$htmls .= '<head>';
				$htmls .= '<meta charset="utf-8">';
				$htmls .= '<meta name="google" content="notranslate" />';
				$htmls .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
				$htmls .= '<link rel="icon" href="'.base_url('woven/assets/media/images/Logo.ico').'" type="image/x-icon">';
				$htmls .= '<title>Printer | ' . $type_show . ' Orders</title>';
				// <!-- Font Awesome -->
				$htmls .= '<link rel="stylesheet" href="'. base_url("assets/font-awesome/css/font-awesome.min.css") . '">';
				$htmls .= '<link rel="stylesheet" href="'. base_url("assets/css/print/printHorizontal.css") . '">';
				$htmls .= '<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">';
				$htmls .= '<script src="' . base_url("assets/js/jquery.min.js") . '" ></script>';

				$htmls .= '<script type="text/javascript">';
				$htmls .= 'window.onload = function() {
								window.print();
								setTimeout(
									function() { window.close();},
									1000
								);
							};';
				$htmls .= "try {
								const po = new PerformanceObserver((list) => {
									for (const entry of list.getEntries()) {
										console.log('Server Timing', entry.serverTiming);
									}
								});
								po.observe({type: 'navigation', buffered: true});
							} catch (e) {
								// Do nothing if the browser doesn't support this API.
							}";
				$htmls .= '</script>';
			$htmls .= '</head>';
			$htmls .= '<body>';
				$htmls .= '<div id="container-box">';
					$htmls .= '<div id="print-title">';
						$htmls .= '<div id="print-logo-title">';
							$htmls .= '<img src="../../assets/media/images/logo-new-w.png" height="30px" ; width="80px" alt="Smiley face">';
						$htmls .= '</div>';
						$htmls .= '<div id="print-main-title">';
							$htmls .= $form_type_label ;
						$htmls .= '</div>';
						// #print-mid-title
						$htmls .= '<div class="print-mid-title print-mid-title-aqua">';
							$htmls .= 'Ngày LSX: ' . $po_date;
						$htmls .= '</div>';
						$htmls .= '<div class="print-mid-title print-mid-title-antiquewhite" >';
							$htmls .= $order_type;
						$htmls .= '</div>';
						// $htmls .= '<div class="print-mid-title">';
						// 	$htmls .= 'BOARD';
						// $htmls .= '</div>';
						$htmls .= '<div class="print-mid-title print-mid-title-aqua">';
							$htmls .= $scrap;
						$htmls .= '</div>';
						$htmls .= '<div class="print-mid-title print-mid-title-antiquewhite">';
							$htmls .= $size;
						$htmls .= '</div>';
						
						$htmls .= '<div class="print-right-title print-mid-title-aqua">';
							$htmls .= 'Số Line: ' . $count_lines;
						$htmls .= '</div>';
					$htmls .= '</div>';

					// $htmls .= '<hr class="dash-break">';
					$htmls .= '<hr class="box-break">';


					$htmls .= '<div id="main-box">';

						// left: set order data details
						$htmls .= '<div id="left-main-box">';
							
							// order detail
							$htmls .= '<div id="order-box">';

								$htmls .= '<table class="">';
									$htmls .= '<tbody>';
										$htmls .= '<tr >';
											//$po_no_css = (strlen($po_no_show) >=13 ) ? 'po-no-2' : 'po-no';
											//$htmls .= '<td colspan=2 class="no-header '.$po_no_css.'" >' . $po_no_show . '</td>';
											// if ( (stripos($po_no_show, 'FOD') !== false) || (stripos($po_no_show, 'CCR') !== false)  ) {
											// 	$htmls .= '<td colspan=2 class="no-header po-no-min" >' . $po_no_show . '</td>';	
											// } else {
											// 	$htmls .= '<td colspan=2 class="no-header po-no" >' . $po_no_show . '</td>';
											// }

											$htmls .= '<td colspan=2 class="no-header po-no" >' . $po_no_show . '</td>';
											
											$htmls .= '<td colspan=3 class="no-header barcode ">' . $po_no_barcode . '</td>';
											$htmls .= '<td colspan=3 class="no-header-right header-bold" style="">Item: ' . $item .' </td>';
										$htmls .= '</tr>';

										$htmls .= '<tr >';
											$htmls .= '<td class="border-hiden header-bold">RBO: </td>';
											$htmls .= '<td colspan=3 class="border-hiden header-bold " style="font-size:20px;">' . $rbo .'</td>';
											$htmls .= '<td class="border-hiden header-bold">Ship To: </td>';
											$htmls .= '<td colspan=4 class="border-hiden header-bold " style="font-size:13px; padding:0px;">' . $ship_to_customer .'</td>';
										$htmls .= '</tr>';

										$htmls .= '<tr >';
											$htmls .= '<td class=" header-bold" style="width:100px;">Ordered Date: </td>';
											$htmls .= '<td class=" header-bold header-value" style="width:100px;">' . $ordered_date .'</td>';
											$htmls .= '<td class=" header-bold " style="width:100px;">Số Khổ:</td>';
											$htmls .= '<td class=" header-bold header-value" style="width:60px;"> '. $textile_size_number .' </td>';
											$htmls .= '<td class=" header-bold" style="width:100px;">Số Dây</td>';
											$htmls .= '<td class=" header-bold header-value" style="width:60px;">' . $wire_number .'</td>';
											$htmls .= '<td class=" header-bold" style="width:100px;">Chiều Rộng BTP: </td>';
											$htmls .= '<td class=" header-bold header-value" style="width:60px;">' . $width_btp .'</td>';
										$htmls .= '</tr>';

										$htmls .= '<tr >';
											$htmls .= '<td class=" header-bold">Request Date: </td>';
											$htmls .= '<td class=" header-bold header-value" style="font-size:14px;" >' . $request_date .'</td>';
											$htmls .= '<td class=" header-bold ">Số Size:</td>';
											$htmls .= '<td class=" header-bold header-value"> '. $count_size .' </td>';
											$htmls .= '<td class=" header-bold">Bánh Răng:</td>';
											$htmls .= '<td class=" header-bold header-value">' . $gear_density .'</td>';
											$htmls .= '<td class=" header-bold">Chiều Rộng TP:</td>';
											$htmls .= '<td class=" header-bold header-value">' . $width_tp .'</td>';
										$htmls .= '</tr>';

										$htmls .= '<tr >';
											$htmls .= '<td class=" header-bold">Promise Date: </td>';
											$htmls .= '<td class=" header-bold header-value" style="font-size:14px;">' . $promise_date .'</td>';
											$htmls .= '<td class=" header-bold ">Loại Chỉ Dọc:</td>';
											$htmls .= '<td class=" header-bold header-value"> '. $vertical_thread_type .' </td>';
											$htmls .= '<td class=" header-bold">Nhiệt Dệt:</td>';
											$htmls .= '<td class=" header-bold header-value">' . $heat_weaving .'</td>';
											$htmls .= '<td class=" header-bold">Chiều Dài BTP:</td>';
											$htmls .= '<td class=" header-bold header-value">' . $length_btp .'</td>';
										$htmls .= '</tr>';

										$htmls .= '<tr >';
											$htmls .= '<td class=" header-bold">Phương pháp xẻ: </td>';
											$htmls .= '<td class=" header-bold header-value" style="font-size:14px; text-transform: uppercase;">' . $sawing_method .'</td>';
											$htmls .= '<td class=" header-bold ">Loại Cắt Gấp:</td>';
											$htmls .= '<td class=" header-bold header-value"> '. $folding_cut_type .' </td>';
											$htmls .= '<td class=" header-bold">Chỉ Dọc Cần (kg)</td>';
											$htmls .= '<td class=" header-bold header-value">' . $need_vertical_thread_number .'</td>';
											$htmls .= '<td class=" header-bold">Chiều Dài TP</td>';
											$htmls .= '<td class=" header-bold header-value">' . $length_tp .'</td>';
										$htmls .= '</tr>';

										$htmls .= '<tr >';
											$htmls .= '<td class=" header-bold">Máy: </td>';
											$htmls .= '<td class=" header-bold header-value" style="font-size:16px; text-transform: uppercase;">' . $machine_type .'</td>';
											$htmls .= '<td class=" header-bold ">Tổng Pick:</td>';
											$htmls .= '<td class=" header-bold header-value"> '. number_format($pick_number_total) .' </td>';
											$htmls .= '<td class=" header-bold">Thông số CW</td>';
											$htmls .= '<td class=" header-bold header-value">'. $cw_specification .'</td>';
											$htmls .= '<td class=" header-bold">&nbsp;</td>';
											$htmls .= '<td class=" header-bold header-value">&nbsp;</td>';
										$htmls .= '</tr>';

									$htmls .= '</tbody>';
								$htmls .= '</table>';

							$htmls .= '</div>';

							// @@@@@@@@@@@@@
								$htmls .= '<div class="machine-details" >';
									$htmls .= 'Máy (bánh răng, số dây): ';
									if (!empty($gear_density_wv ) ) {
										$htmls .= 'WV: (' . $gear_density_wv . ', ' . $wire_number_wv . '); ';
									}
									
									if (!empty($gear_density_cw ) ) {
										$htmls .= 'CW: (' . $gear_density_cw . ', ' . $wire_number_cw . '); ';
									}

									if (!empty($gear_density_lb ) ) {
										$htmls .= 'LB: (' . $gear_density_lb . ', ' . $wire_number_lb . '); ';
									}

								$htmls .= '</div>';	

							// @@@@@@@@@@@@

							$htmls .= '<hr class="dash-break">';
							
							// Vật tư, code keo
							$htmls .= '<div id="order-lable-box">';
								$htmls .= '<table>';
									$htmls .= '<thead>';
										$htmls .= '<tr>';
												$htmls .= '<th>Stt</th>';
												$htmls .= '<th >Vật Tư</th>';
												$htmls .= '<th >Loại VT</th>';
												$htmls .= '<th >Mật Độ</th>';
												$htmls .= '<th >Chiều Dài Chỉ (m)</th>';
												$htmls .= '<th >Số Pick</th>';
												$htmls .= '<th >Số Lượng Cần</th>';
												$htmls .= '<th style="width:25%;" >Số Lô</th>';
											$htmls .= '</tr>';
									$htmls .= '</thead>';
									$htmls .= '<tbody>';
										$index = 1;
										$supplyDataPrint = $supplyDataPrint[0];
										foreach ($supplyDataPrint as $item) {
											$item['grs'] = '';
											if ($item['density'] == 0 ) $item['density'] = '';
											$thread_length = ($item['thread_length'] != 0 ) ? number_format($item['thread_length']) : '';
											if ($item['thread_length'] == 0 ) $item['thread_length'] = '';
											if ($item['pick_number'] == 0 ) $item['pick_number'] = '';
											if ($item['need_horizontal_thread'] == 0 ) $item['need_horizontal_thread'] = '';

											$dvt = ($item['code_type'] == 'glue' ) ? '(m)' : '(kg)';

											$htmls .= '<tr>';
												$htmls .= '<td class="so-line-barcode supply">'. $index .'</td>';
												$htmls .= '<td class="so-line-barcode supply">'. $item['code_name'] .'</td>';
												$htmls .= '<td class="so-line-barcode supply">'. $item['code_type'] .'</td>';
												$htmls .= '<td class="so-line-barcode supply">'. $item['density'] .'</td>';
												$htmls .= '<td class="so-line-barcode supply">'. $thread_length .'</td>';
												$htmls .= '<td class="so-line-barcode supply">'. $item['pick_number'] .'</td>';
												$htmls .= '<td class="so-line-barcode supply">'. $item['need_horizontal_thread'] . ' ' . $dvt .'</td>';
												$htmls .= '<td class="so-line-barcode supply" style="text-align:left;">'. $item['grs'] .'</td>';
											$htmls .= '</tr>';

											$index++;

										} // for end

									$htmls .= '</tbody>';
								$htmls .= '</table>';
							$htmls .= '</div>';
							
							$htmls .= '<hr class="box-break">';
							
							// process, 
							$htmls .= '<div id="process-box">';
								
								$htmls .= '<div id="left-process-box">';
									$htmls .= '<div style="height:57px;">';
										$htmls .= '<table>';

											$htmls .= '<tbody>';
												
												$index = 0;
												$htmls .= '<tr>';
												foreach ($processDataPrint as $item) {
													if ($index == 0 ) {
														$htmls .= '<td class="so-line-barcode supply" style="height:10px; background-color: #59f78d;" >'. $item['order'] .'</td>';
													} else {
														$htmls .= '<td class="so-line-barcode supply" style="height:10px;" >'. $index .'</td>';
													}
													$index++;
												}
												$htmls .= '</tr>';

												$index = 0;
												$htmls .= '<tr>';
												foreach ($processDataPrint as $item) {
													if ($index == 0 ) {
														$htmls .= '<td class="so-line-barcode supply" style="height:12px; background-color: #59f78d;">'. $item['process_name'] .'</td>';
													} else {
														$htmls .= '<td class="so-line-barcode supply" style="height:12px;">'. $item['process_name'] .'</td>';
													}
													$index++;
												}
												$htmls .= '</tr>';

												$index = 0;
												$htmls .= '<tr>';
												foreach ($processDataPrint as $item) {
													if ($index == 0 ) {
														$htmls .= '<td class="so-line-barcode supply" style="height:11px; background-color: #59f78d;">'. $item['name'] .'</td>';
													} else {
														$htmls .= '<td class="so-line-barcode supply" style="height:11px;">'. $item['name'] .'</td>';
													}
													$index++;
												}
												$htmls .= '</tr>';

												$index = 0;
												$htmls .= '<tr>';
												foreach ($processDataPrint as $item) {
													if ($index == 0 ) {
														$htmls .= '<td class="so-line-barcode supply" style="height:11px; background-color: #59f78d;">'. $item['date'] .'</td>';
													} else {
														$htmls .= '<td class="so-line-barcode supply" style="height:11px;">'. $item['date'] .'</td>';
													}
													$index++;
												}
												$htmls .= '</tr>';


											$htmls .= '</tbody>';
										$htmls .= '</table>';
									$htmls .= '</div>';	

									// Do kim loại
									$htmls .= '<div class="do-kim-loai" >';
										$khongKimLoai = khongKimLoaiRemark($remarkArr);
										if (!empty($khongKimLoai ) ) {
											$htmls .= $khongKimLoai;
										}

									$htmls .= '</div>';	

								$htmls .= '</div>';


								$htmls .= '<div id="right-process-box">';
									$htmls .= '<table>';
										$htmls .= '<thead>';
											$htmls .= '<tr>';
													$htmls .= '<th>Stt</th>';
													$htmls .= '<th >Check</th>';
													$htmls .= '<th >Tên</th>';
													$htmls .= '<th >Ngày</th>';
												$htmls .= '</tr>';
										$htmls .= '</thead>';
										$htmls .= '<tbody>';
											$index = 1;
											$operatorPrint[] = array(
												'process_check' => 'Người làm lệnh',
												'name' => $orderDetail['updated_by'],
												'date' => date('d-M',strtotime($orderDetail['po_date']) )
											);
											$operatorPrint[] = array(
												'process_check' => 'Kitting',
												'name' => '',
												'date' => ''
											);
											$operatorPrint[] = array(
												'process_check' => 'QC Online',
												'name' => '',
												'date' => ''
											);
											$operatorPrint[] = array(
												'process_check' => 'QC',
												'name' => '',
												'date' => ''
											);
											$operatorPrint[] = array(
												'process_check' => 'Dò kim loại',
												'name' => '',
												'date' => ''
											);

											foreach ($operatorPrint as $item) {
												$htmls .= '<tr>';
													$htmls .= '<td class="so-line-barcode supply" style="width:8%;">'. $index .'</td>';
													$htmls .= '<td class="so-line-barcode supply" style="width:35%">'. $item['process_check'] .'</td>';
													$htmls .= '<td class="so-line-barcode supply" style="width:35%">'. $item['name'] .'</td>';
													$htmls .= '<td class="so-line-barcode supply" style="width:35%">'. $item['date'] .'</td>';
												$htmls .= '</tr>';
												$index++;
											} // for end
										$htmls .= '</tbody>';
									$htmls .= '</table>';
								$htmls .= '</div>';
								// content

							$htmls .= '</div>';

							
						$htmls .= '</div>';

						// right: set supply data, process
						$htmls .= '<div id="right-main-box">';

							// soline
							$htmls .= '<div id="barcode-box">';
								$htmls .= '<table>';
									$htmls .= '<thead>';
										$htmls .= '<tr>';
												$htmls .= '<th>Stt</th>';
												$htmls .= '<th >SOLine</th>';
												$htmls .= '<th >Số Lượng</th>';
												$htmls .= '<th >Số Giờ</th>';
												$htmls .= '<th >Barcode</th>';
											$htmls .= '</tr>';
									$htmls .= '</thead>';
									$htmls .= '<tbody>';
										$index = 1;
										// print_r($solineDataPrint);
										$qty_total_soline = 0;
										$running_time_total = 0;
										foreach ($solineDataPrint as $item) {

											$htmls .= '<tr>';
												$htmls .= '<td class="so-line-barcode">'. $index .'</td>';
												$htmls .= '<td class="so-line-barcode">'. $item['so_line'] .'</td>';
												$htmls .= '<td class="so-line-barcode">'. number_format($item['qty_of_line']) .'</td>';
												$htmls .= '<td class="so-line-barcode">'. $item['running_time'] .'</td>';
												$htmls .= '<td class="so-line-barcode" style="height:18px;" >'. $item['soline_barcode'] .'</td>';
											$htmls .= '</tr>';

											// total 
											$qty_total_soline += $item['qty_of_line'];
											$running_time_total += $item['running_time'];
											$index++;
										} // for end

										if ($qty_total !== $qty_total_soline ) {
											// $qty_total_soline = (strpos($po_no, 'NS') !==false) ? $qty_total_soline : '???';
											$qty_total_soline = '???';
										} else {
											$qty_total_soline = number_format($qty_total_soline);
										}

										$htmls .= '<tr>';
											$htmls .= '<td colspan=2 class="so-line-barcode">TỔNG</td>';
											$htmls .= '<td class="so-line-barcode">'. $qty_total_soline .'</td>';
											$htmls .= '<td class="so-line-barcode">'. number_format($running_time_total,2) .'</td>';
											// $htmls .= '<td class="so-line-barcode" style="height:20px;" >'. $qty_total_barcode .'</td>';
											$htmls .= '<td class="so-line-barcode" style="height:20px;" >&nbsp;</td>';
										$htmls .= '</tr>';
										
									$htmls .= '</tbody>';
								$htmls .= '</table>';
							$htmls .= '</div>';

							// $htmls .= '<hr class="dash-break">'; //ddd
							// Hiển thị GRS lên trên cùng của các Remark
							$grsRemark = grsRemark($remarkArr);

							$htmls .= '<hr class="box-break">';
								$htmls .= '<div class="remark-all">';
									if(!empty($grsRemark) ) {
										$htmls .= '<div class="remark-detail">';
											$htmls .= $grsRemark;
										$htmls .= '</div>';
									}
									
									if(!empty($remark_1) ) {
										$htmls .= '<div class="remark-detail">';
											$htmls .= ' - ' . $remark_1;
										$htmls .= '</div>';
									}

									if(!empty($remark_2) ) {
										$htmls .= '<div class="remark-detail">';
											$htmls .= ' - ' . $remark_2;
										$htmls .= '</div>';
									}
									
									if(!empty($remark_3) ) {
										$htmls .= '<div class="remark-detail">';
											$htmls .= ' - ' . $remark_3;
										$htmls .= '</div>';
									}

									if (!empty($remarkArr) ) {
										foreach ($remarkArr as $remarkShow ) {
											$remarkShow = (stripos($remarkShow, 'KHONG KIM LOAI') !==false ) ? str_replace('KHONG KIM LOAI', '', trim($remarkShow) ) : trim($remarkShow);
											if (!empty($remarkShow) && $remarkShow != ' ' ) {
												if (stripos($remarkShow, 'GRS') !==false ) continue;
												$htmls .= '<div class="remark-detail">';
													$htmls .= ' - ' . $remarkShow;
												$htmls .= '</div>';
											}
											
										}
									}

									$packing_instr = (stripos($packing_instr, 'KHONG KIM LOAI') !==false ) ? str_replace('KHONG KIM LOAI', ' ', $packing_instr ) : $packing_instr;
									if(!empty($packing_instr) ) {
										$htmls .= '<div class="remark-detail">';
											$htmls .= ' - ' . $packing_instr;
										$htmls .= '</div>';
									}

									// Còn remark tool thì sử dụng vòng lặp, kiểm tra isset, nếu có thì hiển thị
									
								$htmls .= '</div>';


						$htmls .= '</div>';

					$htmls .= '</div>';

					
					
					// remark
					$htmls .= '<div id="size-box-2">';
						// content 
						if ($type == 'common' || $type == 'non_batching' ) {
							if ($type == 'non_batching' ) {
								if (stripos($machine_type,'WV') !==false ) {
									$so_cuon = 5;
								} else if (stripos($machine_type,'CW') !==false ) {
									$so_cuon = 6;
								} else if (stripos($machine_type,'LB') !==false ) {
									$so_cuon = 8;
								}

							} else {
								if ($group_num == 0 ) {
									$so_cuon = '(không lấy được số group)';
								} else {
									$so_cuon = '(không lấy được số group)';
									// $so_cuon = $group_num * (int)$textile_size_number;
									if (stripos($machine_type,'WV') !==false ) {
										$so_cuon = $group_num * 5;
									} else if (stripos($machine_type,'CW') !==false ) {
										$so_cuon = $group_num * 6;
									} else if (stripos($machine_type,'LB') !==false ) {
										$so_cuon = $group_num * 8;
									}
								}
							}
							

							$remark_form = "ĐƠN BATCHING. Số Batch: $batch_no. Đơn hàng này có <span style='font-size:15px;'> $so_cuon </span> cuộn";

							if ($type == 'non_batching' ) { // đơn non batching thì số cuộn bằng số khổ
								$remark_form = "ĐƠN NON BATCHING. Số Batch: $batch_no. Đơn hàng này có <span style='font-size:15px;'> $so_cuon </span> cuộn";
							}
							
							
						} else {
							$remark_form = 'ĐƠN HÀNG: ' . strtoupper($type) . '-'.  $po_no_suffix;
						}
						$htmls .= $remark_form;

					$htmls .= '</div>';

					$htmls .= '<hr class="box-break">';

					// print_r($sizeDataPrint);

					// set size data
					if ($type == 'common' || $type == 'non_batching' ) {
						include_once ('sizeDataPrint_common.php');	
					} else {
						include_once ('sizeDataPrint.php');
					}
					

					// $htmls .= '<hr class="box-break" style="width:100%;">';

					

				$htmls .= '</div>';

			$htmls .= '<body>';

		// $htmls = htmlspecialchars($htmls);
		// echo htmlspecialchars_decode($htmls);
		// results
			echo $htmls; exit();

	}
