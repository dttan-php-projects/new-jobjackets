<?php

class Oe_soview_text extends CI_Model
{
    protected $_table = "oe_soview_text";

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

    public function readSingle($order_number, $line_number) {
        // select 
        $array = array('ORDER_NUMBER' => $order_number, 'LINE_NUMBER' => $line_number);
        $this->vnso->select('*');
        $this->vnso->where($array);
        $this->vnso->order_by("ID", "DESC");
        // $this->vnso->limit(1);
        return $this->vnso->get($this->_table)->result_array();
    }



}
