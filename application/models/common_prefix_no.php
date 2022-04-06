<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
class Common_prefix_no extends CI_Model
{
    protected $_table = "common_prefix_no";
    // constructor with $db as database connection
    public function __construct() {
        parent::__construct();
    }
 
   public function countAll(){
        return $this->db->count_all($this->_table); 
    }

    // read all data
    public function read() {
        // select all query
        $this->db->select('*');
        return $this->db->get($this->_table)->result_array();
    }

    // read all data
    public function readSingle($production_line, $module) {
        // select all query
        $array = array('production_line' => $production_line, 'module' => $module);
        $this->db->where($array);
        return $this->db->get($this->_table)->row_array();
    }

    // read all data
    public function readOptions($where ) {
        // select all query
        $this->db->where($where);
        $this->db->order_by('module', 'asc');
        return $this->db->get($this->_table)->result_array();
    }

    // read all data
    public function readPrefix($prefix) {
        // select all query
        $this->db->where('prefix', $prefix);
        return $this->db->get($this->_table)->row_array();
    }

    // create
    public function create($data_insert) {
        $this->db->insert($this->_table,$data_insert);
        return ($this->db->affected_rows() != 1) ? FALSE : TRUE;
    }

    // update  
    public function update($data_update, $production_line, $module) {
        $array = array('production_line' => $production_line, 'module' => $module);
        $this->db->where($array);
        $res = $this->db->update($this->_table, $data_update);
        if(!$res) {
            $error = $this->db->error();
            return $error;
        } else {
            return 1;
        }
    }

    // delete data
    function delete($production_line, $module) {
        $array = array('production_line' => $production_line, 'module' => $module);
        $this->db->where($array);
        $res = $this->db->delete($this->_table);
        if(!$res) {
            $error = $this->db->error();
            return $error;
        } else {
            return 1;
        }
    }

    //check Exist
    public function checkPrefix($prefix) {
        $array = array('prefix' => $prefix);
        $this->db->where($array);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    //check Exist
    public function isAlreadyExist($production_line, $module) {
        $array = array('production_line' => $production_line, 'module' => $module);
        $this->db->where($array);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }


}