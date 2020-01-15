<?php

function get_page($db, $id, $player){
	$statement = $db->prepare("
		SELECT
		  page.content,
		  ps.target_id,
		  ps.origin_id,
		  ps.command,
		  p.name,
		  pp.prop_id,
		  pp.count,
		  page.is_dead_end,
		  page.is_hidden
		FROM
		  page
		  LEFT JOIN page_succession ps ON ps.origin_id = page.id OR ps.target_id = page.id
		  LEFT JOIN prop_placement pp ON pp.page_id = page.id
		  LEFT JOIN prop p ON p.id = pp.prop_id
		WHERE
		  page.id = ?

	");
	
	$statement->execute([ $id ]);
	$result = $statement->fetchAll();
	
	if ($result === false || $result[0]["content"] == null){
		var_dump($id);
		echo false;
		// This should never happen
	}
		
	$page = array(
		"content" => $result[0]["content"],
		"is_hidden" => $result[0]["is_hidden"],
		"is_dead_end" => $result[0]["is_dead_end"],
		"props"=>array(),
		"outputs"=>array(),
		"id"=>$id
	);
	
	// Props
	$skippedProps = [];
	$processedProps = [];
	
	foreach($result as $prop){
		if ($prop["prop_id"] === null) continue;
		if (in_array($prop["prop_id"], $processedProps)) continue;
		
		$processedProps []= $prop["prop_id"];
		$skip = false;
		foreach ($page["props"] as $pageProp){
			if ($pageProp["id"] == $prop["prop_id"]){
				$skip = true;
			}
		}
		if ($skip) continue;

		// Do not display props the player has and taken from here
		foreach($player["props"] as $playerProp){
			if (
				$playerProp["id"] === $prop["prop_id"] &&
				$playerProp["origin"] === $id &&
				!in_array($playerProp["assignment_id"], $skippedProps)
				){
				$skippedProps [] = $playerProp["assignment_id"];
				$prop["count"]--;
			}
		}
		
		if ($prop["count"] < 1) continue;
		
		// Else add to page
		
		$page["props"] []= array(
			"name"=>$prop["name"],
			"id"=>$prop["prop_id"],
			"count"=>$prop["count"]
		);
	}

	// Outputs
	foreach($result as $output){
		if ($output["command"] === null) continue;
		
		$cmd = $output["origin_id"] === $id ? $output["command"] : reverse_direction_command($output["command"]);
		if ($cmd === false) continue;
		
		if (
			!isset($page["outputs"][$cmd]) || 
			!$page["outputs"][$cmd]["preferred"]
		){
			$page["outputs"][$cmd] = array(
				"preferred" => $output["origin_id"] === $id,
				"destination"=> $output["origin_id"] === $id ? $output["target_id"] : $output["origin_id"]
			);
		}
	}
	
	if ($page["is_hidden"]){
		$page["is_dead_end"] = true;
		$page["content"] = "You cannot go further in that direction: a thick mist hinders your progression. <br>Despite your best efforts, you have no choice but to turn around and go back to where you came from - that is, until the fog has dissipated.";
		$page["props"] = array();
	}
	
	return $page;
}

function receive_submission($db, $p, $player){
	$submission = json_decode($p["submission"], true);
	
	// Checking if the page already exists (shortcut)
	if (strlen($submission["dryText"]) === 0 && $submission["isDeadEnd"] === false){
		$statement = $db->prepare("SELECT id,content FROM page WHERE content LIKE ?");
		$statement->execute([$submission["dryTitle"]."\n"."%"]);
		$result = $statement->fetch();

		if ($result === false){
			// ???
			header('HTTP/1.0 400 Bad Request');
			echo json_encode(["type"=>"error","content"=>"ERR 400: Bad request"]);
			exit;
		}
		else{
			$place = $result["id"];
			$db->prepare("INSERT INTO page_succession (origin_id, target_id, command) VALUES (?,?,?)")
			->execute([$submission["origin"], $place, trim($submission["direction"])]);
			
			$originName = explode("\n", get_page($db, $submission["origin"], $player)["content"])[0];
			
			echo json_encode([
				"type"=>"status",
				"content"=> "After discovering a shortcut leading to <b>".(explode("\n", $result["content"])[0])."</b> from <b>".$originName."</b>, you turned around and went back to where you came from."
			]);
			exit;
		}
	}
	
	// Size limit
	if (strlen($submission["dryText"]) + strlen($submission["dryTitle"]) > 256){
		header('HTTP/1.0 400 Bad Request');
		echo json_encode(["type"=>"error","content"=>"This location was forgotten ages ago, as it was too long and complex for anyone to remember.<br>You decide to turn around and go back to where you came from."]);
		exit;
	}
	if (strlen($submission["dryTitle"]) < 2){
		header('HTTP/1.0 400 Bad Request');
		echo json_encode(["type"=>"error","content"=>"This location was forgotten ages ago.<br>The name was too short, and never really quite sticked to the mind of the travelers passing by.<br>You decide to turn around and go back to where you came from."]);
		exit;
	}
	
	$hide = false;
	$hideReason = "";
	$currentGravity = 0;
	
	$props = [];
	$submittedProps = [];
	foreach($submission["props"] as $prop=>$count){
		$props []= trim($prop);
		$submittedProps[$prop] = $count;
	}
	
	// Check for red flags
	$words = array_merge(
		explode(" ", $submission["dryText"]), 
		explode(" ", str_replace("\n", "", $submission["dryTitle"])), 
		$props
	);
	
	$escapedWords = [];
	foreach($words as $word){
		$escapedWords []= $db->quote($word);
	}
	
	$statement = $db->prepare("SELECT word,gravity FROM word_blacklist WHERE word IN (".
		implode(" ,", $escapedWords)
	.")");
	
	$statement->execute();
	
	$redFlags = $statement->fetchAll();

	foreach($redFlags as $redFlag){
		$currentGravity = max($currentGravity, $redFlag["gravity"]);
		$hideReason = $redFlag["word"]." ";
		$hide = true;
	}
	
	// Checking which props alreay exist
	$existingProps = [];
	foreach($props as $propName){
		$statement = $db->prepare("SELECT id FROM prop WHERE name=?");
		$statement->execute([$propName]);
		$id = ($statement->fetch());
		if ($id === false){
			continue;
		}
		else{
			$existingProps [$propName] = $id["id"];
		}
	}
	
	/// Process submission
	
	// Page
	$statement = $db->prepare("INSERT INTO page (author_id, content, is_hidden, hidden_because, is_dead_end) VALUES (?, ?, ?, ?, ?)");
	$statement->execute([$player["id"], trim($submission["dryTitle"])."\n".trim($submission["dryText"]), $hide, $hideReason, $submission["isDeadEnd"]]);
	$pageId = $db->lastInsertId();
	
	// Props
	foreach($props as $propName){
		if (isset($existingProps[$propName])) continue;
		$db->prepare("INSERT INTO prop (name) VALUES (?)")->execute([$propName]);
		$existingProps[$propName] = $db->lastInsertId ();
	}
	
	// Props placement
	foreach($existingProps as $propName=>$propId){
		$db->prepare("INSERT INTO prop_placement (prop_id, page_id, count) VALUES (?,?,?)")
		->execute([$propId, $pageId, $submittedProps[$propName]]);
	}
	
	// Passage
	$db->prepare("INSERT INTO page_succession (origin_id, target_id, command) VALUES (?,?,?)")
	->execute([$submission["origin"], $pageId, trim($submission["direction"])]);
	
	if ($hide && $currentGravity > 0){
		ban_client($db, $player["client_id"], $hideReason); // Silent ban
	}
	
	// Done!
	$content = "";
	if ($currentGravity === 0 && $hide){
		$content = "The location you submitted is hidden in the fog, waiting for higher instances to recognize it. <br>You decide to turn around - for now." ;
	}
	else if ($submission["isDeadEnd"]){
		$content = "This direction is now forever sealed.<br>You've been transported back to your previous location.";
	}
	else {
		$content = "<b>".$submission["dryTitle"]."</b> is now forever a destination in the world of ADVNTURE.<br>Other adventurers may stumble upon it as a part of their journey.";
	
		// Teleport Player
		$db->prepare("UPDATE player SET page_id=? WHERE id=?")->execute([$pageId, $player["id"]]);	
		echo json_encode([
			"type"=>"teleport",
			"content"=> array(
				"message"=>$content,
				"page_id"=>$pageId
			)
		]);
		exit;
	}
	
	echo json_encode([
		"type"=>"status",
		"content"=> $content
	]);
	exit;
}

function reverse_direction_command($cmd){
	switch($cmd){
		default: return false; // should return false, but
		case "NORTH": return "SOUTH";
		case "SOUTH": return "NORTH";
		case "EAST": return "WEST";
		case "WEST": return "EAST";
		case "UP": return "DOWN";
		case "DOWN": return "UP";
	}
}

function get_page_count($db){
	$st = $db->prepare("SELECT COUNT(*) AS count FROM page WHERE is_hidden=0 AND is_dead_end=0");
	$st->execute();
	return ($st->fetch())["count"];
}

function get_all_page_names($db){
	$st = $db->prepare("SELECT content FROM page WHERE is_dead_end=0 AND is_hidden=0");
	$titles = [];
	$st->execute();
	$pages = $st->fetchAll();
	foreach($pages as $page){
		$titles[]= explode("\n", $page["content"])[0];
	}
	return $titles;
}


?>
