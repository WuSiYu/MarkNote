edit.php

<script src="//cdn.bootcss.com/jquery/2.2.0/jquery.min.js"></script>
<script type="text/javascript">
	$(document).ready(function(){
		$("#btn-newnote").click(function(){
			$.post('include/note.php',{
				action:'newNote',
				title:$("#input-newnote").val()
			},
			function(data,status){
				alert('Status: ' + status + data );
			});
		});	

		$("#btn-newnotebook").click(function(){
			$.post('include/note.php',{
				action:'newNotebook',
				title:$("#input-newnotebook").val()
			},
			function(data,status){
				alert('Status: ' + status + data );
			});
		});	

		$("#btn-subnote").click(function(){
			$.post('include/note.php',{
				action:'newSubnote',
				notebook:$("#input-subnote-book").val(),
				title:$("#input-subnote-note").val()
			},
			function(data,status){
				alert('Status: ' + status + data );
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