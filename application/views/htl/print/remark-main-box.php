<?php

	if (count($remark_main_data) == 1 ) {

		$remark = str_replace(";", "<br>- ", $remark_main_data[0]['remark']);
		if ($remark !== '-' ) {
			$htmls .= '<table class="">';
				$htmls .= '<tbody>';
					$htmls .= '<tr >';
						$htmls .= '<td colspan=2 class="no-header remark-main-box" >- ' . $remark . '</td>';
					$htmls .= '</tr>';
				$htmls .= '</tbody>';
			$htmls .= '</table>';	
		}
		

	} else {

		// left
		$htmls .= '<div id="remark-main-box-left">';
			$htmls .= '<table class="">';
				$htmls .= '<tbody>';
					
					foreach ($remark_main_data as $keyR => $remark_item ) {

						$remark = str_replace(";", "<br>- ", $remark_item['remark']);
						if ($remark !== '-' ) {
							$htmls .= '<tr >';
								if ($keyR%2 == 0 ) {
									$htmls .= '<td class="no-header remark-main-box" >- ' . $remark . '</td>';
								}
							$htmls .= '</tr>';
						}
						
					}

				$htmls .= '</tbody>';
			$htmls .= '</table>';							
		$htmls .= '</div>';

		// right 
		$htmls .= '<div id="remark-main-box-right">';
			$htmls .= '<table class="">';
				$htmls .= '<tbody>';
					foreach ($remark_main_data as $keyR => $remark_item ) {
						
						$remark = str_replace(";", "<br>- ", $remark_item['remark']);
						if ($remark !== '-' ) {
							$htmls .= '<tr >';
								if ($keyR%2 !== 0 ) {
									$htmls .= '<td class="no-header remark-main-box" >- ' . $remark . '</td>';
								}
							$htmls .= '</tr>';
						}
						
					}

				$htmls .= '</tbody>';
			$htmls .= '</table>';							
		$htmls .= '</div>';

	}

	