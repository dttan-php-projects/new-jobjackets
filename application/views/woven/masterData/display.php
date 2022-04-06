<?php
    // echo json_encode($response);exit();
    $results = json_encode($this->_data['results']);
?>

<script>

	// load data
	var results = '<?php print_r($results); ?>';
	console.log('results : ' + results);

	// parse results
	results = JSON.parse(results);

    alert(results.message); 
    window.location ='<?php echo base_url('woven/index'); ?>';

	// // check false
	// if (results.status == false ) {
	// 	alert(results.message); 
    //     window.location ='<?php echo base_url('woven/index'); ?>';
	// } else {
	// 	alert(results.message); 
    //     window.location ='<?php echo base_url('woven/index'); ?>';
	// }

</script>