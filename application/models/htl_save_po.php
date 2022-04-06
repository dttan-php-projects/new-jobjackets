<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
class Htl_save_po extends CI_Model
{
    // database connection and table name
    // private $conn;
    protected $_table = "htl_save_po";
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
 
    public function countAll($form_type=null)
    {
        if ($form_type != null ) {
            $where = array('form_type' => $form_type);
            $this->db->where($where);
        } 
        return $this->db->get($this->_table)->num_rows();
        // return $this->db->count_all($this->_table); 
    }

    public function countNow($form_type=null) 
    {
        $date = date('Y-m-d');
        $where = array('po_date' => $date);
        if ($form_type != null ) $where = array('po_date' => $date, 'form_type' => $form_type);
        $this->db->where($where);
        return $this->db->get($this->_table)->num_rows();
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
        } 

        if (!empty($where) ) $this->db->where($where );
        // $this->db->order_by('po_no', $orderBy );
        $this->db->order_by('updated_date', $orderBy );
        $this->db->limit(4000);
        return $this->db->get($this->_table)->result_array();

    }


    // read for report
    public function readDistance($form_type, $updated_by, $fromDate, $toDate, $orderBy=null){
        
        if ($orderBy == null ) $orderBy = 'DESC';
        if (!empty($form_type) ) {
            $where = array('form_type' => $form_type, 'po_date >=' => $fromDate, 'po_date <=' => $toDate);

            if (!empty($updated_by) ) {
                $where = array('form_type' => $form_type, 'updated_by' =>$updated_by, 'po_date >=' => $fromDate, 'po_date <=' => $toDate);
            }
        } else {
            $where = array('po_date >=' => $fromDate, 'po_date <=' => $toDate);
        }
        
    
        $this->db->where($where);
        $this->db->order_by('po_date', $orderBy );
        // $this->db->order_by('po_no', 'ASC' );
        return $this->db->get($this->_table)->result_array();

    }


    public function readItem($where) 
    {
        $this->db->where($where);
        $this->db->limit(1);
        return $this->db->get($this->_table)->row_array();
    }


    // read all data
    public function readReport($form_type, $from_date, $to_date )
    {
        // select where query
            if (empty($form_type) ) {
                if (!empty($from_date) && !empty($to_date) ) {
                    $where = array( 'po_date >=' => $from_date, 'po_date <=' => $to_date );
                } else {
                    $where = array();
                }
                
            }  else {
                if (!empty($from_date) && !empty($to_date) ) {
                    $where = array('form_type' => $form_type, 'po_date >=' => $from_date, 'po_date <=' => $to_date );
                } else {
                    $where = array('form_type' => $form_type );
                }

            }
            
            $this->db->where($where);
            $this->db->order_by('updated_date', 'ASC' );
            return $this->db->get($this->_table)->result_array();
            
    }

    //get data. 
    public function readOptions($where) 
    {
        // select where query
        $this->db->where($where);
        return $this->db->get($this->_table)->result_array();
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
    public function update($data_update, $where)
    {
        $this->db->where($where);
        $this->db->update($this->_table, $data_update);
        return ($this->db->affected_rows() != 1) ? FALSE : TRUE;
    }

    // delete data
    public function delete($where )
    {
        $this->db->where($where );
        $res = $this->db->delete($this->_table);
        return (!$res) ? $this->db->error() : TRUE;
    }

    public function getLastNO($production_line, $prefix) 
    {
        $array = array('production_line' => $production_line, 'po_no like' => $prefix);
        $this->db->where($array);
        $this->db->order_by('po_no', 'DESC' );
        $this->db->limit(1);
        return $this->db->get($this->_table)->row_array();
    }

    //check PO_NO Exist
    public function isAlreadyExist($where)
    {
        $this->db->where($where );
        $query=$this->db->get($this->_table);
        return ($query->num_rows() > 0) ? TRUE : FALSE;
    }

    

}