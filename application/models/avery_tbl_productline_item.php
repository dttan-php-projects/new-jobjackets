<?php

class Avery_tbl_productline_item extends CI_Model
{
    protected $_table = "tbl_productline_item";
    private $_insertBatch;

    // constructor with $db as database connection
    public function __construct() 
    {
        parent::__construct();
        $this->cs_avery = $this->load->database('cs_avery', true);
    }
    
    public function setInsertBatch($insertBatch) 
    {
        $this->_insertBatch = $insertBatch;
    }

    public function countAll() 
    {
        return $this->cs_avery->count_all($this->_table);
    }

    // read all data
    public function read() 
    {
        return $this->cs_avery->get($this->_table)->result_array();
    }

    //get data. 
    public function readItem($where, $col=null ) 
    {
        // select where query
        $this->cs_avery->where($where);
        if ($col != null ) $this->cs_avery->order_by($col, 'ASC' );
        $this->cs_avery->limit(1);
        return $this->cs_avery->get($this->_table)->row_array();
    }

    //get data. 
    public function readOptions($where, $col=null ) 
    {
        // select where query
        $this->cs_avery->where($where);
        if ($col != null ) $this->cs_avery->order_by($col, 'ASC' );
        return $this->cs_avery->get($this->_table)->result_array();
    }

    public function readCol($col) {
        // select all query
        $this->cs_avery->select($col);
        $this->cs_avery->distinct();
        $this->cs_avery->order_by($col, 'ASC' );
        return $this->cs_avery->get($this->_table)->result_array();
    }
    
    // save data
    public function insertBatch() 
    {
        $data = $this->_insertBatch;
        $this->cs_avery->insert_batch($this->_table, $data);
        return ($this->cs_avery->affected_rows() != 1) ? $this->cs_avery->error() : TRUE;
    }
        
    // create
    public function insert($data_insert)
    {
        $this->cs_avery->insert($this->_table,$data_insert);
        return ($this->cs_avery->affected_rows() != 1) ? FALSE : TRUE;
    }

    // update  
    public function update($data_update, $where) 
    {
        $this->cs_avery->where($where);
        $res = $this->cs_avery->update($this->_table, $data_update);
        return ($res != 1) ? $this->cs_avery->error() : TRUE;
    }

    // delete data
    function delete($where) 
    {
        $this->cs_avery->where($where);
        $res = $this->cs_avery->delete($this->_table);
        return ($res != 1) ? $this->cs_avery->error() : TRUE;
    }

    //check exist
    public function isAlreadyExist($where) 
    {
        $this->cs_avery->where($where);
        $query=$this->cs_avery->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
