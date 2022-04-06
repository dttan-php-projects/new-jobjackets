<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
class Common_size_save extends CI_Model
{
    protected $_table = "common_size_save";
    private $_insertBatch;
 
    // constructor with $db as database connection
    public function __construct() {
        parent::__construct();
    }
 
   public function countAll(){
        return $this->db->count_all($this->_table); 
    }

    public function setInsertBatch($insertBatch) 
    {
        $this->_insertBatch = $insertBatch;
    }

    // read all data
    public function read(){
    
        // select all query
        $this->db->select('*');
        return $this->db->get($this->_table)->result_array();
    }

    //get data
    public function readSingle($so_line)
    {
        // select where query
        $this->db->where("so_line", $so_line);
        return $this->db->get($this->_table)->result_array();
    }

    //get data
    public function readPO($no_number)
    {
        // select where query
        $this->db->where("no_number", $no_number);
        return $this->db->get($this->_table)->result_array();
    }

    // create
    public function create($data_insert)
    {
        $this->db->insert($this->_table,$data_insert);
        return ($this->db->affected_rows() != 1) ? false : true;
    }

    // save data
    public function insertBatch() 
    {
        $data = $this->_insertBatch;
        $this->db->insert_batch($this->_table, $data);
        return ($this->db->affected_rows() != 1) ? $this->db->error() : TRUE;
    }


    // update  
    public function update($data_update, $update_check)
    {
        //$array = array('so_line' => $so_line, 'size' => $size, 'color' => $color);
        $this->db->where($update_check);
        $res = $this->db->update($this->_table, $data_update);
        if(!$res) {
            $error = $this->db->error();
            return $error;
        } else {
            return 1;
        }
    }

    // delete data
    function delete($so_line)
    {
        $this->db->where("so_line", $so_line);
        $res = $this->db->delete($this->_table);
        if(!$res) {
            $error = $this->db->error();
            return $error;
        } else {
            return 1;
        }
    }

    // delete data
    function deletePO($no_number)
    {
        $this->db->where("no_number", $no_number);
        $res = $this->db->delete($this->_table);
        if(!$res) {
            $error = $this->db->error();
            return $error;
        } else {
            return TRUE;
        }
    }

    // delete data
    function deleteSize($so_line,$size,$color)
    {
        $array = array('so_line' => $so_line, 'size' => $size, 'color' => $color);
        $this->db->where($array);
        $this->db->delete($this->_table);
    }

    //check PO_NO Exist
    public function checkSOLine($so_line){
        $this->db->where('so_line', $so_line);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    //check PO_NO Exist
    public function isAlreadyExist($array){
        // $array = array( 'so_line' => $so_line, 'size' => $size, 'color' => $color );
        $this->db->where($array);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }


}