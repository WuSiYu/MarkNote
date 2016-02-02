<?php
	require_once 'include/user.php';

	if(isset($_POST['type'])){
		if( $_POST['type'] == 'login' ){
			login($_POST['username'], $_POST['passwd']);
		}
		if( $_POST['type'] == 'register' ){
			register($_POST['username'], $_POST['email'], $_POST['passwd'], $_POST['nickname']);
		}
		if( $_POST['type'] == 'logout' ){
			logout();
		}
	}


?>

<?php if( !hasLogin() ): ?>
<h1>login</h1>

<form method="post" action="login.php">
	username:
	<input type="text"		name="username" />
	passwd:
	<input type="password"	name="passwd" />
	<input type="submit"	name="submit">

	<input type="hidden"	name="type"		value="login">
</form>



<h1>register</h1>

<form method="post" action="login.php">
	username:
	<input type="text"		name="username" />
	nickname:
	<input type="text"		name="nickname" />
	passwd:
	<input type="password"	name="passwd" />
	email:
	<input type="text"		name="email" />
	<input type="submit"	name="submit">

	<input type="hidden"	name="type"		value="register">
</form>

<?php else: ?>

	user: <?php echo $USERNAME; ?>

	<form method="post" action="login.php">
		<input type="submit"	name="submit"	value="logout">

		<input type="hidden"	name="type"		value="logout">
	</form>

<?php endif; ?>
