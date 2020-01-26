<?php 
 ini_set('display_errors', 1); 
 ini_set('display_startup_errors', 1); 
 error_reporting(E_ALL);
 
	include("database.php");
	include("page.php");
	$count = get_page_count(get_connection());
?>

<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<LINK href="style.css?version=<?php echo rand();?>" rel="stylesheet" type="text/css">
		<link rel="icon" href="favicon.jpg" />
		<title>ADVNTURE.WEB</title>
		<script type="text/javascript" src="res/scripts/communication.js?version=<?php echo rand();?>"></script>
		  
		  <meta property="og:title" content="ADVNTURE.WEB">
		  <meta property="og:description" content="Take a part in the greatest of adventures: <br>Yours.">
		  <meta property="og:image" content="/res/img/metapreview.jpg">
	</head>
	<body>
		<div id="mainContainer">
			<div id="title">
				<h1>
					ADVNTURE V1.1
				</h1>
				<h2>
					An official sequel to EXPLORER - host to <?php echo $count; ?> places
				</h2>
			</div>
			<div class="terminal">
				<div id="input">
					Please wait...
				</div>
			</div>
			<div style="margin-top:5px;display:flex;flex-direction:row;width:100%; justify-content:space-between;font-size:0.8em;">
				<div style="text-align:left;">
					Made by louve@louve.systems
				</div>
				<div>
					Here's a <a style="color:white;" target="_blank" href="https://www.youtube.com/watch?v=QIxRDU_l8Bc&loop=1">fitting soundtrack</a> if you need one! 	
				</div>
				<div style="text-align:right;">
					Follow me on <a style="color:white;" target="_blank" href="https://twitter.com/Rackover">twitter</a>!
				</div>
			</div>
			
			<div id="editorNotice" style="display:none;">
				<b>What happens now?</b><br>
				Since no one has ever done that here before, this situation is for you to tell others about.<br>
				What is it? What will you find here? Where does it lead?<br>
				That's for <b>you</b> and only you to decide.<br><br>
				
				<span class="emphasis">Write what you would like others to experience when they try to do what you just did.</span><br>
				Ask yourself: what should happen when an adventurer does <b>[ %action ]</b> here?
				<ul style="list-style: none">
					<li>- Use <b>ENTER</b> to insert line breaks</li>
					<li>- Insert two line breaks when you're <b>done</b> telling</li>
					<li>- The first line you write will be the <b>title</b></li>
					<li>- Add props by saying "<b>There is </b>1 something" or "<b>There are</b> X somethings"</li>
					<li>- If you write only one line, this shall not be a place, but a dead end.</li>
					<li>- You can also name your place exactly like another existing location to create a <b>shortcut</b>.</li>
				</ul>
				
			</div>
			<div id="help" style="display:none;">
				List of commands for ADVNTURE:<br>
				<ul style="list-style: none">
					<li>- <b>BRIEF</b>: Describes your current location.</li>
					<li>- <b>SOUTH, NORTH, EAST, WEST</b>: Moves you to another location from where you currently are.</li>
					<li>- <b>TAKE &lt;object&gt;</b>: Takes an object and adds it to your inventory.</li>
					<li>- <b>LOSE &lt;object&gt;</b>: Removes an object from your inventory and puts it back where you took it.</li>
					<li>- <b>USE &lt;object&gt;</b>: Uses an object at your current location.</li>
					<li>- <b>INVENTORY</b>: Tells you what objects you currently have.</li>
					<li>- <b>CLEAR</b>: Clears the console.</li>
					<li>- <b>INTRO</b>: Prints the welcome message.</li>
					<li>- <b>CREDITS</b>: Prints the credits.</li>
					<li>- <b>HELP</b>: Prints this message.</li>
				</ul>
			</div>
			<div id="credits" style="display:none;">
				<h1>Credits</h1>
				<ul style="list-style: none">
					<li>- <b style="color:pink;">LOUVE &lt;RACKOVER&gt; HURLANTE</b>: Game design & development</li>
					<li>- <b style="color:lightgreen;">ARCHI &lt;ROGGAH&gt; KAZOO</b>: Moderation</li>
					<li>- <b style="color:#ccccff;">MANUELA &lt;LUNEMAA&gt;</b>: Cuddles & food</li>
				</ul>
				<p style="color:white;">Thanks to everyone who contributed to ADVNTURE!<br>
				I don't have your names, but I appreciate each of your contributions.</p>
				<p>louve@louve.systems - 2019</p>
			</div>
			<div id="intro" style="display:none;">
				<div id="introBigTitle"><h1><pre>       d8888 8888888b.  888     888 888b    888 88888888888 888     888 8888888b.  8888888888 
      d88888 888  "Y88b 888     888 8888b   888     888     888     888 888   Y88b 888        
     d88P888 888    888 888     888 88888b  888     888     888     888 888    888 888        
    d88P 888 888    888 Y88b   d88P 888Y88b 888     888     888     888 888   d88P 8888888    
   d88P  888 888    888  Y88b d88P  888 Y88b888     888     888     888 8888888P"  888        
  d88P   888 888    888   Y88o88P   888  Y88888     888     888     888 888 T88b   888        
 d8888888888 888  .d88P    Y888P    888   Y8888     888     Y88b. .d88P 888  T88b  888        
d88P     888 8888888P"      Y8P     888    Y888     888      "Y88888P"  888   T88b 8888888888</pre></h1></div>
				<h2 style="color:white;"><b><span  id="introWelcomeBar1">------------ </span>Welcome to ADVNTURE!<span  id="introWelcomeBar2"> ------------</span></b></h2>
				<p>ADVNTURE is a game of free exploration and creation.<br>It has no restriction and is only made of what you, and other players, decide to pour into it.</p>
				<p>Enjoy your stay on ADVNTURE - and by all means, expand it in all directions.<br>Do not worry about your language skills, for the vast majority of people aren't native speakers!</p>
				<p><b> The world is yours. Make it your haven!</b></p>
				<p class="emphasis"><b>Type HELP</b> to get a list of commands.<br>Type <b>BRIEF</b> to know where you are.</p>
			</div>
		</div>
	</body>
</html>
