<?php

class Common_remark_po_save extends CI_Model
{
    protected $_table = "common_remark_po_save";
    
    // constructor with $db as database connection
    public function __construct() {
        parent::__construct();
    }

    public function countAll() {
        return $this->db->count_all($this->_table);
    }

    // read all data
    public function read() {
        // select all query
        $this->db->select('*');
        return $this->db->get($this->_table)->result_array();
    }

    public function readProductionLine($production_line) {
        // select all query
		$this->db->select('*');
		$this->db->where('production_line', $production_line);
        return $this->db->get($this->_table)->result_array();
    }

    //get data. 
    public function readSingle($production_line, $po_no, $remark ) {
        $array = array( 'production_line' => $production_line, 'po_no' => $po_no, 'remark' =>$remark );
        $this->db->where($array);
        return $this->db->get($this->_table)->row_array();
    }

    //get data. 
    public function readPO($array) {
        // select where query
        $this->db->where($array);
        return $this->db->get($this->_table)->result_array();
    }

    //get data. 
    public function readItem($array) {
        // select where query
        $this->db->where($array);
        return $this->db->get($this->_table)->row_array();
    }
    
    // create
    public function insert($data_insert) {
        $this->db->insert($this->_table, $data_insert);
        return ($this->db->affected_rows() != 1) ? $this->db->error() : TRUE;
    }

    // create
    public function create($data_insert) {
        $this->db->insert($this->_table, $data_insert);
        return ($this->db->affected_rows() != 1) ? false : true;
    }

    // update  
    public function update($data_update, $data_where) {
        $this->db->where($data_where);
        $res = $this->db->update($this->_table, $data_update);
        return ($res != 1) ? $this->db->error() : TRUE;
    }

    // delete data
    function delete($production_line, $po_no, $remark ) {
        $array = array( 'production_line' => $production_line, 'po_no' => $po_no, 'remark' => $remark );
        $this->db->where($array);
        return (!$this->db->delete($this->_table)) ? $this->db->error() : 1;
    }

    // delete data
    function deleteNO($po_no ) {
        $array = array('po_no' => $po_no );
        $this->db->where($array);
        return (!$this->db->delete($this->_table)) ? $this->db->error() : 1;
    }

    //check exist
    public function isAlreadyExist($array) {
        $this->db->where($array);
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
