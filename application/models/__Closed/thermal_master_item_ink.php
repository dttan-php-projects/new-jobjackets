<?php

class Thermal_master_item_ink extends CI_Model
{
    protected $_table = "thermal_master_item_ink";
    private $_insertBatch;
    
    // constructor with $db as database connection
    public function __construct() 
    {
        parent::__construct();
    }

    public function setInsertBatch($insertBatch) {
        $this->_insertBatch = $insertBatch;
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

    public function readCol($col) 
    {
        // select all query
        $this->db->select($col);
        $this->db->distinct();
        return $this->db->get($this->_table)->result_array();
    }

    //get data. 
    public function readItem($internal_item) 
    {
        // select where query
        $this->db->where('internal_item', $internal_item);
        return $this->db->get($this->_table)->row_array();
    }

    //get data. 
    public function readSingle($array) 
    {
        // select where query
        $this->db->where($array);
        return $this->db->get($this->_table)->row_array();
    }
    
    // save data
    public function insertBatch() 
    {
        $data = $this->_insertBatch;
        $this->db->insert_batch($this->_table, $data);
        return ($this->db->affected_rows() != 1) ? $this->db->error() : TRUE;
    }
        
    // create
    public function insert($data_insert) 
    {
        $this->db->insert($this->_table,$data_insert);
        return ($this->db->affected_rows() != 1) ? FALSE : TRUE;
    }

    // update  
    public function update($data_update, $where_arr) 
    {
        $this->db->where($where_arr);
        $res = $this->db->update($this->_table, $data_update);
        return ($res != 1) ? $this->db->error() : TRUE;
    }

    // delete data
    function delete($internal_item, $ink_code) 
    {
        $this->db->where(array('internal_item' => $internal_item, 'ink_code' => $ink_code) );
        $res = $this->db->delete($this->_table);
        return (!$res) ? $this->db->error() : TRUE;
    }

    //check exist
    public function isAlreadyExist($internal_item, $ink_code) 
    {
        $this->db->where(array('internal_item' => $internal_item, 'ink_code' => $ink_code) );
        $query=$this->thermal->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
