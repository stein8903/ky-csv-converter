<?php

/**
 * Config
 */
class Config
{
	
	public $pdo;
	public $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8";
	public $user = DB_USER;
	public $pass = DB_PASSWORD;

	public $tt_tbl;
	public $ms_tbl;
	public $ma_tbl;
	public $trgp_tbl;
	public $yagp_tbl;

	function __construct()
	{
		global $wpdb;

		$this->tt_tbl = $wpdb->prefix . "kcc_trader_trades";
		$this->ms_tbl = $wpdb->prefix . "kcc_monthly_sum";
		$this->ma_tbl = $wpdb->prefix . "kcc_monthly_average";
		$this->trgp_tbl = $wpdb->prefix . "kcc_trgp";
		$this->yagp_tbl = $wpdb->prefix . "kcc_yagp";

		//pdoでdb接続
		try{
			$this->pdo = new PDO($this->dsn, $this->user, $this->pass);
		}catch(PDOException $e){
			echo "DB接続エラー：" . $e;
			die();
		}

		register_activation_hook(__FILE__, array($this, 'create_tbls'));
		// add_action('admin_menu', array($this, 'add_pages'));


		
		// add_action("admin_print_styles", array($this, "get_assets"));

		// add_filter( 'upload_mimes', array($this,'allow_upload_plain') );
		// add_filter( 'upload_mimes', array($this,'allow_upload_csv') );
	}
	
}