<script>

    // set status and message
        var status = '<?php echo $status; ?>';
        var message = '<?php echo $message; ?>';

    // alert for user and redirect index
        alert (message);
        window.location ='<?php echo base_url('htl'); ?>';

</script>