<?php

	$form_type_label = $others_data['form_type_label'];
	$po_date = $po_data['po_date'];

	$count_line = $po_data['count_lines'];
	$updated_by = $po_data['updated_by'];

	$htmls .= '<div id="print-logo-title">';
		$htmls .= '<img src="../assets/media/images/logo-new-w.png" height="40px" ; width="80px" alt="Smiley face">';
	$htmls .= '</div>';
	$htmls .= '<div id="print-main-title">';
		$htmls .= $form_type_label ;
	$htmls .= '</div>';
