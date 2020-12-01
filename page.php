<?php

include_once "player.php";
include_once "biome.php";

define("OBJECT_TELEPORT_BASE_RANGE", 30);
define("ALLOW_BANNED_SUBMISSIONS", true);



function get_starting_page_id_for_player($db, $player){
	$statement = $db->prepare(
		"SELECT starting_page FROM dimension 
		WHERE id=?");
	$statement->execute([$player["dimension"]]);
	$id = $statement->fetch()["starting_page"];

	return $id;
}

function get_starting_page_id_for_dimension($db, $dimension_id){
	$statement = $db->prepare(
		"SELECT starting_page FROM dimension 
		WHERE id=?");
	$statement->execute([$dimension_id]);
	$id = $statement->fetch()["starting_page"];

	return $id;
}

function get_starting_page_id($db){
	$statement = $db->prepare(
		"SELECT starting_page FROM dimension 
		WHERE initial=1");
	$statement->execute();
	$id = $statement->fetch()["starting_page"];

	return $id;
}

function get_cardinal_directions(){
    return ["NORTH", "SOUTH", "EAST", "WEST"];
}

function get_surrounding_pages_ids($db, $given_str_position){
    
    $directions = get_cardinal_directions();
    $pages_ids = [];
    
    $numerical_position = ["x"=>explode(" ", $given_str_position)[0], "y"=>explode(" ", $given_str_position)[1]];
    
    foreach($directions as $direction){
        
        $shift = get_cardinal_direction_position_shift($direction);
        $str_position = ($numerical_position["x"]+$shift["x"])." ".($numerical_position["y"]+$shift["y"]);

        if ($page_id = get_first_page_with_position($db, $str_position)){
            $pages_ids[$direction] = $page_id;
        }        
    }
    
    return $pages_ids;
}

function get_first_page_with_position($db, $str_position){
    $statement = $db->prepare(
		"SELECT id FROM page 
		WHERE position=?
        ORDER BY id ASC");
	$statement->execute([$str_position]);
	$data = $statement->fetch();
    
    return $data === false ? false : $data["id"];
}

function get_page_hourglass_info($db, $page_id, $player){
	$statement = $db->prepare(
		"
        SELECT 
            page.content,
            page.is_dead_end,
            page.is_hidden,
            page.biome_id,
            prop.id AS prop_id,
            prop_placement.count AS prop_count,
            biome.name AS biome_name,
            player_vision.id AS vision
        FROM 
            page
            LEFT JOIN player_vision player_vision ON player_vision.page_id = page.id AND player_vision.player_id=?
            LEFT JOIN prop_placement prop_placement ON prop_placement.page_id = page.id
            LEFT JOIN prop prop ON prop.id = prop_placement.prop_id
            LEFT JOIN biome biome ON biome.id = page.biome_id
        WHERE 
            page.id=? AND page.is_hidden=0
		");
	$statement->execute([$player["id"], $page_id]);
	
	$data = $statement->fetch();
	
	if ($data && $data["vision"]){
		$content = $data["content"];
		$page_name = strtok($content, "\n");
        $prop_id = $data["prop_id"];
        $props = 0;
        $biome = $data["biome_name"];
        $dead_end = $data["is_dead_end"] == "1";
        $first_loop = true;
        $skippedProps = [];
        
        if ($prop_id !== false){
            while($first_loop || ($data = $statement->fetch())){
                
                $first_loop = false;
                $count = $data["prop_count"];
                
                // Do not display props the player has and taken from here
                foreach($player["props"] as $playerProp){
                    if (
                        $playerProp["id"] === $data["prop_id"] &&
                        $playerProp["origin"] === $page_id &&
                        !in_array($playerProp["assignment_id"], $skippedProps)
                        ){
                        $skippedProps [] = $playerProp["assignment_id"];
                        $count--;
                    }
                }
                
                if ($count > 0){
                    $props++;
                }
            }
        }
        
        return [
            "items"=> $props,
            "biome"=> $biome,
            "name"=>$page_name,
            "is_explored"=>true,
            "is_dead_end"=>$dead_end
        ];
	}
	else{
		return [
            "is_explored"=>false,
            "name"=>""                
        ];
	}
}

function get_page_position($db, $page_id){
    $statement = $db->prepare(
		"SELECT position FROM page 
		WHERE id=?");
	$statement->execute([$page_id]);
	$position = $statement->fetch();
    
    if ($position === false){
        return false;
    }
    
    $position = explode(" ", $position["position"]);
    
    $x = intval($position[0]);
    $y = intval($position[1]);
    
    return ["x"=>$x, "y"=>$y];    
}

function is_position_occupied($db, $position){
    
    $statement = $db->prepare(
		"SELECT id FROM page 
		WHERE position=?");
	$statement->execute([$position]);
	$data = $statement->fetch();
    
    return $data != false;
}

function get_page_biome_id($db, $id){
    
	$statement = $db->prepare("
		SELECT 
            biome_id
        FROM 
            page
        WHERE 
            page.id = ?
    ");
    
    $statement->execute([$id]);
    
    $result = $statement->fetch();
    
	if ($result === false || !isset($result["biome_id"])){
		var_dump($id);
		echo false;
		// This should never happen
	}
    
    return $result["biome_id"];
}

function get_page_biome_name($db, $id){
    
	$statement = $db->prepare("
		SELECT 
            biome.name AS biome
        FROM 
            page
            LEFT JOIN biome biome ON biome.id = page.biome_id
        WHERE 
            page.id = ?
    ");
    
    $statement->execute([$id]);
    
    $result = $statement->fetch();
    
	if ($result === false || !isset($result["biome"])){
		var_dump($id);
		echo false;
		// This should never happen
	}
    
    return $result["biome"];
}

function get_page($db, $id, $player){
	$statement = $db->prepare("
		SELECT
		  page.content,
		  page.position,
		  page_succession.target_id,
		  page_succession.origin_id,
		  page_succession.command,
		  prop.name,
		  prop_placement.prop_id,
		  prop_placement.count,
		  page.is_dead_end,
		  page.is_hidden,
		  hp_event.hp_change,
		  hp_event.id
		FROM
		  page
		  LEFT JOIN page_succession page_succession ON page_succession.origin_id = page.id OR page_succession.target_id = page.id
		  LEFT JOIN prop_placement prop_placement ON prop_placement.page_id = page.id
		  LEFT JOIN prop prop ON prop.id = prop_placement.prop_id
		  LEFT JOIN hp_event hp_event ON hp_event.page_id = page.id
		WHERE
		  page.id = ?
        ORDER BY page_succession.id ASC
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
		"position" => $result[0]["position"],
		"props"=>array(),
		"outputs"=>array(),
		"hourglass"=>array(),
        "completion"=>0,
		"hp_events"=>array(),
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
	
	// HP Event
	$hpEventsDone = [];
	foreach($result as $he){
		if ($he["hp_change"] === null) continue;
		if (in_array($he["id"], $hpEventsDone)) continue;
		$page["hp_events"][] = $he["hp_change"]; 
		$hpEventsDone[] = $he["id"];
	}

	// Outputs
    $is_grid_based = is_player_dimension_grid_based($db, $player);
	foreach($result as $output){
		if ($output["command"] === null) continue;
		
		$cmd = $output["origin_id"] === $id ? $output["command"] : reverse_direction_command($output["command"]);
		if ($cmd === false) continue;
        
		if (
			!isset($page["outputs"][$cmd]) || 
			!$page["outputs"][$cmd]["preferred"]
		){
			$page["outputs"][$cmd] = array(
				"preferred" => $output["origin_id"] === $id || $is_grid_based, // In ASC and grid based, the first connection found is as good as any other
				"destination"=> $output["origin_id"] === $id ? $output["target_id"] : $output["origin_id"]
			);
		}
	}
    
    // Hourglass
    $page["hourglass"]["here"] = get_page_hourglass_info($db, $page["id"], $player);
    $cardinal_directions = get_cardinal_directions();
    foreach($cardinal_directions as $direction){
        if (isset($page["outputs"][$direction])){
            $page["hourglass"][$direction] = get_page_hourglass_info($db, $page["outputs"][$direction]["destination"], $player); 
        }
        else{
            $page["hourglass"][$direction] = get_page_hourglass_info($db, 0, $player); // Getting inexistant page
        }
    }
    $page["completion"] = get_completion_amount($db, $player);
	
	if ($page["is_hidden"]){
		$page["is_dead_end"] = true;
		$page["content"] = "You cannot go further in that direction: a thick mist hinders your progression. <br>Despite your best efforts, you have no choice but to turn around and go back to where you came from - that is, until the fog has dissipated.";
		$page["props"] = array();
		$page["hp_events"] = array();
	}
	
	return $page;
}

function get_completion_amount($db, $player){
    $page_count = get_page_count_in_dimension($db, $player["dimension"]);
    
    
	$st = $db->prepare("
        SELECT COUNT(*) AS count 
        FROM 
            player_vision 
            LEFT JOIN page page ON page.dimension_id=? AND page.id=player_vision.page_id
        WHERE 
            player_id=? AND page.is_dead_end=0 AND page.is_hidden=0
    ");
	$st->execute([$player["dimension"], $player["id"]]);
	
    $seen_count = ($st->fetch())["count"];
    
    return $seen_count/$page_count;
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
			// Might just be a place with objects/hpevents but without a description...
			// Sucks but technically not illegal
						
			header('HTTP/1.0 400 Bad Request');
			echo json_encode(["type"=>"error","content"=>"Not enough was known about this place, and so it never really set in as an interesting location.<br>You decide to turn around and go back to where you came from."]);
			exit;
		}
		else{
			
            if (is_player_dimension_grid_based($db, $player) && reverse_direction_command(trim($submission["direction"])) != false){
                // No shortcut in grid mode with the cardinal directions!!
                return_200("status", "The region name you gave was already taken - you cannot create shortcuts in a grid-based regions, at least - not without objects");
            }
            
			$place = $result["id"];
			$db->prepare("INSERT INTO page_succession (origin_id, target_id, command) VALUES (?,?,?)")
			->execute([$submission["origin"], $place, trim($submission["direction"])]);
			
			$originName = explode("\n", get_page($db, $submission["origin"], $player)["content"])[0];
			
			echo json_encode([
				"type"=>"status",
				"content"=> "After discovering a shortcut leading to <b>".(explode("\n", $result["content"])[0])."</b> from <b>".$originName."</b>, you turned around and went back to where you came from.",
                "hourglass"=>get_page($db, $player["location"], $player)["hourglass"]
			]);
			exit;
		}
	}
	
	// Size limit
	if (strlen($submission["dryText"]) + strlen($submission["dryTitle"]) > 256){
		header('HTTP/1.0 400 Bad Request');
		echo json_encode([
            "type"=>"error",
            "content"=>"This location was forgotten ages ago, as it was too long and complex for anyone to remember.<br>You decide to turn around and go back to where you came from.",
            "hourglass"=>get_page($db, $player["location"], $player)["hourglass"]
            ]);
		exit;
	}
	if (strlen($submission["dryTitle"]) < 2){
		header('HTTP/1.0 400 Bad Request');
		echo json_encode([
            "type"=>"error",
            "content"=>"This location was forgotten ages ago.<br>The name was too short, and never really quite sticked to the mind of the travelers passing by.<br>You decide to turn around and go back to where you came from.",
            "hourglass"=>get_page($db, $player["location"], $player)["hourglass"]
        ]);
		exit;
	}
	
	$hide = false;
	$hideReason = "";
	$currentGravity = 0;
	
    $is_grid_dimension = is_player_dimension_grid_based($db, $player);
    
	$props = [];
	$submittedProps = [];
	foreach($submission["props"] as $prop=>$count){
		$props []= trim($prop);
		$submittedProps[$prop] = $count;
	}
	
	// Check for red flags
	$words = array_merge(
		explode(" ", str_replace('"', "", $submission["dryText"])), 
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
	if (ALLOW_BANNED_SUBMISSIONS || $currentGravity === 0){
        
        // Position calculation
        $position = null;
        $shift = get_cardinal_direction_position_shift(trim($submission["direction"]));
        
        if ($is_grid_dimension){
            $origin_position = get_page_position($db, $submission["origin"]);
            
            if ($shift === false){
                // Dead ends from object teleportations are always "stacked rooms", otherwise you'd block one random cell of the map each time
                if ($submission["objectTeleport"] && !$submission["isDeadEnd"]){
                    // Using an object to teleport
                    $offset = get_page_count_in_dimension($db, $player["dimension"]) + OBJECT_TELEPORT_BASE_RANGE;
                    $ok = false;
                    $shiftIncrement = 1;
                    while(!$ok){
                        $randX = (rand(0, 1)*2-1);
                        $randY = (rand(0, 1)*2-1);
                        
                        $position = [$origin_position["x"] + $randX * $offset * $shiftIncrement, $origin_position["y"] + $randY * $offset * $shiftIncrement];
                        $position = implode(" ", $position);
                        $ok = !is_position_occupied($db, $position);
                        
                            
                        if ($ok){
                            break;
                        }
                        
                        $shiftIncrement++;    
                    }
                }
                else{
                    // Not moving, using an object
                    $position = $origin_position["x"]." ".$origin_position["y"];
                }
            }
            else{
                $ok = false;
                $shiftIncrement = 1;
                while(!$ok){
                    $position = [$origin_position["x"] + $shift["x"] * $shiftIncrement, $origin_position["y"] + $shift["y"]* $shiftIncrement];
                    $position = implode(" ", $position);
                    $ok = !is_position_occupied($db, $position);
                    
                    if ($ok){
                        break;
                    }
                    
                    $shiftIncrement++;
                }
            }
        }
        
        // Finding out biome
        $default_biome = get_default_biome($db);
        $biome = $default_biome;
        
        if (isset($submission["biome"])){
            $biome = get_biome_by_name($db, $submission["biome"]);
        }
        else{
            $try_title = determine_biome($db, $submission["dryTitle"]);
            if ($try_title != $default_biome){
                $biome = $try_title;
            }
            else{
                // Last chance
                $biome = determine_biome($db, $submission["dryText"]);
                
                // If nothing works, and if it's a dead end, we can take the current player's location biome instead
                if ($biome == $default_biome && $submission["isDeadEnd"]){
                    $biome = get_page_biome_id($db, $submission["origin"]);
                }
            }
        }
        
        
		// Page
		$statement = $db->prepare("INSERT INTO page (author_id, content, is_hidden, hidden_because, is_dead_end, dimension_id, position, biome_id) VALUES (?, ?, ".intval($hide).", ?, ".intval($submission["isDeadEnd"]).", ".intval($player["dimension"]).", ?, ?)");
		$statement->execute([$player["id"], trim($submission["dryTitle"])."\n".trim($submission["dryText"]), $hideReason, $position, $biome]);
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
		
		// HP events
		foreach($submission["hpEvents"] as $value){
			$db->prepare("INSERT INTO hp_event (page_id, hp_change) VALUES (?, ?)")->execute([$pageId, $value]);
		}
		
		// Passage
        
        // additional, automatic passages created by the grid
        if ($is_grid_dimension){
            
            if ($shift == false && !$submission["objectTeleport"]){
                $db->prepare("INSERT INTO page_succession (origin_id, target_id, command) VALUES (?,?,?)")
                ->execute([$submission["origin"], $pageId, trim($submission["direction"])]);
            }
                
            $surroundings_by_direction = get_surrounding_pages_ids($db, $position);
            foreach($surroundings_by_direction as $direction=>$surrounding_page_id){
                $db->prepare("INSERT IGNORE INTO page_succession (origin_id, target_id, command) VALUES (?,?,?)")
                    ->execute([$pageId, $surrounding_page_id, $direction]);
            }
        }
        else{
            $db->prepare("INSERT INTO page_succession (origin_id, target_id, command) VALUES (?,?,?)")
            ->execute([$submission["origin"], $pageId, trim($submission["direction"])]);
        }
    }
    
    if ($currentGravity > 0){
        // Silent ban
        ban_client($db, $player["client_id"], $hideReason); 
    }
	
	// Done!
	$content = "";
	if ($currentGravity === 0 && $hide){
		$content = "The location you submitted is hidden in the fog, waiting for higher instances to recognize it. <br>You decide to turn around - for now." ;
	}
	else{
		player_give_vision_on_page($db, $player["id"], $pageId);
		
		if ($submission["isDeadEnd"]){
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
					"page_id"=>$pageId,
                    "hourglass"=>get_page($db, $pageId, $player)["hourglass"]
				)
			]);
			exit;
		}
	}
	
    // Updated hourglass with dead end
	echo json_encode([
		"type"=>"status",
		"content"=> $content,
        "hourglass"=>get_page($db, $player["location"], $player)["hourglass"]
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

function get_cardinal_direction_position_shift($direction){
    
	switch($direction){
		default: return false; // should return false, but
		case "NORTH": return ["x"=>0, "y"=>1];
		case "SOUTH": return ["x"=>0, "y"=>-1];
		case "EAST": return ["x"=>1, "y"=>0];
		case "WEST": return ["x"=>-1, "y"=>0];
	}
}

function get_page_count($db){
	$st = $db->prepare("SELECT COUNT(*) AS count FROM page WHERE is_hidden=0 AND is_dead_end=0");
	$st->execute();
	return ($st->fetch())["count"];
}

function get_page_count_in_dimension($db, $dim_id=1){
	$st = $db->prepare("SELECT COUNT(*) AS count FROM page WHERE is_hidden=0 AND is_dead_end=0 AND dimension_id=?");
	$st->execute([$dim_id]);
	return ($st->fetch())["count"];
}

function get_all_page_names($db, $dimension_id){
	$st = $db->prepare("SELECT content FROM page WHERE is_dead_end=0 AND is_hidden=0 AND dimension_id=".$dimension_id);
	$titles = [];
	$st->execute();
	$pages = $st->fetchAll();
	foreach($pages as $page){
		$titles[]= explode("\n", $page["content"])[0];
	}
	return $titles;
}

function is_player_dimension_grid_based($db, $player){
	$st = $db->prepare("SELECT type FROM dimension WHERE id=?");
	$st->execute([$player["dimension"]]);
	return ($st->fetch())["type"] === "GRID";
}

?>
