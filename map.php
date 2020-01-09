<?php 
	
 ini_set('display_errors', 1); 
	 ini_set('display_startup_errors', 1); 
	 error_reporting(E_ALL);

	include "database.php";
	
	$GLOBALS["map"] = array();

	function make_map($db){
		$pages = [];
		$connections = [];
		
		$dir_to_pos = array(
			"NORTH"=>array(-1, 0),
			"SOUTH"=>array(1, 0),
			"EAST"=>array(0, 1),
			"WEST"=>array(0, -1)
		);
		$allowed_directions = array_keys($dir_to_pos);
		
		$s = $db->prepare("SELECT page_succession.command,page.id,page_succession.origin_id,page_succession.target_id FROM page 
		LEFT JOIN page_succession page_succession ON (page_succession.target_id = page.id OR page_succession.origin_id = page.id)AND page_succession.command IN ('".implode("', '", $allowed_directions)."')");
		$s->execute();
		
		while($row = $s->fetch()){
			if (!in_array($row["command"], $allowed_directions)) continue;
			$pages [$row["id"]]= array("id"=>$row["id"]);
			if (!isset($connections[$row["origin_id"]])){
				$connections[$row["origin_id"]] = array();
			}
			$connections[$row["origin_id"]] [$row["command"]]= $row["target_id"];
		}
		
		explore_fully($connections, 1, array(0,0), array(), $dir_to_pos);
		
	}
	
	function explore_fully($connections, $id, $position, $ignore_list, $dir_to_pos){

		if (!isset($GLOBALS["map"][$position[0]])) $GLOBALS["map"][$position[0]] = array();
		if (!isset($GLOBALS["map"][$position[0]][$position[1]])) $GLOBALS["map"][$position[0]][$position[1]] = array();
		$GLOBALS["map"][$position[0]][$position[1]] []= $id;
		//var_dump("Registering ".$id." at position x:".$position[0]." y:".$position[1]. "(map size: ".count($GLOBALS["map"]).")<br>");
		
		if (!isset($connections[$id])){
			return;
		}
		$possibilities = $connections[$id];
		foreach($possibilities as $command=>$target){
			if (in_array($target, $ignore_list)) continue;
			$ignore_list []= $target;
			explore_fully($connections, $target, array($position[0] + $dir_to_pos[$command][0], $position[1] + $dir_to_pos[$command][1]), $ignore_list, $dir_to_pos);
		}
	}

	if ($_GET["show_map"] != getEnv("ADVNTURE_ADMIN_PASS")) exit;
				
	make_map(get_connection());
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
		<div class="mainContainer">
			<div style="display:flex;flex-direction:row;justify-content:space-around;">
			<?php 
				$size = 48;
				$map = $GLOBALS["map"];
				$xBounds = [0,0];
				$yBounds = [0,0];
				$color = array(2=>"yellow", 3=>"orange", 4=>"red");
				
				foreach($map as $x=>$ys){
					if ($x > $xBounds[1]) $xBounds[1] = $x;
					if ($x < $xBounds[0]) $xBounds[0] = $x;
					foreach($ys as $y=>$_){
						if ($y > $yBounds[1]) $yBounds[1] = $y;
						if ($y < $yBounds[0]) $yBounds[0] = $y;
					}
				}
				
				for($i = $xBounds[0]-1; $i <= $xBounds[1]+1; $i++){
					?>
					<div style="display:flex;flex-direction:column;justify-content:space-around;">
					<?php
					for($j = $yBounds[0]-1; $j <= $yBounds[1]+1; $j++){
						$x = $i;
						$y = $j;
						$is_something = isset($map[$x]) && isset($map[$x][$y]) && count($map[$x][$y]) > 0;
						?>
						<div style="width:<?php echo $size; ?>px;height:<?php echo $size; ?>px;border:1px <?php 
						if ($is_something && $map[$x][$y][0] == "1"){ echo "solid lightgreen";}
						else if ($is_something && count($map[$x][$y]) > 1) { echo "solid ".$color[count($map[$x][$y])]."";}
						else if ($is_something){ echo "solid white";} 
						else{ echo "dotted grey";} 
						?>; margin:2px; padding:2px;">
							<?php
								if ($is_something){
									foreach($map[$x][$y] as $page){
										echo $page." ";
									}					
								}		
							?>
						</div>
						<?php
					}
					?>
					</div>
					<?php
				}
			?>
			</div>
		</div>
	</body>
</html>