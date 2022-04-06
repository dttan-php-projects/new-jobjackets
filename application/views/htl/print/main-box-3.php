<?php

	$original_need = number_format($po_data['original_need'],2); // số tờ ban đầu
	$sheet_batching = $po_data['sheet_batching']; // số tờ thiết kế
	$sheet_packing = $po_data['sheet_packing']; // số tờ sau khi đóng gói
	$setup_sheet_total = $po_data['setup_sheet_total']; // số tờ canh chỉnh
	$paper_compensate_total = $po_data['paper_compensate_total']; // số tờ lỗi cho phép
	$sheet_total = $po_data['sheet_total']; // tổng số tờ đơn hàng
	

	$designed_scrap = $po_data['designed_scrap']; // Phế phẩm thiết kế
	$allowance_scrap = $po_data['allowance_scrap']; // Phế phẩm cho phép
	$setup_scrap = $po_data['setup_scrap']; // Phế phẩm canh chỉnh
	$scrap_total = $po_data['scrap_total']; // Tổng số phế phẩm


	$process_pass_total = $po_data['process_pass_total']; // Tổng số lượt in (pass)
	$sheet_pass_total = $po_data['sheet_pass_total']; // Tổng số lượt đơn hàng
	$running_time = $po_data['running_time']; // Số giờ chạy máy
	$color_total = $po_data['color_total']; // Số màu mực

	$htmls .= '<table>';
		$htmls .= '<thead>';
			$htmls .= '<tr>';
				$htmls .= '<th >TT</th>';
				$htmls .= '<th colspan="2">Tổng số tờ/Sheet</th>';
				$htmls .= '<th colspan="2">Tổng Phế phẩm/Scrap (%)</th>';
				$htmls .= '<th colspan="2">Thông số khác</th>';
			$htmls .= '</tr>';

		$htmls .= '</thead>';

		$htmls .= '<tbody>';

			$htmls .= '<tr style="height:18px;">';
				$htmls .= '<td class="data-details-2 index">1. </td>';
				$htmls .= '<td class="data-details-2">Sheet Ban đầu: </td>';
				$htmls .= '<td class="data-details-2">'. $original_need .' </td>';
				$htmls .= '<td class="data-details-2">&nbsp; </td>';
				$htmls .= '<td class="data-details-2">&nbsp; </td>';

				$htmls .= '<td class="data-details-2">Tổng số lượt in: </td>';
				$htmls .= '<td class="data-details-2">'. $process_pass_total .'</td>';
			$htmls .= '</tr>';

			$htmls .= '<tr style="height:18px;">';
				$htmls .= '<td class="data-details-2 index">2. </td>';
				$htmls .= '<td class="data-details-2">Sheet Thiết kế: </td>';
				$htmls .= '<td class="data-details-2">'. $sheet_batching .' </td>';
				$htmls .= '<td class="data-details-2">Scrap Thiết kế: </td>';
				$htmls .= '<td class="data-details-2">'. $designed_scrap .' </td>';

				$htmls .= '<td class="data-details-2">Tổng số lượt: </td>';
				$htmls .= '<td class="data-details-2">'. $sheet_pass_total .' </td>';
			$htmls .= '</tr>';

			$htmls .= '<tr style="height:18px;">';
				$htmls .= '<td class="data-details-2 index">3. </td>';
				$htmls .= '<td class="data-details-2">Sheet Canh chỉnh: </td>';
				$htmls .= '<td class="data-details-2">'. $setup_sheet_total .' </td>';
				$htmls .= '<td class="data-details-2">Scrap Canh chỉnh: </td>';
				$htmls .= '<td class="data-details-2">'. $setup_scrap .' </td>';

				$htmls .= '<td class="data-details-2">Số giờ chạy: </td>';
				$htmls .= '<td class="data-details-2">'. $running_time .' </td>';
			$htmls .= '</tr>';

			$htmls .= '<tr style="height:18px;">';
				$htmls .= '<td class="data-details-2 index">4. </td>';
				$htmls .= '<td class="data-details-2">Sheet Lỗi cho phép: </td>';
				$htmls .= '<td class="data-details-2">'. $paper_compensate_total .' </td>';
				$htmls .= '<td class="data-details-2">Scrap Lỗi cho phép: </td>';
				$htmls .= '<td class="data-details-2">'. $allowance_scrap .' </td>';

				$htmls .= '<td class="data-details-2">Số màu mực: </td>';
				$htmls .= '<td class="data-details-2">'. $color_total .' </td>';
			$htmls .= '</tr>';


			$htmls .= '<tr style="height:18px;">';
				$htmls .= '<td colspan="2" class="data-details-2 highlights-2" >Tổng số tờ đơn hàng: </td>';
				$htmls .= '<td class="data-details-2 highlights-2">'. $sheet_total .' </td>';
				
				$htmls .= '<td class="data-details-2 highlights-2">Tổng Scrap (%): </td>';
				$htmls .= '<td class="data-details-2 highlights-2">'. $scrap_total .' </td>';
				$htmls .= '<td colspan="2" class="data-details-2">&nbsp;</td>';
			$htmls .= '</tr>';


		$htmls .= '</tbody>';
	$htmls .= '</table>';