<?php
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

			if($stmt->rowCount() != 0){
				$i = 0;
				// $data = [1=>"",2=>"",3=>"",4=>"",5=>"",6=>"",7=>"",8=>"",9=>"",10=>"",11=>"",12=>""];
				$data = [1=>"0.0",2=>"0.0",3=>"0.0",4=>"0.0",5=>"0.0",6=>"0.0",7=>"0.0",8=>"0.0",9=>"0.0",10=>"0.0",11=>"0.0",12=>"0.0"];
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
				$basic_data += $data;
				$data_array[] = $basic_data;
			}
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