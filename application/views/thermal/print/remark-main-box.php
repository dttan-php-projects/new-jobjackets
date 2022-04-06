<?php

	if (count($remark_main_print) == 1 ) {
		$htmls .= '<table class="">';
			$htmls .= '<tbody>';
				$htmls .= '<tr >';
					$htmls .= '<td colspan=2 class="no-header remark-main-box" >' . $remark_main_print[0]['remark'] . '</td>';
				$htmls .= '</tr>';
			$htmls .= '</tbody>';
		$htmls .= '</table>';	

	} else {

		// left
		$htmls .= '<div id="remark-main-box-left">';
			$htmls .= '<table class="">';
				$htmls .= '<tbody>';

					foreach ($remark_main_print as $keyR => $remark_item ) {

						$htmls .= '<tr >';
							if ($keyR%2 == 0 ) {
								$htmls .= '<td class="no-header remark-main-box" >' . $remark_item['remark'] . '</td>';
							}
						$htmls .= '</tr>';
					}

				$htmls .= '</tbody>';
			$htmls .= '</table>';							
		$htmls .= '</div>';

		// right 
		$htmls .= '<div id="remark-main-box-right">';
			$htmls .= '<table class="">';
				$htmls .= '<tbody>';
					foreach ($remark_main_print as $keyR => $remark_item ) {

						$htmls .= '<tr >';
							if ($keyR%2 !== 0 ) {
								$htmls .= '<td class="no-header remark-main-box" >' . $remark_item['remark'] . '</td>';
							}
						$htmls .= '</tr>';
					}

				$htmls .= '</tbody>';
			$htmls .= '</table>';							
		$htmls .= '</div>';

	}

	