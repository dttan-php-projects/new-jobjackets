<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
header('Content-Type: text/html; charset=utf-8');
class Common_users extends CI_Model
{
    protected $_table = "common_users";

    // constructor with $db as database connection
    public function __construct()
    {
        parent::__construct();
    }

    public function countAll()
    {
        return $this->db->count_all($this->_table);
    }

    // read all data
    public function read()
    {
        // select all query
        $this->db->select('*');
        return $this->db->get($this->_table)->result_array();
    }

    //get data 
    public function readItem($username)
    {
        // select where query
        $this->db->where('username', $username);
        $this->db->limit(1);
        return $this->db->get($this->_table)->row_array();
    }

    public function readDepartmentUser($username) {
        
        // select where query
        $results = '';
        
        $department = $this->getDepartment($username);
        $account = $this->getAccount($username);
        
        if ($account == 1 || $account == 2 ) {
            $this->db->where('username', $username);
            $results = $this->db->get($this->_table)->result_array();
        } else if ($account == 3 ) { // user Department admin
            $this->db->where('department', $department);
            $results = $this->db->get($this->_table)->result_array();
        } else if ($account == 9 ) { // supper admin
            $results = $this->db->get($this->_table)->result_array();
        }

        return $results;
        
    }

    public function getAccount($username) {
        // select where query
        $this->db->select('account_type');
        $this->db->where('username', $username);
        $this->db->limit(1);
        $item = $this->db->get($this->_table)->row_array();
        return $item['account_type'];
    }

    public function getDepartment($username) {
        // select where query
        $this->db->select('department');
        $this->db->where('username', $username);
        $this->db->limit(1);
        $item = $this->db->get($this->_table)->row_array();
        return $item['department'];
    }


    public function checkLogin($username, $password)
    {
        $array = array( 'username' => $username, 'password' => $password );
        $this->db->where($array);
        $query = $this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }

    public function login($username, $password)
    {
        $array = array( 'username' => $username, 'password' => $password );
        $this->db->where($array);
        $this->db->limit(1);
        return $this->db->get($this->_table)->row_array();
    }

    // create
    public function create($data_insert)
    {
        $this->db->insert($this->_table, $data_insert);
        return ($this->db->affected_rows() != 1) ? FALSE : TRUE;
    }

    // update  
    public function update($data_update, $username)
    {
        $this->db->where('username', $username);
        $res = $this->db->update($this->_table, $data_update);
        return (!$res) ? $this->db->error() : TRUE;
    }

    // delete data
    public function delete($username)
    {
        $this->db->where('username', $username);
        $res = $this->db->delete($this->_table);
        return (!$res) ? $this->db->error() : TRUE;
    }

    public function isAlreadyExist($username)
    {
        $this->db->where('username', $username);
        $query = $this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }
}
