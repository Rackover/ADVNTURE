let input = document.getElementById("input");
let txt = "";
let flip = false;
let isFrozen = true;
let isInEditor = false;
let editorDirection = "";
let editorExistingPages = [];
let currentPage = 1;
let lastCommand = "";
let dimensionName = "";
let pageCount = 0;
let previousHourglass;
let currentPageName = "";
let isGridDimension;
let isObjectAction = false;

const maxNumberOfCharacters = 256; // I suggest you do not try to change that.
const allowedChars = ':;,!?.azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN&()-"\'$1234567890⠀';
const hpAliases = ["hp", "healthpoints", "hitpoints", "pv", "lp", "lifepoints", "healthpoint", "lifepoint"]
const hpDoubleAliases = ["health", "hit", "life"]
const hpDoubleAliasesPlurals = ["points", "point"]


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
	
    let parsed = parseText(txt);
	let hTxt = parsed.text;
	
	if (txt.substring(0, 9) === "IDENTIFY "){
		let starString = "";
		for(let i = 9; i < txt.length; i++){
			starString += "*";
		}
		hTxt = "IDENTIFY "+ starString;
	}
    
    // Adding line break if the user effectily has en empty line at the end
    if (txt[txt.length-1] == "\n"){
        hTxt += "<br>";
    }

	if (flip){
		hTxt = icon+" > "+hTxt + "_";
	}
	else{
		hTxt = icon+" > "+hTxt+"‎\u00A0";
	}
	
	if (isInEditor){		
		const lines = txt.split("\n");
		let characterCount = 0;
		for (k in lines){
			characterCount += lines[k].length;
		}
		hTxt += "<br>";
        
		if (lines.length <= 1 && lines[0].length > 0){
			if (parsed.isShortcut){
				hTxt += "<br><span class='notice'>Press ENTER to make this place a direct shortcut to '"+shortCutTo+"'</span>"
			}
			else if (parsed.isDeadEnd){
				hTxt += "<br><span class='notice'>With only one line, this will be a dead end.<br>'You cannot go there', or something like that.<br><span style='color:white;'>Press ENTER if you want to keep writing</span> and make this a real place.";
			}
            else if (parsed.isStackedLocation){
				hTxt += "<br><span class='notice'>This place will be the same as one you used the object on, but in a different state. </span>";
            }
		}
		else if (lines.length >1 && lines[lines.length-1].length === 0){
			if (characterCount > 0){
				hTxt += "<span class='notice'>Press ENTER a second time to <span style='color:white;'>end edition</span> and save that place.</span><br>If you wish to write more, you can just <span style='color:white;'>keep writing.</span></span>";
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
            
            if (txt.length > 0){
                hTxt += "<br>"+lengthNotice;
            }
            
			if (characterCount > 0){
				hTxt += "<br><br><span class='notice'>"+document.getElementById("editorTips").innerHTML+(isObjectAction ? document.getElementById("objectActionTips").innerHTML : "")+"</span>";
			}
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
                document.getElementById("minimap").innerHTML = previousHourglass;
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

function splitManually(text, characters){
	let lines = [];
	let builder = "";
	
	for(ci in text){
		const character = text[ci];
			
		if (characters.indexOf(character) > -1){
			lines.push({ character:character, content:builder});
			builder = "";
		}
		else{
			builder += character;
		}
	}	
	
	lines.push({ character:"\n", content:builder});
	
	return lines;
}

function joinManually(splitText, replaceLineBreaks = true){

	let builder = "";
	for(segmentIndex in splitText){
		const segment = splitText[segmentIndex];
		builder += segment.content;
		
		if (segmentIndex < splitText.length-1){
			builder += (replaceLineBreaks && segment.character == "\n" ? "<br>" : segment.character);
		}
	}
	
	return builder;
}

function parseText(){
	// First line has to be a line break
	let components = txt.split('\n');
	const presumedTitle = components.shift();
	const headlessText = txt.length === 0 ? "" : components.join('\n');
	
	let splitLines;
	if (headlessText.length > 0){
		splitLines = splitManually(headlessText, '\n.');
		splitLines.unshift({content: presumedTitle, character: "\n"});
	}
	else{
		splitLines = [{content: presumedTitle, character: "\n"}];
	}

	let objects = {};
	let isDeadEnd = false;
	let isShortCut = false;
    let shortCutTo = "";
    let isStackedLocation = false;
	let dryTextLines = [];
	let dryTitle = splitLines[0].content;
	let hpEvents = []
    let biomeName = null;
    let biomeColor;
	    
	if (isInEditor){
        
        // Getting biome
        const regExp = /\(([^)]+)\)/;
        const matches = regExp.exec(dryTitle);
        let biomeColor = undefined;
        
        // Custom biome?
        if (matches && matches[1]){
            let upperName = matches[1].toUpperCase();
            let biomeContent = biomeContents[upperName];
            if (biomeContent){
                biomeColor = biomeContent.color;
                biomeName = upperName.charAt(0).toUpperCase() + upperName.slice(1).toLowerCase();
                dryTitle = dryTitle.replace("("+matches[1]+")", "");
            }
        }

        // Editor code (or at least, half of it)
		if (splitLines.length <= 1 || (splitLines.length === 2 && splitLines[1].content.length === 0)){
			const shortCutTo = isExistingPage(dryTitle);
			if (shortCutTo){
				splitLines[0].content = "<span style='color:white'><u>"+dryTitle+"</u></span> <span style='color:grey'>&lt;-- This will be a shortcut to '"+existing+"'</span>";
				isShortCut = true;
			}
			else {
                if (biomeColor){
                    splitLines[0].content = "<span style='color:grey'>"+dryTitle+" (<span style='color:"+biomeColor+";'>"+biomeName+"</span>)"+(dryTitle.length === 0 ? "" : " <i>(dead end)</i></span>");     
                }
                else{
                    splitLines[0].content = "<span style='color:grey'>"+dryTitle+(dryTitle.length === 0 ? "" : " <i>(dead end)</i></span>");                    
                }
                
				isDeadEnd = true;
			}
		}
		else{
            if (isObjectAction && isGridDimension && currentPageName.trim() === dryTitle.trim()){
				splitLines[0].content = "<b><u>"+dryTitle+"</u></b> <span style='color:grey'>&lt;-- This will be the same place, but in a different state</span>";             
                isStackedLocation = true;
            }
            else if (dryTitle.length > 0){
                if (biomeColor){
                    splitLines[0].content = "<b>"+dryTitle+" (<span style='color:"+biomeColor+";'>"+biomeName+"</span>)</b> <span style='color:grey'>&lt;-- This will be the name and environment of that place</span>";
                }
                else {
                    splitLines[0].content = "<b>"+dryTitle+"</b> <span style='color:grey'>&lt;-- This will be the name of that place</span>";
                }
            }
		}
		
		for (let i=1; i< splitLines.length; i++){
			const words = splitLines[i].content.trim().split(" ");
			let isDryLine = true;
			if (words.length >= 4){
				if (words[0].toLowerCase() === "there"){
					if (words[1] === "is"){
						splitLines[i].content = "<span class='object'>"+splitLines[i].content+"</span>";
					
						objectName = words.slice(3).join(" ");
						
						// Remove trailing .
						if (objectName[objectName.length-1] === ".")
							objectName = objectName.substring(0, objectName.length-1);
						
						objects[objectName] = 1;
						isDryLine = false;
						
						splitLines[i].content+= " <span style='color:grey'>&lt;-- The "+objectName+" will be a pickable object</span>";
					}
					else if (words[1] === "are"){
						if (!isNaN(parseInt(words[2]))){
							splitLines[i].content = "<span class='object'>"+splitLines[i].content+"</span>";
							objectName = words.slice(3).join(" ");
							
							// Removing trailing . and trailing plural s
							if (objectName[objectName.length-1] === ".")
								objectName = objectName.substring(0, objectName.length-1);
							if (objectName[objectName.length-1] === "s")
								objectName = objectName.substring(0, objectName.length-1);
							
							objects[objectName] = parseInt(words[2]);
							isDryLine = false;
							
							splitLines[i].content+= " <span style='color:grey'>&lt;-- The "+words[2]+" "+objectName+"s will all be pickable objects</span>";
						}						
					}
				}
				else if (words[0].toLowerCase() === "you"){
					if (hpAliases.includes(words[3].toLowerCase().replace(".", "")) || (words.length > 4 && hpDoubleAliases.includes(words[3].toLowerCase()) && hpDoubleAliasesPlurals.includes(words[4].replace(".", "")))){
						const multiple = words[1].toLowerCase() === "gain" ? 1 : (words[1].toLowerCase() === "lose" ? -1 : 0);
						if (multiple != 0){
							const amount = parseInt(words[2]);
							if (!isNaN(amount) && amount > 0){
								isDryLine = false;
								splitLines[i].content = "<span style='color:"+(multiple>0?"lightgreen":"red")+"'>"+splitLines[i].content+"</span>";
								splitLines[i].content+= " <span style='color:grey'>&lt;-- The player will "+(multiple>0?"gain":"lose")+" "+(amount)+" health points upon entering this place</span>";
								hpEvents.push(multiple * amount)
							}								
						}
					}
				}
			}
			if (isDryLine){
				dryTextLines.push({character: splitLines[i].character, content: splitLines[i].content});
			}
		}
	}
	
	const finalText = joinManually(splitLines);
	
	return {
		text: finalText,
		props: objects,
		hpEvents: hpEvents,
		dryText: joinManually(dryTextLines, false),
		dryTitle: dryTitle,
		isDeadEnd: isDeadEnd,
		direction: editorDirection,
		origin: currentPage,
        objectTeleport: isObjectAction && !isStackedLocation,
        biome: biomeName,
        
        // Used in updateText for additional hints
        isShortCut: isShortCut,
        shortCutTo: shortCutTo,
        isStackedLocation: isStackedLocation
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
			console.log(parsed);
			callback(parsed);
		}
		catch(e){
			console.log("["+data+"]");
			console.log(e);
			txt = "";
			const parent = input.parentNode;
			
			const feedback = document.createElement("div");
			feedback.innerHTML = "<span style='color:red;'><b>The server terminated the connection, possibly because of an error.</b><br>Please reload the page to reinitialize the connection.</span><p>If you're stuck somewhere, you can type FAINT to transport yourself back to the forest outskirts.</p>";
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
		if (xhr.readyState>3) {
			success(xhr.responseText); 
		}
	};
	xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send(params);
	return xhr;
}

function interpretServerFeedback(data){
    
    const hourglassData = data.content?.hourglass ?? data?.hourglass;
    if (hourglassData){
        updateHourglass(hourglassData);
    }
    
	switch (data.type){
		default: return data.content;
		case "page":
		case "death":
		case "brief":
		case "recovery":
		case "warp":
			currentPage = data.content.id;
			if (data.type === "brief" || data.type === "recovery"){
				data.content.hp_events = [];
			}
			if (data.type === "death"){
				data.content.isDeath = true;
			}
			if (data.type === "warp" || data.type === "recovery"){
				data.content.updateDimension = true;
			}
			if (data.type === "warp"){
				data.content.isWarp = true;
			}
            
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
            setHourglassEditorMode();
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
			updateDimensionText(data.content.dimension_name, data.content.pages_count);
			return document.getElementById("intro").innerHTML;
			break;
			
		case "teleport":
			currentPage = data.content.page_id;
			return data.content.message;
			break;
            
	}
}

function updateHourglass(hourglass){
    let hourglassInfo = {
        north: hourglass.NORTH,   
        west: hourglass.WEST,
        east: hourglass.EAST,
        south: hourglass.SOUTH,
        here: hourglass.here
    };
    
    previousHourglass = document.getElementById("minimap").innerHTML;
    document.getElementById("minimap").innerHTML = getUpdatedHourglass(hourglassInfo);
}

function setHourglassEditorMode(){
    previousHourglass = document.getElementById("minimap").innerHTML;
    document.getElementById("minimap").innerHTML = getEditorHourglass();
}

function parsePage(page){
	let lines = [];
	
	const content = page.content;
	const elements = content.split("\n");
	
    currentPageName = elements[0];
    
	if (elements.length == 1){
		lines = elements;
	}
    else{
        const hourglass = content.hourglass ?? page.hourglass;
        const color = biomeContents[hourglass?.here.biome];
        const styleInfo = color == undefined ? "" : "style='color:"+color?.color+";'";
        lines.push("<b "+styleInfo+">"+elements[0]+"</b>");
    }
	
	for(let i=1; i< elements.length; i++){
		lines.push(elements[i]);
	}
		
    // Props
    if (page.props.length > 0){
        lines.push("");
    }
	for (k in page.props){
		lines.push("<span class='object'>"+
		formatPropLine(page.props[k].name, page.props[k].count)+
		".</span>");
	}
		
    // HP Events
    if (page.hp_events.length > 0){
        lines.push("");
    }
	for (k in page.hp_events){
		lines.push(formatHPEventLine(page.hp_events[k]));
	}

	if (page.updateDimension){
        isGridDimension = page.dimension_type == "GRID";
		updateDimensionText(page.dimension_name, page.pages_count, page.completion);
	}
    else if (page.completion){
		updateDimensionText(dimensionName, pageCount, page.completion);
    }

	return (page.isDeath && page.death_page != undefined ? parsePage(page.death_page) + "<br><br>" : "")
	+ (page.isDeath ? document.getElementById("death").innerHTML : "") 
	+ (page.isWarp ? document.getElementById("warp").innerHTML.replace("%dimension", initialUpperCase(page.dimension_name)) : "") 
	+ lines.join("<br>");
}

function updateDimensionText(name, count, explored){
	dimensionName = name;
    pageCount = count;
	document.getElementById("dimensionInfo").textContent = "Currently exploring region "+name+" ("+count+" places - "+Math.ceil(explored*100)+"% explored)";
}

function formatHPEventLine(value){
	return "<span style='color:"+(value > 0?"lightgreen":"red")+"'>You "+(value>0?"gain":"lose")+" "+Math.abs(value)+" health points.</span>";
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
    isObjectAction = action.split(" ")[0].toUpperCase() == "USE";
	return document.getElementById("editorNotice").innerHTML.replace("%action", action);
}

function isExistingPage(pageName){
	if (isGridDimension){
    
        return false;
        
    }
    
    
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

function initialUpperCase(str){
	return str.substring(0, 1).toUpperCase() + str.substring(1).toLowerCase();
}

initialize();

