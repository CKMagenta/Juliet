<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Gcmtest extends CI_Controller {

	public function index()
	{
		//echo "GCM Tester";
	}
}
?>

<html>

<body>
<form id = "gcmForm" >
Payload<br />
<input id="event"			name="event" 			type="text" /> : Event<br />
<input id="sender"		name="sender" 		type="text" /> : Sender Idx<br />
<input id="receivers"	name="receivers" 	type="text" /> : Receivers Idxs ; idx:idx:idx <br />
<input id="roomCode" 	name="roomCode" 	type="text" /> : RoomCode (auto) <br /><br />
Message<br />
<input id="idx"		 		name="idx" 				type="text" /> : message idx on DB<br />
<input id="type"		 	name="type" 			type="text" /> : message Type (int) <br />
<input id="title"			name="title"			type="text" /> : message title <br />
<input id="content"		name="content"		type="text" /> : message Content <br /><br />
Appendix (omitted)<br />
<input id="fillIn"		value="Auto-Fill-In"					type="button" />
<input id="submit"							type="submit" />
</form>
<button id="clear">Clear Log</button>
<button id="bigger"> + </button>
<button id="smaller"> - </button>
<div id="console" style = "width : 500px; height : 300px; overflow-y:scroll; overflow-x:auto; padding: 10px;background-color:black; color:rgb(0,255,153); font-size:9px; font-family:'Courier New', Courier, monospace;"></div>
<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
<script src="http://ajax.cdnjs.com/ajax/libs/json2/20110223/json2.js"></script>
<script>
$(document).ready(function () {
	$("#bigger").click(function(e) {
		
    $("#console").css("font-size",$("#console").css("font-size").split("px")[0]*1.2 + "px" );
  });
	$("#smaller").click(function(e) {
		$("#console").css("font-size",$("#console").css("font-size").split("px")[0]*0.8 + "px" );
	});
	$("#fillIn").click(function(e) {
		var r = Math.floor(Math.random(Date.now())*10);
		
		$("#event").val(["Document", "Chat:Meeting", "Chat:Command", "Survey"][(r%4)]);
		$("#sender").val(5);
		$("#receivers").val("5:6");
		$("#roomCode").val("--auto--");
		$("#idx").val(r);
		$("#type").val(r%7);
		$("#title").val("Message title"+r);
		$("#content").val("This is Content"+r);	  
  });
	$("#clear").click(function(e) {
		$("#console").html("");
	});
	$("#submit").click(function(e) {
    var evt 			= $("#event").val();
		var sender 			= $("#sender").val();
		var _receivers 	= $("#receivers").val();
		var _roomCode 	= $("#roomCode").val();
		var idx 				= $("#idx").val();
		var type 				= $("#type").val();
		var title 			= $("#title").val();
		var content 		= $("#content").val();
		
		var receivers = _receivers.split(":");
		var roomCode = "";
		if(_roomCode) {
			roomCode = _roomCode;
		} else {
			roomCode = ""+sender+":"+Date.now();
		}
		
		var appendix = {
			appendixes : [{
				"key":"roomCode",
				"type":69888,
				"sValue":roomCode
			}]
		}
		
		var message =  {
			idx : idx,
			type : type,
			title : title,
			content : content,
			appendix : '{"appendixes":[{"key":"SURVEY","name":"Survey","sValue":{"title":"설문_제목","content":"설문_간단한_내용","openTS":1328022000000,"closeTS":1362150000000,"receivers":"1:2:3","questions":[{"title":"문항 제목","isMultiple":1,"options":["선택1","선택2","선택3"]}, {"title":"문항 제목2","isMultiple":0,"options":["선택1","선택2","선택3","선택4"]} ]},"type":144384}],"type":0}'//appendix
		};
		
		var _payload = {
			event : evt,
			sender : sender,
			receivers : receivers,
			message : message
		}
		var payload = JSON.stringify(_payload);
		$("#console").append("sended JSON : " + payload);
		$.ajax({
			url : "http://172.16.7.52/Projects/CI/index.php/message/sendMessageWithGCM",
			async:true,
			data: {"payload" : payload},
			dataType:"json",
			type:"POST",
			success: function(json) {
				console.log(JSON.stringify(json));
				var message = "Success : <br>" + JSON.stringify(json) + "<br><br>";
				$("#console").append(message);
			},
			error: function(json) {
				console.log(JSON.stringify(json));
				var message = "Error : <br>" + JSON.stringify(json) + "<br><br>";
				$("#console").append(message);
			}
		});
		
		
		e.preventDefault();
		return false;
  });
}) 
</script>
</body>
</html>