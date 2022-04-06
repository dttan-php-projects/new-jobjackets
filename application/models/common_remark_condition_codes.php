<?php

class Common_remark_condition_codes extends CI_Model
{
    protected $_table = "common_remark_condition_codes";
    private $_insertBatch;

    // constructor with $db as database connection
    public function __construct() {
        parent::__construct();
    }

    public function setInsertBatch($insertBatch) {
        $this->_insertBatch = $insertBatch;
    }

    public function countAll() {
        return $this->db->count_all($this->_table);
    }

    // read all data
    public function read() {
        // select all query
        return $this->db->get($this->_table)->result_array();
    }

    //get data. 
    public function readSingle($array) {
        // select where query
        $this->db->where($array);
        return $this->db->get($this->_table)->row_array();
    }

    public function getLastCode() {
        $this->db->order_by('condition_code', 'DESC' );
        $this->db->limit(1);
        $results = $this->db->get($this->_table)->row_array();
        
        return !empty($results) ? $results['condition_code']: '';
    }
    
    // save data
    public function insertBatch() {
        $data = $this->_insertBatch;
        $this->db->insert_batch($this->_table, $data);
        return ($this->db->affected_rows() != 1) ? $this->db->error() : TRUE;
    }
        
    // create
    public function insert($data_insert){
        $this->db->insert($this->_table,$data_insert);
        return ($this->db->affected_rows() != 1) ? FALSE : TRUE;
    }

    // update  
    public function update($data_update, $where_arr) {
        $this->db->where($where_arr);
        $res = $this->db->update($this->_table, $data_update);
        return ($res != 1) ? $this->db->error() : TRUE;
    }

    // delete data
    function delete($condition_code ) {
        $this->db->where('condition_code', $condition_code);
        $this->db->delete($this->_table);
    }

    //check exist
    public function isAlreadyExist($condition_code ) {
        $this->db->where('condition_code', $condition_code);
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
