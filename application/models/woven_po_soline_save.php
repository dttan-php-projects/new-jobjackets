<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
class Woven_po_soline_save extends CI_Model
{
    protected $_table = "woven_po_soline_save";
    private $_insertBatch;

    // constructor with $db as database connection
    public function __construct()
    {
        parent::__construct();
        //Connect to database avery (config/database.php)
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
    public function readItem($so_line)
    {
        // select where query
        $this->db->where('so_line', $so_line );
        $this->db->order_by('po_no', 'DESC' );
        $this->db->limit(1);
        return $this->db->get($this->_table)->result_array();
    } 

    //get data 
    public function readPoSOLines($po_no)
    {
        // select where query
        $this->db->where('po_no', $po_no );
        $this->db->order_by('so_line', 'ASC' );
        return $this->db->get($this->_table)->result_array();
    } 

    // create
    public function create($data_insert){
        $this->db->insert($this->_table,$data_insert);
        return ($this->db->affected_rows() != 1) ? FALSE : TRUE;
    }

    // save data
    public function insertBatch() {
        $data = $this->_insertBatch;
        $this->db->insert_batch($this->_table, $data);
        return ($this->db->affected_rows() != 1) ? $this->db->error() : TRUE;
    }

    // update  
    public function update($data_update, $update_check)
    {
        $this->db->where($update_check );
        $res = $this->db->update($this->_table, $data_update);
        if(!$res) {
            $error = $this->db->error();
            return $error;
        } else {
            return 1;
        }
    }

    // delete data
    function delete($po_no)
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
    
    //check soline exist $this->db->like('column', $keyword, 'before');
    public function checkSOLine($so_line){ // 1
        $this->db->where('so_line',$so_line);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
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


    //check PO_NO Exist
    public function isAlreadyExist($po_no, $so_line ){
        $array = array('po_no' => $po_no, 'so_line' => $so_line );
        $this->db->where($array );
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }



}
