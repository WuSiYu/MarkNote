<?php

	//MarkNote 轻量级云记事本系统

	//功能:
	// 1. 以文件或数据库的方式保存记事本
	// 2. 支持MarkDown(即时预览+优化的textarea)和纯文本两种格式的记事本
	// 3. 可以给记事本设置密码
	// 4. 可生成记事本的二维码,以方便手机用户
	// 5. 可将记事本下载到本地
	// 6. 可以将Markdown记事本一键生成网页
	// 7. 支持伪静态(http://233333.net/记事本名),仅限apache,默认开启,若环境不支持请关闭
	// 8. 支持使用任意英文和数字作为ID

	//== Server Config =====================

	define('MD5_SALT', 'faowifankjsnvlaiuwef2480rasdlkvj');			//加密记事本密码时, 所使用的盐, 请一定要修改为自己设置的
	define('MARK_DOWN_TYPE', '<<<-- MarkDown Type Note -->>>');		//Markdown 格式的标记
	define('NOTE_DATA', __DIR__ . '/NoteData/');					//MarkNote的数据目录
	define('NOTE_PASSWD_FILE', NOTE_DATA . 'passwd.data');			//MarkNote的密码存储文件

	$use_sql = false;						//是否使用MySQL来存储记事本

	$rewrite_create_htaccess_file = true;	//是否创建.htaccess文件以尝试实现伪静态

	$rewrite_use_better_url = true;			//是否使用伪静态后的URL(如/记事本名),若环境不支持伪静态则不要开启
	//======================================


	//== SQL Config ========================

	$sql_host	= "localhost";	//MySQL服务器地址

	$sql_user	= "root";		//MySQL用户名

	$sql_passwd	= "";			//MySQL密码

	$sql_name	= "marknote";	//MarkNote使用的数据库名

	$sql_table	= "note_data";	//MarkNote使用的表名(自动创建)
	//======================================

	function show_error_exit($output){
		//输出错误信息并终止
		echo '<!DOCTYPE html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>';
		echo '<body style="background-color:#eee;margin:8px;">';
		echo '<div style="padding:10px;margin:0;font-size:14px;color:#555;background:#fff;border:0;box-shadow:0px 2px 6px rgba(100, 100, 100, 0.3);">';
		echo $output;
		echo '</div></body></html>';
		exit();
	}

	//显示输入密码框并终止
	function show_input_passwd(){?>
<!DOCTYPE html>
	<head>
		<meta charset="utf-8" />
		<title>输入密码</title>
		<style type="text/css">
			body{
				background:#eee;width:500px;margin:20px auto 20px auto;
			}
			#input-passwd{
				font-size:14px;width:400px;padding:10px;margin:0;font-size:14px;color:#555;background:#fff;border:0;box-shadow:0px 2px 6px rgba(100, 100, 100, 0.3);
			}
			#input-submit{
				font-size:14px;padding:9px 20px;color:#555;background:#fff;border:0;box-shadow:0px 2px 6px rgba(100, 100, 100, 0.3);cursor:pointer;
			}

			@media screen and (max-width: 500px){
				body{
					width:100%;
					padding: 20px;
					margin: 0;
				}
				form{
					width:100%;
				}
				#input-passwd{
					width: 70%;
				}
				#input-submit{
					width: 20%;
				}
			}

		</style>
	</head>
	<body>
		<h3 style="font-weight:400;">请输入密码</h3>
		<form action="<?php echo_note_url(); ?>" method="post">
			<input id="input-passwd" type="password" name="GiveYouPasswd" placeholder="密码" style=""/>
			<input id="input-submit" type="submit" value="提交" style="" />
		</form>
	</body>
