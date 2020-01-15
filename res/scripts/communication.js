let input = document.getElementById("input");
let txt = "";
let flip = false;
let isFrozen = true;
let isInEditor = false;
let editorDirection = "";
let editorExistingPages = [];
let currentPage = 1;
let lastCommand = "";


const maxNumberOfCharacters = 256; // I suggest you do not try to change that.
const allowedChars = ':;,!?.azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN&()-"\'$1234567890⠀';

// Mobile
window.onload = function() {	
	if (detectmob()){
		const toRemove = [
			document.getElementById("introWelcomeBar1"),
			document.getElementById("introWelcomeBar2"),
			document.getElementById("introBigTitle")
		];
		for(k in toRemove){
			toRemove[k].parentNode.removeChild(toRemove[k]);
		}
		
		const txtArea = document.createElement("textarea");
		document.getElementById("mainContainer").appendChild(txtArea);
		txtArea.focus();
		txtArea.style.opacity = "0";
		txtArea.style.width = "99%";
		txtArea.style.height = "99%";
		txtArea.style.position = "absolute";
		txtArea.style.top = "0";
		txtArea.style.left = "0";
		txtArea.style.margin = "0";
		txtArea.style.padding = "0";
		txtArea.style.verticalAlign = "bottom";
		txtArea.style.paddingTop = "70%";
		
		txtArea.oninput = function(e){			
			let str = txtArea.value[txtArea.value.length-1];
			let code = txtArea.value.length <= 0 ? 0 : str.charCodeAt(0);
			
			if (str === "\n" || code === 10){
				code = 13;
				str = "\n";
			}
			else{
				if (isInEditor){
					txt = txtArea.value;
				}
				else{
					txt = txtArea.value.toUpperCase().replace(/(\r\n|\n|\r)/gm,"");
				}
			}
			
			if (isFrozen){
				return;		
			}

			let prevent = true;
			if (allowedChars.includes(str)){
				// Do nothing, text area is already being inputted to
			}				
			else if (code === 13){
				if (isInEditor) inputToConsole("\n");
				else if (txt.length > 0){
					txtArea.value = "";
					submitToConsole();
				}
				else prevent = false;
			}
		    else if (code === 38 && !isInEditor){
				txt = lastCommand;
			}
			else 
				prevent = false;

			if (prevent){
				updateText();
			}
		}
	}
	else{
		document.addEventListener("keydown", function(event){
			event.preventDefault();
			keyPress(event);
		});
		document.addEventListener("keypress", keyPress);
	}
}
// Endof

function keyPress(keyEvent){
	if (isFrozen){
		keyEvent.preventDefault();
		return;		
	}

	let prevent = true;
	if (keyEvent.keyCode == 8) backspaceConsole();
	//else if (keyEvent.keyCode == 46) deleteConsole();
	else if ((keyEvent.keyCode >= 48 && keyEvent.keyCode <= 90) || keyEvent.keyCode == 32 || allowedChars.includes(keyEvent.key)) 
		inputToConsole(keyEvent.key);
	else if (keyEvent.keyCode === 13){
		if (isInEditor) inputToConsole("\n");
		else if (txt.length > 0) submitToConsole();
		else prevent = false;
	}
  else if (keyEvent.keyCode === 38 && !isInEditor){
    txt = lastCommand;
  }
	else prevent = false;

	if (prevent){
		keyEvent.preventDefault();
		updateText();
	}
}

function updateText(){
	if (isFrozen) return;
	
	const icon = isInEditor ? "⚒" : "☺";
	
	let hTxt = parseText(txt).text;
	
	if (txt.substring(0, 9) === "IDENTIFY "){
		let starString = "";
		for(let i = 9; i < txt.length; i++){
			starString += "*";
		}
		hTxt = "IDENTIFY "+ starString;
	}

	if (flip){
		hTxt = icon+" > "+hTxt + "_";
	}
	else{
		hTxt = icon+" > "+hTxt+"‎\u00A0";
	}
	
	if (isInEditor){		
		const lines = txt.split("\n");
		hTxt += "<br>";
		if (lines.length <= 1 && lines[0].length > 0){
			const existing = isExistingPage(lines[0]);
			if (existing){
				hTxt += "<span class='notice'>Press ENTER to make this place a direct shortcut to '"+existing+"'</span>"
			}
			else{
				hTxt += "<span class='notice'>With only one line, this will be a dead end.<br>'You cannot go there', or something like that.<br>Press ENTER if you want to keep writing and make this a real place.";
			}
		}
		else if (lines.length >1 && lines[lines.length-1].length === 0){
			let characterCount = 0;
			for (k in lines){
				characterCount += lines[k].length;
			}
			if (characterCount > 0){
				hTxt += "<span class='notice'>Press ENTER a second time to end edition and save that place.<br>If you wish to write more, you can also keep writing.";
				hTxt += "</span>";
			}
			else{
				hTxt += "<span class='notice'>Press ENTER a second time to abort edition and go back to where you were.</span>";
			}
		}
		else{
			lengthNotice = txt.length + "/"+maxNumberOfCharacters;
			if (txt.length >= maxNumberOfCharacters){
				lengthNotice = "<span style='color:red;'>"+lengthNotice+"</span>";
			}
			else{
				lengthNotice = "<span style='color:darkGrey;'>"+lengthNotice+"</span>";
			}
			hTxt+= lengthNotice;
		}
	}
	
	input = document.getElementById("input");
	input.innerHTML = hTxt;
	flip = !flip;
		
	input.parentNode.scrollTop = input.parentNode.scrollHeight;
}

