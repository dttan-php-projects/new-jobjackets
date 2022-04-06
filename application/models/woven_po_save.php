<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
class Woven_po_save extends CI_Model
{
    // database connection and table name
    // private $conn;
    protected $_table = "woven_po_save";
 
    // constructor with $db as database connection
    public function __construct() {
        parent::__construct();
    }
 
    public function countAll() {
        return $this->db->count_all($this->_table); 
    }

    public function countNow() {
        $date = date('Y-m-d');
        $this->db->where('po_date', $date);
        return $this->db->get($this->_table)->num_rows();
    }

    // read all data
    public function read($order_by, $fromDate='', $toDate='' ){
    
        // select all query
        $this->db->select('*');
        if (!empty($fromDate) && !empty($toDate) ) {
            $array = array('po_date >=' => $fromDate, 'po_date <=' => $toDate);
            $this->db->where($array);
        } 
        $this->db->order_by('updated_date', $order_by );
        return $this->db->get($this->_table)->result_array();
    }

    // read all data
    public function readNow($order_by, $fromDate='', $toDate=''){
    
        // select all query
        $this->db->select('*');
        if (!empty($fromDate) && !empty($toDate) ) {
            $array = array('po_date >=' => $fromDate, 'po_date <=' => $toDate);
            $this->db->where($array);
        } 

        $this->db->order_by('updated_date', $order_by );
        $this->db->limit(500);
        return $this->db->get($this->_table)->result_array();
    }

    // read for report
    public function readDistance($fromDate, $toDate){
        
        $array = array('po_date >=' => $fromDate, 'po_date <=' => $toDate);
        $this->db->select('*');
        $this->db->where($array);
        $this->db->order_by('po_date', 'ASC' );
        return $this->db->get($this->_table)->result_array();

    }

    //get data
    public function readSingle($po_no)
    {
        // select where query
        $this->db->where('po_no',$po_no);
        // $this->db->order_by('created_date', 'DESC' );
        $this->db->limit(1);
        return $this->db->get($this->_table)->row_array();
    }

    //get data
    public function readItem($array)
    {
        // select where query
        $this->db->where($array);
        $this->db->order_by('created_date', 'DESC' );
        $this->db->limit(1);
        return $this->db->get($this->_table)->row_array();
    }

    // read all data
    public function readReport(){
        
        $this->db->order_by('po_date', 'ASC' );
        return $this->db->get($this->_table)->result_array();
    }

    // create
    public function create($data_insert){
        $this->db->insert($this->_table,$data_insert);
        return ($this->db->affected_rows() != 1) ? FALSE : TRUE;
    }

    // update  
    public function update($data_update, $po_no)
    {
        $this->db->where('po_no', $po_no);
        $this->db->update($this->_table, $data_update);
        return ($this->db->affected_rows() != 1) ? FALSE : TRUE;
    }

    // update  
    public function update2($data_update, $where_arr) {
        $this->db->where($where_arr);
        $res = $this->db->update($this->_table, $data_update);
        return ($res != 1) ? $this->db->error() : TRUE;
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

    public function getLastNO($production_line, $prefix) {
        $array = array('production_line' => $production_line, 'po_no like' => $prefix);
        $this->db->where($array);
        $this->db->order_by('po_no', 'DESC' );
        $this->db->limit(1);
        return $this->db->get($this->_table)->row_array();
    }

    //check 
    public function checkBatching($batch_no){
        $this->db->where('batch_no',$batch_no);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    //check PO_NO Exist
    public function isAlreadyExist($po_no){
        $this->db->where('po_no',$po_no);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    

}