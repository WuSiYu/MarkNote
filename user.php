<?php
	require_once 'include/user.php';

	if( has_user($_GET['user']) ){
		echo "string";
	}else {
		echo "no this user";
	}
?>
