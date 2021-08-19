<?php

namespace map;

include_once "page.php";

function get_html_characters_map($db, $player, $radius=3){
    $str_position = $player["position"];
    
    if (is_player_dimension_grid_based($db, $player)){
        
        $page_id = get_first_page_with_position($db, $str_position, $player["dimension"]);
        
        if ($page_id){
            $arr_position = explode(" ", $str_position);
            
            $query = "
                SELECT
                    biome.color,
                    biome.characters
                FROM 
                    page
                    LEFT JOIN biome ON biome.id=page.biome_id
                WHERE 
                    page.dimension_id=".$player["dimension"]."
                    AND page.is_hidden=0
                    AND page.position=?
                ORDER BY page.id ASC
            ";
            
            $statement = $db->prepare($query);
            $final_str = "";
            
            for ($y = $arr_position[1] + $radius; $y >= $arr_position[1] - $radius; $y--){
                
                for ($x = $arr_position[0] - $radius; $x <= $arr_position[0] + $radius; $x++){
                    
                    if ($x == $arr_position[0] && $arr_position[1] == $y){
                        $final_str .= "<span style='color:white;'>"."☺"."</span>";
                    }
                    else{
                        $statement->execute([$x." ".$y]); 
                        $position_fetch = $statement->fetch();
                        
                        if ($position_fetch){
                            $final_str .= "<span style='color:#".$position_fetch["color"].";'>".mb_substr($position_fetch["characters"], 0, 1)."</span>";
                        }
                        else{
                            $final_str .= "?";
                        }
                    }
                    
                    $final_str .= " ";
                }
                $final_str .= "<br>";
            }
            
            $final_str = "You stop for a minute and try to remember what the surroundings look like.<br>For every place you have not explored yet, you write a '?'.<br><br>".$final_str;

            return $final_str;
        }
        else{
            // Note: This is a bug! It should not happen
            return "Unsure of where you are exactly, you fail to draw a map that reminds of you of the places you've been.";
        }        
    }
    else{
        return "For this region's locations are bound by storylines, and not straight paths, it is impossible for you to make a map from memory.";
    }    
}


?>