<?php
/*
Plugin Name: KY Csv Converter
Plugin URI: http://www.example.com/plugin
Description: csvをexcelに変換します。
Author: 金山
Version: 0.1
Author URI: http://www.steins-t.com/1_sitebird/sample/
*/

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

define("ALLOW_UNFILTERED_UPLOADS", true );

require "model/members.php";


/**
 * KCC
 */
class Kcc
{
	public $pdo;
	public $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8";
	public $user = DB_USER;
	public $pass = DB_PASSWORD;

	public $tt_tbl;
	public $ms_tbl;
	public $ma_tbl;
	public $dlog_tbl;

	public $trgp_tbl;
	public $yagp_tbl;
	public $e_system_tbl;
	public $yobi_tbl;

	function __construct()
	{
		global $wpdb;

		$this->tt_tbl = $wpdb->prefix . "kcc_trader_trades";
		$this->ms_tbl = $wpdb->prefix . "kcc_monthly_sum";
		$this->ma_tbl = $wpdb->prefix . "kcc_monthly_average";
		$this->dlog_tbl = $wpdb->prefix . "kcc_date_log";

		$this->trgp_tbl = $wpdb->prefix . "kcc_trgp";
		$this->yagp_tbl = $wpdb->prefix . "kcc_yagp";
		$this->e_system_tbl = $wpdb->prefix . "kcc_8system";
		$this->yobi_tbl = $wpdb->prefix . "kcc_yobi";

		//pdoでdb接続
		try{
			$this->pdo = new PDO($this->dsn, $this->user, $this->pass);
		}catch(PDOException $e){
			echo "DB接続エラー：" . $e;
			die();
		}

		register_activation_hook(__FILE__, array($this, 'create_tbls'));
		add_action('admin_menu', array($this, 'add_pages'));
		add_action("admin_menu", array($this,"add_member_pages"));

		
		add_action("admin_print_styles", array($this, "get_assets"));
		add_action("admin_menu",array($this,"remove_menus"));

		add_filter( 'upload_mimes', array($this,'allow_upload_plain') );
		add_filter( 'upload_mimes', array($this,'allow_upload_csv') );
	}


	function get_assets(){
		$css = plugins_url("view/common.css", __FILE__ );
		echo '<link rel="stylesheet" type="text/css" href="' . $css . '}">';
	}

	function allow_upload_plain( $mimes ) {
	    $mimes['plain'] = 'text/plain';
	    return $mimes;
	}

	function allow_upload_csv( $mimes ) {
	    $mimes['csv'] = 'text/csv';
	    return $mimes;
	}

