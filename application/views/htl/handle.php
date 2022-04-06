<?php
	defined('BASEPATH') or exit('No direct script access allowed');
	$dataCheck = json_decode($results, JSON_UNESCAPED_UNICODE);
	
	
	if ($dataCheck['status'] ==  1 ) {
		include_once ( "templates/master.php" );
	}
	
?>
<script>

	// load data
	var results = '<?php print_r($results); ?>';
	// console.log('results : ' + results);

	// parse results
	results = JSON.parse(results);

	// check false
	if (results.status == false ) {
		alert(results.message); 
		 window.location ='<?php echo base_url('htl/index'); ?>';
	} else {
		// init form and grid
		initHandlePage(results);
	}
	
</script>
