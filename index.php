<!-- NotePad 轻量级云记事本系统 DEV-->
<?php

	//NotePad 轻量级云记事本系统-概述

	//功能:
	// 1.以文件或数据库的方式保存记事本
	// 2.支持MarkDown(即时预览+优化的textarea)和纯文本两种格式的记事本
	// 3.可以给记事本设置密码
	// 4.可生成记事本的二维码,以方便手机用户
	// 5.可将记事本下载到本地

	//== Server Config =====================

	$use_sql = false; //是否使用Mysql

	//======================================


	//== SQL Config ========================

	$sql_host	= "localhost";	//MySQL服务器地址

	$sql_user	= "root";		//MySQL用户名

	$sql_passwd	= "";			//MySQL密码

	$sql_name	= "notepad";	//NotePad使用的数据库名

	$sql_table	= "note_data";	//NotePad使用的表名(自动创建)

	//======================================


	function show_error_exit($output){
		//输出错误信息并终止
		echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";
		echo '<body style="background-color:#eee;margin:8px;">';
		echo '<div style="padding:10px;margin:0;font-size:14px;color:#555;background:#fff;border:0;box-shadow:0px 2px 6px rgba(100, 100, 100, 0.3);">';
		echo $output;
		echo "</div></body>";
		exit();
		die(1);
	}

	function show_input_passwd(){
		//显示输入密码框并终止
		?>
			<title>输入密码</title>
			<meta charset="utf-8" />
			<body style="background:#eee;width:490px;margin:20px auto 20px auto;">
				<h3 style="font-weight:400;">请输入密码</h3>
				<form action="?n=<?php echo $_GET['n']; ?>" method="post">
					<input type="password" name="GiveYouPasswd" placeholder="密码" style="font-size:14px;width:400px;padding:10px;margin:0;font-size:14px;color:#555;background:#fff;border:0;box-shadow:0px 2px 6px rgba(100, 100, 100, 0.3);"/>
					<input type="submit" value="提交" style="font-size:14px;padding:9px 20px;color:#555;background:#fff;border:0;box-shadow:0px 2px 6px rgba(100, 100, 100, 0.3);cursor:pointer;" />
				</form>
			</body>
		<?php
		exit();
	}

	//-----程序从这里开始-----

	//判断是否是第一次使用
	if( $use_sql == false ){
		if( !file_exists("NoteData") ){
			mkdir("NoteData");
			$init_file = fopen("NoteData/index.html", "w+");
			fclose($init_file);
			$init_file = fopen("NoteData/passwd.data", "w+");
			fclose($init_file);
			if( !file_exists("NoteData") ){
				show_error_exit("服务器端错误：无法创建文件,请检查文件系统权限");
			}
		}
	}else{
		$notesql = mysql_connect($sql_host ,$sql_user ,$sql_passwd);
		mysql_select_db($sql_name, $notesql);

		if( !mysql_query("SELECT * FROM ".$sql_table, $notesql) ){

			$is_ok = mysql_query("CREATE TABLE ".$sql_table." ( 
				ID int NOT NULL AUTO_INCREMENT, 
				PRIMARY KEY(ID), 
				passwd varchar(40), 
				content longtext 
			)",$notesql);

			if(!$is_ok){
				show_error_exit("服务器端错误：无法创建数据库表,请修正数据库连接信息或使用文件存储方式");
			}
		}
	}

	//开始处理记事本
	if( $_GET["n"] == "" ){
		//如果访问主页

		if( isset($_COOKIE['myNote']) && $_POST['force_home'] != 'yes' ){
			//如果是已保存cookie的老用户,并不是强制到主页(通过笔记本页的返回主页按钮).则取出记事本ID,并跳转根据ID跳转到笔记本页
			header("location:?n=".$_COOKIE['myNote']);
		}else{

			// $is_home = true; //设置是主页标记为真(在最后生成页面时作判断用)
			$page_type = 'home'; //设置是主页标记为真(在最后生成页面时作判断用)

			if( $_GET['new'] == 'yes' ){
				$this_name = rand(100000,999999);

				if( $use_sql == false ){
					while( file_exists("NoteData/".$this_name) ){
						$this_name = rand(100000,999999);
					}
				}else{
					while( mysql_query("SELECT * FROM ".$sql_table." WHERE ID='".$this_name."'",$notesql) ){
						$this_name = rand(100000,999999);
					}
				}

				// setcookie("myNote", $this_name, time()+31536000000);
				header("location:?n=".$this_name);
			}
		}

	}else{
		//如果指定了ID

		if( preg_match('/[.]|[?]|[$]|[<]|[>][\'][\"]+/',$_GET["n"]) || preg_match('/[A-Za-z]+/',$_GET["n"]) || !preg_match('/[0-9]+/',$_GET["n"]) || preg_match("/[\x7f-\xff]/", $_GET["n"]) || strlen($_GET["n"])!=6 ){
			//如果ID不符合规范
			show_error_exit("错误：输入的ID不合法");
		}

		setcookie("myNote", $_GET["n"], time()+31536000000);

        //判断是否已有笔记本
		if( $use_sql == false ){
			if( file_exists("NoteData/".$_GET["n"]) ){
				$this_ID_have_note = true;
			}else{
				$this_ID_have_note = false;
			}
		}else{
			$sql_return = mysql_query("SELECT * FROM ".$sql_table." WHERE ID='".$_GET['n']."'",$notesql);
			$the_content = mysql_fetch_array($sql_return);
			if( $the_content['ID'] ){
				$this_ID_have_note = true;
			}else{
				$this_ID_have_note = false;
			}
		}

		if( $this_ID_have_note ){
			//如果ID已有笔记

			$page_type = 'text_note';

            //处理输入的密码
			if( isset($_POST['GiveYouPasswd']) ){
				//如果输入了密码
				setcookie("myNodePasswdFor".$_GET['n'], $_POST['GiveYouPasswd'], time()+3600);
				echo "正在检查...";
				header("location:?n=".$_GET['n']);
			}
            
            //检查是这个ID是否有密码
			if( $use_sql == false ){

				//打开密码文件
				$passwd_file = fopen("NoteData/passwd.data","r");

				//读取密码文件
				while( !feof($passwd_file) ){
					//读取一行
					$passwd_file_this_line = fgets($passwd_file);

					//把这行分为两段
					$this_line_array = explode(" ",$passwd_file_this_line);
					
					if( $this_line_array[0] == $_GET['n'] ){
						//如果这个ID有密码并在这一行中

						//有密码标记为真
						$passwd = true;

						//判断是否已输入密码
						if( md5($_GET['n']."MyNote".$_COOKIE['myNodePasswdFor'.$_GET['n']]."Let-It-More-Lang") != $this_line_array[1] ) {
							//如果没有输入密码
							show_input_passwd();
						}
					}
				}

				fclose($passwd_file);
			}else{
				$sql_return = mysql_query("SELECT passwd FROM ".$sql_table." WHERE ID='".$_GET['n']."'");
				$the_passwd = mysql_fetch_array($sql_return);
				if( $the_passwd['passwd'] ){
					//有密码标记为真
					$passwd = true;

					if( md5($_GET['n']."MyNote".$_COOKIE['myNodePasswdFor'.$_GET['n']]."Let-It-More-Lang") != $the_passwd['passwd'] ) {
						//如果没有输入密码
						show_input_passwd();
					}
				}
			}

			if( $_POST['delete_passwd'] == 'yes' ){

				if( $use_sql == false ){

					$passwd_file = fopen("NoteData/passwd.data","a+");

					//读取密码文件
					while( !feof($passwd_file) ){
						//读取一行
						$passwd_file_this_line = fgets($passwd_file);

						//把这行分为两段
						$this_line_array = explode(" ",$passwd_file_this_line);
						
						if( $this_line_array[0] == $_GET['n'] ){
							//如果这个ID有密码并在这一行中
							$passwd_file_content = file_get_contents("NoteData/passwd.data");
							$passwd_file_content_part_1 = substr($passwd_file_content,0,ftell($passwd_file)-strlen($passwd_file_this_line) );
							$passwd_file_content_part_2 = substr($passwd_file_content,ftell($passwd_file));
							file_put_contents("NoteData/passwd.data", $passwd_file_content_part_1.$passwd_file_content_part_2);
							//删除Cookie
							setcookie("myNodePasswdFor".$_GET['n'], $_POST['GiveYouPasswd'], time()-1);
							//提示信息
							echo "<script>alert('密码已删除');</script>";
							//有密码标记为假
							$passwd = false;
						}
					}
					//关闭文件
					fclose($passwd_file);
				}else{
					mysql_query("UPDATE ".$sql_table." SET passwd = '' WHERE ID = '".$_GET['n']."'",$notesql);
					//有密码标记为假
					$passwd = false;
				}
			}

			if( isset($_POST['the_set_passwd']) ){
				//如果要设置密码

				if( $use_sql == false ){
					//打开密码文件
					$passwd_file = fopen("NoteData/passwd.data","a+");

					//写入密码信息
					fputs($passwd_file,$_GET['n'].' '.md5($_GET['n']."MyNote".$_POST['the_set_passwd']."Let-It-More-Lang").' ' );
					fputs($passwd_file,"\n");
					fclose($passwd_file);
				}else{
					mysql_query("UPDATE ".$sql_table." SET passwd = '".md5($_GET['n']."MyNote".$_POST['the_set_passwd']."Let-It-More-Lang")."' WHERE ID = '".$_GET['n']."'",$notesql);
				}

				//设置Cookie
				setcookie("myNodePasswdFor".$_GET['n'], $_POST['the_set_passwd'], time()+3600);
				//提示信息
				echo "<script>alert('密码已设置');</script>";

				//有密码标记为真
				$passwd = true;

				
			}

			if( $_POST["save"] == "yes" && isset($_POST['the_note']) ){
				//如果是普通保存
				
				if( $use_sql == false ){
					file_put_contents("NoteData/".$_GET['n'], $_POST['the_note']);
				}else{
					$to_save_tmp = $_POST['the_note'];
					$to_save_tmp = str_replace("&", "&amp;",$to_save_tmp);
					$to_save_tmp = str_replace("<", "&lt;",$to_save_tmp);
					$to_save_tmp = str_replace(">", "&gt;",$to_save_tmp);
					$to_save_tmp = str_replace("'", "&#39;",$to_save_tmp);
					$to_save_tmp = str_replace("\"", "&#42;",$to_save_tmp);
					$to_save_tmp = str_replace("=", "&#61;",$to_save_tmp);
					$to_save_tmp = str_replace("?", "&#63;",$to_save_tmp);
					mysql_query("UPDATE ".$sql_table." SET content = '".$to_save_tmp."' WHERE ID = '".$_GET['n']."'",$notesql);
				}
				
			}

			if( $_POST["ajax_save"] == "yes" && isset($_POST['the_note']) ){
				//如果是ajax保存

				$to_save_raw = $_POST['the_note'];

				if( $_POST['note_type'] == 'md_note' ){
					$to_save_raw = '<<<-- MarkDown Type Note -->>>'.$to_save_raw;
				}

				if( $use_sql == false ){
					file_put_contents("NoteData/".$_GET['n'], $to_save_raw);
				}else{
					$to_save_tmp = $to_save_raw;
					$to_save_tmp = str_replace("&", "&amp;",$to_save_tmp);
					$to_save_tmp = str_replace("<", "&lt;",$to_save_tmp);
					$to_save_tmp = str_replace(">", "&gt;",$to_save_tmp);
					$to_save_tmp = str_replace("'", "&#39;",$to_save_tmp);
					$to_save_tmp = str_replace("\"", "&#42;",$to_save_tmp);
					$to_save_tmp = str_replace("=", "&#61;",$to_save_tmp);
					$to_save_tmp = str_replace("?", "&#63;",$to_save_tmp);
					mysql_query("UPDATE ".$sql_table." SET content = '".$to_save_tmp."' WHERE ID = '".$_GET['n']."'",$notesql);
				}
				echo "ok";

				//使用ajax时无需再输出HTML,任务已完成,终止执行.
				exit();
			}

			if( $use_sql == false ){
				$note_content_to_show = file_get_contents("NoteData/".$_GET['n']); 
			}else{
				$sql_return = mysql_query("SELECT content FROM ".$sql_table." WHERE ID='".$_GET['n']."'");

				$the_content = mysql_fetch_array($sql_return);
				$note_content_to_show = $the_content['content'];
			}

			if( strpos($note_content_to_show,'<<-- MarkDown Type Note -->>>') == 1 ){
				$page_type = 'md_note';
				$note_content_to_show = str_replace('<<<-- MarkDown Type Note -->>>','',$note_content_to_show);
			}

		}else{
			//如果是新记事本

			if( isset($_POST['type']) ){

				// echo "type is ".$_POST['type'];
				//创建新新文件
				if( $use_sql == false ){
					$note_file = fopen("NoteData/".$_GET['n'], "w+");
					if( $_POST['type'] == 'md' ){
						$note_content_to_show = '#MarkDown格式记事本
- - -
在**右侧**编辑记事本，会在**左侧**显示效果。';
						fwrite($note_file, '<<<-- MarkDown Type Note -->>>'.$note_content_to_show);
					}
					fclose($note_file);
				}else{
					mysql_query("INSERT INTO ".$sql_table." (ID, passwd, content) VALUES ('".$_GET['n']."','','')",$notesql);
					if( $_POST['type'] == 'md' ){
						$note_content_to_show = '#MarkDown格式记事本
- - -
在**右侧**编辑记事本，会在**左侧**显示效果。';
						mysql_query("UPDATE ".$sql_table." SET content = '<<<-- MarkDown Type Note -->>>".$note_content_to_show."' WHERE ID = '".$_GET['n']."'",$notesql);
					}
				}

				$passwd = false;
				if( $_POST['type'] == 'md' ){
					$page_type = 'md_note';
				}else{
					$page_type = 'text_note';
				}

			}else{
				$page_type = 'select_note_type';
			}
		}

		//在记事本编辑页
		// $is_home = false;
	}//END--如果指定ID
