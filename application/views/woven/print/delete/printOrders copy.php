<?php 
	if (empty($results) || !isset($results)) {
		echo "Không lấy được thông tin đơn hàng";
		die();
	}

	$orderDetail = $results['orderDetail'];
	$supplyDataPrint[] = $results['supplyDataPrint'];
	
	$processDataPrint = $results['processDataPrint'];
	$solineDataPrint[] = $results['solineDataPrint'];
	$sizeDataPrint = $results['sizeDataPrint'];

	// Set value
	$form_type = $orderDetail['po_no_suffix'];
	$form_type_label = $orderDetail['form_type_label'];

	$po_no = $orderDetail['po_no'];
	$po_no_barcode = $orderDetail['po_no_barcode'];
	$item = $orderDetail['internal_item'];
	$po_date = $orderDetail['po_date'];

	$ordered_date = $orderDetail['ordered_date'];
	$request_date = $orderDetail['request_date'];
	$promise_date = $orderDetail['promise_date'];
	$ship_to_customer = $orderDetail['ship_to_customer'];

	$rbo = $orderDetail['rbo'];
	$cs = $orderDetail['cs'];
	$sawing_method = $orderDetail['sawing_method'];
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
	$gear_density_wv = $orderDetail['gear_density_wv'];
	$gear_density_cw = $orderDetail['gear_density_cw'];
	$gear_density_lb = $orderDetail['gear_density_lb'];

	$ship_to_customer = $orderDetail['ship_to_customer'];
	$ship_to_customer = $orderDetail['ship_to_customer'];
	$ship_to_customer = $orderDetail['ship_to_customer'];
	$ship_to_customer = $orderDetail['ship_to_customer'];

	// set htmls <?php echo base_url('woven/assets/media/images/Logo.ico')
	$htmls = '';
	$htmls .= '<!DOCTYPE html>';
	$htmls .= '<html>';
	$htmls .= '<head>';
		$htmls .= '<meta charset="utf-8">';
		$htmls .= '<meta name="google" content="notranslate" />';
		$htmls .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
		$htmls .= '<link rel="icon" href="'.base_url('woven/assets/media/images/Logo.ico').'" type="image/x-icon">';
		$htmls .= '<title>Printer | ' . $form_type . ' Orders</title>';
		// <!-- Font Awesome -->
		$htmls .= '<link rel="stylesheet" href="'. base_url("assets/font-awesome/css/font-awesome.min.css") . '">';
		$htmls .= '<link rel="stylesheet" href="'. base_url("assets/css/print/printHorizontal.css") . '">';
		$htmls .= '<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">';
		$htmls .= '<script src="' . base_url("assets/js/jquery.min.js") . '" ></script>';
	
		$htmls .= '<script type="text/javascript">
						window.onload = function() {
							window.print();
							setTimeout(
								function() { window.close();}, 
								100
							);
						}
		</script>';
	$htmls .= '</head>';
	$htmls .= '<body>';
		$htmls .= '<div id="container-box">';
			$htmls .= '<div id="print-title">';
				$htmls .= '<div id="print-logo-title">';
					$htmls .= '<img src="./assets/media/images/logo-new-w.png" height="30px" ; width="80px" alt="Smiley face">';
				$htmls .= '</div>';
				$htmls .= '<div id="print-main-title">';
					$htmls .= strtoupper($form_type_label) ;
				$htmls .= '</div>';
				// #print-mid-title
				$htmls .= '<div class="print-mid-date-title">';
					$htmls .= 'Ngày làm lệnh: 2020-06-08';
				$htmls .= '</div>';
				$htmls .= '<div class="print-mid-title_2">';
					$htmls .= 'NORMAL';
				$htmls .= '</div>';
				$htmls .= '<div class="print-mid-title">';
					$htmls .= 'BOARD';
				$htmls .= '</div>';
				$htmls .= '<div class="print-mid-title_2">';
					$htmls .= 'SCRAP';
				$htmls .= '</div>';
				$htmls .= '<div class="print-mid-title">';
					$htmls .= 'SIZE';
				$htmls .= '</div>';
				$htmls .= '<div class="print-right-title">';
					$htmls .= 'Số SOLine: ';
				$htmls .= '</div>';
			$htmls .= '</div>';

			// $htmls .= '<hr class="dash-break">';
			$htmls .= '<hr class="box-break">';

			
			$htmls .= '<div id="main-box">';
				
				// left: set order data details
				$htmls .= '<div id="left-main-box">';
					$htmls .= '<div id="order-box">';

						$htmls .= '<table class="">';
							$htmls .= '<tbody>';
								$htmls .= '<tr >';
									$htmls .= '<td colspan=2 class="no-header po-no" >' . $po_no . '</td>';
									$htmls .= '<td colspan=2 class="no-header barcode ">' . $po_no_barcode . '</td>';
									$htmls .= '<td colspan=2 class="no-header-right header-bold" style="font-size:18px;">Item: ' . $item .' </td>';
									// $htmls .= '<td class="no-header po-date-value header-bold" style="font-size:18px;" >' . $item .'</td>';
								$htmls .= '</tr>';

								$htmls .= '<tr >';
									$htmls .= '<td class="border-hiden header-bold">RBO: </td>';
									$htmls .= '<td colspan=2 class="border-hiden header-bold " style="font-size:20px;">' . $rbo .'</td>';
									$htmls .= '<td class="border-hiden header-bold">Ship To: </td>';
									$htmls .= '<td colspan=2 class="border-hiden header-bold " style="font-size:11px; padding:0px;">' . $ship_to_customer .'</td>';
								$htmls .= '</tr>';

								$htmls .= '<tr >';
									$htmls .= '<td class=" header-bold">Ordered Date: </td>';
									$htmls .= '<td class=" header-bold header-value">' . $ordered_date .'</td>';
									$htmls .= '<td class=" header-bold ">Request Date:</td>';
									$htmls .= '<td class=" header-bold header-value"> '. $request_date .' </td>';
									$htmls .= '<td class=" header-bold">Promise Date</td>';
									$htmls .= '<td class=" header-bold header-value">' . $promise_date .'</td>';
								$htmls .= '</tr>';

								$htmls .= '<tr >';
									$htmls .= '<td class=" header-bold">Phương pháp xẻ: </td>';
									$htmls .= '<td class=" header-bold header-value" style="font-size:20px; text-transform: uppercase;">' . $sawing_method .'</td>';
									$htmls .= '<td class=" header-bold ">Số Size:</td>';
									$htmls .= '<td class=" header-bold header-value"> '. $count_size .' </td>';
									$htmls .= '<td class=" header-bold">Tổng Pick:</td>';
									$htmls .= '<td class=" header-bold header-value">' . number_format($pick_number_total) .'</td>';
								$htmls .= '</tr>';

								$htmls .= '<tr >';
									$htmls .= '<td class=" header-bold">Số Khổ: </td>';
									$htmls .= '<td class=" header-bold header-value">' . $textile_size_number .'</td>';
									$htmls .= '<td class=" header-bold ">Loại Chỉ Dọc:</td>';
									$htmls .= '<td class=" header-bold header-value"> '. $vertical_thread_type .' </td>';
									$htmls .= '<td class=" header-bold">Loại Cắt Gấp:</td>';
									$htmls .= '<td class=" header-bold header-value">' . $folding_cut_type .'</td>';
								$htmls .= '</tr>';

								$htmls .= '<tr >';
									$htmls .= '<td class=" header-bold">Chỉ Dọc Cần (kg): </td>';
									$htmls .= '<td class=" header-bold header-value">' . $need_vertical_thread_number .'</td>';
									$htmls .= '<td class=" header-bold ">Chiều Rộng BTP:</td>';
									$htmls .= '<td class=" header-bold header-value"> '. $length_btp .' </td>';
									$htmls .= '<td class=" header-bold">Chiều Rộng TP</td>';
									$htmls .= '<td class=" header-bold header-value">' . $length_tp .'</td>';
								$htmls .= '</tr>';

								$htmls .= '<tr >';
									$htmls .= '<td class=" header-bold">Nhiệt Dệt: </td>';
									$htmls .= '<td class=" header-bold header-value">' . $heat_weaving .'</td>';
									$htmls .= '<td class=" header-bold ">Chiều Dài BTP:</td>';
									$htmls .= '<td class=" header-bold header-value"> '. $width_btp .' </td>';
									$htmls .= '<td class=" header-bold">Chiều Dài TP</td>';
									$htmls .= '<td class=" header-bold header-value">' . $width_tp .'</td>';
								$htmls .= '</tr>';

								$htmls .= '<tr >';
									$htmls .= '<td class=" header-bold">Máy: </td>';
									$htmls .= '<td class=" header-bold header-value" style="font-size:20px; text-transform: uppercase;">' . $machine_type .'</td>';
									$htmls .= '<td class=" header-bold ">Số Dây:</td>';
									$htmls .= '<td class=" header-bold header-value"> '. $wire_number .' </td>';
									$htmls .= '<td class=" header-bold">Bánh Răng</td>';
									$htmls .= '<td class=" header-bold header-value">' . $gear_density .'</td>';
								$htmls .= '</tr>';
								
							$htmls .= '</tbody>';
						$htmls .= '</table>';

					$htmls .= '</div>';

					$htmls .= '<hr class="dash-break">';
					
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
								foreach ($solineDataPrint as $item) {
									$htmls .= '<tr>';
										$htmls .= '<td class="so-line-barcode">'. $index .'</td>';
										$htmls .= '<td class="so-line-barcode">'. $item['so_line'] .'</td>';
										$htmls .= '<td class="so-line-barcode">'. number_format($item['qty_of_line']) .'</td>';
										$htmls .= '<td class="so-line-barcode">'. $item['running_time'] .'</td>';
										$htmls .= '<td class="so-line-barcode">'. $item['soline_barcode'] .'</td>';
									$htmls .= '</tr>';
									$index++;
								} // for end
							$htmls .= '</tbody>';
						$htmls .= '</table>';
					$htmls .= '</div>';
				$htmls .= '</div>';
				
				// right: set supply data, process
				$htmls .= '<div id="right-main-box">';
					
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
										$htmls .= '<th >Chỉ Ngang Cần (kg)</th>';
										$htmls .= '<th >Số Lô</th>';
									$htmls .= '</tr>';
							$htmls .= '</thead>';
							$htmls .= '<tbody>';
								$index = 1;
								$supplyDataPrint = $supplyDataPrint[0];
								foreach ($supplyDataPrint as $item) {
									$item['so_lo'] = '';
									if ($item['density'] == 0 ) $item['density'] = '';
									$thread_length = ($item['thread_length'] != 0 ) ? number_format($item['thread_length']) : '';
									if ($item['thread_length'] == 0 ) $item['thread_length'] = '';
									if ($item['pick_number'] == 0 ) $item['pick_number'] = '';
									if ($item['need_horizontal_thread'] == 0 ) $item['need_horizontal_thread'] = '';

									$htmls .= '<tr>';
										$htmls .= '<td class="so-line-barcode supply">'. $index .'</td>';
										$htmls .= '<td class="so-line-barcode supply">'. $item['code_name'] .'</td>';
										$htmls .= '<td class="so-line-barcode supply">'. $item['code_type'] .'</td>';
										$htmls .= '<td class="so-line-barcode supply">'. $item['density'] .'</td>';
										$htmls .= '<td class="so-line-barcode supply">'. $thread_length .'</td>';
										$htmls .= '<td class="so-line-barcode supply">'. $item['pick_number'] .'</td>';
										$htmls .= '<td class="so-line-barcode supply">'. $item['need_horizontal_thread'] .'</td>';
										$htmls .= '<td class="so-line-barcode supply">'. $item['so_lo'] .'</td>';
									$htmls .= '</tr>';

									$index++;

								} // for end

							$htmls .= '</tbody>';
						$htmls .= '</table>';
					$htmls .= '</div>';

					$htmls .= '<hr class="dash-break">'; //ddd
					$htmls .= '<div id="process-box">';
						$htmls .= '<div id="left-process-box">';
							$htmls .= '<table>';
								$htmls .= '<thead>';
									$htmls .= '<tr>';
											$htmls .= '<th>Stt</th>';
											$htmls .= '<th >Process</th>';
											$htmls .= '<th >Tên</th>';
										$htmls .= '</tr>';
								$htmls .= '</thead>';
								$htmls .= '<tbody>';
									$index = 1;
									
									foreach ($processDataPrint as $item) {
										
										$htmls .= '<tr>';
											$htmls .= '<td class="so-line-barcode supply" style="width:8%;">'. $index .'</td>';
											$htmls .= '<td class="so-line-barcode supply" style="width:35%">'. $item['process_name'] .'</td>';
											$htmls .= '<td class="so-line-barcode supply">&nbsp;</td>';
										$htmls .= '</tr>';
										$index++;
									} // for end
								$htmls .= '</tbody>';
							$htmls .= '</table>';
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

			$htmls .= '</div>';

			$htmls .= '<hr class="box-break">';
			
			// set size data
			include_once ('sizeDataPrint.php');


			$htmls .= '<hr class="box-break" style="width:100%;">';
			
			// remark
			$htmls .= '<div id="size-box2">';
				// content
				$htmls .= 'Bánh răng ';	
				$htmls .= 'WV: ' . $gear_density_wv;	
				if (!empty($gear_density_cw ) ) {
					$htmls .= ', CW: ' . $gear_density_cw;	
				}
				if (!empty($gear_density_lb ) ) {
					$htmls .= ', LB: ' . $gear_density_lb;	
				}
				
			$htmls .= '</div>';

		$htmls .= '</div>';

	$htmls .= '<body>';

	$htmls = htmlspecialchars($htmls);
	echo htmlspecialchars_decode($htmls);


?>
<script>

	for (var i=1;i<=2;i++ ) {
		var id = '#size-table-' + i;
		var sizeTable = $(id);
		var height = sizeTable.height();
		var width = sizeTable.width();
		console.log('height: ' + height);
		console.log('width: ' + width);
	}
	
</script>