function backspaceConsole(){
	txt = txt.substring(0, txt.length - 1);
}

function deleteConsole(){
	txt = txt.substring(1, txt.length);
}

function inputToConsole(letter){
	const lines = txt.split("\n");
	if (letter === "\n"){ // That means we are in editor
		if (isExistingPage(lines[0])){
			submitToConsole();
		}
		else if (
			lines.length >1 && 
			lines[lines.length-1].length === 0
		){
			let characterCount = 0;
			for (k in lines){
				characterCount += lines[k].length;
			}
			if (characterCount > 0){
				submitToConsole();
			}
			else{
				isInEditor = false;
				txt = "";
				let feedbacks = document.getElementsByClassName("feedback");
				feedbacks[feedbacks.length-1].innerHTML += "You decided not to explore this direction, and to return to your previous location instead.";
			}
			return;
		}
	}
	else if (isInEditor && txt.length >= maxNumberOfCharacters){
		return;
	}
	txt += isInEditor ? letter : letter.toUpperCase();
}

function clearConsole(){	
	const parent = input.parentNode;
	
	while (parent.firstChild) {
		parent.removeChild(parent.firstChild);
	}
}

function parseText(){
	const lines = txt.split("\n");
	
	let objects = {};
	let isDeadEnd = false;
	let isShortCut = false
	let dryTextLines = [];
	let dryTitle = lines[0];
	
	if (isInEditor){
		if (lines.length <= 1 || (lines.length === 2 && lines[1].length === 0)){
			const existing = isExistingPage(lines[0]);
			if (existing){
				lines[0] = "<span style='color:green'>"+lines[0]+"</span> <span style='color:grey'>&lt;-- This will be a shortcut to '"+existing+"'</span>";
				isShortCut = true;
			}
			else{
				lines[0] = "<span style='color:red'>"+lines[0]+"</span>";
				isDeadEnd = true;
			}
		}
		else{
			lines[0] = "<b>"+lines[0]+"</b> <span style='color:grey'>&lt;-- This will be the name of that place</span>";
		}
		
		for (let i=1; i< lines.length; i++){
			const words = lines[i].split(" ");
			let isDryLine = true;
			if (words.length >= 4){
				if (words[0] === "There"){
					if (words[1] === "is"){
						lines[i] = "<span class='object'>"+lines[i]+"</span>";
					
						objectName = words.slice(3).join(" ");
						
						// Remove trailing .
						if (objectName[objectName.length-1] === ".")
							objectName = objectName.substring(0, objectName.length-1);
						
						objects[objectName] = 1;
						isDryLine = false;
						
						lines[i]+= " <span style='color:grey'>&lt;-- The "+objectName+" will be a pickable object</span>";
					}
					else if (words[1] === "are"){
						if (!isNaN(parseInt(words[2]))){
							lines[i] = "<span class='object'>"+lines[i]+"</span>";
							objectName = words.slice(3).join(" ");
							
							// Removing trailing . and trailing plural s
							if (objectName[objectName.length-1] === ".")
								objectName = objectName.substring(0, objectName.length-1);
							if (objectName[objectName.length-1] === "s")
								objectName = objectName.substring(0, objectName.length-1);
							
							objects[objectName] = parseInt(words[2]);
							isDryLine = false;
							
							lines[i]+= " <span style='color:grey'>&lt;-- The "+words[2]+" "+objectName+"s will all be pickable objects</span>";
						}						
					}
				}
			}
			if (isDryLine){
				dryTextLines.push(lines[i]);
			}
		}
	}
	
	let finalText = lines.join("<br>");
	return {
		text: finalText,
		props: objects,
		dryText: dryTextLines.join("\n"),
		dryTitle: dryTitle,
		isDeadEnd: isDeadEnd,
		direction: editorDirection,
		origin: currentPage
	};
}

function initialize(){
	post({ action: "RECOVER"}, function(data){
		input = document.getElementById("input");
		
		const feedback = document.createElement("div");
		const content = interpretServerFeedback(data);
		
		feedback.innerHTML = content;
		feedback.className = "feedback";
		input.parentNode.prepend(feedback);
		
		isFrozen = false;
		setInterval(updateText, 100);
	});
}

