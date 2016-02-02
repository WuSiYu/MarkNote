edit.php

<script src="//cdn.bootcss.com/jquery/2.2.0/jquery.min.js"></script>
<script type="text/javascript">
	$(document).ready(function(){
		$("button").click(function(){
			$.post('include/note.php',{
				action:'newNote',
				title:'123456'
			},
			function(data,status){
				alert('Status: ' + status);
			});
		});
	});
</script>

<button>new</button>