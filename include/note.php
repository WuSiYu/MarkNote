<?php

	require_once 'sql.php';

	function hasNote($id){
		$sql_output = $sql->query("SELECT ID FROM note_content
			WHERE ID = '$id'");
		if( $sql_output->num_rows > 0 ){
			return true;
		}else{
			return false;
		}
	}

	function newNote($username, $title='New Note'){
		$sql->query("INSERT INTO note_content (user, settings)
			VALUES ('$username', '{\"title\" = \"$title\" }' )");
		return $sql->insert_id;
	}

	function getNote($id){
		$sql_output = $sql->query("SELECT content FROM note_content
			WHERE ID = '$id'");
		if( $sql_output->num_rows > 0 ){
			return $sql_output->fetch_array()['content'];
		}else{
			return false;
		}
	}

	function saveNote($id, $content){
		if( hasNote($id) ){
			$sql->query("UPDATE note_content SET content = '$content'
				WHERE ID = '$id'");
		}
	}
