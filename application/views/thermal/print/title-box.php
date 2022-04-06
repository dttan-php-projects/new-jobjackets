<?php

	$form_type_label = $po_no_print['form_type_label'];
	$po_date = $po_no_print['po_date'];

	$cbs_show = $po_no_print['cbs_show'];
	$count_line = $po_no_print['count_line'];
	$updated_by = $po_no_print['updated_by'];

	$htmls .= '<div id="print-logo-title">';
		$htmls .= '<img src="../../assets/media/images/logo-new-w.png" height="40px" ; width="80px" alt="Smiley face">';
	$htmls .= '</div>';
	$htmls .= '<div id="print-main-title">';
		$htmls .= $form_type_label ;
	$htmls .= '</div>';
	// #print-mid-title
	$htmls .= '<div class="print-mid-title print-mid-title-aqua">';
		$htmls .= 'Ngày LSX: ' . $po_date;
	$htmls .= '</div>';
	
	$htmls .= '<div class="print-mid-title print-mid-title-antiquewhite">';
		$htmls .= $cbs_show;
	$htmls .= '</div>';

	$htmls .= '<div class="print-mid-title print-mid-title-aqua" style="width:11%;">';
		$htmls .= $updated_by;
	$htmls .= '</div>';

	$htmls .= '<div class="print-right-title print-mid-title-antiquewhite">';
		$htmls .= $count_line . ' lines';
	$htmls .= '</div>';
