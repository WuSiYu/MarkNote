<?php #MarkNote安装向导 ?>


<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>MarkNote › 安装</title>
	<style type="text/css">
		body{
			font-family: "Noto Sans CJK SC","Microsoft YaHei UI","Microsoft YaHei","WenQuanYi Micro Hei",sans-serif;
			font-weight: 100;
			background: #eee;
		}
		h1,h2,h3,h4,h5,h6{
			font-weight: 100;
		}

		a,input,button{
			outline: none !important;
			-webkit-appearance: none;
			border-radius: 0;
		}
		button::-moz-focus-inner,input::-moz-focus-inner{
			border-color:transparent !important;
		}
		:focus {
			border: none;
			outline: 0;
		}

		input[type="text"]{
			border: 1px solid #AAA;
			padding: 5px;
			transition: border .25s linear
		}
		input[type="text"]:hover{
			border: 1px solid #44a8eb;
		}
		input[type="text"]:focus{
			border: 1px solid #3498db;
		}

		#page{
			box-shadow: 0px 2px 6px rgba(100, 100, 100, 0.3);
			background: #fff;
			color: #34495e;
			max-width: 800px;
			margin: 50px auto;
			padding: 30px;
		}
		.underline{
			border-bottom: 2px solid #aaa;
		}
		.subtitle{
			margin: 15px 0 10px 0;
		}
		.btn{
			text-decoration: none;
			display: inline-block;
			padding: 10px 18px;
			background: #3498db;
			box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.15);
			color: #fff;
			transition: border .25s linear, color .25s linear, background-color .25s linear;
		}
		.btn:hover{
			background: #44a8eb;
		}
		.btn:active{
			box-shadow: inset 0 2px 0 rgba(0, 0, 0, 0.15);
			background: #2488cb;
		}

	</style>
</head>

<body><div id="page">
<?php

	if( file_exists('../config.php') ){
		?>
		<h2 class="underline" style="font-weight:100;margin:0;" >MarkNote安装向导</h2>
		<p>程序已安装过，若需要调整设置，请直接编辑程序目录下的config.php或删除该文件以重新安装。</p>

		<?php
		exit();
	}


	if( ! isset($_GET['step']) ){
		//Welcome page
		?>

		<h2 class="underline" style="font-weight:100;margin:0;" >MarkNote安装向导</h2>
		<p>欢迎使用MarkNote，本向导会在程序目录下生成config.php，您也可以根据config-sample.php来手动创建。</p>
		<p>MarkNote需要一个可用的MySQL 5.x数据库，并建议启用mod_rewrite这一Apache模块。</p>

		<p>请点击下一步以继续</p>

		<a class="btn" style="float:right;" href="install.php?step=2">下一步 ></a>
		<div style="clear:both;"></div>


		<?php
	}else{
		if($_GET['step']=='2'){
			?>

			<h2 class="underline" style="font-weight:100;margin:0;" >MarkNote安装向导</h2>
			<form id="the-form" action="./install.php?step=3" method="post">
				<h3 class="subtitle">数据库信息</h3>
				<table style="margin-left:50px;">
					<tr>
						<td style="width:150px;">数据库主机</td>
						<td><input type="text" name="sql-host" value="localhost"></td>
					</tr>

					<tr>
						<td>数据库用户</td>
						<td><input type="text" name="sql-user" value="root"></td>
					</tr>

					<tr>
						<td>密码</td>
						<td><input type="text" name="sql-passwd" value=""></td>
					</tr>

					<tr>
						<td>数据库名</td>
						<td><input type="text" name="sql-name" value="marknote"></td>
					</tr>
				</table>


				<h3 class="subtitle">其他选项</h3>
				<table style="margin-left:50px;">
					<tr>
						<td style="width:150px;">启用伪静态</td>
						<td><input type="checkbox" name="enable-rewrite" checked="checked"></td>
					</tr>
				</table>

				<a class="btn" style="float:right;cursor:pointer" onclick="document.getElementById('the-form').submit();">下一步 ></a>
				<div style="clear:both;"></div>
			</form>

			<?php
		}

		if($_GET['step']=='3'){
			if( !file_exists("../.htaccess") && $_POST['enable-rewrite'] ){
				$htaccess_file_content =
"### MarkNote RewriteRule start
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteRule ^([a-zA-Z0-9]+)$ index.php?type=user&user=$1
	RewriteRule ^([a-zA-Z0-9]+)/([a-zA-Z0-9]+)$ index.php?type=notebook&user=$1&notebook=$2
	RewriteRule ^([a-zA-Z0-9]+)/([a-zA-Z0-9]+)/([a-zA-Z0-9]+)$ index.php?type=note&user=$1&notebook=$2&notemane=$3
</IfModule>
### MarkNote RewriteRule end
";
				file_put_contents('../.htaccess', $htaccess_file_content);
			}

			$sql_host	=	$_POST['sql-host'];
			$sql_user	=	$_POST['sql-user'];
			$sql_passwd	=	$_POST['sql-passwd'];
			$sql_name	=	$_POST['sql-name'];

			$sql = new mysqli($sql_host, $sql_user, $sql_passwd, $sql_name);
			if( $sql->connect_errno ){
				?>
				<p>无法连接数据库，请检查你的设置。</p>
				<p>Error: (<?php echo $sql->connect_errno.') '.$sql->connect_error; ?> </p>
				<a class="btn" style="cursor:pointer" onclick="history.go(-1)">< 返回</a>
				<?php
				exit();
			}

			$sql->query('CREATE TABLE note_content (
						ID int NOT NULL AUTO_INCREMENT,
						PRIMARY KEY(ID),
						user tinytext,
						notebook tinytext,
						settings text,
						content longtext,
						comments longtext
					)');

			$sql->query('CREATE TABLE note_users (
						UID int NOT NULL AUTO_INCREMENT,
						PRIMARY KEY(UID),
						username tinytext,
						passwd tinytext,
						settings text,
						notebooks longtext
					)');


			$to_config_file=
'<?php
	$sql_host="'.$sql_host.'";
	$sql_user="'.$sql_user.'";
	$sql_passwd="'.$sql_passwd.'";
	$sql_name="'.$sql_name.'";
?>';


			file_put_contents('../config.php', $to_config_file);
			?>
				<h2 class="underline" style="font-weight:100;margin:0;" >MarkNote安装向导</h2>
				<p>安装已完成</p>
				<a class="btn" style="float:right;cursor:pointer" href="../">完成</a>
				<div style="clear:both;"></div>
			<?php

		}
	}


?>

</div></body>

</html>
