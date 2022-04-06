<script>
    function initOrderForm2(internal_item) {

        // save to server
        let jsonObjects = {};

        // url to server
        let url = '<?php echo base_url("woven/getFormStruct/?internal_item="); ?>' + internal_item;

        //excute with ajax function 
        $.ajax({
            type: "POST",
            data: {
                data: JSON.stringify(jsonObjects)
            },
            url: url,
            dataType: 'json',
            beforeSend: function(x) {
                if (x && x.overrideMimeType) {
                    x.overrideMimeType("application/j-son;charset=UTF-8");
                }
            },
            success: function(data) {
                try {
                    if (data.status == true) {
                        formStructure = data.formStructure;
                        console.log(formStructure);
                        orderForm = handleLayout.cells("a").attachForm(formStructure);
                    }
                } catch (e) {
                    formStructure = [];

                }
            },
            error: function(xhr, status, error) {
                alert('Err: ' + error + 'Response: ' + xhr.responseText);
            },
            async: false
        });

        // orderForm = handleLayout.cells("a").attachForm(formStructure);

    }
</script>