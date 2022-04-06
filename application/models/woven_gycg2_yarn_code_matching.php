<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
class Woven_gycg2_yarn_code_matching extends CI_Model
{
    protected $_table = "woven_gycg2_yarn_code_matching";
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
    public function read()
    {
        // select all query
        $this->db->select('*');
        return $this->db->get($this->_table)->result_array();
    }

    //get data
    public function readSingle($array)
    {
        // select where query
        $this->db->where($array);
        $this->db->order_by('updated_date', 'ASC' );
        return $this->db->get($this->_table)->result_array();
    }

    public function readItem($array)
    {
        // select where query
        $this->db->where($array);
        $this->db->order_by('updated_date', 'ASC' );
        return $this->db->get($this->_table)->row_array();
    }

    // create
    public function insert($data_insert)
    {
        $this->db->insert($this->_table,$data_insert);
        return ($this->db->affected_rows() != 1) ? FALSE : TRUE;
    }

    // save data
    public function insertBatch() 
    {
        $data = $this->_insertBatch;
        $this->db->insert_batch($this->_table, $data);
        return ($this->db->affected_rows() != 1) ? $this->db->error() : TRUE;
    }

    // update  
    public function update($data_update, $update_check)
    {
        $this->db->where($update_check);
        $res = $this->db->update($this->_table, $data_update);

        return (!$res) ? $this->db->error() : TRUE;
        
    }


    // delete data
    function delete($gycg2_supply_code, $existing_supply_code)
    {
        $array = array('gycg2_supply_code' => $gycg2_supply_code, 'existing_supply_code' => $existing_supply_code);
        $this->db->where($array);
        $res = $this->db->delete($this->_table);

        return (!$res) ? $this->db->error() : TRUE;
        
    }

    //check PO_NO Exist
    public function checkCode($existing_supply_code){
        $array = array('existing_supply_code' => $existing_supply_code);
        $this->db->where($array);
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0 ) ? TRUE : FALSE;
        
    }

    //check PO_NO Exist
    public function isAlreadyExist($gycg2_supply_code, $existing_supply_code){
        $array = array('gycg2_supply_code' => $gycg2_supply_code, 'existing_supply_code' => $existing_supply_code);
        $this->db->where($array);
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0 ) ? TRUE : FALSE;
        
    }


}