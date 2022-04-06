<?php

class Automail extends CI_Model
{
    protected $_table = "vnso";

    // constructor with $db as database connection
    public function __construct()
    {
        parent::__construct();
        //Connect to database avery (config/database.php)
        $this->vnso = $this->load->database('au_avery', true);
    }

    public function countAll()
    {
        return $this->vnso->count_all($this->_table);
    }

    // read all data
    public function read()
    {
        // select all query
        $this->vnso->select('*');
        return $this->vnso->get($this->_table)->result_array();
    }

    //get data. 
    public function readSO($order_number)
    {
        // select where query
        $this->vnso->where('ORDER_NUMBER', $order_number);
        $this->vnso->order_by('LENGTH(LINE_NUMBER), LINE_NUMBER', 'ASC' );
        $this->vnso->order_by('CREATEDDATE', 'DESC' );
        return $this->vnso->get($this->_table)->result_array();
    }

    //get data. 
    public function readSOLine($order_number, $line_number)
    {
        // select where query
        $array = array('ORDER_NUMBER' => $order_number, 'LINE_NUMBER' => $line_number);
        $this->vnso->where($array);
        $this->vnso->order_by('ID', 'DESC' );
        // $this->vnso->limit(1);  
        return $this->vnso->get($this->_table)->result_array();
    }

    //get data. 
    public function readInSOLine($where )
    {
        // select where query
        $this->vnso->where_in('concat(ORDER_NUMBER, "-", LINE_NUMBER)', $where);
        $this->vnso->order_by('LENGTH(LINE_NUMBER),LINE_NUMBER', 'ASC' );
        return $this->vnso->get($this->_table)->result_array();
    }

    //get data. 
    public function readItem($array)
    {
        // select where query
        $this->vnso->where($array);
        $this->vnso->order_by('ID', 'ASC' );
        $this->vnso->limit(1);  
        return $this->vnso->get($this->_table)->row_array();
    }

    public function readCol($col) {
        // select all query
        $this->vnso->select($col);
        $this->vnso->distinct();
        $this->vnso->order_by($col, 'ASC' );
        return $this->vnso->get($this->_table)->result_array();
    }

    //check form Exist
    public function checkSO($order_number){
        $this->vnso->where('ORDER_NUMBER',$order_number);
        $query=$this->vnso->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }
    
    //check internal_item exist
    public function checkSOLine($order_number, $line_number){
        $array = array('ORDER_NUMBER' => $order_number, 'LINE_NUMBER' => $line_number);
        $this->vnso->where($array);
        $query=$this->vnso->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    public function getAutomailUpdated() {
        // select 
        $this->vnso->select('CREATEDDATE');
        $this->vnso->order_by("ID", "DESC");
        $this->vnso->limit(1);  
        return $this->vnso->get($this->_table)->row_array();
    }

    public function hasSample($order_number) {
        // select 
        $PACKING_INSTR = '%' . $order_number . '%';
        $where_array = array('PACKING_INSTR' => $PACKING_INSTR);
        $this->vnso->select('PACKING_INSTR');
        $this->vnso->where($where_array);
        $this->vnso->order_by("ID", "DESC");
        $this->vnso->limit(1);  
        $query=$this->vnso->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }




}
