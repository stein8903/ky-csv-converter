<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2 class="mb-20"><?php echo strtoupper($_GET["page"]);?></h2>

	<div class="clearfix">
		<form method="post" class="pull-left mb-20">
			お名前：<input type="text" name="name">
			MT4_ID：<input type="text" name="mt4_id" maxlength='19' placeholder="半角の数字を19字以内に入力してください">
			<button class="button-primary">登録</button>
		</form>
		<form method="post" class="pull-right">
			<select name="year">
<?php foreach ($year as $key => $value) :?>
	<option value="<?php echo $value;?>"><?php echo $value;?></option>
<?php endforeach;?>
			</select>
			<button class="button-primary">Excleをダウンロード</button>
		</form>
	</div>

	<table class="widefat">
		<thead>
		    <tr>
		        <th>ID</th>
		        <th>お名前</th>
		        <th>MT4_ID</th>
		        <th>投稿日</th>
		        <th>アクション</th>
		    </tr>
		</thead>
		<tfoot>
		    <tr>
		        <th>ID</th>
		        <th>お名前</th>
		        <th>MT4_ID</th>
		        <th>投稿日</th>
		        <th>アクション</th>
		    </tr>
		</tfoot>
		<tbody>
<?php
$i = 1;
foreach ($results as $key => $value) :?>
			<tr>
				<td><?php echo $i;?></td>
				<td><?php echo $value["name"];?></td>
				<td><?php echo $value["mt4_id"];?></td>
				<td><?php echo $value["date_time"];?></td>
				<td>
					<form action="" method="post" id="delete_form" style="display: inline-block;">
						<input type="hidden" name="delete" value="<?php echo $value['id'];?>">
						<input type="submit" value="削除" class="button-primary">
					</form>
				</td>
			</tr>
<?php
$i++;
endforeach;?>
		</tbody>
	</table>

	<!--Pagination-->
	<!-- <nav class="pagination cf">
		<ul>
			<li>
				<a href="?page=edit_kuchi&amp;page_num=1" class="page-numbers current">1</a>
			</li>
		</ul>
	</nav> -->
</div><!--wrap-->