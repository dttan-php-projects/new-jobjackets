<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

// require_once "PHPExcel.php";
include_once APPPATH . "/third_party/vendor/autoload.php"; 
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Phpspreadsheet extends Spreadsheet{

	public function __construct() {
		parent::__construct();
	}
}

?>