function submitToConsole(){
	isFrozen = true;
	input.innerHTML = "> "+parseText(txt).text;
	if (txt.substring(0, 9) === "IDENTIFY "){
		let starString = "";
		for(let i = 9; i < txt.length; i++){
			starString += "*";
		}
		input.innerHTML = "> IDENTIFY "+ starString;
	}
	input.id = "";
	input.className = "history";
	
	let action = { action:"COMMAND", command:txt};
	
	if (isInEditor){
		action = {
			action: "SUBMISSION",
			submission: JSON.stringify(parseText(txt))
		}
		isInEditor = false;
	}
  else{
    lastCommand = txt;
  }
	
	post(action, function(data){	
		txt = "";
		const parent = input.parentNode;
		const content = interpretServerFeedback(data);
		
		const feedback = document.createElement("div");
		feedback.innerHTML = content;
		feedback.className = "feedback";
		parent.appendChild(feedback);
		
		const newInput = document.createElement("div");
		parent.appendChild(newInput);
		input = newInput;
		input.id = "input";
		isFrozen = false;
	});
}

function post(payload, callback){
	postAjax('communicator.php', payload, function(data){
		try{
			const parsed = JSON.parse(data);
			callback(parsed);
		}
		catch(e){
			console.log(data);
			txt = "";
			const parent = input.parentNode;
			
			const feedback = document.createElement("div");
			feedback.innerHTML = "<span style='color:red;'><b>The server terminated the connection, possibly because of an error.</b><br>Please reload the page to reinitialize the connection.</span><p>If you're stuck somewhere, you can type RESET to transport yourself back to the forest outskirts.</p>";
			feedback.className = "feedback";
			parent.appendChild(feedback);
			return;
		}
	});
}

function postAjax(url, data, success) {
	var params = typeof data == 'string' ? data : Object.keys(data).map(
			function(k){ return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]) }
		).join('&');

	var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
	xhr.open('POST', url);
	xhr.onreadystatechange = function() {
		if (xhr.readyState>3) { success(xhr.responseText); }
	};
	xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send(params);
	return xhr;
}

function interpretServerFeedback(data){
	switch (data.type){
		default: return data.content;
		case "page":
			currentPage = data.content.id;
			return parsePage(data.content);
		case "props":
			if (data.content.length < 1)
				return "You have nothing on you.";
			
			let props = [];
			for(k in data.content){
				props.push(formatPropLine(data.content[k], 1, true));
			}
			return props.join("<br>");
			break;
			
		case "editor":
			editorDirection = data.content.direction;
			editorExistingPages = data.content.existing_pages;
			return showEditor(data.content.direction);
			break;
			
		case "clear":
			clearConsole();
			return "Cleared console.";
			break;
			
		case "help":
			return document.getElementById("help").innerHTML;
			break;
			
		case "credits":
			return document.getElementById("credits").innerHTML;
			break;
			
		case "intro":
			currentPage = data.content.id;
			return document.getElementById("intro").innerHTML;
			break;
			
		case "teleport":
			currentPage = data.content.page_id;
			return data.content.message;
			break;
	}
}

function parsePage(page){
	let lines = [];
	const elements = page.content.split("\n");
	lines.push("<b>"+elements[0]+"</b>");
	if (elements.length == 1){
		lines = elements;
	}
	
	for(let i=1; i< elements.length; i++){
		lines.push(elements[i]);
	}
		
	for (k in page.props){
		lines.push("<span class='object'>"+
		formatPropLine(page.props[k].name, page.props[k].count)+
		".</span>");
	}
	
	return lines.join("<br>");
}

function formatPropLine(name, count, isInventory=false){
	return (isInventory ? "You have" : ("There "+(count > 1 ? "are" : "is"))) + " "+
		(count>1 ? count : (isVowel(name[0]) ? "an" : "a"))+" "+
		name.toLowerCase()+(count>1?"s":"")	
}

function isVowel(c) {
    return ['a', 'e', 'i', 'o', 'u'].indexOf(c.toLowerCase()) !== -1
}

function showEditor(action, place){
	isInEditor = true;
	return document.getElementById("editorNotice").innerHTML.replace("%action", action);
}

function isExistingPage(pageName){
	
	const upperCaseNames = editorExistingPages.map(function(value) {
	  return value.toUpperCase();
	});
	const pos = upperCaseNames.indexOf(pageName.toUpperCase());
	return (pos >= 0 ? editorExistingPages[pos] : false);
}

function detectmob() { 
 if( navigator.userAgent.match(/Android/i)
 || navigator.userAgent.match(/webOS/i)
 || navigator.userAgent.match(/iPhone/i)
 || navigator.userAgent.match(/iPad/i)
 || navigator.userAgent.match(/iPod/i)
 || navigator.userAgent.match(/BlackBerry/i)
 || navigator.userAgent.match(/Windows Phone/i)
 ){
    return true;
  }
 else {
    return false;
  }
}

initialize();

