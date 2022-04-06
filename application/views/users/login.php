<script>

    var base_url = '<?php echo base_url(); ?>';
    var results = '<?php print_r($results); ?>';
    // console.log('results: ' + results ); 
    results = JSON.parse(results);

    var status = results['status'];
    var message = results['message'];
    var data = results['data'];

    
    if (status == 'false' ) {
        alert(message);
        window.location.href = '<?php echo base_url(); ?>';
    
    } else {
        var url_suffix = data.department.toLowerCase() + '/index/?username='+data.username;
        window.location.href = base_url + url_suffix;
    }

</script>