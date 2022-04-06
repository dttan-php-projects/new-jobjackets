<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
class Thermal_po_soline_save extends CI_Model
{
    protected $_table = "thermal_po_soline_save";

    // constructor with $db as database connection
    public function __construct()
    {
        parent::__construct();
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
    public function readSingle($po_no)
    {
        // select where query
        $this->db->where('po_no',$po_no);
        $this->db->order_by('length(so_line),so_line', 'ASC' );
        $this->db->limit(1);
        return $this->db->get($this->_table)->row_array();
    }

    public function readSOLine($po_no)
    {
        // select where query
        $this->db->where('po_no',$po_no);
        $this->db->order_by('length(so_line),so_line', 'ASC' );
        return $this->db->get($this->_table)->result_array();
    }

    public function readPoNo($order_number, $line_number)
    {
        // select where query
        if (empty($line_number)) {
            $so_line = $order_number;
        } else {
            $so_line = $order_number . '-' . $line_number;
        }
        
        $this->db->like('so_line', $so_line);
        return $this->db->get($this->_table)->row_array();
    }
        

    // create
    public function create($data_insert){
        $this->db->insert($this->_table,$data_insert);
        return ($this->db->affected_rows() != 1) ? FALSE : TRUE;
    }

    // update  
    public function update($data_update, $so_line)
    {
        $this->db->where('so_line', $so_line);
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
            return 1;
        }
    }

    //check PO_NO Exist
    public function checkPO($po_no){
        $this->db->where('po_no',$po_no);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }
    
    //check soline exist $this->db->like('column', $keyword, 'before');
    public function isAlreadyExist($soline){
        $this->db->where('so_line',$soline);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    //check soline exist 
    public function checkSOLineExist($order_number, $line_number){
        if (empty($line_number)) {
            $so_line = $order_number;
        } else {
            $so_line = $order_number . '-' . $line_number;
        }
        $this->db->like('so_line', $so_line);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }



}
