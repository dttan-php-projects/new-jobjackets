<?php
	
	if (empty($results) || !isset($results)) { echo "Không lấy được thông tin đơn hàng"; die(); }
	
	$po_no_print = $results['po_no_print'];
	$so_line_print = $results['so_line_print'];
	$remark_top_print = $results['remark_top_print'];
	$remark_main_print = $results['remark_main_print'];

	$cbs = $po_no_print['cbs'];

	$qty_total = $po_no_print['qty_total'];
	$count = count($so_line_print);
	$unit = 'PCS';

	$packing_instr = (isset($remark_main_print[0]['packing_instr'])) ? $remark_main_print[0]['packing_instr'] : '';

	// print_r($so_line_print);
	
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
			$htmls .= '<link rel="stylesheet" href="'. base_url("assets/css/print/thermal_printHorizontal.css") . '">';
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

						$htmls .= '<div id="header-details-box">';

							// order details top box
							$htmls .= '<div id="order-details-top-box">';
								
								include_once ("order-details-top-box.php");

							$htmls .= '</div>';

							// remark top
							$htmls .= '<div id="remarks-top-box">';
								
								include_once ("remarks-top-box.php");

							$htmls .= '</div>';

						$htmls .= '</div>';
						

					$htmls .= '</div>';
					// main box --------------------------------------------------------------------------------------------------------------
					$htmls .= '<div id="main-box">';
						// order details 2 
						$htmls .= '<div id="main-box-1">';
							if ($cbs == 1 ) {
								include_once ("main-box-1-size.php");
							} else {
								include_once ("main-box-1.php");
							}
							

						$htmls .= '</div>';

						// remark
							$htmls .= '<div id="remark-main-box">';
								
								include_once ("remark-main-box.php");

							$htmls .= '</div>';

					$htmls .= '</div>';

					// footer box --------------------------------------------------------------------------------------------------------------
					$htmls .= '<div id="footer-box">';
						if (!empty($packing_instr) ) {
							// packing instructions
							$htmls .= '<div id="footer-box-1">';
									$htmls .= '<span style="font-size:12px;font-weight:bold;">PACKING INSTR: </span>';
									$htmls .= $packing_instr ;
							$htmls .= '</div>';	
						}

						// Trace Ability
						$htmls .= '<div id="footer-box-2">';
							$htmls .= '<div id="printing-by-box">';

								include_once ("printing-by-box.php");
								
							$htmls .= '</div>';	
							
							$htmls .= '<div id="trace-ability-box">';

								include_once ("trace-ability-box.php");

							$htmls .= '</div>';	
							
						$htmls .= '</div>';	

					$htmls .= '</div>';

				$htmls .= '</div>';
			}

		/* ---------------------------------------------------------------------------------------------------------------------------------------- */
		$htmls .= '</body>';
	$htmls .= '</html>';


	// results
		echo $htmls; exit();

	