</html>
		<?php
		exit();
	}

	function echo_note_url(){
		global $rewrite_use_better_url, $noteId;

		echo ($rewrite_use_better_url ? '' : '?n=') . $noteId;
	}

	function reLocation($url){
		global $rewrite_use_better_url;

		header("Location: ". ($rewrite_use_better_url ? '' : '?n=') . $url);

		exit();
	}

	function encrypt_pass($noteId, $password){
		return md5(MD5_SALT . $noteId . 'MyNote' . $password . 'Let-It-More-Lang');
	}

	// -----程序从这里开始-----

	// error_reporting(0);

	//判断是否是第一次使用

	if( !$use_sql ){
		if( !file_exists(NOTE_DATA) ){
			mkdir(NOTE_DATA);
			if( !file_exists(NOTE_DATA)){
				show_error_exit('服务器端错误：无法创建文件,请检查文件系统权限');
			}
			touch(NOTE_DATA . 'index.html');
			touch(NOTE_PASSWD_FILE);
		}else if( !is_dir(NOTE_DATA) ){
			show_error_exit('服务器端错误：错误的数据目录类型.');
		}
	}else{
		$notesql = mysqli_connect($sql_host, $sql_user, $sql_passwd, $sql_name);
		// mysql_select_db($sql_name, $notesql);

		if( !mysqli_query($notesql,"SELECT * FROM ".$sql_table) ){

			$is_ok = mysqli_query($notesql,"CREATE TABLE ".$sql_table." (
				num int NOT NULL AUTO_INCREMENT,
				PRIMARY KEY(num),
				ID tinytext,
				passwd tinytext,
				content longtext
			)");

			if(!$is_ok){
				show_error_exit("服务器端错误：无法创建数据库表,请修正数据库连接信息或使用文件存储方式");
			}
		}
	}

	//创建伪静态
	if( !file_exists(".htaccess") && $rewrite_create_htaccess_file ){
		$htaccess_file_content =
"### MarkNote RewriteRule start
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteRule ^([a-zA-Z0-9]+)$ index.php?n=$1
	RewriteRule ^([a-zA-Z0-9]+).html$ index.php?n=$1&html=yes
</IfModule>
### MarkNote RewriteRule end
";
		file_put_contents('.htaccess', $htaccess_file_content);
	}

	$noteId = @$_GET['n'];
	$noteTitle = '新建';
	$JavaScript = '';//用于保存需要在页面中输出的 javascript 代码, 以修正在文档开头输出 <script> 标签的问题

	$passwd = false;//代表当前的Note是否有密码
	$note_content_to_show = '';//记事本的默认内容

	//开始处理记事本
	if( $noteId == '' ){
		//如果访问主页

		$url = '';
		$isNew = @$_GET['new'] === 'yes';

		if( $isNew !== true && isset($_COOKIE['myNote']) && @$_POST['force_home'] != 'yes'){
			//如果是已保存cookie的老用户,并不是强制到主页(通过笔记本页的返回主页按钮).则取出记事本ID,并跳转根据ID跳转到笔记本页
			$url = $_COOKIE['myNote'];
		}else{

			$page_type = 'home'; //设置是主页标记为真(在最后生成页面时作判断用)

			if( $isNew ){
				//以当前的时间(带毫秒)再加上随机数生成唯一的(理论上) noteId
				$url = md5(microtime(1) . mt_rand());
			}
		}

		if($url !== ''){
			reLocation($url);
		}
	}else{
		//如果指定了ID
		if( !preg_match('/^[A-Za-z0-9]+$/', $noteId) || strlen($noteId) < 3 || strlen($noteId) > 200){
			//如果ID不符合规范
			show_error_exit("错误：输入的ID不合法");
		}

		$noteTitle = $noteId;

		setcookie("myNote", $noteId, time()+31536000000);

		//判断是否已有笔记本
		if( !$use_sql ){
			$this_ID_have_note = file_exists(NOTE_DATA . $noteId);
		}else{
			$sql_return = mysqli_query($notesql,"SELECT ID, content FROM ".$sql_table." WHERE ID='". $noteId ."'");
			$the_content = mysqli_fetch_array($sql_return);

			$this_ID_have_note = isset($the_content['ID']) && $the_content['ID'];
		}

		if( $this_ID_have_note ){
			//如果ID已有笔记

			$page_type = 'text_note';

			//处理输入的密码
			if( isset($_POST['GiveYouPasswd']) ){
				//如果输入了密码
				setcookie("myNodePasswdFor". $noteId, $_POST['GiveYouPasswd'], time()+3600);
				reLocation($noteId);
			}

			$realpasswd = '';

			//检查是这个ID是否有密码
			if( !$use_sql ){

				//打开密码文件
				$passwd_file = fopen(NOTE_PASSWD_FILE, 'r');

				//读取密码文件
				while( !feof($passwd_file) ){
					//读取一行
					$passwd_file_this_line = fgets($passwd_file);

					//把这行分为两段
					$this_line_array = explode(" ", $passwd_file_this_line);

					if( $this_line_array[0] === $noteId ){
						//如果这个ID有密码并在这一行中

						//有密码标记为真
						$passwd = true;

						$realpasswd = $this_line_array[1];

						//找到密码后, 不再往后找了
						break;
					}
				}

				fclose($passwd_file);
			}else{
				$sql_return = mysqli_query($notesql, "SELECT passwd FROM ".$sql_table." WHERE ID='". $noteId ."'");
				$the_passwd = mysqli_fetch_array($sql_return);
				if( isset($the_passwd['passwd']) && $the_passwd['passwd'] ){
					//有密码标记为真
					$passwd = true;

					$realpasswd = $the_passwd['passwd'];
				}
			}

			//当前的 note 有密码标记
			if($passwd){
				//从Cookie获取密码
				$password = @$_COOKIE['myNodePasswdFor'.$noteId];

				//密码不正确或者未输入, 则显示密码输入框
				if( encrypt_pass($noteId, $password) !== trim($realpasswd) ) {
					show_input_passwd();
				}

				//当前 note 有密码时, 才处理 删除密码的逻辑, 否则 不处理, 因为没有密码, 不需要删除密码
				if( isset($_POST['delete_passwd']) ){

					if( !$use_sql ){

						$passwd_file = fopen(NOTE_PASSWD_FILE, 'a+');

						//读取密码文件
						while( !feof($passwd_file) ){
							//读取一行
							$passwd_file_this_line = fgets($passwd_file);

							//把这行分为两段
							$this_line_array = explode(" ",$passwd_file_this_line);

							if( $this_line_array[0] === $noteId ){
								//如果这个ID有密码并在这一行中
								$passwd_file_content = file_get_contents(NOTE_PASSWD_FILE);
								$passwd_file_content_part_1 = substr($passwd_file_content,0,ftell($passwd_file)-strlen($passwd_file_this_line) );
								$passwd_file_content_part_2 = substr($passwd_file_content,ftell($passwd_file));
								file_put_contents(NOTE_PASSWD_FILE, $passwd_file_content_part_1.$passwd_file_content_part_2);
								//有密码标记为假
								$passwd = false;
								break;
							}
						}
						//关闭文件
						fclose($passwd_file);
					}else{
						mysqli_query($notesql,"UPDATE ".$sql_table." SET passwd = '' WHERE ID = '".$noteId."'");
						//有密码标记为假
						$passwd = false;
					}

					//密码删除成功
					if($passwd === false){
						//删除Cookie
						setcookie("myNodePasswdFor".$noteId, '', time()-1);
						//提示信息
						$JavaScript = "alert('密码已删除');";
					}
				}
			}else{
				//没有密码时, 才处理 设置密码的逻辑, 否则单独多次提交设置密码逻辑, 在使用 文件模式时, 会导致文件里同一 noteId 出现多条密码的情况
				if( isset($_POST['the_set_passwd']) ){

					//如果要设置密码
					$password = $_POST['the_set_passwd'];

					//密码长度至少 6 位
					if(strlen($password) > 5){
						$mpass = encrypt_pass($noteId, $password);

						if( !$use_sql ){
							//打开密码文件
							$passwd_file = fopen(NOTE_PASSWD_FILE, 'a+');

							//写入密码信息
							fputs($passwd_file, $noteId.' '.$mpass);
							fputs($passwd_file, "\n");
							fclose($passwd_file);
						}else{
							mysqli_query($notesql,"UPDATE ".$sql_table." SET passwd = '". $mpass ."' WHERE ID = '".$noteId."'");
						}

						//设置Cookie
						setcookie("myNodePasswdFor".$noteId, $password, time()+3600);
						//提示信息
						$JavaScript = "alert('密码已设置');";

						//有密码标记为真
						$passwd = true;
					}
				}
			}

			if(
				isset($_POST['the_note']) && //有POST过来的 记事本 内容
				(
					isset($_POST['save']) || @$_POST['ajax_save'] === 'yes'
				)
			){

				$to_save_raw = $_POST['the_note'];

				if( @$_POST['note_type'] == 'md_note' ){
					$to_save_raw = MARK_DOWN_TYPE . $to_save_raw;
				}

				if( !$use_sql ){
					file_put_contents(NOTE_DATA . $noteId, $to_save_raw);
				}else{
					$to_save_tmp = $to_save_raw;
					$to_save_tmp = str_replace("&", "&amp;",$to_save_tmp);
					// $to_save_tmp = str_replace("<", "&lt;",$to_save_tmp);
					// $to_save_tmp = str_replace(">", "&gt;",$to_save_tmp);
					$to_save_tmp = str_replace("'", "&#39;",$to_save_tmp);
					$to_save_tmp = str_replace("\"", "&#42;",$to_save_tmp);
					$to_save_tmp = str_replace("=", "&#61;",$to_save_tmp);
					$to_save_tmp = str_replace("?", "&#63;",$to_save_tmp);
					mysqli_query($notesql,"UPDATE ".$sql_table." SET content = '".$to_save_tmp."' WHERE ID = '".$noteId."'");
				}

				if(@$_POST['ajax_save'] === 'yes'){
					echo "ok";
					//使用ajax时无需再输出HTML,任务已完成,终止执行.
					exit();
				}
			}

			if( !$use_sql ){
				$note_content_to_show = file_get_contents(NOTE_DATA . $noteId);
			}else{
				//直接使用上面查询出来的结果, 不再重新查询
				$note_content_to_show = $the_content['content'];
			}

			//如果内容里包含 MarkDown 的特定标记, 则自动将标记移除
			if( strpos($note_content_to_show, MARK_DOWN_TYPE) === 0 ){
				$page_type = 'md_note';
				$note_content_to_show = substr($note_content_to_show, strlen(MARK_DOWN_TYPE));
			}

			if( @$_GET['html'] === 'yes' ){
				$page_type = 'html';
			}
		}else{
			//如果是新记事本
			$page_type = 'select_note_type';//默认值

			if( isset($_POST['type']) ){

				$IsMd = $_POST['type'] === 'md';//是否为新建 MarkDown 格式的记事本


				$note_content_to_show = $IsMd ? (MARK_DOWN_TYPE . '#MarkDown格式记事本
- - -
在**右侧**编辑记事本，会在**左侧**显示效果。') : '';

				//创建新新文件

				if( !$use_sql ){
					$note_file = NOTE_DATA . $noteId;
					if( $IsMd ){
						file_put_contents($note_file, $note_content_to_show);
					}else{
						touch($note_file);
					}
				}else{
					mysqli_query($notesql, "INSERT INTO ".$sql_table." (ID, passwd, content) VALUES ('".$noteId."','','".$note_content_to_show."')");
				}

				$passwd = false;

				$page_type = $IsMd ? 'md_note' : 'text_note';

				//因为MarkDown格式的内容开头有特写的标记,所以此处要将它移除
				if($IsMd){
					$note_content_to_show = substr($note_content_to_show, strlen(MARK_DOWN_TYPE));
				}
			}
		}

		//下载时的 文件名
		$filename = '记事本-' . $noteId . '.' . (( $page_type == 'md_note' ) ? 'md' : 'txt');
	}
?>
<!DOCTYPE html>
	<head>
		<meta charset="utf-8" />
		<title>记事本 › <?php echo $noteTitle; ?></title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
		<link href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAABWVBMVEVhVTVSSjVORzROSDRQSTVfVDVcUTVLRTRHQTRHQjRIQzRZUDVgVDVPSDRMRjRdUjXIojrGoTvFoDvHojv8yD37yT37yD37yD32xT72xT71xT71xT7QsVPOsVTOsFTPsVPwwULvwkLvwULvwkLRsVLPsVPPsVPQsVPqvkXpv0XpvkXpvkXWtFDVtFDUs1DVtFDguUrgukrfuUrfuUrfuUrVtFDUtFDUtFDrv0TrwETqv0Tqv0T6xzz6yDz5vDv3rDr6yDz6xzz6xzz6xzxLRDRLRTTFmTrFjznFoTvFoDr6vjz4rjv7yT37yD30uz3yqzz2xT71xT7NqFPLm1LOsFTuuEHsqD/vwkLvwULOqFLMm1HPsVPotUTmpkPpv0XpvkXTq0/Snk7UtFDUs1DesEndokjfukrfuUresErcokjfuUvTq1DRnU7UtFHptUPop0LrwETqv0T////ZQ5XYAAAAAWJLR0QAiAUdSAAAAAlwSFlzAAAN1wAADdcBQiibeAAAAAd0SU1FB98FCA0SEE9zUCEAAAA2SURBVBjTY+BEAwwYAqKi7qLIAFNAUNBdEBlg1yIGBXi0iCEABbbg14JkB0QLlOPo6OgkKgoAn/UWJhIEn78AAAAASUVORK5CYII=" type="image/x-icon" rel="icon" />
		<script src="http://cdn.bootcss.com/jquery/2.1.1/jquery.min.js"></script>
<?php if ( $page_type == 'html' ) : ?>
		<script src="http://cdn.bootcss.com/markdown.js/0.5.0/markdown.min.js"></script>
		<script src="http://cdn.bootcss.com/prism/0.0.1/prism.min.js"></script>
		<link href="http://cdn.bootcss.com/prism/0.0.1/prism.min.css" rel="stylesheet">
		<style type="text/css">
			body{
				font-size: 14px;
				font-family: '文泉驛正黑','Microsoft yahei UI','Microsoft yahei','微软雅黑',"Lato",Helvetica,Arial,sans-serif !important;
				background: #eee;
				width: 1100px;
				margin: 0px auto 10px auto;
				color: #34495E;
			}
			h1{
				color: #3498db;
			}
			#html-box{
				box-shadow: 0px 2px 6px rgba(100, 100, 100, 0.3);
				background-color: #fff;
				padding: 20px;
				margin: 50px 0;
			}
			#html-box p{
				margin: 15px 0;
			}
			#html-box h2{
				border-bottom:solid 2px #ddd;
				margin-bottom: 5px;
			}
			#html-box blockquote{
				border: solid 2px #eee;
				padding: 0 10px;
			}
			#html-box pre{
				margin: 5px 0;
				padding: 5px;
				background-color: #F2F2F5;
				font-family: "Menlo","Liberation Mono","Consolas","DejaVu Sans Mono","Ubuntu Mono","Courier New","andale mono","lucida console",monospace !important;
			}
			#html-box pre code{
				background-color: #F2F2F5;
				overflow: auto;
			}
			#html-box hr{
				border: 1px solid #888;
			}
			#html-box code{
				background-color: #ddd;
				padding: 2px;
				font-family: "Menlo","Liberation Mono","Consolas","DejaVu Sans Mono","Ubuntu Mono","Courier New","andale mono","lucida console",monospace !important;
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
				background-color: #eee;
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
			@media screen and (max-width: 1140px){
				body{
					margin: 0 20px 0 20px;
					width: auto;
				}
			}
		</style>
		<div id="html-box"><?php echo htmlentities($note_content_to_show); ?></div>
		<script type="text/javascript">
			document.getElementById("html-box").innerHTML = markdown.toHTML($("#html-box").text());
			$("#html-box a").attr("target","_blank");
			codes=$("#html-box pre code");
			langs={"[html code]":"language-markup","[javascript code]":"language-javascript","[js code]":"language-javascript","[css code]":"language-css",
				"[python code]":"language-python","[php code]":"language-php","[perl code]":"language-perl",
				"[c code]":"language-c","[c++ code]":"language-cpp","[c# code]":"language-csharp",
				"[java code]":"language-java","[go code]":"language-go","[ruby code]":"language-ruby",
				"[markdown code]":"language-markdown","[less code]":"language-less","[ini code]":"language-ini"
			}
			for(var x=0;x<codes.length;x++){
				first_line=codes[x].innerHTML.split('\n',1)[0];
				first_line_lower=first_line.toLowerCase()
				codes[x].className="language-markup";
				var l='';
				for(l in langs){
					if(first_line_lower==l){
						codes[x].innerHTML=codes[x].innerHTML.split(first_line+'\n',2)[1];
						codes[x].className=langs[l];
					}
				}
			}
			Prism.highlightAll();
		</script>
