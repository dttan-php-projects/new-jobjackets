<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
class Thermal_save_po_soline extends CI_Model
{
    protected $_table = "thermal_save_po_soline";
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
    public function read($form_type, $updated_by,  $orderBy=null)
    {
        $where = array();

        if ($orderBy==null) $orderBy="ASC";
        if (!empty($form_type) ) {
            if (!empty($updated_by ) ) {
                $where = array('form_type' => $form_type, 'updated_by' => $updated_by );
            } else {
                $where = array('form_type' => $form_type );
            }
        } else {
            if (!empty($updated_by ) ) {
                $where = array('updated_by' => $updated_by );
            } 
        }

        if (!empty($where) ) $this->db->where($where );
        $this->db->order_by('po_no', $orderBy );
        $this->db->limit(4000);
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

    public function readItem($array) 
    {
        $this->db->where($array);
        $this->db->order_by('length(so_line),so_line', 'ASC' );
        $this->db->limit(1);
        return $this->db->get($this->_table)->row_array();
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
        $this->db->order_by('length(so_line),so_line', 'ASC' );
        $this->db->limit(1);
        return $this->db->get($this->_table)->row_array();
    }

    // read for report
    public function readDistance($form_type, $updated_by, $fromDate, $toDate){
        
        $where = array('po_date >=' => $fromDate, 'po_date <=' => $toDate);
        if (!empty($form_type) ) {
            $where = array('form_type' =>$form_type, 'po_date >=' => $fromDate, 'po_date <=' => $toDate);

            if (!empty($updated_by) ) {
                $where = array('form_type' =>$form_type, 'updated_by' =>$updated_by, 'po_date >=' => $fromDate, 'po_date <=' => $toDate);
            }
        } else {
            if (!empty($updated_by) ) {
                $where = array('updated_by' =>$updated_by, 'po_date >=' => $fromDate, 'po_date <=' => $toDate);
            }
        }
        
        $this->db->where($where);
        $this->db->order_by('po_no', 'ASC' );
        return $this->db->get($this->_table)->result_array();

    }

        
    // create
    public function insert($data_insert){
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
    public function update($data_update, $where)
    {
        $this->db->where($where);
        $res = $this->db->update($this->_table, $data_update);
        return (!$res) ? $this->db->error() : TRUE;
    }

    // delete data
    function delete($po_no)
    {
        $this->db->where('po_no', $po_no);
        $res = $this->db->delete($this->_table);
        return (!$res) ? $this->db->error() : TRUE;
    }

    //check PO_NO Exist
    public function checkPO($po_no)
    {
        $this->db->where('po_no',$po_no);
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }

    public function checkOrders($where){
        $this->db->where($where);
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }

    
    //check soline exist $this->db->like('column', $keyword, 'before');
    public function isAlreadyExist($soline){
        $this->db->where('so_line',$soline);
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
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
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }



}
