<?php

class Woven_master_item_process_save extends CI_Model
{
    protected $_table = "woven_master_item_process_save";
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
        return $this->db->get($this->_table)->result_array();
    }

    //get data. 
    public function readSingle($array, $col=null) {
        // select where query
        $this->db->where($array);
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
    public function create($data_insert){
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
    function deleteItem($po_no, $internal_item, $length_btp, $process_code ) {
        $array = array('po_no' => $po_no, 'internal_item' => $internal_item, 'length_btp' => $length_btp, 'process_code' => $process_code );
        $this->db->where($array);
        $this->db->delete($this->_table);
    }

    // delete data
    public function delete($po_no)
    {
        $this->db->where('po_no', $po_no);
        $res = $this->db->delete($this->_table);
        if(!$res) {
            $error = $this->db->error();
            return $error;
        } else {
            return TRUE;
        }
    }

    public function checkPO($po_no){ // 1
        $this->db->where('po_no',$po_no);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    //check exist
    public function isAlreadyExist($array ) {
        //$array = array('po_no' => $po_no, 'internal_item' => $internal_item, 'length_btp' => $length_btp, 'process_code' => $process_code );
        $this->db->where($array );
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
