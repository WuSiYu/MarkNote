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
				<div id="div-notelist-item-<?php echo $value?>" class="div-notelist-item" onmouseover="showNoteDelIcon(this)" onmouseout="hideNoteDelIcon(this)">
					<span class="span-notelist-item-left"></span><span class="span-notelist-item-text" onclick="loadNote(<?php echo $value?>);"><i class="fa fa-file-text" aria-hidden="true"></i><?php echo getNoteTitle($value); ?></span><i class="fa fa-times i-notelist-item-del" onclick="delNote(<?php echo $value; ?>);"></i>
				</div>
				<?php
			}
			if(is_array($value)){
				?>
				<div class="div-notelist-folder">
					<i class="fa fa-angle-down fa-lg i-notelist-folder-arrow" aria-hidden="true"></i>
					<div class="div-notelist-item notebook-opened" onmouseover="showNotebookDelIcon(this)" onmouseout="hideNotebookDelIcon(this)" onclick="toggleNotebook(this);"><span class="span-notelist-item-left"></span><span class="span-notelist-item-text"><i class="fa fa-book" aria-hidden="true"></i><?php echo $value[0]; ?></span><i class="fa fa-times i-notelist-item-del" onclick="delNotebook('<?php echo $value[0]; ?>');"></i></div>
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
					<div class="div-notelist-item div-notelist-item-subnote">
						<span class="span-notelist-item-left span-notelist-item-left-subnote"></span><span class="span-notelist-item-text" onclick="newSubnote('<?php echo $value[0]; ?>');"><i class="fa fa-plus" aria-hidden="true"></i>New Note</span>
					</div>
				</div><?php
			}
		}
		?>
		<div class="div-notelist-item">
			<span class="span-notelist-item-left"></span><span class="span-notelist-item-text" onclick="newNote();"><i class="fa fa-plus" aria-hidden="true"></i>New Note</span>
		</div>
		<div class="div-notelist-item">
			<span class="span-notelist-item-left"></span><span class="span-notelist-item-text" onclick="newNotebook();"><i class="fa fa-plus" aria-hidden="true"></i>New Notebook</span>
		</div>
		<?php
		exit();
	}

?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>MarkNote</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

	<script src="//cdn.bootcss.com/jquery/2.2.0/jquery.min.js"></script>
	<script src="include/js/edit.js"></script>
	<link href="//cdn.bootcss.com/font-awesome/4.6.3/css/font-awesome.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="include/css/edit.css">

</head>

<body>

	<div id='header'>
		<h1 id='header-title'>MarkNote</h1>
	</div>


	<div id="content">
	 	<div id="sidebar">
			<div id="div-notelist">load</div>
	 	</div>
	 	<div id="editor">
<!-- 
			<input type="text" id="input-newnote"></input>
			<button id="btn-newnote">new note</button><br/>

			<input type="text" id="input-newnotebook"></input>
			<button id="btn-newnotebook">new notebook</button><br/>

			<input type="text" id="input-subnote-book"></input>
			<input type="text" id="input-subnote-note"></input>
			<button id="btn-subnote">new notebook->note</button><br/>
 -->
			<textarea id="textarea-note" style="width: 800px;height: 600px;background-color: #191919;color: #fff;border: 0;padding: 20px;resize: none;"></textarea>
			<button id="button-savenote" onclick="saveNote();">save</button>
		</div>
	</div>

</body>
</html>
