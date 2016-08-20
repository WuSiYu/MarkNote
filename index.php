<?php
	//MarkNote 轻量级云记事本系统

	error_reporting(E_ALL);
	ini_set('display_errors', '1');

	if( !file_exists('config.php') ){
		header("Location: include/install.php");
		exit();
	}

	require 'include/user.php';

	if( isset($_GET['type']) ){
		$type = $_GET['type'];
	}else{
		$type = 'home';
	}

	// echo 'index.php<br/>type='.$type.'<br/>';


	if( $type == 'user' ){
		// echo 'load '.$type.' page ---> ';
		require 'user.php';
	}

	if( $type == 'notebook' ){
		// echo 'load '.$type.' page ---> ';
		require 'notebook.php';
	}

	if( $type == 'note' ){
		// echo 'load '.$type.' page ---> ';
		require 'note.php';
	}

	if( $type == 'home' ){
		if(hasLogin()){
			// echo 'load '.$type.' page ---> ';
			require 'edit.php';
		}
		else{
			// echo 'load '.$type.' page ---> ';
			require 'login.php';
		}
	}
