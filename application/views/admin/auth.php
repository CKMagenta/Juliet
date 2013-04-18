<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>사용자 및 기기인증</title>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>
	<script type="text/javascript" src="/juliet/static/bootstrap/js/bootstrap.min.js"></script>
	<link rel="stylesheet" type="text/css" href="/juliet/static/bootstrap/css/bootstrap-responsive.css">
	<link rel="stylesheet" type="text/css" href="/juliet/static/bootstrap/css/bootstrap.css">
	<style type="text/css">
		body {
			padding-top: 60px;
			padding-bottom: 40px;
		}
		.sidebar-nav {
			padding: 9px 0;
		}
		.modal-body{
			width:1000px;
			margin-left:-500px;
			max-height:600px;
		}
		.modal {
			top:-100px !important;
		}
		@media (max-width: 980px) {
			/* Enable use of floated navbar text */
			.navbar-text.pull-right {
				float: none;
				padding-left: 5px;
				padding-right: 5px;
			}
		}
	</style>
<script type="text/javascript">
$(document).ready(function(){

	$('.show-dev-status').click(function(){
			$('#dev-status-modal').modal('hide');
			var	hash = $(this).parent().parent().attr('id');
				$.ajax({
					'url':'http://116.67.94.11/juliet/index.php/capulet/device',
					'type':'get',
					'data': { "u": hash },
					'success': function(data){
						$('#dev-status-modal').html(data);
					}
					});

			$('#dev-status-modal').modal('show');	
		});

	$('#user-status-table .select-all').click(function(){
			var chk_all = $(this);
			var chk_boxes = $("#user-status-table .auth-user-checkbox");
			chk_boxes.prop("checked",chk_all.prop("checked"));
		});

	$("#user-status-form #user-enable").click(function(){
		var data = [];
		var checked_users = $(".auth-user-checkbox:checked").parent().parent().each(function(){
			data.push({"name":"u[]","value":$(this).attr('id')});
			});
		if ( data.length == 0 ){
			return;
		}
		data.push({"name":"e","value":"1"});
		$.ajax({
			'url':'http://116.67.94.11/juliet/index.php/capulet/set_user_status',
			'type':'post',
			'data':data,
			'success':function(resp){
				alert(resp);
				location.reload();
			}
			});
		return false;
	});

	$("#user-status-form #user-disable").click(function(){
		var data = [];
		var checked_users = $(".auth-user-checkbox:checked").parent().parent().each(function(){
			data.push({"name":"u[]","value":$(this).attr('id')});
			});
		if ( data.length == 0 ){
			return;
		}
		data.push({"name":"e","value":"0"});
		$.ajax({
			'url':'http://116.67.94.11/juliet/index.php/capulet/set_user_status',
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
</head>
<body>
<header>
    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container-fluid">
          <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="brand" href="#">Project Juliet</a>
          <div class="nav-collapse collapse">
<!--             <p class="navbar-text pull-right"> -->
<!--               Logged in as <a href="#" class="navbar-link">Username</a> -->
<!--             </p> -->
            <ul class="nav">
              <li class="active"><a href="#">Home</a></li>
<!--               <li><a href="#contact"></a></li> -->
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>
</header>

<section class="container-fluid">

	<article class="row-fluid">
		<div class="span12">
			<div class="row-fluid" id="user-status-form-container">
				<form id="user-status-form" class="form form-inline well">
					<fieldset><legend>사용자/기기 승인</legend>
					<a id="user-enable" class="btn btn-primary">승인</a>
					<a id="user-disable" class="btn btn-warning">승인 해지</a>
					</fieldset>
				</form>
			</div>
			<div class="row-fluid table-container" id="user-status-table-container">
				<table id="user-status-table" class="table table-condensed table-striped table-bordered table-hover">
				<caption>등록된 사용자 목록</caption>
				<thead>
					<tr>
						<th><input type="checkbox" class="select-all"></th>
						<th>상태</th>
						<th>이름</th>
						<th>계급</th>
						<th>직책</th>
						<th>부서</th>
						<th>등록일시</th>
						<th>상태 변경 시간</th>
						<th>등록기기정보</th>
					</tr>
				</thead>
				
				<tbody>
				<?php 
				foreach ( $users as $user ) {
	
					echo "<tr id=\"{$user['user_hash']}\"><td><input type=\"checkbox\" class=\"auth-user-checkbox\"></td>";
					foreach ( $user as $key=>$col ) {
						if (  $key!='user_hash' ) {
							echo "<td>{$col}</td>";
						}
					}
					echo "<td>
							<a class=\"show-dev-status\">
							<i class=\"icon-folder-open\"></i> show</a>
							</td>";
					echo "</tr>";
				}
				?>
				</tbody>
				</table>
			</div>
		</div>
	</article>
</section><!--/.fluid-container-->

<!-- Modal -->
<div id="dev-status-modal" class="modal hide fade modal-body">

</div>
</body>
</html>
