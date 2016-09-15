var NOTEID=0;


function doLayout(){
	var winh=window.innerHeight
		|| document.documentElement.clientHeight
		|| document.body.clientHeight;

	var winw=window.innerWidth
		|| document.documentElement.clientWidth
		|| document.body.clientWidth;

	$("#content").height(winh-56);
	$("#sidebar").height(winh-56);
	$("#editor").height(winh-56);

	$("#editor").width(winw-240);

	document.getElementById("editor-move").style.left  = (winw-240)/2 + "px";
	document.getElementById("editor-ace").style.width = (winw-240)/2 + "px";
	document.getElementById("editor-show").style.width = (winw-240)/2 - 5 + "px";
	document.getElementById("editor-show").style.marginLeft = (winw-240)/2 + 5 + "px";

}

window.onresize = function () {
	doLayout();
}

function loadNotelist(){
	$.post("edit.php",{
		action:"getNotelist"
	},
	function(data,status){
		$("#sidebar-notelist").html(data);
		var notelist = document.getElementById("sidebar-notelist");

		// list 1: notes in lost
		Sortable.create(notelist, {
			group: {
			  name: "notelist",
			  put: ["notebooklist", "sublist"],
			  pull:true
			},
			ghostClass: 'div-notelist-item-moving',
			animation: 150,
			draggable: ".div-notelist-item-single",
			handle: ".handle1",
			onSort: function(evt){
				updateList();
			}
		});

		// list 2: notebooks in list
		Sortable.create(notelist, {
			group: {
			  name: "notebooklist",
			  put: ["notelist", "sublist"],
			  pull:true
			},
			ghostClass: 'div-notelist-item-moving',
			animation: 150,
			draggable: ".div-notelist-folder",
			handle: ".handle1",
			onSort: function(evt){
				updateList();
			}
		});

		// other lists: notes in each notebooks
		$("#sidebar-notelist .div-notelist-folder").each(function(){
			Sortable.create(this, {
				group: {
				  name: "sublist",
				  put: ["notelist"],
				  pull:true
				},
				ghostClass: 'div-notelist-item-moving',
				animation: 150,
				draggable: ".div-notelist-item-subnote",
				onSort: function(evt){
					updateList();
				}
			});
		});

		$("#sidebar-notelist .div-notelist-item-single").each(function(){
			this.oncontextmenu = function(event){
				showNoteContext(this, event);
				return false;
			}
		});
		$(".div-notelist-item-subnote").each(function(){
			this.oncontextmenu = function(event){
				showNoteContext(this, event);
				return false;
			}
		});
	});
}

function updateList(){
	theList = $("#sidebar-notelist");

	theList.children(".div-notelist-item-subnote").children(".span-notelist-item-left").removeClass("span-notelist-item-left-subnote");
	theList.children(".div-notelist-item-subnote").children(".span-notelist-item-text").addClass("handle1");
	theList.children(".div-notelist-item-subnote").addClass("div-notelist-item-single");
	theList.children(".div-notelist-item-subnote").removeClass("div-notelist-item-subnote");

	theList.children(".div-notelist-folder").children(".div-notelist-item-single").children(".span-notelist-item-left").addClass("span-notelist-item-left-subnote");
	theList.children(".div-notelist-folder").children(".div-notelist-item-single").children(".span-notelist-item-text").removeClass("handle1");
	theList.children(".div-notelist-folder").children(".div-notelist-item-single").addClass("div-notelist-item-subnote");
	theList.children(".div-notelist-folder").children(".div-notelist-item-single").removeClass("div-notelist-item-single");

	newList = new Array();

	theList.children().each(function(){
		if( $(this).hasClass("div-notelist-item-single") ){
			if($(this).attr("id")){
				newList.push(parseInt( $(this).attr("id").substring(18) ));
			}
		}
		if( $(this).hasClass("div-notelist-folder") ){
			tmp = new Array();
			tmp.push( $(this).children(".div-notelist-item-notebook-title").text() );
			$(this).children(".div-notelist-item-subnote").each(function(){
				tmp.push(parseInt( $(this).attr("id").substring(18) ));
				// alert($(this).attr("id"));
			});
			// alert(tmp);
			newList.push(tmp);
			// newList.push(parseInt( $(this).attr("id").substring(18) ));
			// alert(parseInt($(this).attr("id").substring(18)));
		}
	});

	newListJSON = JSON.stringify(newList);
	// alert(newListJSON);

	$.post("include/note.php",{
		action:"updateNoteList",
		list:newListJSON
	},
	function(data,status){
		// alert("Status: " + status + data );
	});

}

