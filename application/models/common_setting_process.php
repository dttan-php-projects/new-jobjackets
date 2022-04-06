<?php

class Common_setting_process extends CI_Model
{
    protected $_table = "common_setting_process";
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
    public function read($col=null) {
        // select all query
        if ($col != null ) $this->db->order_by($col, 'ASC' );
        return $this->db->get($this->_table)->result_array();
    }

    //get data. 
    public function readSingle($array) {
        // select where query
        $this->db->where($array);
        return $this->db->get($this->_table)->row_array();
    }

    //get data. 
    public function readOptions($where, $col=null ) 
    {
        // select where query
        $this->db->where($where);
        if ($col != null ) $this->db->order_by($col, 'ASC' );
        return $this->db->get($this->_table)->result_array();
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
    function delete($production_line, $process_code ) {
        $array = array('production_line' => $production_line, 'process_code' => $process_code );
        $this->db->where($array);
        $res = $this->db->delete($this->_table);
        return ($res != 1) ? $this->db->error() : TRUE;
    }

    //check exist
    public function isAlreadyExist($production_line, $process_code ) {
        $array = array('production_line' => $production_line, 'process_code' => $process_code );
        $this->db->where($array );
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
