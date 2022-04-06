<?php
	$htmls .= '<table>';

		$htmls .= '<tbody>';
			$htmls .= '<tr style="height:25px;">';
				$htmls .= '<td class="data-details">QUI CÁCH ĐÓNG GÓI/PACKING</td>';
				$htmls .= '<td class="data-details" style="width:80px;font-size:13px;">'. $so_line .'</td>';
				$htmls .= '<td class="data-details" style="width:120px;font-size:13px;">'. $ordered_item .'</td>';
			$htmls .= '</tr>';

			$htmls .= '<tr style="height:25px;">';
				$htmls .= '<td class="data-details">'. $index .'</td>';
				$htmls .= '<td class="data-details" style="width:80px;font-size:13px;">'. $so_line .'</td>';
				$htmls .= '<td class="data-details" style="width:120px;font-size:13px;">'. $ordered_item .'</td>';
			$htmls .= '</tr>';

		$htmls .= '</tbody>';
	$htmls .= '</table>';