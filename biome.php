<?php
    function get_biome_contents($db){
        $contents = [];
        
        $statement = $db->prepare("SELECT name,color,characters FROM biome ORDER BY id ASC");
        $statement->execute();
        
        while($row = $statement->fetch()){
            $contents[$row["name"]] = array("color"=>"#".$row["color"], "character"=>$row["characters"]);
        }
        
        return $contents;        
    }

    function determine_biome($db, $text){
        
        $statement = $db->prepare("SELECT biome_id FROM biome_words WHERE INSTR(?, word) ORDER BY biome_id ASC");
        $statement->execute([$text]);
        
        $info = $statement->fetch();
        
        if ($info){
            return $info["biome_id"];
        }
        else{
            return get_default_biome($db);
        }
    }
    
    function get_biome_by_name($db, $name){
        $statement = $db->prepare("SELECT id FROM biome WHERE name=? ORDER BY id ASC");
        
        $statement->execute([strtoupper($name)]);
        
        return ($row = $statement->fetch()) ? $row["id"] : get_default_biome($db);
    }

    function get_default_biome($db){
        $statement = $db->prepare("SELECT id FROM biome ORDER BY id ASC");
        $statement->execute();
        return $statement->fetch()["id"];
    }
?>