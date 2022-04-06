<?php
	
	if (empty($results) || !isset($results)) { echo "Không lấy được thông tin đơn hàng"; die(); }

	// print_r($results); exit();
	
	$po_data = $results['po_data'];
	$soline_data = $results['soline_data'];
	$process_data = $results['process_data'];
	$setting_process_data = $results['setting_process_data'];
	$pattern_data = $results['pattern_data'];
	$remark_main_data = $results['remark_main_data'];
	$others_data = $results['others_data'];

	$po_date = $po_data['po_date'];
	$qty_total = $po_data['qty_total'];
	$setup_sheet_total = $po_data['setup_sheet_total'];
	$process_pass_total = $po_data['process_pass_total'];
	$setup_time_total = $po_data['setup_time_total'];
	$count = count($soline_data);

	$packing_instructions = (isset($remark_main_data[0]['packing_instr'])) ? $remark_main_data[0]['packing_instr'] : '';
	
	$htmls = '';
	$htmls .= '<!DOCTYPE html>';
	$htmls .= '<html>';
		$htmls .= '<head>';
			$htmls .= '<meta charset="utf-8">';
			$htmls .= '<meta name="google" content="notranslate" />';
			$htmls .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
			$htmls .= '<link rel="icon" href="'.base_url('assets/media/images/Logo.ico').'" type="image/x-icon">';
			$htmls .= '<title>Printer Orders</title>';
			// <!-- Font Awesome -->
			$htmls .= '<link rel="stylesheet" href="'. base_url("assets/font-awesome/css/font-awesome.min.css") . '">';
			$htmls .= '<link rel="stylesheet" href="'. base_url("assets/css/print/htl_printVertical.css") . '">';
			$htmls .= '<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">';
			$htmls .= '<script src="' . base_url("assets/js/jquery.min.js") . '" ></script>';

			$htmls .= '<script type="text/javascript">';
			$htmls .= 'window.onload = function() {
							window.print();
							setTimeout(
								function() { window.close();},
								1000
							);
						};';
			$htmls .= "try {
							const po = new PerformanceObserver((list) => {
								for (const entry of list.getEntries()) {
									console.log('Server Timing', entry.serverTiming);
								}
							});
							po.observe({type: 'navigation', buffered: true});
						} catch (e) {
							// Do nothing if the browser doesn't support this API.
						}";
			$htmls .= '</script>';
		$htmls .= '</head>';
		
		$htmls .= '<body>';
		/* ---------------------------------------------------------------------------------------------------------------------------------------- */
			// check status
			if ($results['status'] == false ) {
					$htmls .= $results['message'];
			} else {

				$htmls .= '<div id="container-box">';
					// header box --------------------------------------------------------------------------------------------------------------
					$htmls .= '<div id="header-box">';
						// title
						$htmls .= '<div id="print-title-box">';
							
							include_once ("title-box.php");

						$htmls .= '</div>';

						// order details top box
						$htmls .= '<div id="order-details-top-box">';
							
							include_once ("order-details-top-box.php");

						$htmls .= '</div>';
						

					$htmls .= '</div>';

					// $htmls .= '<hr class="dash-break">';
					
					// main box --------------------------------------------------------------------------------------------------------------
					$htmls .= '<div id="main-box">';
						// soline data & label size
							$htmls .= '<div id="main-box-1">';
								
								include_once ("main-box-1.php");
								
							$htmls .= '</div>';

						$htmls .= '<hr class="dash-break">';

						// Process
							$htmls .= '<div id="main-box-2">';
								
								include_once ("main-box-2.php");
								
							$htmls .= '</div>';

						// sheet & Scrap
							$htmls .= '<div id="main-box-3">';
								
								include_once ("main-box-3.php");
								
							$htmls .= '</div>';

						$htmls .= '<hr class="dash-break">';

						// TQ
							$htmls .= '<div id="tq-check-box">';

								include_once ("tq-check-box.php");
								
							$htmls .= '</div>';

						// remark
							$htmls .= '<div id="remark-main-box">';
								
								include_once ("remark-main-box.php");

							$htmls .= '</div>';

					$htmls .= '</div>';


				$htmls .= '</div>';
			}

		/* ---------------------------------------------------------------------------------------------------------------------------------------- */
		$htmls .= '</body>';
	$htmls .= '</html>';


	// results
		echo $htmls; exit();

	
