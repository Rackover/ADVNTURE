<?php 

 ini_set('display_errors', 1); 
 ini_set('display_startup_errors', 1); 
 error_reporting(E_ALL);

	include "database.php";

	function update(){
		$db = get_connection();
		$words = file_get_contents("static/words.txt");
		foreach(explode("\n", $words) as $word){
			$db->prepare("INSERT IGNORE INTO word_blacklist (word, gravity) VALUES (?, 1) ")->execute([trim(str_replace("\n", "", str_replace("\r", "", $word)))]);
		}
	}

	if ($_GET["update"] === getEnv("ADVNTURE_ADMIN_PASS")) update();
	
?>
