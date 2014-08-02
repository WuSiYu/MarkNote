<?php
	/*
	 *	NotePad 轻量级云记事本系统
	 */

	/**** Server Config ****/

	$use_sql = true; //是否使用Mysql


	/***********************/



	/***** SQL Config  *****/

	$sql_host = "localhost";	//Mysql服务器地址

	$sql_user = "root";			//Mysql用户名

	$sql_passwd = "wsy";		//Mysql密码

	$sql_name = "notepad";		//notepad使用的数据库名

	$sql_table = "note_data";	//notepad使用的表名

	/***********************/



	function better_exit($output){
		echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";
		echo '<body style="background-color:#eee;margin:8px;">';
		echo '<div style="padding:10px;margin:0;font-size:14px;color:#555;background:#fff;border:0;box-shadow:0px 2px 6px rgba(100, 100, 100, 0.3);">';
		echo $output;
		echo "</div></body>";
		exit();
		die(1);
	}

	if( $use_sql == false ){
		if( !file_exists("NoteData") ){
			mkdir("NoteData");
			$init_file = fopen("NoteData/index.html", "w+");
			fclose($init_file);
			$init_file = fopen("NoteData/passwd.data", "w+");
			fclose($init_file);
			if( !file_exists("NoteData") ){
				better_exit("服务器错误：无法创建文件,请检查文件系统权限");
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
				better_exit("Mysql Error");
			}
		}
	}

	if( $_GET["n"] == "" ){
		//如果访问主页

		if( isset($_COOKIE['myNote']) ){
			header("location:?n=".$_COOKIE['myNote']);
		}else{
			$is_home = true;
			if( $_GET['new'] == 'yes' ){
				$this_name = rand(100000,999999);

				if( $use_sql == false ){
					while( file_exists("NoteData/".$this_name) ){
						$this_name = rand(100000,999999);
					}
				}else{
					while( mysql_query("SELECT * FROM Persons WHERE ID='".$this_name."'",$notesql) ){
						$this_name = rand(100000,999999);
					}
				}

				setcookie("myNote", $this_name, time()+31536000000);
				header("location:?n=".$this_name);
			}
		}

	}else{
		//如果指定了ID

		if( preg_match('/[.]|[?]|[$]|[<]|[>]+/',$_GET["n"]) || preg_match('/[A-Za-z]+/',$_GET["n"]) || !preg_match('/[0-9]+/',$_GET["n"]) || preg_match("/[\x7f-\xff]/", $_GET["n"]) || strlen($_GET["n"])!=6 ){
			//如果ID不符合规范
			better_exit("错误：请检查地址栏");
		}

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

			if( isset($_POST['GiveYouPasswd']) ){
				//如果输入了密码
				setcookie("myNodePasswdFor".$_GET['n'], $_POST['GiveYouPasswd'], time()+31536000000);
				echo "正在检查...";
				header("location:?n=".$_GET['n']);
			}

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
							//显示输入密码框后终止执行
							exit();
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
						//显示输入密码框后终止执行
						exit();
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

			if( $_POST["save"] == "yes"){
				//如果是普通保存
				
				if( isset($_POST['the_note']) ){

					if( $use_sql == false ){
						file_put_contents("NoteData/".$_GET['n'], $_POST['the_note']);
					}else{
						mysql_query("UPDATE ".$sql_table." SET content = '".$_POST['the_note']."' WHERE ID = '".$_GET['n']."'",$notesql);
					}
				}
				
			}

			if( $_POST["ajax_save"] == "yes"){
				//如果是ajax保存
				if( isset($_POST['the_note']) ){
					if( $use_sql == false ){
						file_put_contents("NoteData/".$_GET['n'], $_POST['the_note']);
					}else{
						mysql_query("UPDATE ".$sql_table." SET content = '".$_POST['the_note']."' WHERE ID = '".$_GET['n']."'",$notesql);
					}
					echo "ok";

					//使用ajax时无需再输出HTML,任务已完成,终止执行.
					exit();
				}
			}

		}else{
			//如果是新记事本

			//创建新新文件
			if( $use_sql == false ){
				$note_file = fopen("NoteData/".$_GET['n'], "w+");
				fclose($note_file);
			}else{
				mysql_query("INSERT INTO ".$sql_table." (ID, passwd, content) VALUES ('".$_GET['n']."','','')",$notesql);
			}

			$passwd = false;
		}

		//在记事本编辑页
		$is_home = false;
	}
