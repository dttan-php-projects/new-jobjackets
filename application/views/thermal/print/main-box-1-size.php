<?php
	
	$index = 0;

	if ($count >=2 ) {
		$htmls .= '<table>';
			$htmls .= '<thead>';
				$htmls .= '<tr>';
					$htmls .= '<th rowspan=2>No</th>';
					$htmls .= '<th colspan=6 >Order Details</th>';
					$htmls .= '<th colspan=2>Size Label </th>';
					$htmls .= '<th colspan=4>Material Details</th>';
					$htmls .= '<th colspan=3>Ink Details </th>';
					$htmls .= '<th rowspan=2 style="width:25px;">Machine</th>';
				$htmls .= '</tr>';

				$htmls .= '<tr>';
					$htmls .= '<th>SO#</th>';
					$htmls .= '<th >Ordered Item </th>';
					$htmls .= '<th >Internal Item</th>';
					$htmls .= '<th >Item Desc</th>';
					$htmls .= '<th >Size </th>';
					$htmls .= '<th >Qty</th>';
					$htmls .= '<th >Length</th>';
					$htmls .= '<th >Width</th>';
					$htmls .= '<th >Material Code</th>';
					$htmls .= '<th >Desc</th>';
					$htmls .= '<th >Qty</th>';
					$htmls .= '<th >UOM</th>';

					$htmls .= '<th >Ink Code</th>';
					$htmls .= '<th >Desc</th>';
					$htmls .= '<th >Qty</th>';

				$htmls .= '</tr>';

			$htmls .= '</thead>';

			$htmls .= '<tbody>';

				foreach ($so_line_print as $key => $soline_item ) {
						
					$index++;

					$htmls .= '<tr style="height:25px;">';
						$htmls .= '<td class="data-details">'. $index .'</td>';
						
						$htmls .= '<td class="data-details so-line" >'. $soline_item['so_line'] .'</td>';
						$htmls .= '<td class="data-details ordered-item" >'. $soline_item['ordered_item'] .'</td>';
						$htmls .= '<td class="data-details internal-item">'. $soline_item['internal_item'] .'</td>';
						$htmls .= '<td class="data-details des">'. $soline_item['internal_item_desc'] .'</td>';
						$htmls .= '<td class="data-details">'. $soline_item['size'] .'</td>';
						$htmls .= '<td class="data-details">'. number_format($soline_item['qty']) .'</td>';
						
						$htmls .= '<td class="data-details">'. $soline_item['length'] .'</td>';
						$htmls .= '<td class="data-details">'. $soline_item['width'] .'</td>';

						$htmls .= '<td class="data-details material-code" >'. $soline_item['material_code'] .'</td>';
						$htmls .= '<td class="data-details desc" >'. $soline_item['material_desc'] .'</td>';
						$htmls .= '<td class="data-details">'. number_format($soline_item['material_qty']) .'</td>';
						$htmls .= '<td class="data-details">'. $soline_item['material_uom'] .'</td>';

						$htmls .= '<td class="data-details ink-code" >'. $soline_item['ink_code'] .'</td>';
						$htmls .= '<td class="data-details desc" >'. $soline_item['ink_desc'] .'</td>';
						$htmls .= '<td class="data-details">'. number_format($soline_item['ink_qty']) .'</td>';

						$htmls .= '<td class="data-details">'. $soline_item['machine'] .'</td>';

					$htmls .= '</tr>';

				}

			$htmls .= '</tbody>';
		$htmls .= '</table>';

		// qui cach dong goi/ descriptions
		$htmls .= '<div id="qty-total-box">';

			$htmls .= '<table>';

				$htmls .= '<tbody>';
					$htmls .= '<tr style="height:25px;">';
						$htmls .= '<td class="border-hiden data-details" style="width:200px;height:20px;">QUI CÁCH ĐÓNG GÓI/PACKING</td>';
						$htmls .= '<td class="border-hiden data-details" style="width:50px;">&nbsp;</td>';
						$htmls .= '<td class="border-hiden data-details">&nbsp;</td>';
					$htmls .= '</tr>';

					$htmls .= '<tr style="height:25px;">';
						$htmls .= '<td class="data-details" style="font-size:20px;background-color:yellow;height:30px;">'. number_format($qty_total) .'</td>';
						$htmls .= '<td class="border-hiden data-details" style="text-align:left;background-color:white;">'. $unit .'</td>';
						$htmls .= '<td class="border-hiden data-details" style="background-color:white;" >&nbsp;</td>';
						
					$htmls .= '</tr>';

				$htmls .= '</tbody>';
			$htmls .= '</table>';

		$htmls .= '</div>';	

	} else { // 1 line

		$htmls .= '<table>';
			$htmls .= '<thead>';
				$htmls .= '<tr>';
					$htmls .= '<th rowspan=2>No</th>';
					$htmls .= '<th colspan=6 >Order Details</th>';
					$htmls .= '<th colspan=2>Size Label </th>';
					$htmls .= '<th colspan=3>Material Details</th>';
					$htmls .= '<th colspan=2>Ink Details </th>';
					$htmls .= '<th rowspan=2 style="width:25px;">Machine</th>';
				$htmls .= '</tr>';

				$htmls .= '<tr>';
					$htmls .= '<th>SO#</th>';
					$htmls .= '<th >Ordered Item </th>';
					$htmls .= '<th >Internal Item</th>';
					$htmls .= '<th >Item Desc</th>';
					$htmls .= '<th >Size </th>';
					$htmls .= '<th >Qty</th>';
					$htmls .= '<th >Length</th>';
					$htmls .= '<th >Width</th>';
					$htmls .= '<th >Material Code</th>';
					$htmls .= '<th >Qty</th>';
					$htmls .= '<th >UOM</th>';

					$htmls .= '<th >Ink Code</th>';
					$htmls .= '<th >Qty</th>';

				$htmls .= '</tr>';

			$htmls .= '</thead>';

			$htmls .= '<tbody>';

				foreach ($so_line_print as $key => $soline_item ) {
							
					$index++;

					$htmls .= '<tr style="height:25px;">';
						$htmls .= '<td class="data-details">'. $index .'</td>';
						
						$htmls .= '<td class="data-details so-line" >'. $soline_item['so_line'] .'</td>';
						$htmls .= '<td class="data-details ordered-item" >'. $soline_item['ordered_item'] .'</td>';
						$htmls .= '<td class="data-details internal-item" >'. $soline_item['internal_item'] .'</td>';
						$htmls .= '<td class="data-details desc" >'. $soline_item['internal_item_desc'] .'</td>';
						$htmls .= '<td class="data-details">'. $soline_item['size'] .'</td>';
						$htmls .= '<td class="data-details">'. number_format($soline_item['qty']) .'</td>';
						
						$htmls .= '<td class="data-details">'. $soline_item['length'] .'</td>';
						$htmls .= '<td class="data-details">'. $soline_item['width'] .'</td>';

						$htmls .= '<td class="data-details material-code" >'. $soline_item['material_code'] .'</td>';
						$htmls .= '<td class="data-details">'. number_format($soline_item['material_qty']) .'</td>';
						$htmls .= '<td class="data-details">'. $soline_item['material_uom'] .'</td>';

						$htmls .= '<td class="data-details ink-code " >'. $soline_item['ink_code'] .'</td>';
						$htmls .= '<td class="data-details">'. number_format($soline_item['ink_qty']) .'</td>';

						$htmls .= '<td class="data-details">'. $soline_item['machine'] .'</td>';

					$htmls .= '</tr>';

				}

			$htmls .= '</tbody>';
		$htmls .= '</table>';

		// qui cach dong goi/ descriptions
		$htmls .= '<div id="qty-total-box">';

			$htmls .= '<table>';

				$htmls .= '<tbody>';
					$htmls .= '<tr style="height:25px;">';
						$htmls .= '<td class="border-hiden data-details" style="width:200px;height:25px;">QUI CÁCH ĐÓNG GÓI/PACKING</td>';
						$htmls .= '<td class="border-hiden data-details" style="width:50px;">&nbsp;</td>';
						$htmls .= '<td class="border-hiden data-details" style="width:120px;font-size:13px;text-align:left;">Material Desc</td>';
						$htmls .= '<td class="border-hiden data-details" style="font-size:13px;text-align:left;">'. $so_line_print[0]['material_desc'] .'</td>';
					$htmls .= '</tr>';

					$htmls .= '<tr style="height:25px;">';
						$htmls .= '<td class=" data-details" style="font-size:20px;background-color:yellow;height:30px;">'. number_format($qty_total) .'</td>';
						$htmls .= '<td class="border-hiden data-details" style="text-align:left;background-color:white;font-size:12px;">'. $unit .'</td>';
						$htmls .= '<td class="border-hiden data-details" style="font-size:13px;text-align:left;">Ink Desc</td>';
						$htmls .= '<td class="border-hiden data-details" style="font-size:13px;text-align:left;">'. $so_line_print[0]['ink_desc'] .'</td>';
					$htmls .= '</tr>';

				$htmls .= '</tbody>';
			$htmls .= '</table>';

		$htmls .= '</div>';	

	}

	

