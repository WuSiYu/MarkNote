<?php
	// if (isset( $_COOKIE['marknote_user'] )) {
	// 	//
	// }else {
	// 	header("Location: login.php");
	// }

	require 'sql.php';

	function has_user( $username ){
		global $sql;
		$sql_output = $sql->query("SELECT username FROM note_users
			WHERE username = '".$username."'");
		if( $sql_output->num_rows > 0 ){
			return true;
		}
		return false;
	}

	function has_login(  ){
		return false;
	}

?>
