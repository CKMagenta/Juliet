<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	<h3><?php echo "조회 대상 : ".$user_info['user_name'];?></h3>
</div>
<div class="row-fluid">
	<div class="row-fluid" id="dev-status-form-container">
	<div class="span12">
		<form id="dev-status-form" class="form form-inline well">
			<input type="hidden" name="u" value="<?php echo $user_hash;?>">
			<a id="dev-enable" class="btn btn-primary">승인</a>
			<a id="dev-disable" class="btn btn-warning">승인 해지</a>
			
		</form>
	</div>
	</div>
	<div class="row-fluid table-container" id="dev-status-table-container">
	<div class="span12">
		<table id="dev-status-table" class="table table-condensed table-striped table-bordered table-hover">
		<thead>
			<tr>
				<th><input type="checkbox" class="select-all"></th>
				<th width="10%">승인여부</th>
				<th width="15%">기기 타입</th>
				<th width="20%">uuid</th>
				<th width="20%">GCM Registration ID</th>
				<th width="15%">등록일시</th>
				<th width="15%">상태 변경 일시</th>
		</tr>
		<tbody>
		<?php 
		if ( count($device_info) ) {
			foreach($device_info as $device) {
				echo "<tr id=\"{$device['uuid']}\"><td><input type=\"checkbox\" class=\"auth-dev-checkbox\"></td>";
				foreach($device as $col) {
					echo "<td>{$col}</td>";
				}
				echo "</tr>";
			}	
		} else {
			echo "<tr><td colspan=7>해당 사용자의 명의로 등록된 기기 정보가 없습니다.</td></tr>";
		}
		?>
		</tbody>
		</table>
	</div>
	</div>
</div>
<style type="text/css">
#dev-status-table{
	word-break:break-all;
	table-layout:fixed;
	height:auto;
			
}
#dev-status-table td {
	word-break:break-all;
}
</style>
<script type="text/javascript">
$(document).ready(function(){
	$('#dev-status-table .select-all').click(function(){
		var chk_all = $(this);
		var chk_boxes = $("#dev-status-table .auth-dev-checkbox");
		chk_boxes.prop("checked",chk_all.prop("checked"));
	});

	$("#dev-status-form #dev-enable").click(function(){
		var data = [];
		var checked_users = $(".auth-dev-checkbox:checked").parent().parent().each(function(){
			data.push({"name":"d[]","value":$(this).attr('id')});
			});
		if ( data.length == 0 ){
			return;
		}
		data.push({"name":"e","value":"1"});
		$.ajax({
			'url':'http://116.67.94.11/juliet/index.php/capulet/set_dev_status',
			'type':'post',
			'data':data,
			'success':function(resp){
				alert(resp);
				location.reload();
			}
			});
		return false;
	});
	
	$("#dev-status-form #dev-disable").click(function(){
		var data = [];
		var checked_users = $(".auth-dev-checkbox:checked").parent().parent().each(function(){
			data.push({"name":"d[]","value":$(this).attr('id')});
			});
		if ( data.length == 0 ){
			return;
		}
		data.push({"name":"e","value":"0"});
		$.ajax({
			'url':'http://116.67.94.11/juliet/index.php/capulet/set_dev_status',
			'type':'post',
			'data':data,
			'success':function(resp){
				alert(resp);
				location.reload();
			}
			});
		return false;
	});
});
</script>