<?php
	/* 用户相关函数 */

	require_once 'sql.php';

	$USERNAME = '';

	$FORCESTATUS = 0;

	function json_encode_fix($input){
		if(version_compare(PHP_VERSION, '5.4.0', '>=') && false){
			return json_encode($input, JSON_UNESCAPED_UNICODE);
		}else{
			$input = json_encode_fix_array($input);
			return urldecode(json_encode($input));
		}
	}

	function json_encode_fix_array($array){
		foreach($array as $key => $value) {
			if(is_string($value)){
				$array[$key] = urlencode($value);
			}
			if(is_array($value)){
				$array[$key] = json_encode_fix_array($value);
			}
		}
		return $array;
	}

	function checkUsername($username){
		return true;
	}


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
			$sql_output = $sql->query("SELECT username FROM note_users
				WHERE username = '$username'");
			$username = $sql_output->fetch_array()['username'];
			$USERNAME = $username;
			return true;
		}else{
			return false;
		}
		
	}

	function register($username, $email, $passwd, $nickname){
		global $sql;
		//something
		if( hasUser($username) )
			exit('Username already exist');
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
			return -1;
		}
		if(md5('ffffffffff'.$passwd.'蛤蛤蛤') == $truePasswd){
			$sql_output = $sql->query("SELECT username FROM note_users
				WHERE username = '$username'");
			$username = $sql_output->fetch_array()['username'];
			setcookie('MarkNoteUser', $username, time()+604800);
			setcookie('MarkNotePasswd', md5('ffffffffff'.$passwd.'蛤蛤蛤'), time()+604800);
			$USERNAME = $username;
			$FORCESTATUS = 1;
			return 0;
		}else{
			echo "wrong passwd";
			return -1;
		}
	}

	function getUserEmail($username){
		global $sql;
		checkUsername($username);
		$sql_output = $sql->query("SELECT email FROM note_users
			WHERE username = '$username'");
		return $sql_output->fetch_array()['email'];
	}

	function logout(){
		global $FORCESTATUS;
		setcookie('MarkNoteUser', '', time()-100);
		setcookie('MarkNotePasswd', '', time()-100);
		$FORCESTATUS = 2;
	}

	function addNotebookToUser($username, $notebook){
		global $sql;
		$sql_output = $sql->query("SELECT notebooks FROM note_users
			WHERE username = '$username'");
		$theNotebooks = json_decode( $sql_output->fetch_array()['notebooks'] );
		if($theNotebooks){
			if(!in_array(array($notebook), $theNotebooks)){
				array_push($theNotebooks, array($notebook));
			}else{
				echo 'notebook name already exist';
				return -1;
			}
		}else{
			$theNotebooks = array(array($notebook));
		}
		echo 'ok';
		$theNotebooks = json_encode_fix($theNotebooks);
		$sql->query("UPDATE note_users SET notebooks = '$theNotebooks'
			WHERE username = '$username'");

	}

	function addNoteToNotebook($username, $notebook, $id){
		global $sql;
		$sql_output = $sql->query("SELECT notebooks FROM note_users
			WHERE username = '$username'");
		$theNotebooks = json_decode( $sql_output->fetch_array()['notebooks'] );
		if($theNotebooks){
			foreach($theNotebooks as $key => $value) {
				if (is_array($value) && $value[0] == $notebook){
					array_push($theNotebooks[$key], $id);
					echo 'ok';
					break;
				}
			}
		}else{
			return -2;	//no such notebook
		}
		$theNotebooks = json_encode_fix($theNotebooks);
		$sql->query("UPDATE note_users SET notebooks = '$theNotebooks'
			WHERE username = '$username'");
	}

	function addSingleNoteToUser($username, $id){
		global $sql;
		$sql_output = $sql->query("SELECT notebooks FROM note_users
			WHERE username = '$username'");
		$theNotebooks = json_decode( $sql_output->fetch_array()['notebooks'] );
		if($theNotebooks){
			array_push($theNotebooks, $id);
		}else{
			$theNotebooks = array($id);
		}
		$theNotebooks = json_encode_fix($theNotebooks);
		$sql->query("UPDATE note_users SET notebooks = '$theNotebooks'
			WHERE username = '$username'");

	}

	function getUserNotebooks($username){
		global $sql;
		checkUsername($username);
		$sql_output = $sql->query("SELECT notebooks FROM note_users
			WHERE username = '$username'");
		return json_decode( $sql_output->fetch_array()['notebooks'] );
	}

	function getIDListFromNoteList($list){
		$IDList = array();
		foreach ($list as $value) {
			if(is_int($value)){
				$IDList[] = $value;
			}
			if(is_array($value)){
				foreach ($value as $value2) {
					if(is_int($value2)){
						$IDList[] = $value2;
					}
				}
			}
		}
		sort($IDList);
		return $IDList;
	}

	function updatetUserNotebooks($username, $list){
		global $sql;
		$oldList = getUserNotebooks($username);
		if( getIDListFromNoteList($oldList) == getIDListFromNoteList($list) ){

			$list = json_encode_fix($list);
			$sql->query("UPDATE note_users SET notebooks = '$list'
				WHERE username = '$username'");

		}
	}

	function removeNoteFromUser($username, $id){
		global $sql;
		$sql_output = $sql->query("SELECT notebooks FROM note_users
			WHERE username = '$username'");
		$theNotebooks = json_decode( $sql_output->fetch_array()['notebooks'] );
		if($theNotebooks){
			foreach($theNotebooks as $key => $value) {
				if( is_int($value) && $value == $id ){
					array_splice($theNotebooks, $key, 1);
					echo 'ok';
					break;
				}
				if( is_array($value) ){
					foreach($value as $key2 => $note) {
						if( is_int($note) && $note == $id){
							array_splice($theNotebooks[$key], $key2, 1);
							echo 'ok';
							break 2;
						}
					}
				}
			}
		}
		$theNotebooks = json_encode_fix($theNotebooks);
		$sql->query("UPDATE note_users SET notebooks = '$theNotebooks'
			WHERE username = '$username'");
	}

	function removeNotebookFromUser($username, $notebook){
		global $sql;
		$sql_output = $sql->query("SELECT notebooks FROM note_users
			WHERE username = '$username'");
		$theNotebooks = json_decode( $sql_output->fetch_array()['notebooks'] );
		if($theNotebooks){
			foreach($theNotebooks as $key => $value) {
				if( is_array($value) && $value[0] == $notebook ){
					if( count($value) == 1 ){
						array_splice($theNotebooks, $key, 1);
						echo 'ok';
					}else{
						echo 'notebook not empty';
					}
					break;
				}
			}
		}
		$theNotebooks = json_encode_fix($theNotebooks);
		$sql->query("UPDATE note_users SET notebooks = '$theNotebooks'
			WHERE username = '$username'");
	}

