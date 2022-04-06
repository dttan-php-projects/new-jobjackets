<?php
	defined('BASEPATH') or exit('No direct script access allowed');
	if ($account_type != 3 ) {
		header('Location: ' . base_url(get_cookie('plan_department')));
	}
	include_once ( "templates/masterUsers.php" );

?>
<script>
    
    initMainUserLayout();
	initMainUserGrid();
	
</script>
