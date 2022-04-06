<?php

	// production line
		$production_line = null != get_cookie('plan_department') ? get_cookie('plan_department') : 'htl'; 

	// check 
		if ($production_line == 'woven' ) {
			include_once ('jsfunction.php');
		} else if ($production_line == 'thermal' ) {
			include_once ('jsThermal.php');
		} else if ($production_line == 'htl' ) {
			include_once ('jsHTL.php');
		}

	// user functions
		include_once ("jsUsers.php");

	// html string
		$html = '<!DOCTYPE html>
				<html>
					<head>
						<!-- meta block -->
						<title> ' . $title. ' </title>
						<meta name="description" content="Planning">
						<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
						<link rel="icon" href="' . base_url("assets/media/images/Logo.ico"). '" type="image/x-icon">
						<link rel="stylesheet" href="' . base_url("assets/Suite_v52/skins/skyblue/dhtmlx.css") . '">
						<link rel="stylesheet" href="' . base_url("assets/css/woven.css") . '">
						<link rel="stylesheet" href="' . base_url("assets/font-awesome/css/font-awesome.min.css") . '">
						<script type="text/javascript" src="' . base_url("assets/Suite_v52/codebase/dhtmlx.js") . '"></script>
						<script type="text/javascript" src="' . base_url("assets/js/jquery.min.js") . '"></script>
					</head>
		';
		$html .= '
				<body>
				<div style="height:40px; position:relative; text-align:center; background-color:#e2efff;" >
					<div id="mainMenu"> </div>
				</div>
				<div style="position:absolute; top:30; width:100%; background-color:#e2efff; " > 
					<div id="mainToolbar"> </div>
				</div>	

				<script>
					doOnLoad();
				</script>
					
		';

		$html .= '</body></html>';

	// render 
		echo "\n";
		echo $html;

?>