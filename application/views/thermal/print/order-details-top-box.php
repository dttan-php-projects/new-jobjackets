<?php

	$po_no = $po_no_print['po_no'];
	$po_no_suffix = $po_no_print['po_no_suffix'];

	$po_no_show = (stripos($po_no_suffix, 'normal') !== false ) ? $po_no :  ($po_no . "-" . $po_no_suffix);
	$so_line_barcode = $po_no_print['so_line_barcode'];
	$rbo = $po_no_print['rbo'];
	$cs = $po_no_print['cs'];
	$so_line_barcode = $po_no_print['so_line_barcode'];
	$ship_to_customer = $po_no_print['ship_to_customer'];
	$ordered_date = $po_no_print['ordered_date'];
	$request_date = $po_no_print['request_date'];
	$promise_date = $po_no_print['promise_date'];


	$htmls .= '<table class="">';
		$htmls .= '<tbody>';

			$htmls .= '<tr >';
				$htmls .= '<td colspan=3 class="no-header po-no" >' . $so_line_barcode . '</td>';
				$htmls .= '<td colspan=4 class="no-header barcode ">' . $rbo . '</td>';
			$htmls .= '</tr>';
			
			$htmls .= '<tr >';
				$htmls .= '<td colspan=3 class="border-hiden header-bold po-no" >' . $po_no_show .'</td>';
				$htmls .= '<td class="border-hiden header-bold" >&nbsp;</td>';
				$htmls .= '<td class="border-hiden header-bold">CS: </td>';
				$htmls .= '<td colspan=2 class="border-hiden header-bold" >' . $cs .'</td>';
				
			$htmls .= '</tr>';

			$htmls .= '<tr >';
				$htmls .= '<td class="border-hiden header-bold" style="width:100px;">Ship To: </td>';
				$htmls .= '<td class="border-hiden header-bold" style="width:100px;" >&nbsp;</td>';
				$htmls .= '<td colspan=2 class="border-hiden header-bold" style="font-size:15px;" >' . $ship_to_customer .'</td>';
				$htmls .= '<td class="border-hiden header-bold">Request Date: </td>';
				$htmls .= '<td colspan=2 class="border-hiden header-bold" >' . $request_date .'</td>';
			$htmls .= '</tr>';

			$htmls .= '<tr >';
				$htmls .= '<td class="border-hiden header-bold" > Ordered Date: </td>';
				$htmls .= '<td class="border-hiden header-bold" >&nbsp;</td>';
				$htmls .= '<td colspan=2 class="border-hiden header-bold" style="width:100px;">' . $ordered_date .'</td>';
				$htmls .= '<td class="border-hiden header-bold " style="width:100px;">Promise Date:</td>';
				$htmls .= '<td colspan=2 class="border-hiden header-bold" style="width:60px;font-size:15px;"> '. $promise_date .' </td>';
				
			$htmls .= '</tr>';
			

		$htmls .= '</tbody>';
	$htmls .= '</table>';
