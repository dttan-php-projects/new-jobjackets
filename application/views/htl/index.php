<?php
	defined('BASEPATH') or exit('No direct script access allowed');

	include_once ( "templates/master.php" );

	$from_date = null !== $this->input->get('from_date') ? trim($this->input->get('from_date')) : '';
	$to_date = null !== $this->input->get('to_date') ? trim($this->input->get('to_date')) : '';

?>

<script>
	var from_date = '<?php echo $from_date; ?>';
	var to_date = '<?php echo $to_date; ?>';
	initMainLayout();
	initMainViewGrid(from_date, to_date);
</script>