?>









<!-- HTML页面部分 -->

<!DOCTYPE html>


	<head>

		<title>记事本</title>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

		<script src="http://cdn.bootcss.com/jquery/2.1.1/jquery.min.js"></script>


		<script type="text/javascript">

			var is_passwd_set_show = false;
			var is_need_save = false;
			var is_pic_loaded = false;



			$(document).ready(function(){
				$("#note-btns-save-form").hide();
				$("#note-btns-save-ajax").show();
				$("#note-btns-save-ajax").css({"background-color":"#ccc","cursor":"default","padding":"9px 13px"}).html("已保存");

				var winh=window.innerHeight
					|| document.documentElement.clientHeight
					|| document.body.clientHeight;

				var winw=window.innerWidth
					|| document.documentElement.clientWidth
					|| document.body.clientWidth;

				$("#note-main-form-div").height(winh-150);

				<?php if ( $page_type == 'md_note' ) : ?>
					if(winw<=1140){
						var box_width = winw-60;
					}else{
						var box_width = $("#note-main-form-div").width();
					}

					$("#note-md-show").height(winh-160).width( box_width - 535 );
					$("#note-md-edit").height(winh-160).width(500).css("margin-left",box_width - 510);
					$("#note-md-move").height(winh-150).css("left",winw - (winw - box_width)/2 - 520);
				<?php endif; ?>
			});

				
			window.onresize = function () {
				var winh=window.innerHeight
					|| document.documentElement.clientHeight
					|| document.body.clientHeight;

				var winw=window.innerWidth
					|| document.documentElement.clientWidth
					|| document.body.clientWidth;
				
				if( is_passwd_set_show ){
					$("#note-main-form-div").height(winh-207);
				}else{
					$("#note-main-form-div").height(winh-150);
				}

				$("#note-btns-setpasswd-form-input").width($("#note-btns-passwdset-form").width()-110);

				<?php if ( $page_type == 'md_note' ) : ?>
					if(winw<=1140){
						var box_width = winw-60;
					}else{
						var box_width = $("#note-main-form-div").width();
					}
					if( is_passwd_set_show ){
						$("#note-md-show").height(winh-217).width( box_width - 535 );
						$("#note-md-edit").height(winh-217).width(500).css("margin-left",box_width - 510);
						$("#note-md-move").height(winh-207).css("left",box_width - 520);
					}else{
						$("#note-md-show").height(winh-160).width( box_width - 535 );
						$("#note-md-edit").height(winh-160).width(500).css("margin-left",box_width - 510);
						$("#note-md-move").height(winh-150).css("left",winw - (winw - box_width)/2 - 520);
					}
				<?php endif; ?>
			}

			function psaawd_set_display(){

				if( !is_passwd_set_show ){
					$('#note-btns-passwdset-form').slideDown(500);
					$('#note-main-form-div').animate({height:'-=57px'},500);
					$("#note-btns-setpasswd-form-input").width($("#note-btns-passwdset-form").width()-110);
					is_passwd_set_show = true;
					<?php if ( $page_type == 'md_note' ) : ?>
						$("#note-md-edit").animate({height:'-=57px'},500);
						$("#note-md-show").animate({height:'-=57px'},500);
					<?php endif; ?>
				}else{
					$('#note-btns-passwdset-form').slideUp(500);
					$('#note-main-form-div').animate({height:'+=57px'},500);
					is_passwd_set_show = false;
					<?php if ( $page_type == 'md_note' ) : ?>
						$("#note-md-edit").animate({height:'+=57px'},500);
						$("#note-md-show").animate({height:'+=57px'},500);
					<?php endif; ?>
				}
			}

			function ajax_save(){
				if( is_need_save ){
					$("#note-btns-save-ajax").css({"background-color":"#ccc","cursor":"wait","padding":"9px 20px"}).html("正在保存");
					$.post("?n=<?php echo $_GET['n']; ?>",
					{
						ajax_save:"yes",
						the_note:$("textarea").val(),
						note_type:"<?php echo $page_type ?>"
					},
					function(data,status){
						$("#note-btns-save-ajax").css({"background-color":"#ccc","cursor":"default","padding":"9px 13px"}).html("已保存");
						is_need_save = false;
					});
				}
			}

			function note_change(ojb){
				$("#note-btns-save-ajax").css({"background-color":"#58BCFF","cursor":"pointer","padding":"9px 20px"}).html("保存");
				is_need_save = true;
			}

			function other_dev_show(){
				$('#note-otherdev-background-div').fadeIn();
				if(!is_pic_loaded){
					$('#note-otherdev-img-add').after("<img src='http://qr.liantu.com/api.php?m=0&fg=222222&w=240&text=<?php echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>'>");
				is_pic_loaded = true;
				}
			}

			function download_note(){
				$('#download-a').attr({
					<?php if ( $page_type == 'md_note' ) : ?>
						"download" : "记事本-<?php echo $_GET['n']; ?>.md",
					<?php else: ?>
						"download" : "记事本-<?php echo $_GET['n']; ?>.txt",
					<?php endif; ?>
					"href" : "data:text/plain,"+$("textarea").val().replace(/\n/g,"%0a").replace(/\#/g,"%23")
				});
				document.getElementById("download-a").click();
			}

			window.onbeforeunload = onbeforeunload_handler;  
			function onbeforeunload_handler(){
				if(is_need_save){
					var warning="您的记事本还没有保存，请确认您是否真的要离开。";      
					return warning;
				}
			}

			$(document).keydown(function(e){
				if( e.ctrlKey && e.which == 83 ){
					ajax_save();
					return false;
				}
			});

			<?php if ( $page_type == 'md_note' || $page_type == 'text_note' ) : ?>
				(function($, undefined) {
					$.fn.getCursorPosition = function() {
						var el = $(this).get(0);
						var pos = 0;
							if ('selectionStart' in el) {
							pos = el.selectionStart;
						} else if ('selection' in document) {
							el.focus();
							var Sel = document.selection.createRange();
							var SelLength = document.selection.createRange().text.length;
							Sel.moveStart('character', -el.value.length);
							pos = Sel.text.length - SelLength;
						}
						return pos;
					}
				})(jQuery);
				$(document).keydown(function(e){
					if( e.which == 9 ){
						var cursor_pos = $('textarea').getCursorPosition();
						$('textarea').val($('textarea').val().slice(0,cursor_pos)+'\t'+$('textarea').val().slice(cursor_pos));
						document.getElementById("note-md-edit").focus();
						document.getElementById("note-md-edit").setSelectionRange(cursor_pos+1,cursor_pos+1);
						return false;
					}
					if( e.which == 13 ){
						var cursor_pos = $('textarea').getCursorPosition();
						var notelines = $('textarea').val().slice(0,cursor_pos).split('\n');
						var listline = notelines[notelines.length-1];
						var n = 0;
						while(listline[n]=='\t'){
							n+=1;
						}
						$('textarea').val($('textarea').val().slice(0,cursor_pos)+'\n'+$('textarea').val().slice(cursor_pos));
						for (i=n; i>0; i--){
							$('textarea').val($('textarea').val().slice(0,cursor_pos+1)+'\t'+$('textarea').val().slice(cursor_pos+1));
						}
						document.getElementById("note-md-edit").focus();
						document.getElementById("note-md-edit").setSelectionRange(cursor_pos+n+1,cursor_pos+n+1);
						return false;
					}
				});
			<?php endif; ?>

		</script>



		<style type="text/css">

			/***** 全局 *****/

			body{
				color: #555;
				font-size: 14px;
				font-family: '文泉驛正黑','Microsoft yahei UI','Microsoft yahei','微软雅黑',"Lato",Helvetica,Arial,sans-serif;
				background:#eee;
				width:1100px;
				margin:0px auto 10px auto;
			}

			input,button{
				outline: none !important;
				-webkit-appearance:none;
				border-radius: 0;
			}

			.btn::-moz-focus-inner,input::-moz-focus-inner{
				border-color:transparent!important;
			}

			:focus {
				border: none;
				outline: 0;
			}

			::selection {
				background:#58BCFF;
				color:#fff;
			}

			::-moz-selection {
				background:#58BCFF;
				color:#fff;
			}

			::-webkit-selection {
				background:#58BCFF;
				color:#fff;
			}

			/* 设置滚动条的样式 */
			::-webkit-scrollbar {
				width: 10px;
			}
			/* 滚动槽 */
			::-webkit-scrollbar-track {
				background-color: #fff;
			}
			/* 滚动条滑块 */
			::-webkit-scrollbar-thumb {
				background: rgba(0,0,0,0.1);
			}

			::-webkit-scrollbar-thumb:hover {
				background: rgba(0,0,0,0.3);
			}

			h1,h2,h3,h4,h4,h5,h6{
				font-weight:100;
				margin: 0;
			}

			.btn{
				padding: 9px 20px;
				color: #555;
				background: #fff;
				border: 0;
				box-shadow:0px 2px 6px rgba(100, 100, 100, 0.3);
				cursor: pointer;
				font-size: 14px;
			}

			.btn:hover{
				background: #fafafa;
			}

			.input{
				font-size: 14px;
				color: #555;
				background: #fff;
				border: 0;
				box-shadow: 0px 2px 6px rgba(100, 100, 100, 0.3);
				padding: 10px;
			}

			/***** 在其他设备上访问对话框 *****/
			#note-otherdev-background-div{
				position: fixed;
				width: 100%;
				height: 100%;
				top: 0;
				left: 0;
				background-color: rgba(0,0,0,0.2);
				z-index: 10;
			}

			#note-otherdev-div{
				position: fixed;
				width: 300px;
				height: 400px;
				top: 50%;
				left: 50%;
				background-color: #fff;
				z-index: 11;
				margin: -200px 0 0 -150px;
				box-shadow: 0px 2px 6px rgba(100, 100, 100, 0.3);
			}

			.note-otherdev-div-divhr{
				width: 100%;
				height: 1px;
				background-color: #aaa;
			}

			@media screen and (max-width: 1140px){

				body{
					margin: 0 20px 0 20px;
					width: auto;
				}

			}



			/***** 主页 *****/
			/* 已移动至局部 */

			/***** 笔记本编辑页 *****/


		</style>

	</head>

	<body>

		<!-- 强制主页表单 -->
		<form action="./" method="post" style="display:none;" id="force-home-form">
			<input type="hidden" name="force_home" value="yes">
		</form>

		<!-- NotePad标题 && 返回主页按钮 -->
 		<h1 style="margin:8px 0 8px 0;display:inline-block;background:#eee;font-size:28px;color:#555;border:0;padding:0;diaplay:inline-block;cursor:pointer;" onclick="$('#force-home-form').submit();" >NotePad</h1>








		<!-- 记事本编辑页共用-1 -->
		<?php if( $page_type == 'text_note' || $page_type == 'md_note' ) : ?>
			<style type="text/css">
				#note-btns-save-ajax{
					float: right;
					background: #58BCFF;
					color: #fff;
				}

				textarea{
					padding: 0;
					margin: 0;
					color: #555;background:#fff;
					border: 0;
					resize: none;
					font-size: 16px !important;
				}

				#note-main-form-div{
					width: 1080px;
					box-shadow: 0px 2px 6px rgba(100, 100, 100, 0.3);
					background: #fff;
					padding: 10px;
				}

				@media screen and (max-width: 1140px){

					body{
						margin: 0 20px 0 20px;
						width: auto;
					}

					#note-main-form-div{
						width: auto;
						padding: 10px;
					}

					textarea{
						width: 100%;
						margin: 0;
					}
				}

				@media screen and (max-width: 420px){

					#note-btns-otherdev-btn{
						display: none;
					}

					#note-md-edit{
						width: 47% !important;
					}

				}
			</style>

			<!-- [在其他设备上访问此记事本]对话框 -->
	 		<div id="note-otherdev-background-div" style="display:none;">
				<div id="note-otherdev-div">
					<div style="background:#eee;padding:10px 0px 8px 10px;"><h4>在其他设备上访问此记事本</h4></div>

					<div class="note-otherdev-div-divhr" style="margin-bottom:8px;"></div>

					<span style="margin-left:10px;">记事本ID: <strong><?php echo $_GET['n']; ?></strong></span>

					<div style="width:240px; height:240px; margin:10px 30px 30px 30px;">
						<span id='note-otherdev-img-add'></span>
					</div>

					<div class="note-otherdev-div-divhr"></div>

					<div style="background-color:#eee; height:58px;">
						<button class="btn" style="float:right;margin:10px 10px 10px 0;" onclick="$('#note-otherdev-background-div').fadeOut();">关闭</button>
					</div>
				</div>
			</div>
		<?php endif; ?>
			






		<!-- 纯文本记事本编辑页 -->
		<?php if ( $page_type == 'text_note' ) : ?>

			<!-- 大框子 -->
			<form action="?n=<?php echo $_GET['n']; ?>" method="post" id="note-main-form" style="margin:0 auto;">
				<div id="note-main-form-div">
					<div style="width:100%; height:100%">
						<textarea autofocus="autofocus" spellcheck="false" name="the_note" onkeydown="note_change(this);" style="width:100%; height:100%"><?php echo $note_content_to_show; ?></textarea>
					</div>
				</div>
				<input type="hidden" name="save" value="yes" />
			</form>


		<?php endif; ?>








		<!-- MarkDown记事本编辑页 -->
		<?php if ( $page_type == 'md_note' ) : ?>
			<style type="text/css">
				#note-md-show p{
					margin: 15px 0;
				}
				#note-md-show h2{
					border-bottom:solid 2px #ddd;
					margin-bottom: 5px;
				}
				#note-md-show blockquote{
					border: solid 2px #eee;
					padding: 0 5px;
				}
				#note-md-show pre{
					margin: 5px 0;
					padding: 5px;
					background-color: #ddd;
				}
				#note-md-show hr{
					border: 1px solid #888;
				}
				#note-md-show code{
					background-color: #ddd;
					padding: 2px;
				}				
				/* 设置滚动条的样式 */
				textarea::-webkit-scrollbar {
					width: 10px;
				}
				/* 滚动槽 */
				textarea::-webkit-scrollbar-track {
					background-color: #dedede;
				}
				/* 滚动条滑块 */
				textarea::-webkit-scrollbar-thumb {
					background: rgba(0,0,0,0.1);
				}

				textarea::-webkit-scrollbar-thumb:hover {
					background: rgba(0,0,0,0.3);
				}
			</style>
            <script src="http://cdn.bootcss.com/markdown.js/0.5.0/markdown.min.js"></script>
			<script type="text/javascript">
				window.onload = function(){
					var oBox = document.getElementById("note-main-form-div"), oLeft = document.getElementById("note-md-show"), oRight = document.getElementById("note-md-edit"), oMove = document.getElementById("note-md-move");
					oMove.onmousedown = function(e){
						var winw=window.innerWidth
							|| document.documentElement.clientWidth
							|| document.body.clientWidth;
						var disX = (e || event).clientX;
						oMove.left = oMove.offsetLeft;
						document.onmousemove = function(e){
							var iT = oMove.left + ((e || event).clientX - disX);
							var e=e||window.event,tarnameb=e.target||e.srcElement;
							oMove.style.margin = 0;
							iT < (winw-oBox.clientWidth)/2 + 100 && (iT = (winw-oBox.clientWidth)/2 + 100);
							iT > winw-(winw-oBox.clientWidth)/2 - 100 && (iT = winw-(winw-oBox.clientWidth)/2 - 100);
							oMove.style.left  = iT + "px";
							oLeft.style.width = iT - (winw-oBox.clientWidth)/2 -25 + "px";
							oRight.style.width = oBox.clientWidth - iT - 30 + (winw-oBox.clientWidth)/2 + "px";
							oRight.style.marginLeft = iT - (winw-oBox.clientWidth)/2 + "px";
							return false
						};
						document.onmouseup = function(){
							document.onmousemove = null;
							document.onmouseup = null;
							oMove.releaseCapture && oMove.releaseCapture()
						};
						oMove.setCapture && oMove.setCapture();
						return false
					};
				};
			</script>
			
			<!-- 大框子 -->
			<form action="?n=<?php echo $_GET['n']; ?>" method="post" id="note-main-form" style="margin:0 auto;">
				<div id="note-main-form-div">
					<div style="width:100%; height:100%">
						<div id="note-md-show" style="position: absolute;width:49%; height:100%; font-size:16px; overflow:auto; padding:5px;"></div>
						<div id="note-md-move" style="height:100%;width:5px;background-color:#aaa;position: absolute;cursor: ew-resize;"></div>
						<textarea id="note-md-edit" style="position: absolute;overflow:auto;width:48%; height:100%; float:right; background-color:#fff; padding:5px" spellcheck="false" oninput="this.editor.update()" autofocus="autofocus" name="the_note" onkeydown="note_change(this);" ><?php echo $note_content_to_show; ?></textarea>
					</div>
				</div>
				<input type="hidden" name="save" value="yes" />
			</form>

			<script>
				function Editor(input, preview) {
					this.update = function () {
						preview.innerHTML = markdown.toHTML(input.value);
						$("#note-md-show a").attr("target","_blank");
					};
					input.editor = this;
					this.update();
				}
				new Editor(document.getElementById("note-md-edit"), document.getElementById("note-md-show"));
			</script>

		<?php endif; ?>








		<!-- 记事本编辑页共用-2 -->
		<?php if( $page_type == 'text_note' || $page_type == 'md_note' ) : ?>
			<form action="?n=<?php echo $_GET['n']; ?>" method="post" id="note-btns-passwdset-form" style="display:none; margin-top:20px; height:37px;">
				<input id="note-btns-setpasswd-form-input" type="password" name="the_set_passwd" placeholder="新密码" class="input" style="width:870px;"/>
				<input id="note-btns-setpasswd-form-btn" type="submit" value="设置" class="btn" style="float:right;"/>
			</form>

			<form action="?n=<?php echo $_GET['n']; ?>" method="post" id="note-btns-passwddelete-form" style="display:none;margin:0;">
				<input type="hidden" name="delete_passwd" value="yes" />
			</form>
			
			<div id="note-btns-div" style="margin:20px 0 0 0;">
				
				<!-- 密码 设置 && 删除 表单+按钮 -->
				<?php if($passwd) : ?>
					<button class="btn" onclick="$('#note-btns-passwddelete-form').submit();">删除密码</button>
				<?php else : ?>
					<button class="btn" onclick="psaawd_set_display();">设置密码</button>
				<?php endif; ?>

				<button style="margin-left:20px;" class="btn" onclick="download_note();" id="note-btns-otherdev-btn">下载</button>

				<a id="download-a" style="display:none"></a>

				<button style="margin-left:20px;" class="btn" onclick="other_dev_show();" id="note-btns-otherdev-btn">在其它设备上访问</button>
				
				<!-- 对于老式浏览器的传统表单保存,在现代浏览器中会自动隐藏 -->
				<button id="note-btns-save-form" class="btn" onclick="document.getElementById('note-main-form').submit();">保存</button>

				<!-- 对于现代浏览器的ajax保存,在现代浏览器中会自动显示 -->
				<button id="note-btns-save-ajax" style="display:none;" class="btn" onclick="ajax_save();">保存</button>

		<?php endif; ?>







		<!-- 主页HTML -->
		<?php if ( $page_type == 'home' ) : ?>

			<style type="text/css">
				body{
					margin: 0 auto 20px auto;
					max-width: 980px;
					width: 95%;
				}

				.homediv{
					box-shadow: 0px 2px 6px rgba(100, 100, 100, 0.3);
					background: #fff;
					display: inline-block;
					width: 440px;
					height: 550px;
					padding: 20px;
				}

				.icon{
					line-height: 100%;
					width: 1em;
					height: 1em;
					position: relative;
					display: block;
					float: left;
				}

				.icon-plus,
				.icon-plus:after {
					position: absolute;
					width: .375em;
					height: .375em;
					border-style: solid;
					border-color: rgb(102, 102, 102); /* #666 */
					font-size: 300px;
				}

				.icon-plus {
					top: 80px;
					left: 94px;
					border-width: 0 .10em .10em 0;
				}

				.icon-plus:after {
					content: "";
					top: .375em;
					left: .375em;
					border-width: .10em 0 0 .10em;
				}


				.icon-file {
					position: absolute;
					top: 60px;
					left: 115px;
					width: .5em;
					height: .75em;
					border-width: .1em;
					border-style: solid;
					border-color: rgb(102, 102, 102); /* #666 */
					background-color: rgb(249, 249, 249); /* #f9f9f9 */
					font-size: 300px;
				}

				.icon-file:before {
					content: "";
					position: absolute;
					top: -.1em;
					left: -.1em;
					width: 0;
					height: 0;
					border-width: .1em;
					border-style: solid;
					border-color: rgb(255, 255, 255) rgb(102, 102, 102) rgb(102, 102, 102) rgb(255, 255, 255); /* #fff and #666 - #fff has to mach body bg*/
				}

				#home-input{
					margin:460px 0px 0px 0px;
					font-size:23px;
					width:255px;
					background:#C6E8FF;
				}

				#home-btn-new{
					margin:460px 0px 0px 0px;
					background:#58BCFF;
					color:#fff;
					font-size:24px;
					padding:9px 154px 9px 154px;
				}

				#home-btn-go{
					margin:460px 15px 0px 0px;
					background:#58BCFF;
					color:#fff;
					font-size:24px;
					padding:9px 30px 9px 30px;
					float:right;
				}

				#back-to-note{
					float: right;
					text-decoration: none;
					background: #58BCFF;
					color: #fff;
					font-size: 15px;
					margin: 8px 0 10px 0;
					box-shadow: 0px 1px 3px rgba(100, 100, 100, 0.3);
				}

				@media screen and (max-width: 1030px){
					body{
						margin: 20px auto;
					}

					.icon{
						display: none;
					}

					#home-form-new,#home-form-go{
						width: 410px;
						margin: 40px auto 0 auto;
					}

					.homediv{
						height: 140px;
						clear: both;
						display: block;
						float: none !important;
						margin: 20px auto;
					}

					#home-btn-new,#home-input,#home-btn-go{
						margin: 0;
					}
				}
			</style>

			<?php if( isset($_COOKIE['myNote']) ) : ?>
				<!-- 强制主页时的返回按钮 -->
				<a href="?n=<?php echo $_COOKIE['myNote']; ?>" id="back-to-note" class="btn" >回到我的笔记</a>
			<?php endif; ?>

			<div style="clear:both;"></div>

			<div class="homediv">

				<h2>还没有记事本?</h2>

				<span class="icon icon-mid">
					<span class="icon-plus"></span>
				</span>

				<form action="?new=yes" method="post" id="home-form-new">
					<button id="home-btn-new" class="btn">立刻创建</button>
				</form>

			</div>

			<div style="float:right;" class="homediv">

				<h2>已有记事本</h2>

				<span class="icon icon-mid">
					<span class="icon-file"></span>
				</span>

				<form action="" method="get" id="home-form-go">
					<input id="home-input" name="n" type="text" class="input" autofocus="autofocus" placeholder="记事本ID" />
					<button id="home-btn-go" class="btn">访问</button>
				</form>

			</div>
		<?php endif; ?>








		<?php if ( $page_type == 'select_note_type' ) : ?>

			<h2 style="margin-bottom:10px;">请选择将要创建的记事本类型:</h2>
			<form id="choose-form-md" action="" method="post">
				<input type="hidden" name="type" value="md">
				<input type="hidden" name="n" value="<?php echo $_GET['n']; ?>">
				<!-- <input type="submit" value="MarkDown" class="btn"> -->
			</form>

			<form id="choose-form-text" action="" method="post"> 
				<input type="hidden" name="type" value="text">
				<input type="hidden" name="n" value="<?php echo $_GET['n']; ?>">
				<!-- <input type="submit" value="Text" class="btn"> -->
			</form>

			<div class="btn" onclick="$('#choose-form-md').submit();" style="height:150px;margin-bottom:20px;padding:10px;">
				<h2>MarkDown格式笔记本</h2>
				<p>
					MarkDown是适合网络书写的语言，使您用极为简单的语法就能编写出样式复杂的HTML文档。<br/>
					MarkDown的语法极为简介，全部由符号表示。例如您写"#标题"就可以产生"&#60;h1&#62;标题&#60;/h1&#62;"
				</p>
			</div>
			<div class="btn" onclick="$('#choose-form-text').submit();" style="height:150px;margin-bottom:20px;padding:10px;">
				<h2>纯文本记事本</h2>
				<p>
					如果您不需要使用MarkDown的功能，您可以简单的创建一个纯文本的记事本。
				</p>
			</div>


		<?php endif; ?>




	</body>

<?php if($use_sql == true){ mysql_close($notesql); }?>