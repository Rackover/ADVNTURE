<?php 

 ini_set('display_errors', 1); 
 ini_set('display_startup_errors', 1); 
 error_reporting(E_ALL);

	include "database.php";

	function update(){
		$db = get_connection();
        
        $statement = $db->prepare("TRUNCATE TABLE biome_words");
        $statement->execute();
        
        
        $statement = $db->prepare("SELECT id,name FROM biome");
        $statement->execute();
        
        while($row = $statement->fetch()){
            $path = "static/".strtolower($row["name"]).".txt";
            $words = file_get_contents($path);
            foreach(explode("\n", $words) as $word){
                $biome_word = trim(str_replace("\n", "", str_replace("\r", "", $word)));
                if (!strlen($biome_word)){
                    continue;
                }
                
                $db->prepare("INSERT IGNORE INTO biome_words (biome_id, word) VALUES (?, ?) ")->execute([$row["id"], $biome_word]);
            }
        }
        
	}

	if ($_GET["update"] === getEnv("ADVNTURE_ADMIN_PASS")) update();
	
?>
