<?php

class Thermal_save_po_material_ink extends CI_Model
{
    protected $_table = "thermal_save_po_material_ink";
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

    //get data. 
    public function readSupply($array) 
    {
        // select where query
        $this->db->where($array);
        $this->db->order_by('order', 'ASC' );
        return $this->db->get($this->_table)->result_array();
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
    function delete($where) 
    {
        $this->db->where($where );
        $res = $this->db->delete($this->_table);
        return (!$res) ? $this->db->error() : TRUE;
    }

    //check exist
    public function isAlreadyExist($where) 
    {
        $this->db->where($where );
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
