<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
class Htl_save_po_soline extends CI_Model
{
    protected $_table = "htl_save_po_soline";
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
    public function read($orderBy=null)
    {
        if ($orderBy==null) $orderBy="ASC";
        // select all query
        $this->db->select('*');
        $this->db->order_by('po_no', $orderBy );
        // $this->db->order_by('length(so_line),so_line', $orderBy );
        return $this->db->get($this->_table)->result_array();
    }

    //get data. 
    public function readOptions($where) 
    {
        // select where query
        $this->db->where($where);
        $this->db->order_by('length(so_line),so_line', 'ASC' );
        return $this->db->get($this->_table)->result_array();
    }

    public function readSOLine($po_no)
    {
        // select where query
        $this->db->where('po_no',$po_no);
        $this->db->order_by('length(so_line),so_line', 'ASC' );

        return $this->db->get($this->_table)->result_array();
    }

    public function readItem($where) 
    {
        $this->db->where($where);
        $this->db->order_by('length(so_line),so_line', 'ASC' );
        $this->db->limit(1);
        return $this->db->get($this->_table)->row_array();
    }

    public function readOrders($where )
    {
        
        $this->db->where($where);
        $this->db->order_by('length(so_line),so_line', 'ASC' );
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
    function delete($where)
    {
        $this->db->where($where );
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

    //check soline exist 
    public function checkSOLineExist($so_line){
        
        $this->db->where('so_line',$so_line);
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }

    public function isAlreadyExist($where){
        $this->db->where($where);
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }

    



}
