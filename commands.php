<?php 
	include "page.php";
	include "player.php";
	include "connection.php";

	define("COMMANDS", array(
		"NORTH" => 'command_direction_north',
		"SOUTH" => 'command_direction_south',
		"EAST" => 'command_direction_east',
		"WEST" => 'command_direction_west',
		"TAKE" => 'command_take',
		"LOSE" => 'command_drop',
		"INVENTORY" => 'command_props',
		"BRIEF" => 'command_brief',
		"CLEAR" => 'command_clear',
		"USE" => 'command_use',
		"RESET" => 'command_reset',
		"HELP" => 'command_help',
		"INTRO" => 'command_intro',
		"WHOAMI" => 'command_whoami',
		"PAGEID" => 'command_page_id',
		"MUSIC" => 'command_music',
		"CREDITS" => 'command_credits',
		"CLIENT" => 'command_client_id',
		"FAKEBAN" => 'command_fake_ban',
		
		"N" => 'command_direction_north',
		"S" => 'command_direction_south',
		"E" => 'command_direction_east',
		"W" => 'command_direction_west',
		"LOOK" => 'command_brief',
		"?" => 'command_help',
		"CRASH" => 'command_crash',
    
		"IDENTIFY"=> 'command_identify',
		"SHOWLAST"=> 'command_last_uploads',
		"LOGOUT" => 'command_logout',
		"GOTO" => 'command_goto',
		"PAGEINFO" => 'command_get_author_of',
		"BANCLIENT" => 'command_ban_client',
		"GETITEM" => 'command_give_me'
	));
		
	function identify_command($db, $p, $player){

		$txt = trim($p["command"]);
		$elements = explode (" ", $txt);
		$command = $elements[0];
		array_shift($elements);
			
		if (isset(COMMANDS[$command])){
			COMMANDS[$command]($db, $elements, $player);
		}
		else{
			return_404($command);
		}
	}
	
	function command_direction($db, $elements, $player, $direction){
		$page = get_page($db, $player["location"], $player);
		if (isset($page["outputs"][$direction])){
			$newId = $page["outputs"][$direction]["destination"];
			$newPage = get_page($db, $newId, $player);
			
			if ($newPage["is_dead_end"]){
				return_200("status", $newPage["content"]);
			}
			
			$db->prepare("UPDATE player SET page_id=? WHERE id=?")->execute([$newId, $player["id"]]);
			return_200("page", $newPage);
		}
		else{
			$existingPages = get_all_page_names($db);
			return_200("editor", array("direction"=>$direction, "existing_pages"=>$existingPages));
		}
	}
	
	function command_direction_north($db, $elements, $player){
		command_direction($db, $elements, $player, "NORTH");
	}
	
	function command_direction_south($db, $elements, $player){
		command_direction($db, $elements, $player, "SOUTH");
	}
	
	function command_direction_east($db, $elements, $player){
		command_direction($db, $elements, $player, "EAST");
	}
	
	function command_direction_west($db, $elements, $player){
		command_direction($db, $elements, $player, "WEST");
	}
	
	function command_fake_ban($db, $elements, $player){
		return_503_banned();
	}
	
	function command_take($db, $elements, $player){
		$page = get_page($db, $player["location"], $player);
		$name = implode(" ", $elements);
		if (strlen($name) ===0 || count($elements) ===0){
			return_200("status", "Take what?");
		}
		foreach($page["props"] as $prop){
			if (strtolower($prop["name"]) === strtolower($name) || 
				strpos(strtolower($prop["name"]), strtolower($name)) > -1){
					
				if (is_full_inventory($player)){
					return_200("status", "Could not take ".strtolower($prop["name"]).": your inventory is full.<br>You must <b>LOSE</b> another object before grabbing ".strtolower($prop["name"]).".");
				}
					
				$db->prepare("INSERT INTO player_prop (player_id, prop_id, original_page_id) VALUES (?, ?, ?)")->execute([$player["id"], $prop["id"], $page["id"]]);
				$player = get_player($db);
				
				return_200("status", "Took ".strtolower($prop["name"]).".");
			}
		}
		return_200("status", "There is no '".$name."' here.");
	}
	
	function command_drop($db, $elements, $player){
		$name = implode(" ", $elements);
		if (strlen($name) ===0 || count($elements) ===0){
			return_200("status", "Lose what?");
		}
		foreach($player["props"] as $prop){
			if (strpos(strtolower($prop["name"]), strtolower($name)) > -1 
				|| strtolower($prop["name"]) === strtolower($name)){
				$db->prepare("DELETE FROM player_prop WHERE id=?")->execute([$prop["assignment_id"]]);
				$player = get_player($db);
				echo json_encode(["type"=>"status","content"=>"Lost ".strtolower($prop["name"])."."]);
				exit;
			}
		}
		
		return_200("status", "You do not have a '".$name."'.");
	}
	
	function command_props($db, $elements, $player){
		$props = [];
		foreach($player["props"] as $prop){
			$props []= $prop["name"];
		}
		return_200("props", $props);
	}
	
	function command_clear($db, $elements, $player){
		return_200("clear", null);
	}
	
	function command_help($db, $elements, $player){
		return_200("help", null);
	}
	
	function command_intro($db, $elements, $player){
		return_200("intro", get_page($db, $player["location"], $player));
	}
	
	function command_credits($db, $elements, $player){
		return_200("credits", null);
	}
	
	function command_whoami($db, $elements, $player){
		return_200("status", "You are user [<span style='color:yellow;'>".$player["id"]."</span>]");
	}
	
	function command_page_id($db, $elements, $player){
		return_200("status", "The ID of your current location is: <span style='color:yellow;'>".$player["location"]."</span>");
	}
	
	function command_client_id($db, $elements, $player){
		return_200("status", "Your client ID is: <span style='color:yellow;font-weight:bold'>".$player["client_id"]."</span> (ADDR: <span style='font-weight:bold;color:lightgreen;'>".$player["client_address"]."</span>)");
	}
	
	function command_brief($db, $elements, $player){
		$page = get_page($db, $player["location"], $player);
		return_200("page", $page);
	}
	
	function command_music($db, $elements, $player){
		$name = str_replace("..", "", str_replace("/", "_", implode(" ", $elements)));
		$status = "The file ".$name." could not be found.";
		if (strlen($name) > 1 && file_exists("res/snd/".$name)){
			$status = '
				Playing '.$name.'<br>
				<audio style="height:16px;margin:4px;padding:0px;" controls autoplay loop id="music">
					<source src="res/snd/'.$name.'" type="audio/ogg">
				</audio>
			';
		}
		return_200("status", $status);
	}
	
	function command_use($db, $elements, $player){
		$name = implode(" ", $elements);
		if (strlen($name) ===0 || count($elements) ===0){
			return_200("status", "Use what?");
		}
		$ok = false;
		foreach($player["props"] as $prop){
			if (strpos(strtolower($prop["name"]), strtolower($name)) > -1 
				|| strtolower($prop["name"]) === strtolower($name)){
				$ok = true;
				break;
			}
		}
		if (!$ok){
			return_200("status", "You do not have a '".$name."'.");
		}
		else{
			$direction = "USE ".strtoupper($prop["name"]);
			command_direction($db, $elements, $player, $direction);
		}
	}
	
	function command_reset($db, $elements, $player){
		$db->prepare("UPDATE player SET page_id=1 WHERE id=?")->execute([$player["id"]]);
		$page = get_page($db, 1, $player);
		return_200("page", $page);
	}
	
	function command_crash($db, $elements, $player){
		var_dump("crash");
		exit;
	}
	
  function command_identify($db, $elements, $player){
    $pass = implode(" ", $elements);
    if (strtoupper($pass) === strtoupper(getEnv("ADVNTURE_ADMIN_PASS"))){
		$_SESSION["isAdmin"] = true;
		return_200("status", "OK.");
    }
    else{
      close_connection_wrong_command("IDENTIFY");
    }
  }
  
  function command_logout($db, $elements, $player){
    if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) close_connection_wrong_command("LOGOUT");
	$_SESSION ["isAdmin"] = false;
	return_200("status", "Logged out.");
  }
	
	function command_get_author_of($db, $elements, $player){	
		if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) close_connection_wrong_command("PAGEINFO");
		if (count($elements) < 1 || !intval($elements[0])) return_200("status", "Invalid page ID supplied");
		$id = $elements[0];
		
		$statement = $db->prepare("
			SELECT
			  page.content,
              page.id AS page_id,
			  page.is_dead_end,
			  page.is_hidden,
			  prop.name as prop_name,
			  pp.count as prop_placement_count,
			  page_succession.command,
			  cli.id AS client_id,
              cli.is_banned AS is_banned,
			  player.id AS player_id,
              otherpage.id as connected_page_id,
              otherpage.content as connected_page_content,
			  otherpage.is_dead_end as is_dead_connection
			FROM
			  page
			  LEFT JOIN page_succession page_succession ON page_succession.origin_id = page.id OR page_succession.target_id = page.id
			  LEFT JOIN prop_placement pp ON pp.page_id = page.id
			  LEFT JOIN prop prop ON prop.id = pp.prop_id
			  LEFT JOIN player player ON player.id = page.author_id
			  LEFT JOIN client cli ON cli.id = player.client_id
              LEFT JOIN page otherpage ON (page_succession.origin_id = otherpage.id OR page_succession.target_id = otherpage.id) AND otherpage.id != page.id
			WHERE
			  page.id = ?
		");
		$statement->execute([$id]);
		$client_id = "???";
		$is_client_banned = false;
		$is_dead_end = false;
		$is_hidden = false;
		$author_id = "???";
		$props = [];
		$connections = [];
		$page_content = "";
		
		while ($row = $statement->fetch()){
			if ($row === false){
				return_200("status", "Invalid ID or non-existent page");
			}
			$client_id = $row["client_id"];
			$is_client_banned = $row["is_banned"];
			$author_id = $row["player_id"];
			$page_content = $row["content"];
			$is_dead_end = $row["is_dead_end"];
			if ($row["prop_name"] != null){
				$props[]= array("name"=>$row["prop_name"], "count"=>$row["prop_placement_count"]);
			}
			if ($row["connected_page_id"] != null){
				$connections []= array(
					"name"=>explode("\n", $row["connected_page_content"])[0],
					"id"=>$row["connected_page_id"],
					"is_dead_end"=>$row["is_dead_connection"],
					"command"=>$row["command"]
				);
			}
		}
		
		$pageElements = explode("\n", $page_content);
		$title = array_shift($pageElements);
		$content = $is_dead_end ? "(dead end)" : implode(" ", $pageElements);
		
		$strProps = [];
		$strConnections = "";
		foreach($props as $prop){
			$strProps []= "x".$prop["count"]." ".$prop["name"]."";
		}
		foreach($connections as $connection){
			$strConnections .= "<li><span style='color:white;'>".$connection["name"]."</span> [id:<b style='color:yellow'>".$connection["id"]."</b>] "
			.($connection["is_dead_end"] ? "(dead end) " : "")."via <span class='emphasis'>".$connection["command"]."</span></li>";
		}
		
		return_200("status", '
				<b class="emphasis">'.$title.'</b> [<b style="color:yellow;">'.$id.'</b>]<br><i>'.$content.'</i><br>Made by <span style="color:white;">PLAYER id:<b style="color:yellow;">'.substr($author_id, 0, 6).'...</b></span> 
				'.($is_client_banned ? "<span style='color:red;'>" : "").'('.($is_client_banned ? "BANNED " : "").'CLIENT id:<b style="color:yellow;">'.$client_id.'</b>)'.($is_client_banned ? "</span>" : "").'
				<br><br><b>Props</b>: '.implode(", ", $strProps).'<br>
				<br><b>Connections</b>:
				<ul>
					'.$strConnections.'
				</ul>
		
		
		');
	}
	
  function command_last_uploads($db, $elements, $player){
    if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) close_connection_wrong_command("SHOWLAST");
    
    $state = $db->prepare("SELECT creation,author_id,content,is_hidden,id FROM page ORDER BY id DESC LIMIT 5");
    $state->execute();
    $data = $state->fetchAll();
	$text = [];
	foreach($data as $page){
		$elements = explode("\n", trim($page["content"]));
		$title = "<b style='color:white;".(count($elements) <= 1 ? " font-weight:normal;" : "")."'>".array_shift($elements)."</b>";
		$content = count($elements) > 0 ? implode("<br>", $elements) : "((dead end))";
		$thisText = $page["creation"]." - ".$title." [<b style='color:yellow'>".$page["id"]."</b>]<br>(author playerID:<span style='color:yellow;'>".$page["author_id"].")</span><br>".$content;
		if ($page["is_hidden"]) $thisText = "<span style='color:red;'>".$thisText."</span>";
		$text []= $thisText;
	}
	
	return_200("status", "<p>".implode("<p></p>", $text )."</p>");      
  }
	
  function close_connection_wrong_command($command){
	return_404($command);
  }
  
  function command_goto($db, $elements, $player){
    if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) close_connection_wrong_command("GOTO");
	$name = implode(" ", $elements);
	if (!$name) return_200("status", "No destination");
    $state = $db->prepare("SELECT id FROM page WHERE content LIKE ?");
    $state->execute([$name."\n%"]);
    $data = $state->fetch();
	if ($data === false){
		return_200("status", "No such page as ".$name);
	}
	else{
		$page = get_page($db, $data["id"], $player);
		$db->prepare("UPDATE player SET page_id=? WHERE id=?")->execute([$data["id"], $player["id"]]);
		return_200("page", $page);
	}
  }
  
  function command_ban_client($db, $elements, $player){
    if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) close_connection_wrong_command("BANCLIENT");
	if (count($elements) < 1 || !intval($elements[0])) return_200("status", "Invalid page ID supplied");
	$id = $elements[0];
	
	ban_client($db, $id, "Manual ban from admin");
	return_200("status", "Client ".$id.", if they exist, have been <b>banned</b>");
  }
  
  
	
	function command_give_me($db, $elements, $player){
		if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) close_connection_wrong_command("GETITEM");
		$name = intval($elements[0]);
		if (strlen($name) ===0 || count($elements) ===0){
			return_200("status", "Take what?");
		}
		if (is_full_inventory($player)){
			return_200("status", "Could not take ".strtolower($prop["name"]).": your inventory is full.<br>You must <b>LOSE</b> another object before grabbing ".strtolower($prop["name"]).".");
		}
			
		$db->prepare("INSERT INTO player_prop (player_id, prop_id, original_page_id) VALUES (?, ?, ?)")->execute([$player["id"], $name, 1]);
		$player = get_player($db);
		return_200("status", "Gave you item ".$name);
	}
  
?>