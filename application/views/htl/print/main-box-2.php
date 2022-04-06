<?php

	$printing_json = json_decode($po_data['printing_json'], true);
	$key_setup_sheet = 0;
	$check_setup_sheet = false;

	$count_passes = count($printing_json) - 1;

	$htmls .= '<table>';
		
		foreach ($printing_json as $key => $printing ) {
			
			$index++;
			$count_printing = count($printing);

			if ($key == 0 ) { // header

				// count item
					$count_item = $count_printing - 7;
				
				$htmls .= '<thead>';

					$htmls .= '<tr>';
						for ($i=0; $i<$count_printing; $i++ ) {
							$printing_show = '';
							if ($printing[$i] == 'PASSES' ) {
								$printing_show = 'Lượt';
							} else if ($printing[$i] == 'FRAME' ) {
								$printing_show = 'Khung';
							} else if ($printing[$i] == 'TIME' ) {
								$printing_show = 'Phút<br>Canh chỉnh';
							} else if ($printing[$i] == 'SHEET' ) {
								$printing_show = 'Tờ<br>Canh chỉnh';
								$key_setup_sheet = $i;
							} else if ($printing[$i] == 'INK USAGE' ) {
								$printing_show = 'Mực<br>(Gram)';
							} else {
								$printing_show = $printing[$i];
							}

							$htmls .= '<th>'. $printing_show .'</th>';	
							
						}
					$htmls .= '</tr>';
				$htmls .= '</thead>';

			} else {
				$htmls .= '<tbody>';

					$htmls .= '<tr>';
						for ($i=0; $i<$count_printing; $i++ ) {
							if ($i == 1 ) {
								$printing_show = '';
								foreach ($setting_process_data as $setting ) {
									if ($setting['process'] == $printing[$i] ) {
										$printing_show = $setting['process_name_vi'];
										break;
									} 
								}
								
							} else {
								// Nếu nội dung hiển thị lần đầu (không tính header), và lấy vị trí thứ i (là vị trí của setup sheet ) ==> setup sheet = setup sheet total. Các lần sau cho bằng 0
								if ( ($key == 1) && ($key_setup_sheet == $i ) ) {
									// $printing_show = $setup_sheet_total;
									$printing_show = $printing[$i];
								} else {
									$printing_show = $printing[$i];
									if ((is_float($printing_show))) $printing_show = number_format($printing_show, 0, '.', ',');
								}								
							}

							$htmls .= '<td class="data-details">'. $printing_show .'</td>';		
						}
					$htmls .= '</tr>';
			}

		}

		// sum total
			$count_merge = $count_item + 2;

			$htmls .= '<tr>';
				$htmls .= '<td colspan='.$count_merge.' class="data-details">Tổng: </td>';	
				$htmls .= '<td class="data-details">' . $process_pass_total . '</td>';
				$htmls .= '<td class="data-details">' . $count_passes . '</td>';
				$htmls .= '<td class="data-details">' . $setup_time_total . '</td>';
				$htmls .= '<td class="data-details">' . $setup_sheet_total . '</td>';	
				$htmls .= '<td class="data-details">&nbsp;</td>';	
			$htmls .= '</tr>';


		$htmls .= '</tbody>';
	$htmls .= '</table>';

	$htmls .= '<hr class="dash-break">';

		