function newNote(){
	$.post("include/note.php",{
		action:"newNote",
		title:prompt()
	},
	function(data,status){
		loadNotelist();
	});
}

function newNotebook(){
	$.post("include/note.php",{
		action:"newNotebook",
		notebook:prompt()
	},
	function(data,status){
		loadNotelist();
	});
}

function newSubnote(notebook){
	$.post("include/note.php",{
		action:"newSubnote",
		notebook:notebook,
		title:prompt()
	},
	function(data,status){
		loadNotelist();
	});
}

function loadNote(id){
	updateStatusBar("#f1c40f", "Loading note...");
	if(NOTEID){
		if(NOTEID == id){
			updateStatusBar("#0f2", "Note loaded");
			return 0;
		}
		$("#div-notelist-item-"+NOTEID).removeClass("div-notelist-item-selected2");
		// $("#div-notelist-item-"+NOTEID+" .span-notelist-item-left").removeClass("span-notelist-item-left-selected");
		if($("#div-notelist-item-"+NOTEID).hasClass("div-notelist-item-subnote")){
			if( $("#div-notelist-item-"+NOTEID).parent() !=  $("#div-notelist-item-"+id).parent() ){
				$("#div-notelist-item-"+NOTEID).parent().children(".notebook-opened").removeClass("div-notelist-item-selected");
				$("#div-notelist-item-"+NOTEID).parent().children(".notebook-opened").children(".span-notelist-item-left").removeClass("span-notelist-item-left-selected");
			}
		}
	}
	NOTEID=id;
	$("#div-notelist-item-"+NOTEID).addClass("div-notelist-item-selected2");
	// $("#div-notelist-item-"+NOTEID+" .span-notelist-item-left").addClass("span-notelist-item-left-selected");
	if($("#div-notelist-item-"+NOTEID).hasClass("div-notelist-item-subnote")){
		$("#div-notelist-item-"+NOTEID).parent().children(".notebook-opened").addClass("div-notelist-item-selected");
		$("#div-notelist-item-"+NOTEID).parent().children(".notebook-opened").children(".span-notelist-item-left").addClass("span-notelist-item-left-selected");
	}
	NoteLoding=true;
	$.post("include/note.php",{
		action:"getNote",
		id:id
	},
	function(data,status){
		// alert("Status: " + status + data );
		EditorAce.session.setValue(data);
		updateEditorShow();
		updateStatusBar("#0f2", "Note loaded");
		NoteLoding=false;
	});
}

var NoteAutosaving = false;
var NoteAutosaveWaiting = false;
function autosaveNote(){
	if(NOTEID){
		if(!NoteAutosaving){
			NoteAutosaving = true;
			setTimeout(function(){
				NoteAutosaving = false;
				if(NoteAutosaveWaiting){
					NoteAutosaveWaiting = false;
					autosaveNote();
				}
			}, 1500);
			updateStatusBar("#f1c40f", "Saving...");
			$.post("include/note.php",{
				action:"saveNote",
				id:NOTEID,
				content:EditorAce.getValue()
			},
			function(data,status){
				// alert("Status: " + status + data );
				updateStatusBar("#0f2", "Note saved");
			});
		}else{
			NoteAutosaveWaiting = true;
		}
	}
}

function saveNote(){
	updateStatusBar("#f1c40f", "Saving...");
	$.post("include/note.php",{
		action:"saveNote",
		id:NOTEID,
		content:EditorAce.getValue()
	},
	function(data,status){
		// alert("Status: " + status + data );
		updateStatusBar("#0f2", "Note saved");
	});
}

