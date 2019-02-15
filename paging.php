<?php
//Ky Library Paging System
namespace KYLibrary\Paging;

function test_echo(){
	return "名前空間でた！";
}

class Kcc_paging{

	public $page_name;
	public $rows_num;
	public $p_list_num;
	public $table;
	public $table_02;

	public $page;
	public $all_pages_count;

	function __construct($a,$b,$c,$d="",$e=""){
		global $wpdb;

		//表示設定
		$this->page_name = $a;
		$this->rows_num = $b;
		$this->p_list_num = $c;
		$this->table = empty($d) ? $wpdb->prefix . "kuchi" : $d;
		$this->table_02 = empty($e) ? $wpdb->prefix . "kuchi" : $e;

		//現在のページ
		if (isset($_POST["page"])) {
			$this->page = $_POST["page"];
		}else{
			$this->page = isset($_GET["page_num"]) ? $_GET["page_num"] : "1";
		}

		//すべてのページ数
		$site_name = $_GET["site_name"];
		$rate = $_GET["rate"];
		$rate_02 = $_GET["rate"] + 0.9;
		$order = $_GET["order"];
		$keyword = $_GET["keyword"];
		$area = $_GET["area"];

		if ($this->page_name == "index") {
			$sql = "SELECT COUNT(*) FROM $this->table";
		}else if ($this->page_name == "search") {
			if (!isset($rate) || $rate == 0 || empty($rate)) {
				$sql = "SELECT COUNT(*) FROM $this->table WHERE site_name LIKE '%$site_name%' AND average BETWEEN 0 AND 5 AND contents LIKE '%$keyword%'";
			}else{
				$sql = "SELECT COUNT(*) FROM $this->table WHERE site_name LIKE '%$site_name%' AND average BETWEEN '$rate' AND '$rate_02' AND contents LIKE '%$keyword%'";
			}
		}else if ($this->page_name == "site_details") {
			$sql = "SELECT COUNT(*) FROM $this->table WHERE site_name LIKE '%$site_name%'";
		}else if ($this->page_name == "ranking") {
			$sql = "SELECT COUNT(*) FROM $this->table";
		}else if ($this->page_name == "site_archive") {
			$sql = "SELECT COUNT(*) FROM $this->table";
		}else if ($this->page_name == "area_ranking") {
			$sql = "SELECT COUNT(*) FROM $this->table JOIN $this->table_02 WHERE $this->table.id = $this->table_02.site_id AND $this->table_02.prefecture = '$pre' AND $this->table_02.address = '$area'";
		}
		$all_rows_count = $wpdb->get_var($sql);
		$this->all_pages_count = ceil($all_rows_count / $this->rows_num);
	}

	function show_sql(){
		$site_name = $_GET["site_name"];
		$rate = $_GET["rate"];
		$order = $_GET["order"];
		$keyword = $_GET["keyword"];

		$pre = $_GET["pre"];
		$area = $_GET["area"];

		$limit = $this->page * $this->rows_num - $this->rows_num;
		if ($this->page_name == "index") {
			$sql = "SELECT * FROM $this->table ORDER BY date_time DESC LIMIT $limit,$this->rows_num";
		}else if ($this->page_name == "search") {
			// $sql = "SELECT * FROM $this->table WHERE title LIKE '%$search%' ORDER BY date_time DESC LIMIT $limit,$this->rows_num";
			if (!isset($rate) || $rate == 0 || empty($rate)) {
				$sql = "SELECT * FROM $this->table WHERE site_name LIKE '%$site_name%' AND average BETWEEN 0 AND 5 AND contents LIKE '%$keyword%' ORDER BY date_time DESC LIMIT $limit,$this->rows_num";
			}else{
				$sql = "SELECT * FROM $this->table WHERE site_name LIKE '%$site_name%' AND average BETWEEN '$rate' AND '$rate_02' AND contents LIKE '%$keyword%' ORDER BY date_time DESC LIMIT $limit,$this->rows_num";
			}
		}else if($this->page_name == "site_details"){
			$sql = "SELECT * FROM $this->table WHERE site_name LIKE '%$site_name%' ORDER BY date_time DESC LIMIT $limit,$this->rows_num";
		}else if ($this->page_name == "ranking") {
			$sql = "SELECT * FROM $this->table ORDER BY date_time ASC LIMIT $limit,$this->rows_num";
		}else if ($this->page_name == "site_archive") {
			$sql = "SELECT * FROM $this->table ORDER BY date_time DESC LIMIT $limit,$this->rows_num";
		}else if ($this->page_name == "area_ranking") {
			$sql = "SELECT * FROM $this->table JOIN $this->table_02 WHERE $this->table.id = $this->table_02.site_id AND $this->table_02.prefecture = '$pre' AND $this->table_02.address = '$area' LIMIT $limit,$this->rows_num";
		}
		return $sql;
	}

	function page_list(){
		$index_count = ceil($this->all_pages_count / $this->p_list_num);
		$start = 1;
		$for = $this->p_list_num + 1;
		$back = 0;
		for ($i=0; $i < $index_count ; $i++) { 
			if ($this->page >= $start && $this->page < $start+$this->p_list_num) {
				if ($back>0) {
					$back_p = $back;
				}
				for ($i=0; $i < $this->p_list_num; $i++) { 
					$start_p[] = $start;
					if ($start >= $this->all_pages_count) {
						break;
					}
					$start++;
				}
				if ($for<=$this->all_pages_count) {
					$for_p = $for;
				}else{
					break;
				}
			}
			$start += $this->p_list_num;
			$for += $this->p_list_num;
			$back += $this->p_list_num;
		}
		$data_array = array("&laquo;"=>$back_p);
		foreach ((array)$start_p as $key => $value) {
			$data_array += array($value=>$value);
		}
		$data_array += array("&raquo;"=>$for_p);
		return $data_array;
	}

	static function current_page($value){
		$page = isset($_GET["page_num"]) ? $_GET["page_num"] : 1;
		if ($value == $page) {
			echo 'current';
		}
	}
}
?>