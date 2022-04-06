<?php
	//check login
	if(!isset($_COOKIE["VNRISIntranet"])) {
		header('Location: login.php');//check login
	} else {
		$USER_CUR = $_COOKIE["VNRISIntranet"]; 
	}
	date_default_timezone_set('Asia/Ho_Chi_Minh');
	function formatDate($value){
		return date('d-M-y',strtotime($value));
	} 
	require_once ( "../define_constant_system.php");
	require_once (PATH_MODEL . "/__connection.php");  
	//connect host
	//$host       = "147.121.73.252";
	$db         = "avery_rfid";
	$table      = "no_cbs";
	$table      = $db.".".$table;
	$conn       = getConnection($db); //host 147.121.73.252, db

	//set content type and xml tag
	header("Content-type:text/xml");
	echo "<?xml version=\"1.0\"?>";

	// query model
	$fields = " internal_item,rbo,order_item,material_code,ribbon_code,material_des,ink_des,width,height,pcs_sht,ghi_chu_item,note_rbo,remark_GIAY,lay_sample_15_pcs,remark_MUC,first_order,blank_gap,kind_of_label,note_price,note_color,UPDATED_BY,CREATED_DATE_TIME,STANDARD_LT,OTHER_REMARK_1,OTHER_REMARK_2,OTHER_REMARK_3,OTHER_REMARK_4   ";
	$query_no_cbs = " SELECT $fields FROM $table ORDER BY CREATED_DATE_TIME DESC ";
	$result_no_cbs = mysqli_query($conn, $query_no_cbs);
	if($result_no_cbs === FALSE) { die(mysql_error()); }

	//sử dụng hàm này cho truy vấn data (không dùng hàm khác)
	$rowsResult = mysqli_fetch_all($result_no_cbs, MYSQLI_ASSOC);
    $count = count($rowsResult);
   //// <column width="*" type="link" align="center" sort="str">DELETE</column>
	if( $count > 0 ){ 
		$header = '<head>
						<column width="85" type="ed" align="center" sort="str">DATE</column>
						<column width="140" type="ed" align="center" sort="str">FORM TYPE</column>
						<column width="120" type="ed" align="center" sort="str">FORM NO</column>
						<column width="80" type="ed" align="center" sort="str">SO-LINE</column>
						<column width="150" type="ed" align="center" sort="str">INTERNAL ITEM</column>
						<column width="150" type="ed" align="center" sort="str">ORDER ITEM</column>
						<column width="110" type="ed" align="center" sort="str">CREATED BY</column>
						<column width="80" type="ed" align="center" sort="str">STATUS</column>
						<column width="62" type="link" align="center" sort="str">PRINT</column>
					</head>';
		echo("<rows>");
		//echo $header;
		if(!empty($rowsResult)){  
			$ID = 0;
			$cellStart = "<cell><![CDATA[";
			$cellEnd = "]]></cell>";
			foreach ($rowsResult as $row){
				$ID++;
				$internal_item 			= $row['internal_item']; 
				$rbo 					= $row['rbo'];
				$order_item 			= $row['order_item'];
				$material_code 			= $row['material_code'];
				$ribbon_code 			= $row['ribbon_code'];
				$material_des 			= $row['material_des'];
				$ink_des 				= $row['ink_des'];
				$width 					= $row['width'];
				$height 				= $row['height'];
				$pcs_sht 				= $row['pcs_sht'];
				$ghi_chu_item 			= $row['ghi_chu_item'];
				$note_rbo 				= $row['note_rbo'];
				$remark_GIAY 			= $row['remark_GIAY'];
				$lay_sample_15_pcs 		= $row['lay_sample_15_pcs'];
				$remark_MUC 			= $row['remark_MUC'];
				$first_order 			= $row['first_order'];
				$blank_gap 				= $row['blank_gap'];
				$kind_of_label 			= $row['kind_of_label'];
				$note_price 			= $row['note_price'];
				$note_color 			= $row['note_color'];

				$UPDATED_BY 			= $row['UPDATED_BY'];
				$CREATED_DATE_TIME 		= $row['CREATED_DATE_TIME'];
				$STANDARD_LT 			= $row['STANDARD_LT'];
				$NOTE 					= $row['note'];

				$OTHER_REMARK_1 		= $row['OTHER_REMARK_1'];
				$OTHER_REMARK_2 		= $row['OTHER_REMARK_2'];
				$OTHER_REMARK_3 		= $row['OTHER_REMARK_3'];
				$OTHER_REMARK_4 		= $row['OTHER_REMARK_4'];

				echo("<row id='".$ID."'>");
					echo( $cellStart);  // LENGTH
						echo($ID);  //1                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($internal_item);  //1                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($rbo);  //2                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($order_item);  //3                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($material_code);  //4                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($ribbon_code);  //5                
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($material_des);  //6                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($ink_des);  //7                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($width);  //88888888888888888               
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($height);  //9                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($blank_gap);  //10                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($ghi_chu_item);  //11                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($note_rbo);  //12                
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($remark_GIAY);  //13                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($lay_sample_15_pcs);  //14                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($remark_MUC);  //15                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($first_order);  //16                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($pcs_sht);  //17             
					echo( $cellEnd);

					echo( $cellStart);  // LENGTH
						echo($kind_of_label);  //18                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($STANDARD_LT);  //19                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($NOTE);  //20 ''''               
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($note_price);  		//21              
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($note_color);  //22           
					echo( $cellEnd);

					echo( $cellStart);  // LENGTH
						echo($OTHER_REMARK_1);  		//23                 
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($OTHER_REMARK_2);  	//24                
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($OTHER_REMARK_3);  //25            
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($OTHER_REMARK_4);  		//26
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($UPDATED_BY);  		//27          
					echo( $cellEnd);
					echo( $cellStart);  // LENGTH
						echo($CREATED_DATE_TIME);  		//28          
					echo( $cellEnd);

				
				echo("</row>");
			}
		}
		echo("</rows>");
	}else{
		echo("<rows></rows>");
	}
?>