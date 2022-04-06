<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
class Thermal_po_save extends CI_Model
{
    // database connection and table name
    // private $conn;
    protected $_table = "thermal_po_save";
 
    // constructor with $db as database connection
    public function __construct() {
        parent::__construct();
    }
 
    public function countAll(){
        return $this->db->count_all($this->_table); 
    }

    public function countNow() {
        $date = date('Y-m-d');
        $this->db->where('po_date', $date);
        return $this->db->get($this->_table)->num_rows();
    }

    // read all data
    public function read($order_by){
    
        // select all query
        $this->db->select('*');
        $this->db->order_by('updated_date', $order_by );
        return $this->db->get($this->_table)->result_array();
    }

    //get data
    public function readSingle($po_no)
    {
        // select where query
        $this->db->where('po_no',$po_no);
        $this->db->limit(1);
        return $this->db->get($this->_table)->row_array();
    }

    // read all data
    public function readReport($array){
        // select where query
            // $sql = "SELECT * FROM $this->_table WHERE po_date >= :re_start_date: AND po_date <= :re_end_date: ORDER BY updated_date ASC ";    
            // $sql = "SELECT * FROM $this->_table WHERE form_type = :form_type: AND po_date >= :re_start_date: AND po_date <= :re_end_date: ORDER BY updated_date ASC ";
            // return $this->db->query($sql, ['form_type' => $form_type, 're_start_date' => $start_date, 're_end_date' => $end_date])->result_array(); // for rows
        $this->db->where($array);
        $this->db->order_by('updated_date', 'ASC' );
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

    // delete data
    public function delete($po_no)
    {
        $this->db->where('po_no', $po_no);
        $res = $this->db->delete($this->_table);
        if(!$res) {
            $error = $this->db->error();
            return $error;
        } else {
            return 1;
        }
    }

    public function getLastNO($production_line, $prefix) {
        $array = array('production_line' => $production_line, 'po_no like' => $prefix);
        $this->db->where($array);
        $this->db->order_by('created_date', 'DESC' );
        $this->db->limit(1);
        return $this->db->get($this->_table)->row_array();
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