<?php

	require_once 'sql.php';

	$USERNAME = '';

	$FORCESTATUS = 0;

	function hasUser($username){
		global $sql;
		$sql_output = $sql->query("SELECT username FROM note_users
			WHERE username = '$username'");
		if( $sql_output->num_rows > 0 ){
			return true;
		}
		return false;
	}

	function hasLogin(){
		global $sql, $USERNAME, $FORCESTATUS;

		if($FORCESTATUS == 1) return true;
		if($FORCESTATUS == 2) return false; 

		if(!isset($_COOKIE['MarkNoteUser']) || !isset($_COOKIE['MarkNotePasswd']))
			return false;

		$username = $_COOKIE['MarkNoteUser'];

		$sql_output = $sql->query("SELECT passwd FROM note_users
			WHERE username = '$username'");
		if( $sql_output->num_rows > 0 ){
			$truePasswd = $sql_output->fetch_array()['passwd'];
		}else{
			return false;
		}

		if( $truePasswd == $_COOKIE['MarkNotePasswd'] ){
			$USERNAME = $username;
			return true;
		}else{
			return false;
		}
		
	}

	function register($username, $email, $passwd, $nickname){
		global $sql;
		//something

		$passwd = md5('ffffffffff'.$passwd.'蛤蛤蛤');
		$sql->query("INSERT INTO note_users (username, passwd, email, settings)
			VALUES ('$username', '$passwd', '$email', '{\"nickname\" = \"$nickname\" }')");
	}

	function login($username, $passwd){
		global $sql, $USERNAME, $FORCESTATUS;
		$sql_output = $sql->query("SELECT passwd FROM note_users
			WHERE username = '$username'");
		if( $sql_output->num_rows > 0 ){
			$truePasswd = $sql_output->fetch_array()['passwd'];
		}else{
			echo "no this user";
		}
		if(md5('ffffffffff'.$passwd.'蛤蛤蛤') == $truePasswd){
			setcookie('MarkNoteUser', $username, time()+604800);
			setcookie('MarkNotePasswd', md5('ffffffffff'.$passwd.'蛤蛤蛤'), time()+604800);
			$USERNAME = $username;
			$FORCESTATUS = 1;
		}else{
			echo "wrong passwd";
		}
	}

	function logout(){
		global $FORCESTATUS;
		setcookie('MarkNoteUser', '', time()-100);
		setcookie('MarkNotePasswd', '', time()-100);
		$FORCESTATUS = 2;
	}

	function addNoteBookToUser(){
		//
	}

	function addSingleNoteToUser(){
		
	}

?>
