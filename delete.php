<?php
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
			$arr = [
				"tbl_name"=>$this->trgp_tb,
				"file_name"=>"trgp",
				"year"=>$_POST["year"]
			];
			$this->mk_excel($arr);

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

	function mk_excel($dataArray){
		//$dataArrayの配列キー
		//tbl_name, file_name, year

		//ライブラリー・データ準備
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$arrayData = $this->download_spreadsheet($dataArray["tbl_name"], $dataArray["year"]);
		
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
		$writer->save($path.'/'.$dataArray["file_name"].'xlsx');
		
		// echo ABSPATH . "wp-content/kcc_temp_file/";
	}

	function download_spreadsheet($tbl_name, $year){
		$members = $this->get_mt4ids($tbl_name);
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

	function get_mt4ids($tbl_name){
		//trgp_tblにある全てのmt4_idを返す。
		$sql = "SELECT mt4_id,name FROM $tbl_name";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();
		while ($rows = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data_array[] = ["mt4_id"=>$rows["mt4_id"],"name"=>$rows["name"]];
		}
		return $data_array;
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