<?php

	//MarkNote 轻量级云记事本系统

	//功能:
	// 1. 以文件或数据库的方式保存记事本
	// 2. 支持MarkDown(即时预览+ACE代码编辑器)和纯文本两种格式的记事本
	// 3. 可以给记事本设置密码
	// 4. 可生成记事本的二维码,以方便手机用户
	// 5. 可将记事本下载到本地
	// 6. 可以将Markdown记事本一键生成网页
	// 7. 支持伪静态(http://233333.net/记事本名),仅限apache,默认开启,若环境不支持请关闭
	// 8. 支持使用任意英文和数字作为ID
	// 9. 可随时更改记事本的ID
	// 10.Markdown记事本支持代码高亮,在代码块的首行用[Python code]这样的格式来规定语言,大写小写均可

	//=== 选项 =============================
	define('MD5_SALT', 'faowifankjsnvlaiuwef2480rasdlkvj');			//加密记事本密码时, 所使用的盐, 请一定要修改为自己设置的
	define('MARK_DOWN_TYPE', '<<<-- MarkDown Type Note -->>>');		//Markdown 格式的标记
	define('NOTE_CONFIG_FILE', 'NoteConfig.php');					//MarkNote的配置文件(在使用文件方式时,自动生成)
	if( isset( $_SERVER['OPENSHIFT_DATA_DIR'] ) )					//在使用cnpaas和openshift等平台时，需要把东西存在特点目录里
		$notedata_prefix=$_SERVER['OPENSHIFT_DATA_DIR'];
	else
		$notedata_prefix='';
	define('NOTE_DATA', $notedata_prefix.'NoteData/');				//MarkNote的数据目录(在使用文件方式时) (自动生成)
	define('NOTE_PASSWD_FILE', NOTE_DATA . 'passwd.data');			//MarkNote的密码存储文件(在使用文件方式时,自动生成)
	define('NOTE_USERS_FILE', NOTE_DATA . 'users.data');			//MarkNote的用户存储文件(在使用文件方式时,自动生成)

	$rewrite_create_htaccess_file = true;	//是否创建.htaccess文件以尝试实现伪静态
	$rewrite_use_better_url = true;			//是否使用伪静态后的URL(如 http://note.domain/记事本名),若环境不支持伪静态则不要开启
	//======================================



	function show_error_exit($output,$show_return=true){
		//输出错误信息并终止
		echo '<!DOCTYPE html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<style>body{font-family: "文泉驛正黑","Microsoft yahei UI","Microsoft yahei","微软雅黑","Lato",Helvetica,Arial,sans-serif !important;}button{border: 0;background: #3498DB;color: #fff;font-size: 16px;padding:5px 10px;box-shadow: 0px 1px 3px rgba(100, 100, 100, 0.3);}</style><title>MarkNote</title></head>';
		echo '<body style="background-color:#eee;margin:50px auto;width:800px;">';
		echo '<div style="padding:20px;margin:0;color:#555;background:#fff;border:0;box-shadow:0px 2px 6px rgba(100, 100, 100, 0.3);">';
		echo '<p style="margin:0 0 5px 0;">'.$output.'</p>';
		if($show_return) echo '<br/><button onclick="history.go(-1)">< 返回</button>';
		echo '</div>';
		echo '</body></html>';
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
		<h3 style="font-weight:400;">此记事本有密码, 请输入密码以继续访问</h3>
		<form action="<?php echo_note_url(); ?>" method="post">
			<input id="input-passwd" type="password" name="GiveYouPasswd" placeholder="密码" style=""/>
			<input id="input-submit" type="submit" value="提交" style="" />
		</form>
	</body>
</html>
		<?php
		exit();
	}

	function echo_note_url($id=0){
		global $rewrite_use_better_url, $noteId;
		if($id==0)$id=$noteId;
		echo ($rewrite_use_better_url ? '' : '?n=') . $id;
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

	// error_reporting(0);					//不显示所以错误提示

	// ini_set("display_errors", "On");		//显示所有错误提示
	// error_reporting(E_ALL);

	$the_markdown_type = str_replace("<", "&lt;",MARK_DOWN_TYPE);
	$the_markdown_type = str_replace(">", "&gt;",$the_markdown_type);

	//判断是否是第一次使用

	if( !file_exists(NOTE_CONFIG_FILE) ){
		if( !isset($_POST['mode']) ){
			show_error_exit('
				<h1 style="font-weight:100;margin:0;"">请选择记事本的存储方式</h1>
				<br/>
				<h2 style="font-weight:100;margin:0 0 5px 0;border-bottom:solid 2px #ddd;">使用文件方式</h3>
				<p style="margin: 5px 0;">点击确定以使用文件方式存储,并自动生成所需文件</p>
				<form action="" method="post">
					<input type="hidden" name="mode" value="file">
					<button>确定,使用文件方式</button>
				</form>
				<br/>
				<br/>
				<h2 style="font-weight:100;margin:0 0 5px 0;border-bottom:solid 2px #ddd;">使用MySQL方式</h3>
				<p style="margin: 5px 0;">填写数据库连接信息,并点击确定以使用MySQL方式存储</p>
				<form action="" method="post">
					<input type="hidden" name="mode" value="sql">
						<div style="margin-bottom:5px"><span style="width:400px;display:inline-block;">数据库主机</span>								<input type="text" name="sql_host" 	placeholder="Host" value="localhost" /></div>
						<div style="margin-bottom:5px"><span style="width:400px;display:inline-block;">数据库用户</span>								<input type="text" name="sql_user" 	placeholder="User" value="root" /></div>
						<div style="margin-bottom:5px"><span style="width:400px;display:inline-block;">密码</span>									<input type="text" name="sql_passwd" placeholder="Password" /></div>
						<div style="margin-bottom:5px"><span style="width:400px;display:inline-block;">数据库名</span>								<input type="text" name="sql_name" 	placeholder="Database Name" value="marknote" /></div>
					<button>确定,使用MySQL方式</button>
				</form>
			',false);
		}else{

			if( $_POST['mode']=='sql' ){
				$use_sql=true;
				$sql_host=$_POST['sql_host'];
				$sql_user=$_POST['sql_user'];
				$sql_passwd=$_POST['sql_passwd'];
				$sql_name=$_POST['sql_name'];
				$sql_table='note_data';
				$sql_table_user='note_user';
			}else{
				$use_sql=false;
			}

			if( !$use_sql ){
				if( !file_exists(NOTE_DATA) ){
					mkdir(NOTE_DATA);
					if( !file_exists(NOTE_DATA)){
						show_error_exit('服务器端错误：无法创建文件,请检查文件系统权限');
					}
					touch(NOTE_DATA . 'index.html');
					touch(NOTE_PASSWD_FILE);
					touch(NOTE_USERS_FILE);
				}else if( !is_dir(NOTE_DATA) ){
					show_error_exit('服务器端错误：错误的数据目录类型.');
				}
			}else{
				$notesql = mysqli_connect($sql_host, $sql_user, $sql_passwd, $sql_name);
				if(!$notesql) show_error_exit("服务器端错误：无法连接数据库,请修正数据库连接信息或使用文件存储方式");

				if( !mysqli_query($notesql,"SELECT * FROM ".$sql_table) ){

					$is_ok = mysqli_query($notesql,"CREATE TABLE ".$sql_table." (
						num int NOT NULL AUTO_INCREMENT,
						PRIMARY KEY(num),
						ID tinytext,
						passwd tinytext,
						content longtext
					)");

					if(!$is_ok) show_error_exit("服务器端错误：无法创建数据库表,请修正数据库连接信息或使用文件存储方式");
				}

				if( !mysqli_query($notesql,"SELECT * FROM ".$sql_table_user) ){

					$is_ok = mysqli_query($notesql,"CREATE TABLE ".$sql_table_user." (
						num int NOT NULL AUTO_INCREMENT,
						PRIMARY KEY(num),
						username tinytext,
						notes longtext
					)");

					if(!$is_ok) show_error_exit("服务器端错误：无法创建数据库表,请修正数据库连接信息或使用文件存储方式");
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

			if( !$use_sql ){
				$to_config_file=
'<?php
	$use_sql=false;
?>
';
			}else{
				$to_config_file=
'<?php
	$use_sql=true;
	$sql_host="'.$sql_host.'";
	$sql_user="'.$sql_user.'";
	$sql_passwd="'.$sql_passwd.'";
	$sql_name="'.$sql_name.'";
	$sql_table="'.$sql_table.'";
	$sql_table_user="'.$sql_table_user.'";
?>
';
			}
			file_put_contents(NOTE_CONFIG_FILE, $to_config_file);
			header("Location: ");
		}
	}else{
		require NOTE_CONFIG_FILE;
	}

	if($use_sql){
		$notesql = mysqli_connect($sql_host, $sql_user, $sql_passwd, $sql_name);
		if(!$notesql) show_error_exit('无法连接数据库');
	}

	$noteId = @$_GET['n'];
	$noteTitle = '新建';
	$JavaScript = '';//需要在页面中输出的 javascript 代码

	$passwd = false;//代表当前的Note是否有密码
	$note_content_to_show = '';//记事本的默认内容

	//开始处理记事本
	if( $noteId == '' ){
		//如果访问主页

		$url = '';
		$isNew = @$_GET['new'] === 'yes';

		if( !$isNew && isset($_COOKIE['myNote']) && @$_POST['force_home'] != 'yes'){
			//如果是已保存cookie的老用户,并不是强制到主页(通过笔记本页的返回主页按钮).则取出记事本ID,并跳转根据ID跳转到笔记本页
			$url = $_COOKIE['myNote'];
		}else{

			$page_type = 'home'; //设置是主页标记为真

			if( $isNew ){
				//以当前的时间(带毫秒)再加上随机数生成唯一的(理论上) noteId
				$url = substr( md5(microtime(1) . mt_rand()) , 15);
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

		if( isset($_COOKIE['myNoteUsername']) ){
			$username = $_COOKIE['myNoteUsername'];
			$have_user = false;
			if( !$use_sql ){

				//打开密码文件
				$users_file = fopen(NOTE_USERS_FILE, 'r');

				//读取密码文件
				while( !feof($users_file) ){
					//读取一行
					$passwd_file_this_line = fgets($users_file);

					//把这行分为两段
					$this_line_array = explode(" ", $passwd_file_this_line);

					if( $this_line_array[0] === $username ){
						//如果找到用户名并在这一行中

						$have_user = true;//有USER标记为真

						$user_notes = trim($this_line_array[1]);

						//找到Username后, 不再往后找了
						break;
					}
				}

				fclose($users_file);
			}else{
				$sql_return = mysqli_query($notesql,"SELECT username, notes FROM ".$sql_table_user." WHERE username='". $username ."'");
				$the_user_notes = mysqli_fetch_array($sql_return);

				$have_user = isset($the_user_notes['notes']) && $the_user_notes['notes'];
				if ($have_user) {
					$user_notes=$the_user_notes['notes'];
				}
			}

			if( $have_user ){
				$user_notes_array = explode(";", $user_notes);
				if( !in_array($noteId, $user_notes_array) ){	//如果ID没有被记录
					$user_notes = $user_notes.';'.$noteId;
					if( !$use_sql ){
						$users_file = fopen(NOTE_USERS_FILE, 'a+');

						//读取密码文件
						while( !feof($users_file) ){
							//读取一行
							$users_file_this_line = fgets($users_file);

							//把这行分为两段
							$this_line_array = explode(" ",$users_file_this_line);

							if( $this_line_array[0] === $username ){
								//如果这个ID有密码并在这一行中
								$users_file_content = file_get_contents(NOTE_USERS_FILE);
								$users_file_content_part_1 = substr($users_file_content,0,ftell($users_file)-strlen($users_file_this_line) );
								$users_file_content_part_2 = substr($users_file_content,ftell($users_file));
								file_put_contents(NOTE_USERS_FILE, $users_file_content_part_1.$username.' '.$user_notes."\n".$users_file_content_part_2);
								break;
							}
						}
						//关闭文件
						fclose($users_file);
					}else{
						mysqli_query($notesql,"UPDATE ".$sql_table_user." SET notes = '". $user_notes ."' WHERE username = '".$username."'");
					}

				}else{
					if( isset($_POST['delete_note_in_list']) ){
						$key = array_search($noteId,$user_notes_array);
						array_splice($user_notes_array, $key, 1);
						$user_notes =  implode(";", $user_notes_array);
						if( !$use_sql ){
							$users_file = fopen(NOTE_USERS_FILE, 'a+');

							//读取密码文件
							while( !feof($users_file) ){
								//读取一行
								$users_file_this_line = fgets($users_file);

								//把这行分为两段
								$this_line_array = explode(" ",$users_file_this_line);

								if( $this_line_array[0] === $username ){
									//如果找到用户名并在这一行中
									$users_file_content = file_get_contents(NOTE_USERS_FILE);
									$users_file_content_part_1 = substr($users_file_content,0,ftell($users_file)-strlen($users_file_this_line) );
									$users_file_content_part_2 = substr($users_file_content,ftell($users_file));
									file_put_contents(NOTE_USERS_FILE, $users_file_content_part_1.$username.' '.$user_notes."\n".$users_file_content_part_2);
									break;
								}
							}
							//关闭文件
							fclose($users_file);
						}else{
							mysqli_query($notesql,"UPDATE ".$sql_table_user." SET notes = '". $user_notes ."' WHERE username = '".$username."'");
						}
						exit('ok');
					}
				}

				$user_notes_array = explode(";", $user_notes);
			}else{
				if( !$use_sql ){
					$users_file_content = file_get_contents(NOTE_USERS_FILE);
					file_put_contents(NOTE_USERS_FILE, $users_file_content.$username.' '.$noteId."\n");
				}else{
					mysqli_query($notesql, "INSERT INTO ".$sql_table_user." (username, notes) VALUES ('".$username."','".$noteId."')");
				}
			}
		}

		if( isset($_POST['delete_note_in_list']) ){ //以防万一
			exit('delete_note_in_list fail');
		}

		if( $this_ID_have_note ){
			//如果ID已有笔记
			if( !$use_sql ){
				$note_content_to_show = file_get_contents(NOTE_DATA . $noteId);
				$note_content_to_show = str_replace("&#92;", "\\",$note_content_to_show);
				$note_content_to_show = str_replace("<", "&lt;",$note_content_to_show);
				$note_content_to_show = str_replace(">", "&gt;",$note_content_to_show);
			}else{
				//直接使用上面查询出来的结果, 不再重新查询
				$note_content_to_show = $the_content['content'];
				$note_content_to_show = str_replace("&amp;", "&",$note_content_to_show);
				$note_content_to_show = str_replace("&#39;", "'",$note_content_to_show);
				$note_content_to_show = str_replace("&#42;", "\"",$note_content_to_show);
				$note_content_to_show = str_replace("&#61;", "=",$note_content_to_show);
				$note_content_to_show = str_replace("&#63;", "?",$note_content_to_show);
				$note_content_to_show = str_replace("&#92;", "\\",$note_content_to_show);
				$note_content_to_show = str_replace("<", "&lt;",$note_content_to_show);
				$note_content_to_show = str_replace(">", "&gt;",$note_content_to_show);

			}

			$page_type = 'text_note';

			//如果内容里包含 MarkDown 的特定标记, 则自动将标记移除
			if( strpos($note_content_to_show, $the_markdown_type) === 0 ){
				$page_type = 'md_note';
				$note_content_to_show = substr($note_content_to_show, strlen($the_markdown_type));
			}

			if( @$_GET['html'] === 'yes' ){
				$page_type = 'html';
			}

			if( strpos($note_content_to_show, '[公开HTML页面]') === 0 && $page_type == 'html' ){
				$no_passwd_view_html_page=true;
			}else{


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
						if(!$passwd){
							//删除Cookie
							setcookie("myNodePasswdFor".$noteId, '', time()-1);
							//提示信息
							$JavaScript = "alert('密码已删除');";
						}
					}
				}else{
					//没有密码时, 才处理设置密码的逻辑
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


				//修改ID
				if( isset($_POST['the_set_id']) ){
					$new_id = $_POST['the_set_id'];
					if( !preg_match('/^[A-Za-z0-9]+$/', $new_id) || strlen($new_id) < 3 || strlen($new_id) > 200){
						//如果ID不符合规范
						show_error_exit("错误：输入的ID不合法");
					}

					//判断新ID是否已有笔记本
					if( !$use_sql ){
						$this_ID_have_note = file_exists(NOTE_DATA . $new_id);
					}else{
						$sql_return = mysqli_query($notesql,"SELECT ID, content FROM ".$sql_table." WHERE ID='". $new_id ."'");
						$newid_the_content = mysqli_fetch_array($sql_return);

						$this_ID_have_note = isset($newid_the_content['ID']) && $newid_the_content['ID'];
					}
					if($this_ID_have_note){
						show_error_exit("错误：输入的ID已存在");
					}

					//设置新ID
					if( !$use_sql ){
						rename(NOTE_DATA.$noteId,NOTE_DATA.$new_id);
					}else{
						mysqli_query($notesql,"UPDATE ".$sql_table." SET ID = '".$new_id."' WHERE ID = '".$noteId."'");
					}

					//更新密码md5
					$new_passwd_md5 = encrypt_pass($new_id, $password);
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
								$new_passwd_line = $new_id.' '.$new_passwd_md5."\n";
								$passwd_file_content = file_get_contents(NOTE_PASSWD_FILE);
								$passwd_file_content_part_1 = substr($passwd_file_content,0,ftell($passwd_file)-strlen($passwd_file_this_line) );
								$passwd_file_content_part_2 = substr($passwd_file_content,ftell($passwd_file));
								file_put_contents(NOTE_PASSWD_FILE, $passwd_file_content_part_1.$new_passwd_line.$passwd_file_content_part_2);
								break;
							}
						}
						//关闭文件
						fclose($passwd_file);
					}else{
						mysqli_query($notesql,"UPDATE ".$sql_table." SET passwd = '".$new_passwd_md5."' WHERE ID = '".$new_id."'");
						//有密码标记为假
						$passwd = false;
					}


					//如果用户中已记录这个ID，则删除它
					if( isset($_COOKIE['myNoteUsername']) ){
						$key = array_search($noteId,$user_notes_array);
						array_splice($user_notes_array, $key, 1);
						$user_notes =  implode(";", $user_notes_array);
						if( !$use_sql ){
							$users_file = fopen(NOTE_USERS_FILE, 'a+');

							//读取密码文件
							while( !feof($users_file) ){
								//读取一行
								$users_file_this_line = fgets($users_file);

								//把这行分为两段
								$this_line_array = explode(" ",$users_file_this_line);

								if( $this_line_array[0] === $username ){
									//如果找到用户名并在这一行中
									$users_file_content = file_get_contents(NOTE_USERS_FILE);
									$users_file_content_part_1 = substr($users_file_content,0,ftell($users_file)-strlen($users_file_this_line) );
									$users_file_content_part_2 = substr($users_file_content,ftell($users_file));
									file_put_contents(NOTE_USERS_FILE, $users_file_content_part_1.$username.' '.$user_notes."\n".$users_file_content_part_2);
									break;
								}
							}
							//关闭文件
							fclose($users_file);
						}else{
							mysqli_query($notesql,"UPDATE ".$sql_table_user." SET notes = '". $user_notes ."' WHERE username = '".$username."'");
						}
					}
					reLocation($new_id);
				}

				if( isset($_POST['the_username']) ){
					$username = $_POST['the_username'];
					if( !preg_match('/^[A-Za-z0-9]+$/', $username) || strlen($username) < 3 || strlen($username) > 200){
						//如果username不符合规范
						show_error_exit("错误：输入的用户名不合法");
					}
					setcookie("myNoteUsername", $username, time()+2592000);
					reLocation($noteId);
				}

				if( isset($_POST['the_note']) && //有POST过来的 记事本 内容
					(isset($_POST['save']) || @$_POST['ajax_save'] === 'yes')
				){

					$to_save_raw = $_POST['the_note'];

					if( @$_POST['note_type'] == 'md_note' ){
						$to_save_raw = $the_markdown_type . $to_save_raw;
					}

					if( !$use_sql ){
						$to_save_raw = str_replace("<", "&lt;",$to_save_raw);
						$to_save_raw = str_replace(">", "&gt;",$to_save_raw);
						file_put_contents(NOTE_DATA . $noteId, str_replace("\\", "&#92;",$to_save_raw));
					}else{
						$to_save_tmp = $to_save_raw;
						$to_save_tmp = str_replace("&", "&amp;",$to_save_tmp);
						$to_save_tmp = str_replace("'", "&#39;",$to_save_tmp);
						$to_save_tmp = str_replace("\"", "&#42;",$to_save_tmp);
						$to_save_tmp = str_replace("=", "&#61;",$to_save_tmp);
						$to_save_tmp = str_replace("?", "&#63;",$to_save_tmp);
						$to_save_tmp = str_replace("\\", "&#92;",$to_save_tmp);
						$to_save_tmp = str_replace("<", "&lt;",$to_save_tmp);
						$to_save_tmp = str_replace(">", "&gt;",$to_save_tmp);
						mysqli_query($notesql,"UPDATE ".$sql_table." SET content = '".$to_save_tmp."' WHERE ID = '".$noteId."'");
					}

					if(@$_POST['ajax_save'] === 'yes'){
						echo "ok";
						//使用ajax时无需再输出HTML,任务已完成,终止执行.
						exit();
					}
				}

			}

		}else{
			//如果是新记事本
			$page_type = 'select_note_type';//默认值

			if( isset($_POST['type']) ){

				$IsMd = $_POST['type'] === 'md';//是否为新建 MarkDown 格式的记事本


				$note_content_to_show = $IsMd ? ($the_markdown_type . '#MarkDown格式记事本
- - -
在**右侧**编辑记事本，会在**左侧**显示效果。
') : '';

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
					$note_content_to_show = substr($note_content_to_show, strlen($the_markdown_type));
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
		<script src="//cdn.bootcss.com/jquery/2.1.1/jquery.min.js"></script>
<?php if ( $page_type == 'html' ) : ?>
		<script src="//cdn.bootcss.com/markdown.js/0.5.0/markdown.min.js"></script>
		<script src="//cdn.bootcss.com/prism/0.0.1/prism.min.js"></script>
		<link href="//cdn.bootcss.com/prism/0.0.1/prism.min.css" rel="stylesheet">
		<script src="//cdn.bootcss.com/mathjax/2.5.3/MathJax.js?config=TeX-AMS-MML_HTMLorMML"></script>
		<style type="text/css">
			body{
				font-size: 16px;
				font-family: Microsoft Yahei,Hiragino Sans GB,WenQuanYi Micro Hei,sans-serif !important;
				line-height: 27px;
				background: #eee;
				width: 1100px;
				margin: 0px auto 10px auto;
				color: #34495E;
			}
			h1{
				color: #3498db;
				font-size: 30px;
			}
			a{
				color: #3498DB;
				text-decoration: none;
			}
			img{
				max-width: 100%;
			}
			#html-box{
				box-shadow: 0px 2px 6px rgba(100, 100, 100, 0.3);
				background-color: #fff;
				padding: 20px;
				margin: 50px 0;
			}
			#html-box p{
				margin: 5px 0 15px 0;
			}
			#html-box h2{
				border-bottom:solid 2px #ddd;
				margin-bottom: 5px;
				padding-bottom: 2px;
			}
			#html-box blockquote{
				border-left: 5px solid #ccc;
				padding: 5px 0 1px 10px;
				margin: 16px 0;
				background-color: #F2F2F5;
			}
			#html-box pre{
				border-left: 5px solid #ccc;
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
				line-height: 16px;
				background-color: #ddd;
				padding: 4px 8px 2px 8px;
				margin: 0px 2px;
				font-family: "Menlo","Liberation Mono","Consolas","DejaVu Sans Mono","Ubuntu Mono","Courier New","andale mono","lucida console",monospace !important;
			}
			#html-box .MathJax_Display,#note-md-show .MathJax_Preview .MJXc-math{
				padding: 10px 0;
				background-color: #F2F2F5;
				display: block;
			}
			#html-box .MathJax_Preview span{
				font-size: 16px !important;
			}
			pre[class*=language-]>code[data-language]::before{
				border-radius: 0 !important;
			}
			#html-box .checkbox-checked{
				width: 24px;
				display:inline-block;
				height:24px;
				background:transparent url('//cdn.bootcss.com/iCheck/1.0.1/skins/square/blue.png') no-repeat scroll 0% 0%;
				background-position:-48px 0px;
				margin-bottom: -7px;
			}
			#html-box .checkbox-notchecked{
				width: 24px;
				display:inline-block;
				height:24px;
				background:transparent url('//cdn.bootcss.com/iCheck/1.0.1/skins/square/blue.png') no-repeat scroll 0% 0%;
				background-position:-24px 0px;
				margin-bottom: -7px;
			}
			:focus {
				border: none;
				outline: 0;
			}
			::selection {
				background:#3498DB;
				color:#fff;
			}
			::-moz-selection {
				background:#3498DB;
				color:#fff;
			}
			::-webkit-selection {
				background:#3498DB;
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
		<div id="html-box"><?php echo $note_content_to_show; ?></div>
		<script type="text/javascript">
			document.getElementById("html-box").innerHTML = markdown.toHTML( $("#html-box").text().replace(/\[公开HTML页面\]/g, "") );
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
			document.getElementById("html-box").innerHTML = document.getElementById("html-box").innerHTML.replace(/\[x\]/g, "<span class=\"checkbox-checked\"></span> ");
			document.getElementById("html-box").innerHTML = document.getElementById("html-box").innerHTML.replace(/\[-\]/g, "<span class=\"checkbox-checked\"></span> ");
			document.getElementById("html-box").innerHTML = document.getElementById("html-box").innerHTML.replace(/\[ \]/g, "<span class=\"checkbox-notchecked\"></span> ");
		</script>
<?php exit(); endif; ?>

		<script type="text/javascript">
			var is_passwd_set_show = false;
			var is_id_set_show = false;
			var is_login_show = false;
			var is_mynote_show = false;
			var is_menu_show = false;
			var is_set_show = false;

			var is_need_save = false;
			var is_pic_loaded = false;
			var settings = '';

			$(document).ready(function(){
				$("#note-btns-save-ajax").css({"cursor":"default"});
				$("#note-btns-save-ajax").addClass("note-btns-save-ajax-saved");
				$("#note-btns-save-ajax").css({"cursor":"default"}).html("已保存");

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

				$("#note-main-form-div").height(winh-48);
				$("textarea").height(winh-68);
				$("#note-mynote").height(winh-48);
				$("#note-menu").height(winh-48);
				$("#note-set").height(winh-48);

				$("#note-btns-setpasswd-form-input").width(winw-120);
				$("#note-btns-setid-form-input").width(winw-120);
				$("#note-btns-login-form-input").width(winw-150);


				<?php if ( $page_type == 'md_note' ) : ?>
					var box_width = winw;
					var edit_width = box_width / 2;
					$("#note-md-show").height(winh-88).width( box_width - edit_width - 95 );
					$("#note-md-edit").height(winh-48).width(edit_width).css("margin-left",box_width - edit_width);
					$("#note-md-move").height(winh-48).css("left",winw - (winw - box_width)/2 - edit_width - 10);

					MathJax.Hub.Config({
						showProcessingMessages: false,
						elements: ['note-md-show']
					});
				<?php endif; ?>

				var settings_raw = getCookie('myNoteSettings');

				if( !settings_raw ){
					setCookie('myNoteSettings','blue,14',1000000);
					settings = 'blue,14'.split(',');
				}else{
					settings = settings_raw.split(',');
				}

				change_theme(settings[0]);

			});

			<?php echo $JavaScript;?>

			//窗口大小改变时调整布局
			window.onresize = function () {
				var winh=window.innerHeight
					|| document.documentElement.clientHeight
					|| document.body.clientHeight;

				var winw=window.innerWidth
					|| document.documentElement.clientWidth
					|| document.body.clientWidth;

				if( is_passwd_set_show ){
					$("#note-main-form-div").height(winh-88);
					$("textarea").height(winh-108);
				}else{
					$("#note-main-form-div").height(winh-48);
					$("textarea").height(winh-68);
				}

				$("#note-btns-setpasswd-form-input").width($("#note-btns-passwdset-form").width()-120);
				$("#note-btns-setid-form-input").width($("#note-btns-setid-form").width()-120);
				$("#note-btns-login-form-input").width($("#note-btns-login-form").width()-150);

				$("#note-mynote").height(winh-48);
				$("#note-menu").height(winh-48);
				$("#note-set").height(winh-48);
				if(is_menu_show){
					$("#note-menu").css("left",winw-250+'px');
				}else{
					$("#note-menu").css("left",winw+'px');
				}
				if(is_set_show){
					$("#note-set").css("left",winw-250+'px');
				}else{
					$("#note-set").css("left",winw+'px');
				}


				<?php if ( $page_type == 'md_note' ) : ?>
					var box_width = winw;
					var edit_width = box_width / 2;
					if( is_passwd_set_show ){
						$("#note-md-show").height(winh-128).width( box_width - edit_width - 95 );
						$("#note-md-edit").height(winh-88).width(edit_width).css("margin-left",box_width - edit_width);
						$("#note-md-move").height(winh-88).css("left",winw - (winw - box_width)/2 - edit_width - 10);
					}else{
						$("#note-md-show").height(winh-88).width( box_width - edit_width - 95 );
						$("#note-md-edit").height(winh-48).width(edit_width).css("margin-left",box_width - edit_width);
						$("#note-md-move").height(winh-48).css("left",winw - (winw - box_width)/2 - edit_width - 10);
					}
					EditorAce.resize();
				<?php endif; ?>
			}

			function set_from_display(the_id, is_display){
				if( !is_display ){
					$(the_id).slideDown(500);
					$('#note-main-form-div').animate({height:'-=40px'},500);
					<?php if ( $page_type == 'md_note' ) : ?>
						$("#note-md-edit").animate({height:'-=40px'},500);
						$("#note-md-show").animate({height:'-=40px'},500);
						$("#note-md-move").animate({height:'-=40px'},500);
					<?php else : ?>
						$("#note-text-edit").animate({height:'-=40px'},500);
					<?php endif; ?>
					$("#note-menu").animate({height:'-=40px', top:'+=40px'},500);
					$("#note-mynote").animate({height:'-=40px', top:'+=40px'},500);
				}else{
					$(the_id).slideUp(500);
					$('#note-main-form-div').animate({height:'+=40px'},500);
					<?php if ( $page_type == 'md_note' ) : ?>
						$("#note-md-edit").animate({height:'+=40px'},500);
						$("#note-md-show").animate({height:'+=40px'},500);
						$("#note-md-move").animate({height:'+=40px'},500);
					<?php else : ?>
						$("#note-text-edit").animate({height:'+=40px'},500);
					<?php endif; ?>
					$("#note-menu").animate({height:'+=40px', top:'-=40px'},500);
					$("#note-mynote").animate({height:'+=40px', top:'-=40px'},500);
				}
			}

			//显示/隐藏 更改密码框
			function psaawd_set_display(){
				set_from_display("#note-btns-passwdset-form", is_passwd_set_show);
				is_passwd_set_show=!is_passwd_set_show;
			}

			//显示/隐藏 更改ID框
			function id_set_display(){
				set_from_display("#note-btns-setid-form", is_id_set_show);
				is_id_set_show=!is_id_set_show;
			}

			//显示/隐藏 登录框
			function login_display(){
				set_from_display("#note-btns-login-form", is_login_show);
				is_login_show=!is_login_show;
			}

			function mynote_display(){
				if( !is_mynote_show ){
					$('#note-mynote').animate({left:'0px'});
					is_mynote_show = true;
					$('#note-black').fadeIn();
				}else{
					$('#note-mynote').animate({left:'-260px'});
					is_mynote_show = false;
					$('#note-black').fadeOut();
				}
			}

			function menu_display(){
				var winw=window.innerWidth
					|| document.documentElement.clientWidth
					|| document.body.clientWidth;
				if( is_menu_show ){
					$('#note-menu').animate({left:winw+'px'});
					is_menu_show = false;
					$('#note-menu-black').fadeOut();
				}else{
					$('#note-menu').animate({left:winw-250+'px'});
					is_menu_show = true;
					$('#note-menu-black').fadeIn();
				}
			}

			function set_display(){
				var winw=window.innerWidth
					|| document.documentElement.clientWidth
					|| document.body.clientWidth;
				if( is_set_show ){
					$('#note-set').animate({left:winw+'px'});
					is_set_show = false;
					$('#note-set-black').fadeOut();
				}else{
					$('#note-set').animate({left:winw-250+'px'});
					is_set_show = true;
					$('#note-set-black').fadeIn();
				}
			}

			function setCookie(c_name,value,expiredays){
				var exdate=new Date()
				exdate.setDate(exdate.getDate()+expiredays)
				document.cookie=c_name+ "=" +escape(value)+
				((expiredays==null) ? "" : ";expires="+exdate.toGMTString())
			}

			function getCookie(c_name){
				if (document.cookie.length>0){
					c_start=document.cookie.indexOf(c_name + "=")
					if (c_start!=-1){
					    c_start=c_start + c_name.length+1
					    c_end=document.cookie.indexOf(";",c_start)
					    if (c_end==-1) c_end=document.cookie.length
					    return unescape(document.cookie.substring(c_start,c_end))
				    }
				}
				return ""
			}

			function change_theme(color){
				settings[0]=color;
				Colors=new Array();
				Colors['black']=['#000','#222'];
				Colors['blue']=['#0072C6','#0062B6'];
				Colors['default']=['#34495E','#1C3146'];
				Colors['green']=['#008A17','#007A07'];
				Colors['green2']=['#03B3B2','#03A3A2'];
				Colors['red']=['#AC193D','#9C092D'];

				$("#header").css("background-color", Colors[color][0]);
				$("#note-set").css("background-color", Colors[color][1]);
				$("#note-mynote").css("background-color", Colors[color][1]);
				$("#note-menu").css("background-color", Colors[color][1]);
				$(".menu-btn").css("background-color", Colors[color][1]);

				setCookie('myNoteSettings', settings, 1000000);
			}

			//使用ajax保存记事本
			function ajax_save(){
				if( is_need_save ){
					$("#note-btns-save-ajax").css({"background-color":"transparent", "cursor":"wait", "padding":"11px 20px 13px 20px"});
					$("#note-btns-save-ajax").css({"cursor":"wait"}).html("保存中");
					$.post("<?php echo_note_url(); ?>",
					{
						ajax_save:"yes",
						<?php if ( $page_type == 'md_note' ) : ?>
							the_note:EditorAce.getValue(),
						<?php else : ?>
							the_note:$("textarea").val(),
						<?php endif; ?>
						note_type:"<?php echo $page_type ?>"
					},
					function(data,status){
						$("#note-btns-save-ajax").css({"cursor":"default", "padding":"11px 20px 13px 20px"});
						$("#note-btns-save-ajax").addClass("note-btns-save-ajax-saved");
						$("#note-btns-save-ajax").css({"cursor":"default"}).html("已保存");
						is_need_save = false;
					});
				}
			}

			//内容改变时，已保存按钮 变成 保存
			function note_change(){
				$("#note-btns-save-ajax").css({"background-color":"#3498DB", "cursor":"pointer", "padding":"11px 28px 13px 28px"});
				$("#note-btns-save-ajax").removeClass("note-btns-save-ajax-saved");
				$("#note-btns-save-ajax").css({"cursor":"pointer"}).html("保存");
				is_need_save = true;
			}

			//显示 在其它设备上范围 对话框
			function other_dev_show(){
				$('#note-otherdev').fadeIn();
				if(!is_pic_loaded){
					$('#note-otherdev-img-add').after("<img alt='Loading...' src='//qr.liantu.com/api.php?m=0&fg=222222&w=240&text=<?php echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>'>");
				is_pic_loaded = true;
				}
			}

			//记事本的下载
			function download_note(){
				$('#download-a').attr({
					"download" : "<?php echo $filename; ?>",
					<?php if ( $page_type == 'md_note' ) : ?>
						"href" : "data:text/plain,"+EditorAce.getValue().replace(/\n/g,"%0a").replace(/\#/g,"%23")
					<?php else : ?>
						"href" : "data:text/plain,"+$("textarea").val().replace(/\n/g,"%0a").replace(/\#/g,"%23")
					<?php endif; ?>
				});
				document.getElementById("download-a").click();
			}

			function delete_note_in_list(noteid,this_btn){
				if(confirm('确定从列表中移除此记事本？\n注意:这不会真正删除此记事本，仅仅是从您的记事本列表中移除')){
					this_btn.style.cursor="wait";
					$.post("./?n="+noteid,
					{
						delete_note_in_list:"yes"
					},
					function(data,status){
						$('#note-list-'+noteid).remove();
					});
				}
				return false;
			}

			//未保存就关闭的警告
			window.onbeforeunload = onbeforeunload_handler;
			function onbeforeunload_handler(){
				if(is_need_save){
					var warning="您的记事本还没有保存，请确认您是否真的要离开。";
					return warning;
				}
			}

			//快捷键Ctrl+s,保存
			$(document).keydown(function(e){
				if( e.ctrlKey && e.which == 83 ){
					ajax_save();
					return false;
				}
			});

		</script>
		<style type="text/css">
			/***** 全局 *****/

			body{
				color: #555;
				font-size: 14px;
				font-family: Microsoft Yahei,Hiragino Sans GB,WenQuanYi Micro Hei,sans-serif !important;
				line-height: 27px;
				background: #fcfcfc;
				width: 1200px;
				margin: 0px auto 10px auto;
			}

			a,input,button{
				outline: none !important;
				-webkit-appearance: none;
				border-radius: 0;
				font-family: '文泉驛正黑','Microsoft yahei UI','Microsoft yahei','微软雅黑',"Lato",Helvetica,Arial,sans-serif !important;
			}

			button::-moz-focus-inner,input::-moz-focus-inner{
				border-color:transparent !important;
			}

			:focus {
				border: none;
				outline: 0;
			}

			::selection {
				background:#3498DB;
				color:#fff;
			}

			::-moz-selection {
				background:#3498DB;
				color:#fff;
			}

			::-webkit-selection {
				background:#3498DB;
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

			a{
				color: #3498DB;
				text-decoration: none;
			}

			img{
				max-width: 100%;
			}

			h1{
				font-size: 30px;
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
				box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.2);
				cursor: pointer;
				font-size: 14px;
				transition: background-color 0.2s;
			}

			.btn:hover{
				background: #f8f8f8;
			}

			.input{
				font-size: 14px;
				color: #555;
				background: #fff;
				border: 0;
				box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.2);
				padding: 10px;
			}

			#header{
				width: 100%;
				background-color: #34495E;
				height: 48px;
			}

			.header-title{
				display: inline-block;
				height: 24px;
				padding: 13px 16px 11px 16px;
				float: left;
				cursor: pointer;

				transition: background-color 0.2s;
			}

			.header-btn{
				display: inline-block;
				height: 48px;
				padding: 11px 21px 13px 17px;
				float: right;
				font-family: '文泉驛正黑','Microsoft yahei UI','Microsoft yahei','微软雅黑',"Lato",Helvetica,Arial,sans-serif !important;
				color: #fff;
				background-color: transparent;
				border: 0px;
				font-size: 16px;
				margin: 0;

				transition: background-color 0.2s;
			}

			.header-btn:hover, .header-title:hover{
				background-color: #0C2136;
			}

			#note-btns-save-ajax:hover{
				background-color: #2387CA;
			}

			.header-btn div{
				margin-bottom: -7px;
			}

		</style>
	</head>
	<body>
		<!-- 强制主页表单 -->
		<form action="./" method="post" style="display:none;" id="force-home-form">
			<input type="hidden" name="force_home" value="yes">
		</form>

		<!-- 记事本编辑页共用 -->
		<?php if( $page_type == 'text_note' || $page_type == 'md_note' ) : ?>
			<link href="//cdn.bootcss.com/evil-icons/1.7.6/evil-icons.min.css" rel="stylesheet">
			<script src="//cdn.bootcss.com/evil-icons/1.7.6/evil-icons.min.js"></script>
			<style type="text/css">

				html{
					overflow: hidden;
				}

				body{
					width: 100%;
				}

				#note-btns-save-ajax{
					height: 24px;
					padding: 11px 20px 13px 20px;
				}

				.note-btns-save-ajax-saved:hover{
					background-color: transparent !important;
				}

				textarea{
					line-height: 17px;
					tab-size: 4;-moz-tab-size: 4;-o-tab-size: 4;
					padding: 0;
					margin: 0;
					color: #555;
					background:#FCFCFC;
					border: 0;
					resize: none;
					font-size: 16px;
					font-family: "Menlo","Liberation Mono","Consolas","DejaVu Sans Mono","Ubuntu Mono","Courier New","andale mono","lucida console",'文泉驛正黑','Microsoft yahei UI','Microsoft yahei','微软雅黑',"Lato",Helvetica,Arial,sans-serif !important;
				}


				#note-btns-showall{
					display: none;
				}

				@media screen and (max-width: 1064px){
					#note-btns-download-btn{
						display: none;
					}
				}

				@media screen and (max-width: 965px){
					#note-btns-passwd-btn, #note-btns-changeid-btn, #note-btns-tohtml-btn, #note-btns-otherdev-btn, #note-btns-setting-btn{
						display: none;
					}
					#note-btns-showall{
						display: block;
					}
				}

				/***** 在其他设备上访问对话框 *****/
				#note-otherdev{
					position: fixed;
					z-index: 110;
				}
				#note-otherdev-black{
					position: fixed;
					width: 100%;
					height: 100%;
					top: 0;
					left: 0;
					background-color: rgba(0,0,0,0.2);
					z-index: 111;
				}

				#note-otherdev-div{
					position: fixed;
					width: 300px;
					height: auto;
					top: 50%;
					left: 50%;
					background-color: #fff;
					z-index: 112;
					margin: -200px 0 0 -150px;
					box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.3);
				}

				.note-otherdev-div-divhr{
					width: 100%;
					height: 1px;
					background-color: #aaa;
				}


				.note-mynote-list{
					padding: 2px 20px;
					display: block;
					color: #fff;
					cursor: default;
					transition: background-color 0.2s;
				}

				.note-mynote-list:hover{
					background-color: #0C2136;
				}

				.note-mynote-list div{
					margin-bottom: -8px;
				}

				.menu-btn{
					padding: 10px 20px;
					display: block;
					cursor: default;
					color: #fff;
					border: 0px;
					width: 100%;
					background-color: #1C3146;
					text-align: left;
					font-size: 16px;
					transition: background-color 0.2s;
				}

				.menu-btn:hover{
					background-color: #0C2136 !important;
				}

				.menu-btn div{
					margin-bottom: -7px;
					margin-right: 5px;
				}

				.note-btns-set-from{
					display: none;
					height: 40px;
				}

				.note-btns-set-from-input{
					width:870px;box-shadow:0 0 0;height:20px;background-color:#0C2136;font-size:16px;color:#fff;
				}

				.note-btns-set-from-btn{
					float:right;font-size:16px;width:100px;height:40px;box-shadow:0 0 0;background-color:#2387CA;color:#fff;
				}
				.note-btns-set-from-btn:hover{
					background-color:#1377BA;
				}

				.remove-note-x{
					margin-top:-1px;float:right;font-size:16px;cursor:pointer;
					transition: color 0.2s;
				}

				.remove-note-x:hover{
					color: #ccc;
				}

			</style>

			<form action="<?php echo_note_url(); ?>" method="post" id="note-btns-passwdset-form" class="note-btns-set-from">
				<input id="note-btns-setpasswd-form-input" class="input note-btns-set-from-input" type="password" name="the_set_passwd" placeholder="请输入要设置的密码"/>
				<input id="note-btns-setpasswd-form-btn" type="submit" value="设置" class="btn note-btns-set-from-btn"/>
			</form>

			<form action="<?php echo_note_url(); ?>" method="post" id="note-btns-setid-form" class="note-btns-set-from">
				<input id="note-btns-setid-form-input" class="input note-btns-set-from-input" type="text" name="the_set_id" placeholder="请输入一个新ID"/>
				<input id="note-btns-setid-form-btn" type="submit" value="设置" class="btn note-btns-set-from-btn"/>
			</form>

			<form action="<?php echo_note_url(); ?>" method="post" id="note-btns-login-form" class="note-btns-set-from">
				<input id="note-btns-login-form-input" class="input note-btns-set-from-input" type="text" name="the_username" placeholder="请输入用户名"/>
				<input id="note-btns-login-form-btn" type="submit" value="登录 / 注册" class="btn note-btns-set-from-btn"  style="width:130px;"/>
			</form>

			<form action="<?php echo_note_url(); ?>" method="post" id="note-btns-passwddelete-form" style="display:none;margin:0;">
				<input type="hidden" name="delete_passwd" value="yes" />
			</form>

			<!-- [在其他设备上访问此记事本]对话框 -->
	 		<div id="note-otherdev" style="display:none;">
				<div id="note-otherdev-black" onclick="$('#note-otherdev').fadeOut();"></div>
				<div id="note-otherdev-div">
					<div style="background:#333;padding:6px 10px 4px 10px;font-size:16px"><h4 style="color:#fff">在其他设备上访问此记事本<span style="float:right;font-size:24px;cursor:pointer;" onclick="$('#note-otherdev').fadeOut();">×</span></h4></div>

					<div class="note-otherdev-div-divhr" style="margin-bottom:8px;"></div>

					<span style="margin-left:10px;">记事本ID: <strong><?php echo $noteId; ?></strong></span>

					<div style="width:240px; height:240px; margin:10px 30px 30px 30px;">
						<span id='note-otherdev-img-add'></span>
					</div>

					<div class="note-otherdev-div-divhr"></div>

					<div style="background-color:#ddd; height:59px;">
						<button class="btn" style="float:right;margin:10px 10px 10px 0;background-color:#bbb;box-shadow:0 0 0;" onclick="$('#note-otherdev').fadeOut();">关闭</button>
					</div>
				</div>
			</div>


			<!-- 记事本列表 -->
			<?php if(isset($username)) : ?>
				<div id="note-black" style="position:fixed;top:48px;left:0px;background:rgba(0, 0, 0, 0.4);width:100%;height:100%;z-index:99;display:none;" onclick="mynote_display()"></div>
				<div id="note-mynote" style="background-color:#1C3146;height:600px;width:250px;left:-260px;position:fixed;top:48px;z-index:100;overflow-x:hidden;overflow-y:auto;color:#fff;box-shadow: 1px 10px 10px rgba(0, 0, 0, 0.5);">
					<div style="padding:5px 10px;background-color:#1D81C4;"><?php echo $username ?>的记事本: </div>

					<?php
						foreach ($user_notes_array as $x) {
							if(strlen($x)>20){
								$x_dis=substr($x,1,20).'...';
							}else{
								$x_dis=$x;
							}
							if($x === $noteId)
								echo '<a title="'.$x.'" class="note-mynote-list" style="background-color:#2387CA;" >'.$x_dis.'</a>';
							else
								echo '<a title="'.$x.'" id="note-list-'.$x.'" href="./'.($rewrite_use_better_url ? '' : '?n=') .$x.'"" class="note-mynote-list" >'.$x_dis.'<span onclick="return false;" ><span title="从列表中移除此记事本" class="remove-note-x" style="" onclick="delete_note_in_list(\''.$x.'\',this);">×</span></span></a>';
						}
					?>
					<a title="使用一个随机的ID创建一个记事本，ID可在稍后更改" href="./?new=yes" class="note-mynote-list" style="margin-left:-5px;"><div data-icon="ei-plus" style="margin-bottom: -8px;"></div> 新建记事本</a>
				</div>
			<?php endif; ?>

			<!-- 侧边栏菜单,用于小分辨率中 -->
			<div id='note-menu-black' onclick="menu_display();" style="position:fixed;top:48px;left:0px;background:rgba(0, 0, 0, 0.4);width:100%;height:100%;z-index:99;display:none;"></div>
			<div id="note-menu" style="background-color:#1C3146;height:600px;width:250px;left:1440px;position:fixed;top:48px;z-index:100;overflow-x:hidden;overflow-y:auto;color:#fff;">

				<button class="menu-btn" title="获取记事本ID并生成二维码" onclick="other_dev_show();" id="note-menu-btns-otherdev-btn"><div data-icon="ei-link"></div><span>二维码</span></button>

				<!-- 密码 设置 && 删除 表单+按钮 -->
				<?php if($passwd) : ?>
					<button class="menu-btn" id="note-menu-btns-passwd-btn" title="删除这个记事本的密码" onclick="$('#note-btns-passwddelete-form').submit();"><div data-icon="ei-unlock"></div><span>删除密码</span></button>
				<?php else : ?>
					<button class="menu-btn" id="note-menu-btns-passwd-btn" title="给这个记事本设置一个密码" onclick="psaawd_set_display();"><div data-icon="ei-lock"></div><span>设置密码</span></button>
				<?php endif; ?>

				<?php if ( $page_type == 'md_note' ) : ?>
					<?php if ($rewrite_use_better_url): ?>
						<a href="./?n=<?php echo $noteId; ?>&html=yes" target="_blank"><button class="menu-btn" title="生成一个网页,网址可直接访问" style="text-decoration:none;color:#fff;font-size:16px;cursor:default;" id="note-menu-btns-tohtml-btn"><div data-icon="ei-external-link"></div><span>HTML页面</span></button></a>
					<?php else : ?>
						<a href="./?n=<?php echo $noteId; ?>&html=yes" target="_blank"><button class="menu-btn" title="生成一个网页,网址可直接访问" style="text-decoration:none;color:#fff;font-size:16px;cursor:default;" id="note-menu-btns-tohtml-btn"><div data-icon="ei-external-link"></div><span>HTML页面</span></button></a>
					<?php endif ?>
				<?php endif ?>

				<a id="download-a" style="display:none"></a>

				<button class="menu-btn" title="将记事本的内容以文件的方式下载" onclick="download_note();" id="note-menu-btns-download-btn"><div data-icon="ei-arrow-down"></div><span>下载</span></button>

				<button class="menu-btn" id="note-menu-btns-changeid-btn" title="给这个记事本更换一个新的ID"  onclick="id_set_display();"><div data-icon="ei-retweet"></div><span>更换ID</span></button>

				<button class="menu-btn" id="note-menu-btns-setting-btn" title="设置" onclick="set_display();" ><div data-icon="ei-gear"></div><span>设置</span></button>
			</div>


			<!-- 设置侧边栏 -->
			<div id='note-set-black' onclick="set_display();" style="position:fixed;top:48px;left:0px;background:rgba(0, 0, 0, 0.4);width:100%;height:100%;z-index:99;display:none;"></div>
			<div id="note-set" style="background-color:#1C3146;height:600px;width:250px;left:1440px;position:fixed;top:48px;z-index:100;overflow-x:hidden;overflow-y:auto;color:#fff;">
				<div style="padding:5px 10px;">
					<b style="margin-bottom:5px;">颜色</b><br/>
					<span style="cursor:pointer;display:inline-block;margin-left:6px;width:20px;height:20px;background-color:#0072C6" onclick="change_theme('blue')"></span>
					<span style="cursor:pointer;display:inline-block;margin-left:6px;width:20px;height:20px;background-color:#34495E" onclick="change_theme('default')"></span>
					<span style="cursor:pointer;display:inline-block;margin-left:6px;width:20px;height:20px;background-color:#000" onclick="change_theme('black')"></span>
					<span style="cursor:pointer;display:inline-block;margin-left:6px;width:20px;height:20px;background-color:#008A17" onclick="change_theme('green')"></span>
					<span style="cursor:pointer;display:inline-block;margin-left:6px;width:20px;height:20px;background-color:#03B3B2" onclick="change_theme('green2')"></span>
					<span style="cursor:pointer;display:inline-block;margin-left:6px;width:20px;height:20px;background-color:#AC193D" onclick="change_theme('red')"></span>
				</div>

			</div>

			<!-- 顶栏 -->
			<div id="header">

				<!-- MarkNote标题 && 返回主页按钮 -->
				<div class="header-title"  onclick="$('#force-home-form').submit();" >
					<h1 title="首页" style="display:inline-block;font-size:24px;color:#FCFCFC;border:0;padding:0;cursor:pointer;margin-top:-3px;">MarkNote</h1>
				</div>

				<!-- 登陆按钮 或 我的记事本按钮 -->
				<?php if(isset($username)) : ?>
					<button title="点击以打开记事本列表侧边栏，再次点击可关闭" class="header-btn" title="" style="float:left;" onclick="mynote_display();" ><div data-icon="ei-user"></div>我的记事本</button>
				<?php else : ?>
					<button title="输入用户名以登陆，登陆后可以记录所有用过的记事本，若用户名不存在则会新建一个" class="header-btn" title="" style="float:left;" onclick="login_display();" ><div data-icon="ei-location"></div>登录 / 注册</button>
				<?php endif;  ?>

				<!-- 保存 -->
				<span class="header-btn" title="也可按Ctrl+S保存" id="note-btns-save-ajax" onclick="ajax_save();">保存</span>

				<button class="header-btn" title="获取记事本ID并生成二维码" onclick="other_dev_show();" id="note-btns-otherdev-btn"><div data-icon="ei-link"></div><span>二维码</span></button>

				<!-- 密码 设置 && 删除 表单+按钮 -->
				<?php if($passwd) : ?>
					<button class="header-btn" id="note-btns-passwd-btn" title="删除这个记事本的密码" onclick="$('#note-btns-passwddelete-form').submit();"><div data-icon="ei-unlock"></div><span>删除密码</span></button>
				<?php else : ?>
					<button class="header-btn" id="note-btns-passwd-btn" title="给这个记事本设置一个密码" onclick="psaawd_set_display();"><div data-icon="ei-lock"></div><span>设置密码</span></button>
				<?php endif; ?>

				<!-- HTML页面 -->
				<?php if ( $page_type == 'md_note' ) : ?>
					<?php if ($rewrite_use_better_url): ?>
						<a class="header-btn" title="生成一个网页,网址可直接访问" style="height:24px;text-decoration:none;color:#fff;font-size:16px;cursor:default;" id="note-btns-tohtml-btn" href="<?php echo $noteId; ?>.html" target="_blank"><div data-icon="ei-external-link"></div><span>HTML页面</span></a>
					<?php else : ?>
						<a class="header-btn" title="生成一个网页,网址可直接访问" style="height:24px;text-decoration:none;color:#fff;font-size:16px;cursor:default;" id="note-btns-tohtml-btn" href="./?n=<?php echo $noteId; ?>&html=yes" target="_blank"><div data-icon="ei-external-link"></div><span>HTML页面</span></a>
					<?php endif ?>
				<?php endif ?>

				<!-- 用于下载的data-url的链接表情-->
				<a id="download-a" style="display:none"></a>
				<!-- 下载按钮 -->
				<button class="header-btn" title="将记事本的内容以文件的方式下载" onclick="download_note();" id="note-btns-download-btn"><div data-icon="ei-arrow-down"></div><span>下载</span></button>

				<!-- 更换ID按钮 -->
				<button class="header-btn" id="note-btns-changeid-btn" title="给这个记事本更换一个新的ID"  onclick="id_set_display();"><div data-icon="ei-retweet"></div><span>更换ID</span></button>

				<!-- 设置侧边栏按钮 -->
				<button class="header-btn" id="note-btns-setting-btn" title="设置" onclick="set_display();" ><div data-icon="ei-gear"></div><span>设置</span></button>

				<!-- 在小分辨率下,显示这个菜单按钮 -->
				<button class="header-btn" id="note-btns-showall" title="显示其他功能" onclick="menu_display();"><div data-icon="ei-navicon"></div><span>菜单</span></button>

			</div>

		<?php endif; //记事本编辑页共用 结束 ?>


		<!-- 纯文本记事本编辑页 -->
		<?php if ( $page_type == 'text_note' ) : ?>

			<!-- 大框子 -->
			<form action="<?php echo_note_url(); ?>" method="post" id="note-main-form" style="margin:0 auto;">
				<div id="note-main-form-div" style="padding: 10px;background-color:#eee;">
					<div style="width:100%; height:100%">
						<textarea id="note-text-edit" placeholder="在这里书写" autofocus="autofocus" spellcheck="false" name="the_note" oninput="note_change();" style="width:100%; height:100%;background-color:#eee;"><?php echo $note_content_to_show; ?></textarea>
					</div>
				</div>
				<input type="hidden" name="save" value="yes" />
			</form>
		<?php endif; //纯文本记事本编辑页 结束 ?>


		<!-- MarkDown记事本编辑页 -->
		<?php if ( $page_type == 'md_note' ) : ?>

			<style type="text/css">
				#note-md-show p{
					margin: 5px 0 12px 0;
				}
				#note-md-show h2{
					border-bottom:solid 2px #ddd;
					margin-bottom: 5px;
					padding-bottom: 2px;
				}
				#note-md-show blockquote{
					border-left: 5px solid #ccc;
					padding: 5px 0 1px 10px;
					margin: 16px 0;
					background-color: #F2F2F5;
				}
				#note-md-show pre{
					border-left: 5px solid #ccc;
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
				#note-md-show .MathJax_Display,#note-md-show .MathJax_Preview .MJXc-math{
					padding: 10px 0;
					background-color: #F2F2F5;
					display: block;
				}
				#note-md-show .MathJax_Preview span{
					font-size: 16px !important;
				}
				#note-md-show code{
					line-height: 16px;
					text-shadow: none;
					background-color: #ddd;
					padding: 4px 8px 2px 8px;
					margin: 0px 2px;
					font-size: 14px;
					font-family: "Menlo","Liberation Mono","Consolas","DejaVu Sans Mono","Ubuntu Mono","Courier New","andale mono","lucida console",monospace !important;
				}
				#note-md-show .checkbox-checked{
					width: 24px;
					display:inline-block;
					height:24px;
					background:transparent url('//cdn.bootcss.com/iCheck/1.0.1/skins/square/blue.png') no-repeat scroll 0% 0%;
					background-position:-48px 0px;
					margin-bottom: -7px;
				}
				#note-md-show .checkbox-notchecked{
					width: 24px;
					display:inline-block;
					height:24px;
					background:transparent url('//cdn.bootcss.com/iCheck/1.0.1/skins/square/blue.png') no-repeat scroll 0% 0%;
					background-position:-24px 0px;
					margin-bottom: -7px;
				}
				pre[class*=language-]>code[data-language]::before{
					border-radius: 0 !important;
				}

			</style>

			<script src="//cdn.bootcss.com/markdown.js/0.5.0/markdown.min.js"></script>
			<script src="//cdn.bootcss.com/prism/0.0.1/prism.min.js"></script>
			<link href="//cdn.bootcss.com/prism/0.0.1/prism.min.css" rel="stylesheet">
			<script src="//cdn.bootcss.com/ace/1.1.9/ace.js"></script>
			<script src="//cdn.bootcss.com/mathjax/2.5.3/MathJax.js?config=TeX-AMS-MML_HTMLorMML"></script>

			<script type="text/javascript">
				window.onload = function(){

					//“中线”调两边宽度
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
							oLeft.style.width = iT - (winw-oBox.clientWidth)/2 - 85 + "px";
							oRight.style.width = oBox.clientWidth - iT - 10 + (winw-oBox.clientWidth)/2 + "px";
							oRight.style.marginLeft = iT - (winw-oBox.clientWidth)/2 + 10 + "px";
							return false
						};
						document.onmouseup = function(){
							document.onmousemove = null;
							document.onmouseup = null;
							oMove.releaseCapture && oMove.releaseCapture();
							EditorAce.resize();
						};
						oMove.setCapture && oMove.setCapture();
						return false
					};

					$(".ace_scrollbar-v").attr("id","note-md-edit-scrollbar"); //给ACE编辑器的滚动条添加ID

					//滚动条事件
					$("#note-md-edit-scrollbar").scroll(function(){
						var t = $(this)[0].scrollTop; //获取编辑区滚动值

						// 自动同步滚动,算法:
						// 预览区滚动值 = 编辑区滚动值 * [ (预览区总滚动高度 - 预览区显示高度) / (编辑区总滚动高度 - 编辑区显示高度) ]
						document.getElementById("note-md-show").scrollTop=t * (document.getElementById("note-md-show").scrollHeight-document.getElementById("note-md-show").offsetHeight) / (document.getElementById("note-md-edit-scrollbar").scrollHeight-document.getElementById("note-md-edit-scrollbar").offsetHeight);
					});
				};
			</script>

			<!-- 大框子 -->
			<form action="<?php echo_note_url(); ?>" method="post" id="note-main-form" style="margin:0 auto;">
				<div id="note-main-form-div">
					<div style="width:100%; height:100%">
						<div id="note-md-show" style="position: absolute;width:49%; height:100%; font-size:16px; overflow:auto;padding:20px 40px;"></div>
						<div id="note-md-move" style="height:100%;width:5px;background-color:#ccc;position: absolute;cursor: ew-resize;"></div>
						<!-- <textarea id="note-md-edit" style="position: absolute;overflow:auto;width:48%; height:100%; float:right; background-color:#fcfcfc; padding:5px; font-size:14px;" spellcheck="false" oninput="this.editor.update();note_change();" autofocus="autofocus" name="the_note" ><?php // echo htmlentities($note_content_to_show); ?></textarea> -->
						<div id="note-md-edit" style="position: absolute;overflow:auto;width:48%; height:100%; float:right; background-color:#fcfcfc;font-size:14px;"><?php echo $note_content_to_show; ?></div>
					</div>
				</div>
				<input type="hidden" name="save" value="yes" />
			</form>

			<script>

				//初始化ACE编辑器
				var EditorAce = ace.edit("note-md-edit");
				EditorAce.setTheme("ace/theme/dawn");
				EditorAce.getSession().setMode("ace/mode/markdown");
				EditorAce.getSession().setUseWrapMode(true);

				//ACE编辑器的内容改变事件
				EditorAce.getSession().on('change', function(e) {
				    update_md();
				    note_change();
				});

				//预览区内容更新函数
				function update_md(){
					preview=document.getElementById("note-md-show");

					//MarkDown -> HTML
					preview.innerHTML = markdown.toHTML( EditorAce.getValue().replace(/\[公开HTML页面\]/g, "") );

					//MathJax公式更新
					MathJax.Hub.PreProcess(document.getElementById("note-md-show"));
					MathJax.Hub.Update();

					//代码高亮更新
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
					preview.innerHTML = preview.innerHTML.replace(/\[x\]/g, "<span class=\"checkbox-checked\"></span> ");
					preview.innerHTML = preview.innerHTML.replace(/\[-\]/g, "<span class=\"checkbox-checked\"></span> ");
					preview.innerHTML = preview.innerHTML.replace(/\[ \]/g, "<span class=\"checkbox-notchecked\"></span> ");
				}

				//页面加载时的首次更新
				update_md();

			</script>

		<?php endif; //MarkDown记事本编辑页 结束 ?>

		<!-- 主页HTML -->
		<?php if ( $page_type == 'home' ) : ?>

			<style type="text/css">
				body{
					margin: 0 auto 20px auto;
					/*max-width: 980px;*/
					width: 100%;
					background-color: #eee;
				}

				.homediv{
					box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.2);
					background: #fff;
					display: inline-block;
					width: 440px;
					height: 577px;
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
					width:265px;
					background:#C6E8FF;
				}

				#home-btn-new{
					margin:460px 0px 0px 0px;
					background:#3498DB;
					color:#fff;
					font-size:24px;
					padding:9px 154px 9px 154px;
				}

				#home-btn-go{
					margin:460px 15px 0px 0px;
					background:#3498DB;
					color:#fff;
					font-size:24px;
					padding:9px 30px 9px 30px;
					float:right;
				}

				#back-to-note{
					text-decoration: none;
					background: #3498DB;
					color: #fff;
				}

				#home-btn-new:hover,#home-btn-go:hover,#back-to-note:hover{
					background:#45A9EC;
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
						height: 167px;
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

			<div id="header">

				<!-- MarkNote标题 && 返回主页按钮 -->
				<div class="header-title">
					<h1 title="首页" style="display:inline-block;font-size:24px;color:#FCFCFC;border:0;padding:0;cursor:pointer;margin-top:-3px;" onclick="$('#force-home-form').submit();" >MarkNote</h1>
				</div>

				<?php if( isset($_COOKIE['myNote']) ) : ?>
					<!-- 强制主页时的返回按钮 -->
					<a title="根据这个设备上的记录来回到您的笔记本" href="<?php if($rewrite_use_better_url){echo $_COOKIE['myNote'];}else{echo '?n='.$_COOKIE['myNote'];} ?>" id="back-to-note" class="header-btn" style="height:24px;padding:11px 25px 13px 25px">回到我的笔记</a>
				<?php endif; ?>

			</div>

			<div style="width:95%;max-width:980px;margin:30px auto;" >

				<div style="clear:both;"></div>

				<div class="homediv">

					<h2>还没有记事本?</h2>
					<p style="margin:12px 0 0 0;">将使用随机的ID新建，可随时更改。也可直接在右侧指定ID新建。</p>

					<span class="icon icon-mid">
						<span class="icon-plus"></span>
					</span>

					<form action="?new=yes" method="post" id="home-form-new">
						<button title="使用一个随机的ID创建一个记事本，ID可在稍后更改" id="home-btn-new" class="btn">立刻创建</button>
					</form>

				</div>

				<div style="float:right;" class="homediv">

					<h2>已有记事本 或 指定ID新建记事本</h2>
					<p style="margin:12px 0 0 0;">根据输入的ID访问记事本，若该ID不存在会自动新建。</p>

					<span class="icon icon-mid">
						<span class="icon-file"></span>
					</span>

					<form action="" method="get" id="home-form-go">
						<input id="home-input" name="n" type="text" class="input" autofocus="autofocus" placeholder="记事本ID" />
						<button title="根据输入的记事本ID来访问记事本" id="home-btn-go" class="btn">提交</button>
					</form>

				</div>
			</div>

		<?php endif; ?>
		<?php if ( $page_type == 'select_note_type' ) : ?>

			<style>
				body{
					background-color: #eee;
					width: 100%;
				}
			</style>

			<div id="header" style="margin-bottom:10px;">
				<h2 class="header-title" style="color:#fff;font-size:21px;margin-top:-2px;">请选择将要创建的记事本类型</h2>
			</div>

			<form id="choose-form-md" action="" method="post">
				<input type="hidden" name="type" value="md">
				<input type="hidden" name="n" value="<?php echo $noteId; ?>">
			</form>

			<form id="choose-form-text" action="" method="post">
				<input type="hidden" name="type" value="text">
				<input type="hidden" name="n" value="<?php echo $noteId; ?>">
			</form>

			<div style="max-width:1140px;width:95%;margin:40px auto;">
				<div class="btn" onclick="$('#choose-form-md').submit();" style="height:150px;margin-bottom:20px;padding:10px;background-color:rgba(118, 197, 255, 0.3)">
					<h2>MarkDown格式笔记本（推荐）</h2>
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
			</div>

		<?php endif; ?>
	</body>
</html><?php if($use_sql){ mysqli_close($notesql); }?>
