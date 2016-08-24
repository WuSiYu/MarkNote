<?php 
	require_once dirname(__FILE__).'/include/user.php';
	require_once dirname(__FILE__).'/include/note.php';

	if(!hasLogin()){
		echo 'Please login';
		exit();
	}

	if( hasLogin() && isset($_POST['action']) && $_POST['action'] == 'getNotelist' ){
		$theNotebooks = getUserNotebooks($USERNAME);
		// var_dump($theNotebooks);
		foreach ($theNotebooks as $value) {
			if(is_int($value)){
				?>
				<div id="div-notelist-item-<?php echo $value?>" class="div-notelist-item div-notelist-item-single" onmouseover="showNoteDelIcon(this)" onmouseout="hideNoteDelIcon(this)">
					<span class="span-notelist-item-left"></span><span class="span-notelist-item-text handle1" onclick="loadNote(<?php echo $value?>);"><i class="fa fa-file-text" aria-hidden="true"></i><?php echo getNoteTitle($value); ?></span><i class="fa fa-times i-notelist-item-del" onclick="delNote(<?php echo $value; ?>);"></i>
				</div>
				<?php
			}
			if(is_array($value)){
				?>
				<div class="div-notelist-item-single" style="height: 0.5px;"></div>

				<div class="div-notelist-folder">
					<i class="fa fa-angle-down fa-lg i-notelist-folder-arrow" aria-hidden="true"></i>
					<div class="div-notelist-item notebook-opened div-notelist-item-notebook-title" onmouseover="showNotebookDelIcon(this)" onmouseout="hideNotebookDelIcon(this)" onclick="toggleNotebook(this);"><span class="span-notelist-item-left"></span><span class="span-notelist-item-text handle1"><i class="fa fa-book" aria-hidden="true"></i><?php echo $value[0]; ?></span><i class="fa fa-times i-notelist-item-del" onclick="delNotebook('<?php echo $value[0]; ?>');"></i></div>
					<?php
					foreach ($value as $note) {
						if(is_int($note)){
							?>
							<div id="div-notelist-item-<?php echo $note?>" class="div-notelist-item div-notelist-item-subnote" onmouseover="showNoteDelIcon(this)" onmouseout="hideNoteDelIcon(this)">
								<span class="span-notelist-item-left span-notelist-item-left-subnote"></span><span class="span-notelist-item-text" onclick="loadNote(<?php echo $note?>);"><i class="fa fa-file-text" aria-hidden="true"></i><?php echo getNoteTitle($note); ?></span><i class="fa fa-times i-notelist-item-del" onclick="delNote(<?php echo $note; ?>);"></i>
							</div>
							<?php
						}
					}?>
					<div class="div-notelist-item">
						<span class="span-notelist-item-left span-notelist-item-left-subnote"></span><span class="span-notelist-item-text" onclick="newSubnote('<?php echo $value[0]; ?>');"><i class="fa fa-plus" aria-hidden="true"></i>New Note</span>
					</div>
				</div><?php
			}
		}
		?>
		<div class="div-notelist-item-single" style="height: 0.5px;"></div>
		<div class="div-notelist-item">
			<span class="span-notelist-item-left"></span><span class="span-notelist-item-text" onclick="newNote();"><i class="fa fa-plus" aria-hidden="true"></i>New Note</span>
		</div>
		<div class="div-notelist-item">
			<span class="span-notelist-item-left"></span><span class="span-notelist-item-text" onclick="newNotebook();"><i class="fa fa-plus" aria-hidden="true"></i>New Notebook</span>
		</div>
		<?php
		exit();
	}

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>MarkNote</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

	<script src="//cdn.bootcss.com/jquery/2.2.0/jquery.min.js"></script>
	<script src="//cdn.bootcss.com/marked/0.3.6/marked.min.js"></script>
	<script src="//cdn.bootcss.com/ace/1.2.5/ace.js"></script>
	<!-- <script src="//cdn.bootcss.com/prism/0.0.1/prism.min.js"></script> -->
	<!-- <script src="//cdn.bootcss.com/mathjax/2.5.3/MathJax.js"></script> -->
	<script src="//cdn.bootcss.com/mathjax/2.6.1/MathJax.js?config=TeX-MML-AM_CHTML"></script>
	<script src="//cdn.bootcss.com/Sortable/1.4.2/Sortable.min.js"></script>
	<script src="include/js/edit.js"></script>
	<script src="include/js/prism.js"></script>

	<link href="//cdn.bootcss.com/font-awesome/4.6.3/css/font-awesome.css" rel="stylesheet">
	<!-- <link href="//cdn.bootcss.com/prism/0.0.1/prism.min.css" rel="stylesheet"> -->
	<link rel="stylesheet" type="text/css" href="include/css/edit.css">
	<link rel="stylesheet" type="text/css" href="include/css/prism.css">

</head>

<body>

	<div id="header">
		<h1 id="header-title">MarkNote</h1>
		<div id="header-user">
			<div id="header-user-head">
				<i class="fa fa-user fa-2x" aria-hiddem="true" style="margin: 7px 0px 0px 5px;"></i>
			</div>
			<span id="header-user-name"><?php echo $USERNAME; ?></span>
			<span id="header-user-emailandlogout"><?php echo getUserEmail($USERNAME); ?> | <a style="cursor: pointer;" onclick="$('#header-user-logoutform').submit();">logout</a></span>
			<form id="header-user-logoutform" method="post" action="login.php">
				<input type="hidden" name="type" value="logout">
			</form>

		</div>
	</div>


	<div id="content">
	 	<div id="sidebar">
	 		<div id="sidebar-status">Status: <span id="sidebar-status-icon">●</span> <span id="sidebar-status-text">page loding...</span></div>
			<div id="sidebar-notelist">load</div>
	 	</div>
	 	<div id="editor">
			<div id="editor-ace"># Welcome to Marknote

Please select a __note__ in the list on the left.</div>
			<div id="editor-move"></div>
			<div id="editor-show"></div>
			<div id="editor-show-preprocess"></div>
		</div>
	</div>

	<div id="contextmenu-1">
		<div class="contextmenu-item"><i class="fa fa-folder" aria-hidden="true"></i> Open</div>
		<div class="contextmenu-item"><i class="fa fa-folder" aria-hidden="true"></i> Rename</div>
		<div class="contextmenu-item"><i class="fa fa-folder" aria-hidden="true"></i> Clone</div>
		<div class="contextmenu-item"><i class="fa fa-folder" aria-hidden="true"></i> Download</div>
		<div class="contextmenu-item"><i class="fa fa-folder" aria-hidden="true"></i> Share</div>
		<div class="contextmenu-item"><i class="fa fa-folder" aria-hidden="true"></i> Delete</div>
	</div>

</body>
</html>
