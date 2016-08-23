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
			if( $_POST['action'] == 'newNotebook' ){
				if( isset($_POST['notebook']) ){
					echo newNotebook($USERNAME, $_POST['notebook']);
				}
			}
			if( $_POST['action'] == 'newSubnote' ){
				if( isset($_POST['notebook']) && isset($_POST['title']) ){
					echo newSubnote($USERNAME, $_POST['notebook'], $_POST['title']);
				}
			}

			if( $_POST['action'] == 'getNote' ){
				if( isset($_POST['id']) && checkNoteUser($_POST['id'], $USERNAME) ){
					echo getNote($_POST['id']);
				}
			}

			if( $_POST['action'] == 'saveNote' ){
				if( isset($_POST['id']) && isset($_POST['content']) && checkNoteUser($_POST['id'], $USERNAME) ){
					echo saveNote($_POST['id'], $_POST['content']);
				}
			}

			if( $_POST['action'] == 'delNote' ){
				if( isset($_POST['id']) && checkNoteUser($_POST['id'], $USERNAME) ){
					echo delNote($_POST['id']);
				}
			}

			if( $_POST['action'] == 'delNotebook' ){
				if( isset($_POST['notebook']) ){
					echo delNotebook($_POST['notebook']);
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
			VALUES ('$username', '{\"title\" : \"$title\" }' )");
		$id = $sql->insert_id;
		addSingleNoteToUser($username, $id);
		return 'ok';
	}	

	function newNotebook($username, $notebook){
		if(!checkTitle($notebook)) return -1;
		
		addNotebookToUser($username, $notebook);
	}	

	function newSubnote($username, $notebook, $title='New Note'){
		global $sql;
		if(!checkTitle($notebook)) return -1;
		if(!checkTitle($title)) return -1;
		
		$sql->query("INSERT INTO note_content (user, settings)
			VALUES ('$username', '{\"title\" : \"$title\" }' )");
		$id = $sql->insert_id;
		addNoteToNotebook($username, $notebook, $id);
		return 'ok';
	}

	function getNote($id){
		global $sql;
		$sql_output = $sql->query("SELECT content FROM note_content
			WHERE ID = '$id'");
		if( $sql_output->num_rows > 0 ){
			$content = $sql_output->fetch_array()['content'];
			$content = str_replace("&amp;", "&",$content);
			$content = str_replace("&#39;", "'",$content);
			$content = str_replace("&#42;", "\"",$content);
			$content = str_replace("&#61;", "=",$content);
			$content = str_replace("&#63;", "?",$content);
			$content = str_replace("&#92;", "\\",$content);
			return $content;

		}else{
			return false;
		}
	}

	function getNoteTitle($id){
		global $sql;
		$sql_output = $sql->query("SELECT settings FROM note_content
			WHERE ID = '$id'");
		if( $sql_output->num_rows > 0 ){
			return json_decode($sql_output->fetch_array()['settings'], true)['title'];
		}else{
			return false;
		}
	}

	function getNoteUser($id){
		global $sql;
		$sql_output = $sql->query("SELECT user FROM note_content
			WHERE ID = '$id'");
		if( $sql_output->num_rows > 0 ){
			return $sql_output->fetch_array()['user'];
		}else{
			return false;
		}
	}

	function checkNoteUser($id, $username){
		return getNoteUser($id) == $username;
	}

	function saveNote($id, $content){
		global $sql;
		if( hasNote($id) ){
			$content = str_replace("&", "&amp;", $content);
			$content = str_replace("'", "&#39;", $content);
			$content = str_replace("\"", "&#42;", $content);
			$content = str_replace("=", "&#61;", $content);
			$content = str_replace("?", "&#63;", $content);
			$content = str_replace("\\", "&#92;", $content);

			$sql->query("UPDATE note_content SET content = '$content'
				WHERE ID = '$id'");
			return 'ok';
		}
	}

	function delNote($id){
		global $sql, $USERNAME;
		if( hasNote($id) ){
			$sql->query("DELETE FROM note_content
				WHERE ID = '$id'");
			removeNoteFromUser($USERNAME, $id);
		}
	}

	function delNotebook($notebook){
		global $USERNAME;

		removeNotebookFromUser($USERNAME, $notebook);
	}
