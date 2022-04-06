<?php
	defined('BASEPATH') or exit('No direct script access allowed');
	if ($account_type != 3 ) { header('Location: ' . base_url('woven')); } 
?>

<script>

	// load data
	var results = '<?php print_r($results); ?>';
	// // console.log('results: ' + results);

	// parse results
	results = JSON.parse(results);

	// check false
	if (results.status == false ) {
		alert(results.message); 
		window.location ='<?php echo base_url('users/recent'); ?>';

	} else {
		alert(results.message); 
		window.location ='<?php echo base_url('users/recent'); ?>';
	}
	
</script>