<?php exit(); endif; ?>
		<script type="text/javascript">
			var is_passwd_set_show = false;
			var is_need_save = false;
			var is_pic_loaded = false;

			$(document).ready(function(){
				$("#note-btns-save-form").hide();
				$("#note-btns-save-ajax").show();
				$("#note-btns-save-ajax").css({"background-color":"#ccc","cursor":"default","padding":"9px 20px"}).html("已保存");

				$('#note-btns-setpasswd-form-btn').click(function(){
					if(($('#note-btns-setpasswd-form-input').val()+'').length < 6){
						alert('请输入密码, 长度至少六位!');
						return false;
					}
				});

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
					var edit_width = box_width / 2;
					$("#note-md-show").height(winh-160).width( box_width - edit_width - 35 );
					$("#note-md-edit").height(winh-160).width(edit_width).css("margin-left",box_width - edit_width - 10);
					$("#note-md-move").height(winh-150).css("left",winw - (winw - box_width)/2 - edit_width - 20);
				<?php endif; ?>
			});

<?php
if($JavaScript !== ''){
	echo $JavaScript;
}?>

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
					var edit_width = box_width / 2;
					if( is_passwd_set_show ){
						$("#note-md-show").height(winh-217).width( box_width - edit_width - 35 );
						$("#note-md-edit").height(winh-217).width(edit_width).css("margin-left",box_width - edit_width - 10);
						$("#note-md-move").height(winh-207).css("left",winw - (winw - box_width)/2 - edit_width - 20);
					}else{
						$("#note-md-show").height(winh-160).width( box_width - edit_width - 35 );
						$("#note-md-edit").height(winh-160).width(edit_width).css("margin-left",box_width - edit_width - 10);
						$("#note-md-move").height(winh-150).css("left",winw - (winw - box_width)/2 - edit_width - 20);
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
						$("#note-md-move").animate({height:'-=57px'},500);
					<?php endif; ?>
				}else{
					$('#note-btns-passwdset-form').slideUp(500);
					$('#note-main-form-div').animate({height:'+=57px'},500);
					is_passwd_set_show = false;
					<?php if ( $page_type == 'md_note' ) : ?>
						$("#note-md-edit").animate({height:'+=57px'},500);
						$("#note-md-show").animate({height:'+=57px'},500);
						$("#note-md-move").animate({height:'+=57px'},500);
					<?php endif; ?>
				}
			}

			function ajax_save(){
				if( is_need_save ){
					$("#note-btns-save-ajax").css({"background-color":"#ccc","cursor":"wait","padding":"9px 20px"}).html("正在保存");
					$.post("<?php echo_note_url(); ?>",
					{
						ajax_save:"yes",
						the_note:$("textarea").val(),
						note_type:"<?php echo $page_type ?>"
					},
					function(data,status){
						$("#note-btns-save-ajax").css({"background-color":"#ccc","cursor":"default","padding":"9px 20px"}).html("已保存");
						is_need_save = false;
					});
				}
			}

			function note_change(){
				$("#note-btns-save-ajax").css({"background-color":"#58BCFF","cursor":"pointer","padding":"9px 20px"}).html("保存");
				is_need_save = true;
			}

			function other_dev_show(){
				$('#note-otherdev-background-div').fadeIn();
				if(!is_pic_loaded){
					$('#note-otherdev-img-add').after("<img alt='Loading...' src='http://qr.liantu.com/api.php?m=0&fg=222222&w=240&text=<?php echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>'>");
				is_pic_loaded = true;
				}
			}

			function download_note(){
				$('#download-a').attr({
					"download" : "<?php echo $filename; ?>",
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

				var is_focus = true;
				$(document).ready(function(){
					the_textarea = $('textarea');
					the_textarea.focus(function(){is_focus=true;});
					the_textarea.blur(function(){is_focus=false;});
				});

				$(document).keydown(function(e){
					the_textarea = $('textarea');

					if(is_focus){
						if( e.which == 9 ){
							var cursor_pos = the_textarea.getCursorPosition();
							the_textarea.val(the_textarea.val().slice(0,cursor_pos)+'\t'+the_textarea.val().slice(cursor_pos));
							<?php if ( $page_type == 'md_note' ) : ?>
								document.getElementById("note-md-edit").focus();
								document.getElementById("note-md-edit").setSelectionRange(cursor_pos+1,cursor_pos+1);
							<?php else : ?>
								document.getElementById("note-text-edit").focus();
								document.getElementById("note-text-edit").setSelectionRange(cursor_pos+1,cursor_pos+1);
							<?php endif; ?>
							return false;
						}
						if( e.which == 13 ){
							var cursor_pos = the_textarea.getCursorPosition();
							var notelines = the_textarea.val().slice(0,cursor_pos).split('\n');
							var listline = notelines[notelines.length-1];
							var ntab = 0,nsp = 0;
							while(listline[ntab]=='\t'){
								ntab+=1;
							}
							while( listline[nsp]==' ' && listline[nsp+1]==' ' && listline[nsp+2]==' ' && listline[nsp+3]==' '){
								nsp+=4;
							}
							the_textarea.val(the_textarea.val().slice(0,cursor_pos)+'\n'+the_textarea.val().slice(cursor_pos));
							for (i=ntab; i>0; i--){
								the_textarea.val(the_textarea.val().slice(0,cursor_pos+1)+'\t'+the_textarea.val().slice(cursor_pos+1));
							}
							for (i=nsp; i>0; i--){
								the_textarea.val(the_textarea.val().slice(0,cursor_pos+1)+' '+the_textarea.val().slice(cursor_pos+1));
							}
							<?php if ( $page_type == 'md_note' ) : ?>
								document.getElementById("note-md-edit").focus();
								document.getElementById("note-md-edit").setSelectionRange(cursor_pos+ntab+nsp+1,cursor_pos+ntab+nsp+1);
							<?php else : ?>
								document.getElementById("note-text-edit").focus();
								document.getElementById("note-text-edit").setSelectionRange(cursor_pos+ntab+nsp+1,cursor_pos+ntab+nsp+1);
							<?php endif; ?>
							return false;
						}
					}
				});
			<?php endif; ?>
		</script>
		<style type="text/css">
			/***** 全局 *****/

			body{
				color: #555;
				font-size: 14px;
				font-family: '文泉驛正黑','Microsoft yahei UI','Microsoft yahei','微软雅黑',"Lato",Helvetica,Arial,sans-serif !important;
				background: #eee;
				width: 1100px;
				margin: 0px auto 10px auto;
			}

			input,button{
				outline: none !important;
				-webkit-appearance:none;
				border-radius: 0;
				font-family: '文泉驛正黑','Microsoft yahei UI','Microsoft yahei','微软雅黑',"Lato",Helvetica,Arial,sans-serif !important;

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
				background-color: #eee;
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
				background: #f8f8f8;
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

		</style>
	</head>
	<body>
		<!-- 强制主页表单 -->
		<form action="./" method="post" style="display:none;" id="force-home-form">
			<input type="hidden" name="force_home" value="yes">
		</form>
		<!-- MarkNote标题 && 返回主页按钮 -->
		<h1 title="首页" style="margin:8px 0 8px 0;display:inline-block;background:#eee;font-size:28px;color:#555;border:0;padding:0;diaplay:inline-block;cursor:pointer;" onclick="$('#force-home-form').submit();" >MarkNote</h1>

		<!-- 记事本编辑页共用-1 -->
		<?php if( $page_type == 'text_note' || $page_type == 'md_note' ) : ?>
			<style type="text/css">
				#note-btns-save-ajax{
					float: right;
					background: #58BCFF;
					color: #fff;
				}

				textarea{
					line-height: 17px;
					tab-size: 4;-moz-tab-size: 4;-o-tab-size: 4;
					padding: 0;
					margin: 0;
					color: #555;background:#fff;
					border: 0;
					resize: none;
					font-size: 16px;
					font-family: "Menlo","Liberation Mono","Consolas","DejaVu Sans Mono","Ubuntu Mono","Courier New","andale mono","lucida console",'文泉驛正黑','Microsoft yahei UI','Microsoft yahei','微软雅黑',"Lato",Helvetica,Arial,sans-serif !important;
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

				@media screen and (max-width: 650px){

					#note-btns-otherdev-btn{
						display: none;
					}
				}

				@media screen and (max-width: 460px){

					#note-btns-download-btn{
						display: none;
					}
				}

				@media screen and (max-width: 420px){

					#note-btns-tohtml-btn{
						display: none;
					}
				}

			</style>

			<!-- [在其他设备上访问此记事本]对话框 -->
	 		<div id="note-otherdev-background-div" style="display:none;">
				<div id="note-otherdev-div">
					<div style="background:#eee;padding:10px 0px 8px 10px;"><h4>在其他设备上访问此记事本</h4></div>

					<div class="note-otherdev-div-divhr" style="margin-bottom:8px;"></div>

					<span style="margin-left:10px;">记事本ID: <strong><?php echo $noteId; ?></strong></span>

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
			<form action="<?php echo_note_url(); ?>" method="post" id="note-main-form" style="margin:0 auto;">
				<div id="note-main-form-div">
					<div style="width:100%; height:100%">
						<textarea id="note-text-edit" autofocus="autofocus" spellcheck="false" name="the_note" oninput="note_change();" style="width:100%; height:100%"><?php echo htmlentities($note_content_to_show); ?></textarea>
					</div>
				</div>
				<input type="hidden" name="save" value="yes" />
			</form>
		<?php endif; ?>
		<!-- MarkDown记事本编辑页 -->
		<?php if ( $page_type == 'md_note' ) : ?>
			<style type="text/css">
				#note-md-show p{
					margin: 5px 0 15px 0;
				}
				#note-md-show h2{
					border-bottom:solid 2px #ddd;
					margin-bottom: 5px;
				}
				#note-md-show blockquote{
					border: solid 2px #eee;
					padding: 0 10px;
				}
				#note-md-show pre{
					margin: 5px 0;
					padding: 5px;
					background-color: #F2F2F5;
					font-family: "Menlo","Liberation Mono","Consolas","DejaVu Sans Mono","Ubuntu Mono","Courier New","andale mono","lucida console",monospace !important;
				}
				#note-md-show pre code{
					overflow: auto;
					background-color: #F2F2F5;
					margin: 0;
					padding: 0;
				}
				#note-md-show hr{
					border: 1px solid #888;
				}
				#note-md-show code{
					text-shadow: none;
					background-color: #ddd;
					padding: 2px 5px;
					margin: 0px 2px;
					font-size: 14px;
					font-family: "Menlo","Liberation Mono","Consolas","DejaVu Sans Mono","Ubuntu Mono","Courier New","andale mono","lucida console",monospace !important;
				}

			</style>
			<script src="http://cdn.bootcss.com/markdown.js/0.5.0/markdown.min.js"></script>
			<script src="http://cdn.bootcss.com/prism/0.0.1/prism.min.js"></script>
			<link href="http://cdn.bootcss.com/prism/0.0.1/prism.min.css" rel="stylesheet">
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
			<form action="<?php echo_note_url(); ?>" method="post" id="note-main-form" style="margin:0 auto;">
				<div id="note-main-form-div">
					<div style="width:100%; height:100%">
						<div id="note-md-show" style="position: absolute;width:49%; height:100%; font-size:16px; overflow:auto; padding:5px;"></div>
						<div id="note-md-move" style="height:100%;width:5px;background-color:#eee;position: absolute;cursor: ew-resize;"></div>
						<textarea id="note-md-edit" style="position: absolute;overflow:auto;width:48%; height:100%; float:right; background-color:#fff; padding:5px; font-size:14px;" spellcheck="false" oninput="this.editor.update();note_change();" autofocus="autofocus" name="the_note" ><?php echo htmlentities($note_content_to_show); ?></textarea>
					</div>
				</div>
				<input type="hidden" name="save" value="yes" />
			</form>

			<script>
				function Editor(input, preview) {
					this.update = function () {
						preview.innerHTML = markdown.toHTML(input.value);
						$("#note-md-show a").attr("target","_blank");
						codes=$("#note-md-show pre code");
						langs={"[html code]":"language-markup","[javascript code]":"language-javascript","[js code]":"language-javascript","[css code]":"language-css",
							"[python code]":"language-python","[php code]":"language-php","[perl code]":"language-perl",
							"[c code]":"language-c","[c++ code]":"language-cpp","[c# code]":"language-csharp",
							"[java code]":"language-java","[go code]":"language-go","[ruby code]":"language-ruby",
							"[markdown code]":"language-markdown","[less code]":"language-less","[ini code]":"language-ini"
						}
						for(var x=0;x<codes.length;x++){
							first_line=codes[x].innerHTML.split('\n',1)[0];
							first_line_lower=first_line.toLowerCase()
							codes[x].className="language-markup";
							var l='';
							for(l in langs){
								if(first_line_lower==l){
									codes[x].innerHTML=codes[x].innerHTML.split(first_line+'\n',2)[1];
									codes[x].className=langs[l];
								}
							}
						}
						Prism.highlightAll();
					};
					input.editor = this;
					this.update();
				}
				new Editor(document.getElementById("note-md-edit"), document.getElementById("note-md-show"));
			</script>
		<?php endif; ?>
		<!-- 记事本编辑页共用-2 -->
		<?php if( $page_type == 'text_note' || $page_type == 'md_note' ) : ?>
			<form action="<?php echo_note_url(); ?>" method="post" id="note-btns-passwdset-form" style="display:none; margin-top:20px; height:37px;">
				<input id="note-btns-setpasswd-form-input" type="password" name="the_set_passwd" placeholder="新密码" class="input" style="width:870px;"/>
				<input id="note-btns-setpasswd-form-btn" type="submit" value="设置" class="btn" style="float:right;"/>
			</form>

			<form action="<?php echo_note_url(); ?>" method="post" id="note-btns-passwddelete-form" style="display:none;margin:0;">
				<input type="hidden" name="delete_passwd" value="yes" />
			</form>

			<div id="note-btns-div" style="margin:20px 0 0 0;">

				<!-- 密码 设置 && 删除 表单+按钮 -->
				<?php if($passwd) : ?>
					<button title="删除这个记事本的密码" class="btn" onclick="$('#note-btns-passwddelete-form').submit();">删除密码</button>
				<?php else : ?>
					<button title="给这个记事本设置一个密码" class="btn" onclick="psaawd_set_display();">设置密码</button>
				<?php endif; ?>

				<button title="将记事本的内容以文件的方式下载" style="margin-left:20px;" class="btn" onclick="download_note();" id="note-btns-download-btn">下载</button>

				<a id="download-a" style="display:none"></a>

				<?php if( $page_type == 'md_note' ) : ?>
					<?php if ($rewrite_use_better_url): ?>
						<a title="生成一个网页,网址可直接访问" style="margin-left:20px;text-decoration:none;" class="btn" id="note-btns-tohtml-btn" href="<?php echo $noteId; ?>.html" target="_blank">生成HTML</a>
					<?php else : ?>
						<a title="生成一个网页,网址可直接访问" style="margin-left:20px;text-decoration:none;" class="btn" id="note-btns-tohtml-btn" href="./?n=<?php echo $noteId; ?>&html=yes" target="_blank">生成HTML</a>
					<?php endif ?>
				<?php endif; ?>

				<button title="获取记事本ID并生成二维码" style="margin-left:20px;" class="btn" onclick="other_dev_show();" id="note-btns-otherdev-btn">在其它设备上访问</button>

				<!-- 对于老式浏览器的传统表单保存,在现代浏览器中会自动隐藏 -->
				<button title="也可按Ctrl+S保存" id="note-btns-save-form" class="btn" onclick="document.getElementById('note-main-form').submit();">保存</button>

				<!-- 对于现代浏览器的ajax保存,在现代浏览器中会自动显示 -->
				<button title="也可按Ctrl+S保存" id="note-btns-save-ajax" style="display:none;" class="btn" onclick="ajax_save();">保存</button>

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
						margin: 0 auto;
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
				<a title="根据这个设备上的记录来回到您的笔记本" href="<?php if($rewrite_use_better_url){echo $_COOKIE['myNote'];}else{echo '?n='.$_COOKIE['myNote'];} ?>" id="back-to-note" class="btn" >回到我的笔记</a>
			<?php endif; ?>

			<div style="clear:both;"></div>

			<div class="homediv">

				<h2>还没有记事本?</h2>

				<span class="icon icon-mid">
					<span class="icon-plus"></span>
				</span>

				<form action="?new=yes" method="post" id="home-form-new">
					<button title="使用一个随机的ID创建一个记事本" id="home-btn-new" class="btn">立刻创建</button>
				</form>

			</div>

			<div style="float:right;" class="homediv">

				<h2>已有记事本</h2>

				<span class="icon icon-mid">
					<span class="icon-file"></span>
				</span>

				<form action="" method="get" id="home-form-go">
					<input id="home-input" name="n" type="text" class="input" autofocus="autofocus" placeholder="记事本ID" />
					<button title="根据输入的记事本ID来访问记事本" id="home-btn-go" class="btn">访问</button>
				</form>

			</div>
		<?php endif; ?>
		<?php if ( $page_type == 'select_note_type' ) : ?>
			<h2 style="margin-bottom:10px;">请选择将要创建的记事本类型:</h2>

			<form id="choose-form-md" action="" method="post">
				<input type="hidden" name="type" value="md">
				<input type="hidden" name="n" value="<?php echo $noteId; ?>">
			</form>

			<form id="choose-form-text" action="" method="post">
				<input type="hidden" name="type" value="text">
				<input type="hidden" name="n" value="<?php echo $noteId; ?>">
			</form>

			<div class="btn" onclick="$('#choose-form-md').submit();" style="height:150px;margin-bottom:20px;padding:10px;">
				<h2>MarkDown格式笔记本</h2>
				<p>
					MarkDown是适合网络书写的语言，使您用极为简单的语法就能编写出样式复杂的HTML文档。<br/>
					MarkDown的语法极为简介，由符号表示。例如您写"#标题"就可以产生"&#60;h1&#62;标题&#60;/h1&#62;"的HTML
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
</html><?php if($use_sql){ mysqli_close($notesql); }?>
