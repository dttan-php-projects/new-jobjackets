<?php
	// label-size
		$label_size_arr[] = "Kích thước nhãn: ";
		$ups_label_arr[] = "UPS (pcs/tờ): ";
		$ups_total = 0;
		foreach ($pattern_data as $kPa => $valPa ) {
			$ups_total += $valPa['ups'];
			$label_size_arr[] = $valPa['label_size'];
			$ups_label_arr[] = $valPa['ups_label'];
		}

	// add ups total
		$label_size_arr[] = "Tổng UPS";
		$ups_label_arr[] = $ups_total;

	// show
		$id_label = (count($label_size_arr) <= 4) ? 'label-size-box' : 'label-size-box-2';

		$htmls .= '<div id="'.$id_label.'">';

			$htmls .= '<table>';
				
				$htmls .= '<tbody>';
					
					$htmls .= '<tr style="height:18px;">';
						foreach ($label_size_arr as $kLabel => $label ) {
							if ($kLabel == 0 ) {
								$htmls .= '<td class="border-hiden data-details" style="text-align:left;width: 100px;background-color: #59f78d;">' . $label . '</td>';
							} else {
								$htmls .= '<td class="border-hiden data-details">' . $label . '</td>';
							}
							
						}
					$htmls .= '</tr>';

					$htmls .= '<tr style="height:18px;">';
						foreach ($ups_label_arr as $kU => $ups_label ) {
							if ($kU == 0 ) {
								$htmls .= '<td class="border-hiden data-details" style="text-align:left;width: 100px; background-color: #59f78d;">' . $ups_label . '</td>';
							} else {
								$htmls .= '<td class="border-hiden data-details">' . $ups_label . '</td>';
							}
							
						}
					$htmls .= '</tr>';

				$htmls .= '</tbody>';
			$htmls .= '</table>';

		$htmls .= '</div>';	