<?php
	//MarkNote 轻量级云记事本系统

	//=== 选项 =============================
	define('MD5_SALT', 'faowifankjsnvlaiuwef2480rasdlkvj');			//加密记事本密码时, 所使用的盐, 请一定要修改为自己设置的


	error_reporting(E_ALL);
	ini_set('display_errors', '1');


	if( !file_exists('config.php') ){
		header("Location: include/install.php");
		exit();
	}

	echo 'index.php<br/>type='.$_GET['type'].'<br/>';


	if( $_GET['type'] == 'user' ){
		require 'user.php';
	}

	if( $_GET['type'] == 'notebook' ){
		require 'notebook.php';
	}

	if( $_GET['type'] == 'note' ){
		require 'note.php';
	}


?>
