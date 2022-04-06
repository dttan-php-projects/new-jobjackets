<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
$production_line = null !== get_cookie('plan_department') ? get_cookie('plan_department') : 'htl';
$plan_account_type = null !== get_cookie('plan_account_type') ? get_cookie('plan_account_type') : 1;
$username = null !== get_cookie('plan_loginUser') ? get_cookie('plan_loginUser') : '';
$form_type = null !== get_cookie('plan_print_form') ? get_cookie('plan_print_form') : '';


?>
<script>
    var myAccountForm;
    var automail_updated = "<?php echo !empty($automail_updated) ? $automail_updated : 'loading...'; ?>";
    var production_line = '<?php echo $production_line; ?>';
    var plan_account_type = '<?php echo $plan_account_type; ?>';
    var username = '<?php echo $username; ?>';
    var form_type = '<?php echo $form_type; ?>';
    if (form_type == null) form_type = '';

    var width = screen.width;
    var form_layout_width = 750;

    var ups_data_save = [];
    var dhxReportWins;

    /*
        | ------------------------------------------------------------------------------------------------------------
        | ON LOAD
        | ------------------------------------------------------------------------------------------------------------
    */

    function doOnLoad() {
        $(document).ready(function() {
            // menu
            initMainMenu();
            // toolbar
            initMainToolbar();

            //get Soline input
            mainToolbar.getInput("input_data").focus();

            // get input
            inputData();

            onClickMainToolbar();


        });


    }

    function getCookie(cname) {
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

    function setCookie(cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toGMTString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    }

    function detachedSOLine(so_line) {
        so_line = so_line.trim();
        so_line = so_line.replace(" ", "");

        // tách input thành Order number (SO) và line number (LINE)
        so_line_detached = so_line.split("-");

        // set order number and line number
        order_number = so_line_detached[0];
        line_number = so_line_detached[1];
    }

    function getToday()
    {
        var currentTime = new Date();
        var dd = String(currentTime.getDate()).padStart(2, '0');
        var mm = String(currentTime.getMonth() + 1).padStart(2, '0'); //January is 0!
        var yyyy = currentTime.getFullYear();
        today = yyyy +"-"+ mm +"-"+ dd;

        return today;
    }

    function formatDate(date) {

        date = date.toLocaleString('en-US', {
            timeZone: 'Asia/Bangkok'
        });

        // const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        const monthNames = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];
        const dateObj = new Date(date);
        const month = monthNames[dateObj.getMonth()];
        const day = String(dateObj.getDate()).padStart(2, '0');
        const year = dateObj.getFullYear();

        return year + '-' + month + '-' + day;

    }

    /*
        | ------------------------------------------------------------------------------------------------------------
        | INPUT DATA
        | ------------------------------------------------------------------------------------------------------------
    */

    function inputData() {
        // attach events
        mainToolbar.attachEvent("onEnter", function(id) {

            //get input
            var input = mainToolbar.getInput("input_data");

            // check input. 
            if (id == "input_data") {

                var input_data = input.value;
                checkDataExist(input_data)

            }

        });
    }

    // attach main toolbar
    function onClickMainToolbar() {
        mainToolbar.attachEvent("onClick", function(name) {
            if (name == "save") {

                // get data to check
                var promise_date = ordersForm.getItemValue('promise_date');
                var machine = ordersForm.getItemValue('machine');
                if (!machine || machine == null ) {
                    dhtmlx.alert({
                        title:"Warning",
                        type:"alert-warning",
                        ok:"Đồng ý",
                        text:"Vui lòng chọn MÁY cho đơn hàng"
                    });
                    return false;
                }

                // check empty data
                if (!promise_date) {
                    dhtmlx.alert({
                        title:"Warning",
                        type:"alert-warning",
                        ok:"Đồng ý",
                        text:"Promise Date đang rỗng. Vui lòng nhập vào Promise Date."
                    });
                    return false;
                }

                // save data
                saveOrders();



            }
        });
    }

    // check Automail, Master Data
    function checkDataExist(input_data) {
        //json data encode
        var jsonObjects = {
            "input_data": input_data
        };
        var url = "<?php echo base_url('htl/checkDataExist/'); ?>";

        //excute with ajax
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
                
                if (data.status == false) {
                    alert(data.message);
                    location.reload();
                    return false;
                } else {

                    // Lấy dữ liệu tại đây
                    isAlreadyExist(input_data);

                }

            },
            error: function(xhr, status, error) {
                alert('Error. Vui lòng liên hệ quản trị hệ thống!');
                location.reload();
                return false;
            }
        });
    }

    function isAlreadyExist(input_data) {
        //json data encode
        var jsonObjects = {
            "input_data": input_data
        };
        var url = "<?php echo base_url('htl/isAlreadyExist/'); ?>";

        //excute with ajax
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

                // Đã làm lệnh, lựa chọn sửa đơn hay không
                if (data.status == true) {

                    var confirm_user = confirm(data.message);
                    if (!confirm_user) {
                        window.location = '<?php echo base_url('htl'); ?>';
                        return false;

                    } else {
                        // kiểm tra có dữ liệu đơn hàng trong automail, master data không
                        handle(input_data, edit = true);
                    }

                } else {

                    // kiểm tra có dữ liệu đơn hàng trong automail, master data không
                    handle(input_data);

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
    function initMainMenu() {
        mainMenu = new dhtmlXMenuObject({
            parent: "mainMenu",
            iconset: "awesome",
            json: "<?php echo base_url('assets/xml/htl_main_menu.xml'); ?>",
            top_text: "<?php echo "<img style='width:60px;' src='" . base_url('assets/media/images/Logo.PNG') . "'/>&nbsp;&nbsp;&nbsp; " . strtoupper($production_line) . " PRODUCTION PLANNING "; ?>"
        });
        mainMenu.setAlign("right");

        mainMenu.attachEvent("onClick", function(id) {
            if (id !== "home") {
                if (id == 'prepress_oh') {
                    if (plan_account_type == 3) {
                        prepressOHWin();
                    } else {
                        alert('Bạn không có quyền truy cập chức năng này');
                    }

                } else if (id == 'master_data') {
                    if (plan_account_type == 3) {
                        location.href = "<?php echo base_url('htl/masterFile'); ?>";
                    } else {
                        alert('Bạn không có quyền truy cập chức năng này');
                    }

                } else if ((id == 'view_reports') ) {

                    // Init calendar, attach from date and to date 
                    var from_date = mainToolbar.getValue("from_date");
                    var to_date = mainToolbar.getValue("to_date");

                    // check input date
                    if (!from_date && to_date ) {
                        dhtmlx.alert({
                            title:"Warning",
                            type:"alert-warning",
                            ok:"Đồng ý",
                            text:"Vui lòng chọn From Date để tiếp tục"
                        });
                    } else if (from_date && !to_date ) {
                        dhtmlx.alert({
                            title:"Warning",
                            type:"alert-warning",
                            ok:"Đồng ý",
                            text:"Vui lòng chọn To Date để tiếp tục"
                        });
                    } else {
                        
                        if ( (!from_date && !to_date) ) {
                            dhtmlx.alert({
                                title:"Lưu ý",
                                ok:"Đồng ý",
                                text:"Dữ liệu ngày KHÔNG được chọn. Chương trình sẽ Reports dữ liệu trong ngày",
                                callback: function() {
                                    reportOrders(from_date, to_date);
                                }
                            });
                            
                        } else {
                            if (from_date > to_date ) {
                                dhtmlx.alert({
                                    title:"Warning",
                                    type:"alert-warning",
                                    ok:"Đồng ý",
                                    text:"Bạn đã chọn From Date > To Date. Vui lòng chọn lại"
                                });
                            } else {

                                reportOrders(from_date, to_date);
                            }
                            
                        }

                       

                    } 

                    

                } else if (id == 'view_user') {
                    if (plan_account_type == 3 || plan_account_type == 9) {
                        location.href = "<?php echo base_url('users/recent') ?>";
                    } else {
                        alert('Bạn không có quyền truy cập chức năng này');
                    }

                } else if (id == 'create_user') {
                    if (plan_account_type == 3 || plan_account_type == 9) {
                        initCreateUserWindow('');
                    } else {
                        alert('Bạn không có quyền truy cập chức năng này');
                    }

                } else if (id == 'remarks') {
                    location.href = "<?php echo base_url('remarks'); ?>";
                } else if (id == 'view_distance') {
                    // Init calendar, attach from date and to date 
                    var from_date = mainToolbar.getValue("from_date");
                    var to_date = mainToolbar.getValue("to_date");
                    var suffix_url_views = '?from_date=' + from_date + '&to_date=' + to_date;
                    location.href = "<?php echo base_url($production_line); ?>" + "/index/" + suffix_url_views;
                }

            } else {
                if (!plan_account_type) {
                    alert('Bạn không có quyền truy cập chức năng này');
                } else {
                    location.href = "<?php echo base_url($production_line); ?>";
                }

            }
        });
    }

    // layout
    function initMainLayout() {
        mainLayout = new dhtmlXLayoutObject({
            parent: document.body,
            pattern: "1C",
            offsets: {
                top: 60
            },
            cells: [{
                id: "a",
                header: true,
                text: "TỔNG DANH SÁCH ĐƠN HÀNG"
            }]
        });

        var url = "<?php echo base_url('htl/countOrders'); ?>";
        $.ajax({
            type: "POST",
            data: {
                data: ''
            },
            url: url,
            dataType: 'json',
            beforeSend: function(x) {
                if (x && x.overrideMimeType) {
                    x.overrideMimeType("application/j-son;charset=UTF-8");
                }
            },
            success: function(results) {

                var countAll = results.countAll;
                var countNow = results.countNow;
                var now = results.now;
                mainLayout.cells("a").setText('TỔNG ĐƠN HÀNG: <span style="color:red;font-size:15px;">' + countAll + '</span> || ĐƠN HÀNG HÔM NAY (' + now + '): <span style="color:red;font-size:15px;">' + countNow + '</span> ');
            },
            error: function(xhr, status, error) {
                alert('Load tổng số lượng đơn hàng lỗi');
                return false;
            }
        });

    }

    // toolbar
    function initMainToolbar() {
        // attach to sidebar
        // mainToolbar = new dhtmlXToolbarObject("mainToolbar");
        mainToolbar = new dhtmlXToolbarObject({
            parent: "mainToolbar",
            icons_size: 18,
            iconset: "awesome"
        });
        // init item
        mainToolbar.addButton("input_label", 3, "<span style='color:blue;font-weight:bold;font-size:13px;'>Input</span>", "fa fa-fire");
        mainToolbar.addInput("input_data", 4, "", 160);
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
        // var username = getCookie('plan_loginUser');
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
    function initMainViewGrid(from_date, to_date) {
        mainviewGrid = mainLayout.cells("a").attachGrid();
        mainviewGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
        mainviewGrid.setHeader("No, Form, Date, NO#, Loại Đơn, Plan Type, Qty, RBO, Item, Cập Nhật Bởi, Ngày Cập Nhật, Print, Sửa, Xóa"); //sets the headers of columns
        mainviewGrid.setColumnIds(",form_type, po_date, po_no, po_no_suffix, plan_type, qty, rbo, item, update_by, update_date, print, edit, delete"); //sets the columns' ids
        mainviewGrid.setInitWidths("50,70,90,200,100,100,90,300,220,140,*,90,50,50"); //sets the initial widths of columns
        mainviewGrid.setColAlign("center,center,center,center,center,center,center,center,center,center,center,center,center,center"); //sets the alignment of columns
        mainviewGrid.setColTypes("ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,link"); //sets the types of columns
        mainviewGrid.setColSorting("str,str,str,str,str,str,str,str,str,str,str,str,str,str"); //sets the sorting types of columns
        mainviewGrid.enableSmartRendering(true);

        mainviewGrid.setColumnColor(",,,#d5f1ff,,#d5f1ff,#d5f1ff,#d5f1ff,#d5f1ff");
        mainviewGrid.setStyle("font-weight:bold; font-size:13px;text-align:center;color:#007bff;", "font-size:12px;", "", "font-weight:bold;color:#0000ff;font-size:14px;");

        //Lưu ý: filter vượt quá 26 bị lỗi
        mainviewGrid.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
        mainviewGrid.enableMultiselect(true);

        mainviewGrid.init();

        loadMainViewGrid(from_date, to_date);

    }

    // order layout -------------------------------------------------------------------------------------------
    function initHandleLayout() {
        form_layout_width = (width > 1600) ? (width / 2) - 200 : 660;

        handleLayout = new dhtmlXLayoutObject({
            parent: document.body,
            pattern: "3J",
            offsets: {
                top: 64,
                bottom: 5
            },
            cells: [{
                    id: "a",
                    header: true,
                    text: "Orders Grid"
                },
                {
                    id: "b",
                    header: true,
                    text: "Order Details Form",
                    width: form_layout_width
                },
                {
                    id: "c",
                    header: true,
                    text: "Master Data Grid"
                }
            ]
        });

        // detach layout sub
        LayoutMaster = handleLayout.cells("c").attachLayout({
            pattern: "2U",
            offsets: {
                top: 5
            },
            cells: [{
                    id: "a",
                    header: true,
                    text: "Master  Data",
                    height: 300
                },
                {
                    id: "b",
                    header: true,
                    text: "Process"
                },
            ]
        });

    }

    function ordersGrid(results) {
        var orders = results.automailData;

        ordersGrid = handleLayout.cells("a").attachGrid();
        handleLayout.cells("a").setText("<span style='color:red;'>ORDER DETAILS</span>");
        ordersGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
        ordersGrid.setHeader("No., SO#, LINE, SO-LINE, QTY, ITEM, ORDERED ITEM, CUSTOMER ITEM, RBO, ORDERED DATE, REQUEST DATE, PROMISE DATE, SHIP TO CUSTOMER, BILL TO CUSTOMER, CS, ORDER TYPE NAME, FLOW STATUS CODE, PRODUCTION METHOD, PACKING INSTR, PACKING INSTRUCTION, ATTACHMENT"); //sets the headers of columns
        ordersGrid.setInitWidths("45,80,80,100,100,140,140,140,200,110,110,110,200,200,100,140,140,140,150,150,200");
        ordersGrid.setColAlign("right,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center"); //sets the alignment of columns
        ordersGrid.setColTypes("ed,ed,ed,ed,edn,ed,ed,ed,txt,ed,ed,ed,txt,txt,ed,ed,ed,ed,txt,txt,txt"); //sets the types of columns

        ordersGrid.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
        ordersGrid.setRowTextStyle("1", "background-color: red; font-family: arial;");
        ordersGrid.init();

        ordersGrid.enableSmartRendering(true); // false to disable

        // column types
        ordersGrid.setNumberFormat("0,000", "4", ".", ","); // qty

        // load data
        var automailData = {
            rows: []
        };

        for (var i = 0; i < orders.length; i++) {
            automailData.rows.push(orders[i]);
        }

        // load automail data to grid
        ordersGrid.parse(automailData, "json");

    }

    // load data: master 
    function masterDataGrid() {
        // get data
        var dataload = results.masterData;

        // init            
        masterDataGrid = LayoutMaster.cells("a").attachGrid();
        LayoutMaster.cells("a").setText("<span style='color:red;'>MASTER DATA</span>");
        masterDataGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
        masterDataGrid.setHeader("No., Internal Item, Material Code, Material Name, Material Width, Material Length, Product Type, Plan Type, Scrap, Remark 1, Remark 2, remark 3");
        masterDataGrid.setInitWidths("45,140,140,120,120,120,120,100,80,120,120,120");
        masterDataGrid.setColAlign("right,center,center,center,center,center,center,center,center,center,center,center");
        masterDataGrid.setColTypes("ed,ed,ed,edn,edn,ed,ed,ed,ed,txt,ed,ed");

        masterDataGrid.setRowTextStyle("1", "background-color: red; font-family: arial;");
        masterDataGrid.init();

        masterDataGrid.enableSmartRendering(true); // false to disable

        // column types
        masterDataGrid.setNumberFormat("0,000.00", "7", ".", ","); // qty

        // load data
        var data = {
            rows: []
        };

        for (var i = 0; i < dataload.length; i++) {
            data.rows.push(dataload[i]);
        }

        masterDataGrid.parse(data, "json");

    }

    // load data: process
    function processDataGrid() {
        // get data
        var dataload = results.processData;
        var header = results.processHeader;
        var countHeader = header.length;
        var countHeaderCheck = countHeader - 3;

        // get header data
        var col = "No., Printing,";
        for (var k = 0; k < countHeaderCheck; k++) col += ",";

        // init            
        processDataGrid = LayoutMaster.cells("b").attachGrid();
        LayoutMaster.cells("b").setText("<span style='color:red;'>PROCESS DATA</span>");
        processDataGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
        processDataGrid.setHeader(col);
        processDataGrid.setInitWidths("45,100,100,100,100,100,100,100,100,100,100,100,100");

        processDataGrid.enableDragAndDrop(true);
        
        //maximal parameters set
        // processDataGrid.enableColumnMove(true);
        // processDataGrid.enableColumnMove(true);

        processDataGrid.init();

        

        // set
        for (var i = 0; i < header.length; i++) {
            processDataGrid.setColLabel(i, header[i]);
        }

        processDataGrid.setColAlign("right,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center");
        processDataGrid.setRowTextStyle("1", "background-color: red; font-family: arial;");

        processDataGrid.enableSmartRendering(true); // false to disable

        // load data
        var data = {
            rows: []
        };

        var last_id = dataload.length+1;

        var length_check = 0;
        var last_row = {
            id: last_id,
            data:[]
        };
        for (var i = 0; i <= dataload.length; i++) {
            if (dataload[i] ) {
                length_check = (i==0) ? dataload[i].data.length : length_check;
                data.rows.push(dataload[i]);

                // console.log('data: ' + JSON.stringify(dataload[i]));
            } else {
                // console.log('length_check: ' + length_check);
                for (var j=0; j<length_check; j++ ) {
                    if (j==0 ) {
                        last_row.data.push(last_id);
                    } else {
                        last_row.data.push('');
                    }
                }

                // console.log('last_row: ' + JSON.stringify(last_row));
                data.rows.push(last_row);
            }
            
        }

        processDataGrid.parse(data, "json");

        // sắp xếp lại Grid process nếu người dùng có thay đổi số thứ tự
        var count_row = processDataGrid.getRowsNum();
        processDataGrid.attachEvent("onEnter", function(id,ind){
            // resorting STT
            if(ind==0){
                processDataGrid.sortRows(0,"str", "asc"); // sorts grid
            }
            
        });

        var header_arr = ["NO.", "PRINTING", "PASSES", "FRAME", "TIME", "SHEET", "INK USAGE"];

        // cho người dùng có thể hoán đổi vị trí các cột Item
        processDataGrid.attachEvent("onHeaderClick", function(ind,obj){

            // lấy giá trị 2 tiêu đề
            var colLabel_be = processDataGrid.getColLabel(ind);
            var colLabel_af = processDataGrid.getColLabel(ind+1);

            if (header_arr.indexOf(colLabel_be) !== -1 || header_arr.indexOf(colLabel_af) !== -1 ) {
                dhtmlx.alert({
                    title:"Warning",
                    type:"alert-warning",
                    ok:"Đồng ý",
                    text:"Bạn đã chọn sai vị trí các Item. Chọn Item phía trước để hoán đổi với Item phía sau"
                });
            } else {
                // set giá trị 2 tiêu đề hoán đổi
                processDataGrid.setColLabel(ind, colLabel_af);
                processDataGrid.setColLabel(ind+1, colLabel_be);

                processDataGrid.forEachRow(function(id) {

                    // đổi giá trị của 2 cột với nhau
                    var data_be = processDataGrid.cells(id,ind).getValue();
                    var data_af = processDataGrid.cells(id, ind+1).getValue();

                    processDataGrid.cells(id,ind).setValue(data_af);
                    processDataGrid.cells(id,ind+1).setValue(data_be);

                    
                });
            }

            
            
        });

        

    }

    // handle form struct
    function ordersFormStruct() {
        var form_width = form_layout_width - 50;
        var form_width_2 = form_width - 170;

        var struct = [];

        // save to server
        var jsonObjects = {}
        // url to server
        var url = '<?php echo base_url($production_line . "/getMachine"); ?>';

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
                        // machine list
                        var machine_json = data.machine_json;
                        var order_type_json = data.order_type_json;

                        // struct
                        struct = [{
                                type: "settings",
                                position: "label-left",
                                labelWidth: 150,
                                inputWidth: 250
                            },
                            {
                                type: "fieldset",
                                label: "Thông tin đơn hàng",
                                width: 720,
                                blockOffset: 10,
                                offsetLeft: 20,
                                offsetTop: 20,
                                list: [{
                                        type: "settings",
                                        position: "label-left",
                                        labelWidth: 140,
                                        inputWidth: 180,
                                        labelAlign: "left"
                                    },
                                    {
                                        type: "input",
                                        id: "uom_cost",
                                        name: "uom_cost",
                                        label: "<span style='color:fuchsia;font-weight:bold;'>Đơn vị hàng hóa:</span>",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "NotEmpty"
                                    },
                                    {
                                        type: "input",
                                        id: "po_date",
                                        name: "po_date",
                                        label: "Ngày:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "NotEmpty"
                                    },
                                    {
                                        type: "input",
                                        id: "po_no",
                                        name: "po_no",
                                        label: "NO#:",
                                        icon: "icon-input",
                                        className: "",
                                        required: true,
                                        validate: "NotEmpty"
                                    },
                                    {
                                        type: "input",
                                        id: "product_type",
                                        name: "product_type",
                                        label: "Product Type:",
                                        icon: "icon-input"
                                    },
                                    {
                                        type: "input",
                                        id: "customer_item",
                                        name: "customer_item",
                                        label: "Customer Item:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "NotEmpty"
                                    },

                                    {
                                        type: "input",
                                        id: "label_size",
                                        name: "label_size",
                                        label: "<span style='color:red;font-weight:bold;'>Label Size (w*h)</span>:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "NotEmpty"
                                    },
                                    {
                                        type: "input",
                                        id: "sheet_batching",
                                        name: "sheet_batching",
                                        label: "<span style='color:red;font-weight:bold;'>Sheet Batching</span>:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidInteger"
                                    },
                                    {
                                        type: "input",
                                        id: "ups",
                                        name: "ups",
                                        label: "<span style='color:red;font-weight:bold;'>UPS (w*h) </span>:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "NotEmpty"
                                    },
                                    {
                                        type: "input",
                                        id: "original_need",
                                        name: "original_need",
                                        label: "Need Sheet:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidNumeric"
                                    },
                                    {
                                        type: "input",
                                        id: "sheet_packing",
                                        name: "sheet_packing",
                                        label: "Sheet Packing:",
                                        icon: "icon-input",
                                        validate: "ValidInteger"
                                    },

                                    {
                                        type: "input",
                                        id: "setup_sheet_total",
                                        name: "setup_sheet_total",
                                        label: "Total Sheet Setup:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidInteger"
                                    },
                                    {
                                        type: "input",
                                        id: "sheet_pass_total",
                                        name: "sheet_pass_total",
                                        label: "Total Sheet Pass:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidInteger"
                                    },
                                    {
                                        type: "input",
                                        id: "paper_compensate_total",
                                        name: "paper_compensate_total",
                                        label: "Paper Compensate:",
                                        icon: "icon-input",
                                        validate: "ValidInteger"
                                    },

                                    {
                                        type: "input",
                                        id: "sheet_total",
                                        name: "sheet_total",
                                        label: "Total Sheet:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidNumeric"
                                    },
                                    {
                                        type: "input",
                                        id: "ups_total",
                                        name: "ups_total",
                                        label: "Total UPS:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidNumeric"
                                    },
                                    {
                                        type: "input",
                                        id: "pattern",
                                        name: "pattern",
                                        label: "<span style='color:blue;font-weight:bold;'>Khung HFE (4 ký tự)</span>:",
                                        icon: "icon-input"
                                    },


                                    {
                                        type: "newcolumn",
                                        "offset": 20
                                    },

                                    {
                                        type: "select",
                                        id: "machine",
                                        name: "machine",
                                        label: "<span style='color:red;font-weight:bold;'>Machine</span>:",
                                        style: "color:blue; ",
                                        required: true,
                                        validate: "NotEmpty",
                                        options: machine_json
                                    },
                                    {
                                        type: "input",
                                        id: "promise_date",
                                        name: "promise_date",
                                        label: "Promise Date:",
                                        icon: "icon-input",
                                        className: "",
                                        required: true,
                                        validate: "NotEmpty"
                                    },
                                    {
                                        type: "input",
                                        id: "qty_total",
                                        name: "qty_total",
                                        label: "<span style='color:red;font-weight:bold;'>Quantity</span>:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidInteger"
                                    },
                                    {
                                        type: "input",
                                        id: "internal_item",
                                        name: "internal_item",
                                        label: "Internal Item:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "NotEmpty"
                                    },
                                    {
                                        type: "input",
                                        id: "film_number",
                                        name: "film_number",
                                        label: "Số Film:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidInteger"
                                    },

                                    {
                                        type: "input",
                                        id: "process_pass_total",
                                        name: "process_pass_total",
                                        label: "Total Pass:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidInteger"
                                    },
                                    {
                                        type: "input",
                                        id: "running_time",
                                        name: "running_time",
                                        label: "Running Time:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidNumeric"
                                    },
                                    {
                                        type: "input",
                                        id: "setup_time_total",
                                        name: "setup_time_total",
                                        label: "Total Time:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidInteger"
                                    },
                                    {
                                        type: "input",
                                        id: "color_total",
                                        name: "color_total",
                                        label: "Total Color:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidInteger"
                                    },
                                    {
                                        type: "input",
                                        id: "allowance_scrap",
                                        name: "allowance_scrap",
                                        label: "Scrap Allowance:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidNumeric"
                                    },

                                    {
                                        type: "input",
                                        id: "designed_scrap",
                                        name: "designed_scrap",
                                        label: "Scrap Design:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidNumeric"
                                    },
                                    {
                                        type: "input",
                                        id: "setup_scrap",
                                        name: "setup_scrap",
                                        label: "Scrap Setup:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidNumeric"
                                    },
                                    {
                                        type: "input",
                                        id: "scrap_total",
                                        name: "scrap_total",
                                        label: "Total Scrap:",
                                        icon: "icon-input",
                                        required: true,
                                        validate: "ValidNumeric"
                                    },
                                    {
                                        type: "select",
                                        id: "order_type_local",
                                        name: "order_type_local",
                                        label: "Loại đơn (FR/Urgent/..):",
                                        style: "color:blue; ",
                                        validate: "NotEmpty",
                                        options: order_type_json
                                    },
                                    {
                                        type: "input",
                                        id: "plan_type",
                                        name: "plan_type",
                                        label: "Type (FOD/Sample/...):",
                                        icon: "icon-input"
                                    },


                                ]
                            },
                            {
                                type: "fieldset",
                                label: "Thông tin khách hàng",
                                width: "auto",
                                blockOffset: 0,
                                offsetLeft: "20",
                                offsetTop: "5",
                                list: [{
                                        type: "settings",
                                        position: "label-left",
                                        labelWidth: 120,
                                        inputWidth: form_width_2,
                                        labelAlign: "left"
                                    },
                                    {
                                        type: "input",
                                        id: "rbo",
                                        name: "rbo",
                                        label: "RBO:",
                                        icon: "icon-input",
                                        className: "",
                                        required: true,
                                        validate: "NotEmpty",
                                        readonly: true
                                    },
                                    {
                                        type: "input",
                                        id: "bill_to_customer",
                                        name: "bill_to_customer",
                                        label: "Bill to:",
                                        icon: "icon-input",
                                        className: "",
                                        required: true,
                                        validate: "NotEmpty",
                                        readonly: true
                                    },
                                    {
                                        type: "input",
                                        id: "ship_to_customer",
                                        name: "ship_to_customer",
                                        label: "Ship to:",
                                        icon: "icon-input",
                                        className: "",
                                        required: true,
                                        validate: "NotEmpty",
                                        readonly: true
                                    }
                                ]
                            },
                            {
                                type: "fieldset",
                                label: "Ghi chú cho sản xuất",
                                width: "auto",
                                blockOffset: 0,
                                offsetLeft: "20",
                                offsetTop: "5",
                                list: [{
                                        type: "settings",
                                        position: "label-left",
                                        labelWidth: 120,
                                        inputWidth: form_width_2,
                                        labelAlign: "left"
                                    },
                                    {
                                        type: "input",
                                        id: "po_remark_1",
                                        name: "po_remark_1",
                                        label: "Remark 1:",
                                        icon: "icon-input"
                                    },
                                    {
                                        type: "input",
                                        id: "po_remark_2",
                                        name: "po_remark_2",
                                        label: "Remark 2:",
                                        icon: "icon-input"
                                    }
                                ]
                            },
                            {
                                type: "fieldset",
                                label: "Lưu ý",
                                width: "auto",
                                blockOffset: 0,
                                offsetLeft: "20",
                                offsetTop: "5",
                                list: [{
                                        type: "settings",
                                        position: "label-left",
                                        labelWidth: "auto",
                                        inputWidth: "auto",
                                        labelAlign: "left"
                                    },
                                    {type: "label", label: "Các thông số nhãn màu đỏ có thể thay đổi. Cẩn thận khi thay đổi Số lượng (Quantity) đơn hàng"},
                                ]
                            }
                        ];

                    }
                } catch (e) {
                    struct = [];

                }
            },
            error: function(xhr, status, error) {
                struct = [];
            },
            async: false
        });

        return struct;
    }

    // handle form data
    function ordersForm() {

        // init
        formStructure = ordersFormStruct();
        ordersForm = handleLayout.cells("b").attachForm();
        // ordersForm = handleLayout.cells("b").attachForm(formStructure);	
        ordersForm.loadStruct(formStructure, function() {

            // load data
            var fdata = results.formData;
            var fPrepressOHData = results.prepressOHData;

            ordersForm.setItemValue('uom_cost', fdata.uom_cost);
            ordersForm.setItemValue('po_date', fdata.po_date);
            ordersForm.setItemValue('po_no', fdata.po_no);
            ordersForm.setItemValue('product_type', fdata.product_type);
            ordersForm.setItemValue('customer_item', fdata.po_customer_item);
            ordersForm.setItemValue('process_pass_total', fdata.process_pass_total);

            ordersForm.setItemValue('machine', fdata.machine);
            ordersForm.setItemValue('promise_date', fdata.promise_date);
            ordersForm.setItemValue('qty_total', fdata.qty_total);
            ordersForm.setItemValue('internal_item', fdata.po_internal_item);
            ordersForm.setItemValue('film_number', fdata.film_number);


            ordersForm.setItemValue('setup_time_total', fdata.setup_time_total);
            ordersForm.setItemValue('color_total', fdata.color_total);
            ordersForm.setItemValue('allowance_scrap', fdata.allowance_scrap);
            ordersForm.setItemValue('plan_type', fdata.plan_type);

            ordersForm.setItemValue('rbo', fdata.rbo);
            ordersForm.setItemValue('bill_to_customer', fdata.bill_to_customer);
            ordersForm.setItemValue('ship_to_customer', fdata.ship_to_customer);


            ordersForm.setItemValue('label_size', fPrepressOHData.label_size);
            ordersForm.setItemValue('sheet_batching', fPrepressOHData.sheet_batching);
            ordersForm.setItemValue('ups', fPrepressOHData.ups);



            // focus
            if (form_type == 'htl') {
                ordersForm.getInput('label_size').focus();
            } else if (form_type == 'hfe') {
                ordersForm.getInput('sheet_batching').focus();
            }

            // attach key

            // for label size
            var label_size = ordersForm.getInput('label_size');
            label_size.onkeypress = function(event) {
                var keycode = (event.keyCode ? event.keyCode : event.which);
                if (keycode == '13') {
                    var sheet_batching = ordersForm.getInput('sheet_batching').value;
                    if (!sheet_batching) {
                        ordersForm.getInput('sheet_batching').focus();
                    } else {
                        var ups = ordersForm.getInput('ups').value;
                        if (!ups) {
                            ordersForm.getInput('ups').focus();
                        } else {
                            var label_size = ordersForm.getInput('label_size').value;
                            if (label_size) {
                                calculate();
                            } else {
                                dhtmlx.message({
                                    text: "Bạn chưa nhập LABEL SIZE hoặc nhập chưa đúng định dạng (w * h) ",
                                    expire:6000,
                                    type:"error"
                                });
                            }
                        }
                    }
                }
            }

            // for sheet batching
            var sheet_batching = ordersForm.getInput('sheet_batching');
            sheet_batching.onkeypress = function(event) {
                var keycode = (event.keyCode ? event.keyCode : event.which);
                if (keycode == '13') {
                    var ups = ordersForm.getInput('ups').value;
                    if (!ups) {
                        ordersForm.getInput('ups').focus();
                    } else {
                        var label_size = ordersForm.getInput('label_size').value;
                        if (!label_size) {
                            ordersForm.getInput('label_size').focus();
                        } else {
                            var sheet_batching = ordersForm.getInput('sheet_batching').value;
                            if (sheet_batching) {
                                calculate();
                            } else {
                                dhtmlx.message({
                                    text: "Bạn chưa nhập SHEET BATCHING hoặc nhập chưa đúng định dạng (số) ",
                                    expire:6000,
                                    type:"error"
                                });
                            }
                        }

                    }

                }
            }

            // for ups
            var ups = ordersForm.getInput('ups');
            ups.onkeypress = function(event) {
                var keycode = (event.keyCode ? event.keyCode : event.which);
                if (keycode == '13') {
                    var label_size = ordersForm.getInput('label_size').value;
                    if (!label_size) {
                        ordersForm.getInput('label_size').focus();
                    } else {
                        var sheet_batching = ordersForm.getInput('sheet_batching').value;
                        if (!sheet_batching) {
                            ordersForm.getInput('sheet_batching').focus();
                        } else {
                            var ups = ordersForm.getInput('ups').value;
                            if (ups) {
                                calculate();
                            } else {
                                dhtmlx.message({
                                    text: "Bạn chưa nhập UPS hoặc nhập chưa đúng định dạng",
                                    expire:6000,
                                    type:"error"
                                });
                            }
                        }

                    }

                }
            }

            // for qty total
            var qty_total = ordersForm.getInput('qty_total');
            qty_total.onkeypress = function(event) {
                var keycode = (event.keyCode ? event.keyCode : event.which);
                if (keycode == '13') {
                    var qty_total = ordersForm.getInput('qty_total').value;
                    if (qty_total) {
                        calculate();
                    } else {
                        dhtmlx.message({
                            text: "Bạn chưa nhập QTY hoặc nhập chưa đúng định dạng (số)",
                            expire:6000,
                            type:"error"
                        });
                    }
                }
            }

            // for hfe
            var pattern = ordersForm.getInput('pattern'); // hfe
            pattern.onkeypress = function(event) {
                var keycode = (event.keyCode ? event.keyCode : event.which);
                if (keycode == '13') {
                    var pattern = ordersForm.getInput('pattern').value;
                    if (pattern) {
                        calculate();
                    } else {
                        dhtmlx.message({
                            text: "Bạn chưa nhập PATTERN",
                            expire:6000,
                            type:"error"
                        });
                    }
                }
            }


            // attach blur
            ordersForm.attachEvent("onBlur", function(name) {

                if (form_type == 'htl') {
                    if (name == 'ups') { // for ups
                        var label_size = ordersForm.getInput('label_size').value;
                        if (!label_size) {
                            ordersForm.getInput('label_size').focus();
                        } else {
                            var sheet_batching = ordersForm.getInput('sheet_batching').value;
                            if (!sheet_batching) {
                                ordersForm.getInput('sheet_batching').focus();
                            } else {
                                var ups = ordersForm.getInput('ups').value;
                                if (ups) calculate();
                            }
                        }

                    } else if (name == 'sheet_batching') { // for sheet batching
                        var label_size = ordersForm.getInput('label_size').value;
                        if (!label_size) {
                            ordersForm.getInput('label_size').focus();
                        } else {
                            var ups = ordersForm.getInput('ups').value;
                            if (!ups) {
                                ordersForm.getInput('ups').focus();
                            } else {
                                var sheet_batching = ordersForm.getInput('sheet_batching').value;
                                if (sheet_batching) calculate();
                            }
                        }

                    } else if (name == 'label_size') { // for label size
                        var sheet_batching = ordersForm.getInput('sheet_batching').value;
                        if (!sheet_batching) {
                            ordersForm.getInput('sheet_batching').focus();
                        } else {
                            var ups = ordersForm.getInput('ups').value;
                            if (!ups) {
                                ordersForm.getInput('ups').focus();
                            } else {
                                var label_size = ordersForm.getInput('label_size').value;
                                if (label_size) calculate();
                            }
                        }

                    } else if (name == 'qty_total') { // for qty total
                        var qty_total = ordersForm.getInput('qty_total').value;
                        if (!qty_total) {
                            ordersForm.getInput('qty_total').focus();
                        } else {
                            if (qty_total) calculate();
                        }

                    }

                } else if (form_type == 'hfe') {
                    if (name == 'sheet_batching') { // for HFE (pattern)
                        var sheet_batching = ordersForm.getInput('sheet_batching').value;
                        var pattern = ordersForm.getInput('pattern').value;
                        if (!pattern) {
                            ordersForm.getInput('pattern').focus();
                        } else {
                            if (sheet_batching) calculate();
                        }

                    } else if (name == 'pattern') {
                        var sheet_batching = ordersForm.getInput('sheet_batching').value;
                        var pattern = ordersForm.getInput('pattern').value;
                        if (!sheet_batching) {
                            ordersForm.getInput('sheet_batching').focus();
                        } else {
                            if (pattern) calculate();
                        }
                    }
                }



            });

            // attach on change
            ordersForm.attachEvent("onChange", function(name, value, state) {
                if (name == 'machine') {

                    var machine = ordersForm.getSelect('machine').value;
                    var sheet_pass_total = ordersForm.getInput('sheet_pass_total').value;
                    var setup_time_total = ordersForm.getInput('setup_time_total').value;
                    if (machine) {
                        var running_time = runningTime(machine, sheet_pass_total, setup_time_total);
                        ordersForm.setItemValue('running_time', running_time);
                    }

                }
            });

            // validation live
            ordersForm.enableLiveValidation(true);

        });


    }

    // calculate all parameters 
    function calculate() {

        var fdata = results.formData;

        var count_lines = ordersGrid.getRowsNum();
        var label_size = ordersForm.getInput('label_size').value;
        var sheet_batching = Number(ordersForm.getInput('sheet_batching').value);
        var qty_total = Number(ordersForm.getInput('qty_total').value);
        // var qty_total = Number(fdata.qty_total);
        // var allowance_scrap = Number(masterDataGrid.cellByIndex(0,8).getValue());
        var allowance_scrap = Number(fdata.allowance_scrap);
        var setup_sheet_total = fdata.setup_sheet_total;
        var process_pass_total = fdata.process_pass_total;
        var ups_total = 0;

        // get data
        if (form_type == 'htl') {


            var ups_label = ordersForm.getInput('ups').value;

            if (ups_label.indexOf("*") === -1) {
                alert("Nhập không đúng định dạng UPS");
                ordersForm.getInput('ups').focus();
                location.reload();
            } else {

                // // check 
                // if (count_lines >= 2) {
                //     if (ups_label.indexOf("+") == -1) {
                //         alert("Nhập không đúng định dạng UPS");
                //         ordersForm.getInput('ups').focus();
                //     }
                // }

                // get data
                var ups_arr = [];
                var label_size_arr = [];
                if (ups_label.indexOf("+") === -1) {
                    ups_arr.push(ups_label);
                    label_size_arr.push(label_size);
                } else {
                    ups_arr = ups_label.split("+");
                    label_size_arr = label_size.split("+");
                }

                // console.log("ups_arr: "+ ups_arr);
                // console.log("label_size_arr: "+ label_size_arr);

                for (var i = 0; i < ups_arr.length; i++) {
                    // detache
                    var label_size_line_arr = label_size_arr[i].split("*"); // do người dùng nhập tương ứng nên không cần foreach cho label size
                    var ups_line_arr = ups_arr[i].split("*");

                    // label size
                    var width = label_size_line_arr[0];
                    var length = label_size_line_arr[1];
                    var label_size = String(width) + " x " + String(length);

                    // ups widh, lengh
                    var ups_width = Number(ups_line_arr[0]);
                    var ups_length = Number(ups_line_arr[1]);
                    var ups_label = String(ups_width) + " x " + String(ups_length);
                    var ups = ups_width * ups_length;

                    ups_data_save.push({
                        width: width,
                        length: length,
                        label_size: label_size,
                        ups_width: ups_width,
                        ups_length: ups_length,
                        ups_label: ups_label,
                        ups: ups
                    });

                    // console.log("ups_width: "+ ups_width);
                    // console.log("ups_length: "+ ups_length);
                    // console.log("ups_label: "+ ups_label);
                    // console.log("ups: "+ ups);

                    // return false;

                    // ups total
                    ups_total += ups;

                }

                // sheet 
                // original_need
                var original_need = qty_total / ups_total;

                // sheet_total
                var sheet_total = sheet_batching * (1 + allowance_scrap) + setup_sheet_total + 1;
                // sheet_packing
                var sheet_packing = sheet_batching;
                // sheet_pass_total
                var sheet_pass_total = sheet_total * process_pass_total;

                // paper_compensate_total
                var paper_compensate_total = Math.ceil(sheet_batching * allowance_scrap);

                // scrap
                // designed_scrap
                var designed_scrap = (sheet_batching - original_need) / original_need;
                // setup_scrap
                var setup_scrap = setup_sheet_total / original_need;
                // scrap_total
                var scrap_total = allowance_scrap + designed_scrap + setup_scrap;

                // fix
                original_need = original_need.toFixed(2);
                sheet_total = Math.ceil(sheet_total);
                sheet_pass_total = Math.ceil(sheet_pass_total);
                designed_scrap = designed_scrap.toFixed(1);
                setup_scrap = setup_scrap.toFixed(1);
                scrap_total = scrap_total.toFixed(1);

                // load form
                ordersForm.setItemValue('original_need', original_need);
                ordersForm.setItemValue('sheet_total', sheet_total);
                ordersForm.setItemValue('sheet_packing', sheet_packing);
                ordersForm.setItemValue('sheet_pass_total', sheet_pass_total);
                ordersForm.setItemValue('setup_sheet_total', setup_sheet_total);

                ordersForm.setItemValue('paper_compensate_total', paper_compensate_total);
                ordersForm.setItemValue('designed_scrap', designed_scrap);
                ordersForm.setItemValue('setup_scrap', setup_scrap);
                ordersForm.setItemValue('designed_scrap', designed_scrap);
                ordersForm.setItemValue('scrap_total', scrap_total);

                ordersForm.setItemValue('ups_total', ups_total);

                // ordersForm.setItemValue('running_time', running_time );

                // Tính toán lưu lượng mực
                

            }


        } else if (form_type == 'hfe') {
            var pattern_no = ordersForm.getInput('pattern').value;

            // save to server
            var jsonObjects = {
                pattern_no: pattern_no
            }

            // url to server
            var url = '<?php echo base_url($production_line . "/patternData"); ?>';

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
                        if (data.status == false) {
                            alert(data.message);
                            return false;
                        } else {
                            var patternData = data.patternData;

                            var width = patternData.width;
                            var length = patternData.length;
                            var label_size = patternData.label_size;
                            var ups_width = patternData.ups_width;
                            var ups_length = patternData.ups_length;
                            var ups_total = Number(patternData.ups);
                            var ups_label = patternData.ups_label;

                            ordersForm.setItemValue('label_size', label_size);
                            ordersForm.setItemValue('ups', ups_label);

                            // ups_data_save.push({width : width, length : length, label_size : label_size, ups_width : ups_width, ups_length : ups_length, ups_label : ups_label, ups : ups });

                            // sheet 
                            // original_need
                            var original_need = qty_total / ups_total;
                            // sheet_packing
                            var sheet_packing = sheet_batching;

                            // paper_compensate_total
                            var paper_compensate_total = Math.ceil(sheet_batching * allowance_scrap);
                            // setup_sheet_total
                            var setup_sheet_total = Math.ceil((sheet_batching + paper_compensate_total) / 500) * 3;
                            // sheet_total
                            var sheet_total = Math.ceil(original_need) + sheet_batching + setup_sheet_total + paper_compensate_total;

                            // sheet_pass_total
                            var sheet_pass_total = sheet_total * process_pass_total;

                            // scrap
                            // designed_scrap
                            var designed_scrap = (sheet_batching - original_need) / original_need;
                            // setup_scrap
                            var setup_scrap = setup_sheet_total / original_need;
                            // scrap_total
                            var scrap_total = allowance_scrap + designed_scrap + setup_scrap;

                            // fix
                            original_need = original_need.toFixed(2);
                            sheet_total = Math.ceil(sheet_total);
                            sheet_pass_total = Math.ceil(sheet_pass_total);
                            designed_scrap = designed_scrap.toFixed(1);
                            setup_scrap = setup_scrap.toFixed(1);
                            scrap_total = scrap_total.toFixed(1);

                            // load form
                            ordersForm.setItemValue('original_need', original_need);
                            ordersForm.setItemValue('sheet_total', sheet_total);
                            ordersForm.setItemValue('sheet_packing', sheet_packing);
                            ordersForm.setItemValue('sheet_pass_total', sheet_pass_total);
                            ordersForm.setItemValue('setup_sheet_total', setup_sheet_total);

                            ordersForm.setItemValue('designed_scrap', designed_scrap);
                            ordersForm.setItemValue('setup_scrap', setup_scrap);
                            ordersForm.setItemValue('designed_scrap', designed_scrap);
                            ordersForm.setItemValue('scrap_total', scrap_total);

                            ordersForm.setItemValue('ups_total', ups_total);
                            ordersForm.setItemValue('paper_compensate_total', paper_compensate_total);

                            // ordersForm.setItemValue('running_time', running_time );

                        }
                    } catch (e) {
                        alert('Error. Please contact to the system admin. Error: ' + e);
                        return false;
                    }
                },
                error: function(xhr, status, error) {
                    alert('Lưu dữ liệu không thành công: Vui lòng liên hệ quản trị hệ thống. ' + xhr.responseText);
                    return false;
                }
            });
        }


        // // running_time
        //     var machine = ordersForm.getSelect('machine').value;
        //     var running_time = runningTime(machine, sheet_pass_total, fdata.setup_time_total);



    }


    function getPattern(pattern_no) {

    }

    function getMachineSpeed(machine) {
        // init 
        machine_speed = 0;

        // // // default
        // //     if (machine == 'ATMA') {
        // //         machine_speed = 375;
        // //     } else if (machine == 'SAKURAI') {
        // //         machine_speed = 1200;
        // //     } else if (machine == 'FAPL_MAY_NHO') {
        // //         machine_speed = 900;
        // //     } else if (machine == 'FAPL_MAY_LON') {
        // //         machine_speed = 900;
        // //     }

        // save to server
        var jsonObjects = {
            machine: machine
        }

        // url to server
        var url = '<?php echo base_url($production_line . "/getMachineSpeed"); ?>';

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
                        machine_speed = data.machine_speed;
                    }
                } catch (e) {
                    return machine_speed;
                }
            },
            async: false

        });

        return machine_speed;

    }

    function runningTime(machine, sheet_pass_total, setup_time_total) {
        var running_time = 0;
        var machine_speed = 0;
        machine = machine.toUpperCase();
        machine_speed = getMachineSpeed(machine);

        // running_time = (((sheet_pass_total / machine_speed) * 60) + setup_time_total) / 60;
        running_time = (sheet_pass_total / machine_speed);

        return running_time.toFixed(1);
    }

    // load order info
    function initHandlePage(results) {
        var fdata = results.formData;

        // init Layout
        initHandleLayout();

        // init Grid & Form
        ordersGrid(results);

        masterDataGrid();
        processDataGrid();

        ordersForm();
    }


    /*
        | ------------------------------------------------------------------------------------------------------------
        | 4.  LOAD DATA
        | ------------------------------------------------------------------------------------------------------------
    */


    // load data to grid
    function loadMainViewGrid(from_date, to_date) {
        var suffix_url_views = '?from_date=' + from_date + '&to_date=' + to_date;
        var url = "<?php echo base_url('htl/recent/'); ?>" + suffix_url_views;

        //excute with ajax function 
        $.ajax({
            type: "POST",
            data: {
                data: ''
            },
            url: url,
            dataType: 'json',
            beforeSend: function(x) {
                if (x && x.overrideMimeType) {
                    x.overrideMimeType("application/j-son;charset=UTF-8");
                }
            },
            success: function(results) {

                var data = {
                    rows: []
                };

                for (var i = 0; i < results.length; i++) {
                    data.rows.push(results[i]);
                }

                // load automail data to grid
                mainviewGrid.parse(data, "json");

                
            },
            error: function(xhr, status, error) {
                // alert(error);alert(xhr.responseText);
                alert('Lỗi hiển thị dữ liệu. Vui lòng liên hệ quản trị hệ thống. ');
                return false;
            }
        });

    }

    // load order
    function handle(input_data, edit = false) {
        var url_suffix = '?orders=' + input_data + '&edit=' + edit;
        location.href = '<?php echo base_url('htl/handle'); ?>' + url_suffix;
        
    }

    // save
    function saveOrders() {

        // get form data
        var po_date = ordersForm.getItemValue('po_date');
        var po_no = ordersForm.getItemValue('po_no');
        var product_type = ordersForm.getItemValue('product_type');
        var customer_item = ordersForm.getItemValue('customer_item');
        var label_size = ordersForm.getItemValue('label_size');

        var sheet_batching = ordersForm.getItemValue('sheet_batching');
        var ups_label = ordersForm.getItemValue('ups');
        var original_need = ordersForm.getItemValue('original_need');
        var sheet_packing = ordersForm.getItemValue('sheet_packing');
        var setup_sheet_total = ordersForm.getItemValue('setup_sheet_total');

        var sheet_pass_total = ordersForm.getItemValue('sheet_pass_total');
        var paper_compensate_total = ordersForm.getItemValue('paper_compensate_total');
        var sheet_total = ordersForm.getItemValue('sheet_total');
        var pattern = ordersForm.getItemValue('pattern');
        var machine = ordersForm.getSelect('machine').value;

        var promise_date = ordersForm.getItemValue('promise_date');
        var qty_total = ordersForm.getItemValue('qty_total');
        var internal_item = ordersForm.getItemValue('internal_item');
        var film_number = ordersForm.getItemValue('film_number');
        var process_pass_total = ordersForm.getItemValue('process_pass_total');

        var running_time = ordersForm.getItemValue('running_time');
        var setup_time_total = ordersForm.getItemValue('setup_time_total');
        var color_total = ordersForm.getItemValue('color_total');
        var allowance_scrap = ordersForm.getItemValue('allowance_scrap');
        var designed_scrap = ordersForm.getItemValue('designed_scrap');

        var setup_scrap = ordersForm.getItemValue('setup_scrap');
        var scrap_total = ordersForm.getItemValue('scrap_total');
        var order_type_local = ordersForm.getSelect('order_type_local').value;
        var ups_total = ordersForm.getItemValue('ups_total');
        var plan_type = ordersForm.getItemValue('plan_type');
        var uom_cost = ordersForm.getItemValue('uom_cost');

        var rbo = ordersForm.getItemValue('rbo');
        var bill_to_customer = ordersForm.getItemValue('bill_to_customer');
        var ship_to_customer = ordersForm.getItemValue('ship_to_customer');
        var po_remark_1 = ordersForm.getItemValue('po_remark_1');
        var po_remark_2 = ordersForm.getItemValue('po_remark_2');



        // set data to save
        var formData = {
            po_date: po_date,
            po_no: po_no,
            product_type: product_type,
            customer_item: customer_item,
            label_size: label_size,

            sheet_batching: sheet_batching,
            ups_label: ups_label,
            original_need: original_need,
            sheet_packing: sheet_packing,
            setup_sheet_total: setup_sheet_total,

            sheet_pass_total: sheet_pass_total,
            paper_compensate_total: paper_compensate_total,
            sheet_total: sheet_total,
            pattern: pattern, // HFE
            machine: machine,

            promise_date: promise_date,
            qty_total: qty_total,
            internal_item: internal_item,
            film_number: film_number,
            process_pass_total: process_pass_total,

            running_time: running_time,
            setup_time_total: setup_time_total,
            color_total: color_total,
            allowance_scrap: allowance_scrap,
            designed_scrap: designed_scrap,

            setup_scrap: setup_scrap,
            scrap_total: scrap_total,
            order_type_local: order_type_local,
            ups_total: ups_total,
            plan_type: plan_type,
            uom_cost: uom_cost,

            rbo: rbo,
            bill_to_customer: bill_to_customer,
            ship_to_customer: ship_to_customer,
            po_remark_1: po_remark_1,
            po_remark_2: po_remark_2

        }



        // get process
        var processHeader = [];
        var processData = [];

        
        var colNum = processDataGrid.getColumnsNum();
        var rowNum = processDataGrid.getRowsNum();

        // header
        for (var col=0; col<colNum; col++ ) {
            processHeader.push(processDataGrid.getColLabel(col));
        }


        // process dataa
        
        for(var row=0; row<rowNum; row++ ) {

            var processElement = [];
            for (var col=0; col<colNum; col++ ) {
                processElement.push(processDataGrid.cellByIndex(row, col).getValue() );
            }

            processData.push(processElement);

        }

        // reset process
        results['processData'] = processData;
        results['processHeader'] = processHeader;

        // save to server
        var jsonObjects = {
            formData: formData,
            results: results
        }

        // console.log('json save: ' + JSON.stringify(jsonObjects));
        // return false;

        // url to server
        var url = '<?php echo base_url($production_line . "/saveOrders"); ?>';

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
                    if (data.status == false) {
                        alert(data.message);
                        return false;
                    } else {
                        var is_print = confirm(data.message);
                        if (is_print) {
                            printOrders(data.po_no);
                        } else {
                            window.location = '<?php echo base_url($production_line); ?>';
                        }
                    }
                } catch (e) {
                    alert('Lỗi khi in. Vui lòng liên hệ quản trị hệ thống. Lỗi: ' + e);
                    return false;
                }
            },
            error: function(xhr, status, error) {
                alert('Lưu dữ liệu không thành công: Vui lòng liên hệ quản trị hệ thống. ' + xhr.responseText);
                return false;
            }
        });


    }

    // print
    function printOrders(po_no) {
        var wi = window.open('about:blank', '_blank');
        wi.window.location = '<?php echo base_url("htl/printOrders/?po_no="); ?>' + po_no;
        window.location = '<?php echo base_url($production_line); ?>';
    }

    // report order data 
    function reportOrders(from_date, to_date) {

        var suffix_url = '?from_date=' + from_date + '&to_date=' + to_date;
        var url = "<?php echo base_url($production_line); ?>" + "/reportOrders/" + suffix_url;

        // close if exist
        if (dhxReportWins) {
            dhxReportWins.window("WindowsDetail").close();
        }

        // create
        dhxReportWins = new dhtmlXWindows();

        if (!dhxReportWins.isWindow("WindowsDetail")) {


            // setup
            var id = "WindowsDetail";
            var w = Number(($(window).width() - 100) );
            var h = Number(($(window).height() - 100) );

            var x = Number(($(window).width() - w) / 2);
            var y = Number(($(window).height() - h) / 2);
            var Popup = dhxReportWins.createWindow(id, x, y, w, h);

            // attach grid
            var grid = dhxReportWins.window(id).attachGrid();

            // close
            Popup.attachEvent("onClose", function(win) {
                if (win.getId() == "WindowsDetail") win.hide();
            });

            // Grid setup
                // title
                var title = "Reports Dữ liệu từ "+from_date+" đến "+to_date;
                if (!from_date || !to_date ) {
                    var today = getToday();
                    title = "Reports Dữ liệu trong ngày "+today;
                }
                
                dhxReportWins.window(id).setText(title);

                // init grid
                grid.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
                grid.setStyle("font-weight:bold; font-size:12px;text-align:center;", "font-size:12px;", "font-weight:bold;color:red;font-size:16px;", "font-weight:bold;color:#0000ff;font-size:13px;");


            // Grid init
            grid.init();
            
            // load data
            grid.enableSmartRendering(true); // false to disable
            grid.loadXML(url, function() {

                dhxReportWins.window("WindowsDetail").progressOff();

                // toolbar
                var reportToolbar = dhxReportWins.window("WindowsDetail").attachToolbar();
                reportToolbar.setIconset('awesome');
                reportToolbar.setIconSize(12);

                reportToolbar.addText('tool',0, '');
                reportToolbar.addSpacer("tool");
                reportToolbar.addText('report_toolbar',1, 'Vui lòng chọn chức năng: ');                
                reportToolbar.addButton('reports',4, '<a style="color:red;font-weight:bold;">Reports Dữ liệu theo khoảng ngày đã chọn |||</a>', 'fa fa-cloud-download', null);

                // attach Event
                reportToolbar.attachEvent("onClick", function(id) {
                    //1. upload
                    if (id == 'reports' ) {
                        reportCSV(grid, 'HTL_Reports')
                    } 
                });

                
            });


        } else {
            dhxReportWins.window("WindowsDetail").show();
        }


    }

    function reportCSVOff(grid, file_name)
    {

        var today = getToday();
        
        grid.enableCSVHeader(true);
        grid.setCSVDelimiter(',');
        var csv = grid.serializeToCSV();
        filename = file_name+'_'+today+'.csv';

        if (csv == null) return;
        if (!csv.match(/^data:text\/csv/i)) {
            csv = 'data:text/csv;charset=utf-8,' + csv;
        }

        // data = csv;
        data = encodeURI(csv);

        for (var k=0;k<=100;k++){
            data = data.replace('&amp;','&');
            // console.log('data: '+data);
        }
        link = document.createElement('a');
        link.setAttribute('href', data);
        link.setAttribute('download', filename);
        link.click();


    }



    // \\147.121.56.227\htdocs\avery\auto\planning\f1\jobjackets_test\uploads\htl\reports
    function reportCSV(grid, file_name)
    {

        var today = getToday();
        
        grid.enableCSVHeader(true);
        grid.setCSVDelimiter(',');
        var csv = grid.serializeToCSV();
        filename = file_name+'_'+today+'.xlsx';

        location.href = "<?php echo base_url('uploads/htl/reports/') ?>"+filename;


    }

    function prepressOHWin() {
        // close if exist
        if (dhxWins) {
            dhxWins.window("Windows").close();
        }

        // create
        dhxWins = new dhtmlXWindows();

        if (!dhxWins.isWindow("Windows")) {


            // init win
            var id = "Windows";
            var w = 960;
            var h = 600;
            var x = Number(($(window).width() - w) / 2);
            var y = Number(($(window).height() - h) / 2);
            var Popup = dhxWins.createWindow(id, x, y, w, h);

            // grid
            // init grid
            var prepressOHGrid = dhxWins.window(id).attachGrid();

            // close
            Popup.attachEvent("onClose", function(win) {
                if (win.getId() == "Windows") win.hide();
            });

            // title
            dhxWins.window(id).setText("Prepress OH (Đơn chưa làm lệnh)");

            // init grid
            prepressOHGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
            prepressOHGrid.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter");
            prepressOHGrid.setRowTextStyle("1", "background-color: red; font-family: arial;");
            prepressOHGrid.init();
            prepressOHGrid.enableSmartRendering(true); // false to disable

            // load data
            prepressOHGrid.loadXML("<?php echo base_url($production_line . '/loadPrepressOH') ?>", function() {

            });

            // toolbar
            // init toolbar
            var prepressOHToolbar = dhxWins.window(id).attachToolbar();
            // setup
            prepressOHToolbar.setIconset("awesome");
            // add item
            prepressOHToolbar.addText("prepress_oh", 0, "Vui lòng sử dụng file excel (.XLSX) để Imports");
            prepressOHToolbar.addSpacer("prepress_oh");
            prepressOHToolbar.addButton("imports_prepress_oh", 10, "<span style='color:red;font-weight:bold;'>Imports (.XLSX) </span>", "fa fa-file-excel-o");
            prepressOHToolbar.addSeparator("separator_1", 11);
            prepressOHToolbar.addButton("exports_prepress_oh", 15, "<span style='color:blue;font-weight:bold;'>Exports (.XLSX)</span>", "fa fa-file-excel-o");
            prepressOHToolbar.addSeparator("separator_3", 16);

            // attach
            prepressOHToolbar.attachEvent("onClick", function(id) {
                //1. upload
                if (id == "imports_prepress_oh") {
                    importPrepressOH();
                } else if (id == "exports_prepress_oh") {

                }
            });

        } else {
            dhxWins.window("Windows").show();
        }

    }


    // imports prepress OH
    function importPrepressOH() {
        if (!dhxWins) {
            dhxWins = new dhtmlXWindows();
        }

        var id = "Imports";
        var w = 400;
        var h = 100;
        var x = Number(($(window).width() - 400) / 2);
        var y = Number(($(window).height() - 50) / 2);
        var Popup = dhxWins.createWindow(id, x, y, w, h);
        dhxWins.window(id).setText("Imports OH");
        Popup.attachHTMLString(
            '<div style="width:500%;margin:20px">' +
            '<form action="<?php echo base_url($production_line . '/importPrepressOH'); ?>" enctype="multipart/form-data" method="post" accept-charset="utf-8">' +
            '<input type="file" name="file" id="file" class="form-control filestyle" value="value" data-icon="false"  />' +
            '<input type="submit" name="importfile" value="Import" id="importfile-id" class="btn btn-block btn-primary"  />' +
            '</form>' +
            '</div>'
        );
    }

    /*
        | ------------------------------------------------------------------------------------------------------------
        | MASTER FILE
        | ------------------------------------------------------------------------------------------------------------
    */

    // onload ------------------------------------------------------------------------------------------------------
    function doloadMasterFile() {
        masterFileLayout();
    }

    // var ------------------------------------------------------------------------------------------------------
    var dhxWins;
    var masterFileFormStruct;
    var dhxWins2;


    // function ------------------------------------------------------------------------------------------------------

    // layout
    function masterFileLayout() {
        masterfileLayout = new dhtmlXLayoutObject({
            parent: document.body,
            pattern: "2U",
            offsets: {
                top: 64,
                left: 2,
                right: 2,
                bottom: 5
            },
            cells: [{
                    id: "a",
                    header: true,
                    text: "Data Grid"
                },
                {
                    id: "b",
                    header: true,
                    text: "Form Edit",
                    width: 650
                }
            ]
        });

        // menu 
        masterFileMenu();

    }

    // menu
    function masterFileMenu() {
        // init 
        master_data_menu = new dhtmlXMenuObject({
            parent: "mainMenu",
            iconset: "awesome",
            json: "<?php echo base_url('assets/xml/htl_master_data_menu.xml'); ?>",
            top_text: "<?php echo "<img style='width:60px;' src='" . base_url('assets/media/images/Logo.PNG') . "'/>&nbsp;&nbsp;&nbsp; HTL MASTER DATA "; ?>"
        });

        // align
        master_data_menu.setAlign("right");

        // master default
        master = 'master_item';

        // default
        masterFileToolbar(master); // toolbar
        masterFileGrid(master); // grid
        masterFileForm(master); // form

        // attach menu
        master_data_menu.attachEvent("onClick", function(id) {

            master = id;

            if (id !== 'home') {

                if (id == 'come_back_planning') {
                    location.href = "<?php echo base_url($production_line); ?>";
                } else if (id == 'sample_master_item') {
                    window.open('https://docs.google.com/spreadsheets/d/1TSWJtWsF6JClvNPN5EY-loZGsoNNPtBTb7tyHb1vmyg/edit?usp=sharing',"_blank");
                } else if (id == 'sample_master_process') {
                    window.open('https://docs.google.com/spreadsheets/d/1VjcAZBIWhPRxBRX0SEqoyTeAMQMj5wtXx8wgX0SvGOE/edit?usp=sharing',"_blank");
                } else if ((id == 'master_item') || (id == 'master_process')) {

                    masterFileToolbar(master); // toolbar
                    masterFileGrid(master); // grid
                    masterFileForm(master); // form

                } else {
                    // cac menu khac, xu ly sau
                    masterFileWin(master);
                }

            } else {
                if (!plan_account_type) {
                    alert('Bạn không có quyền truy cập chức năng này');
                } else {
                    location.href = "<?php echo base_url($production_line); ?>" + "/masterFile";
                }
            }

        });

    }

    // master data: main master toolbar
    function masterFileToolbar(master) 
    {
        // init
        masterfileToolbar = new dhtmlXToolbarObject({
            parent: "masterfileToolbar",
            icons_size: 18,
            iconset: "awesome"
        });

        // set id
        imports_master = 'imports_' + master;
        exports_master = 'exports_' + master;

        // init item
        masterfileToolbar.addText("master_data_function", 1, "Imports/Exports");
        masterfileToolbar.addSpacer("master_data_function");
        masterfileToolbar.addText("master_data_note", 2, "Vui lòng sử dụng file Excel (.XLSX) để Imports ");
        masterfileToolbar.addSeparator("separator_0", 3);

        masterfileToolbar.addButton(imports_master, 10, "<span style='color:green;font-weight:bold;font-size:13px;'>Imports (.XLSX)</span>", "fa fa-file-excel-o");
        masterfileToolbar.addSeparator("separator_1", 11);
        masterfileToolbar.addButton(exports_master, 15, "<span style='color:blue;font-weight:bold;font-size:13px;'>Exports (.XLSX)</span>", "fa fa-file-excel-o");
        masterfileToolbar.addSeparator("separator_3", 16);

        // attach
        masterfileToolbar.attachEvent("onClick", function(name) {
            if ((name == 'imports_master_item') || (name == 'imports_master_process')) {

                importMasterFile(master);

            } else if ((name == 'exports_master_item') || (name == 'exports_master_process')) {

                exportMasterFile(master);

            }

        });

    }

    // grid
    function masterFileGrid(master) 
    {
        // if (dhxWins2) dhxWins2.window("WindowsDetail").close();
        // attach to layout
        masterfileLayout.cells("a").progressOn();
        masterfileGrid = masterfileLayout.cells("a").attachGrid();

        // check master
        if (master == 'master_item') {

            masterfileLayout.cells("a").setText("<span style='color:red;'>MASTER FILE</span>");
            masterfileGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
            masterfileGrid.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
            masterfileGrid.setRowTextStyle("1", "background-color: red; font-family: arial;");
            masterfileGrid.init();

            masterfileGrid.enableSmartRendering(true); // false to disable

            masterfileGrid.loadXML("<?php echo base_url($production_line . '/loadMasterFile') ?>", function() {
                masterfileLayout.cells("a").progressOff();
                loadMasterFileForm(master);
            });
        } else if (master == 'master_process') {

            masterfileLayout.cells("a").setText("<span style='color:red;'>MASTER PROCESS</span>");
            masterfileGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
            masterfileGrid.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
            masterfileGrid.setRowTextStyle("1", "background-color: red; font-family: arial;");
            masterfileGrid.init();

            masterfileGrid.enableSmartRendering(true); // false to disable

            masterfileGrid.loadXML("<?php echo base_url($production_line . '/loadMasterProcess') ?>", function() {
                masterfileLayout.cells("a").progressOff();
                loadMasterFileForm(master);
            });
        }

    }


    function masterFileWin(master) {
        // close if exist
        if (dhxWins2) {
            dhxWins2.window("WindowsDetail").close();
        }

        // create
        dhxWins2 = new dhtmlXWindows();

        if (!dhxWins2.isWindow("WindowsDetail")) {


            // setup
            var id = "WindowsDetail";
            var w = 1200;
            var h = 700;
            var x = Number(($(window).width() - w) / 2);
            var y = Number(($(window).height() - h) / 2);
            var Popup = dhxWins2.createWindow(id, x, y, w, h);

            // attach grid
            var masterfileGrid2 = dhxWins2.window(id).attachGrid();
            masterfileGrid2.enableValidation(true, true); 
            masterfileGrid2.setStyle("font-weight:bold; font-size:12px;text-align:center;", "font-size:12px;", "font-weight:bold;color:red;font-size:16px;", "font-weight:bold;color:#0000ff;font-size:13px;");

            // close
            Popup.attachEvent("onClose", function(win) {
                if (win.getId() == "WindowsDetail") win.hide();
            });

            // Grid setup
            if (master == 'master_setting_process') {
                // title
                dhxWins2.window(id).setText("Master Process Setting");

                // setup grid
                masterfileGrid2.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
                masterfileGrid2 .setColValidators([null,null,"NotEmpty","NotEmpty","NotEmpty"]);

            } else if (master == 'master_machine') {

                // title
                dhxWins2.window(id).setText("Master Machine");

                // setup grid
                masterfileGrid2.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
                masterfileGrid2 .setColValidators([null,null,"NotEmpty","NotEmpty","ValidNumeric","NotEmpty"]);

            } else if (master == 'master_order_type_local') {
                // title
                dhxWins2.window(id).setText("Loại Đơn Hàng");

                // setup grid
                masterfileGrid2.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
                masterfileGrid2 .setColValidators([null,"NotEmpty","NotEmpty"]);

            } else if (master == 'master_pattern') {
                // title
                dhxWins2.window(id).setText("Pattern/Khuôn Bế");

                // setup grid
                masterfileGrid2.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
                masterfileGrid2 .setColValidators([null,"NotEmpty","ValidNumeric","ValidNumeric",null,"ValidNumeric","ValidNumeric"]);

            } else if (master == 'master_scrap') {
                // title
                dhxWins2.window(id).setText("Scrap (%)");

                // setup grid
                masterfileGrid2.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
                masterfileGrid2 .setColValidators([null,"ValidNumeric","ValidNumeric","ValidNumeric","ValidNumeric","ValidNumeric","ValidNumeric"]);
               
            } else if (master == 'master_setup_time') {
                // title
                dhxWins2.window(id).setText("Bảng Thời Gian Canh Chỉnh");

                // setup grid
                masterfileGrid2.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
                masterfileGrid2 .setColValidators([null,"NotEmpty","NotEmpty","ValidNumeric"]);
                
            }

            // Grid init
            masterfileGrid2.init();

            // load data
            masterfileGrid2.enableSmartRendering(true); // false to disable
            masterfileGrid2.loadXML("<?php echo base_url($production_line . '/masterfileGrid2/') ?>"+master, function() {
                
                // load last row
                var state=masterfileGrid2.getStateOfView();
                if(state[2]>0) masterfileGrid2.showRow(masterfileGrid2.getRowId(state[2]-1));

                // onLiveValidationError onValidationError
                masterfileGrid2.attachEvent("onValidationError", function(id,index,value,input,rule){
                    var row = Number(id)+1;
                    var col = Number(index)+1;

                    dhtmlx.message({
                        text: "Nhập không đúng định dạng vị trí (Cột "+col+", dòng "+row+")",
                        expire:6000,
                        type:"error"
                    });

                    masterfileGrid2.cells(id,index).setValue("");
                    masterfileGrid2.selectCell(id, index, false, true);
                    masterfileGrid2.editCell();
                    
                });

                
                masterfileGrid2.attachEvent("onCheckbox", function(rId,cInd,state){

                    var jsonObjects = {};
                    // check and save auto
                    var onCheckbox = false;
                    var delConf = 'no';
                    if (master == 'master_setting_process' ) {

                        var form_type = masterfileGrid2.cells(rId,1).getValue();
                        var process = masterfileGrid2.cells(rId,2).getValue();
                        var type = masterfileGrid2.cells(rId,3).getValue();
                        var process_name_vi = masterfileGrid2.cells(rId,4).getValue();
                        var process_name_en = masterfileGrid2.cells(rId,5).getValue();

                        //json data encode
                        jsonObjects = { 
                            "production_line": production_line, 
                            "form_type": form_type,
                            "process": process,
                            "type": type,
                            "process_name_vi": process_name_vi,
                            "process_name_en": process_name_en
                        };

                        if (cInd == 8 && (masterfileGrid2.cells(rId,cInd).getValue() == 1) ) { // save
                            onCheckbox = true;
                        } else if (cInd == 9 && (masterfileGrid2.cells(rId,cInd).getValue() == 1) ) { // del
                            onCheckbox = true;
                            conf = confirm("Bạn có chắc chắn muốn xóa Setting Process: "+process+ "?");
                            if (conf == false ) {
                                return false;
                            } else {
                                delConf = 'del';
                            }
                        }
                        

                    } else if (master == 'master_machine' ) {

                        var form_type = masterfileGrid2.cells(rId,1).getValue();
                        var machine = masterfileGrid2.cells(rId,2).getValue();
                        var machine_name = masterfileGrid2.cells(rId,3).getValue();
                        var machine_speed = masterfileGrid2.cells(rId,4).getValue();
                        var machine_unit = masterfileGrid2.cells(rId,5).getValue();

                        //json data encode
                        jsonObjects = { 
                            "form_type": form_type,
                            "machine": machine,
                            "machine_name": machine_name,
                            "machine_speed": machine_speed,
                            "machine_unit": machine_unit
                        };

                        if (cInd == 8 && (masterfileGrid2.cells(rId,cInd).getValue() == 1) ) { // save
                            onCheckbox = true;
                        } else if (cInd == 9 && (masterfileGrid2.cells(rId,cInd).getValue() == 1) ) { // del

                            onCheckbox = true;
                            conf = confirm("Bạn có chắc chắn muốn xóa máy: "+machine+ "?");
                            if (conf == false ) {
                                return false;
                            } else {
                                delConf = 'del';
                            }
                        }

                    } else if (master == 'master_order_type_local' ) {

                        var order_type_local = masterfileGrid2.cells(rId,1).getValue();
                        var descriptions = masterfileGrid2.cells(rId,2).getValue();

                        //json data encode
                        jsonObjects = { 
                            "order_type_local": order_type_local, 
                            "descriptions": descriptions
                        };

                        if (cInd == 5 && (masterfileGrid2.cells(rId,cInd).getValue() == 1) ) { // save
                            onCheckbox = true;
                        } else if (cInd == 6 && (masterfileGrid2.cells(rId,cInd).getValue() == 1) ) { // del

                            onCheckbox = true;
                            conf = confirm("Bạn có chắc chắn muốn xóa Loại đơn hàng: "+order_type_local+ "?");
                            if (conf == false ) {
                                return false;
                            } else {
                                delConf = 'del';
                            }
                        }

                    } else if (master == 'master_pattern' ) {

                        var pattern_no = masterfileGrid2.cells(rId,1).getValue();
                        var width = masterfileGrid2.cells(rId,2).getValue();
                        var length = masterfileGrid2.cells(rId,3).getValue();
                        var label_size = width + 'x' + length;
                        
                        var ups_width = masterfileGrid2.cells(rId,5).getValue();
                        var ups_length = masterfileGrid2.cells(rId,6).getValue();
                        var ups = Number(ups_width) * Number(ups_length);
                        var ups_label = ups_width + 'x' + ups_length;

                        //json data encode
                        jsonObjects = { 
                            "pattern_no": pattern_no, 
                            "width": width,
                            "length": length,
                            "label_size": label_size,
                            "ups_width": ups_width,
                            "ups_length": ups_length,
                            "ups": ups,
                            "ups_label": ups_label
                        };
                        
                        
                        if (cInd == 10 && (masterfileGrid2.cells(rId,cInd).getValue() == 1) ) { // save
                            onCheckbox = true;
                        } else if (cInd == 11 && (masterfileGrid2.cells(rId,cInd).getValue() == 1) ) { // del

                            onCheckbox = true;
                            conf = confirm("Bạn có chắc chắn muốn xóa Khuôn bế: "+pattern_no+ "?");
                            if (conf == false ) {
                                return false;
                            } else {
                                delConf = 'del';
                            }
                        }

                    } else if (master == 'master_scrap' ) {

                        var qty_limit = masterfileGrid2.cells(rId,1).getValue();
                        var scrap_color_1 = masterfileGrid2.cells(rId,2).getValue();
                        var scrap_color_2 = masterfileGrid2.cells(rId,3).getValue();
                        var scrap_color_3 = masterfileGrid2.cells(rId,4).getValue();
                        var scrap_color_4 = masterfileGrid2.cells(rId,5).getValue();
                        var scrap_color_5 = masterfileGrid2.cells(rId,6).getValue();

                        //json data encode
                        jsonObjects = { 
                            "qty_limit": qty_limit, 
                            "scrap_color_1": scrap_color_1,
                            "scrap_color_2": scrap_color_2,
                            "scrap_color_3": scrap_color_3,
                            "scrap_color_4": scrap_color_4,
                            "scrap_color_5": scrap_color_5
                        };
                        
                        
                        if (cInd == 9 && (masterfileGrid2.cells(rId,cInd).getValue() == 1) ) { // save
                            onCheckbox = true;
                        } else if (cInd == 10 && (masterfileGrid2.cells(rId,cInd).getValue() == 1) ) { // del

                            onCheckbox = true;
                            conf = confirm("Bạn có chắc chắn muốn xóa Số lượng giới hạn: "+qty_limit+ "?");
                            if (conf == false ) {
                                return false;
                            } else {
                                delConf = 'del';
                            }

                        }

                    } else if (master == 'master_setup_time' ) {

                        var ink_group = masterfileGrid2.cells(rId,1).getValue();
                        var ink_type = masterfileGrid2.cells(rId,2).getValue();
                        var alignment_times = masterfileGrid2.cells(rId,3).getValue();

                        //json data encode
                        jsonObjects = { 
                            "ink_group": ink_group, 
                            "ink_type": ink_type,
                            "alignment_times": alignment_times
                        };
                        
                        
                        if (cInd == 6 && (masterfileGrid2.cells(rId,cInd).getValue() == 1) ) { // save
                            onCheckbox = true;
                        } else if (cInd == 7 && (masterfileGrid2.cells(rId,cInd).getValue() == 1) ) { // del

                            onCheckbox = true;
                            conf = confirm("Bạn có chắc chắn muốn xóa Nhóm mực: "+ink_group+ "?");
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
                        var url = "<?php echo base_url($production_line . '/updateMasterAuto?master='); ?>"+master+'&del='+delConf;
                        // console.log('url: ' + url);
                        ajaxRequest(jsonObjects, url);
                    }
                    
                });

            });


        } else {
            dhxWins2.window("WindowsDetail").show();
        }


    }


    // form 
    function masterFileForm(master) {
        if (master == 'master_item') {

            // struct
            masterFileFormStruct = [{
                    type: "settings",
                    position: "label-left",
                    labelWidth: 150,
                    inputWidth: 200
                },
                {
                    type: "fieldset",
                    label: "Master File",
                    width: 580,
                    blockOffset: 10,
                    offsetLeft: 30,
                    offsetTop: 30,
                    list: [{
                            type: "settings",
                            position: "label-left",
                            labelWidth: 120,
                            inputWidth: 120,
                            labelAlign: "left"
                        },
                        // { type: "select", id: "form_type", name: "form_type", label: "Form Type", style: "color:blue; ", required: true, validate: "NotEmpty", options: [
                        //     <?php
                                //         echo $form_type_local;
                                //     
                                ?>
                        // ]},
                        {
                            type: "input",
                            id: "form_type",
                            name: "form_type",
                            label: "Form Type:",
                            icon: "icon-input",
                            className: "",
                            required: true,
                            validate: "NotEmpty"
                        },
                        {
                            type: "input",
                            id: "internal_item",
                            name: "internal_item",
                            label: "Internal Item:",
                            icon: "icon-input",
                            className: "",
                            required: true,
                            validate: "NotEmpty"
                        },
                        {
                            type: "input",
                            id: "material_code",
                            name: "material_code",
                            label: "Material Code:",
                            icon: "icon-input",
                            className: "",
                            required: true,
                            validate: "NotEmpty"
                        },
                        {
                            type: "input",
                            id: "material_name",
                            name: "material_name",
                            label: "Material Name:",
                            icon: "icon-input",
                            className: "",
                            required: true,
                            validate: "NotEmpty"
                        },
                        {
                            type: "input",
                            id: "material_width",
                            name: "material_width",
                            label: "material Width:",
                            icon: "icon-input",
                            className: ""
                        },
                        {
                            type: "input",
                            id: "material_length",
                            name: "material_length",
                            label: "material Length:",
                            icon: "icon-input",
                            className: ""
                        },

                        {
                            type: "newcolumn",
                            "offset": 20
                        },

                        {
                            type: "input",
                            id: "product_type",
                            name: "product_type",
                            label: "Product Type:",
                            icon: "icon-input",
                            className: ""
                        },
                        {
                            type: "input",
                            id: "plan_type",
                            name: "plan_type",
                            label: "Plan Type:",
                            icon: "icon-input",
                            className: ""
                        },
                        {
                            type: "input",
                            id: "scrap",
                            name: "scrap",
                            label: "Scrap (HTL):",
                            icon: "icon-input",
                            className: "",
                            validate: "ValidNumeric"
                        },
                        {
                            type: "input",
                            id: "remark_1",
                            name: "remark_1",
                            label: "Remark 1:",
                            icon: "icon-input",
                            className: ""
                        },
                        {
                            type: "input",
                            id: "remark_2",
                            name: "remark_2",
                            label: "Remark 2:",
                            icon: "icon-input",
                            className: ""
                        },
                        {
                            type: "input",
                            id: "remark_3",
                            name: "remark_3",
                            label: "Remark 3:",
                            icon: "icon-input",
                            className: ""
                        }

                    ]
                },
                {
                    type: "fieldset",
                    label: "Chọn chức năng",
                    width: 580,
                    blockOffset: 10,
                    offsetLeft: 30,
                    offsetTop: 5,
                    list: [{
                            type: "button",
                            id: "update_master_main",
                            name: "update_master_main",
                            value: "<span style='color:red;font-weight:bold;'>Update</span>",
                            position: "label-center",
                            width: 150,
                            offsetLeft: 50
                        },
                        {
                            type: "newcolumn",
                            "offset": 50
                        },
                        {
                            type: "button",
                            id: "delete_master_main",
                            name: "delete_master_main",
                            value: "<span style='color:#cc0000;font-weight:bold;'>Delete</span>",
                            position: "label-center",
                            width: 150,
                            offsetLeft: 50
                        }
                    ]
                }
            ];

        } else if (master == 'master_process') {
            // struct
            masterFileFormStruct = [{
                    type: "settings",
                    position: "label-left",
                    labelWidth: 150,
                    inputWidth: 200
                },
                {
                    type: "fieldset",
                    label: "Master File",
                    width: 580,
                    blockOffset: 10,
                    offsetLeft: 30,
                    offsetTop: 30,
                    list: [{
                            type: "settings",
                            position: "label-left",
                            labelWidth: 120,
                            inputWidth: 120,
                            labelAlign: "left"
                        },

                        {
                            type: "input",
                            id: "internal_item",
                            name: "internal_item",
                            label: "Internal Item:",
                            icon: "icon-input",
                            className: "",
                            required: true,
                            validate: "NotEmpty"
                        },
                        {
                            type: "input",
                            id: "process",
                            name: "process",
                            label: "Process:",
                            icon: "icon-input",
                            className: "",
                            required: true,
                            validate: "NotEmpty"
                        },
                        {
                            type: "input",
                            id: "process_code",
                            name: "process_code",
                            label: "Process Code:",
                            icon: "icon-input",
                            required: true,
                            validate: "NotEmpty"
                        },
                        {
                            type: "input",
                            id: "process_name_vi",
                            name: "process_name_vi",
                            label: "Process Name (vi):",
                            icon: "icon-input"
                        },

                        {
                            type: "newcolumn",
                            "offset": 20
                        },

                        {
                            type: "input",
                            id: "order",
                            name: "order",
                            label: "Thứ tự:",
                            icon: "icon-input",
                            className: "",
                            required: true,
                            validate: "ValidNumeric",
                            labelWidth: "150",
                            width: "100"
                        },
                        {
                            type: "input",
                            id: "frame",
                            name: "frame",
                            label: "Khung:",
                            icon: "icon-input",
                            className: "",
                            labelWidth: "150",
                            width: "100"
                        },
                        {
                            type: "input",
                            id: "passes",
                            name: "passes",
                            label: "Số lượt:",
                            icon: "icon-input",
                            className: "",
                            validate: "ValidNumeric",
                            labelWidth: "150",
                            width: "100"
                        },
                        {
                            type: "input",
                            id: "setup_time",
                            name: "setup_time",
                            label: "Thời gian canh chỉnh (HTL):",
                            icon: "icon-input",
                            validate: "ValidNumeric",
                            labelWidth: "150",
                            width: "100"
                        },
                        {
                            type: "input",
                            id: "setup_sheet",
                            name: "setup_sheet",
                            label: "Số tờ canh chỉnh (HTL):",
                            icon: "icon-input",
                            validate: "ValidNumeric",
                            labelWidth: "150",
                            width: "100"
                        },

                    ]
                },
                {
                    type: "fieldset",
                    label: "Chọn chức năng",
                    width: 580,
                    blockOffset: 10,
                    offsetLeft: 30,
                    offsetTop: 5,
                    list: [{
                            type: "button",
                            id: "update_master_process",
                            name: "update_master_process",
                            value: "<span style='color:red;font-weight:bold;'>Update</span>",
                            position: "label-center",
                            width: 150,
                            offsetLeft: 50
                        },
                        {
                            type: "newcolumn",
                            "offset": 50
                        },
                        {
                            type: "button",
                            id: "delete_master_process",
                            name: "delete_master_process",
                            value: "<span style='color:#cc0000;font-weight:bold;'>Delete</span>",
                            position: "label-center",
                            width: 150,
                            offsetLeft: 50
                        }
                    ]
                }
            ];

        }

        // init 
        masterfileForm = masterfileLayout.cells("b").attachForm(masterFileFormStruct);

        // Validation live
        masterfileForm.enableLiveValidation(true);

        // attach button
        masterfileForm.attachEvent("onButtonClick", function(name) {
            
            if (name == 'delete_master_main' ) {
                var internal_item = masterfileForm.getItemValue('internal_item');
                var confirm_del = confirm("Bạn chắc chắn muốn xóa Item: " + internal_item + "?");
                if (!confirm_del ) return false;

            } else if (name == 'delete_master_process' ) {
                var internal_item = masterfileForm.getItemValue('internal_item');
                var process = masterfileForm.getItemValue('process');
                var confirm_del = confirm("Bạn chắc chắn muốn xóa Process (Item): " + process + " (" + internal_item + ") ?");
                if (!confirm_del ) return false;
            }

            var values = masterfileForm.getFormData();
            // console.log('values'+JSON.stringify(values) );
            var url = "<?php echo base_url($production_line . '/updateMasterForm/'); ?>"+name;
            ajaxRequest( values, url );

        });
    }

    function loadMasterFileForm(master) 
    {
        if (master == 'master_item') {

            if (masterfileGrid.getRowsNum()) {

                // select row 0
                masterfileGrid.selectRow(0, true);

                // attach row select
                masterfileGrid.attachEvent("onRowSelect", function(rId, ind) {

                    // get data
                    var form_type = masterfileGrid.cells(rId, 1).getValue().trim();
                    var internal_item = masterfileGrid.cells(rId, 2).getValue().trim();
                    var material_code = masterfileGrid.cells(rId, 3).getValue().trim();
                    var material_name = masterfileGrid.cells(rId, 4).getValue().trim();
                    var material_width = masterfileGrid.cells(rId, 5).getValue().trim();

                    var material_length = masterfileGrid.cells(rId, 6).getValue().trim();
                    var product_type = masterfileGrid.cells(rId, 7).getValue().trim();
                    var plan_type = masterfileGrid.cells(rId, 8).getValue().trim();
                    var scrap = masterfileGrid.cells(rId, 9).getValue().trim();
                    var remark_1 = masterfileGrid.cells(rId, 10).getValue().trim();
                    var remark_2 = masterfileGrid.cells(rId, 11).getValue().trim();
                    var remark_3 = masterfileGrid.cells(rId, 12).getValue().trim();

                    // set form data
                    masterfileForm.setItemValue('form_type', form_type);
                    masterfileForm.setItemValue('internal_item', internal_item);
                    masterfileForm.setItemValue('material_code', material_code);
                    masterfileForm.setItemValue('material_name', material_name);
                    masterfileForm.setItemValue('material_width', material_width);

                    masterfileForm.setItemValue('material_length', material_length);
                    masterfileForm.setItemValue('product_type', product_type);
                    masterfileForm.setItemValue('plan_type', plan_type);
                    masterfileForm.setItemValue('scrap', scrap);
                    masterfileForm.setItemValue('remark_1', remark_1);

                    masterfileForm.setItemValue('remark_2', remark_2);
                    masterfileForm.setItemValue('remark_3', remark_3);

                    // set read only
                    masterfileForm.setReadonly("form_type", true);
                    masterfileForm.setReadonly("internal_item", true);

                });
            };
        } else if (master == 'master_process') {
            if (masterfileGrid.getRowsNum()) {

                // select row 0
                masterfileGrid.selectRow(0, true);

                // attach row select
                masterfileGrid.attachEvent("onRowSelect", function(rId, ind) {

                    // get data
                    var internal_item = masterfileGrid.cells(rId, 1).getValue().trim();
                    var process = masterfileGrid.cells(rId, 2).getValue().trim();
                    var process_name_vi = masterfileGrid.cells(rId, 3).getValue().trim();
                    var process_code = masterfileGrid.cells(rId, 4).getValue().trim();
                    var order = masterfileGrid.cells(rId, 5).getValue().trim();

                    var frame = masterfileGrid.cells(rId, 6).getValue().trim();
                    var passes = masterfileGrid.cells(rId, 7).getValue().trim();
                    var setup_time = masterfileGrid.cells(rId, 8).getValue().trim();

                    var setup_sheet = masterfileGrid.cells(rId, 9).getValue().trim();

                    // set form data
                    masterfileForm.setItemValue('internal_item', internal_item);
                    masterfileForm.setItemValue('process', process);
                    masterfileForm.setItemValue('process_name_vi', process_name_vi);
                    masterfileForm.setItemValue('order', order);
                    masterfileForm.setItemValue('process_code', process_code);
                    masterfileForm.setItemValue('frame', frame);

                    masterfileForm.setItemValue('passes', passes);
                    masterfileForm.setItemValue('setup_time', setup_time);
                    masterfileForm.setItemValue('setup_sheet', setup_sheet);

                    // set read only
                    masterfileForm.setReadonly("internal_item", true);
                    masterfileForm.setReadonly("process", true);
                    // masterfileForm.setReadonly("process_code", true);
                    masterfileForm.setReadonly("order", true);

                });
            };
        }
    }

    // imports
    function importMasterFile(master) {
        var link = "<?php echo base_url($production_line . '/importMasterFile?master='); ?>";
        var link_suf = '';
        var set_text = '';
        if (master == 'master_item') {
            link_suf = 'masterFile';
            set_text = 'Imports Master File';
        } else if (master == 'master_process') {
            link_suf = 'masterProcess';
            set_text = 'Imports Master Process';
        }

        link = link + link_suf;


        if (!dhxWins) {
            dhxWins = new dhtmlXWindows();
        }

        var id = "WindowsDetail";
        var w = 400;
        var h = 100;
        var x = Number(($(window).width() - 400) / 2);
        var y = Number(($(window).height() - 50) / 2);
        var Popup = dhxWins.createWindow(id, x, y, w, h);
        dhxWins.window(id).setText(set_text);
        Popup.attachHTMLString(
            '<div style="width:500%;margin:20px">' +
            '<form action="' + link + '" enctype="multipart/form-data" method="post" accept-charset="utf-8">' +
            '<input type="file" name="file" id="file" class="form-control filestyle" value="value" data-icon="false"  />' +
            '<input type="submit" name="importfile" value="Import" id="importfile-id" class="btn btn-block btn-primary"  />' +
            '</form>' +
            '</div>'
        );
    }

    function exportMasterFile(master) {

        var link = "<?php echo base_url($production_line . '/exportMasterFile?master='); ?>";
        var link_suf = '';
        if (master == 'master_item') {
            link_suf = 'masterFile';
        } else if (master == 'master_process') {
            link_suf = 'masterProcess';
        }

        link += link_suf;
        location.href = link;

    }

    // delete po no
    function delete_confirm(po_no) {

        var conf = confirm("Bạn có chắc chắn muốn xóa NO# " + po_no + " ?");
        if (!conf ) {
            return false;
        } else {
            window.location = '<?php echo base_url('htl/delete/'); ?>'+po_no;
        }
        
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

    
</script>