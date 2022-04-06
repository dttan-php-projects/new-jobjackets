<?php

class Common_special_item_remarks extends CI_Model
{
    protected $_table = "common_special_item_remarks";
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
        $this->db->order_by('updated_date', 'DESC' );
        return $this->db->get($this->_table)->result_array();
    }

    //get data. 
    public function readSingle($internal_item) {
        // select where query
        $this->db->where('internal_item', $internal_item);
        return $this->db->get($this->_table)->result_array();
    }

    //get data. 
     public function readItem($array) {
        // select where query
        $this->db->where($array);
        $this->db->order_by('updated_date', 'DESC' );
        return $this->db->get($this->_table)->row_array();
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
    function delete($production_line, $internal_item ) {
        $array = array('production_line' => $production_line, 'internal_item' => $internal_item );
        $this->db->where($array);
        $res = $this->db->delete($this->_table);
        if(!$res) {
            return $this->db->error();
        } else {
            return TRUE;
        }
    }

    //check exist
    public function checkItem($internal_item ) {
        $this->db->where('internal_item', $internal_item );
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }

    //check exist
    public function isAlreadyExist($where ) {
        $this->db->where($where );
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
