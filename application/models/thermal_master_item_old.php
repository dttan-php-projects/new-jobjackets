<?php

class Thermal_master_item_old extends CI_Model
{
    protected $_table = "master_bom";
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
        return $this->thermal138->count_all($this->_table);
    }

    // read all data
    public function read() 
    {
        // select all query
        return $this->thermal138->get($this->_table)->result_array();
    }

    //get data. 
    public function readItem($where, $col=null ) 
    {
        // select where query
        $this->thermal138->where($where);
        if ($col != null ) $this->thermal138->order_by($col, 'ASC' );
        $this->thermal138->limit(1);
        return $this->thermal138->get($this->_table)->row_array();
    }

    //get data. 
    public function readOptions($where, $col=null) 
    {
        // select where query
        $this->thermal138->where($where);
        if ($col != null ) $this->thermal138->order_by($col, 'ASC' );
        return $this->thermal138->get($this->_table)->result_array();
    }

    public function readCol($col) {
        // select all query
        $this->thermal138->select($col);
        $this->thermal138->distinct();
        $this->thermal138->order_by($col, 'ASC' );
        return $this->thermal138->get($this->_table)->result_array();
    }
    
    // save data
    public function insertBatch() 
    {
        $data = $this->_insertBatch;
        $this->thermal138->insert_batch($this->_table, $data);
        return ($this->thermal138->affected_rows() != 1) ? $this->thermal138->error() : TRUE;
    }
        
    // create
    public function insert($data_insert)
    {
        $this->thermal138->insert($this->_table,$data_insert);
        return ($this->thermal138->affected_rows() != 1) ? FALSE : TRUE;
    }

    // update  
    public function update($data_update, $where) 
    {
        $this->thermal138->where($where);
        $res = $this->thermal138->update($this->_table, $data_update);
        return ($res != 1) ? $this->thermal138->error() : TRUE;
    }

    // delete data
    function delete($where) 
    {
        $this->thermal138->where($where);
        $res = $this->thermal138->delete($this->_table);
        return ($res != 1) ? $this->thermal138->error() : TRUE;
    }

    //check exist
    public function isAlreadyExist($where) 
    {
        $this->thermal138->where($where);
        $query=$this->thermal138->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
