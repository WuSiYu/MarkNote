<?php 
	require_once dirname(__FILE__).'/include/user.php';
	require_once dirname(__FILE__).'/include/note.php';

	if( hasLogin() && isset($_POST['action']) && $_POST['action'] == 'getNotelist' ){
		$theNotebooks = getUserNotebooks($USERNAME);
		var_dump($theNotebooks);
		foreach ($theNotebooks as $value) {
			if(is_int($value)){
				?>
				<div>
					<span id="p-notelist-item-<?php echo $value?>" class="p-notelist-item" onclick="loadNote(<?php echo $value?>);">ID : <?php echo $value; ?> TITLE : <?php echo getNoteTitle($value); ?></span> <span class="btn-notelist-item-del" onclick="delNote(<?php echo $value; ?>);">del</span>
				</div>
				<?php
			}
			if(is_array($value)){
				?>
				<div class="p-notelist-item" >NOTEBOOK : <?php echo $value[0]; ?> <span class="btn-notelist-item-del" onclick="delNotebook('<?php echo $value[0]; ?>');">del</span></div>
				<?php
				foreach ($value as $note) {
					if(is_int($note)){
						?>
						<div>
							<span id="p-notelist-item-<?php echo $note?>" class="p-notelist-item p-notelist-item-subnote" onclick="loadNote(<?php echo $note?>);">ID : <?php echo $note; ?> TITLE : <?php echo getNoteTitle($note); ?></span> <span class="btn-notelist-item-del" onclick="delNote(<?php echo $note; ?>);">del</span>
						</div>
						<?php
					}
				}
			}
		}
		exit();
	}

?>

edit.php
<h1>MarkNote</h1>

<?php if(hasLogin()): ?>
	<style type="text/css">
		.p-notelist-item-selected{
			background-color: #000;
			color: #fff;
		}

		.p-notelist-item-subnote{
			margin-left: 30px;
		}

		.btn-notelist-item-del{
			color: red;
		}
	</style>
	<script src="//cdn.bootcss.com/jquery/2.2.0/jquery.min.js"></script>
	<script type="text/javascript">
		var NOTEID=0;

		function loadNotelist(){
			$.post('edit.php',{
				action:'getNotelist',
			},
			function(data,status){
				$("#div-notelist").html(data);
			});
		}

		function loadNote(id){
			// alert(id);
			if(NOTEID){
				if(NOTEID == id){
					return 0;
				}
				$("#p-notelist-item-"+NOTEID).removeClass("p-notelist-item-selected");
			}
			NOTEID=id;
			$("#p-notelist-item-"+NOTEID).addClass("p-notelist-item-selected");
			$.post('include/note.php',{
				action:'getNote',
				id:id
			},
			function(data,status){
				// alert('Status: ' + status + data );
				$("#textarea-note").val(data);
			});
		}

		function saveNote(){
			$.post('include/note.php',{
				action:'saveNote',
				id:NOTEID,
				content:$("#textarea-note").val()
			},
			function(data,status){
				// alert('Status: ' + status + data );
			});
		}

		function delNote(id){
			$.post('include/note.php',{
				action:'delNote',
				id:id
			},
			function(data,status){
				// alert('Status: ' + status + data );
				loadNotelist();
			});
		}

		function delNotebook(notebook){
			$.post('include/note.php',{
				action:'delNotebook',
				notebook:notebook
			},
			function(data,status){
				// alert('Status: ' + status + data );
				loadNotelist();
			});
		}

		$(document).ready(function(){
			loadNotelist();

			$("#btn-newnote").click(function(){
				$.post('include/note.php',{
					action:'newNote',
					title:$("#input-newnote").val()
				},
				function(data,status){
					// alert('Status: ' + status + data );
					loadNotelist();
				});
			});	

			$("#btn-newnotebook").click(function(){
				$.post('include/note.php',{
					action:'newNotebook',
					notebook:$("#input-newnotebook").val()
				},
				function(data,status){
					// alert('Status: ' + status + data );
					loadNotelist();
				});
			});	

			$("#btn-subnote").click(function(){
				$.post('include/note.php',{
					action:'newSubnote',
					notebook:$("#input-subnote-book").val(),
					title:$("#input-subnote-note").val()
				},
				function(data,status){
					// alert('Status: ' + status + data );
					loadNotelist();
				});
			});
		});

	</script>

	<input type="text" id="input-newnote"></input>
	<button id="btn-newnote">new note</button><br/>

	<input type="text" id="input-newnotebook"></input>
	<button id="btn-newnotebook">new notebook</button><br/>

	<input type="text" id="input-subnote-book"></input>
	<input type="text" id="input-subnote-note"></input>
	<button id="btn-subnote">new notebook->note</button><br/>

	<div id="div-notelist">load</div>

	<textarea id="textarea-note"></textarea>
	<button id="button-savenote" onclick="saveNote();">save</button>
	<button onclick="alert(NOTEID);">test</button>

<?php endif; ?>
