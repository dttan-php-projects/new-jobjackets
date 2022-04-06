<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
class Thermal_po_detail_save extends CI_Model
{
    protected $_table = "thermal_po_detail_save";
 
    // constructor with $db as database connection
    public function __construct() {
        parent::__construct();
    }
 
   public function countAll(){
        return $this->db->count_all($this->_table); 
    }

    // read all data
    public function read(){
    
        // select all query
        $this->db->select('so_line, item_code, item_qty, note');
        return $this->db->get($this->_table)->result_array();
    }

    //get data
    public function readSingle($so_line)
    {
        // select where query
        $this->db->where('so_line',$so_line);
        return $this->db->get($this->_table)->result_array();
    }

    // create
    public function create($data_insert){
        $this->db->insert($this->_table,$data_insert);
        return ($this->db->affected_rows() != 1) ? FALSE : TRUE;
    }

    // update  
    public function update($data_update, $so_line, $item_code)
    {
        $array = array('so_line' => $so_line, 'item_code' => $item_code);
        $this->db->where($array);
        $res = $this->db->update($this->_table, $data_update);
        //return ($this->db->affected_rows() != 1) ? FALSE : TRUE;
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
        $this->db->where('so_line', $so_line);
        $res = $this->db->delete($this->_table);
        if(!$res) {
            $error = $this->db->error();
            return $error;
        } else {
            return 1;
        }
    }

    //check PO_NO Exist
    public function checkSoline($so_line){
        $this->db->where('so_line',$so_line);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    //check Item code Exist
    public function checkItem($item_code){
        $this->db->where('item_code',$item_code);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    //check Item code Exist
    public function isAlreadyExist($so_line, $item_code){
        $array = array('so_line' => $so_line, 'item_code' => $item_code );
        $this->db->where($array);
        $query=$this->db->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }


}