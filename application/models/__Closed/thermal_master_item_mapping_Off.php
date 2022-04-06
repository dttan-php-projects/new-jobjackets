<?php

class Thermal_master_item_mapping extends CI_Model
{
    protected $_table = "thermal_master_item_mapping";
    private $_updateBatch;
    private $_insertBatch;

    // constructor with $db as database connection
    public function __construct() {
        parent::__construct();
    }

    public function setUpdateBatch($updateBatch) {
        $this->_updateBatch = $updateBatch;
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
        $this->db->select('*');
        $this->db->order_by("internal_item", "ASC");
        return $this->db->get($this->_table)->result_array();
    }

    public function readCol($col) {
        // select all query
        $this->db->select($col);
        $this->db->distinct();
        return $this->db->get($this->_table)->result_array();
    }

    public function readMaterialCol() {
        // select all query
        $this->db->select('item_code');
        $this->db->distinct();
        $this->db->where('note', 'material' );
        return $this->db->get($this->_table)->result_array();
    }
    

    /** *********** Truyen array dang: $array = array('form_type' => $form_type, 'internal_item' => $internal_item, 'item_code' => $item_code); ******* */
    public function readSingle($array) {
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

    // save data
    public function insertBatch() {
        $data = $this->_insertBatch;
        $this->db->insert_batch($this->_table, $data);
        return ($this->db->affected_rows() != 1) ? $this->db->error() : TRUE;
    }

    // create
    public function insert($data_insert) {
        $this->db->insert($this->_table,$data_insert);
        return ($this->db->affected_rows() != 1) ? FALSE : TRUE;
    }

    // update batch
    public function updateBatch() {
        $data = $this->_updateBatch;
        $this->db->update_batch($this->_table, $data, 'internal_item');
        return ($this->db->affected_rows() != 1) ? $this->db->error() : TRUE;
    }

    // update  
    public function update($data_update, $array) {
        $this->db->where($array);
        $res = $this->db->update($this->_table, $data_update);
        return ($res != 1) ? $this->db->error() : TRUE;
    }

    // delete data
    function delete($array) {
        $this->db->where($array);
        $this->db->delete($this->_table);
    }

    //check form Exist
    public function checkForm_type($form_type) {
        $this->db->where('form_type',$form_type);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }
    
    //check internal_item exist
    public function checkInternal_item($internal_item) {
        $this->db->where('internal_item',$internal_item);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    //check item code exist
    public function checkItem_code($item_code) {
        $this->db->where('item_code',$item_code);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    //check item code exist
    public function checkCBS($internal_item) {
        $this->db->select('cbs');
        $this->db->where('internal_item',$internal_item);
        $this->db->order_by("updated_date", "DESC");
        $this->db->limit(1);  
        return $this->db->get($this->_table)->row_array();
    }

    //check exist
    public function isAlreadyExist($form_type, $internal_item, $item_code) {
        $array = array( 'form_type' => $form_type, 'internal_item' => $internal_item, 'item_code' => $item_code );
        $this->db->where($array);
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
