<?php

	$name = $po_data['name'];

	$TQCheckArr[] = array( 'Planning/Kế hoạch', '', $po_date, $name );
	$TQCheckArr[] = array( 'Prepress/Thiết kế', '', '', '' );
	$TQCheckArr[] = array( 'Sreen room/Phòng Khung', '', '', '' );
	$TQCheckArr[] = array( 'Ink room/Phòng Mực', '', '', '' );
	$TQCheckArr[] = array( 'Mat. Handing/Giao Vật tư', '', '', '' );


	$TQCheckArr2[] = array( 'Operator Vận hành', '', '', '' );
	$TQCheckArr2[] = array( 'Tr. ca/G. sát', '', '', '' );
	$TQCheckArr2[] = array( 'Số tờ QC nhận', '', '', '' );
	$TQCheckArr2[] = array( 'Số lượng QC kiểm', '', '', '' );
	$TQCheckArr2[] = array( '', '', '', '' );


	$htmls .= '<div id="tq-check-box-1">';

		$htmls .= '<table>';
			$htmls .= '<thead>';
				$htmls .= '<tr>';
					$htmls .= '<th >Công đoạn</th>';
					$htmls .= '<th >Số lượng</th>';
					$htmls .= '<th >Ngày</th>';
					$htmls .= '<th >Ký tên</th>';
				$htmls .= '</tr>';

			$htmls .= '</thead>';

			$htmls .= '<tbody>';
				$index = 0;
				foreach ($TQCheckArr as $TQ ) {

					$index++;

					$htmls .= '<tr style="height:18px;font-size:10px;">';
						$htmls .= '<td class="data-details">' . $TQ[0] . ' </td>';
						$htmls .= '<td class="data-details">' . $TQ[1] . ' </td>';
						$htmls .= '<td class="data-details">' . $TQ[2] . ' </td>';
						$htmls .= '<td class="data-details">' . $TQ[3] . ' </td>';
						
					$htmls .= '</tr>';
				}
				

			$htmls .= '</tbody>';
		$htmls .= '</table>';

	$htmls .= '</div>';


	$htmls .= '<div id="tq-check-box-2">';

		$htmls .= '<table>';
			$htmls .= '<thead>';
				$htmls .= '<tr>';
					$htmls .= '<th >Công đoạn</th>';
					$htmls .= '<th >Số lượng</th>';
					$htmls .= '<th >Ngày</th>';
					$htmls .= '<th >Ký tên</th>';
				$htmls .= '</tr>';

			$htmls .= '</thead>';

			$htmls .= '<tbody>';
				foreach ($TQCheckArr2 as $TQ ) {

					$index++;

					$htmls .= '<tr style="height:18px;font-size:10px;">';
						$htmls .= '<td class="data-details">' . $TQ[0] . ' </td>';
						$htmls .= '<td class="data-details">' . $TQ[1] . ' </td>';
						$htmls .= '<td class="data-details">' . $TQ[2] . ' </td>';
						$htmls .= '<td class="data-details">' . $TQ[3] . ' </td>';
						
					$htmls .= '</tr>';
				}
				

			$htmls .= '</tbody>';
		$htmls .= '</table>';

	$htmls .= '</div>';



	