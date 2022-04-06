<?php

class Htl_prepress_ink_usage extends CI_Model
{
    protected $_table = "htl_prepress_ink_usage";
    private $_insertBatch;

    // constructor with $db as database connection
    public function __construct() 
    {
        parent::__construct();
    }
    
    public function setInsertBatch($insertBatch) 
    {
        $this->_insertBatch = $insertBatch;
    }

    public function countAll() 
    {
        return $this->db->count_all($this->_table);
    }

    // read all data
    public function read($col=null ) 
    {
        // select all query
        if ($col != null ) $this->db->order_by($col, 'ASC' );
        return $this->db->get($this->_table)->result_array();
    }

    //get data. 
    public function readItem($array, $col=null) 
    {
        // select where query
        $this->db->where($array);
        if ($col != null ) $this->db->order_by($col, 'ASC' );
        $this->db->limit(1);
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

    public function readCol($col) 
    {
        // select all query
        $this->db->select($col);
        $this->db->distinct();
        $this->db->order_by($col, 'ASC' );
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
    public function update($data_update, $where) 
    {
        $this->db->where($where);
        $res = $this->db->update($this->_table, $data_update);
        return ($res != 1) ? $this->db->error() : TRUE;
    }

    // delete data
    function delete($where) 
    {
        $this->db->where($where);
        $res = $this->db->delete($this->_table);
        return ($res != 1) ? $this->db->error() : TRUE;
    }

    //check exist
    public function isAlreadyExist($where) 
    {
        $this->db->where($where);
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