?>

<!DOCTYPE html>


	<head>

		<title>NotePad 云记事本系统</title>
		<meta charset="utf-8" />

		<script src="http://lib.sinaapp.com/js/jquery/1.7.2/jquery.min.js"></script>

		<script type="text/javascript">

			var is_passwd_set_show = false;
			var is_need_save = true;

			$(document).ready(function(){
				$("#save-form").hide();
				$("#save-ajax").show();
			});

			function psaawd_set_display(){

				if( is_passwd_set_show == false ){
					$('#passwd-set-form').slideDown(500);
					$('textarea').animate({height:'-=57px'},500);
					is_passwd_set_show = true;
				}else{
					$('#passwd-set-form').slideUp(500);
					$('textarea').animate({height:'+=57px'},500);
					is_passwd_set_show = false;
				}
			}

			function ajax_save(){
				if( is_need_save == true ){
					$("#save-ajax").css({"background-color":"#ccc","cursor":"wait"}).html("正在保存");
					$.post("?n=<?php echo $_GET['n']; ?>",
					{
						ajax_save:"yes",
						the_note:$("textarea").val()
					},
					function(data,status){
						$("#save-ajax").css({"background-color":"#ccc","cursor":"default"}).html("已保存");
						is_need_save = false;
					});
				}
			}

			function note_change(){
				$("#save-ajax").css({"background-color":"#58BCFF","cursor":"pointer"}).html("保存");
				is_need_save = true;
			}

		</script>

		<style type="text/css">
			body{
				color: #555;
				font-size: 14px;
				font-family: '文泉驛正黑','Microsoft yahei UI','Microsoft yahei','微软雅黑',"Lato",Helvetica,Arial,sans-serif;
			}
			input,button{
				outline: none !important ;
			}
			.btn::-moz-focus-inner{
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
			h1,h2,h3{
				font-weight:100;
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
			textarea{
				width: 960px;
				height: 500px;
				padding: 0;
				margin: 10px;
				color: #555;background:#fff;
				border: 0;
				resize: none;
			}
			.input{
				font-size: 14px;
				color: #555;
				background: #fff;
				border: 0;
				box-shadow: 0px 2px 6px rgba(100, 100, 100, 0.3);
				padding: 10px;
			}
			#show_url_background{
				position: fixed;
				width: 100%;
				height: 100%;
				top: 0;
				left: 0;
				background-color: rgba(0,0,0,0.2);
				z-index: 10;
			}
			#show_url{
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
			.divhr{
				width: 100%;
				height: 1px;
				background-color: #aaa;
			}
			.homediv{
				box-shadow: 0px 2px 6px rgba(100, 100, 100, 0.3);
				background: #fff;
				display: inline-block;
			}
			.icon {
				/* don't change width and height in order to change the size of the icon,
				you can control the size with font-size for different class(es) - below */
				line-height: 100%;
				width: 1em;
				height: 1em;
				position: relative;
				display: block;
				float: left;
			}
			/* Icon Plus */
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
				left: 114px;
				border-width: 0 .10em .10em 0;
			}
			.icon-plus:after {
				content: "";
				top: .375em;
				left: .375em;
				border-width: .10em 0 0 .10em;
			}
			/* Icon File */
			.icon-file {
				position: absolute;
				top: 60px;
				left: 135px;
				width: .5em;
				height: .75em;
				border-width: .1em;
				border-style: solid;
				border-color: rgb(102, 102, 102); /* #666 */
				background-color: rgb(249, 249, 249); /* #f9f9f9 */
				/* for browsers that supports */
				/*border-radius: .0625em;*/
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
		</style>
	</head>

	<body style="background:#eee;width:980px;margin:10px auto 10px auto;">

		<h1 style="margin:0 0 10px 0;display:inline-block;">NotePad</h1>
		<h2 style="margin:5px 0 0 0;float:right;">云记事本系统</h2>

		<?php if( $is_home == false ) : ?>
	 		<div id="show_url_background" style="display:none;">
				<div id="show_url">

					<div style="background:#eee;padding:10px 0px 8px 10px;"><h4 style="margin:0;">在其他设备上访问此记事本</h4></div>

					<div class="divhr" style="margin:0 0 8px 0;"></div>

					<span style="margin:0 0 0 10px;">记事本ID: <strong><?php echo $_GET['n']; ?></strong></span>
					<img src="http://qr.liantu.com/api.php?m=0&fg=222222&w=240&text=<?php echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>" style="margin:10px 30px 27px 30px;"/>
					
					<div class="divhr"></div>

					<div style="background-color:#eee;height:57px;">
						<button class="btn" style="float:right;margin:10px 10px 10px 0;" onclick="$('#show_url_background').fadeOut();">关闭</button>
					</div>

				</div>

			</div>

			<form action="?n=<?php echo $_GET['n']; ?>" method="post" id="note-form" style="margin:0;">
				<div style="width:980px;box-shadow:0px 2px 6px rgba(100, 100, 100, 0.3);background:#fff;">
					<textarea autofocus="autofocus" name="the_note" onkeydown="note_change();" ><?php
						if( $use_sql == false ){
							echo file_get_contents("NoteData/".$_GET['n']); 
						}else{
							$sql_return = mysql_query("SELECT content FROM ".$sql_table." WHERE ID='".$_GET['n']."'");

							$the_content = mysql_fetch_array($sql_return);
							echo $the_content['content'];
						}
					?></textarea>
				</div>
				<input type="hidden" name="save" value="yes" />
			</form>

			<form action="?n=<?php echo $_GET['n']; ?>" method="post" id="passwd-set-form" style="display:none;margin:0;">
				<input type="password" name="the_set_passwd" placeholder="新密码" class="input" style="width:870px;margin:20px 0 0 0;" />
				<input type="submit" value="设置" class="btn" style="float:right;margin:20px 0 0 0" />
			</form>

			<form action="?n=<?php echo $_GET['n']; ?>" method="post" id="passwd-delete-form" style="display:none;margin:0;">
				<input type="hidden" name="delete_passwd" value="yes" />
			</form>

			<button id="save-form" style="margin:20px 0 0 0;float:right;background:#58BCFF;color:#fff;" class="btn" onclick="$('#note-form').submit();">保存</button>

			<button id="save-ajax" style="margin:20px 0 0 0;float:right;background:#58BCFF;color:#fff;display:none;" class="btn" onclick="ajax_save();">保存</button>
			
			<?php if(!$passwd) : ?>
				<button class="btn" style="margin:20px 0 0 0;" onclick="psaawd_set_display();">设置密码</button>
			<?php else : ?>
				<button class="btn" style="margin:20px 0 0 0;" onclick="$('#passwd-delete-form').submit();">删除密码</button>
			<?php endif; ?>

			<button style="margin:20px 0 0 20px;" class="btn" onclick="$('#show_url_background').fadeIn();">在其它设备上访问</button>

		<?php else : ?>

			<div style="clear:both;"></div>

			<div style="width:480px;height:550px;" class="homediv">

				<h2 style="margin:20px 0 0 20px;">还没有记事本?</h2>

				<span class="icon icon-mid">
					<span class="icon-plus"></span>
				</span>

				<form action="?new=yes" method="post">
					<button style="margin:419px 0 0 20px;background:#58BCFF;color:#fff;font-size:24px;padding:9px 154px 9px 154px;" class="btn">立刻创建</button>
				</form>

			</div>

			<div style="width:480px;height:550px;float:right;" class="homediv">

				<h2 style="margin:20px 0 0 20px;">已有记事本</h2>

				<span class="icon icon-mid">
					<span class="icon-file"></span>
				</span>

				<form action=" " method="get">
					<input name="n" type="text" class="input" autofocus="autofocus" placeholder="记事本ID" style="margin:419px 0px 0px 20px;font-size:23px;width:255px;background:#C6E8FF;" />
					<button style="margin:419px 35px 0 0;background:#58BCFF;color:#fff;font-size:24px;padding:9px 30px 9px 30px;float:right;" class="btn">访问</button>
				</form>

			</div>

		<?php endif; ?>
	</body>
