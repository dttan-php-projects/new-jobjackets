<?php

class Holidays extends CI_Model
{
    protected $_table = "holidays";
    private $_insertBatch;

    // constructor with $db as database connection
    public function __construct() 
    {
        parent::__construct();
        $this->au_avery = $this->load->database('au_avery', true);
    }
    
    public function setInsertBatch($insertBatch) 
    {
        $this->_insertBatch = $insertBatch;
    }

    public function countAll() 
    {
        return $this->au_avery->count_all($this->_table);
    }

    // read all data
    public function read() 
    {
        // select all query
        return $this->au_avery->get($this->_table)->result_array();
    }

    //get data. 
    public function readItem($where, $col=null ) 
    {
        // select where query
        $this->au_avery->where($where);
        if ($col != null ) $this->au_avery->order_by($col, 'ASC' );
        $this->au_avery->limit(1);
        return $this->au_avery->get($this->_table)->row_array();
    }

    //get data. 
    public function readOptions($where, $col=null ) 
    {
        // select where query
        $this->au_avery->where($where);
        if ($col != null ) $this->au_avery->order_by($col, 'ASC' );
        return $this->au_avery->get($this->_table)->result_array();
    }

    public function readCol($col) {
        // select all query
        $this->au_avery->select($col);
        $this->au_avery->distinct();
        $this->au_avery->order_by($col, 'ASC' );
        return $this->au_avery->get($this->_table)->result_array();
    }
    
    // save data
    public function insertBatch() 
    {
        $data = $this->_insertBatch;
        $this->au_avery->insert_batch($this->_table, $data);
        return ($this->au_avery->affected_rows() != 1) ? $this->au_avery->error() : TRUE;
    }
        
    // create
    public function insert($data_insert)
    {
        $this->au_avery->insert($this->_table,$data_insert);
        return ($this->au_avery->affected_rows() != 1) ? FALSE : TRUE;
    }

    // update  
    public function update($data_update, $where) 
    {
        $this->au_avery->where($where);
        $res = $this->au_avery->update($this->_table, $data_update);
        return ($res != 1) ? $this->au_avery->error() : TRUE;
    }

    // delete data
    function delete($where) 
    {
        $this->au_avery->where($where);
        $res = $this->au_avery->delete($this->_table);
        return ($res != 1) ? $this->au_avery->error() : TRUE;
    }

    //check exist
    public function isAlreadyExist($where) 
    {
        $this->au_avery->where($where);
        $query=$this->au_avery->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
