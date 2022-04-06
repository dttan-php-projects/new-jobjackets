<?php

class Woven_master_item_process_backup extends CI_Model
{
    protected $_table = "woven_master_item_process_backup";
    private $_updateBatch;
    private $_insertBatch;

    // constructor with $db as database connection
    public function __construct() {
        parent::__construct();
    }

    public function setInsertBatch($insertBatch) {
        $this->_insertBatch = $insertBatch;
    }

    public function setUpdateBatch($updateBatch) {
        $this->_updateBatch = $updateBatch;
    }

    public function countAll() {
        return $this->db->count_all($this->_table);
    }

    // read all data
    public function read() {
        // select all query
        $this->db->order_by('internal_item ASC','process_code ASC');
        return $this->db->get($this->_table)->result_array();
    }

    // read all data
    public function readLimit($start_num, $len) {
        // select all query
        $this->db->order_by('internal_item ASC','process_code ASC');
        $this->db->limit($len, $start_num );
        return $this->db->get($this->_table)->result_array();
    }

    //get data. 
    public function readSingle($array) {
        // select where query
        $this->db->where($array);
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
        return ($res != 1) ? FALSE : TRUE;
    }

    // delete data
    function delete($internal_item, $length_btp, $process_code ) {
        $array = array('internal_item' => $internal_item, 'length_btp' => $length_btp, 'process_code' => $process_code );
        $this->db->where($array);
        $this->db->delete($this->_table);
    }

    //check exist
    public function checkItem($internal_item ) {
        $this->db->where('internal_item', $internal_item );
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }

    //check exist
    public function checkMasterItem($array) {
        
        $this->db->where($array );
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }

    //check exist
    public function isAlreadyExist($internal_item, $length_btp, $process_code ) {
        $array = array('internal_item' => $internal_item, 'length_btp' => $length_btp, 'process_code' => $process_code );
        $this->db->where($array );
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
