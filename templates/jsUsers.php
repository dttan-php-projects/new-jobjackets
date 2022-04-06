<?php include_once ("jsCommon.php"); ?>
<script>
    
    // onload ----------------------------------------------------------------------------------------------------------------------------------
        function doUsersOnLoad() 
        {
            $(document).ready(function(){

                initUsersMenu();

            });
            
        }

    // init ----------------------------------------------------------------------------------------------------------------------------------

        function initUsersMenu() 
        {
            mainMenu = new dhtmlXMenuObject({
                parent: "mainMenu",
                iconset: "awesome",
                json: "<?php echo base_url('assets/xml/users_menu.xml'); ?>",
                top_text: "<?php echo "<img style='width:60px;' src='". base_url('assets/media/images/Logo.PNG') ."'/>&nbsp;&nbsp;&nbsp; WOVEN PRODUCTION PLAN PROGRAM "; ?>"
            });
            mainMenu.setAlign("right");

            mainMenu.attachEvent("onClick", function(id){
                if(id !== "home") {
                    if (id == 'create_user' ) {
                        if (Number(getCookie('plan_account_type') == 3)) {
                            initCreateUserWindow('');
                        } else {
                            alert('Bạn không có quyền truy cập chức năng này');
                        }
                        
                    } else if (id == 'view_user' ) {
                        if (Number(getCookie('plan_account_type') == 3)) {
                            location.href = "<?php echo base_url('users/recent') ?>";
                        } else {
                            alert('Bạn không có quyền truy cập chức năng này');
                        }

                    } else if (id == 'come_back_planning' ) {
                        location.href = "<?php echo base_url(get_cookie('plan_department')); ?>";
                    }

                } else {
                    if (!getCookie('plan_account_type') ) {
                            alert('Bạn không có quyền truy cập chức năng này');
                    } else {
                        location.href = "<?php echo base_url('users/recent/'); ?>" ;
                    }
                    
                }
            });
        }

        // users: main layout
        function initMainUserLayout() 
        {
            mainUserLayout = new dhtmlXLayoutObject({
                parent: document.body,
                pattern: "1C",
                offsets: {
                    top: 35,
                    left:10,
                    right:10,
                    bottom: 10
                },
                cells: [
                    {id: "a", header: true, text: "DANH SÁCH NGƯỜI DÙNG"}
                ]
            });
        }

        // users: main grid
        function initMainUserGrid() 
        {
            mainUserGrid = mainUserLayout.cells("a").attachGrid();
            mainUserGrid.setImagePath("<?php echo base_url('assets/Suite_v52/skins/skyblue/imgs/') ?>" );
            mainUserGrid.setHeader("No#, Username, Department, Account Type, Edit, Delete"); //sets the headers of columns
            mainUserGrid.setColumnIds("no,username,department,account_type,edit,delete"); //sets the columns' ids
            mainUserGrid.setInitWidths("50,*,200,200,150,150"); //sets the initial widths of columns
            mainUserGrid.setColAlign("center,center,center,center,center,center"); //sets the alignment of columns
            mainUserGrid.setColTypes("ed,ed,ed,ed,ed,ed"); //sets the types of columns
            mainUserGrid.setColSorting("str,str,str,str,str,str"); //sets the sorting types of columns
            mainUserGrid.enableSmartRendering(true);

            // mainUserGrid.setColumnColor(",#d5f1ff");
            mainUserGrid.setStyle("font-weight:bold; font-size:13px;text-align:center;color:#990000;","font-size:12px;", "", "font-weight:bold;color:#0000ff;font-size:14px;");

            //Lưu ý: filter vượt quá 26 bị lỗi
            mainUserGrid.attachHeader(",#text_filter,#text_filter,#text_filter");
            mainUserGrid.enableMultiselect(true);
            mainUserGrid.init(); //dataProcessor

            loadMainUserGrid();

            
        }

        // users: create a user window
        var dhxWins;
        function initCreateUserWindow(usernameEdit) 
        {
            if(!dhxWins){ dhxWins= new dhtmlXWindows(); }

            var id = "createUserWindows";
            var w = 400;
            var h = 300;
            var x = Number(($(window).width())/3);
            var y = Number( ($(window).height()) -854 );

            var create = dhxWins.createWindow(id, x, y, w, h);
            dhxWins.window(id).setText("Create User");
            
            // get user and check
            var username = getCookie('plan_loginUser');
            if (!username ) {
                alert('Vui lòng kiểm tra lại thông tin đăng nhập');
                location.reload();
                return false;
            }

            var data = {username : username };
            var jsonObjects = JSON.stringify(data);

            var url = "<?php echo base_url('users/getInfo/?usernameEdit='); ?>"+usernameEdit;

            //excute with ajax function 
            $.ajax({
                type: "POST",
                data: { data: jsonObjects },
                url: url,
                dataType: 'json',
                beforeSend: function(x) { 
                    if (x && x.overrideMimeType) { x.overrideMimeType("application/j-son;charset=UTF-8"); }
                },
                success: function(results) {
                    if (results.status == false ) {
                        alert(results.message);
                        window.location = '<?php base_url('woven'); ?>';
                        return false;
                    } else {
                        var userStructure = results.userStructure;

                        var userInfoEdit = results.userInfoEdit;
                        var departmentEdit = userInfoEdit['department'];
                        var permissionEdit = userInfoEdit['permission'];

                        createUserForm = create.attachForm();
                        createUserForm.loadStruct(userStructure);
                        
                        // Trường hợp edit
                        if (usernameEdit ) {
                            createUserForm.setItemValue('username', usernameEdit );
                            createUserForm.setItemValue('department', departmentEdit );
                            createUserForm.setItemValue('permission', permissionEdit );
                        }

                        createUserForm.attachEvent("onButtonClick", function(name){

                            if (name == 'createUser' ) {
                                var username = createUserForm.getItemValue('username');
                                var password = createUserForm.getItemValue('password');
                                var department = createUserForm.getItemValue('department');
                                var permission = createUserForm.getItemValue('permission');

                                if (!username ) {
                                    alert('Username không được để trống ');
                                    return false;
                                }

                                if (!password ) {
                                    alert('Password không được để trống ');
                                    return false;
                                }

                                if (!department ) {
                                    alert('Department không được để trống ');
                                    return false;
                                }

                                if (!permission ) {
                                    alert('Permission không được để trống ');
                                    return false;
                                }

                                var data = { username : username, password : password, department : department, permission : permission };
                                var jsonObjects = JSON.stringify(data);
                                var url = "<?php echo base_url('users/create'); ?>";
                                
                                $.ajax({
                                    type: "POST",
                                    data: { data: jsonObjects },
                                    url: url,
                                    dataType: 'json',
                                    beforeSend: function(x) { 
                                        if (x && x.overrideMimeType) { x.overrideMimeType("application/j-son;charset=UTF-8"); }
                                    },
                                    success: function(results) {
                                        if (results.status == false ) {
                                            alert(results.message);
                                            window.location = '<?php echo base_url('users/recent'); ?>';
                                            return false;
                                        } else { // true
                                            alert(results.message);
                                            window.location = '<?php echo base_url('users/recent'); ?>';

                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        // alert(error);alert(xhr.responseText);
                                        alert('Lưu dữ liệu không thành công: Vui lòng liên hệ quản trị hệ thống. '+xhr.responseText);
                                        return false;
                                    }
                                });   
		
                            }
                        }); 

                    }
                },
                error: function(xhr, status, error) {
                    // alert(error);alert(xhr.responseText);
                    alert('Lưu dữ liệu không thành công: Vui lòng liên hệ quản trị hệ thống. '+xhr.responseText);
                    return false;
                }
            });   

        }
        // users: Edit layout
        function initEditUserLayout() 
        {
            editUserLayout = new dhtmlXLayoutObject({
                parent: document.body,
                pattern: "1C",
                offsets: {
                    top: 63,
                    left:10,
                    right:10,
                    bottom: 10
                },
                cells: [
                    {id: "a", header: true, text: "EDIT USER"}
                ]
            });
        }

        // users:  load main users
        function loadMainUserGrid() 
        {
            var url = "<?php echo base_url('users/readDepartmentUser'); ?>";

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
                    mainUserGrid.parse(data,"json");
                },
                error: function(xhr, status, error) {
                    // alert(error);alert(xhr.responseText);
                    alert('Lưu dữ liệu không thành công: Vui lòng liên hệ quản trị hệ thống. '+xhr.responseText);
                    return false;
                }
            });

        }
        // users: delete a user
        function deleteUser(username) 
        {
            var message = "Bạn có chắc chắn muốn xóa User " + username + " ?";
            var a = confirm(message);
            if (!a ) { return false; } else { }
        }
        
        // users: logout
        function logout() 
        {
            var jsonObjects = {};
            var url = "<?php echo base_url('users/logout/'); ?>";

            //excute with ajax function 
            $.ajax({
                type: "POST",
                data: { data: JSON.stringify(jsonObjects) },
                url: url,
                dataType: 'json',
                beforeSend: function(x) { 
                    if (x && x.overrideMimeType) { x.overrideMimeType("application/j-son;charset=UTF-8"); }
                },
                success: function(results) {
                    
                    if (results.status == true ) {
                        // alert(results.message);
                        location.href = "<?php echo base_url('users'); ?>";

                    }
                    
                },
                error: function(xhr, status, error) {
                    // alert(error);alert(xhr.responseText);
                    alert('Lưu dữ liệu không thành công: Vui lòng liên hệ quản trị hệ thống. '+xhr.responseText);
                    return false;
                }
            });
        }
        // users: change profile
        function changeProfile(username) 
        {
            // var url_suffix = 'so_line='+so_line+'&item='+item;
            location.href = '<?php echo base_url('users/edit/'); ?>' + username;
        }

        
        

        



</script>