<?php

class Autoload extends CI_Model
{
    protected $_table = "autoload_log";

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

    public function getAutomailUpdated() {
        // select 
        $this->vnso->select('CREATEDDATE, STATUS, EMPTY, LOGDATA');
        $this->vnso->where('FUNC', 'AUTOMAIL');
        $this->vnso->order_by('ID', 'DESC');
        $this->vnso->limit(1);
        return $this->vnso->get($this->_table)->row_array();

    }

    public function getLastUpdated() {
        // select 
        $this->vnso->select('CREATEDDATE, STATUS, EMPTY, LOGDATA');
        $this->vnso->where('FUNC', 'AUTOMAIL');
        $this->vnso->order_by('ID', 'DESC');
        $this->vnso->limit(1);
        return $this->vnso->get($this->_table)->row_array();

    }

    public function getLastUpdatedOK() {
        // select 
        $where = array('FUNC' => 'AUTOMAIL', 'STATUS' => 'OK' );
        $this->vnso->select('CREATEDDATE, STATUS, EMPTY, LOGDATA');
        $this->vnso->where($where );
        $this->vnso->order_by('ID', 'DESC');
        $this->vnso->limit(1);
        return $this->vnso->get($this->_table)->row_array();

    }



}
