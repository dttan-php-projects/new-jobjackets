<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
class Woven_prepress_batching extends CI_Model
{
    protected $_table = "prd_plan_prepress_batching";
 
    // constructor with $db as database connection
    public function __construct() {
        parent::__construct();
        $this->production = $this->load->database('production', true);
    }
 
   public function countAll(){
        return $this->production->count_all($this->_table); 
    }

    // read all data
    public function read(){
    
        // select all query
        $this->production->select('*');
        return $this->production->get($this->_table)->result_array();
    }

    //get data
    public function readSingle($batch_no)
    {
        // select where query
        $this->production->where("batch_no", $batch_no);
        $this->production->order_by('size', 'ASC' );
        return $this->production->get($this->_table)->result_array();
    }

    public function readSingle2($batch_no, $col=null)
    {
        // select where query
        $this->production->where("batch_no", $batch_no);
        $this->production->order_by('group_num', 'ASC' );
        return $this->production->get($this->_table)->result_array();
    }

    public function readItem($array)
    {
        // select where query
        $this->db->where($array);
        $this->db->order_by('up_date', 'DESC' );
        return $this->db->get($this->_table)->row_array();
    }

    //get data
    public function running_time_total($batch_no)
    {
        // select where query
        $this->production->select('running_time_total');
        $this->production->from($this->_table);
        $this->production->where("batch_no", $batch_no);
        // $this->production->distinct();
        // $this->production->order_by('size', 'ASC' );
        $this->production->limit(1);
        return $this->production->get()->result()->row('running_time_total');
    }

    // update  
    public function update($data_update, $update_check)
    {
        //$array = array('so_line' => $so_line, 'size' => $size, 'color' => $color);
        $this->production->where($update_check);
        $res = $this->production->update($this->_table, $data_update);
        if(!$res) {
            $error = $this->production->error();
            return $error;
        } else {
            return 1;
        }
    }


    // delete data
    function delete($batch_no)
    {
        $this->production->where("batch_no", $batch_no);
        $res = $this->production->delete($this->_table);
        if(!$res) {
            $error = $this->production->error();
            return $error;
        } else {
            return TRUE;
        }
    }

    //check PO_NO Exist
    public function isAlreadyExist($array){
        // $array = array( 'so_line' => $so_line, 'size' => $size, 'color' => $color );
        $this->production->where($array);
        $query=$this->production->get($this->_table);
        if($query->num_rows() > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }


}