	function remove_menus(){
		if (!current_user_can("administrator")) {
			remove_menu_page("index.php");
			remove_menu_page("edit.php?post_type=post_lp");
			remove_menu_page("upload.php");
			remove_menu_page("edit-comments.php");
			remove_menu_page("tools.php");
			remove_menu_page("edit.php?post_type=page");
			remove_menu_page("edit.php");

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			$sql = "CREATE TABLE " . $this->dlog_tbl . "(
					id int unsigned auto_increment primary key,
					year_month date,
					date_time datetime
			)";
			dbDelta($sql);
		}
	}

	//////////////////////////////////////////////////////////////////////////////////
	//テーブル作成
	//////////////////////////////////////////////////////////////////////////////////
	function create_tbls(){
		global $wpdb;

	    //DBのバージョン
	    $kcc_db_version = '0.2';

	    //現在のDBバージョン取得
	    $installed_ver = get_option( 'kcc_version' );

	    // DBバージョンが違ったら作成
	    if( $installed_ver != $kcc_db_version ) {
	    	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		    $sql[] = "CREATE TABLE " . $this->tt_tbl . " (
					trade_id int,
					mt4_id bigint unsigned,
					account_type varchar(40),
					account_currency varchar(40),
					campaign text,

					order_time date,
					settle_time date,

					trade_category varchar(40),
					trade_type varchar(40),
					product varchar(40),
					lot float,

					pay varchar(40)
		    )";

		    $sql[] = "CREATE TABLE " . $this->ms_tbl . "(
					id int unsigned auto_increment primary key,
					name varchar(30),
					mt4_id bigint unsigned,
					lot float,
					month date
			)";

		    $sql[] = "CREATE TABLE " . $this->ma_tbl . "(
					id int unsigned auto_increment primary key,
					name varchar(30),
					mt4_id bigint unsigned,
					lot float,
					month date
			)";

			$sql[] = "CREATE TABLE " . $this->dlog_tbl . "(
					id int unsigned auto_increment primary key,
					settle_time date,
					date_time datetime
			)";

			$sql[] = "CREATE TABLE " . $this->trgp_tbl . "(
					id int unsigned auto_increment primary key,
					name varchar(30),
					mt4_id bigint unsigned,
					date_time datetime
			)";

			$sql[] = "CREATE TABLE " . $this->yagp_tbl . "(
					id int unsigned auto_increment primary key,
					name varchar(30),
					mt4_id bigint unsigned,
					date_time datetime
			)";

			$sql[] = "CREATE TABLE " . $this->e_system_tbl . "(
					id int unsigned auto_increment primary key,
					name varchar(30),
					mt4_id bigint unsigned,
					date_time datetime
			)";

			$sql[] = "CREATE TABLE " . $this->yobi_tbl . "(
					id int unsigned auto_increment primary key,
					name varchar(30),
					mt4_id bigint unsigned,
					date_time datetime
			)";

		    for ($i=0; $i < count($sql); $i++) { 
		    	dbDelta($sql[$i]);
		    }
		    update_option('kcc_version', $kcc_db_version);
		}
	}

	//////////////////////////////////////////////////////////////////////////////////
	//メニューページ作成
	//////////////////////////////////////////////////////////////////////////////////
	function add_pages(){
		add_menu_page('CSVデータ管理','CSVデータ管理',  'edit_pages', 'kcc_manage_page', array($this,'manage_page'), '
dashicons-chart-line', 26);
		add_submenu_page("kcc_manage_page", "データ履歴", "データ履歴", "edit_pages", "date_log", array($this,"dlog_page"));
	}
	
	function add_member_pages(){
		add_submenu_page("kcc_manage_page", "TRGP", "TRGP", "edit_pages", "trgp", array($this,"trgp"));
		add_submenu_page("kcc_manage_page", "YAGP", "YAGP", "edit_pages", "yagp", array($this,"yagp"));
		add_submenu_page("kcc_manage_page", "8system", "8system", "edit_pages", "e_system", array($this,"e_system"));
		add_submenu_page("kcc_manage_page", "予備", "予備", "edit_pages", "yobi", array($this,"yobi"));
	}


	//////////////////////////////////////////////////////////////////////////////////
	//CSVアップロード
	//////////////////////////////////////////////////////////////////////////////////
	function manage_page() {
		global $wpdb;
	    
	    $file = $_FILES["upfile"];
	    // echo mime_content_type ($_FILES['upfile']['tmp_name'] );
	    // var_dump($_FILES);
	    if (isset($file)) {
	    	var_dump($_FILES);

	    	if ( ! function_exists( 'wp_handle_upload' ) ) {
			    require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}
			$uploadedfile = $_FILES['upfile'];

			$upload_overrides = array( 'test_form' => false );

			$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

			if ( $movefile && ! isset( $movefile['error'] ) ) {
				echo "<pre>";
				var_dump($movefile);
				echo "</pre>";

				$this->upload_csv($movefile["file"]);//tt_tblにアップ
				$this->to_monthly_average();//tt_tblのデータをma_tblに変換
				$this->to_monthly_sum();
				$this->to_dlog();
			}else{
				var_dump($movefile['error']);
			}
	    }elseif (isset($_POST["delete"])) {//データベースリセット
	    	$sql[] = "DELETE FROM $this->tt_tbl";
	    	$sql[] = "DELETE FROM $this->ma_tbl";
	    	$sql[] = "DELETE FROM $this->ms_tbl";
	    	$sql[] = "DELETE FROM $this->dlog_tbl";
	    	for ($i=0; $i < count($sql); $i++) { 
	    		$wpdb->query($sql[$i]);
	    	}
	    }

	    include_once("view/manage.php");
	}
	
	function upload_csv($file_path){
		global $wpdb;

		$data=file_get_contents($file_path);
		// $data=mb_convert_encoding($data, "UTF-8","sjis-win");
		$data=preg_replace("/\r\n|\r|\n/","\n",$data);
		// var_dump($data);

		$temp=tmpfile();
		$csv=array();

		fwrite($temp, $data);
		rewind($temp);

		while (!feof($temp)) {
			$line=fgets($temp);
			$line = explode(",", $line);
			// var_dump($line);
			$csv[] = $line;
		}
		fclose($temp);

		$count=count($csv);
		for ($i=0; $i < $count; $i++) { 
			if ($i!=0 && !empty($csv[$i][0])) {

				$trade_id = $csv[$i][0];
				$mt4_id = $csv[$i][1];
				$account_type = $csv[$i][2];
				$account_currency = $csv[$i][3];
				$campaign = $csv[$i][4];
				$order_time = $this->date_time_type($csv[$i][5]);
				$settle_time = $this->date_time_type($csv[$i][6]);
				$trade_category = $csv[$i][7];
				$trade_type = $csv[$i][8];
				$product = $csv[$i][9];
				$lot = $csv[$i][10];
				$pay = $csv[$i][11];

				$sql = "INSERT INTO $this->tt_tbl(trade_id,mt4_id,account_type,account_currency,campaign,order_time,settle_time,trade_category,trade_type,product,lot,pay) VALUES('$trade_id','$mt4_id','$account_type','$account_currency','$campaign','$order_time','$settle_time','$trade_category','$trade_type','$product','$lot','$pay')";
				$wpdb->query($sql);
			}
		}
	}

	//////////////////////////////////////////////////////////////////////////////////
	//monthly_averge_tblに入れる
	//////////////////////////////////////////////////////////////////////////////////
	function to_monthly_average(){
		global $wpdb;

		$sql = "SELECT `mt4_id`, AVG(lot) as lot, DATE_FORMAT(settle_time, '%Y-%m') as settle_time FROM $this->tt_tbl GROUP BY `mt4_id`, DATE_FORMAT(settle_time, '%Y-%m')";
		$results = $wpdb->get_results($sql, ARRAY_A);
		foreach ($results as $key => $value) {
			$data_array[] = ["mt4_id"=>$value["mt4_id"], "lot"=>$value["lot"], "date"=>$value["settle_time"]];
		}
		// echo "<pre>";
		// var_dump($data_array);
		// echo "</pre>";

		foreach ($data_array as $key => $value) {
			$mt4_id = $value["mt4_id"];
			$lot = $value["lot"];
			$date = $this->date_time_type($value["date"]);

			$sql = "SELECT COUNT(*) FROM $this->ma_tbl WHERE mt4_id = '$mt4_id' AND month = '$date'";
			$results = $wpdb->get_results($sql, ARRAY_N);
			// echo "<pre>";
			// var_dump($results);
			// echo "</pre>";

			if ($results[0][0]) {
				$sql = "UPDATE $this->ma_tbl SET lot = '$lot' WHERE mt4_id = '$mt4_id' AND month = '$date'";
				$wpdb->query($sql);
			}else{
				$sql = "INSERT INTO $this->ma_tbl(mt4_id, lot, month) VALUES('$mt4_id', '$lot', '$date')";
				$wpdb->query($sql);
			}
		}
	}

	function date_time_type($str){
		$timestamp = strtotime(str_replace("/", "-", $str));
		$date_time = date("Y-m-d H:i:s", $timestamp);
		return $date_time;
	}


	//////////////////////////////////////////////////////////////////////////////////
	//monthly_sum_tblに入れる
	//////////////////////////////////////////////////////////////////////////////////
	function to_monthly_sum(){
		global $wpdb;

		$sql = "SELECT `mt4_id`, SUM(lot) as lot, DATE_FORMAT(settle_time, '%Y-%m') as settle_time FROM $this->tt_tbl GROUP BY `mt4_id`, DATE_FORMAT(settle_time, '%Y-%m')";
		$results = $wpdb->get_results($sql, ARRAY_A);
		foreach ($results as $key => $value) {
			$data_array[] = ["mt4_id"=>$value["mt4_id"], "lot"=>$value["lot"], "date"=>$value["settle_time"]];
		}

		foreach ($data_array as $key => $value) {
			$mt4_id = $value["mt4_id"];
			$lot = $value["lot"];
			$date = $this->date_time_type($value["date"]);

			$sql = "SELECT COUNT(*) FROM $this->ms_tbl WHERE mt4_id = '$mt4_id' AND month = '$date'";
			$results = $wpdb->get_results($sql, ARRAY_N);
			// echo "<pre>";
			// var_dump($results);
			// echo "</pre>";

			if ($results[0][0]) {
				$sql = "UPDATE $this->ms_tbl SET lot = '$lot' WHERE mt4_id = '$mt4_id' AND month = '$date'";
				$wpdb->query($sql);
			}else{
				$sql = "INSERT INTO $this->ms_tbl(mt4_id, lot, month) VALUES('$mt4_id', '$lot', '$date')";
				$wpdb->query($sql);
			}
		}
	}


	//////////////////////////////////////////////////////////////////////////////////
	//datelogに入れる
	//////////////////////////////////////////////////////////////////////////////////
	function to_dlog(){
		global $wpdb;

		$sql = "SELECT DATE_FORMAT(settle_time, '%Y-%m') as settle_time FROM $this->tt_tbl GROUP BY DATE_FORMAT(settle_time, '%Y-%m')";
		$results = $wpdb->get_results($sql,ARRAY_A);
		foreach ($results as $key => $value) {
			$data_array[] = ["settle_time"=>$value["settle_time"]];
		}

		foreach ($data_array as $key => $value) {
			$date = $this->date_time_type($value["settle_time"]);

			$sql = "SELECT COUNT(*) FROM $this->dlog_tbl WHERE settle_time = '$date'";
			$results = $wpdb->get_results($sql, ARRAY_N);

			if ($results[0][0]) {
				$sql = "UPDATE $this->dlog_tbl SET settle_time = '$date' WHERE year_month = '$date'";
				$wpdb->query($sql);
			}else{
				$sql = "INSERT INTO $this->dlog_tbl(settle_time,date_time) VALUES('$date',sysdate())";
				$wpdb->query($sql);
			}
		}
	}




	function dlog_page(){
		global $wpdb;

		$sql = "SELECT * FROM $this->dlog_tbl";
		$results = $wpdb->get_results($sql,ARRAY_A);
		include_once("view/date_log.php");
	}








	//////////////////////////////////////////////////////////////////////////////////
	//excel出力
	//////////////////////////////////////////////////////////////////////////////////
	function trgp(){
		global $wpdb;

		//登録処理
		if (isset($_POST["name"])) {
			$name = $_POST["name"];
			$mt4_id = $_POST["mt4_id"];
			
			//同じMT4IDは登録できないようにする
			$sql = "SELECT mt4_id FROM $this->trgp_tbl WHERE mt4_id='$mt4_id'";
			$results = $wpdb->get_results($sql, ARRAY_A);
			// if (!$results) {
			if (!$wpdb->num_rows) {
				$wpdb->query("INSERT INTO $this->trgp_tbl(name, mt4_id, date_time) VALUES('$name', '$mt4_id', sysdate())");
			}else{
				echo "<script>alert('同じMT4IDは入れません…');</script>";
			}
		}
		//削除処理
		if (isset($_POST["delete"])) {
			$id = $_POST["delete"];
			$wpdb->query("DELETE FROM $this->trgp_tbl WHERE id='$id'");
		}
		//ダウンロード処理
		if (isset($_POST["year"])) {
			$year = $_POST["year"];
			$this->mk_excel($year);
			$url = content_url() . "/uploads/trgp.xlsx";
			echo '<meta http-equiv="refresh" content="1;URL='.$url.'">';
		}

		//データ取得
		$sql = "SELECT * FROM $this->trgp_tbl ORDER BY date_time ASC";
		$results = $wpdb->get_results($sql,ARRAY_A);

		$year = $this->get_year();

		include_once("view/members.php");

		$path = wp_upload_dir();
		$path = $path["basedir"];
		// echo $path.'/trgp.xlsx';
	}

	function get_year(){
		global $wpdb;
		
		$sql = "SELECT month FROM $this->ms_tbl GROUP BY month";
		$results = $wpdb->get_results($sql,ARRAY_A);
		foreach ($results as $key => $value){
			$time_stamp = strtotime($value["month"]);
			$year = date("Y",$time_stamp);

			$data_array[] = $year;
		}
		$result = array_unique($data_array);
		return $result;
	}

	function get_mt4ids(){
		//trgp_tblにある全てのmt4_idを返す。
		$sql = "SELECT mt4_id,name FROM $this->trgp_tbl";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();
		while ($rows = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data_array[] = ["mt4_id"=>$rows["mt4_id"],"name"=>$rows["name"]];
		}
		return $data_array;
	}

	function download_spreadsheet($year){
		$members = $this->get_mt4ids();
		foreach ($members as $key => $value) {
			$sql = "SELECT name,mt4_id,lot,DATE_FORMAT(month, '%m') as month FROM $this->ms_tbl WHERE mt4_id=:mt4_id AND YEAR(month)=:year ORDER BY month ASC";
			$stmt = $this->pdo->prepare($sql);
			$stmt->bindValue(":mt4_id", $value["mt4_id"], PDO::PARAM_INT);
			$stmt->bindValue(":year", $year, PDO::PARAM_INT);
			$stmt->execute();

			$i = 0;
			$data = [1=>"0.0",2=>"0.0",3=>"0.0",4=>"0.0",5=>"0.0",6=>"0.0",7=>"0.0",8=>"0.0",9=>"0.0",10=>"0.0",11=>"0.0",12=>"0.0"];
			
			if($stmt->rowCount() != 0){
				while ($rows = $stmt->fetch(PDO::FETCH_ASSOC)) {
					if ($i==0) {
						$basic_data = [
							"row"=>"=ROW()-1",
							"name"=>$value["name"],
							"mt4_id"=>$rows["mt4_id"]
						];
					}
					$data[(int)$rows["month"]] = $rows["lot"];
					$i++;
				}
			}else{
				$basic_data = [
					"row"=>"=ROW()-1",
					"name"=>$value["name"],
					"mt4_id"=>$value["mt4_id"]
				];
			}
			$basic_data += $data;
			$data_array[] = $basic_data;
		}
		return $data_array;
	}

	function mk_excel($year){
		//ライブラリー・データ準備
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$arrayData = $this->download_spreadsheet($year);
		
		//１行目のデータ
		$first_row = ["MT4ID","1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"];
		$sheet->fromArray($first_row, NULL, 'C1', true);

		//データを貼り付ける
		// 第四引数をtrueにしないと緩い比較が行われて0がnullとして扱われるため空欄になるので注意
		$sheet->fromArray($arrayData, NULL, 'A2', true);

		$writer = new Xlsx($spreadsheet);
		$upload_dir = wp_upload_dir();
		$path = $pathupload_dir["path"]."/trgp.xlsx";

		$path = get_template_directory();	
		$path = wp_upload_dir();

		//11/28
		//URLではなくパスを指定したら、ちゃんとファイルが生成された！
		//あとはダウンロードの問題だけだが、
		//https://wordpress.stackexchange.com/questions/3480/how-can-i-force-a-file-download-in-the-wordpress-backend
		//template_redirectフックを試してみよう！
		$path = $path["basedir"];
		$writer->save($path.'/trgp.xlsx');
		
		// echo ABSPATH . "wp-content/kcc_temp_file/";
	}














	function yagp(){
		global $wpdb;
		
		//登録処理
		if (isset($_POST["name"])) {
			$name = $_POST["name"];
			$mt4_id = $_POST["mt4_id"];
			
			//同じMT4IDは登録できないようにする
			$sql = "SELECT mt4_id FROM $this->yagp_tbl WHERE mt4_id='$mt4_id'";
			$results = $wpdb->get_results($sql, ARRAY_A);
			// if (!$results) {
			if (!$wpdb->num_rows) {
				$wpdb->query("INSERT INTO $this->yagp_tbl(name, mt4_id, date_time) VALUES('$name', '$mt4_id', sysdate())");
			}else{
				echo "<script>alert('同じMT4IDは入れません…');</script>";
			}
		}
		//削除処理
		if (isset($_POST["delete"])) {
			$id = $_POST["delete"];
			$wpdb->query("DELETE FROM $this->yagp_tbl WHERE id='$id'");
		}
		//ダウンロード処理
		if (isset($_POST["year"])) {
			$year = $_POST["year"];
			// $hoge = $this->download_spreadsheet($year);
			// echo "<pre>";
			// var_dump($hoge);
			// echo "</pre>";
			$this->y_mk_excel($year);
			$url = content_url() . "/uploads/yagp.xlsx";
			echo '<meta http-equiv="refresh" content="1;URL='.$url.'">';
		}

		//データ取得
		$sql = "SELECT * FROM $this->yagp_tbl ORDER BY date_time ASC";
		$results = $wpdb->get_results($sql,ARRAY_A);

		$year = $this->get_year();

		include_once("view/members.php");
	}

	function y_get_mt4ids(){
		//yagp_tblにある全てのmt4_idを返す。
		$sql = "SELECT mt4_id,name FROM $this->yagp_tbl";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();
		while ($rows = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data_array[] = ["mt4_id"=>$rows["mt4_id"],"name"=>$rows["name"]];
		}
		return $data_array;
	}

	function y_download_spreadsheet($year){
		$members = $this->y_get_mt4ids();
		foreach ($members as $key => $value) {
			$sql = "SELECT name,mt4_id,lot,DATE_FORMAT(month, '%m') as month FROM $this->ms_tbl WHERE mt4_id=:mt4_id AND YEAR(month)=:year ORDER BY month ASC";
			$stmt = $this->pdo->prepare($sql);
			$stmt->bindValue(":mt4_id", $value["mt4_id"], PDO::PARAM_INT);
			$stmt->bindValue(":year", $year, PDO::PARAM_INT);
			$stmt->execute();

			$i = 0;
			$data = [1=>"0.0",2=>"0.0",3=>"0.0",4=>"0.0",5=>"0.0",6=>"0.0",7=>"0.0",8=>"0.0",9=>"0.0",10=>"0.0",11=>"0.0",12=>"0.0"];
			
			if($stmt->rowCount() != 0){
				while ($rows = $stmt->fetch(PDO::FETCH_ASSOC)) {
					if ($i==0) {
						$basic_data = [
							"row"=>"=ROW()-1",
							"name"=>$value["name"],
							"mt4_id"=>$rows["mt4_id"]
						];
					}
					$data[(int)$rows["month"]] = $rows["lot"];
					$i++;
				}
			}else{
				$basic_data = [
					"row"=>"=ROW()-1",
					"name"=>$value["name"],
					"mt4_id"=>$value["mt4_id"]
				];
			}
			$basic_data += $data;
			$data_array[] = $basic_data;
		}
		return $data_array;
	}

	function y_mk_excel($year){
		//ライブラリー・データ準備
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$arrayData = $this->y_download_spreadsheet($year);
		
		//１行目のデータ
		$first_row = ["MT4ID","1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"];
		$sheet->fromArray($first_row, NULL, 'C1', true);

		//データを貼り付ける
		// 第四引数をtrueにしないと緩い比較が行われて0がnullとして扱われるため空欄になるので注意
		$sheet->fromArray($arrayData, NULL, 'A2', true);

		$writer = new Xlsx($spreadsheet);
		$upload_dir = wp_upload_dir();
		$path = $pathupload_dir["path"]."/yagp.xlsx";

		$path = get_template_directory();	
		$path = wp_upload_dir();

		//11/28
		//URLではなくパスを指定したら、ちゃんとファイルが生成された！
		//あとはダウンロードの問題だけだが、
		//https://wordpress.stackexchange.com/questions/3480/how-can-i-force-a-file-download-in-the-wordpress-backend
		//template_redirectフックを試してみよう！
		$path = $path["basedir"];
		$writer->save($path.'/yagp.xlsx');
		
		// echo ABSPATH . "wp-content/kcc_temp_file/";
	}

	












	function e_system(){
		global $wpdb;
		
		//登録処理
		if (isset($_POST["name"])) {
			$name = $_POST["name"];
			$mt4_id = $_POST["mt4_id"];
			
			//同じMT4IDは登録できないようにする
			$sql = "SELECT mt4_id FROM $this->e_system_tbl WHERE mt4_id='$mt4_id'";
			$results = $wpdb->get_results($sql, ARRAY_A);
			// if (!$results) {
			if (!$wpdb->num_rows) {
				$wpdb->query("INSERT INTO $this->e_system_tbl(name, mt4_id, date_time) VALUES('$name', '$mt4_id', sysdate())");
			}else{
				echo "<script>alert('同じMT4IDは入れません…');</script>";
			}
		}
		//削除処理
		if (isset($_POST["delete"])) {
			$id = $_POST["delete"];
			$wpdb->query("DELETE FROM $this->e_system_tbl WHERE id='$id'");
		}
		//ダウンロード処理
		if (isset($_POST["year"])) {
			$year = $_POST["year"];
			// $hoge = $this->download_spreadsheet($year);
			// echo "<pre>";
			// var_dump($hoge);
			// echo "</pre>";
			$this->e_mk_excel($year);
			$url = content_url() . "/uploads/8system.xlsx";
			echo '<meta http-equiv="refresh" content="1;URL='.$url.'">';
		}

		//データ取得
		$sql = "SELECT * FROM $this->e_system_tbl ORDER BY date_time ASC";
		$results = $wpdb->get_results($sql,ARRAY_A);

		$year = $this->get_year();

		include_once("view/members.php");
	}

	function e_get_mt4ids(){
		//8system_tblにある全てのmt4_idを返す。
		$sql = "SELECT mt4_id,name FROM $this->e_system_tbl";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();
		while ($rows = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data_array[] = ["mt4_id"=>$rows["mt4_id"],"name"=>$rows["name"]];
		}
		return $data_array;
	}

	function e_download_spreadsheet($year){
		$members = $this->e_get_mt4ids();
		foreach ($members as $key => $value) {
			$sql = "SELECT name,mt4_id,lot,DATE_FORMAT(month, '%m') as month FROM $this->ms_tbl WHERE mt4_id=:mt4_id AND YEAR(month)=:year ORDER BY month ASC";
			$stmt = $this->pdo->prepare($sql);
			$stmt->bindValue(":mt4_id", $value["mt4_id"], PDO::PARAM_INT);
			$stmt->bindValue(":year", $year, PDO::PARAM_INT);
			$stmt->execute();

			$i = 0;
			$data = [1=>"0.0",2=>"0.0",3=>"0.0",4=>"0.0",5=>"0.0",6=>"0.0",7=>"0.0",8=>"0.0",9=>"0.0",10=>"0.0",11=>"0.0",12=>"0.0"];
			
			if($stmt->rowCount() != 0){
				while ($rows = $stmt->fetch(PDO::FETCH_ASSOC)) {
					if ($i==0) {
						$basic_data = [
							"row"=>"=ROW()-1",
							"name"=>$value["name"],
							"mt4_id"=>$rows["mt4_id"]
						];
					}
					$data[(int)$rows["month"]] = $rows["lot"];
					$i++;
				}
			}else{
				$basic_data = [
					"row"=>"=ROW()-1",
					"name"=>$value["name"],
					"mt4_id"=>$value["mt4_id"]
				];
			}
			$basic_data += $data;
			$data_array[] = $basic_data;
		}
		return $data_array;
	}

	function e_mk_excel($year){
		//ライブラリー・データ準備
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$arrayData = $this->e_download_spreadsheet($year);
		
		//１行目のデータ
		$first_row = ["MT4ID","1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"];
		$sheet->fromArray($first_row, NULL, 'C1', true);

		//データを貼り付ける
		// 第四引数をtrueにしないと緩い比較が行われて0がnullとして扱われるため空欄になるので注意
		$sheet->fromArray($arrayData, NULL, 'A2', true);

		$writer = new Xlsx($spreadsheet);
		$upload_dir = wp_upload_dir();
		$path = $pathupload_dir["path"]."/8system.xlsx";

		$path = get_template_directory();	
		$path = wp_upload_dir();

		//11/28
		//URLではなくパスを指定したら、ちゃんとファイルが生成された！
		//あとはダウンロードの問題だけだが、
		//https://wordpress.stackexchange.com/questions/3480/how-can-i-force-a-file-download-in-the-wordpress-backend
		//template_redirectフックを試してみよう！
		$path = $path["basedir"];
		$writer->save($path.'/8system.xlsx');
		
		// echo ABSPATH . "wp-content/kcc_temp_file/";
	}












	function yobi(){
		global $wpdb;
		
		//登録処理
		if (isset($_POST["name"])) {
			$name = $_POST["name"];
			$mt4_id = $_POST["mt4_id"];
			
			//同じMT4IDは登録できないようにする
			$sql = "SELECT mt4_id FROM $this->yobi_tbl WHERE mt4_id='$mt4_id'";
			$results = $wpdb->get_results($sql, ARRAY_A);
			// if (!$results) {
			if (!$wpdb->num_rows) {
				$wpdb->query("INSERT INTO $this->yobi_tbl(name, mt4_id, date_time) VALUES('$name', '$mt4_id', sysdate())");
			}else{
				echo "<script>alert('同じMT4IDは入れません…');</script>";
			}
		}
		//削除処理
		if (isset($_POST["delete"])) {
			$id = $_POST["delete"];
			$wpdb->query("DELETE FROM $this->yobi_tbl WHERE id='$id'");
		}
		//ダウンロード処理
		if (isset($_POST["year"])) {
			$year = $_POST["year"];
			// $hoge = $this->download_spreadsheet($year);
			// echo "<pre>";
			// var_dump($hoge);
			// echo "</pre>";
			$this->yo_mk_excel($year);
			$url = content_url() . "/uploads/yobi.xlsx";
			echo '<meta http-equiv="refresh" content="1;URL='.$url.'">';
		}

		//データ取得
		$sql = "SELECT * FROM $this->yobi_tbl ORDER BY date_time ASC";
		$results = $wpdb->get_results($sql,ARRAY_A);

		$year = $this->get_year();

		include_once("view/members.php");
	}

	function yo_get_mt4ids(){
		//yobi_tblにある全てのmt4_idを返す。
		$sql = "SELECT mt4_id,name FROM $this->yobi_tbl";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();
		while ($rows = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data_array[] = ["mt4_id"=>$rows["mt4_id"],"name"=>$rows["name"]];
		}
		return $data_array;
	}

	function yo_download_spreadsheet($year){
		$members = $this->yo_get_mt4ids();
		foreach ($members as $key => $value) {
			$sql = "SELECT name,mt4_id,lot,DATE_FORMAT(month, '%m') as month FROM $this->ms_tbl WHERE mt4_id=:mt4_id AND YEAR(month)=:year ORDER BY month ASC";
			$stmt = $this->pdo->prepare($sql);
			$stmt->bindValue(":mt4_id", $value["mt4_id"], PDO::PARAM_INT);
			$stmt->bindValue(":year", $year, PDO::PARAM_INT);
			$stmt->execute();

			$i = 0;
			$data = [1=>"0.0",2=>"0.0",3=>"0.0",4=>"0.0",5=>"0.0",6=>"0.0",7=>"0.0",8=>"0.0",9=>"0.0",10=>"0.0",11=>"0.0",12=>"0.0"];
			
			if($stmt->rowCount() != 0){
				while ($rows = $stmt->fetch(PDO::FETCH_ASSOC)) {
					if ($i==0) {
						$basic_data = [
							"row"=>"=ROW()-1",
							"name"=>$value["name"],
							"mt4_id"=>$rows["mt4_id"]
						];
					}
					$data[(int)$rows["month"]] = $rows["lot"];
					$i++;
				}
			}else{
				$basic_data = [
					"row"=>"=ROW()-1",
					"name"=>$value["name"],
					"mt4_id"=>$value["mt4_id"]
				];
			}
			$basic_data += $data;
			$data_array[] = $basic_data;
		}
		return $data_array;
	}

	function yo_mk_excel($year){
		//ライブラリー・データ準備
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$arrayData = $this->yo_download_spreadsheet($year);
		
		//１行目のデータ
		$first_row = ["MT4ID","1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"];
		$sheet->fromArray($first_row, NULL, 'C1', true);

		//データを貼り付ける
		// 第四引数をtrueにしないと緩い比較が行われて0がnullとして扱われるため空欄になるので注意
		$sheet->fromArray($arrayData, NULL, 'A2', true);

		$writer = new Xlsx($spreadsheet);
		$upload_dir = wp_upload_dir();
		$path = $pathupload_dir["path"]."/yobi.xlsx";

		$path = get_template_directory();	
		$path = wp_upload_dir();

		//11/28
		//URLではなくパスを指定したら、ちゃんとファイルが生成された！
		//あとはダウンロードの問題だけだが、
		//https://wordpress.stackexchange.com/questions/3480/how-can-i-force-a-file-download-in-the-wordpress-backend
		//template_redirectフックを試してみよう！
		$path = $path["basedir"];
		$writer->save($path.'/yobi.xlsx');
		
		// echo ABSPATH . "wp-content/kcc_temp_file/";
	}
}

$hoge = new Kcc();