function delNote(id){
	$.post("include/note.php",{
		action:"delNote",
		id:id
	},
	function(data,status){
		// alert("Status: " + status + data );
		loadNotelist();
	});
}

function delNotebook(notebook){
	$.post("include/note.php",{
		action:"delNotebook",
		notebook:notebook
	},
	function(data,status){
		// alert("Status: " + status + data );
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
		$(item).parent().animate({height:"28px"});
		$(item).parent().children("i").animate({rotation: -90});
	}else{
		$(item).parent().animate({height:$(item).parent().children(".div-notelist-item").size()*28+"px" });
		$(item).parent().children("i").animate({rotation: 0});
	}
	$(item).toggleClass("notebook-opened");
}

var EditorShowProcessing = false;
var EditorShowWaiting = false;
function updateEditorShow(){
	if(!EditorShowProcessing){
		EditorShowProcessing = true;

		document.getElementById("editor-show-preprocess").innerHTML = marked(EditorAce.getValue());
		Prism.highlightAll();
		// MathJax.Hub.Process(document.getElementById("editor-show-preprocess"));
		// MathJax.Hub.Update(document.getElementById("editor-show-preprocess"));

		MathJax.Hub.Queue(["Typeset",MathJax.Hub,"editor-show-preprocess"], function(){
			document.getElementById("editor-show").innerHTML = document.getElementById("editor-show-preprocess").innerHTML;
			EditorShowProcessing = false;
			if(EditorShowWaiting){
				updateEditorShow();
				EditorShowWaiting = false;
			}
		});
	}else{
		EditorShowWaiting = true;
	}

	// MathJax.Hub.Reprocess(document.getElementById("editor-show"));
	// MathJax.Hub.Queue(["Typeset",MathJax.Hub,"editor-show-preprocess"], function(){
	// 	document.getElementById("editor-show").innerHTML = document.getElementById("editor-show-preprocess").innerHTML
	// });
	// MathJax.Hub.Reprocess(document.getElementById("editor-show-preprocess"), function(){
	// 	document.getElementById("editor-show").innerHTML = document.getElementById("editor-show-preprocess").innerHTML
	// });
	// document.getElementById("editor-show").innerHTML = document.getElementById("editor-show-preprocess").innerHTML

}

function updateStatusBar(color, text){
	document.getElementById("sidebar-status-icon").style.color = color;
	document.getElementById("sidebar-status-text").innerHTML = text;
}

var noteContextID = 0;
function showNoteContext(item, event){
	var e = event || window.event;
	var context = $("#contextmenu-1");
	context.show();
	$("#contextmenu-1").css({
		"top" : e.clientY+'px',
		"left" : e.clientX+'px'
	});
	noteContextID = parseInt($(item).attr("id").substring(18));
	$(item).addClass("div-notelist-item-contextmenu-show");
}

function contextDelNote(){
	delNote(noteContextID);
	$("#contextmenu-1").hide();
}

function showNotebookContext(){

}

var EditorAce;
var NoteLoding=false;
$(document).ready(function(){
	loadNotelist();
	doLayout();
	updateStatusBar("#bdc3c7", "Ready");

	$("#btn-newnote").click(function(){
		$.post("include/note.php",{
			action:"newNote",
			title:$("#input-newnote").val()
		},
		function(data,status){
			// alert("Status: " + status + data );
			loadNotelist();
		});
	});	

	$("#btn-newnotebook").click(function(){
		$.post("include/note.php",{
			action:"newNotebook",
			notebook:$("#input-newnotebook").val()
		},
		function(data,status){
			// alert("Status: " + status + data );
			loadNotelist();
		});
	});	

	$("#btn-subnote").click(function(){
		$.post("include/note.php",{
			action:"newSubnote",
			notebook:$("#input-subnote-book").val(),
			title:$("#input-subnote-note").val()
		},
		function(data,status){
			// alert("Status: " + status + data );
			loadNotelist();
		});
	});

	document.onclick=function(event){
		var e = event || window.event;
		var doHide = true;
		$(".contextmenu").each(function(){
			contextmenuPos = $(this).offset();
			if( contextmenuPos.left <= e.clientX && e.clientX <= contextmenuPos.left+$(this).width() &&
				contextmenuPos.top <= e.clientY && e.clientY <= contextmenuPos.top+$(this).height() ){
				doHide = false;
			}
			// alert(contextmenuPos.x+"px "+contextmenuPos.y+"px "+e.clientX+"px "+e.clientY+"px ");
		});


		// alert("hide");
		if(doHide){
			if(noteContextID){
				$("#div-notelist-item-"+noteContextID).removeClass("div-notelist-item-contextmenu-show");
			}
			$("#contextmenu-1").hide();
		}
	}; 

	var oBox = document.getElementById("editor"), oLeft = document.getElementById("editor-ace"), oRight = document.getElementById("editor-show"), oMove = document.getElementById("editor-move");
	oMove.onmousedown = function(e){
		var winw=window.innerWidth
			|| document.documentElement.clientWidth
			|| document.body.clientWidth;
		var disX = (e || event).clientX;
		oMove.left = oMove.offsetLeft;
		document.onmousemove = function(e){
			var iT = oMove.left + ((e || event).clientX - disX);
			var e=e||window.event,tarnameb=e.target||e.srcElement;
			oMove.style.margin = 0;
			iT < 100 && (iT = 100);
			iT > oBox.clientWidth - 100 && (iT = oBox.clientWidth - 100);
			oMove.style.left  = iT + "px";
			oLeft.style.width = iT + "px";
			oRight.style.width = oBox.clientWidth - iT - 5 + "px";
			oRight.style.marginLeft = iT + 5 + "px";
			return false
		};
		document.onmouseup = function(){
			document.onmousemove = null;
			document.onmouseup = null;
			oMove.releaseCapture && oMove.releaseCapture();
			EditorAce.resize();
		};
		oMove.setCapture && oMove.setCapture();
		return false
	};

	//初始化ACE编辑器
	EditorAce = ace.edit("editor-ace");
	EditorAce.setTheme("ace/theme/tomorrow_night_eighties");
	EditorAce.getSession().setMode("ace/mode/markdown");
	EditorAce.getSession().setUseWrapMode(true);
	updateEditorShow();

	//ACE编辑器的内容改变事件
	EditorAce.getSession().on("change", function(e){
		if(!NoteLoding){
			updateEditorShow();
		}
	});

	MathJax.Hub.Config({
		showProcessingMessages: false,
		elements: ["editor-show"]
	});

	$(".ace_scrollbar-v").attr("id","editor-ace-scrollbar"); //给ACE编辑器的滚动条添加名称

	$("#editor-ace-scrollbar").scroll(function(){
		var t = $(this)[0].scrollTop; //获取编辑区滚动值

		// 自动同步滚动,算法:
		// 预览区滚动值 = 编辑区滚动值 * [ (预览区总滚动高度 - 预览区显示高度) / (编辑区总滚动高度 - 编辑区显示高度) ]
		document.getElementById("editor-show").scrollTop=t * (document.getElementById("editor-show").scrollHeight-document.getElementById("editor-show").offsetHeight) / (document.getElementById("editor-ace-scrollbar").scrollHeight-document.getElementById("editor-ace-scrollbar").offsetHeight);
	});

	$(document).keydown(function(e){
		if( e.ctrlKey && e.which == 83 ){
			saveNote();
			return false;
		}
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
$.cssHooks["rotation"] = {
	get: function (elem) {
		var $elem = $(elem);
		var matrix = getMatches($elem.css("transform"));
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
				transform: "rotate(" + deg + "deg)"
			});
		}
	}
};
$.cssNumber.rotation = true;
$.fx.step.rotation = function (fx) {
	$.cssHooks.rotation.set(fx.elem, fx.now + fx.unit);
};
