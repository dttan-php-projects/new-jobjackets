<script>
    var mainMenu;
    var mainToolbar;
    var mainLayout, handleLayout, sizeLayout, masterDataLayout, supplyLayout, mainUserLayout, editUserLayout, masterfileLayout;
    var myAccountForm, orderForm, createUserForm, editUserForm, masterfileFormm, createMasterDataForm;
    var myPop;
    var mainviewGrid, orderGrid, sizeGrid, masterDataGrid, supplyGrid, glueGrid, mainUserGrid, masterfileGrid;
    var automail_updated = "<?php echo !empty($automail_updated) ? $automail_updated : 'loading...'; ?>";
    var base_url = '<?php echo base_url(); ?>';

    var checkboxOrdersArr = [];
    var sizeCheckArr = [];
    // var so_cai_total = 0;
    var count_size = 0;
    var selectedRowIdMasterData;
    var machineType_check, item_check, length_btp_check, folding_cut_type;

    var dhxWins;
    var order_type_cookie = getCookie('plan_order_type');
    var count_ms = 0;

    var pm_scrap_check = 0;
    var pm_scrap = 0;

    /*
        | ------------------------------------------------------------------------------------------------------------
        | 1.  ON LOAD
        | ------------------------------------------------------------------------------------------------------------
    */
    function doOnLoad() {
        $(document).ready(function() {
            initMainMenu();

            initMainToolbar();

            //get Soline input
            mainToolbar.getInput("batch_number_input").focus();
            order_type_cookie = getCookie('plan_order_type');
            if (order_type_cookie) {
                if (order_type_cookie == 'common') {
                    mainToolbar.getInput("batch_number_input").focus();
                } else if (order_type_cookie == 'ccr') {
                    mainToolbar.getInput("so_line_input").focus();
                } else if (order_type_cookie == 'buildstock') {
                    mainToolbar.getInput("item_input").focus();
                }
            }

            inputData();
            onClickMainToolbar();
        });


    }

    /*
        | ------------------------------------------------------------------------------------------------------------
        | 2.  CHECK INPUT DATA
        | ------------------------------------------------------------------------------------------------------------
    */
    // on Enter attach 

    function inputData() {
        // attach events
        mainToolbar.attachEvent("onEnter", function(itemId) {
            //get Soline input and item input
            so_line_input = mainToolbar.getInput("so_line_input");
            item_input = mainToolbar.getInput("item_input");
            batch_number_input = mainToolbar.getInput("batch_number_input");

            // check input. User input SOLine or item or (soline and item).
            if (itemId == "batch_number_input") {

                var batch_number = batch_number_input.value;

                /*
                    | ------------------------------------------------------------------------------------------------------------
                    | TR?????NG H???P NON BATCHING.  ????N NON BATCHING.
                    |       - Nh???p v??o l?? SOLine nh??ng nh???p v??o v??? tr?? ????n batching
                    |       - T??? SOLine n??y l???y ra s??? batch_no trong d??? li???u l??u b??n Prepress
                    |       - Check batch_no c?? l??m l???nh ch??a? N???u c?? th?? th??ng b??o c?? mu???n s???a k?
                    |       - Ch??a l??m l???nh th?? chuy???n sang function l??m l???nh s???n xu???t
                    | ------------------------------------------------------------------------------------------------------------
                */

                if (batch_number.indexOf('-') !== -1) {

                    checkNonBatching(batch_number);

                } else {
                    /*
                        | ------------------------------------------------------------------------------------------------------------
                        | TH1. ????N BATCHING HO???C BATCHING FOD. FOD l?? ????n m?? item ???? ???????c l??m l???nh sx l???n ?????u ti??n.
                        |       - Check batch_no c?? l??m l???nh ch??a? N???u c?? th?? th??ng b??o c?? mu???n s???a k?
                        |       - Ch??a l??m l???nh th?? chuy???n sang function l??m l???nh s???n xu???t
                        | ------------------------------------------------------------------------------------------------------------
                    */

                    checkBatchingExist(batch_number);

                    // commonOrder(batch_number_input.value); // ????n n??y l??m sau
                    batch_number_input.focus(); // set focus
                    batch_number_input.value = ''; // set focus
                }




            } else if (itemId == "so_line_input") {

                // check SOLine
                checkSOLineInput(so_line_input.value);
                if (error) {
                    alert(message);
                    location.reload();
                    return false;
                }

                // next to item input
                item_input.focus(); // set focus

            } else if (itemId == "item_input") {

                checkItemInput(item_input.value);
                if (error) {
                    alert(message);
                    location.reload();
                    return false;
                } else {

                    checkItemExist(item_input.value);



                }





                item_input.focus(); // set focus
            }

        });
    }

    // Check soline input exactly
    function checkSOLineInput(input_data) {
        error = 0;
        input_data = input_data.trim();
        input_data = input_data.replace(" ", "");

        if (!input_data) {
            message = "[ERROR 01.01]. VUI L??NG NH???P SO# !";
            error = 1;
        } else {
            if (input_data.length < 10 || input_data.length > 13) {
                message = "[ERROR 01.02]. B???N ???? NH???P SAI SO#, VUI L??NG NH???P L???I !!";
                error = 1;
            } else {
                //t??m xem input c?? d???u "-" kh??ng? //Tr?????ng h???p nh???p Order number (SO), kh??ng nh???p line
                if (input_data.search("-") == -1) {
                    message = "[ERROR 01.03]. B???N ???? NH???P SAI SO#, VUI L??NG NH???P L???I !!! ";
                    error = 1;

                } else { //Tr?????ng h???p nh???p line
                    if (input_data.length < 10) {
                        message = "[ERROR 01.04]. B???N ???? NH???P SAI SO#, VUI L??NG NH???P L???I !!!! ";
                        error = 1;
                    } else {
                        // detached
                        detachedSOLine(input_data);

                        if (order_number.length != 8) {
                            message = "[ERROR 01.05]. B???N ???? NH???P SAI SO#, VUI L??NG NH???P L???I !!!! ";
                            error = 1;
                        } else if (order_number.length == 8 && (line_number.length == 0 || line_number.length > 4)) {
                            message = "[ERROR 01.05]. B???N ???? NH???P SAI SO#, VUI L??NG NH???P L???I !!!! ";
                            error = 1;
                        } else {

                            //Khong phai so
                            if (isNaN(order_number) == true || isNaN(line_number) == true) {
                                message = "[ERROR 01.06]. B???N ???? NH???P SAI SO#, VUI L??NG NH???P L???I !!!! ";
                                error = 1;
                            }
                        }
                    }

                }

            }
        }

    } // END

    function checkSOLineExist(so_line_input, item_input) {
        // detached
        detachedSOLine(so_line_input);

        //json data encode
        var jsonObjects = {
            "order_number": order_number,
            "line_number": line_number
        };
        var url = "<?php echo base_url('/woven/checkSOLineExist/'); ?>";

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
                var agreeEdit;
                if (data.status == false) {

                    alert(data.message);
                    location.reload();
                    return false;

                } else {

                    agreeEdit = confirm(data.message);
                    if (agreeEdit) {

                        // L??m ti???p ????n h??ng b?? (CCR)
                        loadData(so_line_input, item_input);

                    } else {

                        location.href = "<?php echo base_url('woven'); ?>";
                        return false;

                    }
                }

            },
            error: function(xhr, status, error) {
                // alert(error);alert(xhr.responseText);
                // alert('Error(check): Kh??ng check ???????c soline t???n t???i. Vui l??ng li??n h??? qu???n tr??? h??? th???ng! '+xhr.responseText);
                alert('Error(check): Kh??ng check ???????c soline t???n t???i. Vui l??ng li??n h??? qu???n tr??? h??? th???ng!');
                location.reload();
                return false;
            }
        });
    } // END

    function checkItemInput(input_data) {
        error = 0;
        input_data = input_data.trim();
        input_data = input_data.replace(" ", "");

        if (!input_data) {
            message = "[ERROR 01.01]. VUI L??NG NH???P ITEM# !";
            error = 1;
        } else {
            if (input_data.length < 6) {
                message = "[ERROR 01.02]. ????? D??I ITEM KH??NG NH??? H??N 5 K?? T???, VUI L??NG NH???P L???I !!";
                error = 1;
            }
        }

    }

    function checkItemExist(item) {
        //json data encode
        var jsonObjects = {
            "item": item
        };
        var url = "<?php echo base_url('woven/checkItemExist/'); ?>";

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
                    if (!so_line_input.value) {
                        /*
                            | ------------------------------------------------------------------------------------------------------------
                            | TH3. ????N BUILD STOCK. Ch??? nh???p Item. L???y d??? li???u t??? master data. S??? LSX c?? ?????u l???nh l??: NO
                            | ------------------------------------------------------------------------------------------------------------
                        */

                        loadData('', item_input.value);

                    } else {
                        // check SOLine
                        checkSOLineInput(so_line_input.value);
                        if (error) {
                            alert(message);
                            location.reload();
                            return false;
                        }

                        checkSOLineExist(so_line_input.value, item_input.value);

                    }
                }

            },
            error: function(xhr, status, error) {
                // alert(error);alert(xhr.responseText);
                // alert('Error(check): Kh??ng check ???????c soline t???n t???i. Vui l??ng li??n h??? qu???n tr??? h??? th???ng! '+xhr.responseText);
                alert('Error(check): Kh??ng check ???????c Item t???n t???i. Vui l??ng li??n h??? qu???n tr??? h??? th???ng!');
                location.reload();
                return false;
            }
        });
    }

    function checkBatchingExist(batch_no) {
        //json data encode
        var jsonObjects = {
            "batch_no": batch_no
        };
        var url = "<?php echo base_url('woven/checkBatchingExist/'); ?>";

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
                // l??u cookie ????n batching
                setCookie('plan_order_type', 'common', 365);
                setCookie('non_batching', '', 365);

                if (data.status == false) {
                    var confirm_user = confirm(data.message);
                    if (!confirm_user) {
                        window.location = '<?php echo base_url('woven'); ?>';
                        return false;

                    } else {
                        // S???a ????n th?????ng ho???c FOD (c?? s??? batch)
                        editCommonOrder(batch_no);
                    }

                } else {
                    // ????n th?????ng ho???c FOD (c?? s??? batch)
                    commonOrder(batch_no);
                }

            },
            error: function(xhr, status, error) {
                // alert(error);alert(xhr.responseText);
                // alert('Error(check): Kh??ng check ???????c soline t???n t???i. Vui l??ng li??n h??? qu???n tr??? h??? th???ng! '+xhr.responseText);
                alert('Error(check): Kh??ng check ???????c ????n Batching t???n t???i. Vui l??ng li??n h??? qu???n tr??? h??? th???ng!');
                location.reload();
                return false;
            }
        });
    }

    // check ????n Non Batching ???? l??m l???nh ch??a.
    function checkNonBatching(batch_no) {
        //json data encode
        var jsonObjects = {
            "batch_no": batch_no
        };
        var url = "<?php echo base_url('woven/checkNonBatchingExist/'); ?>";

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

                // l???y s??? batch_no ????ng (S??? nh???p v??o hi???u l?? batch_no nh??ng b???n ch???t l?? SOLine ???????c nh???p v??o)
                batch_no = data.batch_no;

                // l??u cookie ????n non batching
                setCookie('plan_order_type', 'common', 365);
                setCookie('non_batching', 'non_batching', 365);

                if (data.status == false) {
                    var confirm_user = confirm(data.message);
                    if (!confirm_user) {
                        window.location = '<?php echo base_url('woven'); ?>';
                        return false;

                    } else {
                        // s???a ????n non batching
                        editCommonOrder(batch_no);
                    }

                } else {
                    // ????n non batching
                    commonOrder(batch_no);
                }

            },
            error: function(xhr, status, error) {
                alert('Error(check): Kh??ng check ???????c ????n Non Batching t???n t???i. Vui l??ng li??n h??? qu???n tr??? h??? th???ng!');
                location.reload();
                return false;
            }
        });
    }


    /*
        | ------------------------------------------------------------------------------------------------------------
        | 3.  INIT FUNCTION 
        | ------------------------------------------------------------------------------------------------------------
    */

    function initMainMenu() {
        mainMenu = new dhtmlXMenuObject({
            parent: "mainMenu",
            iconset: "awesome",
            json: "<?php echo base_url('assets/xml/woven_main_menu.xml'); ?>",
            top_text: "<?php echo "<img style='width:60px;' src='" . base_url('assets/media/images/Logo.PNG') . "'/>&nbsp;&nbsp;&nbsp; WOVEN PRODUCTION PLAN PROGRAM "; ?>"
        });
        mainMenu.setAlign("right");

        mainMenu.attachEvent("onClick", function(id) {
            if (id !== "home") {
                if (id == 'import') {
                    if (Number(getCookie('plan_account_type') == 3)) {
                        importMaster();
                    } else {
                        alert('B???n kh??ng c?? quy???n truy c???p ch???c n??ng n??y');
                    }

                } else if (id == 'exportMaster' || id == 'exportMasterSupply') {
                    if (Number(getCookie('plan_account_type') == 3)) {
                        exportMasterData(id);
                    } else {
                        alert('B???n kh??ng c?? quy???n truy c???p ch???c n??ng n??y');
                    }

                } else if (id == 'view') {
                    if (Number(getCookie('plan_account_type') == 3)) {
                        location.href = "<?php echo base_url('woven/viewMasterFile'); ?>";
                    } else {
                        alert('B???n kh??ng c?? quy???n truy c???p ch???c n??ng n??y');
                    }

                } else if (id == 'add_item') {
                    // initCreateMasterDataWindow();
                    if (Number(getCookie('plan_account_type') == 3)) {
                        location.href = "<?php echo base_url('woven/createMasterItem2'); ?>";
                    } else {
                        alert('B???n kh??ng c?? quy???n truy c???p ch???c n??ng n??y');
                    }

                } else if (id == 'report') {
                    // Init calendar, attach from date and to date 
                    var from_date = mainToolbar.getValue("from_date");
                    var to_date = mainToolbar.getValue("to_date");

                    report(from_date, to_date);

                } else if (id == 'view_user') {
                    if (Number(getCookie('plan_account_type') == 3)) {
                        location.href = "<?php echo base_url('users/recent') ?>";
                    } else {
                        alert('B???n kh??ng c?? quy???n truy c???p ch???c n??ng n??y');
                    }

                } else if (id == 'create_user') {
                    if (Number(getCookie('plan_account_type') == 3)) {
                        initCreateUserWindow('');
                    } else {
                        alert('B???n kh??ng c?? quy???n truy c???p ch???c n??ng n??y');
                    }

                } else if (id == 'sample_file') {
                    downloadSampleFile();
                } else if (id == 'importSpecialItem') {
                    importSpecialItem();
                } else if (id == 'importSpecialTable') {
                    importSpecialTable();
                } else if (id == 'importGYCG2') {
                    importGYCG2();
                } else if (id == 'remarks') {
                    location.href = "<?php echo base_url('remarks'); ?>";
                } else if (id == 'view_distance') {
                    // Init calendar, attach from date and to date 
                    var from_date = mainToolbar.getValue("from_date");
                    var to_date = mainToolbar.getValue("to_date");
                    var suffix_url_views = '?from_date=' + from_date + '&to_date=' + to_date;
                    location.href = "<?php echo base_url('woven/index/'); ?>" + suffix_url_views;
                } else if (id == 'file_name_master') {
                    fileNameGrid();
                }  else if (id == 'updateItemKiem100') {
                    updateItemKiem100();
                } else if (id == 'uploadThreadLength' ) {
                    uploadThreadLength();
                }

            } else {
                if (!getCookie('plan_account_type')) {
                    alert('B???n kh??ng c?? quy???n truy c???p ch???c n??ng n??y');
                } else {
                    location.href = "<?php echo base_url('woven'); ?>";
                }

            }
        });
    }

    function initMainToolbar() {
        // attach to sidebar
        // mainToolbar = new dhtmlXToolbarObject("mainToolbar");
        mainToolbar = new dhtmlXToolbarObject({
            parent: "mainToolbar",
            icons_size: 18,
            iconset: "awesome"
        });
        // init item
        mainToolbar.addButton("batch_number_label", 1, "<span style='color:green;font-weight:bold;font-size:13px;'>Batch Number#:</span>", "fa fa-sticky-note");
        mainToolbar.addInput("batch_number_input", 2, "", 100);
        mainToolbar.addButton("so_line_label", 3, "<span style='color:blue;font-weight:bold;font-size:13px;'>SOLine</span>", "fa fa-fire");
        mainToolbar.addInput("so_line_input", 4, "", 100);
        mainToolbar.addButton("item_label", 5, "<span style='color:blue;font-weight:bold;font-size:13px;'>Item</span>", "fa fa-info-circle fa-2x");
        mainToolbar.addInput("item_input", 6, "", 120);
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
                    // {type: "label", label: username_label, offsetRight: 10 },
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
                text: "T???NG DANH S??CH ????N H??NG"
            }]
        });

        var url = "<?php echo base_url('woven/countOrders'); ?>";
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
                mainLayout.cells("a").setText('T???NG ????N H??NG: <span style="color:red;font-size:15px;">' + countAll + '</span> || ????N H??NG H??M NAY (' + now + '): <span style="color:red;font-size:15px;">' + countNow + '</span> ');
            },
            error: function(xhr, status, error) {
                alert('Load t???ng s??? l?????ng ????n h??ng l???i');
                return false;
            }
        });

    }

    function initMainViewGrid(from_date, to_date) {
        mainviewGrid = mainLayout.cells("a").attachGrid();
        mainviewGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
        mainviewGrid.setHeader("Order Type, Created Date, NO#, Type, Data, Quantity, RBO, Item - Length, Updated By, Updated Date, Print, Edit, Delete"); //sets the headers of columns
        mainviewGrid.setColumnIds("form_type, po_date, po_no, po_no_suffix, soline, qty, rbo, item_length, update_by, update_date, print, edit, delete"); //sets the columns' ids
        mainviewGrid.setInitWidths("100,100,140,100,120,90,120,*,120,110,90,90,90"); //sets the initial widths of columns
        mainviewGrid.setColAlign("center,center,center,center,center,center,center,center,center,center,center,center,center"); //sets the alignment of columns
        mainviewGrid.setColTypes("ed,ed,ed,ed,ed,edn,ed,ed,ed,ed,ed,ed,ed"); //sets the types of columns
        mainviewGrid.setColSorting("str,str,str,str,str,str,str,str,str,str,str,str,str"); //sets the sorting types of columns
        mainviewGrid.enableSmartRendering(true);

        mainviewGrid.setColumnColor(",,,#d5f1ff,,#d5f1ff,#d5f1ff");
        mainviewGrid.setStyle("font-weight:bold; font-size:13px;text-align:center;color:#990000;", "font-size:12px;", "", "font-weight:bold;color:#0000ff;font-size:14px;");

        //L??u ??: filter v?????t qu?? 26 b??? l???i
        mainviewGrid.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
        mainviewGrid.enableMultiselect(true);
        mainviewGrid.init();

        loadMainViewGrid(from_date, to_date);

    }

    function initHandleLayout() {
        handleLayout = new dhtmlXLayoutObject({
            parent: document.body,
            pattern: "3J",
            offsets: {
                top: 64,
                bottom: 5
            },
            cells: [
                // {id: "a", header: true, text: "Order Grid", width: 300},
                {
                    id: "a",
                    header: true,
                    text: "Order Data Form",
                    width: 900,
                    height: 400
                },
                {
                    id: "b",
                    header: true,
                    text: "Order Size & Master Data"
                },
                {
                    id: "c",
                    header: true,
                    text: "Order Data Grid"
                }
            ]
        });
    }

    // init form Order
    function initOrderForm(internal_item) {


        //json data encode
        var jsonObjects = {};
        var url = "<?php echo base_url('woven/getProcessItem/?internal_item='); ?>" + internal_item;

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

                let process_string_list = data.process_string;
                let process_arr = process_string_list.split('-');

                var string = '[{type: "settings", position: "label-left", labelWidth: "auto", inputWidth: "auto" },{type: "fieldset", label: "Th??ng tin chi ti???t", width: "auto", blockOffset: 0, offsetLeft: "10", offsetTop: "10",';
                string += 'list: [';
                string += '{type: "settings", position: "label-left", labelWidth: 120, inputWidth: 90, labelAlign: "left"},';
                string += '{type: "input", id: "orderedType", name: "orderedType", label: "Ordered Type:", icon: "icon-input", required: true, validate: "NotEmpty"},';
                string += '{type: "input", id: "wire_number", name: "wire_number", label: "S??? D??y:", icon: "icon-input", required: true, validate: "NotEmpty"}, ';
                string += '{type: "input", id: "gear_density", name: "gear_density", label: "M???t ????? B??nh R??ng:", icon: "icon-input", required: true, validate: "NotEmpty" },';
                string += '{type: "input", id: "count_size", name: "count_size", label: "T????ng s???? Size:", icon: "icon-input", required: true, validate: "NotEmpty" },';
                string += '{type: "input", id: "pick_number_total", name: "pick_number_total", label: "T???ng S??? Pick:", icon: "icon-input", required: true, validate: "NotEmpty" },';
                string += '{type: "input", id: "textile_size_number", name: "textile_size_number", label: "S??? Kh???:", icon: "icon-input", required: false},';
                string += '{type: "input", id: "warp_yarn_number", name: "warp_yarn_number", label: "S??? S???i D???c:", icon: "icon-input", required: false},';
                string += '{type: "input", id: "board", name: "board", label: "BOARD:", icon: "icon-input", required: false},';
                string += '{type: "input", id: "qty_total", name: "qty_total", label: "T???ng S??? L?????ng:", icon: "icon-input", required: true, validate: ""},';
                string += '{type: "input", id: "running_time_total", name: "running_time_total", label: "Th???i gian ch???y:", icon: "icon-input", className: "", validate: "ValidNumeric" },';

                string += '{type: "newcolumn", "offset": 20},';
                string += '{type: "input", id: "formNO", name: "formNO", label: "NO#:", labelAlign: "left", icon: "icon-input"},';
                string += '{type: "calendar", id: "formDate", name: "formDate", label: "Ng??y l??m l???nh:", icon: "icon-input", dateFormat: "%d-%m-%Y", className: "", required: true, validate: "NotEmpty" },';
                string += '{type: "input", id: "item", name: "item", label: "Item:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },';
                string += '{type: "input", id: "rbo",name: "rbo", label: "RBO:",icon: "icon-input", className: "", required: true, validate: "NotEmpty" },';
                string += '{type: "input", id: "length_tp", name: "length_tp", label: "Length TP (mm):", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },';
                string += '{type: "input", id: "width_tp", name: "width_tp", label: "Width TP (mm):", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },';
                string += '{type: "input", id: "length_btp", name: "length_btp", label: "Length BTP (mm):", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },';
                string += '{type: "input", id: "width_btp", name: "width_btp", label: "Width BTP (mm):", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },';
                string += '{type: "checkbox", id: "fod", name: "fod", label: "FOD:", icon: "icon-input" },';
                string += '{type: "newcolumn", "offset": 20 },';
                string += '{type: "calendar", id: "orderedDate", name: "orderedDate", label: "Ordered Date:", icon: "icon-input", dateFormat: "%Y-%m-%d", className: "", required: true, validate: "NotEmpty"},';
                string += '{type: "calendar", id: "requestDate", name: "requestDate", label: "Request Date:", icon: "icon-input", dateFormat: "%Y-%m-%d", className: "", required: true, validate: "NotEmpty" },';
                string += '{type: "calendar", id: "promiseDate", name: "promiseDate", label: "Promise Date:", icon: "icon-input", dateFormat: "%Y-%m-%d", className: "" },';
                string += '{type: "input", id: "pattern", name: "pattern", label: "S??? Pattern:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },';
                string += '{type: "input", id: "need_vertical_thread_number", name: "need_vertical_thread_number", label: "Ch??? d???c c???n(kg):", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },';
                string += '{ type: "input", id: "folding_cut_type", name: "folding_cut_type", label: "Lo???i c???t g???p:", icon: "icon-input", className: "", required: true, validate: "NotEmpty" },';
                string += '{type: "input", id: "water_glue_rate", name: "water_glue_rate", label: "Ti?? l???? h????/n??????c:", icon: "icon-input" },';
                string += '{type: "checkbox", id: "meters_per_roll_check", name: "meters_per_roll_check", label: "M??t/Cu???n:", icon: "icon-input" },';
                string += '{type: "input", id: "socai_group_total", name: "socai_group_total", label: "S??? c??i t???ng:", icon: "icon-input", className: "", validate: "ValidNumeric"},';
                string += '{type: "newcolumn", "offset": 35},';

                let setting_process = <?php echo json_encode($setting_process); ?>;
                let index = 0;
                for (var iP = 0; iP < process_arr.length; iP++) {

                    index++;
                    let process_name = 'process_' + index;

                    let process_code = process_arr[iP];

                    let process_name_label = '';

                    for (var i = 0; i < setting_process.length; i++) {
                        if (process_code == setting_process[i]['process_code']) {
                            process_name_label = process_code + '-' + setting_process[i]['process_name_vi'];
                            break;
                        }
                    }

                    string += '{ "type": "checkbox", id: "' + process_name + '", "name": "' + process_name + '", "label": "' + process_name_label + '", "labelWidth": "80", "inputWidth": "40" },';
                }

                string += ']}]';

                eval('var formStructure=' + string);
                orderForm = handleLayout.cells("a").attachForm(formStructure);

            },
            error: function(xhr, status, error) {
                alert(xhr.responseText);
                return false;
            },
            async: false

        });




    }


    function initSizeLayout() {
        sizeLayout = handleLayout.cells("b").attachLayout({
            pattern: "2E",
            cells: [{
                    id: "a",
                    text: "Order Size"
                },
                {
                    id: "b",
                    text: "Master & Supply Data"
                }
            ]
        });
    }

    function initOrderGrid(results) {
        var orderGrid_url = base_url + 'woven/';
        orderGrid = handleLayout.cells("c").attachGrid();
        orderGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
        orderGrid.setHeader("CHECK, SO-LINE, ITEM, QTY, ORDERED ITEM, ORDER TYPE, ORDERED DATE, REQUEST DATE, PROMISE DATE, SHIP TO, BILL TO, CS, PACKING INSTRUCTION, ATTACHMENT, BATCH_NO"); //sets the headers of columns
        orderGrid.setColumnIds("check,so_line,item,qty,ordered_item,order_type_name,ordered_date,request_date,promise_date,ship_to,bill_to,cs,packing_instr,attachment,batch_no"); //sets the columns' ids
        orderGrid.setInitWidths("55,90,110,70,110,110,110,110,110,150,150,100,200,200,50"); //sets the initial widths of columns
        orderGrid.setColAlign("center,center,center,center,center,center,center,center,center,center,center,center,center,center,center"); //sets the alignment of columns
        orderGrid.setColTypes("ch,ed,ed,ed,ed,ed,ed,ed,ed,txt,txt,txt,txt,txt,txt"); //sets the types of columns

        if (order_type_cookie == 'common') {
            orderGrid.setColTypes("ro,ed,ed,ed,ed,ed,ed,ed,ed,txt,txt,txt,txt,txt,txt"); //sets the types of columns
        }

        orderGrid.setColSorting("str,str,str,str,str,str,str,str,str,str,str,str,str,str,str"); //sets the sorting types of columns
        orderGrid.enableSmartRendering(true);
        //L??u ??: filter v?????t qu?? 26 b??? l???i
        // orderGrid.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
        orderGrid.enableMultiselect(true);
        orderGrid.init(); //dataProcessor 

        // load Order data info in automail
        loadOrderGrid(results);

        //check all the checkboxes in the first column
        orderGrid.setCheckedRows(0, 1);

    }

    function initsizeGrid() {
        sizeGrid = sizeLayout.cells("a").attachGrid();
        sizeGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
        sizeGrid.setHeader("CHECK,SIZE,SO CAI,SO-LINE"); //sets the headers of columns
        sizeGrid.setInitWidths("60,80,100,100"); //sets the initial widths of columns
        sizeGrid.setColAlign("center,center,center,center"); //sets the alignment of columns
        sizeGrid.setColTypes("ch,ed,ed,ed"); //sets the types of columns
        sizeGrid.setColSorting("str,str,str,str"); //sets the sorting types of columns
        sizeGrid.init();

        sizeGrid.attachEvent("onRowSelect", function(id, ind) { // Fire When user click on row in grid        
            // 
        });

    }

    function initMasterDataLayout() {
        masterDataLayout = sizeLayout.cells("b").attachLayout({
            pattern: "2E",
            cells: [{
                    id: "a",
                    text: "Master Data",
                    height: 200
                },
                {
                    id: "b",
                    text: "Supply Data",
                    width: 400
                }
            ]
        });
    }

    function initSupplyLayout() {
        supplyLayout = masterDataLayout.cells("b").attachLayout({
            pattern: "2U",
            cells: [{
                    id: "a",
                    text: "Supply Data"
                },
                {
                    id: "b",
                    text: "Glue Data",
                    width: 250
                }
            ]
        });
    }

    function initMasterDataGrid(results) {
        masterDataGrid = masterDataLayout.cells("a").attachGrid();
        masterDataGrid.setHeader("Machine, Length, Item, Rbo, S??? D??y, Lo???i Ch??? D???c, Lo???i C???t G???p, Pattern, M???t ????? B??nh R??ng, Chi???u D??i TP, Chi???u R???ng TP, CBS, Scrap, PP X???, TSKT CW, Nhi???t D???t, S??? M??t/M??y, T??? l??? H???/N?????c, S??? C??i Min, Taffeta/Satin, S??? Kh???, S??? D??y M???i, Remark 1, Remark 2, Remark 3, Updated By, Updated Date, Chi???u R???ng BTP, Special Remark, Process"); //sets the headers of columns
        masterDataGrid.setColumnIds("machine,length,item,rbo,wire_number,vertical_thread_type,folding_cut_type,pattern,gear_density,length_tp,width_tp,cbs,scrap,sawing_method,cw_specification,heat_weaving,meter_number_per_machine,water_glue_rate,so_cai_min,taffeta_satin,textile_size_number,new_wire_number,remark_1,remark_2,remark_3,updated_by,updated_date,width_btp,special_remark, process");
        masterDataGrid.setInitWidths("60,60,110,120,60,90,90,80,120,90,100,50,60,50,70,70,80,90,90,90,90,90,90,90,90,90,90,110,110,110");
        masterDataGrid.setColAlign("left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left");
        masterDataGrid.setColTypes("ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro");
        masterDataGrid.setColSorting("str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str");
        masterDataGrid.init();

        if (!masterDataGrid.getRowsNum()) {

            loadMasterDataGrid(results);
        }

    }

    function initSupplyGrid() {
        supplyGrid = supplyLayout.cells("a").attachGrid();
        supplyGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
        supplyGrid.setHeader("TT, M?? V???t T??, M???t ?????, S??? Pick, Chi???u D??i Ch???, Ch??? Ngang C???n"); //sets the headers of columns
        supplyGrid.setInitWidths("40,150,110,110,120,*"); //sets the initial widths of columns
        supplyGrid.setColAlign("left,left,left,left,left,left"); //sets the alignment of columns
        supplyGrid.setColTypes("ed,ed,ed,ed,ed,ed"); //sets the types of columns
        supplyGrid.setColSorting("str,str,str,str,str,str"); //sets the sorting types of columns
        supplyGrid.init();
        //supplyGrid.load(RootDataPath+'grid_so.php', function(){ //takes the path to your data feed        
        //}); 

        var colNumsupplyGrid = supplyGrid.getColumnsNum();
        var setColor = '';
        for (var i = 0; i < supplyGrid.getColumnsNum(); i++) {

            var evenId = i / 2;
            if (evenId == parseInt(evenId)) {
                if (setColor == '') {
                    setColor += 'e6ffff';
                } else {
                    setColor += ',e6ffff';
                }

            } else {
                if (setColor == '') {
                    setColor += 'e6f7ff';
                } else {
                    setColor += ',#e6f7ff';
                }
            }
        }

        supplyGrid.setColumnColor(setColor);

    }

    function initGlueGrid() {
        glueGrid = supplyLayout.cells("b").attachGrid();
        glueGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
        glueGrid.setHeader("TT, Keo, Qty"); //sets the headers of columns
        glueGrid.setInitWidths("40,*,40"); //sets the initial widths of columns
        glueGrid.setColAlign("left,left,left"); //sets the alignment of columns
        glueGrid.setColTypes("ed,ed,ed"); //sets the types of columns
        glueGrid.setColSorting("str,str,str"); //sets the sorting types of columns
        glueGrid.init();

    }

    // load order info
    function initHandlePage(results) {
        // init Layout
        initHandleLayout();
        initSizeLayout();
        initMasterDataLayout();
        initSupplyLayout();

        // init Grid & Form
        initOrderGrid(results);
        initsizeGrid();
        initMasterDataGrid(results);
        initSupplyGrid();
        initGlueGrid();
    }

    // master data: load main page
    function doloadMasterFile() {
        initMainMenu();
        initMasterFileToolbar();

    }

    // master data: main master layout
    function initMasterFileLayout() {
        masterfileLayout = new dhtmlXLayoutObject({
            parent: document.body,
            pattern: "2U",
            offsets: {
                top: 63,
                left: 10,
                right: 10,
                bottom: 10
            },
            cells: [{
                    id: "a",
                    header: true,
                    text: "DATA"
                },
                {
                    id: "b",
                    header: true,
                    text: "FORM EDIT",
                    width: 720
                }
            ]
        });
    }

    // master data: main master toolbar
    function initMasterFileToolbar() {
        masterfileToolbar = new dhtmlXToolbarObject({
            parent: "masterfileToolbar",
            icons_size: 18,
            iconset: "awesome"
        });

        // init item
        masterfileToolbar.addButton("masterfile_label", 1, "<span style='color:green;font-weight:bold;font-size:13px;'>MASTER FILE</span>", "fa fa-sticky-note");
        masterfileToolbar.addSpacer("masterfile_label");

        masterfileToolbar.addButton("main_masterfile", 4, "<span style='color:blue;font-weight:bold;font-size:13px;'>Main Master File</span>", "fa fa-file-excel-o");
        masterfileToolbar.addSeparator("separator_1", 5);

        masterfileToolbar.addButton("supply", 7, "<span style='color:blue;font-weight:bold;font-size:13px;'>Supply</span>", "fa fa-file-excel-o fa-2x");
        masterfileToolbar.addSeparator("separator_2", 9);

        masterfileToolbar.addButton("process", 11, "<span style='color:blue;font-weight:bold;font-size:13px;'>Process</span>", "fa fa-file-excel-o");
        masterfileToolbar.addSeparator("separator_3", 20);

        // initMasterFileGrid();
        // initMasterFileForm();


        masterfileToolbar.attachEvent("onClick", function(name) {
            if (name == "main_masterfile") {
                initMasterFileGrid();
                initMasterFileForm();
            } else if (name == "supply") {
                initMasterFileSupplyGrid();
                initMasterFileSupplyForm();
            } else if (name == "process") {
                masterProcess();
            }

        });

    }

    function viewMasterFile() {
        initMasterFileLayout();
        initMasterFileGrid();
        initMasterFileForm();
    }


    // master data: update main master grid
    function initMasterFileGrid() {

        masterfileLayout.cells("a").progressOn();
        masterfileGrid = masterfileLayout.cells("a").attachGrid();
        masterfileGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
        masterfileGrid.setHeader("NO#, Machine, Item, Lenght BTP, Width BTP, RBO, S??? D??y, Lo???i Ch??? D???c, Lo???i C???t G???p, Pattern, B??nh R??ng, Length TP, Width TP, CBS, Scrap, Lo???i C???t, PP X???, TSKT CW, Nhi???t D???t, M??t/M??y, T??? L??? H???/N?????c, S??? C??i Min, Taffeta/Satin, S??? Kh???, S??? D??y M???i, Scrap Sonic, Remark 1, Remark 2, Remark 3, Updated By, Updated Date, Special Remark, Process");
        masterfileGrid.setColumnIds("no,machine_type,internal_item,length_btp,width_btp,rbo,wire_number,vertical_thread_type,folding_cut_type,pattern,gear_density,length_tp,width_tp,cbs,scrap,cut_type,sawing_method,cw_specification,heat_weaving,meter_number_per_machine,water_glue_rate,so_cai_min,taffeta_satin,textile_size_number,new_wire_number,scrap_sonic,remark_1,remark_2,remark_3,updated_by,updated_date,special_remark,process");
        masterfileGrid.setInitWidths("70,90,150,90,90,160,100,110,100,100,100,120,120,120,120,120,120,120,120,120,120,120,120,120,120,120,120,120,120,120,120,110,110");
        masterfileGrid.setColAlign("center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center,center");
        masterfileGrid.setColTypes("ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed,ed");
        masterfileGrid.setColSorting("str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str");
        masterfileGrid.enableSmartRendering(true);

        masterfileGrid.setColumnColor(",#d5f1ff,#d5f1ff,#d5f1ff");
        masterfileGrid.setStyle("font-weight:bold; font-size:12px;text-align:center;color:#990000;", "font-size:11px;", "", "font-weight:bold;color:#0000ff;font-size:13px;");

        //L??u ??: filter v?????t qu?? 26 b??? l???i
        masterfileGrid.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
        masterfileGrid.enableMultiselect(true);
        masterfileGrid.init(); //dataProcessor

        loadMasterFileGrid();

    }

    // master data: update main master form
    function initMasterFileForm() {
        var formStruct = [{
                type: "settings",
                position: "label-left",
                labelWidth: 150,
                inputWidth: 200
            },
            {
                type: "fieldset",
                label: "Main Master File",
                width: 660,
                blockOffset: 10,
                offsetLeft: 30,
                offsetTop: 30,
                list: [{
                        type: "settings",
                        position: "label-left",
                        labelWidth: 140,
                        inputWidth: 150,
                        labelAlign: "left"
                    },
                    {
                        type: "select",
                        id: "machine_type",
                        name: "machine_type",
                        label: "Machine",
                        style: "color:blue; ",
                        required: true,
                        validate: "NotEmpty",
                        options: [{
                                value: "",
                                text: "Ch???n M??y"
                            },
                            {
                                value: "wv",
                                text: "WV"
                            },
                            {
                                value: "cw",
                                text: "CW"
                            },
                            {
                                value: "lb",
                                text: "LB"
                            }
                        ]
                    },
                    {
                        type: "input",
                        id: "internal_item",
                        name: "internal_item",
                        label: "Item:",
                        icon: "icon-input",
                        className: "",
                        required: true,
                        validate: "NotEmpty"
                    },
                    {
                        type: "input",
                        id: "length_btp",
                        name: "length_btp",
                        label: "Length BTP:",
                        icon: "icon-input",
                        className: "",
                        required: true,
                        validate: "ValidNumeric"
                    },
                    {
                        type: "input",
                        id: "width_btp",
                        name: "width_btp",
                        label: "Width BTP:",
                        icon: "icon-input",
                        className: "",
                        required: true,
                        validate: "ValidNumeric"
                    },
                    {
                        type: "input",
                        id: "rbo",
                        name: "rbo",
                        label: "RBO:",
                        icon: "icon-input",
                        className: ""
                    },
                    {
                        type: "input",
                        id: "wire_number",
                        name: "wire_number",
                        label: "S??? D??y:",
                        icon: "icon-input",
                        className: ""
                    },
                    {
                        type: "input",
                        id: "vertical_thread_type",
                        name: "vertical_thread_type",
                        label: "Lo???i Ch??? D???c:",
                        icon: "icon-input",
                        className: ""
                    },
                    {
                        type: "input",
                        id: "folding_cut_type",
                        name: "folding_cut_type",
                        label: "Lo???i C???t G???p:",
                        icon: "icon-input",
                        className: ""
                    },
                    {
                        type: "input",
                        id: "pattern",
                        name: "pattern",
                        label: "Pattern:",
                        icon: "icon-input",
                        className: ""
                    },
                    {
                        type: "input",
                        id: "gear_density",
                        name: "gear_density",
                        label: "B??nh R??ng:",
                        icon: "icon-input",
                        className: ""
                    },
                    {
                        type: "input",
                        id: "length_tp",
                        name: "length_tp",
                        label: "Length TP:",
                        icon: "icon-input",
                        className: "",
                        required: true,
                        validate: "ValidNumeric"
                    },
                    {
                        type: "input",
                        id: "width_tp",
                        name: "width_tp",
                        label: "Width TP:",
                        icon: "icon-input",
                        className: "",
                        required: true,
                        validate: "ValidNumeric"
                    },
                    {
                        type: "select",
                        id: "cbs",
                        name: "cbs",
                        label: "Size:",
                        style: "color:blue; ",
                        options: [{
                                value: 0,
                                text: "Kh??ng C?? Size",
                                selected: true
                            },
                            {
                                value: 1,
                                text: "C?? Size"
                            }
                        ]
                    },
                    {
                        type: "input",
                        id: "scrap",
                        name: "scrap",
                        label: "Scrap:",
                        icon: "icon-input",
                        className: "",
                        validate: "ValidNumeric"
                    },
                    {
                        type: "input",
                        id: "cut_type",
                        name: "cut_type",
                        label: "Lo???i C???t:",
                        icon: "icon-input",
                        className: ""
                    },
                    {
                        type: "newcolumn",
                        "offset": 20
                    },

                    {
                        type: "input",
                        id: "sawing_method",
                        name: "sawing_method",
                        label: "PP X???:",
                        labelAlign: "left",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "cw_specification",
                        name: "cw_specification",
                        label: "TSKT CW:",
                        icon: "icon-input",
                        className: "",
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "heat_weaving",
                        name: "heat_weaving",
                        label: "Nhi???t D???t:",
                        icon: "icon-input",
                        className: ""
                    },
                    {
                        type: "input",
                        id: "meter_number_per_machine",
                        name: "meter_number_per_machine",
                        label: "M??t/M??y:",
                        icon: "icon-input",
                        className: "",
                        validate: "ValidNumeric"
                    },
                    {
                        type: "input",
                        id: "water_glue_rate",
                        name: "water_glue_rate",
                        label: "T??? L??? H???/N?????c:",
                        icon: "icon-input",
                        className: ""
                    },
                    {
                        type: "input",
                        id: "so_cai_min",
                        name: "so_cai_min",
                        label: "S??? C??i Min:",
                        icon: "icon-input",
                        className: "",
                        required: false,
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "taffeta_satin",
                        name: "taffeta_satin",
                        label: "Taffeta/Satin:",
                        icon: "icon-input",
                        className: ""
                    },
                    // { type: "input", id: "textile_size_number", name: "textile_size_number", label: "S??? Kh???:", labelAlign: "left", icon: "icon-input", required: false, validate: "ValidInteger" },
                    {
                        type: "select",
                        id: "textile_size_number",
                        name: "textile_size_number",
                        label: "S??? Kh???:",
                        style: "color:blue; ",
                        required: true,
                        options: [{
                                value: 3,
                                text: "3",
                                selected: true
                            },
                            {
                                value: 5,
                                text: "5"
                            },
                            {
                                value: 6,
                                text: "6"
                            },
                            {
                                value: 10,
                                text: "10"
                            }
                        ]
                    },
                    // { type: "input", id: "new_wire_number", name: "new_wire_number", label: "S??? D??y M???i:", icon: "icon-input", className: "", required: false, validate: "ValidInteger" },
                    {
                        type: "select",
                        id: "new_wire_number",
                        name: "new_wire_number",
                        label: "S??? D??y M???i (prepress):",
                        labelWidth: "140",
                        style: "color:blue; ",
                        required: true,
                        options: [{
                                value: 1000,
                                text: "Kh??ng Batcing (1000)",
                                selected: true
                            },
                            {
                                value: 1001,
                                text: "FOD - Ch??a Batching (1001)"
                            },
                            <?php
                            $length_check = 60;
                            for ($i = 1; $i <= $length_check; $i++) {
                                if ($i == $length_check) {
                                    echo '{ value: ' . $i . ', text: "' . $i . '" }';
                                } else {
                                    echo '{ value: ' . $i . ', text: "' . $i . '" },';
                                }
                            }
                            ?>
                        ]
                    },
                    {
                        type: "input",
                        id: "scrap_sonic",
                        name: "scrap_sonic",
                        label: "Scrap Sonic:",
                        icon: "icon-input",
                        className: "",
                        required: false,
                        validate: "ValidInteger"
                    },
                    // { type: "input", id: "pick_number_total", name: "pick_number_total", label: "T???ng S??? Pick:", icon: "icon-input", className: "", required: true, validate: "ValidInteger" },
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
                    },
                    {
                        type: "input",
                        id: "special_item_remark",
                        name: "special_item_remark",
                        label: "Special Remark:",
                        icon: "icon-input",
                        className: ""
                    },

                    {
                        type: "input",
                        id: "process",
                        name: "process",
                        label: "Process:",
                        icon: "icon-input",
                        className: ""
                    },
                    {
                        type: "button",
                        id: "loadProcess",
                        name: "loadProcess",
                        value: "<span style='color:red;'>Load Process</span>",
                        position: "label-center",
                        width: 150,
                        offsetLeft: 140
                    }

                ]
            },
            {
                type: "fieldset",
                label: "Ch???n ch???c n??ng",
                width: 660,
                blockOffset: 10,
                offsetLeft: 30,
                offsetTop: 5,
                list: [{
                        type: "button",
                        id: "updateMasterfile",
                        name: "updateMasterfile",
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
                        id: "deleteMasterfile",
                        name: "deleteMasterfile",
                        value: "<span style='color:#cc0000;font-weight:bold;'>Delete</span>",
                        position: "label-center",
                        width: 150,
                        offsetLeft: 50
                    }
                ]
            }
        ];

        // attach form
        masterfileForm = masterfileLayout.cells("b").attachForm(formStruct);

        // Validation live
        masterfileForm.enableLiveValidation(true);

        masterfileForm.attachEvent("onButtonClick", function(name) {
            if (name == 'updateMasterfile') {

                masterfileForm.send("<?php echo base_url('woven/updateMainMaster/'); ?>", "post", function(test, res) {
                    // parse json to object
                    var obj = JSON.parse(res);

                    if (obj.status == true) {
                        alert(obj.message);
                        location.href = '<?php echo base_url("woven/viewMasterFile/") ?>';
                    } else {
                        alert(obj.message);
                    }
                });

            } else if (name == 'deleteMasterfile') {
                var machine_type = masterfileForm.getItemValue('machine_type');
                var internal_item = masterfileForm.getItemValue('internal_item');
                var length_btp = masterfileForm.getItemValue('length_btp');
                var del_type = 'mainMaster';
                deleteMasterData(del_type, machine_type, internal_item, length_btp);
            } else if (name == 'loadProcess') {
                let internal_item = masterfileForm.getItemValue('internal_item');
                let code = masterfileForm.getItemValue('process');
                loadProcessDetail(internal_item, code);
            }

        });

    }

    // master data: update supply grid
    function initMasterFileSupplyGrid() {
        masterfileLayout.cells("a").progressOn();
        masterfileGrid = masterfileLayout.cells("a").attachGrid();
        masterfileGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
        masterfileGrid.setHeader("NO#, Item, Lenght BTP, V???t T??, Lo???i V???t T??, M???t ?????, S??? Pick, Th??? th???"); //sets the headers of columns
        masterfileGrid.setColumnIds("no,internal_item,length_btp,code_name,code_type,density,pick_number,order"); //sets the columns' ids
        masterfileGrid.setInitWidths("80,*,150,200,120,120,120,90"); //sets the initial widths of columns
        masterfileGrid.setColAlign("center,center,center,center,center,center,center,center"); //sets the alignment of columns
        masterfileGrid.setColTypes("ed,ed,ed,ed,ed,ed,ed,ed"); //sets the types of columns
        masterfileGrid.setColSorting("str,str,str,str,str,str,str,str"); //sets the sorting types of columns
        masterfileGrid.enableSmartRendering(true);

        masterfileGrid.setColumnColor(",#d5f1ff,#d5f1ff,#d5f1ff");
        masterfileGrid.setStyle("font-weight:bold; font-size:12px;text-align:center;color:#990000;", "font-size:11px;", "", "font-weight:bold;color:#0000ff;font-size:13px;");

        //L??u ??: filter v?????t qu?? 26 b??? l???i
        masterfileGrid.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
        masterfileGrid.enableMultiselect(true);
        masterfileGrid.init(); //dataProcessor

        loadMasterFileSupplyGrid();

    }

    // master data: update supply form
    function initMasterFileSupplyForm() {
        var formStruct = [{
                type: "settings",
                position: "label-left",
                labelWidth: 150,
                inputWidth: 200
            },
            {
                type: "fieldset",
                label: "Supply",
                width: 300,
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
                    // { type: "select", id: "machine_type", name: "machine_type", label: "Machine", style: "color:red; ", required: true, validate: "NotEmpty", options: [
                    //     { value: "", text: "Ch???n M??y" },
                    //     { value: "wv", text: "WV" },
                    //     { value: "cw", text: "CW" },
                    //     { value: "lb", text: "LB" }
                    // ]},
                    {
                        type: "input",
                        id: "internal_item",
                        name: "internal_item",
                        label: "Item:",
                        icon: "icon-input",
                        className: "",
                        required: true,
                        validate: "NotEmpty"
                    },
                    {
                        type: "input",
                        id: "length_btp",
                        name: "length_btp",
                        label: "Length BTP:",
                        icon: "icon-input",
                        className: "",
                        required: true,
                        validate: "NotEmpty"
                    },
                    {
                        type: "input",
                        id: "code_name",
                        name: "code_name",
                        label: "V???t t??:",
                        icon: "icon-input",
                        className: "",
                        required: true,
                        validate: "NotEmpty"
                    },
                    {
                        type: "input",
                        id: "code_type",
                        name: "code_type",
                        label: "Lo???i V???t t??:",
                        icon: "icon-input",
                        className: "",
                        required: true,
                        validate: "NotEmpty"
                    },
                    {
                        type: "input",
                        id: "density",
                        name: "density",
                        label: "M???t ?????:",
                        icon: "icon-input",
                        className: "",
                        required: true,
                        validate: "NotEmpty"
                    },
                    {
                        type: "input",
                        id: "pick_number",
                        name: "pick_number",
                        label: "S??? Pick:",
                        icon: "icon-input",
                        required: true,
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "order",
                        name: "order",
                        label: "Th??? t???:",
                        icon: "icon-input",
                        className: "",
                        required: true,
                        validate: "ValidInteger"
                    }

                ]
            },
            {
                type: "fieldset",
                label: "Ch???n ch???c n??ng",
                width: 300,
                blockOffset: 10,
                offsetLeft: 30,
                offsetTop: 5,
                list: [{
                        type: "button",
                        id: "updateMasterfile",
                        name: "updateMasterfile",
                        value: "<span style='color:red;font-weight:bold;'>Update</span>",
                        position: "label-center",
                        width: 100,
                        offsetLeft: 10
                    },
                    {
                        type: "newcolumn",
                        "offset": 10
                    },
                    {
                        type: "button",
                        id: "deleteMasterfile",
                        name: "deleteMasterfile",
                        value: "<span style='color:#cc0000;font-weight:bold;'>Delete</span>",
                        position: "label-center",
                        width: 100,
                        offsetLeft: 10
                    }
                ]
            }
        ];

        masterfileLayout.cells("b").setWidth(500);
        masterfileForm = masterfileLayout.cells("b").attachForm(formStruct);

        // handle save update
        masterfileForm.attachEvent("onButtonClick", function(name) {
            if (name == 'updateMasterfile') {

                masterfileForm.send("<?php echo base_url('woven/updateMainMasterSupply/'); ?>", "post", function(test, res) {
                    // parse json to object
                    var obj = JSON.parse(res);

                    if (obj.status == true) {
                        alert(obj.messagge);
                        location.href = '<?php echo base_url("woven/viewMasterFile/") ?>';
                    } else {
                        alert(obj.messagge);
                    }
                });

            } else if (name == 'deleteMasterfile') {
                var internal_item = masterfileForm.getItemValue('internal_item');
                var length_btp = masterfileForm.getItemValue('length_btp');
                var code_name = masterfileForm.getItemValue('code_name');
                var order = masterfileForm.getItemValue('order');
                var del_type = 'supply';
                deleteMasterData(del_type, '', internal_item, length_btp, code_name, order);
            }

        });

    }


    // master data: form insert new master data
    function formStructMaster() {
        formStructMaster = [{
                type: "settings",
                position: "label-left",
                width: 900,
                labelWidth: "auto",
                inputWidth: "auto",
                offsetLeft: 30
            },
            {
                type: "fieldset",
                width: "auto",
                blockOffset: 0,
                label: "Detail",
                offsetLeft: 20,
                offsetTop: 20,
                list: [{
                        type: "settings",
                        position: "label-left",
                        labelWidth: 140,
                        inputWidth: 140,
                        labelAlign: "left"
                    },
                    {
                        type: "select",
                        id: "machine_type",
                        name: "machine_type",
                        label: "Machine",
                        style: "color:blue; ",
                        options: [{
                                value: "wv",
                                text: "WV",
                                selected: true
                            },
                            {
                                value: "cw",
                                text: "CW"
                            },
                            {
                                value: "lb",
                                text: "LB"
                            }
                        ]
                    },
                    {
                        type: "input",
                        id: "internal_item",
                        name: "internal_item",
                        label: "Item:",
                        icon: "icon-input",
                        required: true,
                        validate: "NotEmpty"
                    },
                    {
                        type: "input",
                        id: "length_btp",
                        name: "length_btp",
                        label: "Length BTP (mm):",
                        icon: "icon-input",
                        required: true,
                        validate: "ValidNumeric"
                    },
                    {
                        type: "input",
                        id: "width_btp",
                        name: "width_btp",
                        label: "Width BTP (mm):",
                        icon: "icon-input",
                        required: true,
                        validate: "ValidNumeric"
                    },
                    {
                        type: "input",
                        id: "rbo",
                        name: "rbo",
                        label: "RBO:",
                        icon: "icon-input",
                        validate: ""
                    },
                    {
                        type: "input",
                        id: "wire_number",
                        name: "wire_number",
                        label: "S??? D??y:",
                        icon: "icon-input",
                        required: true,
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "vertical_thread_type",
                        name: "vertical_thread_type",
                        label: "Lo???i Ch??? D???c:",
                        icon: "icon-input",
                        required: false,
                        validate: ""
                    },
                    {
                        type: "input",
                        id: "folding_cut_type",
                        name: "folding_cut_type",
                        label: "Lo???i C???t G???p:",
                        icon: "icon-input",
                        required: false,
                        validate: ""
                    },
                    {
                        type: "input",
                        id: "pattern",
                        name: "pattern",
                        label: "Pattern:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "gear_density",
                        name: "gear_density",
                        label: "B??nh R??ng:",
                        icon: "icon-input",
                        required: false,
                        validate: ""
                    },
                    {
                        type: "input",
                        id: "length_tp",
                        name: "length_tp",
                        label: "Length TP (mm):",
                        icon: "icon-input",
                        required: true,
                        validate: "ValidNumeric"
                    },
                    {
                        type: "input",
                        id: "width_tp",
                        name: "width_tp",
                        label: "Width TP (mm):",
                        icon: "icon-input",
                        required: true,
                        validate: "ValidNumeric"
                    },
                    {
                        type: "select",
                        id: "cbs",
                        name: "cbs",
                        label: "Size:",
                        style: "color:blue; ",
                        options: [{
                                value: 0,
                                text: "Kh??ng C?? Size",
                                selected: true
                            },
                            {
                                value: 1,
                                text: "C?? Size"
                            }
                        ]
                    },
                    {
                        type: "input",
                        id: "scrap",
                        name: "scrap",
                        label: "% Scrap:",
                        icon: "icon-input",
                        required: true,
                        validate: "ValidInteger",
                        value: 15
                    },

                    {
                        type: "newcolumn",
                        "offset": 20
                    },
                    {
                        type: "input",
                        id: "cut_type",
                        name: "cut_type",
                        label: "Lo???i C???t:",
                        icon: "icon-input",
                        className: ""
                    },
                    {
                        type: "input",
                        id: "sawing_method",
                        name: "sawing_method",
                        label: "PP X???:",
                        labelAlign: "left",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "cw_specification",
                        name: "cw_specification",
                        label: "TSKT CW:",
                        icon: "icon-input",
                        className: "",
                        required: false,
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "heat_weaving",
                        name: "heat_weaving",
                        label: "Nhi???t D???t:",
                        icon: "icon-input",
                        className: ""
                    },
                    {
                        type: "input",
                        id: "meter_number_per_machine",
                        name: "meter_number_per_machine",
                        label: "M??t/M??y:",
                        icon: "icon-input",
                        className: "",
                        required: false,
                        validate: "ValidNumeric"
                    },
                    {
                        type: "input",
                        id: "water_glue_rate",
                        name: "water_glue_rate",
                        label: "T??? L??? H???/N?????c:",
                        icon: "icon-input",
                        className: ""
                    },
                    // { type: "input", id: "so_cai_min", name: "so_cai_min", label: "S??? C??i Min (prepress):", icon: "icon-input", className: "", required: false, validate: "ValidInteger" },
                    // { type: "input", id: "taffeta_satin", name: "taffeta_satin", label: "Taffeta/Satin:", icon: "icon-input", className: "" },
                    {
                        type: "input",
                        id: "textile_size_number",
                        name: "textile_size_number",
                        label: "S??? Kh??? (prepress):",
                        labelAlign: "left",
                        icon: "icon-input",
                        required: true,
                        validate: "ValidInteger"
                    },
                    // { type: "input", id: "new_wire_number", name: "new_wire_number", label: "S??? D??y M???i:", icon: "icon-input", className: "", required: false, validate: "ValidInteger" },
                    {
                        type: "select",
                        id: "new_wire_number",
                        name: "new_wire_number",
                        label: "S??? D??y M???i (prepress):",
                        style: "color:blue; ",
                        required: true,
                        options: [{
                                value: 1000,
                                text: "Kh??ng Batcing (1000)",
                                selected: true
                            },
                            {
                                value: 1001,
                                text: "FOD - Ch??a Batching (1001)"
                            },
                            <?php
                            $length_check = 60;
                            for ($i = 1; $i <= $length_check; $i++) {
                                if ($i == $length_check) {
                                    echo '{ value: ' . $i . ', text: "' . $i . '" }';
                                } else {
                                    echo '{ value: ' . $i . ', text: "' . $i . '" },';
                                }
                            }
                            ?>
                        ]
                    },
                    // { type: "input", id: "pick_number_total", name: "pick_number_total", label: "T???ng S??? Pick:", icon: "icon-input", className: "", required: true, validate: "ValidInteger" },
                    {
                        type: "input",
                        id: "glue_1",
                        name: "glue_1",
                        label: "Keo 1:",
                        icon: "icon-input",
                        className: ""
                    },
                    {
                        type: "input",
                        id: "glue_2",
                        name: "glue_2",
                        label: "Keo 2:",
                        icon: "icon-input",
                        className: ""
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
                    },

                    {
                        type: "newcolumn",
                        "offset": 0
                    },

                    {
                        type: "fieldset",
                        width: "auto",
                        blockOffset: 0,
                        label: "Process (b???t bu???c)",
                        style: "color:red; ",
                        offsetLeft: 20,
                        offsetTop: 0,
                        list: [{
                                type: "settings",
                                position: "label-left",
                                labelWidth: 100,
                                inputWidth: 50,
                                labelAlign: "left"
                            },

                            {
                                type: "checkbox",
                                id: "wv_01",
                                name: "wv_01",
                                label: "D???t",
                                checked: true
                            },
                            {
                                type: "checkbox",
                                id: "wv_02",
                                name: "wv_02",
                                label: "X??? Sonic"
                            },
                            {
                                type: "checkbox",
                                id: "wv_03",
                                name: "wv_03",
                                label: "Qua H???"
                            },
                            {
                                type: "checkbox",
                                id: "wv_04",
                                name: "wv_04",
                                label: "Qua N?????c"
                            },
                            {
                                type: "checkbox",
                                id: "wv_05",
                                name: "wv_05",
                                label: "N???i ?????u",
                                checked: true
                            },
                            {
                                type: "checkbox",
                                id: "wv_06",
                                name: "wv_06",
                                label: "D??n Keo"
                            },
                            {
                                type: "checkbox",
                                id: "wv_07",
                                name: "wv_07",
                                label: "C???t G???p"
                            },
                            {
                                type: "checkbox",
                                id: "wv_08",
                                name: "wv_08",
                                label: "C???t Laser"
                            },
                            {
                                type: "checkbox",
                                id: "wv_09",
                                name: "wv_09",
                                label: "????ng G??i",
                                checked: true
                            },
                        ]
                    }

                ]
            },

            {
                type: "fieldset",
                width: "auto",
                blockOffset: 0,
                label: "Material",
                offsetLeft: 20,
                offsetTop: 0,
                list: [{
                        type: "settings",
                        position: "label-left",
                        labelWidth: 100,
                        inputWidth: 120,
                        labelAlign: "left"
                    },
                    {
                        type: "input",
                        id: "supply_code_1",
                        name: "supply_code_1",
                        label: "V???t T?? 1:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "supply_code_2",
                        name: "supply_code_2",
                        label: "V???t T?? 2:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "supply_code_3",
                        name: "supply_code_3",
                        label: "V???t T?? 3:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "supply_code_4",
                        name: "supply_code_4",
                        label: "V???t T?? 4:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "supply_code_5",
                        name: "supply_code_5",
                        label: "V???t T?? 5:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "supply_code_6",
                        name: "supply_code_6",
                        label: "V???t T?? 6:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "supply_code_7",
                        name: "supply_code_7",
                        label: "V???t T?? 7:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "supply_code_8",
                        name: "supply_code_8",
                        label: "V???t T?? 8:",
                        icon: "icon-input"
                    },

                    {
                        type: "newcolumn",
                        "offset": 20
                    },

                    {
                        type: "input",
                        id: "density_1",
                        name: "density_1",
                        label: "M???t ????? 1:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "density_2",
                        name: "density_2",
                        label: "M???t ????? 2:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "density_3",
                        name: "density_3",
                        label: "M???t ????? 3:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "density_4",
                        name: "density_4",
                        label: "M???t ????? 4:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "density_5",
                        name: "density_5",
                        label: "M???t ????? 5:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "density_6",
                        name: "density_6",
                        label: "M???t ????? 6:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "density_7",
                        name: "density_7",
                        label: "M???t ????? 7:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "density_8",
                        name: "density_8",
                        label: "M???t ????? 8:",
                        icon: "icon-input"
                    },

                    {
                        type: "newcolumn",
                        "offset": 20
                    },

                    {
                        type: "input",
                        id: "pick_number_1",
                        name: "pick_number_1",
                        label: "S??? Pick 1:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "pick_number_2",
                        name: "pick_number_2",
                        label: "S??? Pick 2:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "pick_number_3",
                        name: "pick_number_3",
                        label: "S??? Pick 3:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "pick_number_4",
                        name: "pick_number_4",
                        label: "S??? Pick 4:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "pick_number_5",
                        name: "pick_number_5",
                        label: "S??? Pick 5:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "pick_number_6",
                        name: "pick_number_6",
                        label: "S??? Pick 6:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "pick_number_7",
                        name: "pick_number_7",
                        label: "S??? Pick 7:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "pick_number_8",
                        name: "pick_number_8",
                        label: "S??? Pick 8:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    }
                ]
            },

            {
                type: "button",
                id: "createMasterItem",
                name: "createMasterItem",
                value: "Update",
                position: "label-center",
                width: 210,
                offsetLeft: 390
            }
        ];

    }

    // master data: insert new item
    function initCreateMasterDataWindow() {
        formStructMaster();

        if (!dhxWins) {
            dhxWins = new dhtmlXWindows();
        }

        var id = "addMasterData";
        var w = 1000;
        var h = 820;
        // var x = Number(($(window).width())/5);
        // var y = Number( ($(window).height()) - 700 );
        var x = 500;
        var y = 5;
        var create = dhxWins.createWindow(id, x, y, w, h);
        dhxWins.window(id).setText("Add Master Data");

        createMasterDataForm = create.attachForm();
        createMasterDataForm.loadStruct(formStructMaster);

        // Validation live: 
        createMasterDataForm.enableLiveValidation(true);

        createMasterDataForm.attachEvent("onButtonClick", function(name) {
            if (name == 'createMasterItem') {
                createMasterDataForm.send("<?php echo base_url('woven/createMasterItem/'); ?>", "post", function(test, res) {
                    // parse json to object
                    var obj = JSON.parse(res);

                    if (obj.status == true) {
                        alert(obj.messagge);
                        location.href = '<?php echo base_url("woven/viewMasterFile/") ?>';
                    } else {
                        alert(obj.messagge);
                    }
                });

            }

        });

    }

    /*
        | ------------------------------------------------------------------------------------------------------------
        | 4.  OTHER
        | ------------------------------------------------------------------------------------------------------------
    */

    function setCookie(cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toGMTString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
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

    function detachedSOLine(so_line) {
        so_line = so_line.trim();
        so_line = so_line.replace(" ", "");

        // t??ch input th??nh Order number (SO) v?? line number (LINE)
        so_line_detached = so_line.split("-");

        // set order number and line number
        order_number = so_line_detached[0];
        line_number = so_line_detached[1];
    }

    /*
        | ------------------------------------------------------------------------------------------------------------
        | 5.  ORDER TYPE CLASSIFICATION
        | ------------------------------------------------------------------------------------------------------------
    */
    // ????N TH?????NG HO???C FOD. FOD l?? ????n m?? item ???? ???????c l??m l???nh sx l???n ?????u ti??n
    function commonOrder(batch_no) {
        // setCookie('plan_order_type', 'common', 365);

        var url_suffix = 'batch_no=' + batch_no;
        location.href = '<?php echo base_url('/woven/commonOrder/?'); ?>' + url_suffix;

    }

    /*
        | ????N B?? (CCR): ????n nh???p so_line v?? item, L??c n??y so_line ???? l??m r???i nh??ng v???n l??m l???nh b??nh th?????ng,
        | S??? LSX c?? ?????u l???nh NO, ??u??i l?? CCR, D??? li???u ch??? c???n l???y ra t??? master data, kh??ng l???y t??? automail
        | ????N BUILD STOCK. Ch??? nh???p Item. L???y d??? li???u t??? master data. S??? LSX c?? ?????u l???nh l??: NO
    */

    function loadData(so_line, item) {
        if (item) {
            if (so_line) {
                setCookie('plan_order_type', 'ccr', 365);
            } else {
                setCookie('plan_order_type', 'buildstock', 365);
            }
        }

        var url_suffix = 'so_line=' + so_line + '&item=' + item;
        location.href = '<?php echo base_url('/woven/loadData/?'); ?>' + url_suffix;

    }

    /*
        | ------------------------------------------------------------------------------------------------------------
        | 6.  HANDLE
        | ------------------------------------------------------------------------------------------------------------
    */
    function onClickMainToolbar() {
        mainToolbar.attachEvent("onClick", function(name) {
            if (name == "save") {
                // check empty data
                var order_type = orderForm.getItemValue('orderedType');
                var ordered_date = orderForm.getItemValue('orderedDate');
                var request_date = orderForm.getItemValue('requestDate');
                var promise_date = orderForm.getItemValue('promiseDate');

                if (!order_type) {
                    alert('Order Type Not Empty');
                    return false;
                }
                if (!ordered_date) {
                    alert('Order Date Not Empty');
                    return false;
                }
                if (!request_date) {
                    alert('Request Date Not Empty');
                    return false;
                }
                // if (!promise_date ) {
                //     alert('Promise Date Not Empty'); return false;
                // }
                saveOrders();
            }
        });
    }

    // load data to grid
    function loadMainViewGrid(from_date, to_date) {
        var suffix_url_views = '?from_date=' + from_date + '&to_date=' + to_date;
        var url = "<?php echo base_url('woven/recent/'); ?>" + suffix_url_views;
        // var url = "<?php echo base_url('woven/recent'); ?>";

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
                alert('L??u d??? li???u kh??ng th??nh c??ng: Vui l??ng li??n h??? qu???n tr??? h??? th???ng. ' + xhr.responseText);
                return false;
            }
        });

    }

    // load from vnso or vnso_closed
    function loadOrderGrid(dataLoad) {
        orderGrid.attachEvent("onCheck", function(rowId, cInd, state) { // fires after the state of a checkbox has been changed 
            checkboxOrders(rowId, state);
        });

        var automailData = {
            rows: []
        };

        if (!dataLoad.automailData.length) { // Tr?????ng h???p buildstock
            // var tmp_array = [1, '','','','','', '','','','','', '','',''];
            orderGrid.addRow(1, [1, '', '', '', '', '', '', '', '', '', '', '', '', '']);
        } else {
            for (var i = 0; i < dataLoad.automailData.length; i++) {
                automailData.rows.push(dataLoad.automailData[i]);
            }

            // load automail data to grid
            orderGrid.parse(automailData, "json");
        }

    }

    // Check box order
    function checkboxOrders(rowId, state) {
        if (state) {
            if (checkboxOrdersArr.indexOf(rowId) == -1) checkboxOrdersArr.push(rowId); // push array so
        } else {
            if (checkboxOrdersArr.indexOf(rowId) != -1) checkboxOrdersArr.splice(checkboxOrdersArr.indexOf(rowId), 1); // delete element
        }

        if (checkboxOrdersArr.length > 0) {
            loadMasterDataGrid(results);

        } else {
            masterDataGrid.clearAll();
            supplyGrid.clearAll();
            glueGrid.clearAll();
            orderForm.clear();
            sizeGrid.clearAll();
            hideColumnSize();
        }

    }

    // load data to grid
    function loadMasterDataGrid(dataLoad) {
        var masterData = {
            rows: []
        };
        masterDataGrid.clearAll();

        // get machine_type
        var machine_type = dataLoad.batchingData.machine_type;

        for (var i = 0; i < dataLoad.masterData.length; i++) {
            masterData.rows.push(dataLoad.masterData[i]);
        }
        // load data to grid
        masterDataGrid.parse(masterData, "json");

        // true will cal function onRow select
        if (masterDataGrid.getRowsNum()) {

            // choose master data row. apply batching and non batching orders. 20210225
            // masterDataGrid.selectRow(0, true);
            if (!machine_type) {
                masterDataGrid.selectRowById(0, true, true, true);
            } else {
                masterDataGrid.forEachRow(function(id) {
                    var machine_type_check = masterDataGrid.cells(id, 0).getValue().toLowerCase();

                    if (machine_type_check == machine_type) {
                        // console.log("Batching or Non Batching Machine Type | Master Data Machine Type: " + machine_type + " | " + machine_type_check);
                        // masterDataGrid.selectRow(id, true);
                        masterDataGrid.selectRowById(id, true, true, true);

                    }
                });
            }

            // Fire When user click on row in grid
            var selectedId = masterDataGrid.getSelectedRowId();
            if (selectedId == null) masterDataGrid.selectRow(0, true, true, true);

            masterDataGrid.attachEvent("onRowSelect", function(id, ind) {

                let internal_item = masterDataGrid.cells(id, 2).getValue();
                initOrderForm(internal_item);

                // load supply and glue
                supplyGrid.clearAll();
                glueGrid.clearAll();
                sizeGrid.clearAll();

                loadsizeGrid(dataLoad);
                loadSupplyGrid(dataLoad);
                loadGlueGrid(dataLoad.supplyData);
                loadOrderForm(dataLoad);
                loadProcess(dataLoad.processData);

            });

        }


    }

    // load data to grid
    function loadSupplyGrid(dataLoad) {

        var supplyDataArr = {};

        if (masterDataGrid.getSelectedRowId() && masterDataGrid.getRowsNum()) {
            // clear supply grid
            supplyGrid.clearAll();
            // get machine, length_btp, item, folding_cut_type (lo???i c???t g???p)
            getIdentificationItem();

            for (var i = 0; i < dataLoad.supplyData.length; i++) {
                var supplyAll = dataLoad.supplyData[i];

                if (supplyAll['internal_item'] == item_check) {
                    supplyDataArr = {
                        rows: supplyAll.supply
                    };
                }
            }

            supplyGrid.parse(supplyDataArr, "json");

        } else {
            supplyGrid.clearAll();
        }

    }

    // load data to grid
    function loadGlueGrid(dataLoad) {
        var glueData = {};

        if (masterDataGrid.getSelectedRowId() && masterDataGrid.getRowsNum()) {
            // clear grid
            glueGrid.clearAll();

            for (var i = 0; i < dataLoad.length; i++) {
                var glueAll = dataLoad[i];
                if (glueAll['internal_item'] == item_check) {
                    glueData = {
                        rows: glueAll.glue
                    };
                }

            }

            glueGrid.parse(glueData, "json"); // load to grid

        } else {
            glueGrid.clearAll();
        }

    }

    function isNumber(num) {
        return (typeof num == 'string' || typeof num == 'number') && !isNaN(num - 0) && num !== '';
    };

    // load data to grid
    function loadsizeGrid(dataLoad) {
        var sizeData = {
            rows: []
        };

        sizeGrid.clearAll();

        if (masterDataGrid.getSelectedRowId() && masterDataGrid.getRowsNum()) {

            sizeGrid.setColLabel(3, 'QTY');

            count_size = dataLoad.sizeData.length;
            var qty_size_total = 0;
            if (!count_size) {
                if (getCookie('plan_order_type') == 'ccr') {
                    var sizeUser = prompt('Nh???p s??? Size ????? t???o ????n h??ng');

                    if (isNumber(sizeUser) == false) { // kh??ng ph???i s???
                        alert('S??? l?????ng Size l?? ki???u S???. Vui l??ng nh???p l???i. ');
                        loacation.reload();
                        return false;

                    } else { // l?? s???

                        if (sizeUser == 0) {
                            alert('Nh???p sai s??? l?????ng Size. Vui l??ng nh???p l???i. ');
                            loacation.reload();
                            return false;

                        } else {
                            count_size = sizeUser;
                        }
                    }

                    for (var i = 1; i <= count_size; i++) {
                        sizeGrid.addRow(i, [1, '', '', '']);
                    }
                } else {
                    count_size = 1;
                    sizeGrid.addRow(1, [1, '', '', '']);
                }

            } else {

                for (var i = 0; i < count_size; i++) {
                    sizeData.rows.push(dataLoad.sizeData[i]);
                }

                sizeGrid.parse(sizeData, "json"); // load to grid
            }


            var sizeCheckBefore = [];

            // Tr?????ng h???p ???? load d??? li???u v?? check
            sizeGrid.forEachRow(function(id) {
                var checked = sizeGrid.cells(id, 0).getValue();
                var idCheck = id - 1;
                if (checked == true) {
                    sizeCheckBefore.push(idCheck);
                }
            });

            // Tr?????ng h???p 1: D??? li???u ???? check khi load trang. 
            // Tr?????ng h???p 2: D??? li???u ???????c check b???i ng?????i d??ng
            if (sizeCheckBefore.length > 0) {

                sizeCheckArr = sizeCheckBefore;
                handleSize(sizeCheckArr);

            } else {

                sizeGrid.attachEvent("onCheck", function(rId, cInd, state) {

                    sizeCheckArr = checkSize(rId, state);
                    handleSize(sizeCheckArr);

                });

            }

        } else {
            hideColumnSize();
        }



    }

    // x??? l?? size v?? thao t??c ng?????i d??ng
    function handleSize(sizeCheckedArr) {

        // X??? l?? t????ng t??c c???a ng?????i d??ng
        var checkSize = 0;
        var rowSizeId = sizeCheckedArr[checkSize];

        var colSizeId = 1;
        var setEdit = 'size';

        var length = sizeCheckedArr.length;

        // Ki???m tra d??? li???u size, 
        if (!sizeGrid.cellByIndex(rowSizeId, colSizeId).getValue()) {

            // Tr?????ng h???p ch??a c?? size 
            sizeGrid.selectRow(rowSizeId, false);
            sizeGrid.selectCell(rowSizeId, colSizeId, false, true);
            sizeGrid.editCell();

            // b???t s??? ki???n Enter
            sizeGrid.attachEvent("onEnter", function(id, ind) {

                // N???u s???a xong t???ng ?? th?? t??ng d??ng l??n 1 , c???t gi??? nguy??n
                if (setEdit == 'size') {

                    // T??ng checkSize + 1, d??ng ???????c ch???n s??? = n???i dung ph???n t??? th??? checkSize trong arr size ???? ch???n
                    checkSize++;
                    rowSizeId = sizeCheckedArr[checkSize];

                    // Tr?????ng h???p ch??a b???ng chi???u d??i arr size ch???n
                    if (checkSize < length) {

                        sizeGrid.selectRow(rowSizeId, false);
                        sizeGrid.selectCell(rowSizeId, colSizeId, false, true);
                        sizeGrid.editCell();
                    }

                    // N???u d??ng = s??? size th?? chuy???n l??n d??ng ?????u ti??n, c???t t???i v??? tr?? s??? l?????ng size
                    if (checkSize == length) {
                        checkSize = 0;
                        rowSizeId = sizeCheckedArr[checkSize];
                        colSizeId = 3;
                        setEdit = 'qty';
                        sizeGrid.selectRow(rowSizeId, false);
                        sizeGrid.selectCell(rowSizeId, colSizeId, false, true);
                        sizeGrid.editCell();
                    }

                } else if (setEdit == 'qty') { // Tr?????ng h???p edit c??c ?? t???i v??? tr?? s??? l?????ng size

                    checkSize++;
                    rowSizeId = sizeCheckedArr[checkSize];



                    if (checkSize < length) {
                        sizeGrid.selectRow(rowSizeId, false);
                        sizeGrid.selectCell(rowSizeId, colSizeId, false, true);
                        sizeGrid.editCell();
                    } else {
                        // to update data
                        setEdit = 'enter';

                        updateDataLoad();

                    }

                }

                if (setEdit == 'enter') {
                    updateDataLoad();
                }


            });

        } else { // L???y ???????c size

            // Ki???m tra qty, n???u ch??a c?? th?? m??? d??ng ?????u ti??n, c???t qty
            if (!sizeGrid.cellByIndex(0, 3).getValue()) {

                checkSize = 0;
                rowSizeId = sizeCheckedArr[checkSize];
                colSizeId = 3;
                setEdit = 'qty';
                sizeGrid.selectRow(rowSizeId, false);
                sizeGrid.selectCell(rowSizeId, colSizeId, false, true);
                sizeGrid.editCell();

                sizeGrid.attachEvent("onEnter", function(id, ind) {

                    // N???u s???a xong t???ng ?? th?? t??ng d??ng l??n 1, c???t gi??? nguy??n
                    if (setEdit == 'qty') { // Tr?????ng h???p edit c??c ?? t???i v??? tr?? s??? l?????ng size

                        checkSize++;
                        rowSizeId = sizeCheckedArr[checkSize];



                        if (checkSize < length) {
                            sizeGrid.selectRow(rowSizeId, false);
                            sizeGrid.selectCell(rowSizeId, colSizeId, false, true);
                            sizeGrid.editCell();
                        } else {
                            // to update data
                            setEdit = 'enter';

                            updateDataLoad();
                        }

                    }

                    // S??? d???ng cho tr?????ng h???p user s???a l???i d??? li???u nh???p
                    if (setEdit == 'enter') {
                        updateDataLoad();
                    }

                });

            } else {
                // Tr?????ng h???p ???? c?? size, qty
                sizeGrid.selectRow(length - 1, false);
                sizeGrid.selectCell(length - 1, 3, false, true);
                sizeGrid.editCell();

                sizeGrid.attachEvent("onEnter", function(id, ind) {
                    updateDataLoad();
                });

            }
        }


    }

    // get c??c d??ng ???? ch???n c???a size
    function checkSize(rId, state) {

        var id = rId - 1;
        if (state) {
            if (sizeCheckArr.indexOf(id) == -1) sizeCheckArr.push(id); // push array so
        } else {
            if (sizeCheckArr.indexOf(id) != -1) sizeCheckArr.splice(sizeCheckArr.indexOf(id), 1); // delete element

        }

        sizeCheckArr.sort();

        return sizeCheckArr;
    }

    // H??m d??ng ????? c???p nh???t c??c th??ng s??? kh??c sau khi nh???p size xong
    function updateDataLoad() {

        var qty_total = 0;
        var so_cai_total = 0;

        count_size = sizeCheckArr.length;
        
        

        for (var id = 0; id < count_size; id++) {
        
            sizeGrid.cellByIndex(sizeCheckArr[id], 2).setValue(so_cai(sizeCheckArr[id]));
            so_cai_total += Number(sizeGrid.cellByIndex(sizeCheckArr[id], 2).getValue());
            qty_total += Number(sizeGrid.cellByIndex(sizeCheckArr[id], 3).getValue());
        }

        if (orderForm.getItemValue('socai_group_total')) {
            so_cai_total = orderForm.getItemValue('socai_group_total');
        }

        // Load ch??? ngang c???n trong supplyGrid
        need_horizontal_thread(so_cai_total);

        // Load s??? l?????ng c???n c???a code keo
        need_horizontal_thread_glue(so_cai_total);

        // ch??? d???c c???n
        orderForm.setItemValue('need_vertical_thread_number', need_vertical_thread_number(so_cai_total));
        orderForm.setItemValue('qty_total', qty_total);

    }

    function hideColumnSize() {
        if (sizeGrid.getColumnsNum() > 1) {
            for (var n = 3; n < sizeGrid.getColumnsNum(); n++) {
                sizeGrid.setColumnHidden(n, true);
            }
        }
    }

    function so_cai(rowSizeId) {
        var so_cai = 0;
        var qty_of_size = 0;
        var wire_number = 0;
        var scrap = 1;
        var sonic_number = 0; // scrap sonic

        // set qty of size data
        qty_of_size = Number(sizeGrid.cellByIndex(rowSizeId, 3).getValue());

        // set wire number and scrap
        var selectedMasterDataId = masterDataGrid.getSelectedRowId();
        wire_number = Number(masterDataGrid.cells(selectedMasterDataId, 4).getValue());
        scrap = Number(masterDataGrid.cells(selectedMasterDataId, 12).getValue());
        scrap = scrap / 100;

        // get setting process 
        
        var process_arr = [];
        let setting_process = <?php echo json_encode($setting_process); ?>;
        
        for (i = 1; i <= setting_process.length; i++) {
            let process_check = 'process_' + i;
            if (orderForm.getItemLabel(process_check)) {
                process_arr.push(orderForm.getItemLabel(process_check));
            }
        }

        if (pm_scrap_check == 0 ) {
            if (process_arr.length > 0 ) {
            
            setting_process.forEach ( (setting) => {
                var process_code = setting.process_code;

                process_arr.forEach( (element) => {
                    
                    if (element.indexOf(process_code) !== -1 ) {
                        pm_scrap += parseInt(setting.pm_scrap);
                        return;
                    }
                });
            });

            pm_scrap_check = 1;

            
        }
        }
        

        // // // set sonic
        // // var sonic_status = orderForm.getItemValue('xe_sonic');
        // // var sonic_bonus = 0;
        // // if (sonic_status) {
        // //     sonic_number = 15;
        // //     sonic_bonus = 5;
        // // } else {
        // //     sonic_number = 13;
        // // }

        // console.log('qty_of_size: ' + qty_of_size);
        // console.log('wire_number: ' + wire_number);
        // // console.log('sonic_number: ' + sonic_number);
        // console.log('scrap: ' + scrap);
        // console.log('pm_scrap: ' + pm_scrap);
        // C??: C??ng th???c tr?????c ????
        // so_cai = Math.ceil(((qty_of_size / wire_number) + sonic_number) * (1 + scrap));

        // C??ng th???c m???i (L???c y??u c???u 20211007)
        so_cai = Math.ceil(((qty_of_size / wire_number) + pm_scrap) / (1 - scrap));
        // so_cai += sonic_bonus;

        

        // Tr?????ng h???p ?????c bi???t
        if (so_cai < 15) {
            so_cai = 15;
        }

        // console.log('so_cai F: ' + so_cai);

        return so_cai;

    }

    // C??ng th???c t??nh ch??? ngang c???n c???a v???t t??
    function need_horizontal_thread(so_cai_total) {
        var pick_number = 0;
        var thread_length = 0;
        var meter_number_per_machine = 0;
        var need_horizontal_thread = 0;


        var selectedMasterDataId = masterDataGrid.getSelectedRowId();
        meter_number_per_machine = Number(masterDataGrid.cells(selectedMasterDataId, 16).getValue());
        supplyGrid.forEachRow(function(id) {
            pick_number = Number(supplyGrid.cells(id, 3).getValue());
            thread_length = Number(supplyGrid.cells(id, 4).getValue());

            need_horizontal_thread = ((so_cai_total * pick_number * meter_number_per_machine) / thread_length);

            // 20211012: X??? l?? theo y??u c???u c???a Tien.Ha (Tho.Pham nh???n cho Ti??n). N???u need_horizontal_thread < 0.1 th?? cho b???ng 0.1
            if (need_horizontal_thread < 0.1 ) need_horizontal_thread = 0.1;

            need_horizontal_thread = need_horizontal_thread.toFixed(2);

            supplyGrid.cells(id, 5).setValue(need_horizontal_thread);
        });

    }

    // C??ng th???c t??nh ch??? ngang c???n c???a keo, c??c code keo s??? l?????ng b???ng nhau
    function need_horizontal_thread_glue(so_cai_total) {
        var need_horizontal_thread = 0;

        var selectedMasterDataId = masterDataGrid.getSelectedRowId();
        var length_btp = Number(masterDataGrid.cells(selectedMasterDataId, 1).getValue()); // chi???u d??i btp
        var wire_number = Number(masterDataGrid.cells(selectedMasterDataId, 4).getValue()); // s??? d??y = ribbon

        if (so_cai_total && length_btp) {
            need_horizontal_thread = ((so_cai_total * wire_number * length_btp) / 1000)
            need_horizontal_thread = need_horizontal_thread.toFixed(2);
        }

        glueGrid.forEachRow(function(id) {
            glueGrid.cells(id, 2).setValue(need_horizontal_thread);
        });

    }

    // C??ng th???c t??nh ch??? d???c c???n (kg)
    function need_vertical_thread_number(so_cai_total) {

        /* 
            C??NG TH???C T??NH CH??? D???C C???N: ( t???ng s??? c??i * chi???u d??i btp * th??ng s??? taffeta/satin theo m??y ) / (9 000 000 / 2 k?? t??? ?????u c???a lo???i ch??? d???c )
        */

        // so_cai_total: ???? c??
        var taffeta_satin_number = 0; // th??ng s??? taffeta/satin
        var density_vertical_thread = 0; // 2 k?? t??? ?????u c???a lo???i ch??? d???c
        var need_vertical_thread_number = 0; // results


        // var length_btp = orderForm.getItemValue('length_btp');

        var selectedMasterDataId = masterDataGrid.getSelectedRowId();
        var machine_type = masterDataGrid.cells(selectedMasterDataId, 0).getValue().toLowerCase(); // machine type
        var length_btp = Number(masterDataGrid.cells(selectedMasterDataId, 1).getValue()); // chi???u d??i btp
        var taffeta_satin = masterDataGrid.cells(selectedMasterDataId, 19).getValue(); // Taffeta ho???c Satin
        var vertical_thread_type = masterDataGrid.cells(selectedMasterDataId, 5).getValue(); // lo???i ch??? d???c


        density_vertical_thread = vertical_thread_type.substr(0, 2); // c???t 2 k?? t??? ?????u

        if (machine_type == 'wv') {
            if (taffeta_satin == 'taffeta') {
                taffeta_satin_number = 6032;
            } else if (taffeta_satin == 'satin') {
                taffeta_satin_number = 12064;
            }

        } else if (machine_type == 'cw') {
            if (taffeta_satin == 'taffeta') {
                taffeta_satin_number = 8704;
            } else if (taffeta_satin == 'satin') {
                taffeta_satin_number = 18624;
            }

        } else if (machine_type == 'lb') {
            if (taffeta_satin == 'taffeta') {
                taffeta_satin_number = 8704;
            } else if (taffeta_satin == 'satin') {
                taffeta_satin_number = 18624;
            }
        }

        // result 
        need_vertical_thread_number = (so_cai_total * length_btp * taffeta_satin_number) / 9000000 / density_vertical_thread;
        need_vertical_thread_number = need_vertical_thread_number.toFixed(2);
        return need_vertical_thread_number;

    }

    // load data to form
    function loadOrderForm(dataLoad) {

        // process load form
        orderForm.clear();

        var data = dataLoad.prefixNoData;
        var formDataEdit = dataLoad.formDataEdit;
        var batchingData = dataLoad.batchingData;

        orderForm.setItemValue('formNO', data['prefix_new']);
        orderForm.setItemValue('formDate', data['po_date_new']);
        orderForm.setItemValue('fod', data['fod']);

        orderForm.setItemValue('running_time_total', batchingData['running_time_total']);
        orderForm.setItemValue('socai_group_total', batchingData['socai_group_total']);

        var formDataEdit_length = Object.keys(formDataEdit).length;
        formDataEdit_length = Number(formDataEdit_length);

        if (formDataEdit_length > 0) {
            orderForm.setItemValue('orderedType', formDataEdit['order_type']);
            orderForm.setItemValue('orderedDate', formDataEdit['ordered_date']);
            orderForm.setItemValue('requestDate', formDataEdit['request_date']);
            orderForm.setItemValue('promiseDate', formDataEdit['promise_date']);
        } else {
            // ????n batching
            var date_check = [];
            var request_date_min_pos;
            var orderedDate;
            var requestDate;
            var promiseDate;
            var order_type_cookie = getCookie('plan_order_type');
            if (order_type_cookie == 'common') {
                if (orderGrid.getRowsNum()) {
                    orderGrid.forEachRow(function(irO) {
                        var requestDate = Date.parse(orderGrid.cells(irO, 7).getValue());
                        date_check.push(requestDate);
                    });

                    if (date_check.length > 0) {
                        var request_date_min = Math.min.apply(Math, date_check);
                        request_date_min_pos = date_check.indexOf(request_date_min);
                    }

                    orderForm.setItemValue('orderedDate', orderGrid.cellByIndex(request_date_min_pos, 6).getValue());
                    orderForm.setItemValue('requestDate', orderGrid.cellByIndex(request_date_min_pos, 7).getValue());
                    orderForm.setItemValue('promiseDate', orderGrid.cellByIndex(request_date_min_pos, 8).getValue());
                }

            } else {
                // Cac don khac

            }

            // get Order type name
            if (orderGrid.getRowsNum()) {
                orderForm.setItemValue('orderedType', orderGrid.cellByIndex(0, 5).getValue());

                // ?????i v???i ????n Buildstock th?? m???c ?????nh NORMAL
                if (order_type_cookie == 'buildstock') {
                    orderForm.setItemValue('orderedType', 'NORMAL');
                }
            } else {
                orderForm.setItemValue('orderedType', 'NORMAL');
            }


        }

        // count_size
        var count_size = sizeGrid.getRowsNum();
        orderForm.setItemValue('count_size', count_size);

        var qty_total = 0;
        if (orderGrid.getRowsNum()) {
            orderGrid.forEachRow(function(irO) {
                var checked = orderGrid.cells(irO, 0).getValue();
                if (checked) {
                    qty_total += Number(orderGrid.cells(irO, 3).getValue())
                }
            });
        }


        if (orderGrid.getRowsNum()) {
            var order_type = orderGrid.cells(1, 5).getValue();
            var item = orderGrid.cells(1, 2).getValue();
        }

        var selectedRowIdMaster = masterDataGrid.getSelectedRowId();
        if (selectedRowIdMaster && masterDataGrid.getRowsNum()) {

            var length_btp = masterDataGrid.cells(selectedRowIdMaster, 1).getValue();
            var folding_cut_type = masterDataGrid.cells(selectedRowIdMaster, 6).getValue(); // loai cat gap
            var pattern = masterDataGrid.cells(selectedRowIdMaster, 7).getValue();
            var gear_density = masterDataGrid.cells(selectedRowIdMaster, 8).getValue();
            var length_tp = masterDataGrid.cells(selectedRowIdMaster, 9).getValue();
            var width_tp = masterDataGrid.cells(selectedRowIdMaster, 10).getValue();
            var water_glue_rate = masterDataGrid.cells(selectedRowIdMaster, 17).getValue(); // T??? l??? h???/ n?????c
            var textile_size_number = masterDataGrid.cells(selectedRowIdMaster, 20).getValue(); // S??? kh???
            var width_btp = masterDataGrid.cells(selectedRowIdMaster, 27).getValue();

            var machine_type = masterDataGrid.cells(selectedRowIdMaster, 0).getValue();

            var item = masterDataGrid.cells(selectedRowIdMaster, 2).getValue();
            var rbo = masterDataGrid.cells(selectedRowIdMaster, 3).getValue();
            var wire_number = masterDataGrid.cells(selectedRowIdMaster, 4).getValue(); // so day

            var vertical_thread_type = masterDataGrid.cells(selectedRowIdMaster, 5).getValue(); // loai chi doc

            orderForm.setItemValue('wire_number', wire_number);
            orderForm.setItemValue('gear_density', gear_density);
            orderForm.setItemValue('textile_size_number', textile_size_number);
            orderForm.setItemValue('warp_yarn_number', 114);
            orderForm.setItemValue('board', 'board');
            orderForm.setItemValue('item', item);
            orderForm.setItemValue('rbo', rbo);
            orderForm.setItemValue('length_tp', length_tp);
            orderForm.setItemValue('width_tp', width_tp);
            orderForm.setItemValue('length_btp', length_btp);
            orderForm.setItemValue('width_btp', width_btp);
            orderForm.setItemValue('pattern', pattern);
            orderForm.setItemValue('folding_cut_type', folding_cut_type);
            orderForm.setItemValue('water_glue_rate', water_glue_rate);

        }

        var pick_number_total = 0;
        if (supplyGrid.getRowsNum()) {
            supplyGrid.forEachRow(function(id) {
                pick_number_total += Number(supplyGrid.cells(id, 3).getValue()); // + s??? pick 
            });

            orderForm.setItemValue('pick_number_total', pick_number_total);
        }

    }

    // Load Process data to form
    function getIdentificationItem() {
        var selectedRowIdMasterData = masterDataGrid.getSelectedRowId();
        if (selectedRowIdMasterData && masterDataGrid.getRowsNum()) {
            machineType_check = masterDataGrid.cells(selectedRowIdMasterData, 0).getValue().toLowerCase();
            item_check = masterDataGrid.cells(selectedRowIdMasterData, 2).getValue();
            length_btp_check = masterDataGrid.cells(selectedRowIdMasterData, 1).getValue();
            folding_cut_type = masterDataGrid.cells(selectedRowIdMasterData, 6).getValue();
        }
    }

    // load process
    function loadProcess(processData) {

        let setting_process = <?php echo json_encode($setting_process); ?>;
        for (i = 1; i <= setting_process.length; i++) {
            let process_check = 'process_' + i;
            if (orderForm.getItemLabel(process_check)) {
                orderForm.setItemValue(process_check, 1);
            }
        }
        // processData.forEach(function(element) {
        //     orderForm.setItemValue(element['process_code'], 1);
        // });

    }

    function dateFormat(data) {
        var d = new Date(data);
        //d = d.toLocaleString('en-US', { timeZone: 'Asia/Bangkok' });
        // var d = new Date(data);
        var day, month, year;

        day = d.getDate();
        if (day <= 9) {
            day = "0" + day;
        }

        month = d.getMonth();
        year = d.getFullYear();
        // year = year.toString();
        var date_string = year + "-" + month + "-" + day;

        var results = new Date(date_string);
        return results;
    }

    // get Order Data selected to save
    function getDataSave() {
        var poDataSave = [];
        var solineDataSave = [];
        var processDataSave = [];
        var supplyDataSave = [];
        var sizeDataSave = [];
        var getDataSave = [];
        var running_time = 0;

        getIdentificationItem();

        var updated_by = 'updated_by';
        var production_line = 'woven';

        // get type (ccr, builstock, normal/fod) and suffix
        var po_no_suffix = ''; // 
        var type = '';
        var fod = orderForm.getItemValue('fod');
        var order_type_cookie = getCookie('plan_order_type');
        var non_batching = getCookie('non_batching');
        if (order_type_cookie == 'ccr') {
            type = 'ccr';
            po_no_suffix = 'CCR';
        } else if (order_type_cookie == 'buildstock') {
            type = 'buildstock';
            po_no_suffix = 'BUILDSTOCK';
            if (fod) { po_no_suffix = 'FOD'; }
        } else if (order_type_cookie == 'common') {
            type = 'common';
            po_no_suffix = 'NORMAL';
            if (fod) {
                po_no_suffix = 'FOD';
            }

            // ?????i v???i ????n non batching
            if (non_batching == 'non_batching') {
                type = 'non_batching';
            }
        }

        // set data from form
        var order_type = orderForm.getItemValue('orderedType');
        var wire_number = orderForm.getItemValue('wire_number');
        var gear_density = orderForm.getItemValue('gear_density');
        var count_size = orderForm.getItemValue('count_size');
        var pick_number_total = Number(orderForm.getItemValue('pick_number_total'));
        var textile_size_number = orderForm.getItemValue('textile_size_number');
        var warp_yarn_number = orderForm.getItemValue('warp_yarn_number'); // s??? s???i d???c
        var board = orderForm.getItemValue('board'); // 
        var qty_total = Number(orderForm.getItemValue('qty_total'));

        var prefixNo = orderForm.getItemValue('formNO').trim();
        var po_date = orderForm.getItemValue('formDate');
        po_date = po_date.toLocaleString('en-US', {
            timeZone: 'Asia/Bangkok'
        });

        var internal_item = orderForm.getItemValue('item'); // 
        var rbo = orderForm.getItemValue('rbo'); // 
        var length_tp = orderForm.getItemValue('length_tp'); // 
        var width_tp = orderForm.getItemValue('width_tp'); // 
        var length_btp = orderForm.getItemValue('length_btp'); // 
        var width_btp = orderForm.getItemValue('width_btp'); // 

        var ordered_date = orderForm.getItemValue('orderedDate');
        var request_date = orderForm.getItemValue('requestDate');
        var promise_date = orderForm.getItemValue('promiseDate');
        var pattern = orderForm.getItemValue('pattern');
        var need_vertical_thread_number = Number(orderForm.getItemValue('need_vertical_thread_number'));
        var folding_cut_type = orderForm.getItemValue('folding_cut_type');
        var water_glue_rate = orderForm.getItemValue('water_glue_rate');
        var meters_per_roll_check = orderForm.getItemValue('meters_per_roll_check');

        var count_lines = orderGrid.getRowsNum();

        // Cong thuc tinh met cuon neu checkbox met cuon duoc chon.
        // CT cu: // $ly_thuyet_met_cuon = (($total_row_size*$width_BTP)/1000)*(3/2);
        var meters_per_roll = 0;
        if (meters_per_roll_check) {
            meters_per_roll = Number(((qty_total * Number(width_btp)) / 1000) * (3 / 2));
        }

        // 2. master Data Grid
        var selectedRowIdMaster = masterDataGrid.getSelectedRowId();
        if (selectedRowIdMaster && masterDataGrid.getRowsNum()) {

            var machine_type = masterDataGrid.cells(selectedRowIdMaster, 0).getValue().toLowerCase();
            // var length_btp = masterDataGrid.cells(selectedRowIdMaster,1).getValue();
            // var item = masterDataGrid.cells(selectedRowIdMaster,2).getValue();
            // var rbo = masterDataGrid.cells(selectedRowIdMaster,3).getValue();
            // var wire_number = masterDataGrid.cells(selectedRowIdMaster,4).getValue(); // so day

            var vertical_thread_type = masterDataGrid.cells(selectedRowIdMaster, 5).getValue(); // loai chi doc
            var folding_cut_type = masterDataGrid.cells(selectedRowIdMaster, 6).getValue(); // loai cat gap
            var pattern = masterDataGrid.cells(selectedRowIdMaster, 7).getValue();
            var gear_density = masterDataGrid.cells(selectedRowIdMaster, 8).getValue();
            var length_tp = masterDataGrid.cells(selectedRowIdMaster, 9).getValue();
            var width_tp = masterDataGrid.cells(selectedRowIdMaster, 10).getValue();
            var cbs = masterDataGrid.cells(selectedRowIdMaster, 11).getValue();
            var scrap = masterDataGrid.cells(selectedRowIdMaster, 12).getValue();
            var sawing_method = masterDataGrid.cells(selectedRowIdMaster, 13).getValue(); // Phuong phap xe
            var cw_specification = masterDataGrid.cells(selectedRowIdMaster, 14).getValue(); // thong so ky thuat CW

            var heat_weaving = masterDataGrid.cells(selectedRowIdMaster, 15).getValue(); // Nhiet det
            var meter_number_per_machine = masterDataGrid.cells(selectedRowIdMaster, 16).getValue(); // Met cuon
            var water_glue_rate = masterDataGrid.cells(selectedRowIdMaster, 17).getValue(); // T??? l??? h???/ n?????c
            var so_cai_min = masterDataGrid.cells(selectedRowIdMaster, 18).getValue(); // s??? c??i min
            var taffeta_satin = masterDataGrid.cells(selectedRowIdMaster, 19).getValue(); // Taffeta ho???c Satin

            var textile_size_number = masterDataGrid.cells(selectedRowIdMaster, 20).getValue(); // S??? kh???
            var new_wire_number = masterDataGrid.cells(selectedRowIdMaster, 21).getValue(); // S??? D??y m???i
            var remark_1 = masterDataGrid.cells(selectedRowIdMaster, 22).getValue();
            var remark_2 = masterDataGrid.cells(selectedRowIdMaster, 23).getValue();
            var remark_3 = masterDataGrid.cells(selectedRowIdMaster, 24).getValue();

            var special_item_remark = masterDataGrid.cells(selectedRowIdMaster, 28).getValue();
            var process_arr = masterDataGrid.cells(selectedRowIdMaster, 29).getValue();

            // 9 process
            var process_item, status;
            var cut_type = '';

            // let setting_process = <?php echo json_encode($setting_process); ?>;
            // let process_order = 1;
            // for (let i = 0; i < setting_process.length; i++) {
            //     status = Number(orderForm.getItemValue(setting_process[i]['process_code']));
            //     if (!status) {
            //         continue;
            //     } else {
            //         let process_code = setting_process[i]['process_code'];
            //         let process_name = setting_process[i]['process_name_vi'];

            //         if (process_code == 'XS') {
            //             cut_type += 'Sonic'; // Sonic
            //         }

            //         if (process_code == 'QH') {
            //             cut_type += '/Coating'; // qua h???
            //         }

            //         if (process_code == 'QN') {
            //             cut_type += '/Qua N?????c'; // qua nuoc
            //         }

            //         if (process_code == 'DK') {
            //             cut_type += '/D??n Keo'; // Dan keo
            //         }

            //         if (process_code == 'CG') {
            //             cut_type += '/' + folding_cut_type; // c???t g???p
            //         }

            //         if (process_code == 'LS') {
            //             cut_type += '/Laser'; // c???t laser
            //         }


            //         process_item = {
            //             po_no: prefixNo,
            //             machine_type: machine_type,
            //             internal_item: internal_item,
            //             length_btp: length_btp,
            //             process_code: process_code,
            //             process_name: process_name,
            //             process_order: process_order,
            //             status: 1
            //         }


            //         console.log('process_js: ' + JSON.stringify(process_item));

            //         processDataSave.push(process_item);

            //         process_order++;
            //     }
            // }


            let setting_process = <?php echo json_encode($setting_process); ?>;
            let process_order = 1;
            for (let i = 1; i <= setting_process.length; i++) {

                process_name_check = 'process_' + i;

                if (orderForm.getItemLabel(process_name_check)) {
                    let process_string = orderForm.getItemLabel(process_name_check);
                    let process_arr = process_string.split('-');
                    let process_code = process_arr[0];
                    let process_name = process_arr[1];
                    let process_order = i;

                    if (process_code == 'XS') {
                        cut_type += 'Sonic'; // Sonic
                    }

                    if (process_code == 'QH') {
                        cut_type += '/Coating'; // qua h???
                    }

                    if (process_code == 'QN') {
                        cut_type += '/Qua N?????c'; // qua nuoc
                    }

                    if (process_code == 'DK') {
                        cut_type += '/D??n Keo'; // Dan keo
                    }

                    if (process_code == 'CG') {
                        cut_type += '/' + folding_cut_type; // c???t g???p
                    }

                    if (process_code == 'LS') {
                        cut_type += '/Laser'; // c???t laser
                    }


                    process_item = {
                        po_no: prefixNo,
                        machine_type: machine_type,
                        internal_item: internal_item,
                        length_btp: length_btp,
                        process_code: process_code,
                        process_name: process_name,
                        process_order: process_order,
                        status: 1
                    }


                    // console.log('process_js: ' + JSON.stringify(process_item));

                    processDataSave.push(process_item);
                }
            }


            // Supply Data Grid
            var supply_item;
            var count_supply = 0;
            var thread_length_total = 0;
            var need_horizontal_thread_total = 0;
            if (supplyGrid.getRowsNum()) {
                supplyGrid.forEachRow(function(id) {

                    var code_order = Number(supplyGrid.cells(id, 0).getValue()); // th??? t??? v???t t??
                    var code_name = supplyGrid.cells(id, 1).getValue(); // m?? v???t t??
                    var density = supplyGrid.cells(id, 2).getValue(); // M???t ????? (theo v???t t??)
                    var pick_number = Number(supplyGrid.cells(id, 3).getValue()); // s??? pick 
                    var thread_length = Number(supplyGrid.cells(id, 4).getValue()); // Chi???u d??i ch???
                    var need_horizontal_thread = Number(supplyGrid.cells(id, 5).getValue()); // Ch??? ngang c???n

                    count_supply++;
                    thread_length_total += thread_length;
                    need_horizontal_thread_total += need_horizontal_thread;

                    var code_type = 'supply';
                    supply_item = {
                        po_no: prefixNo,
                        machine_type: machine_type,
                        internal_item: internal_item,
                        length_btp: length_btp,
                        code_name: code_name,
                        code_type: code_type,
                        density: density,
                        pick_number: pick_number,
                        order: code_order,
                        thread_length: thread_length,
                        need_horizontal_thread: need_horizontal_thread
                    }

                    // set supply data to save. C?? nhi???u d??ng d??? li???u ????? save.
                    supplyDataSave.push(supply_item);
                });
            }

            // Glue data Grid
            var glue_item;
            if (glueGrid.getRowsNum()) {
                glueGrid.forEachRow(function(id) {
                    var glue_order = Number(glueGrid.cells(id, 0).getValue()); // th??? t??? keo
                    var glue_code = glueGrid.cells(id, 1).getValue(); // m?? keo (code_name)
                    var need_horizontal_thread = glueGrid.cells(id, 2).getValue(); // s??? l?????ng keo (c??c code keo s??? l?????ng b???ng nhau)
                    var code_type = 'glue';
                    // keo n??n kh??ng c?? c??c th??ng tin sau
                    var density = 0;
                    var pick_number = 0;
                    var thread_length = 0;
                    // S??? l?????ng keo c???n 2 code gi???ng nhau:  need_horizontal_thread = (s??? c??i t???ng * chi???u d??i btp) /1000
                    if (!need_horizontal_thread) need_horizontal_thread = 0;

                    supply_item = {
                        po_no: prefixNo,
                        machine_type: machine_type,
                        internal_item: internal_item,
                        length_btp: length_btp,
                        code_name: glue_code,
                        code_type: code_type,
                        density: density,
                        pick_number: pick_number,
                        order: glue_order,
                        thread_length: thread_length,
                        need_horizontal_thread: need_horizontal_thread
                    }

                    supplyDataSave.push(supply_item);
                });
            }

            // size data Grid 
            var size_item;
            var so_cai_total = 0;
            var up_user = '';
            var no_number = '';
            var color = 'non';
            var material_code = '';

            // Do ????n h??ng ccr ho???c h??ng buildstock ch??? c?? nhi???u nh???t 1 so_line 
            var so_line = orderGrid.cellByIndex(0, 1).getValue(); // so_line

            if (sizeGrid.getRowsNum()) {

                count_size = sizeCheckArr.length;

                for (var id = 0; id < count_size; id++) {

                    var size = sizeGrid.cellByIndex(sizeCheckArr[id], 1).getValue(); // size 
                    var so_cai_size = Number(sizeGrid.cellByIndex(sizeCheckArr[id], 2).getValue()); // s??? c??i

                    // // check so cai (tr?????ng h???p ?????c bi???t)
                    // so_cai_size = getSpecialSocai(order_type_cookie, rbo, so_cai_size);


                    var qty = sizeGrid.cellByIndex(sizeCheckArr[id], 3).getValue(); // qty c???a t???ng so_line, size c???a c???t th??? cId (theo so_line)
                    var target_qty = so_cai_size * Number(wire_number);

                    so_cai_total += so_cai_size;

                    size_item = {
                        up_user: up_user,
                        production_line: production_line,
                        no_number: no_number,
                        so_line: so_line,
                        size: size,
                        color: color,
                        qty: qty,
                        material_code: material_code,
                        so_cai_size: so_cai_size,
                        target_qty: target_qty
                    }

                    // set data to save. C?? nhi???u d??ng d??? li???u ????? save.
                    sizeDataSave.push(size_item);
                }

            }

            var running_time_total = 0;

            // (sum(s??? c??i) x t???ng s??? pick)/s??? ph??t theo m??y. s??? ph??t theo m??y = Satin (450), Taffeta (540)
            // 2022-02-10: Tien yeu cau sua xong gui mail sau. s??? ph??t theo m??y = Satin (450) ==> 500, Taffeta (540) ==> 605
            var machine_minutes = 1;
            if (taffeta_satin == 'satin') {
                // machine_minutes = 450;
                machine_minutes = 500;
            } else if (taffeta_satin == 'taffeta') {
                // machine_minutes = 540;
                machine_minutes = 605;
            }

            // th???i gian ch???y m??y 
            if (orderForm.getItemValue('running_time_total')) {
                running_time_total = orderForm.getItemValue('running_time_total');
            } else {
                running_time_total = (so_cai_total * pick_number_total) / machine_minutes / 60;
                running_time_total = running_time_total.toFixed(2);
            }

            if (orderForm.getItemValue('socai_group_total')) {
                so_cai_total = orderForm.getItemValue('socai_group_total');
            }

            // set po_save table data
            need_horizontal_thread_total = need_horizontal_thread_total.toFixed(2);
            need_horizontal_thread_total = Number(need_horizontal_thread_total);
            var printed = 0;

            var batch_no = orderGrid.cellByIndex(0, 14).getValue();
            poDataSave = {
                production_line: production_line,
                machine_type: machine_type,
                po_no: prefixNo,
                type: type,
                batch_no: batch_no,
                po_no_suffix: po_no_suffix,
                order_type: order_type,
                count_lines: count_lines,
                po_date: po_date,
                ordered_date: ordered_date,
                request_date: request_date,
                promise_date: promise_date,
                qty_total: qty_total,
                pick_number_total: pick_number_total,
                thread_length_total: thread_length_total,
                need_horizontal_thread_total: need_horizontal_thread_total,
                count_supply: count_supply,
                need_vertical_thread_number: need_vertical_thread_number,
                warp_yarn_number: warp_yarn_number, // so soi doc
                meters_per_roll: meters_per_roll,
                so_cai_total: so_cai_total,
                running_time_total: running_time_total,
                printed: printed,
                internal_item: internal_item,
                length_btp: length_btp,
                width_btp: width_btp,
                rbo: rbo,
                wire_number: wire_number,
                vertical_thread_type: vertical_thread_type,
                folding_cut_type: folding_cut_type,
                pattern: pattern,
                gear_density: gear_density,
                length_tp: length_tp,
                width_tp: width_tp,
                cbs: cbs,
                scrap: scrap,
                cut_type: cut_type,
                sawing_method: sawing_method,
                cw_specification: cw_specification,
                heat_weaving: heat_weaving,
                meter_number_per_machine: meter_number_per_machine,
                water_glue_rate: water_glue_rate,
                so_cai_min: so_cai_min,
                taffeta_satin: taffeta_satin,
                textile_size_number: textile_size_number,
                new_wire_number: new_wire_number,
                remark_1: remark_1,
                remark_2: remark_2,
                remark_3: remark_3,
                special_item_remark: special_item_remark,
                process: process_arr,
                updated_by: updated_by
            }

        } // end master 

        var soline_item;
        var warp_yarn = 'warp_yarn';
        if (orderGrid.getRowsNum()) {

            var so_line_total = 0;
            orderGrid.forEachRow(function(irO) {

                var so_line = orderGrid.cells(irO, 1).getValue();
                if (!so_line) {
                    so_line = 'non';
                }

                if (order_type_cookie == 'common') {
                    var qty_of_line = orderGrid.cells(irO, 3).getValue();
                    so_line_total += Number(qty_of_line);
                    if (!qty_of_line) {
                        if (orderGrid.getRowsNum() == 1) {
                            qty_of_line = qty_total;
                        } else {
                            qty_of_line = 0;
                        }

                    }
                } else {
                    qty_of_line = qty_total;
                }

                var ordered_item = orderGrid.cells(irO, 4).getValue();
                var order_type_name = orderGrid.cells(irO, 5).getValue();
                // var ordered_date = orderGrid.cells(irO,6).getValue();
                // var request_date = orderGrid.cells(irO,7).getValue();
                // var promise_date = orderGrid.cells(irO,8).getValue();
                var ship_to_customer = orderGrid.cells(irO, 9).getValue();
                var bill_to_customer = orderGrid.cells(irO, 10).getValue();
                var cs = orderGrid.cells(irO, 11).getValue();
                var packing_instr = orderGrid.cells(irO, 12).getValue();
                var attachment = orderGrid.cells(irO, 13).getValue();
                var running_time = (((running_time_total * qty_of_line) / qty_total * 100) / 100).toFixed(2);
                // round((($soline_item['running_time'] * ($soline_item['qty_of_line']/$qty_total*100)) / 100), 2);

                soline_item = {
                    po_no: prefixNo,
                    so_line: so_line,
                    internal_item: internal_item,
                    length_btp: length_btp,
                    qty_of_line: qty_of_line,
                    running_time: running_time,
                    count_size: count_size,
                    warp_yarn: warp_yarn,
                    ordered_item: ordered_item,
                    order_type_name: order_type_name,
                    ship_to_customer: ship_to_customer,
                    bill_to_customer: bill_to_customer,
                    cs: cs,
                    packing_instr: packing_instr,
                    attachment: attachment
                }

                // set order data to save 
                solineDataSave.push(soline_item);

            });

            // Check s??? l?????ng ????n h??ng
            if (so_line_total !== qty_total) {
                if (order_type_cookie != 'buildstock') {
                    var confContinue = confirm('T???ng s??? l?????ng THEO SIZE kh??c t???ng s??? l?????ng THEO SO#.\nV???n ti???p t???c l??m l???nh?');
                    if (!confContinue) {
                        window.location = '<?php echo base_url('woven'); ?>';
                        return false;
                    }
                }


            }

        }

        // results
        getDataSave = {
            "poDataSave": poDataSave,
            "processDataSave": processDataSave,
            "supplyDataSave": supplyDataSave,
            "solineDataSave": solineDataSave,
            "sizeDataSave": sizeDataSave
        }

        return getDataSave;
    }

    function importMaster() {

        if (!dhxWins) {
            dhxWins = new dhtmlXWindows();
        }

        var id = "WindowsDetail";
        var w = 400;
        var h = 100;
        var x = Number(($(window).width() - 400) / 2);
        var y = Number(($(window).height() - 50) / 2);
        var Popup = dhxWins.createWindow(id, x, y, w, h);
        dhxWins.window(id).setText("Import Master Data");
        Popup.attachHTMLString(
            '<div style="width:500%;margin:20px">' +
            '<form action="<?php echo base_url('woven/importMasterData'); ?>" enctype="multipart/form-data" method="post" accept-charset="utf-8">' +
            '<input type="file" name="masterfile" id="masterfile" class="form-control filestyle" value="value" data-icon="false"  />' +
            '<input type="submit" name="importfile" value="Import" id="importfile-id" class="btn btn-block btn-primary"  />' +
            '</form>' +
            '</div>'
        );
    }

    function exportMasterData(id) {
        location.href = "<?php echo base_url('woven/exportMasterData?option='); ?> " + id;
    }

    function saveOrders() {
        var jsonObjects = getDataSave();
        if (jsonObjects == false) {
            alert('Input save data, please! ');
            return false;
        }

        jsonObjects = JSON.stringify(jsonObjects);

        // console.log('get Data save: ' + jsonObjects);
        // return false;

        var url = "<?php echo base_url('/woven/saveOrders/'); ?>";

        //excute with ajax function 
        $.ajax({
            type: "POST",
            data: {
                data: jsonObjects
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
                            window.location = '<?php echo base_url('woven'); ?>';
                        }
                    }
                } catch (e) {
                    alert('L??u d??? li???u kh??ng th??nh c??ng. Vui l??ng li??n h??? qu???n tr??? h??? th???ng. L???i: ' + e);
                    return false;
                }
            },
            error: function(xhr, status, error) {
                // alert(error);alert(xhr.responseText);
                alert('L??u d??? li???u kh??ng th??nh c??ng: Vui l??ng li??n h??? qu???n tr??? h??? th???ng. ' + xhr.responseText);
                return false;
            }
        });
    }

    // Chuy???n trang ?????n trang in
    function printOrders(po_no) {
        var wi = window.open('about:blank', '_blank');
        wi.window.location = '<?php echo base_url("woven/printOrders/"); ?>' + po_no;
        window.location = '<?php echo base_url('woven'); ?>';
    }

    function delete_confirm(po_no) {
        var message = "B???n c?? ch???c ch???n mu???n x??a NO# " + po_no + " ?";
        if (!window.confirm(message)) {
            return false;
        } else {
            window.location = '<?php echo base_url('woven'); ?>';
        }
    }

    // Edit
    function editCommonOrder(batch_no) {

        var data = {
            batch_no: batch_no
        };
        var jsonObjects = JSON.stringify(data);
        var url = "<?php echo base_url('woven/batchingOrderEdit'); ?>";

        //excute with ajax function 
        $.ajax({
            type: "POST",
            data: {
                data: jsonObjects
            },
            url: url,
            dataType: 'json',
            beforeSend: function(x) {
                if (x && x.overrideMimeType) {
                    x.overrideMimeType("application/j-son;charset=UTF-8");
                }
            },
            success: function(results) {
                if (results.status == false) {
                    alert(results.message);
                    window.location = '<?php base_url('woven'); ?>';
                    return false;
                } else {
                    // edit
                    var suffix_url = "?batch_no=" + results.batch_no + "&po_no_edit=" + results.po_no_edit;
                    var url = "<?php echo base_url('woven/commonOrder/'); ?>" + suffix_url;
                    location.href = url;

                }
            },
            error: function(xhr, status, error) {
                // alert(error);alert(xhr.responseText);
                alert('L??u d??? li???u kh??ng th??nh c??ng: Vui l??ng li??n h??? qu???n tr??? h??? th???ng. ' + xhr.responseText);
                return false;
            }
        });

    }


    function loadMasterFileGrid() {
        //json data encode
        // var jsonObjects = { "order_number": order_number, "line_number": line_number };
        var jsonObjects = {};
        var url = "<?php echo base_url('woven/loadMasterFile/'); ?>";

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
            success: function(results) {

                if (results.status == false) {
                    alert(results.message);
                } else {

                    count_ms = results.dataMaster.length;
                    var data = {
                        rows: []
                    };

                    for (var i = 0; i < results.dataMaster.length; i++) {
                        data.rows.push(results.dataMaster[i]);
                    }

                    // load grid
                    masterfileGrid.parse(data, "json");
                    // set cell layout 
                    masterfileLayout.cells("a").setText("DATA MAIN MASTER:  " + count_ms + " rows");
                    masterfileLayout.cells("a").progressOff();

                    loadMasterFileForm();

                }

            },
            error: function(xhr, status, error) {
                // alert(error);alert(xhr.responseText);
                alert('L??u d??? li???u kh??ng th??nh c??ng: Vui l??ng li??n h??? qu???n tr??? h??? th???ng. ' + xhr.responseText);
                return false;
            }
        });
    }
    // master data: load supply data grid
    function loadMasterFileSupplyGrid() {
        //json data encode
        // var jsonObjects = { "order_number": order_number, "line_number": line_number };
        var jsonObjects = {};
        var url = "<?php echo base_url('woven/loadMasterFileSupply/'); ?>";

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
            success: function(results) {

                if (results.status == false) {
                    alert(results.message);
                } else {

                    count_ms = results.dataMaster.length;
                    var data = {
                        rows: []
                    };

                    for (var i = 0; i < results.dataMaster.length; i++) {
                        data.rows.push(results.dataMaster[i]);
                    }

                    // load grid
                    masterfileGrid.parse(data, "json");
                    // set cell layout 
                    masterfileLayout.cells("a").setText("SUPPLY DATA:  " + count_ms + " rows");
                    masterfileLayout.cells("a").progressOff();

                    // set form data
                    loadMasterFileSupplyForm();

                }

            },
            error: function(xhr, status, error) {
                // alert(error);alert(xhr.responseText);
                alert('L??u d??? li???u kh??ng th??nh c??ng: Vui l??ng li??n h??? qu???n tr??? h??? th???ng. ' + xhr.responseText);
                return false;
            }
        });

    }


    // master data: load main master data form
    function loadMasterFileForm() {
        // true will cal function onRow select
        if (masterfileGrid.getRowsNum()) {

            // select row 0
            masterfileGrid.selectRow(0, true);

            // attach row select
            masterfileGrid.attachEvent("onRowSelect", function(rId, ind) {

                // get data
                var machine_type = masterfileGrid.cells(rId, 1).getValue().trim();
                var internal_item = masterfileGrid.cells(rId, 2).getValue().trim();
                var length_btp = masterfileGrid.cells(rId, 3).getValue().trim();
                var width_btp = masterfileGrid.cells(rId, 4).getValue().trim();
                var rbo = masterfileGrid.cells(rId, 5).getValue().trim();
                var wire_number = Number(masterfileGrid.cells(rId, 6).getValue().trim());
                var vertical_thread_type = masterfileGrid.cells(rId, 7).getValue().trim();
                var folding_cut_type = masterfileGrid.cells(rId, 8).getValue().trim();
                var pattern = masterfileGrid.cells(rId, 9).getValue().trim();
                var gear_density = masterfileGrid.cells(rId, 10).getValue().trim();

                var length_tp = Number(masterfileGrid.cells(rId, 11).getValue().trim());
                var width_tp = Number(masterfileGrid.cells(rId, 12).getValue().trim());
                var cbs = Number(masterfileGrid.cells(rId, 13).getValue().trim());
                var scrap = Number(masterfileGrid.cells(rId, 14).getValue().trim());
                var cut_type = masterfileGrid.cells(rId, 15).getValue().trim();
                var sawing_method = masterfileGrid.cells(rId, 16).getValue().trim();
                var cw_specification = Number(masterfileGrid.cells(rId, 17).getValue().trim());
                var heat_weaving = masterfileGrid.cells(rId, 18).getValue().trim();
                var meter_number_per_machine = Number(masterfileGrid.cells(rId, 19).getValue().trim());
                var water_glue_rate = masterfileGrid.cells(rId, 20).getValue().trim();

                var so_cai_min = Number(masterfileGrid.cells(rId, 21).getValue().trim());
                var taffeta_satin = masterfileGrid.cells(rId, 22).getValue().trim();
                var textile_size_number = masterfileGrid.cells(rId, 23).getValue().trim();
                if (textile_size_number) Number(textile_size_number);

                var new_wire_number = Number(masterfileGrid.cells(rId, 24).getValue().trim());
                var scrap_sonic = Number(masterfileGrid.cells(rId, 25).getValue().trim());
                // var pick_number_total = Number(masterfileGrid.cells(rId,26).getValue().trim() );
                var remark_1 = masterfileGrid.cells(rId, 26).getValue().trim();
                var remark_2 = masterfileGrid.cells(rId, 27).getValue().trim();
                var remark_3 = masterfileGrid.cells(rId, 28).getValue().trim();

                var special_item_remark = masterfileGrid.cells(rId, 31).getValue().trim();
                var process_arr = masterfileGrid.cells(rId, 32).getValue().trim();

                // set form data
                masterfileForm.setItemValue('machine_type', machine_type);
                masterfileForm.setItemValue('internal_item', internal_item);
                masterfileForm.setItemValue('length_btp', length_btp);
                masterfileForm.setItemValue('width_btp', width_btp);
                masterfileForm.setItemValue('rbo', rbo);
                masterfileForm.setItemValue('wire_number', wire_number);
                masterfileForm.setItemValue('vertical_thread_type', vertical_thread_type);
                masterfileForm.setItemValue('folding_cut_type', folding_cut_type);
                masterfileForm.setItemValue('pattern', pattern);
                masterfileForm.setItemValue('gear_density', gear_density);

                masterfileForm.setItemValue('length_tp', length_tp);
                masterfileForm.setItemValue('width_tp', width_tp);
                masterfileForm.setItemValue('cbs', cbs);
                masterfileForm.setItemValue('scrap', scrap);
                masterfileForm.setItemValue('cut_type', cut_type);
                masterfileForm.setItemValue('sawing_method', sawing_method);
                masterfileForm.setItemValue('cw_specification', cw_specification);
                masterfileForm.setItemValue('heat_weaving', heat_weaving);
                masterfileForm.setItemValue('meter_number_per_machine', meter_number_per_machine);
                masterfileForm.setItemValue('water_glue_rate', water_glue_rate);

                masterfileForm.setItemValue('so_cai_min', so_cai_min);
                masterfileForm.setItemValue('taffeta_satin', taffeta_satin);

                // X??? l?? tr?????ng h???p s??? kh???
                // N???u S??? kh??? r???ng th?? ??p d???ng c??ng th???c
                masterfileForm.setItemValue('textile_size_number', textile_size_number);
                if (textile_size_number == 0 || (textile_size_number == '')) {
                    checkTextileSizeNumber(machine_type, taffeta_satin, cut_type);
                }

                masterfileForm.setItemValue('new_wire_number', new_wire_number);

                masterfileForm.setItemValue('scrap_sonic', scrap_sonic);
                // masterfileForm.setItemValue('pick_number_total', pick_number_total );

                masterfileForm.setItemValue('remark_1', remark_1);
                masterfileForm.setItemValue('remark_2', remark_2);
                masterfileForm.setItemValue('remark_3', remark_3);

                masterfileForm.setItemValue('special_item_remark', special_item_remark);
                masterfileForm.setItemValue('process', process_arr);


            });

        }

    }

    /*  S??? KH???:  -------------------------------------------------------------
        Duy.PhamThi g???i: 
        - M??y CW, D???a v??o lo???i c???t: 
            + Sonic: 6 kh???
            + C??n l???i: 3 Kh???
        - M??y WV
            + tafeta: 5 kh???
            + Satin : 10 kh???
    */
    function checkTextileSizeNumber(machine_type, taffeta_satin, cut_type) {
        if (machine_type == 'wv') {
            taffeta_satin = taffeta_satin.toLowerCase();
            if (taffeta_satin == 'tafeta') {
                textile_size_number = 5;
            } else if (taffeta_satin == 'satin') {
                textile_size_number = 10;
            } else {
                textile_size_number = 5;
            }

        } else if (machine_type == 'cw') {
            cut_type = cut_type.toLowerCase();
            if (cut_type.indexOf('sonic') !== -1) {
                textile_size_number = 6;
            } else {
                textile_size_number = 3;
            }

        }

        masterfileForm.setItemValue('textile_size_number', textile_size_number);
    }

    // master data: load main supply form
    function loadMasterFileSupplyForm() {
        // true will cal function onRow select
        if (masterfileGrid.getRowsNum()) {

            // select row 0
            masterfileGrid.selectRow(0, true);

            // attach row select
            masterfileGrid.attachEvent("onRowSelect", function(rId, ind) {

                var internal_item = masterfileGrid.cells(rId, 1).getValue().trim();
                var length_btp = masterfileGrid.cells(rId, 2).getValue().trim();
                var code_name = masterfileGrid.cells(rId, 3).getValue().trim();
                var code_type = masterfileGrid.cells(rId, 4).getValue().trim();
                var density = masterfileGrid.cells(rId, 5).getValue().trim();
                var pick_number = Number(masterfileGrid.cells(rId, 6).getValue().trim());
                var order = Number(masterfileGrid.cells(rId, 7).getValue().trim());

                // set form data
                // masterfileForm.setItemValue('machine_type', machine_type );
                masterfileForm.setItemValue('internal_item', internal_item);
                masterfileForm.setItemValue('length_btp', length_btp);
                masterfileForm.setItemValue('code_name', code_name);
                masterfileForm.setItemValue('code_type', code_type);
                masterfileForm.setItemValue('density', density);
                masterfileForm.setItemValue('pick_number', pick_number);
                masterfileForm.setItemValue('order', order);


            });

        }

    }

    // delete main master data (woven_master_item table)
    function deleteMasterData(del_type, machine_type = '', internal_item, length_btp, code_name = '', order = '') {
        // alert for user
        var alertCheck = 'Vui l??ng ch???n d??ng mu???n x??a';
        // check and set data POST
        if (del_type == 'mainMaster') {
            if (!machine_type || !internal_item || !length_btp) {
                alert(alertCheck);
            }

            //json data encode
            var jsonObjects = {
                "del_type": del_type,
                "machine_type": machine_type,
                "internal_item": internal_item,
                "length_btp": length_btp
            };
            // confirm ok
            var confirm_del = confirm('B???n ch???c ch???n mu???n x??a ' + machine_type + ' - ' + internal_item + ' - ' + length_btp + '?');

        } else if (del_type == 'supply') {
            if (!internal_item || !length_btp || !code_name) {
                alert(alertCheck);
            }

            //json data encode
            var jsonObjects = {
                "del_type": del_type,
                "internal_item": internal_item,
                "length_btp": length_btp,
                "code_name": code_name,
                "order": order
            };
            // confirm ok
            var confirm_del = confirm('B???n ch???c ch???n mu???n x??a ' + internal_item + ' - ' + length_btp + ' - ' + code_name + ' - ' + order + '?');
        }


        if (!confirm_del) {
            // location.reload();
        } else { // delete

            // url 
            var url = "<?php echo base_url('/woven/deleteMasterData/'); ?>";
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
                    alert(data.message);
                    location.reload();
                },
                error: function(xhr, status, error) {
                    alert('Error. Master Data Delete!');
                    // location.reload();
                }
            });
        }


    }

    // report order data 
    function report(from_date, to_date) {
        var suffix_url = '?from_date=' + from_date + '&to_date=' + to_date;
        location.href = "<?php echo base_url('woven/reports/'); ?>" + suffix_url;
    }

    function downloadSampleFile() {
        location.href = "<?php echo base_url('woven/downloadSampleFile/'); ?>";
    }

    function importSpecialItem() {

        if (!dhxWins) {
            dhxWins = new dhtmlXWindows();
        }

        var id = "WindowsDetail";
        var w = 400;
        var h = 100;
        var x = Number(($(window).width() - 400) / 2);
        var y = Number(($(window).height() - 50) / 2);
        var Popup = dhxWins.createWindow(id, x, y, w, h);
        dhxWins.window(id).setText("Import Special Item");
        Popup.attachHTMLString(
            '<div style="width:500%;margin:20px">' +
            '<form action="<?php echo base_url('woven/importSpecialItem'); ?>" enctype="multipart/form-data" method="post" accept-charset="utf-8">' +
            '<input type="file" name="masterfile" id="masterfile" class="form-control filestyle" value="value" data-icon="false"  />' +
            '<input type="submit" name="importfile" value="Import" id="importfile-id" class="btn btn-block btn-primary"  />' +
            '</form>' +
            '</div>'
        );
    }

    // import d??? li???u ?????n b???ng woven_special_item_remarks
    function importSpecialTable() {

        if (!dhxWins) {
            dhxWins = new dhtmlXWindows();
        }

        var id = "WindowsDetail";
        var w = 400;
        var h = 100;
        var x = Number(($(window).width() - 400) / 2);
        var y = Number(($(window).height() - 50) / 2);
        var Popup = dhxWins.createWindow(id, x, y, w, h);
        dhxWins.window(id).setText("Import Special Table");
        Popup.attachHTMLString(
            '<div style="width:500%;margin:20px">' +
            '<form action="<?php echo base_url('woven/importSpecialTable'); ?>" enctype="multipart/form-data" method="post" accept-charset="utf-8">' +
            '<input type="file" name="masterfile" id="masterfile" class="form-control filestyle" value="value" data-icon="false"  />' +
            '<input type="submit" name="importfile" value="Import" id="importfile-id" class="btn btn-block btn-primary"  />' +
            '</form>' +
            '</div>'
        );
    }

    // import d??? li???u ?????n b???ng woven_special_item_remarks
    function importGYCG2() {

        if (!dhxWins) {
            dhxWins = new dhtmlXWindows();
        }

        var id = "WindowsDetail";
        var w = 400;
        var h = 100;
        var x = Number(($(window).width() - 400) / 2);
        var y = Number(($(window).height() - 50) / 2);
        var Popup = dhxWins.createWindow(id, x, y, w, h);
        dhxWins.window(id).setText("Import Data");
        Popup.attachHTMLString(
            '<div style="width:500%;margin:20px">' +
            '<form action="<?php echo base_url('woven/importGYCG2'); ?>" enctype="multipart/form-data" method="post" accept-charset="utf-8">' +
            '<input type="file" name="masterfile" id="masterfile" class="form-control filestyle" value="value" data-icon="false"  />' +
            '<input type="submit" name="importfile" value="Import" id="importfile-id" class="btn btn-block btn-primary"  />' +
            '</form>' +
            '</div>'
        );
    }

    // Hi???n th??? d??? li???u File name master data (mail: Re: Th??m t??n file 1A1B tr??n l???nh s???n xu???t)
    function fileNameGrid() {
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
            var fileNameGrid = dhxWins.window(id).attachGrid();

            // close
            Popup.attachEvent("onClose", function(win) {
                if (win.getId() == "Windows") win.hide();
            });

            // title
            dhxWins.window(id).setText("FileName Master");

            // init grid
            fileNameGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
            fileNameGrid.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter");
            fileNameGrid.setRowTextStyle("1", "background-color: red; font-family: arial;");
            fileNameGrid.init();
            fileNameGrid.enableSmartRendering(true); // false to disable

            // load data
            fileNameGrid.clearAll();
            fileNameGrid.loadXML("<?php echo base_url($production_line . '/loadFileNameData') ?>", function() {
                //set values for select box in 2th column
                combobox = fileNameGrid.getCombo(2);
                combobox.put("satin", "Satin");
                combobox.put("taffeta", "Taffeta");
            });


            // save or delete
            fileNameGrid.attachEvent("onCheckbox", function(rId, cInd, state) {

                var file_name = fileNameGrid.cells(rId, 1).getValue();
                var taffeta_satin = fileNameGrid.cells(rId, 2).getValue();
                var gear_density_limit = fileNameGrid.cells(rId, 3).getValue();

                //json data encode
                var jsonObjects = {
                    "file_name": file_name,
                    "taffeta_satin": taffeta_satin,
                    "gear_density_limit": gear_density_limit
                };

                if (cInd == 6) { // save
                    var url = "<?php echo base_url($production_line . '/saveFileName'); ?>";
                    if (jsonObjects && url) {

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
                                alert(data.message);
                                // reload
                                if (fileNameGrid.getRowsNum()) {
                                    fileNameGrid.clearAll();
                                    fileNameGrid.loadXML("<?php echo base_url($production_line . '/loadFileNameData') ?>", function() {
                                        //set values for select box in 2th column
                                        combobox = fileNameGrid.getCombo(2);
                                        combobox.put("satin", "Satin");
                                        combobox.put("taffeta", "Taffeta");
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                alert('Error. Vui l??ng li??n h??? qu???n tr??? h??? th???ng!');
                                location.reload();
                                return false;
                            }
                        });

                    }
                } else if (cInd == 7) { // del
                    var conf = confirm("X??a file name: " + file_name + "?");
                    if (conf) {
                        var url = "<?php echo base_url($production_line . '/deleteFileName'); ?>";
                        // deleteFileName(jsonObjects, url );
                        if (jsonObjects && url) {
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
                                    alert(data.message);
                                    // reload
                                    if (fileNameGrid.getRowsNum()) {
                                        fileNameGrid.clearAll();
                                        fileNameGrid.loadXML("<?php echo base_url($production_line . '/loadFileNameData') ?>", function() {
                                            //set values for select box in 2th column
                                            combobox = fileNameGrid.getCombo(2);
                                            combobox.put("satin", "Satin");
                                            combobox.put("taffeta", "Taffeta");
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    alert('Error. Vui l??ng li??n h??? qu???n tr??? h??? th???ng!');
                                    location.reload();
                                    return false;
                                }
                            });

                        }
                    }
                }

            });


        } else {
            dhxWins.window("Windows").show();
        }

    }


    // Hi???n th??? d??? li???u File name master data (mail: Re: Th??m t??n file 1A1B tr??n l???nh s???n xu???t)
    function loadProcessDetail(internal_item = null, code = null) {
        // close if exist
        if (dhxWins) {
            dhxWins.window("Windows").close();
        }

        // create
        dhxWins = new dhtmlXWindows();

        if (!dhxWins.isWindow("Windows")) {

            // init win
            let id = "Windows";
            let w = 960;
            let h = 600;
            let x = Number(($(window).width() - w) / 2);
            let y = Number(($(window).height() - h) / 2);
            let Popup = dhxWins.createWindow(id, x, y, w, h);

            // close
            Popup.attachEvent("onClose", function(win) {
                if (win.getId() == "Windows") win.hide();
            });

            // title
            dhxWins.window(id).setText("Update Process Data");

            //json data encode
            var jsonObjects = {
                "code": code
            };
            var url = "<?php echo base_url('woven/getProcess/?internal_item='); ?>" + internal_item + '&code=' + code;

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

                    let process_json = data.process_json;

                    loadProcessForm = Popup.attachForm();
                    loadProcessForm.loadStruct(process_json);

                    loadProcessForm.attachEvent("onButtonClick", function(name) {
                        if (name == 'update' || name == 'default') {

                            loadProcessForm.send("<?php echo base_url('woven/saveProcess/?name='); ?>" + name, "post", function(test, res) {

                                // parse json to object
                                var obj = JSON.parse(res);

                                if (obj.status == true) {
                                    alert(obj.message);
                                    location.href = '<?php echo base_url("woven/viewMasterFile/") ?>';
                                } else {
                                    alert(obj.message);
                                }
                            });

                        } else if (name == 'normal') {

                            let normal = 'DE-XS-ND-CG-DG';
                            let normal_arr = normal.split('-');

                            let count_setting_process = <?php echo $count_process; ?>;
                            let count = normal_arr.length;

                            let i = 0;
                            normal_arr.forEach(function(element) {
                                i++;
                                let index = (i < 10) ? '0' + i : i;
                                loadProcessForm.setItemValue('process_' + index, element);
                            });

                            for (let j = (count + 1); j <= count_setting_process; j++) {
                                let index = (j < 10) ? '0' + j : j;
                                loadProcessForm.setItemValue('process_' + index, 'none');
                            }

                        }
                    });


                },
                error: function(xhr, status, error) {
                    alert(xhr.responseText);
                    return false;
                }
            });

        } else {
            dhxWins.window("Windows").show();
        }

    }

    function masterProcess() {

        var confirm_user = confirm("????Y L?? CH???C N??NG QUAN TR???NG. B???N C?? CH???C CH???N TI???P T???C?");
        if (!confirm_user) return false;

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
            var processGrid = dhxWins.window(id).attachGrid();

            // close
            Popup.attachEvent("onClose", function(win) {
                if (win.getId() == "Windows") win.hide();
            });

            // title
            dhxWins.window(id).setText("Master Process");

            // init grid
            processGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>");
            processGrid.attachHeader(",#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
            processGrid.setRowTextStyle("1", "background-color: red; font-family: arial;");
            processGrid.init();
            processGrid.enableSmartRendering(true); // false to disable

            // load data
            processGrid.clearAll();
            processGrid.loadXML("<?php echo base_url($production_line . '/loadMasterProcess') ?>", function() {});


            // save or delete
            processGrid.attachEvent("onCheckbox", function(rId, cInd, state) {

                var process_code = processGrid.cells(rId, 1).getValue();
                var process_name_vi = processGrid.cells(rId, 2).getValue();
                var process_name_en = processGrid.cells(rId, 3).getValue();

                var event = '';
                if (cInd == 7) {
                    event = 'save';
                } else if (cInd == 8) {
                    var conf = confirm("Ch???c ch???n mu???n X??A Process: " + process_code + "-" + process_name_vi + "?");
                    if (!conf) return false;
                    event = 'delete';
                }

                //json data encode
                var jsonObjects = {
                    "event": event,
                    "process_code": process_code,
                    "process_name_vi": process_name_vi,
                    "process_name_en": process_name_en
                };


                var url = "<?php echo base_url($production_line . '/updateMasterProcess'); ?>";

                if (jsonObjects && url) {

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
                            alert(data.message);
                            // reload
                            if (processGrid.getRowsNum()) {
                                processGrid.clearAll();
                                processGrid.loadXML("<?php echo base_url($production_line . '/loadMasterProcess') ?>", function() {});
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('Error. Vui l??ng li??n h??? qu???n tr??? h??? th???ng!');
                            location.reload();
                            return false;
                        }
                    });

                }

            });


        } else {
            dhxWins.window("Windows").show();
        }

    }

    /* 
        Handle create master item
    */

    var createMasterItemlayout;
    var createMasterDataForm;

    function createMasterFile() {
        
        let setting_process_basic = <?php echo json_encode($setting_process_basic); ?>;
        // console.log('setting_process_basic: ' + JSON.stringify(setting_process_basic) );
        var process_code_string = '';
        for (var i = 0; i < setting_process_basic.length; i++) {
            var process_code = setting_process_basic[i]['process_code'];
            
            process_code_string += (i==0) ? process_code : ('-' + process_code);
            
        }

        // layout
        createMasterItemlayout = new dhtmlXLayoutObject({
            parent: document.body,
            pattern: "1C",
            offsets: {
                top: 63,
                left: 10,
                right: 10,
                bottom: 10
            },
            cells: [{
                id: "a",
                header: true,
                text: "WOVEN CREATE FORM"
            }]
        });

        // structure
        formStructMaster = [{
                type: "settings",
                position: "label-left",
                width: 1200,
                labelWidth: "auto",
                inputWidth: "auto",
                offsetLeft: 30
            },
            {   type: "fieldset", width: "auto", blockOffset: 0, label: "Detail", offsetLeft: 50, offsetTop: 20,
                list: [
                    {   type: "settings", position: "label-left", labelWidth: 150, inputWidth: 150, labelAlign: "left" },
                    {   type: "select", id: "machine_type", name: "machine_type", label: "Machine", style: "color:blue; ",
                        options: [
                            { value: "wv", text: "WV", selected: true },
                            { value: "cw", text: "CW" },
                            { value: "lb", text: "LB" }
                        ]
                    },
                    {   type: "input", id: "internal_item", name: "internal_item", label: "Item:", icon: "icon-input", required: true, validate: "NotEmpty" },
                    {   type: "input", id: "length_btp", name: "length_btp", label: "Length BTP (mm):", icon: "icon-input", required: true, validate: "ValidNumeric" },
                    {   type: "input", id: "width_btp", name: "width_btp", label: "Width BTP (mm):", icon: "icon-input", required: true, validate: "ValidNumeric" },
                    {   type: "input", id: "rbo", name: "rbo", label: "RBO:", icon: "icon-input", validate: "" },
                    {   type: "input", id: "wire_number", name: "wire_number", label: "S??? D??y:", icon: "icon-input", required: true, validate: "ValidInteger" },
                    {   type: "input", id: "vertical_thread_type", name: "vertical_thread_type", label: "Lo???i Ch??? D???c:", icon: "icon-input", required: false, validate: "" },
                    {   type: "input", id: "folding_cut_type", name: "folding_cut_type", label: "Lo???i C???t G???p:", icon: "icon-input", required: false, validate: "" },
                    {   type: "input", id: "pattern", name: "pattern", label: "Pattern:", icon: "icon-input" },
                    {   type: "input", id: "gear_density", name: "gear_density", label: "B??nh R??ng:", icon: "icon-input", required: false, validate: "" },
                    {   type: "input", id: "length_tp", name: "length_tp", label: "Length TP (mm):", icon: "icon-input", required: true, validate: "ValidNumeric" },
                    {   type: "input", id: "width_tp", name: "width_tp", label: "Width TP (mm):", icon: "icon-input", required: true, validate: "ValidNumeric" },
                    {   type: "select", id: "cbs",  name: "cbs", label: "Size:", style: "color:blue; ", 
                        options: [
                            {value: 0, text: "Kh??ng C?? Size", selected: true }, 
                            { value: 1, text: "C?? Size" }
                        ] 
                    },
                    {   type: "input", id: "scrap", name: "scrap", label: "% Scrap:", icon: "icon-input", required: true, validate: "ValidInteger", value: 15 },

                    {   type: "newcolumn", "offset": 20 },
                    {   type: "input", id: "cut_type", name: "cut_type", label: "Lo???i C???t:", icon: "icon-input", className: "" },
                    {   type: "input", id: "sawing_method", name: "sawing_method", label: "PP X???:", labelAlign: "left", icon: "icon-input" },
                    {   type: "input", id: "cw_specification", name: "cw_specification", label: "TSKT CW:", icon: "icon-input", className: "", required: false, validate: "ValidInteger" },
                    {   type: "input", id: "heat_weaving", name: "heat_weaving", label: "Nhi???t D???t:", icon: "icon-input", className: "" },
                    {   type: "input", id: "meter_number_per_machine", name: "meter_number_per_machine", label: "M??t/M??y:", icon: "icon-input" },
                    {   type: "input", id: "water_glue_rate", name: "water_glue_rate", label: "T??? L??? H???/N?????c:", icon: "icon-input", className: "" },
                    // { type: "input", id: "so_cai_min", name: "so_cai_min", label: "S??? C??i Min (prepress):", icon: "icon-input", className: "", required: false, validate: "ValidInteger" },
                    // { type: "input", id: "taffeta_satin", name: "taffeta_satin", label: "Taffeta/Satin:", icon: "icon-input", className: "" },
                    {   type: "input", id: "textile_size_number", name: "textile_size_number", label: "S??? Kh??? (prepress):", labelAlign: "left", icon: "icon-input", required: true, validate: "ValidInteger" },
                    // { type: "input", id: "new_wire_number", name: "new_wire_number", label: "S??? D??y M???i:", icon: "icon-input", className: "", required: false, validate: "ValidInteger" },
                    {   type: "select", id: "new_wire_number", name: "new_wire_number", label: "S??? D??y M???i (prepress):", style: "color:blue; ", required: true,
                        options: [
                            {   value: 1000, text: "Kh??ng Batcing (1000)", selected: true },
                            {   value: 1001, text: "FOD - Ch??a Batching (1001)" },
                            
                            <?php
                            $length_check = 60;
                            for ($i = 1; $i <= $length_check; $i++) {
                                if ($i == $length_check) {
                                    echo '{ value: ' . $i . ', text: "' . $i . '" }';
                                } else {
                                    echo '{ value: ' . $i . ', text: "' . $i . '" },';
                                }
                            }
                            ?>
                        ]
                    },
                    // { type: "input", id: "pick_number_total", name: "pick_number_total", label: "T???ng S??? Pick:", icon: "icon-input", className: "", required: true, validate: "ValidInteger" },
                    {   type: "input", id: "glue_1", name: "glue_1", label: "Keo 1:", icon: "icon-input", className: "" },
                    {   type: "input", id: "glue_2", name: "glue_2", label: "Keo 2:", icon: "icon-input", className: "" },
                    {   type: "input", id: "remark_1", name: "remark_1", label: "Remark 1:", icon: "icon-input", className: "" },
                    {   type: "input", id: "remark_2", name: "remark_2", label: "Remark 2:", icon: "icon-input", className: "" },
                    {   type: "input", id: "remark_3", name: "remark_3", label: "Remark 3:", icon: "icon-input", className: "" },

                    {   type: "newcolumn",  "offset": 0 },

                    {   type: "fieldset", width: "auto", blockOffset: 0, label: "Process (C???p nh???t l???i trong Master Data)", style: "color:red; ", offsetLeft: 20, offsetTop: 0,
                        list: [
                            {   type: "settings", position: "label-left", labelWidth: 100, inputWidth: 250, labelAlign: "left" },
                            {   type: "input", id: "process", name: "process", label: "Process:", value: process_code_string },

                        ]
                    }

                ]
            },

            {   type: "fieldset", width: "auto", blockOffset: 0, label: "Material", offsetLeft: 50, offsetTop: 0,
                list: [
                    {   type: "settings", position: "label-left", labelWidth: 150, inputWidth: 150, labelAlign: "left" },
                    {   type: "input", id: "supply_code_1", name: "supply_code_1", label: "V???t T?? 1:", icon: "icon-input" },
                    {   type: "input", id: "supply_code_2", name: "supply_code_2", label: "V???t T?? 2:", icon: "icon-input" },
                    {   type: "input", id: "supply_code_3", name: "supply_code_3", label: "V???t T?? 3:", icon: "icon-input" },
                    {   type: "input", id: "supply_code_4", name: "supply_code_4", label: "V???t T?? 4:", icon: "icon-input" },
                    {   type: "input", id: "supply_code_5", name: "supply_code_5", label: "V???t T?? 5:", icon: "icon-input" },
                    {   type: "input", id: "supply_code_6", name: "supply_code_6", label: "V???t T?? 6:", icon: "icon-input" },
                    {   type: "input", id: "supply_code_7", name: "supply_code_7", label: "V???t T?? 7:", icon: "icon-input" },
                    {   type: "input", id: "supply_code_8", name: "supply_code_8", label: "V???t T?? 8:", icon: "icon-input" },

                    {   type: "newcolumn", "offset": 20 },

                    {   type: "input", id: "density_1", name: "density_1", label: "M???t ????? 1:", icon: "icon-input" },
                    {   type: "input", id: "density_2", name: "density_2", label: "M???t ????? 2:", icon: "icon-input" },
                    {
                        type: "input",
                        id: "density_3",
                        name: "density_3",
                        label: "M???t ????? 3:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "density_4",
                        name: "density_4",
                        label: "M???t ????? 4:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "density_5",
                        name: "density_5",
                        label: "M???t ????? 5:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "density_6",
                        name: "density_6",
                        label: "M???t ????? 6:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "density_7",
                        name: "density_7",
                        label: "M???t ????? 7:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "density_8",
                        name: "density_8",
                        label: "M???t ????? 8:",
                        icon: "icon-input"
                    },

                    {
                        type: "newcolumn",
                        "offset": 20
                    },

                    {
                        type: "input",
                        id: "pick_number_1",
                        name: "pick_number_1",
                        label: "S??? Pick 1:",
                        icon: "icon-input"
                    },
                    {
                        type: "input",
                        id: "pick_number_2",
                        name: "pick_number_2",
                        label: "S??? Pick 2:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "pick_number_3",
                        name: "pick_number_3",
                        label: "S??? Pick 3:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "pick_number_4",
                        name: "pick_number_4",
                        label: "S??? Pick 4:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "pick_number_5",
                        name: "pick_number_5",
                        label: "S??? Pick 5:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "pick_number_6",
                        name: "pick_number_6",
                        label: "S??? Pick 6:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "pick_number_7",
                        name: "pick_number_7",
                        label: "S??? Pick 7:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    },
                    {
                        type: "input",
                        id: "pick_number_8",
                        name: "pick_number_8",
                        label: "S??? Pick 8:",
                        icon: "icon-input",
                        validate: "ValidInteger"
                    }
                ]
            },

            {
                type: "button",
                id: "createMasterItem",
                name: "createMasterItem",
                value: "T???o Item m???i",
                position: "label-center",
                width: 210,
                offsetLeft: 390
            }
        ];

        // attach form
        createMasterDataForm = createMasterItemlayout.cells("a").attachForm(formStructMaster);

        // Validation live: 
        createMasterDataForm.enableLiveValidation(true);

        createMasterDataForm.attachEvent("onButtonClick", function(name) {
            if (name == 'createMasterItem') {
                createMasterDataForm.send("<?php echo base_url('woven/createMasterItem/'); ?>", "post", function(test, res) {
                    // parse json to object
                    var obj = JSON.parse(res);

                    if (obj.status == true) {
                        alert(obj.messagge);
                        location.href = '<?php echo base_url("woven/viewMasterFile/") ?>';
                    } else {
                        alert(obj.messagge);
                    }
                });

            }

        });





    }


    // Update Item th??m c??ng ??o???n Kiem 100% v??o tr?????c ????ng g??i
    function updateItemKiem100() {

        var conf = confirm("Th??m c??ng ??o???n Ki???m 100% (KH) cho c??c Item. S??? d???ng m???u c?? 1 c???t Item (ghi ????ng t??n c???t)");
        if (!conf ) return false;

        if (!dhxWins) {
            dhxWins = new dhtmlXWindows();
        }

        var id = "WindowsDetail";
        var w = 400;
        var h = 100;
        var x = Number(($(window).width() - 400) / 2);
        var y = Number(($(window).height() - 50) / 2);
        var Popup = dhxWins.createWindow(id, x, y, w, h);
        dhxWins.window(id).setText("Import Item Kiem 100%");
        Popup.attachHTMLString(
            '<div style="width:500%;margin:20px">' +
            '<form action="<?php echo base_url('woven/updateItemKiem100'); ?>" enctype="multipart/form-data" method="post" accept-charset="utf-8">' +
            '<input type="file" name="masterfile" id="masterfile" class="form-control filestyle" value="value" data-icon="false"  />' +
            '<input type="submit" name="importfile" value="Import" id="importfile-id" class="btn btn-block btn-primary"  />' +
            '</form>' +
            '</div>'
        );
    }

    function uploadThreadLength()
    {

        alert("Sheet (.xlsx) Import m???u g???m c?? 2 c???t: Item Code v?? Length Weft. Vui l??ng s??? d???ng ????ng ?????nh d???ng");
        
        if (!dhxWins) {
            dhxWins = new dhtmlXWindows();
        }

        var id = "WindowsDetail";
        var w = 400;
        var h = 100;
        var x = Number(($(window).width() - 400) / 2);
        var y = Number(($(window).height() - 50) / 2);
        var Popup = dhxWins.createWindow(id, x, y, w, h);
        dhxWins.window(id).setText("C???p nh???t chi???u d??i ch???");
        Popup.attachHTMLString(
            '<div style="width:500%;margin:20px">' +
            '<form action="<?php echo base_url('woven/uploadThreadLength'); ?>" enctype="multipart/form-data" method="post" accept-charset="utf-8">' +
            '<input type="file" name="masterfile" id="masterfile" class="form-control filestyle" value="value" data-icon="false"  />' +
            '<input type="submit" name="importfile" value="Import" id="importfile-id" class="btn btn-block btn-primary"  />' +
            '</form>' +
            '</div>'
        );
    }

</script>