<?php


	$po_no = $po_data['po_no'];
	$po_no_suffix = $po_data['po_no_suffix'];
	$po_no_show = (stripos($po_no_suffix, 'normal') !== false ) ? $po_no :  ($po_no . "-" . $po_no_suffix);
	$po_no_barcode = $others_data['po_no_barcode'];
	$machine = $po_data['machine'];
	$machine_speed = $others_data['machine_speed'];
	$machine_unit = $others_data['machine_unit'];
	
	$qty_total = $po_data['qty_total'];
	$customer_item = $po_data['customer_item'];

	$rbo = $po_data['rbo'];
	$ship_to_customer = $po_data['ship_to_customer'];
	$promise_date = $po_data['promise_date'];
	$request_date = $po_data['request_date'];

	$material_code = $po_data['material_code'];
	$material_name = $po_data['material_name'];
	$material_label = $po_data['material_width'] . ' x ' . $po_data['material_length'];

	$product_type = $po_data['product_type'];

	// remark top: plan type & remark được user remark trực tiếp lúc làm lệnh sx.
	$remark_top = !empty($po_data['plan_type']) ? ("- " .$po_data['plan_type']) : '';
	$remark_top .= !empty($po_data['po_remark_1']) ? ("<br>- " . $po_data['po_remark_1']) : '';
	$remark_top .= !empty($po_data['po_remark_2']) ? ("<br>- " . $po_data['po_remark_2']) : '';


	$htmls .= '<table style="width:100%;">';
		$htmls .= '<tbody>';

			$htmls .= '<tr  >';
				$htmls .= '<td colspan=3 class="no-header barcode" >' . $po_no_barcode . '</td>';
				$htmls .= '<td colspan=2 class="no-header remark-top">' . $remark_top . '</td>';
			$htmls .= '</tr>';

			$htmls .= '<tr >';
				$htmls .= '<td colspan=3 class="no-header po-no" >' . $po_no_show . '</td>';
				$htmls .= '<td class="no-header "> Máy: </td>';
				$htmls .= '<td class="no-header content">' . $machine . '</td>';
			$htmls .= '</tr>';

			$htmls .= '<tr >';
				$htmls .= '<td class="no-header" style="width:110px;" >Ngày làm lệnh: </td>';
				$htmls .= '<td colspan=2 class="no-header content">' . $po_date . '</td>';
				$htmls .= '<td class="no-header " style="width:110px;">Tốc độ ('.$machine_unit.'): </td>';
				$htmls .= '<td class="no-header content" style="width:150px;">' . $machine_speed . '</td>';
			$htmls .= '</tr>';

			$htmls .= '<tr >';
				$htmls .= '<td class="no-header" >PD/CRD: </td>';
				$htmls .= '<td colspan=2 class="no-header content highlights">' . $promise_date . ' / ' . $request_date . '</td>';
				$htmls .= '<td class="no-header ">Mã vật tư: </td>';
				$htmls .= '<td class="no-header content " style="font-size:12px;">' . $material_code . '</td>';
			$htmls .= '</tr>';

			$htmls .= '<tr >';
				$htmls .= '<td class="no-header" >Tổng số lượng: </td>';
				$htmls .= '<td colspan=2 class="no-header content">' . number_format($qty_total) . '</td>';
				$htmls .= '<td class="no-header ">Tên Vật tư: </td>';
				$htmls .= '<td class="no-header content">' . $material_name . '</td>';
			$htmls .= '</tr>';

			$htmls .= '<tr >';
				$htmls .= '<td class="no-header" >Số SO#: </td>';
				$htmls .= '<td colspan=2 class="no-header content">' . $count . '</td>';
				$htmls .= '<td class="no-header ">Kích thước VT: </td>';
				$htmls .= '<td class="no-header content">' . $material_label . '</td>';
			$htmls .= '</tr>';

			$htmls .= '<tr >';
				$htmls .= '<td class="no-header" >RBO: </td>';
				$htmls .= '<td colspan=2 class="no-header content">' . $rbo . '</td>';
				$htmls .= '<td class="no-header ">Thể loại in: </td>';
				$htmls .= '<td class="no-header content">' . strtoupper($product_type) . '</td>';
			$htmls .= '</tr>';

			if ($this->form_type == 'htl' ) {
				$htmls .= '<tr >';
					$htmls .= '<td class="no-header" >Ship to: </td>';
					$htmls .= '<td colspan=4 class="no-header content">' . $ship_to_customer . '</td>';
				$htmls .= '</tr>';
			} else if ($this->form_type == 'hfe' ) {
				$pattern_data_0 = $pattern_data[0];
				$label_size = $pattern_data_0['label_size'] . 'mm';
				$pattern_no = $pattern_data_0['pattern_no'];
				$pattern_info = "$label_size No: $pattern_no";

				$htmls .= '<tr >';
					$htmls .= '<td class="no-header" >Ship to: </td>';
					$htmls .= '<td colspan=2 class="no-header content">' . $ship_to_customer . '</td>';
					$htmls .= '<td class="no-header ">Khuôn bế: </td>';
				$htmls .= '<td class="no-header content">' . $pattern_info. '</td>';
				$htmls .= '</tr>';
			}
			

		$htmls .= '</tbody>';
	$htmls .= '</table>';
