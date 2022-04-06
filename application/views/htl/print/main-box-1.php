<?php

	$index = 0;

	if ($count <=10 ) {
		$htmls .= '<table>';
			$htmls .= '<thead>';
				$htmls .= '<tr>';
					$htmls .= '<th>Số</th>';
					$htmls .= '<th>Đơn hàng</th>';
					$htmls .= '<th>Số lượng</th>';
					$htmls .= '<th>Đơn vị</th>';
					$htmls .= '<th>Mã hệ thống</th>';
					$htmls .= '<th>RBO</th>';
					$htmls .= '<th>Ghi chú</th>';
				$htmls .= '</tr>';

			$htmls .= '</thead>';

			$htmls .= '<tbody>';

				foreach ($soline_data as $key => $value ) {
					
					$index++;

					$remark_1 = !empty($value['remark_1']) ? ($value['remark_1'] . '. ') : '';
					$remark_2 = !empty($value['remark_2']) ? ($value['remark_2'] . '. ') : '';
					$remark_3 = !empty($value['remark_3']) ? ($value['remark_3'] . '. ') : '';
					$remark = $remark_1 . $remark_2 . $remark_3;

					$htmls .= '<tr style="height: 18px;">';
						$htmls .= '<td class="data-details index">'. $index .'</td>';
						
						$htmls .= '<td class="data-details so-line" >'. $value['so_line'] .'</td>';
						$htmls .= '<td class="data-details qty-of-line">'. number_format($value['qty_of_line']) .'</td>';
						$htmls .= '<td class="data-details uom-cost" >'. $value['uom_cost'] .'</td>';
						$htmls .= '<td class="data-details internal-item" >'. $value['internal_item'] .'</td>';
						$htmls .= '<td class="data-details">'. $value['rbo'] .'</td>';
						
						$htmls .= '<td class="data-details">'. $remark .'</td>';

					$htmls .= '</tr>';

				}

			$htmls .= '</tbody>';
		$htmls .= '</table>';

		$htmls .= '<hr class="dash-break">';

		// label size
		include_once ("label-size.php");



	} else { // more than 10 lines

		$htmls .= '<table>';
			$htmls .= '<thead>';
				$htmls .= '<tr>';
					$htmls .= '<th>Số</th>';
					$htmls .= '<th>Đơn hàng</th>';
					$htmls .= '<th>Số lượng</th>';
					$htmls .= '<th>Đơn vị</th>';
					$htmls .= '<th>Mã hệ thống</th>';
					$htmls .= '<th>RBO</th>';
					$htmls .= '<th>Ghi chú</th>';
				$htmls .= '</tr>';

			$htmls .= '</thead>';

			$htmls .= '<tbody>';

					$remark_1 = !empty($po_data['remark_1']) ? ($po_data['remark_1'] . '. ') : '';
					$remark_2 = !empty($po_data['remark_2']) ? ($po_data['remark_2'] . '. ') : '';
					$remark = $remark_1 . $remark_2;

					$htmls .= '<tr style="height:25px;">';
						$htmls .= '<td class="data-details index">1</td>';
						
						$htmls .= '<td class="data-details so-line" >Có '. $count .' SO#</td>';
						$htmls .= '<td class="data-details qty-of-line">'. number_format($qty_total) .'</td>';
						$htmls .= '<td class="data-details uom-cost" >'. $po_data['uom_cost'] .'</td>';
						$htmls .= '<td class="data-details internal-item" >'. $po_data['internal_item'] .'</td>';
						$htmls .= '<td class="data-details">'. $po_data['rbo'] .'</td>';
						
						$htmls .= '<td class="data-details">'. $remark .'</td>';

					$htmls .= '</tr>';


			$htmls .= '</tbody>';
		$htmls .= '</table>';

		$htmls .= '<hr class="dash-break">';

		// label size
		include_once ("label-size.php");

	}

	

