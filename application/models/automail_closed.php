<?php

class Automail_closed extends CI_Model
{
    protected $_table = "vnso_total";

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
    public function readItem($array)
    {
        // select where query
        $this->vnso->where($array);
        $this->vnso->order_by('ID', 'ASC' );
        $this->vnso->limit(1);
        return $this->vnso->get($this->_table)->row_array();
    }

    // create
    public function create($data_insert){
        $this->vnso->insert($this->_table,$data_insert);
    }

    // update  
    public function update($data_update, $order_number, $line_number)
    {
        $array = array('order_number' => $order_number, 'line_number' => $line_number);
        $this->vnso->where($array);
        $this->vnso->update($this->_table, $data_update);
    }

    // delete data
    function delete($array,$order_number, $line_number)
    {
        $array = array('order_number' => $order_number, 'line_number' => $line_number);
        $this->vnso->where($array);
        $this->vnso->delete($this->_table);
    }

    //check form Exist
    public function checkSO($order_number){
        $this->vnso->where('order_number',$order_number);
        $query=$this->vnso->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }
    
    //check internal_item exist
    public function checkSOLine($order_number, $line_number){
        $array = array('order_number' => $order_number, 'line_number' => $line_number);
        $this->vnso->where($array);
        $query=$this->vnso->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    public function getSizeAutomail($order_number, $line_number) {
        // select query
        $this->vnso->select('VIRABLE_BREAKDOWN_INSTRUCTIONS');
        $this->vnso->where($order_number, $line_number);
        $this->vnso->order_by("ID", "DESC");
        $this->vnso->limit(1);  

        return $this->vnso->get($this->_table)->row_array();
    }




}
