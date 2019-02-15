<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2 class="mb-20">アップロード履歴</h2>

	<table class="widefat">
		<thead>
		    <tr>
		        <th>ID</th>
		        <th>年月</th>
		        <th>投稿日</th>
		        <th>アクション</th>
		    </tr>
		</thead>
		<tfoot>
		    <tr>
		        <th>ID</th>
		        <th>年月</th>
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
				<td><?php echo $value["settle_time"];?></td>
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