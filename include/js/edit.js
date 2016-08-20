var NOTEID=0;


function loadNotelist(){
	$.post('edit.php',{
		action:'getNotelist',
	},
	function(data,status){
		$("#div-notelist").html(data);
	});
}

function newNote(){
	$.post('include/note.php',{
		action:'newNote',
		title:prompt()
	},
	function(data,status){
		loadNotelist();
	});
}

function newNotebook(){
	$.post('include/note.php',{
		action:'newNotebook',
		notebook:prompt()
	},
	function(data,status){
		loadNotelist();
	});
}

function newSubnote(notebook){
	$.post('include/note.php',{
		action:'newSubnote',
		notebook:notebook,
		title:prompt()
	},
	function(data,status){
		loadNotelist();
	});
}

function loadNote(id){
	// alert(id);
	if(NOTEID){
		if(NOTEID == id){
			return 0;
		}
		$("#div-notelist-item-"+NOTEID).removeClass("div-notelist-item-selected");
		$("#div-notelist-item-"+NOTEID+" .span-notelist-item-left").removeClass("span-notelist-item-left-selected");
	}
	NOTEID=id;
	$("#div-notelist-item-"+NOTEID).addClass("div-notelist-item-selected");
	$("#div-notelist-item-"+NOTEID+" .span-notelist-item-left").addClass("span-notelist-item-left-selected");
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

function showNoteDelIcon(item){
	$(item).find(".i-notelist-item-del").show();
}

function hideNoteDelIcon(item){
	$(item).find(".i-notelist-item-del").hide();
}

function showNotebookDelIcon(item){
	if($(item).parent().children(".div-notelist-item").size()==2){
		$(item).find(".i-notelist-item-del").show();
	}
}

function hideNotebookDelIcon(item){
	$(item).find(".i-notelist-item-del").hide();
}

function toggleNotebook(item){

	if($(item).hasClass("notebook-opened")){
		$(item).parent().animate({height:'28px'});
		$(item).parent().children("i").animate({rotation: -90});
	}else{
		$(item).parent().animate({height:$(item).parent().children(".div-notelist-item").size()*28+"px" });
		$(item).parent().children("i").animate({rotation: 0});
	}
	$(item).toggleClass("notebook-opened");
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




var matrixRegex = /(?:matrix\(|\s*,\s*)([-+]?[0-9]*\.?[0-9]+(?:[e][-+]?[0-9]+)?)/gi;
var getMatches = function (string, regex) {
	regex || (regex = matrixRegex);
	var matches = [
	];
	var match;
	while (match = regex.exec(string)) {
		matches.push(match[1]);
	}
	return matches;
};
$.cssHooks['rotation'] = {
	get: function (elem) {
		var $elem = $(elem);
		var matrix = getMatches($elem.css('transform'));
		if (matrix.length != 6) {
			return 0;
		}
		return Math.atan2(parseFloat(matrix[1]), parseFloat(matrix[0])) * (180 / Math.PI);
	},
	set: function (elem, val) {
		var $elem = $(elem);
		var deg = parseFloat(val);
		if (!isNaN(deg)) {
			$elem.css({
				transform: 'rotate(' + deg + 'deg)'
			});
		}
	}
};
$.cssNumber.rotation = true;
$.fx.step.rotation = function (fx) {
	$.cssHooks.rotation.set(fx.elem, fx.now + fx.unit);
};
