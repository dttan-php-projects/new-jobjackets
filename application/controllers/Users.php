<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */

	protected $staticSalt = 'Toidaquenthatroi';

	 // Hàm khởi tạo
	function __construct()
	{
		// Gọi đến hàm khởi tạo của cha
		parent::__construct();
		$this->load->helper(array('url', 'form'));
		$this->load->library('form_validation');
		$this->load->model('common_users');
		$this->load->model('common_user_departments', 'departments');
		$this->load->model('common_user_permissions', 'permissions');
		$this->load->model('autoload');

		// date_default_timezone_set("Asia/Ho_Chi_Minh"); 

		$this->_data['account_type'] = !empty($this->getCookie('plan_account_type')) ? $this->getCookie('plan_account_type') : '';
		//get automail updated date
		$automail_updated = $this->autoload->getAutomailUpdated();
		if (!empty($automail_updated['CREATEDDATE']) ) {
			$this->_data['automail_updated'] = $automail_updated['CREATEDDATE'];
		} else {
			$this->_data['automail_updated'] = 'loading...';
		}
		
	}

	public function setCookie($name,$value,$days=1) 
	{
        setcookie($name, $value, time() + ($days * 60 * 60 * 24 ), "/"); //1 ngay = 86400 +> 1s = 1/86400 = 
    }

	public function resetCookie($name,$value="") 
	{
        setcookie($name,$value,0, "/");
	}
	

	//Encode
	public function myEncode($value)
	{
		return base64_encode($this->staticSalt . $value);
	}

	//Decode
	public function myDecode($value)
	{
		return base64_decode($this->staticSalt . $value);
	}


	public function index()
	{
		$department = isset($_COOKIE['plan_department']) ? $_COOKIE['plan_department'] : '';
		$title = $department . ' plan program';
		$this->_data['title'] = ucwords($title);

		if ($this->checkLogin() ) {
			if (!empty($this->getCookie('plan_department')) ) {

				$this->load->view($this->getCookie('plan_department') . '/index', $this->_data );
			} else {
				$this->load->view('users/index' );
			}
			
		} else {
			$this->load->view('users/index' );
		}

	}

	public function checkLogin()
	{
		return isset($_COOKIE['plan_loginUser']) ? true : false;
	}

	public function getCookie($c)
	{
		return isset($_COOKIE[$c]) ? $_COOKIE[$c] : '';
	}


	public function login()
	{
		$username = null !== $this->input->post('username') ? trim($this->input->post('username')) : '';
		$password = null !== $this->input->post('password') ? trim($this->input->post('password')) : '';
		$check = null !== $this->input->post('check') ? trim($this->input->post('check')) : '';  // true = checked

		if (!empty($username) && !empty($password)) {
			
			$password = $this->myEncode($password);
			if ($this->common_users->checkLogin($username, $password)) {
				$user = $this->common_users->login($username, $password);

				$department = strtolower($user['department']);
				
				
				$this->setCookie('plan_loginUser', $username, 30 );
				// $this->setCookie('plan_order_type', $user['account_type'], 30 ); @@@@@@@@@@@@@
				$this->setCookie('plan_account_type', $user['account_type'], 30 ); 
				$this->setCookie('plan_department', strtolower($user['department']), 30 );
				$this->setCookie('permission', strtoupper($user['permission']), 30 );
				$this->setCookie('plan_print_form', strtolower($user['form_type']), 30 );

				$this->_data['results'] = array(
					'status' => true,
					'message' => 'Login success.',
					'data' => $user
				);
				// echo json_encode($this->_data['results'],JSON_UNESCAPED_UNICODE); exit();

			} else {
				$this->_data['results'] = array(
					'status' => false,
					'message' => 'Username or Password incorect',
					'data' => ''
				);
				// echo json_encode($this->_data['results'],JSON_UNESCAPED_UNICODE); exit();
			}

		} else {
			$this->_data['results'] = array(
				'status' => false,
				'message' => 'Empty Username or Password.',
				'data' => ''
			);
		}
		$this->_data['results'] = json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);
		$this->load->view('users/login', $this->_data );

	}

	public function logout() 
	{
		
		$this->setCookie('plan_loginUser', '', 0 );
		$this->setCookie('plan_order_type', '', 0 );
		$this->setCookie('plan_account_type', '', 0 ); 
		$this->setCookie('plan_department', '', 0 );

		$results = array(
			'status' => true,
			'message' => 'Logout Success'
		);

		echo json_encode($results, JSON_UNESCAPED_UNICODE);

	}

	public function recent() 
	{
		
		$this->_data['title'] = 'User Management';
		$this->load->view('users/views', $this->_data);
	}

	public function readDepartmentUser() 
	{
		
		$this->_data['title'] = 'User Management';
		$username = $this->getCookie('plan_loginUser');
		$list = $this->common_users->readDepartmentUser($username);

		$this->_data['results'] = array();

		if (!empty($list) ) {
			
			$index = 0;
			foreach ($list as $item ) {
				$index++;

				$username = trim($item['username']);
				$department = strtoupper(trim($item['department']));

				$edit = '<span style="color:blue;font-weight:bold;font-size:12px;"><a href="./edit/'. $username .'" title="Edit" rel="follow, index" >Edit</a></span>';				
				$delete = '<span style="color:blue;font-weight:bold;font-size:12px;"><a href="./delete/'. $username .'" title="Delete" rel="follow, index" onclick="return deleteUser('."'$username'".');">Delete</a></span>';

				if ($item['account_type'] == 9 ) {
					$account_type = 'Supper Admin';
				} else if ($item['account_type'] == 3 ) {
					$account_type = 'Admin';
				} else if ($item['account_type'] == 2 ) {
					$account_type = 'Standard L2';
				} else if ($item['account_type'] == 1 ) {
					$account_type = 'Standard L1';
				}

				$this->_data['results'][] = [
					'id' => $index,
					'data' => [
						$index,
						$username, 
						$department, 
						$account_type, 
						$edit,
						$delete
					]
				];
			}
			
		}

		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE);

	}

	public function getInfo() 
	{
		// set post data
		$dataPost = isset($_POST["data"]) ? $_POST["data"] : '';
		$dataPost = json_decode($dataPost, true);

		$usernameEdit = null !== $this->input->get('usernameEdit') ? trim($this->input->get('usernameEdit')) : '';

		$permissionData = array();
		$departmentData = array();
		$userInfoEdit = '';

		if (empty($dataPost) ) {
			$this->_data['results'] = array(
				"status" => false,
				"message" => "Không nhận được dữ liệu POST!"
			);

		} else {
			// get username
			$username = trim($dataPost['username']);
			if (!$this->common_users->isAlreadyExist($username) ) {
				$this->_data['results'] = array(
					"status" => false,
					"message" => "Không tồn tại Username trong hệ thống"
				);
			} else {
				
				$userInfo = $this->common_users->readItem($username);
				if (!empty($usernameEdit) ) {
					$userInfoEdit = $this->common_users->readItem($usernameEdit);
				}
				

				if ($userInfo['account_type'] == 9 ) {
					$permissionData = $this->permissions->read();
					$departmentData = $this->departments->read();

				} else if ($userInfo['account_type'] == 3 ) {

					// $permission = $userInfo['permission'];
					$permission = substr( $userInfo['permission'],  0, 2 );
					$where = "permission like '$permission%' OR permission like 'ST%' OR permission like '%AD%' ";
					$permissionData = $this->permissions->readWhere($where);
					$departmentData[] = array('department' => $userInfo['department'] );

				}

				// set data
				$departmentList = '';
				$permissionList = '';
				foreach ($departmentData as $department_item ) {
					$department = $department_item['department'];
					$departmentList .= '{ value: "' . $department . '", text: "' . $department . '" },';
				}

				foreach ($permissionData as $permission_item ) {
					$permission = $permission_item['permission'];
					$permission_description = $permission_item['permission_description'];
					if (substr($permission,  0, 2 ) == 'AD' ) {
						$permission_label = 'Admin ' . substr( $permission,  strlen($permission)-2, 2 ) ;
					} else if (substr($permission,  0, 2 ) == 'ST' ) {
						$permission_label = 'Standard ' . substr( $permission,  strlen($permission)-2, 2 ) ;
					}
					
					$permissionList .= '{ value: "' . $permission . '", text: "' . $permission_description . '" },';

				}

				$userStructure = '[
					{ 	type: "settings", position: "label-left", labelWidth: 550, inputWidth: 550 },
					{	type: "fieldset", label: "Create New User", width: "auto", blockOffset: 0, offsetLeft: "10", offsetTop: "10",
						list: [
							{ type: "settings", position: "label-left", labelWidth: 120, inputWidth: 160, labelAlign: "left" },
							{ type: "input", id: "username", name: "username", label: "Username:", icon: "icon-input", className: "", validate: "NotEmpty", required: true },
							{ type: "password", id: "password", name: "password", label: "Password:", icon: "icon-input", className: "", validate: "NotEmpty", required: true },
							{ type: "select", id: "department", name: "department", label: "Department", style: "color:blue; ", validate: "NotEmpty", required: true, options: [
								{ value: "", text: "Select Department" },
								' . $departmentList . '
							]},
							{ type: "select", id: "permission", name: "permission", label: "Permission", style: "color:blue; ", validate: "NotEmpty", required: true, options: [
								{ value: "", text: "Select Permission" },
								' . $permissionList . '
							]}
							
						]
					},{type: "button", id: "createUser", name: "createUser", value: "Save", position: "label-center", width:100, offsetLeft:"250"}
				];';


				$this->_data['results'] = array(
					"status" => true,
					"message" => "Get Data success",
					"userInfoEdit" => $userInfoEdit,
					"userStructure" => $userStructure
				);
			}
		}
		
		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE); exit();

	}

	public function create() 
	{

		// init results var 
		$this->_data['results'] = array();

		$created_by = isset($_COOKIE['plan_loginUser']) ? strtolower($_COOKIE['plan_loginUser']) : '';
		$created = date('Y-m-d H:i:s');

		$dataPost = isset($_POST["data"]) ? $_POST["data"] : '';
		// $dataPost = '{"username":"ngan.nguyen", "password":"ngan.nguyen", "department":"woven", "permission":"ST01"}';
		$dataPost = json_decode($dataPost, true);
		
		// check empty post data
		if (empty($dataPost)) {
			$this->_data['results'] = array(
				"status" => false,
				"message" => "Không nhận được dữ liệu POST!"
			);

		} else {
			
			$username = isset($dataPost['username']) ? $dataPost['username'] : '';
			$password = isset($dataPost['password']) ? $dataPost['password'] : '';
			$department = isset($dataPost['department']) ? $dataPost['department'] : '';
			$permission = isset($dataPost['permission']) ? $dataPost['permission'] : '';

			// filter
			$username = filter_var(trim($username), FILTER_SANITIZE_STRING);
			$password = filter_var(trim($password), FILTER_SANITIZE_STRING);
			$department = filter_var(trim(strtoupper($department)), FILTER_SANITIZE_STRING);
			$permission = filter_var(trim($permission), FILTER_SANITIZE_STRING);

			// encode password
			$password = $this->myEncode($password);
			
			if ( substr($permission, 0,2 )  == 'ST' ) {
				if (substr($permission, strlen($permission)-2,2 )  == '01' ) {
					$account_type = 1;
				} else if (substr($permission, strlen($permission)-2,2 )  == '02' ) {
					$account_type = 2;
				}
			} else if ( substr($permission, 0,2 )  == 'AD' ) {
				$account_type = 3;
			} else if ( substr($permission, 0,2 )  == 'SU' ) {
				$account_type = 9;
			}

			// check exist
			if ($this->common_users->isAlreadyExist($username) ) {
				$editData = array(
					'password' => $password,
					'salt' => $this->staticSalt,
					'account_type' => $account_type,
					'department' => $department,
					'permission' => $permission,
					'created_by' => $created_by,
					'created' => $created
				);

				$res = $this->common_users->update($editData, $username);
				if ($res == FALSE ) {
					$this->_data['results'] = array(
						"status" => false,
						"message" => "Update The User Error"
					);
				} else {
					$this->_data['results'] = array(
						"status" => true,
						"message" => "Update The User Success"
					);
				}

			} else {

				$createData = array(
					'username' => $username,
					'password' => $password,
					'salt' => $this->staticSalt,
					'account_type' => $account_type,
					'department' => $department,
					'permission' => $permission,
					'created_by' => $created_by
				);

				$res = $this->common_users->create($createData);
				if ($res == FALSE ) {
					$this->_data['results'] = array(
						"status" => false,
						"message" => "Create New User Error"
					);
				} else {
					$this->_data['results'] = array(
						"status" => true,
						"message" => "Create New User Success"
					);
				}
			}

			

			// echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE); exit();


		}

		echo json_encode($this->_data['results'], JSON_UNESCAPED_UNICODE); exit();
		
	}

	public function edit($usernameEdit) 
	{

		$this->_data['title'] = 'Edit User';

		if (empty($usernameEdit) ) {
			$results = array(
				"status" => false,
				"message" => "Username is empty!"
			);
		} else {
			$results = array(
				"status" => true,
				"message" => "Edit!",
				"usernameEdit" => $usernameEdit
			);
		}

		$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
		$this->load->view('users/edit', $this->_data );

	}

	public function delete($username) 
	{

		$this->_data['title'] = 'Delete User';

		// $username = null !== $this->input->get('username') ? trim($this->input->get('username')) : '';
		// $confirm = null !== $this->input->get('confirm') ? trim($this->input->get('confirm')) : '';
		// $confirm = !empty($confirm) ? (int)$confirm : '';
		
		if (empty($username) ) {
			$results = array(
				"status" => false,
				"message" => "Username is empty!",
				"username" => $username
			);
		} else {

			$result = $this->common_users->delete($username);
			if ($result != TRUE ) {
				$results = array(
					"status" => false,
					"message" => "Delete User $username Error !",
					"username" => $username
				);
			} else {
				$results = array(
					"status" => true,
					"message" => "Delete User $username Success !",
					"username" => $username
				);
			}
			

		}

		$this->_data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
		$this->load->view('users/delete', $this->_data );
		
	}



}
