<?php

	require_once dirname(__FILE__).'/sql.php';
	require_once dirname(__FILE__).'/user.php';


	if( hasLogin() ){


		if( isset($_POST['action']) ){
			if( $_POST['action'] == 'newNote' ){
				if( isset($_POST['title']) ){
					echo newNote($USERNAME, $_POST['title']);
				}
			}
		}


	}

	function checkTitle($theTitle){
		return true;
	}

	function hasNote($id){
		global $sql;
		$sql_output = $sql->query("SELECT ID FROM note_content
			WHERE ID = '$id'");
		if( $sql_output->num_rows > 0 ){
			return true;
		}else{
			return false;
		}
	}

	function newNote($username, $title='New Note'){
		global $sql;
		if(!checkTitle($title)) return -1;
		
		$sql->query("INSERT INTO note_content (user, settings)
			VALUES ('$username', '{\"title\" = \"$title\" }' )");
		$id = $sql->insert_id;
		addSingleNoteToUser($username, $id);
		return $id;
	}	

	function newNotebook($username, $title='New Note'){
		global $sql;
		if(!checkTitle($title)) return -1;
		
		$sql->query("INSERT INTO note_content (user, settings)
			VALUES ('$username', '{\"title\" = \"$title\" }' )");
		$id = $sql->insert_id;
		addSingleNoteToUser($username, $id);
		return $id;
	}	

	function newSubnote($username, $title='New Note'){
		global $sql;
		if(!checkTitle($title)) return -1;
		
		$sql->query("INSERT INTO note_content (user, settings)
			VALUES ('$username', '{\"title\" = \"$title\" }' )");
		$id = $sql->insert_id;
		addSingleNoteToUser($username, $id);
		return $id;
	}

	function getNote($id){
		global $sql;
		$sql_output = $sql->query("SELECT content FROM note_content
			WHERE ID = '$id'");
		if( $sql_output->num_rows > 0 ){
			return $sql_output->fetch_array()['content'];
		}else{
			return false;
		}
	}

	function saveNote($id, $content){
		global $sql;
		if( hasNote($id) ){
			$sql->query("UPDATE note_content SET content = '$content'
				WHERE ID = '$id'");
		}
	}

