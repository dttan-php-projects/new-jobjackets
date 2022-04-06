<?php

class Avery_vnso_size_oe extends CI_Model
{
    protected $_table = "vnso_size_oe";

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
    public function readSOLine($order_number, $line_number)
    {
        // select where query
        $array = array('ORDER_NUMBER' => $order_number, 'LINE_NUMBER' => $line_number);
        $this->vnso->where($array);
        $this->vnso->order_by('SIZE', 'ASC' );
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
    public function checkSOLine($order_number, $line_number ){
        $array = array('ORDER_NUMBER' => $order_number, 'LINE_NUMBER' => $line_number);
        $this->vnso->where($array);
        $query=$this->vnso->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }




}
