<?php
    $production_line = null !== get_cookie('plan_department') ? get_cookie('plan_department') : 'htl';
?>
<script>
    var mySidebar;
    var mainToolbar;
    var mainLayout;
    var remarkForm, conditionForm;
    var remarkGrid, conditionGrid;
    var base_url = '<?php echo base_url(); ?>';
    var app_url = '<?php echo base_url('application/'); ?>';
    var root_url = '<?php echo  $_SERVER["DOCUMENT_ROOT"] ; ?>';
    var myAccountForm;
    var dhxWins;
    // var formStructRemark;

    /*
        | ------------------------------------------------------------------------------------------------------------
        | ON LOAD FUNCTION
        | ------------------------------------------------------------------------------------------------------------
    */

        function doOnLoad() 
        {
            $(document).ready(function(){
                
                // menu
                mainMenu();

                // toolbar
                mainToolbar();
                //get Soline input
                mainToolbar.getInput("from_date").focus();

                // layout
                mainLayout();

            });
        }

    /*
        | ------------------------------------------------------------------------------------------------------------
        | INIT FUNCTION
        | ------------------------------------------------------------------------------------------------------------
    */
        
        function mainLayout(id=null ) 
        {
            
            mainLayout = new dhtmlXLayoutObject({
                parent: document.body,
                pattern: "1C",
                offsets: {
                    top: 60
                },
                cells: [
                    {id: "a", header: true, text: "REMARK VIEWS"}
                ]
            });

            mainGrid();


        }

    
        function mainMenu() 
        {
            mainMenu = new dhtmlXMenuObject({
                parent: "mainMenu",
                iconset: "awesome",
                json: "<?php echo base_url('assets/xml/remarks_menu.xml'); ?>",
                top_text: "<?php echo "<img style='width:60px;' src='./assets/media/images/Logo.png'/>&nbsp;&nbsp;&nbsp; REMARKS - PLANNING "; ?>"
            });
            mainMenu.setAlign("right");
            
            mainMenu.attachEvent("onClick", function(id){
                if(id !== "home") {
                    if ( (id == 'view_remarks') || (id == 'view_conditions') ) {

                        mainGrid(id );

                    } else if (id == 'create_remark' ) {

                        createRemark();

                        // if (Number(getCookie('plan_account_type') == 3)) {
                        //     importMaster();
                        // } else {
                        //     alert('Bạn không có quyền truy cập chức năng này');
                        // }
                    } else if (id == 'create_condition' ) {
                        createCondition();
                    } else if (id == 'report' ) {
                        
                    } else if (id == 'come_back_planning' ) {
                        location.href = "<?php echo base_url(get_cookie('plan_department')); ?>";
                    } 

                } else {
                    location.href = "<?php echo base_url('remarks'); ?>";
                }
            });
        }

        function mainGrid(id=null )
        {

            if (mainGrid) mainGrid.clearAll();

            // check id
            if (id == null ) id = 'view_remarks';
            if (id == 'view_remarks' ) {
                mainLayout.cells("a").setText('REMARKS. <span style="color:red;"> Lưu ý các remark cùng một điều kiện tách nhau bởi dấu ";"</span>');
            } else if (id == 'view_conditions' ) {
                mainLayout.cells("a").setText('CONDITIONS. <span style="color:red;"> Các điều kiện được sử dụng chung cho các Production line nên không được phép thay đổi.</span>');
            }

            // grid
            var mainGrid = mainLayout.cells("a").attachGrid();
            mainGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>" );

            mainGrid.enableSmartRendering(true);
            mainGrid.setColumnColor(",,,#d5f1ff,,#d5f1ff,#d5f1ff");
            mainGrid.setStyle("font-weight:bold; font-size:13px;text-align:center;color:#990000;","font-size:12px;", "", "font-weight:bold;color:#0000ff;font-size:14px;");
            mainGrid.enableStableSorting(true);
            mainGrid.enableSmartRendering(true);
            mainGrid.enableMultiselect(true);
            
            // validation
            mainGrid.enableValidation(true, true); 
            mainGrid.setColValidators([null,null,"NotEmpty","NotEmpty"]);

            mainGrid.init();

            // load data
            mainGrid.loadXML("<?php echo base_url('remarks/mainGrid/') ?>"+id, function() {
                // onLiveValidationError onValidationError
                mainGrid.attachEvent("onValidationError", function(id,index,value,input,rule){
                    var row = Number(id)+1;
                    var col = Number(index)+1;

                    dhtmlx.message({
                        text: "Nhập không đúng định dạng vị trí (Cột "+col+", dòng "+row+")",
                        expire:6000,
                        type:"error"
                    });

                    mainGrid.cells(id,index).setValue("");
                    mainGrid.selectCell(id, index, false, true);
                    mainGrid.editCell();
                    
                });
            });

            mainGrid.attachEvent("onCheckbox", function(rId,cInd,state){
                
                var jsonObjects = {};
                // check and save auto
                var onCheckbox = false;
                var delConf = 'no';

                if (id == 'view_remarks' ) {

                    var condition_code = mainGrid.cells(rId,1).getValue();
                    var conditions = mainGrid.cells(rId,2).getValue();
                    var remark = mainGrid.cells(rId,3).getValue();
                
                    //json data encode
                    jsonObjects = { 
                        "condition_code": condition_code,
                        "conditions": conditions,
                        "remark": remark
                    };


                    if (cInd == 6 && (mainGrid.cells(rId,cInd).getValue() == 1) ) { // save
                        onCheckbox = true;
                    } else if (cInd == 7 && (mainGrid.cells(rId,cInd).getValue() == 1) ) { // del

                        onCheckbox = true;
                        conf = confirm("Bạn có chắc chắn muốn xóa Remark: "+remark+ "?");
                        if (conf == false ) {
                            return false;
                        } else {
                            delConf = 'del';
                        }
                    }


                }

                // if on check save or delete button
                if (onCheckbox == true ) {
                    //  sent to data to updated
                    var url = "<?php echo base_url('remarks/updateRemarks?master='); ?>"+id+'&del='+delConf;
                    console.log('url: ' + url);
                    ajaxRequest(jsonObjects, url);
                }
            });
        }

        function mainToolbar() 
        {
            mainToolbar = new dhtmlXToolbarObject({
                parent: "mainToolbar",
                icons_size: 18,
                iconset: "awesome"
            });

            // init item
            mainToolbar.addButton("remarks", 1, "<span style='color:green;font-weight:bold;font-size:13px;'>REMARKS:</span>", "fa fa-sticky-note");
            mainToolbar.addSpacer("remarks");
            mainToolbar.addText("from_date_label", 11, "Date: From");
            mainToolbar.addInput("from_date", 12, "", 80);
            mainToolbar.addText("to_date_label", 13, "to");
            mainToolbar.addInput("to_date", 14, "", 80);
            mainToolbar.addSeparator("separator_2", 15);
            mainToolbar.addButton("my_account", 16, "<span style='color:blue;font-weight:bold;font-size:12px;'>My Account</span>", "fa fa-sign-out");
            mainToolbar.addSeparator("separator_3", 20);

            // Init calendar, attach from date and to date
            from_date = mainToolbar.getInput("from_date");
            to_date = mainToolbar.getInput("to_date");

            myCalendar = new dhtmlXCalendarObject([from_date, to_date]);
            myCalendar.setDateFormat("%Y-%m-%d");

            // Init Popup and attach form my account   
            myPop = new dhtmlXPopup({
                toolbar: mainToolbar,
                id: "my_account"
            });

            var username_label = 'Loading...';
            var username = getCookie('plan_loginUser');
            if (username) {
                username_label = "<span style='color:blue;font-weight:bold;font-size:12px;'>" + username + "</span>";
            }

            myPop.attachEvent("onShow", function() {

                // check if myForm is not inited - call init once when popup shown 1st time
                // another way to check is if (myForm instanceof dhtmlXForm)
                if (!myAccountForm) {
                    
                    myAccountForm = myPop.attachForm([{
                            type: "settings",
                            position: "label-left",
                            width: 230
                        },
                        {
                            type: "block",
                            width: 230,
                            list: [{
                                    type: "button",
                                    name: "changePass",
                                    value: "Change Password",
                                    width: 100,
                                    offsetRight: 10
                                },
                                {
                                    type: "newcolumn"
                                },
                                {
                                    type: "button",
                                    name: "logout",
                                    value: "Logout",
                                    width: 80,
                                    offsetLeft: 20
                                }
                            ]
                        }
                    ]);

                    myAccountForm.attachEvent("onButtonClick", function(name) {
                        if (name == 'changePass') {
                            changeProfile(username);
                        } else {
                            logout();
                        }
                        myPop.hide();
                    });
                    
                }

                myAccountForm.setFocusOnFirstActive();
                
            });

            mainToolbar.setItemText('my_account', username_label);

        }

        function formStruct() 
        {
            formStruct = [
                { type: "settings", position: "label-left", labelWidth: 150, inputWidth: 200 },
                {
                    type: "fieldset", label: "<span style='color:red;font-weight:bold;'>Conditions</span>", width: 550, blockOffset: 10, offsetLeft: 30, offsetTop: 30,
                    list: [
                        { type: "settings", position: "label-left", labelWidth: 120, inputWidth: 350, labelAlign: "left" },
                        { type: "checkbox", id: "rbo", name: "rbo", label: "1. RBO", icon: "icon-input", className: "condition_form col_1" },
                        { type: "checkbox", id: "ship_to_customer", name: "ship_to_customer", label: "2. Ship To Customer", icon: "icon-input", className: "condition_form col_1" },
                        { type: "checkbox", id: "bill_to_customer", name: "bill_to_customer", label: "3. Bill To Cumstomer", icon: "icon-input", className: "condition_form col_1" },
                        {type: "newcolumn", offset: 40},
                        { type: "checkbox", id: "internal_item", name: "internal_item", label: "4. Internal Item", icon: "icon-input", className: "condition_form col_1" },
                        { type: "checkbox", id: "ordered_item", name: "ordered_item", label: "5. Ordered Item", icon: "icon-input", className: "condition_form col_1" },
                        { type: "checkbox", id: "order_type_name", name: "order_type_name", label: "6. Order Type Name", icon: "icon-input", className: "condition_form col_1" },
                        {type: "newcolumn", offset: 40},
                        { type: "checkbox", id: "material_code", name: "material_code", label: "7. Material Code", icon: "icon-input", className: "condition_form col_1" },
                        { type: "checkbox", id: "ink_code", name: "ink_code", label: "8. Ink Code", icon: "icon-input", className: "condition_form col_1" },
                        { type: "checkbox", id: "packing_instr", name: "packing_instr", label: "9. Packing Instr", icon: "icon-input", className: "condition_form col_1" }

                    ]
                }, 
                {   type: "button", id: "updateCondition", name: "updateCondition", value: "Update", position: "label-center", width: 210, offsetLeft: 360 }
            ];

            // return formStruct;
        }


        function createRemark() 
        {
            if(!dhxWins){ dhxWins= new dhtmlXWindows(); }

            var id = "addRemarkWindows";
            var w = 980;
            var h = 500;

            // var x = Number(($(window).width())/3);
            // var y = Number( ($(window).height()) - 500 );

            var win_x = Number(($(window).width()));
            var win_y = Number(($(window).height()));
            console.log("win_x: " + win_x);
            console.log("win_y: " + win_y);

            if (win_x <= 1600 ) {
                var x = Number(win_x/3);    
            } else {
                var x = Number(win_x/3);    
            }

            if (win_y <= 900 ) {
                var y = Number(win_y - 500 );    
            } else {
                var y = Number(win_y - 800);    
            }

            var create = dhxWins.createWindow(id, x, y, w, h);
            dhxWins.window(id).setText("Add Remark");
            
            var url = "<?php echo base_url('remarks/createRemark/'); ?>";
            //excute with ajax function 
            $.ajax({
                type: "POST",
                data: { data: '' },
                url: url,
                dataType: 'json',
                beforeSend: function(x) { if (x && x.overrideMimeType) { x.overrideMimeType("application/j-son;charset=UTF-8"); } },
                success: function(results) {
                    
                    if (results.status == false ) {
                        alert(results.message);
                    } else {
                        var structCreate = results.struct;
                        var condition_label = results.condition_label;

                        createRemarkForm = create.attachForm();
                        createRemarkForm.loadStruct(structCreate);

                        console.log('structCreate:: ' + structCreate);

                        // disable item 
                        createRemarkForm.disableItem("rbo");
                        createRemarkForm.disableItem("ship_to_customer");
                        createRemarkForm.disableItem("bill_to_customer");
                        createRemarkForm.disableItem("internal_item");
                        createRemarkForm.disableItem("ordered_item");
                        createRemarkForm.disableItem("order_type_name");
                        createRemarkForm.disableItem("material_code");
                        createRemarkForm.disableItem("ink_code");
                        createRemarkForm.disableItem("packing_instr");

                        attachcreateRemarkForm(condition_label);

                    }
                    
                },
                error: function(xhr, status, error) {
                    alert('Error load data (add remark)');
                    // alert('Error load data (add remark). '+xhr.responseText);
                    return false;
                }
            });
            
        }

        function createCondition() 
        {
            if(!dhxWins){ dhxWins= new dhtmlXWindows(); }

            var id = "addConditionWindows";
            var w = 650;
            var h = 300;
            var x = Number(($(window).width())/3);
            var y = Number( ($(window).height()) -854 );

            var create = dhxWins.createWindow(id, x, y, w, h);
            dhxWins.window(id).setText("Add Condition");

            formStruct();
            createForm = create.attachForm();
            createForm.loadStruct(formStruct);
            createForm.setItemLabel("updateCondition", "Add Condition");
            
            var plan_department = getCookie('plan_department');
            if (plan_department == 'woven' ) {
                createForm.disableItem('material_code');
                createForm.disableItem('ink_code');
            }


            var checkedCount = 0;
            var checkList = [];
            createForm.attachEvent("onChange", function (name, value, state){
                
                if (state == 1 ) {
                    checkedCount++;
                    checkList.push(name);
                } else {
                    checkedCount--;
                    // lấy lại các phần tử lưu vào mảng (trừ phần tử đang bỏ check )
                    checkList = checkList.filter(item => item !== name);
                }
                if (checkedCount == 2 ) {
                    
                    createForm.disableItem('rbo');
                    createForm.disableItem('ship_to_customer');
                    createForm.disableItem('bill_to_customer');
                    createForm.disableItem('internal_item');
                    createForm.disableItem('ordered_item');
                    createForm.disableItem('order_type_name');
                    createForm.disableItem('material_code');
                    createForm.disableItem('ink_code');
                    createForm.disableItem('packing_instr');
                    for(var i=0;i<checkList.length; i++ ) {
                        createForm.enableItem(checkList[i]);
                    }
                } else {
                    
                    createForm.enableItem('rbo');
                    createForm.enableItem('ship_to_customer');
                    createForm.enableItem('bill_to_customer');
                    createForm.enableItem('internal_item');
                    createForm.enableItem('ordered_item');
                    createForm.enableItem('order_type_name');
                    createForm.enableItem('material_code');
                    createForm.enableItem('ink_code');
                    if (plan_department == 'woven' ) {
                        createForm.disableItem('material_code');
                        createForm.disableItem('ink_code');
                    }
                    
                    createForm.enableItem('packing_instr');
                }

            });

            // attach
            createForm.attachEvent("onButtonClick", function(name){

                if (name == 'updateCondition' ) {
                    var rbo = createForm.getItemValue("rbo");
                    var ship_to_customer = createForm.getItemValue("ship_to_customer");
                    var bill_to_customer = createForm.getItemValue("bill_to_customer");
                    var internal_item = createForm.getItemValue("internal_item");

                    var ordered_item = createForm.getItemValue("ordered_item");
                    var order_type_name = createForm.getItemValue("order_type_name");
                    var material_code = createForm.getItemValue("material_code");
                    var ink_code = createForm.getItemValue("ink_code");
                    var packing_instr = createForm.getItemValue("packing_instr");

                    var data = { 
                        rbo : rbo, 
                        ship_to_customer : ship_to_customer, 
                        bill_to_customer : bill_to_customer, 
                        internal_item : internal_item, 
                        ordered_item  : ordered_item,   
                        order_type_name : order_type_name, 
                        material_code : material_code,
                        ink_code : ink_code,
                        packing_instr : packing_instr
                    };
                    var jsonObjects = JSON.stringify(data);
                    var url = "<?php echo base_url('remarks/saveCondition'); ?>";
                    
                    $.ajax({
                        type: "POST",
                        data: { data: jsonObjects },
                        url: url,
                        dataType: 'json',
                        beforeSend: function(x) { 
                            if (x && x.overrideMimeType) { x.overrideMimeType("application/j-son;charset=UTF-8"); }
                        },
                        success: function(results) {
                            alert(results.message);
                            location.reload();
                        },
                        error: function(xhr, status, error) {
                            // alert(error);alert(xhr.responseText);
                            alert('Error Load Data: '+xhr.responseText);
                            return false;
                        }
                    });   

                }
            });

        }

        function attachcreateRemarkForm(condition_label) 
        {
            // Validation live
            createRemarkForm.enableLiveValidation(true);
            
            var condition = createRemarkForm.getCombo("condition");
            console.log('condition:: ' + condition); 
            condition.attachEvent("onChange", function(value,state){

                // disable item 
                createRemarkForm.disableItem("condition");
                createRemarkForm.disableItem("rbo");
                createRemarkForm.disableItem("ship_to_customer");
                createRemarkForm.disableItem("bill_to_customer");
                createRemarkForm.disableItem("internal_item");
                createRemarkForm.disableItem("ordered_item");
                createRemarkForm.disableItem("order_type_name");
                createRemarkForm.disableItem("material_code");
                createRemarkForm.disableItem("ink_code");
                createRemarkForm.disableItem("packing_instr");
                
                var condition_codes = createRemarkForm.getItemValue("condition");                
                console.log("Code: "+condition_codes);
                
                // check enable Item
                for (var i=0; i<condition_label.length; i++ ) {
                    
                    if (condition_label[i].indexOf(condition_codes) !== -1 ) {
                        
                        if (condition_label[i].toLowerCase().indexOf("rbo") !== -1 ) {
                            createRemarkForm.enableItem("rbo");
                        }
                        if (condition_label[i].toLowerCase().indexOf("ship to customer") !== -1 ) {
                            createRemarkForm.enableItem("ship_to_customer");
                        }
                        if (condition_label[i].toLowerCase().indexOf("bill to customer") !== -1 ) {
                            createRemarkForm.enableItem("bill_to_customer");
                        }
                        if (condition_label[i].toLowerCase().indexOf("internal item") !== -1 ) {
                            createRemarkForm.enableItem("internal_item");
                        }
                        if (condition_label[i].toLowerCase().indexOf("ordered item") !== -1 ) {
                            createRemarkForm.enableItem("ordered_item");
                        }
                        if (condition_label[i].toLowerCase().indexOf("order type") !== -1 ) {
                            createRemarkForm.enableItem("order_type_name");
                        }
                        if (condition_label[i].toLowerCase().indexOf("material code") !== -1 ) {
                            createRemarkForm.enableItem("material_code");
                        }
                        if (condition_label[i].toLowerCase().indexOf("ink code") !== -1 ) {
                            createRemarkForm.enableItem("ink_code");
                        }
                        if (condition_label[i].toLowerCase().indexOf("packing instr") !== -1 ) {
                            createRemarkForm.enableItem("packing_instr");
                        }
                        
                    }
                }
                
                // attach save button
                createRemarkForm.attachEvent("onButtonClick", function(name){
                    
                    if (name == 'createRemark' ) {
                        
                        var rbo = createRemarkForm.getCombo('rbo').getChecked();
                        var ship_to_customer = createRemarkForm.getCombo('ship_to_customer').getChecked();
                        var bill_to_customer = createRemarkForm.getCombo('bill_to_customer').getChecked();
                        var internal_item = createRemarkForm.getCombo('internal_item').getChecked();
                        var ordered_item = createRemarkForm.getCombo('ordered_item').getChecked();
                        var order_type_name = createRemarkForm.getCombo('order_type_name').getChecked();
                        var material_code = createRemarkForm.getCombo('material_code').getChecked();
                        var ink_code = createRemarkForm.getCombo('ink_code').getChecked();
                        var packing_instr = createRemarkForm.getItemValue('packing_instr');
                        var remark = createRemarkForm.getItemValue('remark');

                        //json data encode
                        var jsonObjects = {
                            condition_code : condition_codes,
                            rbo : rbo, 
                            ship_to_customer : ship_to_customer,
                            bill_to_customer : bill_to_customer,
                            internal_item : internal_item,
                            ordered_item : ordered_item,
                            order_type_name : order_type_name,
                            material_code : material_code,
                            ink_code : ink_code,
                            packing_instr : packing_instr,
                            remark : remark
                        };

                        if (jsonObjects.rbo.length == 0 ) delete jsonObjects.rbo;
                        if (jsonObjects.ship_to_customer.length == 0 ) delete jsonObjects.ship_to_customer;
                        if (jsonObjects.bill_to_customer.length == 0 ) delete jsonObjects.bill_to_customer;
                        if (jsonObjects.internal_item.length == 0 ) delete jsonObjects.internal_item;
                        if (jsonObjects.ordered_item.length == 0 ) delete jsonObjects.ordered_item;
                        if (jsonObjects.order_type_name.length == 0 ) delete jsonObjects.order_type_name;
                        if (jsonObjects.material_code.length == 0 ) delete jsonObjects.material_code;
                        if (jsonObjects.ink_code.length == 0 ) delete jsonObjects.ink_code;
                        if (jsonObjects.packing_instr.length == 0 ) delete jsonObjects.packing_instr;

                        // console.log('jsonObjects: ' + JSON.stringify(jsonObjects));   
                        var url = "<?php echo base_url('remarks/createRemarkSave/'); ?>";
                        //excute with ajax function 
                        $.ajax({
                            type: "POST",
                            data: { data: JSON.stringify(jsonObjects) },
                            url: url,
                            dataType: 'json',
                            beforeSend: function(x) { if (x && x.overrideMimeType) { x.overrideMimeType("application/j-son;charset=UTF-8"); } },
                            success: function(results) {
                                if (results.status == false ) {
                                    alert(results.message);
                                    // location.reload();
                                    return false;
                                } else {
                                    alert(results.message);
                                    location.reload();
                                }
                            },
                            error: function(xhr, status, error) { alert('Error load data (add remark). '+xhr.responseText); return false;}
                        });

                    }         
                            
                });
                
            });
        }


        function ajaxRequest(jsonObjects, url )
        {
            // check 
            if (jsonObjects && url ) {

                //excute with ajax
                    $.ajax({
                        type: "POST",
                        data: { data: JSON.stringify(jsonObjects) },
                        url: url,
                        dataType: 'json',
                        beforeSend: function(x) { if (x && x.overrideMimeType) { x.overrideMimeType("application/j-son;charset=UTF-8");} },
                        success: function(data) {

                            alert(data.message );
                            location.reload();
                                
                        },
                        error: function(xhr, status, error) {
                            alert('Error. Vui lòng liên hệ quản trị hệ thống!');
                            location.reload();
                            return false;
                        }
                    });

            }

        }

        
        


    /*
        | ------------------------------------------------------------------------------------------------------------
        | OTHERS
        | ------------------------------------------------------------------------------------------------------------
    */

        function setCookie(cname, cvalue, exdays) 
        {
            var d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            var expires = "expires=" + d.toGMTString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }

        function getCookie(cname) 
        {
            var name = cname + "=";
            var decodedCookie = decodeURIComponent(document.cookie);
            var ca = decodedCookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return false;
        }

        function changeProfile(username) 
        {
            // var url_suffix = 'so_line='+so_line+'&item='+item;
            location.href = '<?php echo base_url('users/edit/'); ?>' + username;
        }

    /*
        | ------------------------------------------------------------------------------------------------------------
        | ================
        | ------------------------------------------------------------------------------------------------------------
    */





</script>
