<?php 
    include_once  "page.php";
    include_once  "player.php";
    include_once  "connection.php";

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
        "HELP" => 'command_help',
        "INTRO" => 'command_intro',
        "WHOAMI" => 'command_whoami',
        "PAGEID" => 'command_page_id',
        "MUSIC" => 'command_music',
        "CREDITS" => 'command_credits',
        "CLIENT" => 'command_client_id',
        "FAKEBAN" => 'command_fake_ban',
        "STATUS" => 'command_hp_status',
        "REST" => 'command_rest',
        "FAINT" => 'command_suicide',    
        "WARP" => 'command_warp',
        "REGIONS" => 'command_list_dimensions',
        "REGION" => 'command_get_dimension',
        
        "N" => 'command_direction_north',
        "S" => 'command_direction_south',
        "E" => 'command_direction_east',
        "W" => 'command_direction_west',
        "LOOK" => 'command_brief',
        "?" => 'command_help',
        "CRASH" => 'command_crash',
        "HEALTHCHECK" => 'command_hp_status',
        "HP" => 'command_hp_status',
        "HEALTH" => 'command_hp_status',
        "HEALTHPOINTS" => 'command_hp_status',    
        "SUICIDE" => 'command_suicide',
    
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
            
            // HP Calculation
            $change = 0;
            foreach($newPage["hp_events"] as $hpChange){
                $change += $hpChange;
            }
            $player["hp"] += $change;
            $player["hp"] = min($player["hp"], MAX_HP);
                
            if ($player["hp"] <= 0){
                $db->prepare("UPDATE player SET page_id=? WHERE id=?")->execute([get_starting_page($player)."", $player["id"]]);    // First page (ID:1)
                $db->prepare("UPDATE player SET hp=? WHERE id=?")->execute([BASE_HP, $player["id"]]);    
                $db->prepare("DELETE FROM player_prop WHERE player_id=?")->execute([$player["id"]]);
                $newPage = get_page($db, get_starting_page($player), $player["id"]);
                return_200("death", $newPage);
            }
            else if ($change != 0){
                $db->prepare("UPDATE player SET hp=? WHERE id=?")->execute([$player["hp"], $player["id"]]);
                // $newPage["content"].= "<br>"."(You currently have ".$player["hp"]." health points)";
            }
            
            // Returning results
            if ($newPage["is_dead_end"]){
                return_200("status", $newPage["content"]);
            }
            
            $db->prepare("UPDATE player SET page_id=? WHERE id=?")->execute([$newId, $player["id"]]);
            return_200("page", $newPage);
        }
        else{
            $state = $db->prepare("SELECT readonly FROM dimension WHERE id=?");
            $state->execute([$player["dimension"]]);
            $readonly = $state->fetch()["readonly"];

            if ($readonly){
                return_200("status", "This region is history - and history may not be rewritten.");
            }
            else{
                $existingPages = get_all_page_names($db, $player["dimension"]);
                return_200("editor", array("direction"=>$direction, "existing_pages"=>$existingPages));
            }
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
    
    function command_rest($db, $elements, $player){
        $page = get_page($db, $player["location"], $player);
        $change = 0;
        foreach($page["hp_events"] as $hpChange){
            $change += $hpChange;
        }
        if ($change < 0){
            return_200("status", "You cannot rest in a dangerous place like this!");
        }
        if ($player["hp"] >= BASE_HP){
            return_200("status", "You decide to rest and stay here for a little while before resuming your journey.");
        }
        $regain = min(BASE_HP-$player["hp"], random_int (2, 5));
        $player["hp"] += $regain;
        $db->prepare("UPDATE player SET hp=? WHERE id=?")->execute([$player["hp"], $player["id"]]);    
        return_200("status","You take some time to rest and heal your wounds. When you get up after a few hours, you feel much better.<br><br>You recovered <b style='color:lightgreen;'>".$regain."</b> health points.");
    }
    
    function command_suicide($db, $elements, $player){
        $startID = get_starting_page_id_for_player($db, $player);
        $db->prepare("UPDATE player SET page_id=? WHERE id=?")->execute([$startID, $player["id"]]);    // First page (ID:1)
        $db->prepare("UPDATE player SET hp=? WHERE id=?")->execute([BASE_HP, $player["id"]]);    
        $db->prepare("DELETE FROM player_prop WHERE player_id=?")->execute([$player["id"]]);
        return_200("death", get_page($db, $startID, $player));
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
        return_200("status", "You are user [<span style='color:yellow;'>".$player["id"]."</span>]<br><br><i style='color:grey;'>\"Alan...? That's your name, isn't it?\"<br>\"The name of my user.\"</i>");
    }
    
    function command_page_id($db, $elements, $player){
        return_200("status", "The ID of your current location is: <span style='color:yellow;'>".$player["location"]."</span>");
    }
    
    function command_client_id($db, $elements, $player){
        return_200("status", "Your client ID is: <span style='color:yellow;font-weight:bold'>".$player["client_id"]."</span> (ADDR: <span style='font-weight:bold;color:lightgreen;'>".$player["client_address"]."</span>)");
    }
    
    function command_brief($db, $elements, $player){
        $page = get_page($db, $player["location"], $player);
        return_200("brief", $page);
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
        $db->prepare("UPDATE player SET page_id=? WHERE id=?")->execute([get_starting_page($player)."", $player["id"]]);
        $page = get_page($db, get_starting_page($player), $player);
        return_200("page", $page);
    }
    
    function command_crash($db, $elements, $player){
        var_dump("crash");
        exit;
    }

    function command_warp($db, $elements, $player){
        $dimName = $elements[0];              
        $state = $db->prepare("SELECT id,name FROM dimension WHERE name=?");
        $state->execute([$dimName]);
        $data = $state->fetch();
        if ($data === false){
            return_200("status", "This region is unknown. Type REGIONS to get a list of known regions.");
        }
        if ($data["id"] == $player["dimension"]){
            return_200("status", "You already are exploring region ".$data["name"].".");
        }
        $dimension = $data["id"];
        $dimName = $data["name"];
        $player["dimension"] = $dimension;
        $player["dimension_name"] = $dimName;

        // Building response
        $page = get_page($db, get_starting_page_id_for_dimension($db, $dimension), $player);
        $page["dimension_name"] = $player["dimension_name"];
        $page["pages_count"] = get_page_count_in_dimension($db, $player["dimension"]);

        $db->prepare("UPDATE player SET page_id=? WHERE id=?")->execute([$page["id"], $player["id"]]);
        return_200("warp", $page);
    }

    function command_get_dimension($db, $elements, $player){
        if (count($elements) > 0){
            command_warp($db, $elements, $player);
        }
        return_200("status", "You are currently exploring the region of ".$player["dimension_name"]);
    }

    function command_list_dimensions($db, $elements, $player){
        $state = $db->prepare("SELECT readonly,name FROM dimension ORDER BY readonly ASC");
        $state->execute();
        $data = $state->fetchAll();
        $dimensions = "REGION&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;STATUS<br>=============================";
        foreach($data as $row){
            $dimensions.= "<br>".($player["dimension_name"] === $row["name"] ? "&gt; <b class='emphasis'>" : "<b>").str_replace(" ", "&nbsp;", str_pad($row["name"], ($player["dimension_name"] === $row["name"] ? 8 : 10), " "))."</b>|&nbsp;&nbsp;&nbsp;&nbsp;".($row["readonly"] ? "History" : "Unexplored");
        }
        return_200("status", "You have heard of the following regions:<br><br>".$dimensions);
    }
        
  function command_hp_status($db, $elements, $player){
      $msg = "";
      if ($player["hp"] < BASE_HP/4){
        $msg .= "At this point, you're hardly keeping yourself together.<br>You can barely stand, and definitely need some rest.";
      }
      else if ($player["hp"] < BASE_HP/2){
        $msg .= "You are not feeling well. Maybe some rest is in order.";
      }
      else if ($player["hp"] < BASE_HP){
        $msg .= "A few bruises, but nothing bad.";
      }
      else if ($player["hp"] < BASE_HP*1.5){
        $msg .= "You feel fresh and ready to pursue your ADVNTURE, wherever it may lead!";
      }
      else if ($player["hp"] < BASE_HP*4){
        $msg .= "You feel unusally strong and robust.<br>Nothing can stop you.";
      }
      else if ($player["hp"] < BASE_HP*10){
        $msg .= "You feel like your skin is steel-plated, harmproof.<br>You're filled with determination.";
      }
      else if ($player["hp"] <= MAX_HP){
        $msg .= "A supernatural strength inhabits you.<br>You feel invincible.";
      }
      
      $msg .= "<br>You currently have ".$player["hp"]." health points.";
      return_200("status", $msg);
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
              hp_event.hp_change,
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
              LEFT JOIN hp_event hp_event ON hp_event.page_id = page.id
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
        $hp_change = 0;
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
            if ($row["hp_change"] != null){
                $hp_change += $row["hp_change"];
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
                <br>
                <br>Health change: '.$hp_change.'<br>
                <br><b>Props</b>: '.implode(", ", $strProps).'<br>
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