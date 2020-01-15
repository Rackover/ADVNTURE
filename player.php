<?php

define("INVENTORY_LIMIT", 10);

function get_client($db, $ip){
	$state = $db->prepare("SELECT id, is_banned, ban_reason FROM client WHERE address=?");
	$state->execute([$ip]);
	$result = $state->fetch();
	
	if ($result===false){
		create_client($db, $ip);
		return get_client($db, $ip);
	}
	return $result;
}

function create_client($db, $ip){
	$state = $db->prepare("INSERT INTO client (address, is_banned) VALUES (?, ?)");
	$state->execute([$ip, 0]);
}

function get_player($db, $new=false){
	
	$ip = md5($_SERVER["REMOTE_ADDR"]);
	
	$client = get_client($db, $ip);	
	$playerId = hash("sha256", uniqid());
	
	if (isset($_SESSION["playerId"])){
		$playerId = $_SESSION["playerId"];
	}
	
	$statement = $db->prepare("
		SELECT
		  player.page_id,
		  player_prop.prop_id,
		  player_prop.original_page_id,
		  player_prop.id AS player_prop_id,
		  prop.name
		FROM
		  player
		  LEFT JOIN player_prop player_prop ON player.id = player_prop.player_id
		  LEFT JOIN prop prop ON player_prop.prop_id = prop.id
		WHERE
		  player.id=".($db->quote($playerId))."
	");
	$statement->execute([ $ip ]);
	$result = $statement->fetchAll();
	
	if ($result === false || count($result) === 0){
		create_player($db, $client["id"], $playerId);
		$_SESSION["playerId"] = $playerId;
		return get_player($db, true);
	}
	else{
		$player = array(
			"location"=>$result[0]["page_id"],
			"is_banned"=>$client["is_banned"],
			"props"=>array(),
			"id"=>$playerId,
			"client_id"=>$client["id"],
			"is_new"=>$new
		);
		foreach($result as $row){
			if ($row["prop_id"] === null) continue;
			$player["props"] []= [
				"id"=>$row["prop_id"],
				"origin"=>$row["original_page_id"],
				"name"=>$row["name"],
				"assignment_id"=>$row["player_prop_id"]
			];
		}
		return $player;		
	}
}

function ban_client($db, $id, $reason){
	$state = $db->prepare("UPDATE client SET is_banned=1, ban_reason=? WHERE id=".$id);
	$state->execute([$reason]);
}

function player_lose_object($db, $player_id, $object_id){
	$db->prepare("DELETE FROM player_prop WHERE prop_id=? AND player_id=?")->execute([$object_id, $player_id]);
}

function create_player($db, $clientId, $playerId){
	$statement = $db->prepare("INSERT IGNORE INTO player (id, client_id, page_id) VALUES (?, ?, ?)");
	$statement->execute([ $playerId, $clientId, "1" ]);
}

function is_full_inventory($player){
	return count($player["props"]) >= INVENTORY_LIMIT;
}

?>