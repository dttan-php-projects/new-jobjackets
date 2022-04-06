<?php 
    date_default_timezone_set('Asia/Ho_Chi_Minh'); 
    $production_line = null !== get_cookie('plan_department') ? get_cookie('plan_department') : 'thermal' ;
?>
<script>
    var myAccountForm;
    var automail_updated = "<?php echo !empty($automail_updated) ? $automail_updated : 'loading...'; ?>";
    var production_line = getCookie('plan_department');
    if (!production_line) production_line = 'thermal';

    var width= screen.width;
    var form_layout_width = 960;

    /*
        | ------------------------------------------------------------------------------------------------------------
        | ON LOAD
        | ------------------------------------------------------------------------------------------------------------
    */
    
        function doOnLoad() 
        {
            $(document).ready(function(){
                // menu
                initMainMenu();
                // toolbar
                initMainToolbar();

                //get Soline input
                mainToolbar.getInput("so_line_input").focus();
                order_type_cookie = getCookie('plan_order_type');
                if (order_type_cookie) {
                    if (order_type_cookie == 'common') {
                        mainToolbar.getInput("so_line_input").focus();
                    } else {
                        mainToolbar.getInput("so_line_input").focus();
                    }
                }

                // get data
                    inputData();
                    onClickMainToolbar();

                
            });
            

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

        function setCookie(cname, cvalue, exdays) 
        {
            var d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            var expires = "expires=" + d.toGMTString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }

        function detachedSOLine(so_line) 
        {
            so_line = so_line.trim();
            so_line = so_line.replace(" ", "");
            
            // tách input thành Order number (SO) và line number (LINE)
            so_line_detached = so_line.split("-");
            
            // set order number and line number
            order_number = so_line_detached[0];
            line_number = so_line_detached[1];
        }

        function formatDate(date ) {

            date = date.toLocaleString('en-US', { timeZone: 'Asia/Bangkok' });
                
            // const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            const monthNames = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];
            const dateObj = new Date(date);
            const month = monthNames[dateObj.getMonth()];
            const day = String(dateObj.getDate()).padStart(2, '0');
            const year = dateObj.getFullYear();

            return year + '-' + month + '-' +  day;

        }

    /*
        | ------------------------------------------------------------------------------------------------------------
        | CHECK INPUT DATA
        | ------------------------------------------------------------------------------------------------------------
    */
        // on Enter attach
        function inputData() 
        {
            // attach events
            mainToolbar.attachEvent("onEnter", function(id) {
                
                //get input
                    var input = mainToolbar.getInput("so_line_input");
                
                // check input. User input SOLine or item or (soline and item).
                if (id == "so_line_input") {
                    
                    var input_value = input.value;

                    // check SOLine
                    checkSOLineInput(input_value);
                    if (error) {
                        alert(message);
                        location.reload();
                        return false;
                    } else {
                        // check order is already exist
                        checkDataExist(input_value);
                        
                    }
                    
                }

            });
        }

        // Check soline input exactly
        function checkSOLineInput(input_data) 
        {
            error = 0;
            input_data = input_data.trim();
            input_data = input_data.replace(" ", "");

            if (!input_data) {
                message = "[ERROR 01.01]. VUI LÒNG NHẬP SO# !";
                error = 1;
            } else {
                if (input_data.length < 8 || input_data.length > 13) {
                    message = "[ERROR 01.02]. BẠN ĐÃ NHẬP SAI SO#, VUI LÒNG NHẬP LẠI !!";
                    error = 1;
                } else {

                    // Trường hợp nhập SO#, không nhập line
                    if (input_data.search("-") == -1) {
                        if (input_data.length != 8 ) {
                            message = "[ERROR 01.03]. BẠN ĐÃ NHẬP SAI SO#, VUI LÒNG NHẬP LẠI !!! ";
                            error = 1;
                        }
                    } else {
                        // detached
                        detachedSOLine(input_data);

                        if (order_number.length != 8) {
                            message = "[ERROR 01.05]. BẠN ĐÃ NHẬP SAI SO#, VUI LÒNG NHẬP LẠI !!!! ";
                            error = 1;
                        } else if (order_number.length == 8 && (line_number.length == 0 || line_number.length > 4)) {
                            message = "[ERROR 01.05]. BẠN ĐÃ NHẬP SAI SO#, VUI LÒNG NHẬP LẠI !!!! ";
                            error = 1;
                        } else {

                            //Khong phai so
                            if (isNaN(order_number) == true || isNaN(line_number) == true) {
                                message = "[ERROR 01.06]. BẠN ĐÃ NHẬP SAI SO#, VUI LÒNG NHẬP LẠI !!!! ";
                                error = 1;
                            }
                        }
                    }
                    

                }
            }

        } // END

        // check Automail, Master Data
        function checkDataExist(input_value) 
        {
            //json data encode
            var jsonObjects = { "input_value": input_value };
            var url = "<?php echo base_url('thermal/checkDataExist/'); ?>";

            //excute with ajax
            $.ajax({
                type: "POST",
                data: { data: JSON.stringify(jsonObjects) },
                url: url,
                dataType: 'json',
                beforeSend: function(x) { if (x && x.overrideMimeType) { x.overrideMimeType("application/j-son;charset=UTF-8");} },
                success: function(data) {

                    if (data.status == false ) {
                        alert(data.message );
                        location.reload();
                        return false;
                    } else {

                        // Lấy dữ liệu tại đây
                        isAlreadyExist(input_value);
                        
                    }
                        
                },
                error: function(xhr, status, error) {
                    // alert(error);alert(xhr.responseText);
                    // alert('Error(check): Không check được soline tồn tại. Vui lòng liên hệ quản trị hệ thống! '+xhr.responseText);
                    alert('Error(check): Không check được soline tồn tại. Vui lòng liên hệ quản trị hệ thống!');
                    location.reload();
                    return false;
                }
            });
        }

        function onClickMainToolbar() 
        {
            mainToolbar.attachEvent("onClick", function(name)
            {
                console.log("name: "+name);
                if(name == "save" ) {

                    // get data to check
                        var promise_date = orderForm.getItemValue('promise_date');

                    // check empty data
                        if (!promise_date ) {
                            alert('Promise Date Not Empty'); 
                            return false;
                        } 

                        console.log("promise_date: "+promise_date);

                    // save data
                        saveOrders();


                    
                } else {
                    console.log("no no ");
                } 
            });
        }

        function isAlreadyExist(so_line) 
        {
            //json data encode
            var jsonObjects = { "so_line": so_line };
            var url = "<?php echo base_url('thermal/isAlreadyExist/'); ?>";

            //excute with ajax
            $.ajax({
                type: "POST",
                data: { data: JSON.stringify(jsonObjects) },
                url: url,
                dataType: 'json',
                beforeSend: function(x) { if (x && x.overrideMimeType) { x.overrideMimeType("application/j-son;charset=UTF-8");} },
                success: function(data) {

                    if (data.status == false ) {
                        
                        if (data.edit == false ) {
                            alert(data.message );
                            location.reload();
                            return false;
                        } else {
                            var confirm_user = confirm(data.message );
                            if (!confirm_user ) {
                                window.location = '<?php echo base_url('thermal'); ?>';
                                return false;

                            } else {
                                handle(so_line, data.po_no_edit);
                            }
                        }
                        

                    } else {

                        // Lấy dữ liệu tại đây
                        handle(so_line);
                        
                    }
                        
                },
                error: function(xhr, status, error) {
                    // alert(error);alert(xhr.responseText);
                    // alert('Error(check): Không check được soline tồn tại. Vui lòng liên hệ quản trị hệ thống! '+xhr.responseText);
                    alert('Error(check): Không check được soline tồn tại. Vui lòng liên hệ quản trị hệ thống!');
                    location.reload();
                    return false;
                }
            });
        }

    
    /*
        | ------------------------------------------------------------------------------------------------------------
        | INIT
        | ------------------------------------------------------------------------------------------------------------
    */

        // menu
        function initMainMenu() 
        {
            mainMenu = new dhtmlXMenuObject({
                parent: "mainMenu",
                iconset: "awesome",
                json: "<?php echo base_url('assets/xml/thermal_main_menu.xml'); ?>",
                top_text: "<?php echo "<img style='width:60px;' src='". base_url('assets/media/images/Logo.PNG') ."'/>&nbsp;&nbsp;&nbsp; " . strtoupper($production_line) ." PRODUCTION PLANNING "; ?>"
            });
            mainMenu.setAlign("right");

            mainMenu.attachEvent("onClick", function(id){
                if(id !== "home") {
                    if (id == 'master_data' ) {
                        if (getCookie('permission') == 'AD02') {
                            location.href = "<?php echo base_url('thermal/masterFile'); ?>";
                        } else {
                            alert('Bạn không có quyền truy cập chức năng này');
                        }
                        
                    } else if (id == 'report_form_type' ) {
                        // Init calendar, attach from date and to date 
                        var from_date = mainToolbar.getValue("from_date");
                        var to_date = mainToolbar.getValue("to_date");
                        var form_type = getCookie('print_type_thermal') ? getCookie('print_type_thermal') : 'all' ;

                        reportOrders(from_date, to_date, form_type);

                    } else if (id == 'report_all' ) {
                        // Init calendar, attach from date and to date 
                        var from_date = mainToolbar.getValue("from_date");
                        var to_date = mainToolbar.getValue("to_date");
                        var form_type = 'all' ;

                        reportOrders(from_date, to_date, form_type);

                    } else if (id == 'view_user' ) {
                        if (Number(getCookie('plan_account_type') == 3)) {
                            location.href = "<?php echo base_url('users/recent') ?>";
                        } else {
                            alert('Bạn không có quyền truy cập chức năng này');
                        }
                        
                    } else if (id == 'create_user' ) {
                        if (Number(getCookie('plan_account_type') == 3)) {
                            initCreateUserWindow('');
                        } else {
                            alert('Bạn không có quyền truy cập chức năng này');
                        }
                        
                    } else if (id == 'remarks' ) {
                        location.href = "<?php echo base_url('remarks'); ?>";
                    } else if (id == 'view_distance' ) {
                        // Init calendar, attach from date and to date 
                        var from_date = mainToolbar.getValue("from_date");
                        var to_date = mainToolbar.getValue("to_date");
                        var suffix_url_views = '?from_date='+from_date+'&to_date='+to_date;
                        location.href = "<?php echo base_url($production_line); ?>"+ "/index/"+suffix_url_views;
                    }

                } else {
                    if (!getCookie('plan_account_type') ) {
                            alert('Bạn không có quyền truy cập chức năng này');
                    } else {
                        location.href = "<?php echo base_url($production_line); ?>";
                    }
                    
                }
            });
        }

        // layout
        function initMainLayout() 
        {
            mainLayout = new dhtmlXLayoutObject({
                parent: document.body,
                pattern: "1C",
                offsets: {
                    top: 60
                },
                cells: [
                    {id: "a", header: true, text: "TỔNG DANH SÁCH ĐƠN HÀNG"}
                ]
            });

            var url = "<?php echo base_url('thermal/countOrders'); ?>";
            $.ajax({
                type: "POST",
                data: { data: '' },
                url: url,
                dataType: 'json',
                beforeSend: function(x) { if (x && x.overrideMimeType) { x.overrideMimeType("application/j-son;charset=UTF-8"); } },
                success: function(results) {
                    var countAll = results.countAll;
                    var countNow = results.countNow;
                    var now = results.now;
                    mainLayout.cells("a").setText('TỔNG ĐƠN HÀNG: <span style="color:red;font-size:15px;">'+countAll+'</span> || ĐƠN HÀNG HÔM NAY ('+now+'): <span style="color:red;font-size:15px;">'+countNow+'</span> ');
                },
                error: function(xhr, status, error) {
                    alert('Load tổng số lượng đơn hàng lỗi');
                    return false;
                }
            });

        }

        // toolbar
        function initMainToolbar() 
        {
            // attach to sidebar
            // mainToolbar = new dhtmlXToolbarObject("mainToolbar");
            mainToolbar = new dhtmlXToolbarObject({
                parent: "mainToolbar",
                icons_size: 18,
                iconset: "awesome"
            });
            // init item
            mainToolbar.addButton("so_line_label", 3, "<span style='color:blue;font-weight:bold;font-size:13px;'>Input</span>", "fa fa-fire");
            mainToolbar.addInput("so_line_input", 4, "", 160);
            mainToolbar.addSeparator("separator_1", 7);
            mainToolbar.addText("automail", 8, "Automail updated: <span style='color:red;font-weight:bold;font-size:12px;'>" + automail_updated + "</span>");
            mainToolbar.addSpacer("automail");
            mainToolbar.addButton("save", 9, "<span style='color:red;font-weight:bold;font-size:16px;'>Save</span>", "fa fa-floppy-o");
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

        // main grid
        function initMainViewGrid(from_date, to_date) 
        {
            mainviewGrid = mainLayout.cells("a").attachGrid();
            mainviewGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>" );
            mainviewGrid.setHeader("Form Type, Created Date, NO#, Type, SOLine, Quantity, RBO, Internal Item, Updated By, Updated Date, Print, Edit, Delete"); //sets the headers of columns
            mainviewGrid.setColumnIds("form_type, po_date, po_no, po_no_suffix, soline, qty, rbo, internal_item, update_by, update_date, print, edit, delete"); //sets the columns' ids
            mainviewGrid.setInitWidths("100,100,140,100,120,90,120,*,120,110,90,90,90"); //sets the initial widths of columns
            mainviewGrid.setColAlign("center,center,center,center,center,center,center,center,center,center,center,center,center"); //sets the alignment of columns
            mainviewGrid.setColTypes("ed,ed,ed,ed,ed,edn,ed,ed,ed,ed,ed,ed,ed"); //sets the types of columns
            mainviewGrid.setColSorting("str,str,str,str,str,str,str,str,str,str,str,str,str"); //sets the sorting types of columns
            mainviewGrid.enableSmartRendering(true);

            mainviewGrid.setColumnColor(",,,#d5f1ff,,#d5f1ff,#d5f1ff");
            mainviewGrid.setStyle("font-weight:bold; font-size:13px;text-align:center;color:#990000;","font-size:12px;", "", "font-weight:bold;color:#0000ff;font-size:14px;");

            //Lưu ý: filter vượt quá 26 bị lỗi
            mainviewGrid.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
            mainviewGrid.enableMultiselect(true);
            mainviewGrid.init();
            
            loadMainViewGrid(from_date, to_date );

        }

        // order layout -------------------------------------------------------------------------------------------
        function initHandleLayout() 
        {
            form_layout_width = (width >1600) ? (width/2) + 100 : 960;
            handleLayout = new dhtmlXLayoutObject({
                parent: document.body,
                pattern: "3L",
                offsets: {
                    top: 64,
                    bottom: 5
                },
                cells: [
                    {id: "a", header: true, text: "Orders Grid"},
                    {id: "b", header: true, text: "Order Details Form", width: form_layout_width},
                    {id: "c", header: true, text: "Size & Supply Grid", height: 400}
                ]
            });
        }

        function initOrderGrid() 
        {
            orderGrid = handleLayout.cells("a").attachGrid();
            orderGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>" );
            orderGrid.setHeader("#, SO#, LINE, SO-LINE, QTY, INTERNAL ITEM, ITEM DESC, ORDERED ITEM, RBO, LENGTH, WIDTH, CS, BILL TO CUSTOMER, SHIP TO CUSTOMER, ORDERED DATE, REQUEST DATE, PROMISE DATE, ORDER TYPE NAME, FLOW STATUS CODE, PRODUCTION MEDTHOD, PACKING INSTRUCTIONS, PACKING INSTR, ATTACHMENT "); //sets the headers of columns
            orderGrid.setColumnIds("index,order_number,line_number,so_line,qty,internal_item,item_desc,ordered_item,rbo,length,width,cs,bill_to_customer,ship_to_customer,ordered_date,request_date,promise_date,order_type_name,flow_status_code,production_method,packing_instructions,packing_instr,attachment"); //sets the columns' ids
            orderGrid.setInitWidths("55,100,70,120,70,120,140,120,120,150,80,80,130,130,150,110,110,150,130,150,200,200,300"); //sets the initial widths of columns
            orderGrid.setColAlign("center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center"); //sets the alignment of columns
            orderGrid.setColTypes("ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,txt,txt,txt,txt,txt,txt,txt"); //sets the types of columns
            
            orderGrid.setColSorting("str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str"); 
            orderGrid.enableSmartRendering(true);
            orderGrid.enableMultiselect(true);
            orderGrid.editCell();
            orderGrid.init(); //dataProcessor 

            orderGrid.clearAll();

            // load Order data info in automail
	        loadOrderGrid();
                
        }

        function initSizeSupplyGrid() 
        {
            orderGrid = handleLayout.cells("c").attachGrid();
            orderGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>" );
            if (cbs == 1) {
                orderGrid.setHeader("#, SO-LINE, SIZE, COLOR, QTY, MATERIAL CODE, MATERIAL DESC, MATERIAL QTY, INK CODE, INK DESC, INK QTY "); //sets the headers of columns
                orderGrid.setColumnIds("index,so_line,size,color,qty,material_code,material_desc,material_qty,ink_code,ink_desc,ink_qty"); //sets the columns' ids
                orderGrid.setInitWidths("40,100,80,80,80,120,120,100,120,120,80"); //sets the initial widths of columns
                orderGrid.setColAlign("center,center,center,center,center,center,center,center,center,center,center"); //sets the alignment of columns
                orderGrid.setColTypes("ed,ed,ed,ed,ed,ed,txt,ed,txt,ed,ed"); //sets the types of columns
                orderGrid.setColSorting("str,str,str,str,str,str,str,str,str,str,str"); 
            } else {
                handleLayout.cells("c").setText("Material & Ink Grid");
                orderGrid.setHeader("#, SO-LINE, MATERIAL CODE, MATERIAL DESC, MATERIAL QTY, MATERIAL ORDER, MATERIAL UOM, MATERIAL ROLL/KIT, MATERIAL BASEROLL, INK CODE, INK DESC, INK QTY, INK ORDER, INK UOM, INK ROLL/KIT, INK BASEROLL "); //sets the headers of columns
                orderGrid.setColumnIds("index,so_line,material_code,material_desc,material_qty,material_order,material_uom,material_roll_per_kit,material_base_roll,ink_code,ink_desc,ink_qty,ink_order,ink_uom,ink_roll_per_kit,ink_base_roll"); //sets the columns' ids
                orderGrid.setInitWidths("40,100,120,120,110,120,120,140,140,120,120,120,120,120,120,120"); //sets the initial widths of columns
                orderGrid.setColAlign("center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center"); //sets the alignment of columns
                orderGrid.setColTypes("ed,ed,ed,txt,ed,ed,ed,ed,ed,ed,ed,txt,ed,ed,ed,ed"); //sets the types of columns
                orderGrid.setColSorting("str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str"); 
            }
            
            orderGrid.enableSmartRendering(true);
            orderGrid.enableMultiselect(true);
            orderGrid.editCell();
            orderGrid.init(); //dataProcessor 

            orderGrid.clearAll();

            // load data to grid
	            loadSizeSupplyGrid();
                
        }

        function initOrderForm() 
        {
            var form_width = form_layout_width - 50;
            var form_width_2 = form_width - 170;
            formStructure = [
                { type: "settings", position: "label-left", labelWidth: "150", inputWidth: "200" },
                
                {
                    type: "fieldset", label: "Thông tin đơn hàng", width: form_width, blockOffset: 0, offsetLeft: "20", offsetTop: "20",
                    list: [
                        { type: "settings", position: "label-left", labelWidth: 110, inputWidth: 110, labelAlign: "left" },
                        { type: "input", id: "po_no_prefix", name: "po_no_prefix", label: "NO#:", icon: "icon-input", className: "", required: true, validate: "NotEmpty", style: "color:blue;" },
                        { type: "input", id: "size", name: "size", label: "SIZE:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                        { type: "input", id: "order_type_name", name: "order_type_name", label: "Order type name:", icon: "icon-input", className: "", required: true, validate: "NotEmpty", readonly: true },
                        { type: "input", id: "cs", name: "cs", label: "CS:", icon: "icon-input", className: "", required: true, validate: "NotEmpty", readonly: true },
                        { type: "input", id: "qty_total", name: "qty_total", label: "Tổng số lượng:", icon: "icon-input", className: "", required: true, validate: "NotEmpty", readonly: true },
                        { type: "input", id: "material_qty_total", name: "material_qty_total", label: "Tổng SL Material:", icon: "icon-input", className: "", required: true, validate: "NotEmpty", readonly: true },
                        
                        { type: "newcolumn", "offset": 20 },
                        { type: "calendar", id: "po_date", name: "po_date", label: "Ngày làm lệnh:", icon: "icon-input", dateFormat: "%Y-%m-%d", className: "", required: true, validate: "NotEmpty", style: "color:blue;" },
                        { type: "calendar", id: "ordered_date", name: "ordered_date", label: "Ordered date:", icon: "icon-input", dateFormat: "%Y-%m-%d", className: "", required: true, validate: "NotEmpty", readonly: true },
                        { type: "calendar", id: "request_date", name: "request_date", label: "Request date:", icon: "icon-input", dateFormat: "%Y-%m-%d", className: "", required: true, validate: "NotEmpty", readonly: true },
                        { type: "calendar", id: "promise_date", name: "promise_date", label: "Promise date:", icon: "icon-input", dateFormat: "%Y-%m-%d", className: "", required: true, validate: "NotEmpty", style: "color:blue;" },
                        { type: "select", name: "sample", label: "Sample", position: "label-left", offsetLeft: "0", style: "color:blue;", options: [
                            { text: "1", value: "1", "selected": true  },
                            { text: "2", value: "2"},
                            { text: "3", value: "3" }
                        ]},
                        { type: "input", id: "ink_qty_total", name: "ink_qty_total", label: "Tổng SL Ink:", icon: "icon-input", className: "", required: true, validate: "NotEmpty", readonly: true },
                        
                        { type: "newcolumn", "offset": 20 },
                        { type: "select", name: "po_no_suffix", label: "Loại đơn", position: "label-left", offsetLeft: "0", style: "color:blue;", width: 200, options: [
                            { text: "Đơn thường", value: "normal", "selected": true},
                            { text: "Fast Respone (FR)", value: "fr"  },
                            { text: "Urgent (UR)", value: "ur"  },
                        ]},
                        { type: "calendar", id: "data_received", name: "data_received", label: "Data Received:", icon: "icon-input", dateFormat: "%Y-%m-%d", className: "", style: "color:blue;"},
                        { type: "select", name: "po_file", label: "File", position: "label-left", offsetLeft: "0", style: "color:blue;", options: [
                            { text: "1", value: "1", "selected": true  },
                            { text: "2", value: "2&3"},
                            { text: "3", value: "In thêm" },
                            { text: "4", value: "Sample" }
                        ]},
                        { type: "input", id: "remark_1", name: "remark_1", label: "Remark 1:", width:260, icon: "icon-input", className: "", style: "color:blue;" },
                        { type: "input", id: "remark_2", name: "remark_2", label: "Remark 2:", width:260, icon: "icon-input", className: "", style: "color:blue;" },
                        { type: "input", id: "remark_3", name: "remark_3", label: "Remark 3:", width:260, icon: "icon-input", className: "", style: "color:blue;" },
                        { type: "input", id: "remark_4", name: "remark_4", label: "Remark 4:", width:260, icon: "icon-input", className: "", style: "color:blue;" },
                    ]
                },
                {
                    type: "fieldset", label: "Thông tin khách hàng", width: "auto", blockOffset: 0, offsetLeft: "20", offsetTop: "5",
                    list: [
                        { type: "settings", position: "label-left", labelWidth: 120, inputWidth: form_width_2, labelAlign: "left" },
                        { type: "input", id: "rbo", name: "rbo", label: "RBO:", icon: "icon-input", className: "", required: true, validate: "NotEmpty", readonly: true },
                        { type: "input", id: "bill_to_customer", name: "bill_to_customer", label: "Bill to:", icon: "icon-input", className: "", required: true, validate: "NotEmpty", readonly: true },
                        { type: "input", id: "ship_to_customer", name: "ship_to_customer", label: "Ship to:", icon: "icon-input", className: "", required: true, validate: "NotEmpty", readonly: true },
                    ]
                }

            ];
            orderForm = handleLayout.cells("b").attachForm(formStructure);	


            // set data
                loadOrderForm();

        }

        // load order info
        function initHandlePage(results) 
        {
            order_details = results.order_details;
            supply_details = results.supply_details;
            size_details = results.size_details;

            cbs = (order_details[0].cbs) ? Number(order_details[0].cbs) : 0 ;
            
            // init Layout
            initHandleLayout();
            
            // init Grid & Form
            initOrderGrid();
            initOrderForm();
            initSizeSupplyGrid();
        }


    /*
        | ------------------------------------------------------------------------------------------------------------
        | 4.  LOAD DATA
        | ------------------------------------------------------------------------------------------------------------
    */

        // load data to grid
        function loadMainViewGrid(from_date, to_date ) 
        {
            var suffix_url_views = '?from_date='+from_date+'&to_date='+to_date;
            var url = "<?php echo base_url('thermal/recent/'); ?>"+suffix_url_views;

            //excute with ajax function 
            $.ajax({
                type: "POST",
                data: { data: '' },
                url: url,
                dataType: 'json',
                beforeSend: function(x) { 
                    if (x && x.overrideMimeType) { x.overrideMimeType("application/j-son;charset=UTF-8"); }
                },
                success: function(results) {
                    
                    var data = {rows:[]};
            
                    for(var i = 0; i<results.length; i++ ){
                        data.rows.push(results[i]);
                    }

                    // load automail data to grid
                    mainviewGrid.parse(data,"json");
                },
                error: function(xhr, status, error) {
                    // alert(error);alert(xhr.responseText);
                    alert('Lỗi hiển thị dữ liệu. Vui lòng liên hệ quản trị hệ thống. ');
                    return false;
                }
            });
            
        }

        // load order
        function handle(so_line,po_no_edit=null) 
        {
            if (so_line ) setCookie('plan_order_type', 'common', 365);
            if (po_no_edit == null ) po_no_edit = '';
            
            var url_suffix = '?orders='+so_line+'&po_no_edit='+po_no_edit;
            location.href = '<?php echo base_url('thermal/handle'); ?>' + url_suffix;

        }

        // load from vnso or vnso_closed
        function loadOrderGrid() 
        {  
            if (!order_details.length ) {
                alert('Không lấy được thông tin đơn hàng');
            } else {
                var id = 0;
                for(var i = 0; i<order_details.length; i++ ){
                    var row = results.order_details[i];
                    
                    id++;
                    // set data
                    var index = row.index;
                    var order_number = row.order_number;
                    var line_number = row.line_number;
                    var so_line = order_number+'-'+line_number;
                    var qty = row.qty;
                    var item = row.item;
                    var item_desc = row.item_desc;
                    var ordered_item = row.ordered_item;
                    var rbo = row.rbo;
                    var length = row.length;
                    var width = row.width;
                
                    var cs = row.cs;
                    var bill_to_customer = row.bill_to_customer;
                    var ship_to_customer = row.ship_to_customer;
                    var ordered_date = row.ordered_date;
                    var request_date = row.request_date;
                    var promise_date = row.promise_date;
                    var order_type_name = row.order_type_name;
                    var flow_status_code = row.flow_status_code;
                    var production_method = row.production_method;
                    var packing_instructions = row.packing_instructions;
                    var packing_instr = row.packing_instr;
                    var attachment = row.attachment;
                   
                    // add to grid
                    orderGrid.addRow(id, [index, order_number, line_number, so_line, qty, item, item_desc, ordered_item, rbo, length, width, cs, bill_to_customer, ship_to_customer, ordered_date, request_date, promise_date, order_type_name, flow_status_code, production_method, packing_instructions, packing_instr, attachment ]);
                }

            }
            
        }

        // order form
        function loadOrderForm()
        {
            var fdata = order_details[0];
            var cbs = fdata.cbs;
            var size_check = (cbs == 1 ) ? 'Có Size' : 'Không có Size';

            orderForm.setItemValue('po_no_prefix', fdata.po_no_prefix );
            orderForm.setItemValue('size', size_check );
            orderForm.setItemValue('order_type_name', fdata.order_type_name );
            orderForm.setItemValue('cs', fdata.cs );
            orderForm.setItemValue('qty_total', fdata.qty_total );
            orderForm.setItemValue('material_qty_total', fdata.material_qty_total );
            orderForm.setItemValue('po_date', fdata.po_date );
            orderForm.setItemValue('ordered_date', fdata.ordered_date );
            orderForm.setItemValue('request_date', fdata.request_date );
            orderForm.setItemValue('promise_date', fdata.promise_date );
            orderForm.setItemValue('sample', fdata.sample );
            orderForm.setItemValue('ink_qty_total', fdata.ink_qty_total );

            orderForm.setItemValue('po_no_suffix', fdata.po_no_suffix );
            orderForm.setItemValue('data_received', fdata.data_received );
            orderForm.setItemValue('remark_1', fdata.remark_1 );
            orderForm.setItemValue('remark_2', fdata.remark_2 );
            orderForm.setItemValue('remark_3', fdata.remark_3 );
            orderForm.setItemValue('remark_4', fdata.remark_4 );

            orderForm.setItemValue('rbo', fdata.rbo );
            orderForm.setItemValue('bill_to_customer', fdata.bill_to_customer );
            orderForm.setItemValue('ship_to_customer', fdata.ship_to_customer );
        }

        function loadSizeSupplyGrid()
        {
            if (cbs == 1 ) {
                if (!size_details.length ) {
                    alert('Không lấy được thông tin Size');
                } else {
                    var id = 0;
                    for(var i = 0; i<size_details.length; i++ ){
                        var row = results.size_details[i];
                        
                        id++;
                        // set data
                        var index = row.index;
                        var so_line = row.so_line;
                        var size = row.size;
                        var color = row.color;
                        var qty = row.size_qty;
                        
                        var material_code = row.size_material_code;
                        var material_desc = row.size_material_desc;
                        var material_qty = row.size_material_qty;

                        var ink_code = row.size_ink_code;
                        var ink_desc = row.size_ink_desc;
                        var ink_qty = row.size_ink_qty;
                    
                        // add to grid
                        orderGrid.addRow(id, [index, so_line, size, color, qty, material_code, material_desc, material_qty, ink_code, ink_desc, ink_qty ]);
                    }

                }
            } else {
                if (!supply_details.length ) {
                    alert('Không lấy được thông tin Size');
                } else {
                    var id = 0;
                    for(var i = 0; i<supply_details.length; i++ ){
                        var row = results.supply_details[i];
                        
                        id++;
                        // set data
                        var index = row.index;
                        var so_line = row.so_line;
                        
                        var material_code = row.material_code;
                        var material_desc = row.material_desc;
                        var material_qty = row.material_qty;
                        var material_order = row.material_order;
                        var material_uom = row.material_uom;
                        var material_roll_qty_per_kit = row.material_roll_qty_per_kit;
                        var material_base_roll = row.material_base_roll;

                        var ink_code = row.ink_code;
                        var ink_desc = row.ink_desc;
                        var ink_qty = row.ink_qty;
                        var ink_order = row.ink_order;
                        var ink_uom = row.ink_uom;
                        var ink_roll_qty_per_kit = row.ink_roll_qty_per_kit;
                        var ink_base_roll = row.ink_base_roll;
                    
                        // add to grid
                        orderGrid.addRow(id, [index, so_line, material_code, material_desc, material_qty, material_order, material_uom, material_roll_qty_per_kit, material_base_roll, ink_code, ink_desc, ink_qty, ink_order, ink_uom, ink_roll_qty_per_kit, ink_base_roll ]);
                    }

                }

            }
            
        }

        // save
        function saveOrders()
        {
            
            // get form data
                var po_no_prefix = orderForm.getItemValue('po_no_prefix');
                var po_date = formatDate(orderForm.getItemValue('po_date') );
                var promise_date = formatDate(orderForm.getItemValue('promise_date') );
                var sample = orderForm.getItemValue('sample');
                var po_no_suffix = orderForm.getItemValue('po_no_suffix');
                var data_received = formatDate(orderForm.getItemValue('data_received') );
                var po_file = orderForm.getItemValue('po_file');
                var remark_1 = orderForm.getItemValue('remark_1');
                var remark_2 = orderForm.getItemValue('remark_2');
                var remark_3 = orderForm.getItemValue('remark_3');
                var remark_4 = orderForm.getItemValue('remark_4');

            // set data to save
                var formData = {
                    po_no_prefix : po_no_prefix,
                    po_date : po_date,
                    promise_date : promise_date,
                    sample : sample,
                    po_no_suffix : po_no_suffix,
                    data_received : data_received,
                    po_file : po_file,
                    remark_1 : remark_1,
                    remark_2 : remark_2,
                    remark_3 : remark_3,
                    remark_4 : remark_4
                }

            // save to server
                var jsonObjects = { formData : formData, results : results }
            // url to server
                var url = '<?php echo base_url($production_line ."/saveOrders/"); ?>';

            //excute with ajax function 
            $.ajax({
                type: "POST",
                data: { data: JSON.stringify(jsonObjects) },
                url: url,
                dataType: 'json',
                beforeSend: function(x) { if (x && x.overrideMimeType) { x.overrideMimeType("application/j-son;charset=UTF-8"); } },
                success: function(data) {

                    try{        
                        if (data.status == false) {
                            alert(data.message);
                            return false;
                        } else {
                            var is_print = confirm(data.message);
                            if (is_print) {
                                printOrders(data.po_no);
                            } else {
                                window.location ='<?php echo base_url($production_line ); ?>';
                            } 
                        } 
                    }catch(e) {     
                        alert('Lỗi khi in. Vui lòng liên hệ quản trị hệ thống. Lỗi: '+e);
                        return false;
                    }
                },
                error: function(xhr, status, error) {
                    // alert(error);alert(xhr.responseText);
                    alert('Lưu dữ liệu không thành công: Vui lòng liên hệ quản trị hệ thống. '+xhr.responseText);
                    return false;
                }
            });


        }

        // print
        function printOrders(po_no) 
        {
            var wi = window.open('about:blank', '_blank');
            wi.window.location ='<?php echo base_url("thermal/printOrders/"); ?>'+po_no;
            window.location ='<?php echo base_url($production_line ); ?>';
        }

        // report order data 
        function reportOrders(from_date, to_date, form_type ) 
        {
            var suffix_url = '?from_date=' + from_date + '&to_date=' + to_date + '&form_type=' + form_type;
            location.href = "<?php echo base_url(get_cookie('plan_department')); ?>" + "/reportOrders/" + suffix_url ;
        }

    /*
        | ------------------------------------------------------------------------------------------------------------
        | MASTER DATA
        | ------------------------------------------------------------------------------------------------------------
    */
        var dhxWins2;

        // onload ------------------------------------------------------------------------------------------------------
            function doloadMasterFile() 
            {
                masterFileLayout();

            }

        // init ------------------------------------------------------------------------------------------------------

            // layout
            function masterFileLayout() 
            {
                // init layout
                    masterfileLayout = new dhtmlXLayoutObject({
                        parent: document.body,
                        pattern: "2U",
                        offsets: {
                            top: 30,
                            left:2,
                            right:2,
                            bottom: 5
                        },
                        cells: [
                            {id: "a", header: true, text: "Data Grid" },
                            {id: "b", header: true, text: "Form Edit", width: 770}
                        ]
                    });

                // menu
                    masterFileMenu();

            }

            // menu
            function masterFileMenu() 
            {
                // init menu
                    master_data_menu = new dhtmlXMenuObject({
                        parent: "mainMenu",
                        iconset: "awesome",
                        json: "<?php echo base_url('assets/xml/thermal_master_data_menu.xml'); ?>",
                        top_text: "<?php echo "<img style='width:60px;' src='". base_url('assets/media/images/Logo.PNG') ."'/>&nbsp;&nbsp;&nbsp; THERMAL MASTER DATA "; ?>"
                    });
                
                // align
                    master_data_menu.setAlign("right");


                // master default
                    master = 'views';

                // default
                    masterFileGrid(master); // grid
                    masterFileForm(master); // form
                    
                // attach menu
                master_data_menu.attachEvent("onClick", function(id){

                    console.log('per: ' + getCookie('permission'));
                    if (getCookie('permission') != "AD02"  ) {
                        console.log('N');
                    } else {
                        console.log('Y');
                    }
                    if (getCookie('permission') == "AD02" || (getCookie('permission') == "SU01" ) ) {

                        if(id !== "home") {
                            master = id;
                            console.log('master: ' + master);
                            if (id == 'views' || id == 'views_material' ) {
                                
                                masterFileGrid(master); // grid
                                masterFileForm(master); // form

                            } else if (id == 'add_item' ) {

                                createMasterFile('masterfile');

                            } else if (id == 'imports' ) {
                                
                                importMasterFile();

                            } else if (id == 'exports' ) {

                            } else if (id == 'sample_file' ) {
                                window.open('https://docs.google.com/spreadsheets/d/1wG9H_o3uAAY7ttuQ6m4am2plkw_ZyFRyl3PubtwpWy8/edit#gid=0' , '_blank');

                            } else if (id == 'add_material' ) {
                                
                                createMasterFile('material');

                            } else if (id == 'come_back_planning' ) {

                                location.href = "<?php echo base_url($production_line); ?>";

                            } 

                        } else {
                            location.href = "<?php echo base_url($production_line); ?>"+"/masterFile";
                        }

                    } else {
                        alert('Bạn không có quyền truy cập chức năng này !!');
                        location.href = "<?php echo base_url($production_line); ?>"; 
                    }
                    
                });

            }

            

            // master data: update main master grid
            function masterFileGrid(master) 
            {
                masterfileLayout.cells("a").progressOn();
                masterfileGrid = masterfileLayout.cells("a").attachGrid();

                if (master == 'views' ) {
                    masterfileLayout.cells("a").setText("<span style='color:red;'>MASTER FILE</span>");

                    masterfileGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>" );
                    masterfileGrid.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
                    masterfileGrid.setRowTextStyle("1", "background-color: red; font-family: arial;");
                    masterfileGrid.init();

                    masterfileGrid.enableSmartRendering(true); // false to disable
                    
                    masterfileGrid.loadXML("<?php echo base_url($production_line . '/loadMasterFile') ?>",function(){
                        masterfileLayout.cells("a").progressOff();
                        loadMasterFileForm(master);
                    });

                } else if (master == 'views_material' ) {

                    masterfileLayout.cells("a").setText("<span style='color:red;'>MATERIAL</span>");

                    masterfileGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>" );
                    masterfileGrid.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
                    masterfileGrid.setRowTextStyle("1", "background-color: red; font-family: arial;");
                    masterfileGrid.init();

                    masterfileGrid.enableSmartRendering(true); // false to disable
                    
                    masterfileGrid.loadXML("<?php echo base_url($production_line . '/loadMasterMaterial') ?>",function(){
                        masterfileLayout.cells("a").progressOff();
                        loadMasterFileForm(master);
                    });
                }

                

            }

            var dhxWins;
            function importMasterFile() 
            {
                
                if(!dhxWins){ dhxWins= new dhtmlXWindows(); }

                var id = "WindowsDetail";
                var w = 400;
                var h = 100;
                var x = Number(($(window).width()-400)/2);
                var y = Number(($(window).height()-50)/2);
                var Popup = dhxWins.createWindow(id, x, y, w, h);
                dhxWins.window(id).setText("Import Special Item");
                Popup.attachHTMLString(
                    '<div style="width:500%;margin:20px">' +
                        '<form action="<?php echo base_url($production_line.'/importMasterFile'); ?>" enctype="multipart/form-data" method="post" accept-charset="utf-8">' +
                            '<input type="file" name="file" id="file" class="form-control filestyle" value="value" data-icon="false"  />' +
                            '<input type="submit" name="importfile" value="Import" id="importfile-id" class="btn btn-block btn-primary"  />' +
                        '</form>' +
                    '</div>'
                );
            }

            function formStruct(add=false )
            {
                var struct = [];

                // save to server
                var jsonObjects = {}
                // url to server
                    var url = '<?php echo base_url($production_line ."/getMachine"); ?>';

                //excute with ajax function 
                $.ajax({
                    type: "POST",
                    data: { data: JSON.stringify(jsonObjects) },
                    url: url,
                    dataType: 'json',
                    beforeSend: function(x) { if (x && x.overrideMimeType) { x.overrideMimeType("application/j-son;charset=UTF-8"); } },
                    success: function(data) {
                        try{        
                            if (data.status == true ) {
                                // machine list
                                    var machine_json = data.machine_json;

                                // struct
                                    if (add == false ) {
                                        struct = [
                                            { type: "settings", position: "label-left", labelWidth: 150, inputWidth: 250 },
                                            {
                                                type: "fieldset", label: "Master File", width: 720, blockOffset: 10, offsetLeft: 30, offsetTop: 30,
                                                list: [
                                                    { type: "settings", position: "label-left", labelWidth: 120, inputWidth: 200, labelAlign: "left" },
                                                    
                                                    { type: "input", id: "form_type", name: "form_type", label: "Form Type:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                                    { type: "input", id: "internal_item", name: "internal_item", label: "Internal Item:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                                    { type: "input", id: "rbo", name: "rbo", label: "RBO:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                                    { type: "input", id: "rbo_remark", name: "rbo_remark", label: "RBO Remark:", icon: "icon-input" },
                                                    { type: "input", id: "kind_of_label", name: "kind_of_label", label: "Loại con nhãn:", icon: "icon-input" },
                                                    
                                                    { type: "input", id: "length", name: "length", label: "Dài (length):", icon: "icon-input", className: "", required: true, validate: "ValidNumeric" },
                                                    { type: "input", id: "width", name: "width", label: "Rộng (width):", icon: "icon-input", className: "", required: true, validate: "ValidNumeric" },
                                                    { type: "input", id: "unit", name: "unit", label: "Đơn vị:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                                    { type: "input", id: "ups", name: "ups", label: "UPS:", icon: "icon-input", validate: "ValidInteger" },
                                                    { type: "select", id: "cbs", name: "cbs", label: "Size:", style: "color:blue; ", options: [
                                                        { value: 0, text: "Không Có Size", selected:true },
                                                        { value: 1, text: "Có Size" }
                                                    ]},
                                                    
                                                    { type: "input", id: "gap", name: "gap", label: "GAP:", icon: "icon-input", validate: "ValidInteger" },
                                                    { type: "input", id: "site_printing", name: "site_printing", label: "Số mặt in:", icon: "icon-input" },
                                                    { type: "select", id: "machine", name: "machine", label: "Machine:", style: "color:blue;", required: true, validate: "NotEmpty", options: machine_json },
                                                    { type: "input", id: "format", name: "format", label: "Format:", icon: "icon-input" },
                                                    { type: "input", id: "standard_speed", name: "standard_speed", label: "Standard speed:", icon: "icon-input" },
                                                    
                                                    { type: "input", id: "speed_unit", name: "speed_unit", label: "Speed unit:", icon: "icon-input" },
                                                    { type: "input", id: "cutter", name: "cutter", label: "cutter:", icon: "icon-input" },
                                                    { type: "input", id: "security", name: "security", label: "Security:", icon: "icon-input" },
                                                    { type: "input", id: "fg_ipps", name: "fg_ipps", label: "FG IPPS:", icon: "icon-input" },
                                                    { type: "input", id: "pcs_set", name: "pcs_set", label: "PCS SET:", icon: "icon-input", validate: "ValidInteger" },
                                                    
                                                    { type: "newcolumn", "offset": 20 },

                                                    { type: "input", id: "scrap", name: "scrap", label: "Scrap:", icon: "icon-input", validate: "ValidNumeric" },
                                                    { type: "input", id: "chieu_in_thuc_te", name: "chieu_in_thuc_te", label: "Chiều in thực tế:", icon: "icon-input", validate: "ValidInteger" },
                                                    { type: "input", id: "layout_prepress", name: "layout_prepress", label: "Layout prepress:", icon: "icon-input", validate: "ValidInteger" },
                                                    { type: "input", id: "material_code", name: "material_code", label: "Material Code:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                                    { type: "input", id: "material_desc", name: "material_desc", label: "Material Desc:", icon: "icon-input" },
                                                    
                                                    { type: "input", id: "material_order", name: "material_order", label: "Material order:", icon: "icon-input" },
                                                    { type: "input", id: "material_uom", name: "material_uom", label: "Material UOM:", icon: "icon-input", required: true, validate: "NotEmpty" },
                                                    { type: "input", id: "material_roll_qty_per_kit", name: "material_roll_qty_per_kit", label: "Material số Roll/KIT:", icon: "icon-input" },
                                                    { type: "input", id: "material_baseroll", name: "material_baseroll", label: "Material baseroll:", icon: "icon-input" },
                                                    { type: "input", id: "ink_code", name: "ink_code", label: "Ink Code:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },

                                                    { type: "input", id: "ink_desc", name: "ink_desc", label: "Ink Desc:", icon: "icon-input" },
                                                    { type: "input", id: "ink_order", name: "ink_order", label: "Ink order:", icon: "icon-input" },
                                                    { type: "input", id: "ink_uom", name: "ink_uom", label: "Ink UOM:", icon: "icon-input" },
                                                    { type: "input", id: "ink_roll_qty_per_kit", name: "ink_roll_qty_per_kit", label: "Ink số MT/KIT:", icon: "icon-input" },
                                                    { type: "input", id: "ink_baseroll", name: "ink_baseroll", label: "Ink baseroll:", icon: "icon-input" },

                                                    { type: "input", id: "remark_1", name: "remark_1", label: "Remark 1:", icon: "icon-input" },
                                                    { type: "input", id: "remark_2", name: "remark_2", label: "Remark 2:", icon: "icon-input" },
                                                    { type: "input", id: "remark_3", name: "remark_3", label: "Remark 3:", icon: "icon-input" },
                                                    { type: "input", id: "remark_4", name: "remark_4", label: "Remark 4:", icon: "icon-input" }

                                                    

                                                ]
                                            }, 
                                            { type: "fieldset", label: "Chọn chức năng", width: 720, blockOffset: 10, offsetLeft: 30, offsetTop: 5,
                                                list: [
                                                    { type: "button", id: "update", name: "update", value: "<span style='color:red;font-weight:bold;'>Cập nhật</span>", position: "label-center", width: 150, offsetLeft: 100 },
                                                    { type: "newcolumn", "offset": 50 },
                                                    { type: "button", id: "delete", name: "delete", value: "<span style='color:#cc0000;font-weight:bold;'>Xóa</span>", position: "label-center", width: 150, offsetLeft: 100 }
                                                ]
                                            }
                                        ];
                                    } else {
                                        struct = [
                                            { type: "settings", position: "label-left", labelWidth: 150, inputWidth: 250 },
                                            {
                                                type: "fieldset", label: "Master File", width: 720, blockOffset: 10, offsetLeft: 30, offsetTop: 30,
                                                list: [
                                                    { type: "settings", position: "label-left", labelWidth: 120, inputWidth: 200, labelAlign: "left" },
                                                    
                                                    { type: "input", id: "form_type", name: "form_type", label: "Form Type:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                                    { type: "input", id: "internal_item", name: "internal_item", label: "Internal Item:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                                    { type: "input", id: "rbo", name: "rbo", label: "RBO:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                                    { type: "input", id: "rbo_remark", name: "rbo_remark", label: "RBO Remark:", icon: "icon-input" },
                                                    { type: "input", id: "kind_of_label", name: "kind_of_label", label: "Loại con nhãn:", icon: "icon-input" },
                                                    
                                                    { type: "input", id: "length", name: "length", label: "Dài (length):", icon: "icon-input", className: "", required: true, validate: "ValidNumeric" },
                                                    { type: "input", id: "width", name: "width", label: "Rộng (width):", icon: "icon-input", className: "", required: true, validate: "ValidNumeric" },
                                                    { type: "input", id: "unit", name: "unit", label: "Đơn vị:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                                    { type: "input", id: "ups", name: "ups", label: "UPS:", icon: "icon-input", validate: "ValidInteger" },
                                                    { type: "select", id: "cbs", name: "cbs", label: "Size:", style: "color:blue; ", options: [
                                                        { value: 0, text: "Không Có Size", selected:true },
                                                        { value: 1, text: "Có Size" }
                                                    ]},
                                                    
                                                    { type: "input", id: "gap", name: "gap", label: "GAP:", icon: "icon-input", validate: "ValidInteger" },
                                                    { type: "input", id: "site_printing", name: "site_printing", label: "Số mặt in:", icon: "icon-input" },
                                                    { type: "select", id: "machine", name: "machine", label: "Machine:", style: "color:blue;", required: true, validate: "NotEmpty", options: machine_json },
                                                    { type: "input", id: "format", name: "format", label: "Format:", icon: "icon-input" },
                                                    { type: "input", id: "standard_speed", name: "standard_speed", label: "Standard speed:", icon: "icon-input" },
                                                    
                                                    { type: "input", id: "speed_unit", name: "speed_unit", label: "Speed unit:", icon: "icon-input" },
                                                    { type: "input", id: "cutter", name: "cutter", label: "cutter:", icon: "icon-input" },
                                                    { type: "input", id: "security", name: "security", label: "Security:", icon: "icon-input" },
                                                    { type: "input", id: "fg_ipps", name: "fg_ipps", label: "FG IPPS:", icon: "icon-input" },
                                                    { type: "input", id: "pcs_set", name: "pcs_set", label: "PCS SET:", icon: "icon-input", validate: "ValidInteger" },
                                                    
                                                    { type: "newcolumn", "offset": 20 },

                                                    { type: "input", id: "scrap", name: "scrap", label: "Scrap:", icon: "icon-input", validate: "ValidNumeric" },
                                                    { type: "input", id: "chieu_in_thuc_te", name: "chieu_in_thuc_te", label: "Chiều in thực tế:", icon: "icon-input", validate: "ValidInteger" },
                                                    { type: "input", id: "layout_prepress", name: "layout_prepress", label: "Layout prepress:", icon: "icon-input", validate: "ValidInteger" },
                                                    { type: "input", id: "material_code", name: "material_code", label: "Material Code:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                                    { type: "input", id: "material_desc", name: "material_desc", label: "Material Desc:", icon: "icon-input" },
                                                    
                                                    { type: "input", id: "material_order", name: "material_order", label: "Material order:", icon: "icon-input", value: 1 },
                                                    { type: "input", id: "material_uom", name: "material_uom", label: "Material UOM:", icon: "icon-input", required: true, validate: "NotEmpty" },
                                                    { type: "input", id: "material_roll_qty_per_kit", name: "material_roll_qty_per_kit", label: "Material số Roll/KIT:", icon: "icon-input" },
                                                    { type: "input", id: "material_baseroll", name: "material_baseroll", label: "Material baseroll:", icon: "icon-input" },
                                                    { type: "input", id: "ink_code", name: "ink_code", label: "Ink Code:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },

                                                    { type: "input", id: "ink_desc", name: "ink_desc", label: "Ink Desc:", icon: "icon-input" },
                                                    { type: "input", id: "ink_order", name: "ink_order", label: "Ink order:", icon: "icon-input", value: 1 },
                                                    { type: "input", id: "ink_uom", name: "ink_uom", label: "Ink UOM:", icon: "icon-input" },
                                                    { type: "input", id: "ink_roll_qty_per_kit", name: "ink_roll_qty_per_kit", label: "Ink số MT/KIT:", icon: "icon-input" },
                                                    { type: "input", id: "ink_baseroll", name: "ink_baseroll", label: "Ink baseroll:", icon: "icon-input" },

                                                    { type: "input", id: "remark_1", name: "remark_1", label: "Remark 1:", icon: "icon-input" },
                                                    { type: "input", id: "remark_2", name: "remark_2", label: "Remark 2:", icon: "icon-input" },
                                                    { type: "input", id: "remark_3", name: "remark_3", label: "Remark 3:", icon: "icon-input" },
                                                    { type: "input", id: "remark_4", name: "remark_4", label: "Remark 4:", icon: "icon-input" }

                                                    

                                                ]
                                            }, 
                                            { type: "fieldset", label: "Chọn chức năng", width: 720, blockOffset: 10, offsetLeft: 30, offsetTop: 5,
                                                list: [
                                                    { type: "button", id: "create", name: "create", value: "<span style='color:red;font-weight:bold;'>Save</span>", position: "label-center", width: 150, offsetLeft: 300 }
                                                ]
                                            }
                                        ];
                                    }
                                    
                            } 
                        }catch(e) {     
                            struct = [];
                        }
                    },
                    error: function(xhr, status, error) { struct = []; },
                    async: false
                });

                return struct;
            }

            function formStructMaterial(add=false )
            {
                var struct = [];

                if (add == false ) {
                    struct = [
                        { type: "settings", position: "label-left", labelWidth: 150, inputWidth: 250 },
                        {
                            type: "fieldset", label: "Master File", width: 720, blockOffset: 10, offsetLeft: 30, offsetTop: 30,
                            list: [
                                { type: "settings", position: "label-left", labelWidth: 140, inputWidth: 180, labelAlign: "left" },
                                
                                { type: "input", id: "internal_item", name: "internal_item", label: "Internal Item:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                { type: "input", id: "code_name", name: "code_name", label: "Tên Vật tư/Mực:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                { type: "input", id: "order", name: "order", label: "Thứ tự:", icon: "icon-input", validate: "ValidInteger" },
                                { type: "input", id: "descriptions", name: "descriptions", label: "Mô tả:", icon: "icon-input" },

                                { type: "newcolumn", "offset": 20 },

                                { type: "select", id: "code_type", name: "code_type", label: "Loại:", style: "color:blue; ", required: true, validate: "NotEmpty", options: [
                                    { value: "", text: "Chọn loại", selected: true },
                                    { value: 'ink', text: "ink" },
                                    { value: 'material', text: "material" }
                                ]},
                                { type: "input", id: "uom", name: "uom", label: "UOM:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                { type: "input", id: "roll_qty_per_kit", name: "roll_qty_per_kit", label: "Số Roll/KIT (MT/KIT):", icon: "icon-input", validate: "ValidInteger" },
                                { type: "input", id: "base_roll", name: "base_roll", label: "Baseroll:", icon: "icon-input", validate: "ValidInteger" }

                            ]
                        }, 
                        { type: "fieldset", label: "Chọn chức năng", width: 720, blockOffset: 10, offsetLeft: 30, offsetTop: 5,
                            list: [
                                { type: "button", id: "update", name: "update", value: "<span style='color:red;font-weight:bold;'>Cập nhật</span>", position: "label-center", width: 150, offsetLeft: 100 },
                                { type: "newcolumn", "offset": 50 },
                                { type: "button", id: "delete", name: "delete", value: "<span style='color:#cc0000;font-weight:bold;'>Xóa</span>", position: "label-center", width: 150, offsetLeft: 100 }
                            ]
                        }
                    ];
                } else {
                    
                    struct = [
                        { type: "settings", position: "label-left", labelWidth: 150, inputWidth: 250 },
                        {
                            type: "fieldset", label: "Master File", width: 720, blockOffset: 10, offsetLeft: 30, offsetTop: 30,
                            list: [
                                { type: "settings", position: "label-left", labelWidth: 140, inputWidth: 180, labelAlign: "left" },
                                
                                { type: "input", id: "internal_item", name: "internal_item", label: "Internal Item:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                { type: "input", id: "code_name", name: "code_name", label: "Tên Vật tư/Mực:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                { type: "input", id: "order", name: "order", label: "Thứ tự:", icon: "icon-input", required: true, validate: "ValidInteger" },
                                { type: "input", id: "descriptions", name: "descriptions", label: "Mô tả:", icon: "icon-input" },

                                { type: "newcolumn", "offset": 20 },

                                { type: "select", id: "code_type", name: "code_type", label: "Loại:", style: "color:blue; ", required: true, validate: "NotEmpty", options: [
                                    { value: "", text: "Chọn loại", selected: true },
                                    { value: 'ink', text: "ink" },
                                    { value: 'material', text: "material" }
                                ]},
                                { type: "input", id: "uom", name: "uom", label: "UOM:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },
                                { type: "input", id: "roll_qty_per_kit", name: "roll_qty_per_kit", label: "Số Roll/KIT (MT/KIT):", icon: "icon-input", validate: "ValidInteger" },
                                { type: "input", id: "base_roll", name: "base_roll", label: "Baseroll:", icon: "icon-input", validate: "ValidInteger" }

                            ]
                        }, 
                        { type: "fieldset", label: "Chọn chức năng", width: 720, blockOffset: 10, offsetLeft: 30, offsetTop: 5,
                            list: [
                                { type: "button", id: "create", name: "create", value: "<span style='color:red;font-weight:bold;'>Save</span>", position: "label-center", width: 150, offsetLeft: 300 }
                            ]
                        }
                    ];
                }

                return struct;
                
            }

            // form 
            function masterFileForm(master) 
            {
                var masterFileFormStruct;

                // load struct form
                    if (master == 'views' ) {
                        
                        masterFileFormStruct = formStruct();

                    } else if (master == 'views_material' ) {
                        
                        masterFileFormStruct = formStructMaterial();

                    }
                

                // init 
                    masterfileForm = masterfileLayout.cells("b").attachForm(masterFileFormStruct);
                                        
                // Validation live
                    masterfileForm.enableLiveValidation(true);

                // attach button
                    masterfileForm.attachEvent("onButtonClick", function(name){	   
                        if (name == 'update' ) {

                            if (master == 'views' ) {
                                var link_suf = '/updateMasterFile';
                            } else if (master == 'views_material' ) {
                                var link_suf = '/updateMasterMaterial';
                            }

                            masterfileForm.send("<?php echo base_url($production_line ); ?>"+link_suf, "post", function(test,res){

                                // parse json to object
                                    var obj = JSON.parse(res);
                                // alert
                                    alert(obj.message);
                                // redirect
                                    location.href = '<?php echo base_url($production_line . "/masterFile") ?>';

                            });	

                        } else if (name == 'delete' ) {
                            var internal_item = masterfileForm.getItemValue('internal_item');
                            var length_btp = masterfileForm.getItemValue('length_btp');
                            var del_type = 'mainMaster';
                            // deleteMasterData(del_type, machine_type, internal_item, length_btp);
                        }    
                                
                    });
                
            }

            // form
            function loadMasterFileForm(master) 
            {

                if(masterfileGrid.getRowsNum() ) {
                    console.log('here');

                    // select row 0
                        masterfileGrid.selectRow(0, true);

                    // attach row select
                        masterfileGrid.attachEvent("onRowSelect", function(rId,ind){

                            if (master == 'views' ) {

                                // get data
                                    var form_type = masterfileGrid.cells(rId,1).getValue().trim();
                                    var internal_item = masterfileGrid.cells(rId,2).getValue().trim();
                                    var rbo = masterfileGrid.cells(rId,3).getValue().trim();
                                    var rbo_remark = masterfileGrid.cells(rId,4).getValue().trim();
                                    var kind_of_label = masterfileGrid.cells(rId,5).getValue().trim();

                                    var length = masterfileGrid.cells(rId,6).getValue().trim();
                                    var width = masterfileGrid.cells(rId,7).getValue().trim();
                                    var unit = masterfileGrid.cells(rId,8).getValue().trim();
                                    var ups = masterfileGrid.cells(rId,9).getValue().trim();
                                    var cbs = masterfileGrid.cells(rId,10).getValue().trim();

                                    var gap = masterfileGrid.cells(rId,11).getValue().trim();
                                    var site_printing = masterfileGrid.cells(rId,12).getValue().trim();
                                    var machine = masterfileGrid.cells(rId,13).getValue().trim();
                                    var format = masterfileGrid.cells(rId,14).getValue().trim();
                                    var standard_speed = masterfileGrid.cells(rId,15).getValue().trim();

                                    var speed_unit = masterfileGrid.cells(rId,16).getValue().trim();
                                    var cutter = masterfileGrid.cells(rId,17).getValue().trim();
                                    var security = masterfileGrid.cells(rId,18).getValue().trim();
                                    var fg_ipps = masterfileGrid.cells(rId,19).getValue().trim();
                                    var pcs_set = masterfileGrid.cells(rId,20).getValue().trim();

                                    var scrap = masterfileGrid.cells(rId,21).getValue().trim();
                                    var chieu_in_thuc_te = masterfileGrid.cells(rId,22).getValue().trim();
                                    var layout_prepress = masterfileGrid.cells(rId,23).getValue().trim();
                                    var material_code = masterfileGrid.cells(rId,24).getValue().trim();
                                    var material_desc = masterfileGrid.cells(rId,25).getValue().trim();

                                    var material_order = masterfileGrid.cells(rId,26).getValue().trim();
                                    var material_uom = masterfileGrid.cells(rId,27).getValue().trim();
                                    var material_roll_qty_per_kit = masterfileGrid.cells(rId,28).getValue().trim();
                                    var material_baseroll = masterfileGrid.cells(rId,29).getValue().trim();
                                    var ink_code = masterfileGrid.cells(rId,30).getValue().trim();

                                    var ink_desc = masterfileGrid.cells(rId,31).getValue().trim();
                                    var ink_order = masterfileGrid.cells(rId,32).getValue().trim();
                                    var ink_uom = masterfileGrid.cells(rId,33).getValue().trim();
                                    var ink_roll_qty_per_kit = masterfileGrid.cells(rId,34).getValue().trim();
                                    var ink_baseroll = masterfileGrid.cells(rId,35).getValue().trim();

                                    var remark_1 = masterfileGrid.cells(rId,36).getValue().trim();
                                    var remark_2 = masterfileGrid.cells(rId,37).getValue().trim();
                                    var remark_3 = masterfileGrid.cells(rId,38).getValue().trim();
                                    var remark_4 = masterfileGrid.cells(rId,39).getValue().trim();


                                // set form data
                                    masterfileForm.setItemValue('form_type', form_type );
                                    masterfileForm.setItemValue('internal_item', internal_item );
                                    masterfileForm.setItemValue('rbo', rbo );
                                    masterfileForm.setItemValue('rbo_remark', rbo_remark );
                                    masterfileForm.setItemValue('kind_of_label', kind_of_label );

                                    masterfileForm.setItemValue('length', length );
                                    masterfileForm.setItemValue('width', width );
                                    masterfileForm.setItemValue('unit', unit );
                                    masterfileForm.setItemValue('ups', ups );
                                    masterfileForm.setItemValue('cbs', cbs );

                                    masterfileForm.setItemValue('gap', gap );
                                    masterfileForm.setItemValue('site_printing', site_printing );
                                    masterfileForm.setItemValue('machine', machine );
                                    masterfileForm.setItemValue('format', format );
                                    masterfileForm.setItemValue('standard_speed', standard_speed );

                                    masterfileForm.setItemValue('speed_unit', speed_unit );
                                    masterfileForm.setItemValue('cutter', cutter );
                                    masterfileForm.setItemValue('security', security );
                                    masterfileForm.setItemValue('fg_ipps', fg_ipps );
                                    masterfileForm.setItemValue('pcs_set', pcs_set );

                                    masterfileForm.setItemValue('scrap', scrap );
                                    masterfileForm.setItemValue('chieu_in_thuc_te', chieu_in_thuc_te );
                                    masterfileForm.setItemValue('layout_prepress', layout_prepress );
                                    masterfileForm.setItemValue('material_code', material_code );
                                    masterfileForm.setItemValue('material_desc', material_desc );

                                    masterfileForm.setItemValue('material_order', material_order );
                                    masterfileForm.setItemValue('material_uom', material_uom );
                                    masterfileForm.setItemValue('material_roll_qty_per_kit', material_roll_qty_per_kit );
                                    masterfileForm.setItemValue('material_baseroll', material_baseroll );
                                    masterfileForm.setItemValue('ink_code', ink_code );

                                    masterfileForm.setItemValue('ink_desc', ink_desc );
                                    masterfileForm.setItemValue('ink_order', ink_order );
                                    masterfileForm.setItemValue('ink_uom', ink_uom );
                                    masterfileForm.setItemValue('ink_roll_qty_per_kit', ink_roll_qty_per_kit );
                                    masterfileForm.setItemValue('ink_baseroll', ink_baseroll );

                                    masterfileForm.setItemValue('remark_1', remark_1 );
                                    masterfileForm.setItemValue('remark_2', remark_2 );
                                    masterfileForm.setItemValue('remark_3', remark_3 );
                                    masterfileForm.setItemValue('remark_4', remark_4 );

                                // set read only
                                    masterfileForm.setReadonly("form_type", true);
                                    masterfileForm.setReadonly("internal_item", true);

                            } else if (master == 'views_material' ) {

                                // get data
                                    var internal_item = masterfileGrid.cells(rId,1).getValue().trim();
                                    var code_name = masterfileGrid.cells(rId,2).getValue().trim();
                                    var order = masterfileGrid.cells(rId,3).getValue().trim();
                                    var descriptions = masterfileGrid.cells(rId,4).getValue().trim();
                                    var code_type = masterfileGrid.cells(rId,5).getValue().trim();

                                    var uom = masterfileGrid.cells(rId,6).getValue().trim();
                                    var roll_qty_per_kit = masterfileGrid.cells(rId,7).getValue().trim();
                                    var base_roll = masterfileGrid.cells(rId,8).getValue().trim();

                                // set form data
                                    masterfileForm.setItemValue('internal_item', internal_item );
                                    masterfileForm.setItemValue('code_name', code_name );
                                    masterfileForm.setItemValue('order', order );
                                    masterfileForm.setItemValue('descriptions', descriptions );
                                    masterfileForm.setItemValue('code_type', code_type );

                                    masterfileForm.setItemValue('uom', uom );
                                    masterfileForm.setItemValue('roll_qty_per_kit', roll_qty_per_kit );
                                    masterfileForm.setItemValue('base_roll', base_roll );
                                    

                                // set read only
                                    masterfileForm.setReadonly("internal_item", true);
                                    masterfileForm.setReadonly("code_name", true);
                                    masterfileForm.setReadonly("order", true);

                            }

                                
                        });
                };
                
            }

            // create master file
            function createMasterFile(master)
            {
                // close if exist
                    if(dhxWins2){ dhxWins2.window("WindowsDetail").close(); }

                // create
                    dhxWins2= new dhtmlXWindows(); 

                if (!dhxWins2.isWindow("WindowsDetail")){

                    
                    // setup
                        var id = "WindowsDetail";
                        var w = 890;
                        var h = 850;
                        var x = Number(($(window).width()-w)/2);
                        var y = Number(($(window).height()-h)/2);
                        var Popup = dhxWins2.createWindow(id, x, y, w, h);

                    // set name
                        dhxWins2.window(id).setText("Tạo Item mới ");
                        
                    // attach form
                        createMasterDataForm = Popup.attachForm();

                    // close
                        Popup.attachEvent("onClose", function(win){ if (win.getId() == "WindowsDetail") win.hide(); });

                    // struct
                        if (master == 'masterfile' ) {
                            formStructMaster = formStruct(true);
                            var link_suf = '/createMasterFile';
                        } else if (master == 'material' ) {
                            formStructMaster = formStructMaterial(true);
                            var link_suf = '/createMasterMaterial';
                        }

                    // link
                        var link = "<?php echo base_url($production_line); ?>"+link_suf;
                        console.log('link: ' + link);
                        

                    // load struct
                        createMasterDataForm.loadStruct(formStructMaster);

                    // Validation live: 
                        createMasterDataForm.enableLiveValidation(true);

                    // set read only
                        if (master == 'masterfile' ) {
                            masterfileForm.setReadonly("material_order", true);
                            masterfileForm.setReadonly("ink_order", true);
                        }
                    

                    // sent post
                        createMasterDataForm.attachEvent("onButtonClick", function(name){	   
                            if (name == 'create' ) {
                                createMasterDataForm.send(link,"post",function(test,res){
                                    // parse json to object
                                    var obj = JSON.parse(res);

                                    if(obj.status == true){
                                        alert(obj.message);
                                        location.href = '<?php echo base_url($production_line ."/masterFile"); ?>';
                                    }else{
                                        alert(obj.message);
                                    }
                                });	

                            }       
                                    
                        });

                    
                } else {
                    dhxWins2.window("WindowsDetail").show(); 
                }

            }

            // material
            function material()
            {

            }
        
    

    

</script>