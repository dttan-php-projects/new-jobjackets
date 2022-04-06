<?php
	defined('BASEPATH') or exit('No direct script access allowed');
	include_once ( "templates/master.php" );

?>
<script>

	// load data
	var results = '<?php print_r($results); ?>';
	// console.log('results: ' + results);
	results = JSON.parse(results);

	// init form and grid
	initHandlePage(results);
	
</script>
