<?php 
	$url_home = "/tandoan/planning/";
	if (empty($print_data) || !isset($print_data)) {
		echo "Không lấy được thông tin đơn hàng";
		die();
	}
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="google" content="notranslate" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="/Module/Images/Logo.ico" type="image/x-icon">
	<title>Printer | <?php echo isset($form_type) ? $form_type : 'Orders'; ?> </title>
	<!-- Font Awesome -->
	<link rel="stylesheet" href="/tandoan/planning/bower_components/font-awesome/css/font-awesome.min.css">
	<!-- Ionicons -->
	<link rel="stylesheet" href="/tandoan/planning/bower_components/Ionicons/css/ionicons.min.css">
	<!-- Theme style -->
	<!-- <link rel="stylesheet" href="/tandoan/planning/dist/css/AdminLTE.min.css"> -->
	<link rel="stylesheet" href="<?php echo base_url('assets/css/printer/print_Horizontal.css'); ?>">

	<!-- Google Font -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

</head>
<script type="text/javascript">
	window.onload = function() {
		window.print();
		setTimeout(function() {
			window.close();
		}, 100);
	}
</script>
<!-- onload="window.print();" -->
<body>
	<div id="container-box">
		<div id="print-title">
			<div id="print-logo-title">
				<img src="/Module/Images/AD-Logo.png" height="45px" ; width="150px" alt="Smiley face">
			</div>
			<div id="print-main-title">
				LỆNH SẢN XUẤT <?php echo strtoupper($production_line) . ': ' . strtoupper($form_type_label);  ?>
			</div>
			<div id="print-right-title">
				Số line: <?php echo $print_data[0]['count_line']; ?> &nbsp;&nbsp;&nbsp;
			</div>
		</div>
		<hr class="dash-break">
		<!-- header box -->
		<div id="header-box">
			<?php
				echo '<table class="border-hiden">';
					echo '<tbody>';
						echo '<tr >';
							echo '<td colspan=2 class="no-header po-no" >' . $po_no . '</td>';
							// echo '<td>111</td>';
							echo '<td colspan=3 class="no-header barcode ">' . $so_line_barcode . '</td>';
							// echo '<td>1</td>';
							echo '<td class="no-header-right header-bold">Ngày làm đơn: </td>';
							echo '<td class="no-header po-date-value header-bold" >' . $print_data[0]['po_date'] .'</td>';
						echo '</tr>';
						echo '<tr >';
							echo '<td style="width:12%;" class="border-hiden header-bold">Ordered date: </td>';
							echo '<td style="width:12%;" class="border-hiden header-bold header-value">' . $print_data[0]['ordered_date'] .'</td>';
							echo '<td style="width:15%;" class="border-hiden ">&nbsp;</td>';
							echo '<td style="width:10%;" class="border-hiden header-bold">Ship to: </td>';
							echo '<td colspan=3 style="width:48%;" class="border-hiden header-bold header-value">' . $print_data[0]['ship_to_customer'] .'</td>';
							// echo '<td>2</td>';
						echo '</tr>';
						echo '<tr >';
							echo '<td class="border-hiden header-bold">Request date: </td>';
							echo '<td class="border-hiden header-bold header-value">' . $print_data[0]['request_date'] .'</td>';
							echo '<td class="border-hiden ">&nbsp;</td>';
							echo '<td class="border-hiden header-bold">RBO: </td>';
							echo '<td colspan=3 class="border-hiden header-bold header-value">' . $print_data[0]['rbo'] .'</td>';
							// echo '<td>2</td>';
						echo '</tr>';
						echo '<tr>';
							echo '<td class="border-hiden header-bold">Promise date: </td>';
							echo '<td class="border-hiden header-bold header-value">' . $print_data[0]['promise_date'] .'</td>';
							echo '<td class="border-hiden ">&nbsp;</td>';
							echo '<td class="border-hiden header-bold">CS name: </td>';
							echo '<td colspan=3 class="border-hiden header-bold header-value">' . $print_data[0]['cs'] .'</td>';
							// echo '<td>2</td>';
						echo '</tr>';
					echo '</tbody>';
				echo '</table>';

			?>
		</div>
		<hr class="dash-break">
		<!-- ./ header box -->
		<!-- main 1 -->
		<div id="main-box-1">
			<?php
			echo '<table>';
				echo '<thead>';
						echo '<tr>';
							echo '<th rowspan=2 class="header-main-info" style="3%;"><i class="fa fa-list-ol" aria-hidden="true"></i></th>';
							echo '<th colspan=4 class="header-main-info">Order info</th>';
							// echo '<th>Qty</th>';
							// echo '<th>Qty</th>';
							// echo '<th>Qty</th>';
							echo '<th colspan=2 class="header-main-info">Size label</th>';
							// echo '<th>Qty</th>';
							echo '<th colspan=2 class="header-main-info">Paper info</th>';
							// echo '<th>Qty</th>';
							// echo '<th>Qty</th>';
							echo '<th colspan=2 class="header-main-info">Ink info</th>';
							// echo '<th>Qty</th>';
							// echo '<th>Qty</th>';
						echo '</tr>';
						echo '<tr>';
							// echo '<th>&nbsp;</th>';
							echo '<th>SO#</th>';
							echo '<th >Ordered Item</th>';
							echo '<th >Internal Item</th>';
							echo '<th >Qty</th>';
							echo '<th >Length</th>';
							echo '<th >Width</th>';
							echo '<th >Material Code</th>';

							echo '<th >Qty</th>';
							echo '<th >Ink Code</th>';

							echo '<th >Qty</th>';
						echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
					$index = 1;
					// print_r($print_data);
					foreach ($print_data as $item) {
						echo '<tr>';
							echo '<td class="order">'. $index .'</td>';
							echo '<td class="so-line">'. $item['so_line'] .'</td>';
							echo '<td class="ordered-item">'. $item['ordered_item'] .'</td>';
							echo '<td class="internal-item">'. $item['internal_item'] .'</td>';
							echo '<td class="qty">'. number_format($item['qty_of_line']) .'</td>';
							
							echo '<td class="length-width">'. $item['length'] .'</td>';
							echo '<td class="length-width">'. $item['width'] .'</td>';
							
							echo '<td class="material-code">'. $item['material_code'] .'</td>';
							echo '<td class="material-qty">'. number_format($item['material_qty']) .'</td>';
							echo '<td class="ink-code">'. $item['ink_code'] .'</td>';
							echo '<td class="ink-qty">'. number_format($item['ink_qty']) .'</td>';
						echo '</tr>';
					} // for end
					echo '<tr>';
						echo '<td colspan="7" class="border-hiden"><div class="qty-total-detail">Qui cách đóng gói/ packaging</div><div class="qty-total">' . number_format($print_data[0]['qty_total']) .'</div><div class="qty_total_unit">pcs</div></td>';
						echo '<td colspan="2" class="border-hiden" ><div class="material-des">Description:&nbsp; '. $print_data[0]['material_des'] .'</div></td>';
						echo '<td colspan="2" class="border-hiden" ><div class="ink-des">Description: &nbsp; '. $print_data[0]['ink_des'] .'</div></td>';
					echo '</tr>';

				echo '</tbody>';
			echo '</table>';

			?>
		</div>
		<!-- ./ main 1 -->
		<!-- main 2 -->
		<hr class="dash-break">
		<?php
			$remark_master_data = isset($print_data[0]['remark_master_data']) ? $print_data[0]['remark_master_data'] : '';
			$remark_master_data_1 = $remark_master_data_2 = $remark_master_data_3 = '';
			if ( !empty($remark_master_data) && strpos($remark_master_data, ';') !== false ) {
				$remark_master_data_arr = explode(';', $remark_master_data);
				$remark_master_data_1 = isset($remark_master_data_arr[0]) ? $remark_master_data_arr[0] : '';
				$remark_master_data_2 = isset($remark_master_data_arr[1]) ? $remark_master_data_arr[1] : '';
				$remark_master_data_3 = isset($remark_master_data_arr[2]) ? $remark_master_data_arr[2] : '';
			}
			if (!empty($remark_master_data)) {
				echo '<div id="main-box-2">';
					echo '<div id="content-main-2-left" class="content-main-2">';
						echo $remark_master_data_1;
					echo '</div>';
					echo '<div id="content-main-2-left" class="content-main-2">';
						echo $remark_master_data_2;
					echo '</div>';
					echo '<div id="content-main-2-left" class="content-main-2">';
						echo $remark_master_data_3;
					echo '</div>';
				echo '</div>';
			}
			
		?>
		
		
		<!-- ./ main 2 -->
		<!-- bottom -->
		<div id="bottom-box">
			<table>
				<tbody>
					<tr>
						<th rowspan="4" style="width:15.5%;">&nbsp;Trace <br />Ability</th>
						<th>Loại</th>
						<th>Màu</th>
						<th>PO#</th>
						<th>Lot#</th>
						<th>Ghi chú</th>
					</tr>
					<tr>
						<!-- <td >Trace <br />Ability</td>	 -->
						<td style="text-align: center;">Vải</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<!-- <td rowspan="4">Trace <br />Ability</td>	 -->
						<td style="text-align: center;">Giấy</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<!-- <td rowspan="4">Trace <br />Ability</td>	 -->
						<td style="text-align: center;">Mực</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>
				</tbody>
			</table>
		</div>
		<hr class="dash-break">
		<div id=bottom-box-2>
			<?php
				$remarkCount = 0;
				echo '<table class="border-hiden">';
					echo '<tbody>';
						// packing instruction
						if (isset($print_data[0]['packing_instr']) && !empty($print_data[0]['packing_instr']) ) {
							echo '<tr>';
								echo '<td class="border-hiden" style="width:7%;">Packing instr  </td>';
								echo '<td class="border-hiden packing-instr">: &nbsp;' .$print_data[0]['packing_instr']. '</td>';
							echo '</tr>';
						}
						// remark tool
						if (isset($print_data[0]['remark_tool']) && !empty($print_data[0]['remark_tool']) ) {
							$remarkCount++;
							echo '<tr>';
								echo '<td class="border-hiden" style="width:7%;">Remark '. $remarkCount .'</td>';
								echo '<td class="border-hiden packing-instr">: &nbsp;' .$print_data[0]['remark_tool']. '</td>';
							echo '</tr>';
						}
						// remark program
						if (isset($print_data[0]['remark_program']) && !empty($print_data[0]['remark_program']) ) {
							$remarkCount++;
							echo '<tr>';
								echo '<td class="border-hiden" style="width:7%;">Remark '. $remarkCount .'</td>';
								echo '<td class="border-hiden packing-instr">: &nbsp;' .$print_data[0]['remark_program']. '</td>';
							echo '</tr>';
						}
						

					echo '<tbody>';
				echo '</table>';
			?>
		
		</div>
		<!-- ./ bottom -->

	</div>

</body>

</html>