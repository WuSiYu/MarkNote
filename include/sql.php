<?php
	require dirname(__FILE__).'/../config.php';
	$sql = new mysqli($sql_host, $sql_user, $sql_passwd, $sql_name);
	if( $sql->connect_errno ){
		?>
		<p>无法连接数据库，请检查你的设置。</p>
		<p>Error: (<?php echo $sql->connect_errno.') '.$sql->connect_error; ?> </p>
		<a class="btn" style="cursor:pointer" onclick="history.go(-1)">< 返回</a>
		<?php
		exit();
	}

?>
