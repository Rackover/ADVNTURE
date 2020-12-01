<?php 
	
 ini_set('display_errors', 1); 
	 ini_set('display_startup_errors', 1); 
	 error_reporting(E_ALL);

	include "database.php";
	include "page.php";
	
	$GLOBALS["map"] = array();
	$GLOBALS["pages"] = array();
	$GLOBALS["connections"] = array();

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
		
		$s = $db->prepare("SELECT page.is_dead_end,page.content,page_succession.command,page.id,page_succession.origin_id,page_succession.target_id FROM page 
		LEFT JOIN page_succession page_succession ON (page_succession.target_id = page.id OR page_succession.origin_id = page.id)");
		$s->execute();
		
		while($row = $s->fetch()){
			$pages [$row["id"]]= array("id"=>$row["id"], "title"=>explode("\n", $row["content"])[0], "is_dead_end"=>$row["is_dead_end"]);
			$ids = array($row["origin_id"], $row["target_id"]);
			foreach($ids as $id){
				if (!isset($connections[$id])){
					$connections[$id] = array();
				}
        $cmd = ($id === $row["origin_id"] ? $row["command"] : reverse_direction_command($row["command"]));
        if ($cmd === false) continue;
				$targetId = $id === $row["origin_id"] ? $row["target_id"] : $row["origin_id"];
				$connections[$id] [$cmd] = $targetId;
			}
			
		}
		
		explore_fully($connections, 1, array(0,0), array(), $dir_to_pos, $allowed_directions );
		$GLOBALS["pages"] = $pages;
		$GLOBALS["connections"] = $connections;
	}
	
	function explore_fully($connections, $id, $position, $ignore_list, $dir_to_pos, $allowed_directions ){

		if (!isset($GLOBALS["map"][$position[0]])) $GLOBALS["map"][$position[0]] = array();
		if (!isset($GLOBALS["map"][$position[0]][$position[1]])) $GLOBALS["map"][$position[0]][$position[1]] = array();
		if (in_array($id, $GLOBALS["map"][$position[0]][$position[1]])) return;
		$GLOBALS["map"][$position[0]][$position[1]] []= $id;
		//var_dump("Registering ".$id." at position x:".$position[0]." y:".$position[1]. "(map size: ".count($GLOBALS["map"]).")<br>");
		
		if (!isset($connections[$id])){
			return;
		}
		$possibilities = $connections[$id];
		foreach($possibilities as $command=>$target){
			if (in_array($target, $ignore_list)) continue;
			if (!in_array($command, $allowed_directions)) continue;
			$ignore_list []= $target;
			explore_fully($connections, $target, array($position[0] + $dir_to_pos[$command][0], $position[1] + $dir_to_pos[$command][1]), $ignore_list, $dir_to_pos, $allowed_directions);
		}
	}

	if ((!isset($_GET["pass"]) || $_GET["pass"] != getEnv("ADVNTURE_MAP_PASS"))) exit;
				
	make_map(get_connection());
	
	
	$color = array(0=>"white", 1=>"lightgreen", 2=>"yellow", 3=>"orange", 4=>"red");
?>
<!doctype html>
<html lang="en" STYLE="margin:0;padding:0;">
	<head>
		<meta charset="utf-8" />
		<LINK href="style.css?version=<?php echo rand();?>" rel="stylesheet" type="text/css">
		<link rel="icon" href="favicon.jpg" />
		<title>ADVNTURE.WEB</title>
		
		
  <script src="res/scripts/lib/go.js"></script>
  <script id="code">
    function init() {
		
      var $ = go.GraphObject.make;  // for conciseness in defining templates
	  var layout = new go.ForceDirectedLayout();
	  layout.defaultSpringLength  = 30;
	  
      myDiagram = $(go.Diagram, "diagram",  // create a Diagram for the DIV HTML element
        {
		  "layout": layout
        });
      // define a simple Node template
      myDiagram.nodeTemplate =
        $(go.Node, "Auto",  // the Shape will go around the TextBlock
          $(go.Shape, "RoundedRectangle", { strokeWidth: 0, fill: "white" },
            // Shape.fill is bound to Node.data.color
            new go.Binding("fill", "color")),
          $(go.TextBlock,
            { margin: 5, stroke: '#333' }, // Specify a margin to add some room around the text
            // TextBlock.text is bound to Node.data.key
            new go.Binding("text", "name"),
			new go.Binding("font", "font"))
        );
      // but use the default Link template, by not setting Diagram.linkTemplate
      // create the model data that will be represented by Nodes and Links
      myDiagram.model = new go.GraphLinksModel();
	 myDiagram.model.linkFromPortIdProperty = "fromPort";
	 myDiagram.linkTemplate =
    $(go.Link,
	
      { curve: go.Link.Bezier},
      $(go.Shape, {stroke: "grey"}),
      $(go.Shape, { toArrow: "Standard", stroke: "grey"}),
      $(go.TextBlock, new go.Binding("text", "fromPort"), { stroke: "grey", segmentOffset: new go.Point(0, -10), segmentFraction: 0.3, segmentOrientation: go.Link.OrientUpright  })
	 );
		
		const elements = <?php 
			$elements = [];
			foreach($GLOBALS["pages"] as $page){
				if (!isset($GLOBALS["connections"][$page["id"]])) continue;
				$linksCount = $GLOBALS["connections"][$page["id"]];
				$elements []= array("key"=>$page["id"], "name"=>$page["title"], "font"=>"".(10+2*count($linksCount))."px DOS", "color"=>($page["is_dead_end"] ? "yellow" : "white"));
			}
		
			echo json_encode($elements);
		?>;
		const links = <?php 
			$links = [];
			foreach($GLOBALS["connections"] as $pageId=>$pageOutputs){
				foreach($pageOutputs as $command=>$destination){
					if ($destination === null) continue;
					$links []= array("from"=>$pageId, "to"=>$destination, "fromPort"=>$command);
				}
			}
			echo json_encode($links);
		?>;
		myDiagram.model.addNodeDataCollection(elements);
		myDiagram.model.linkDataArray = links;
    }
  </script>
	</head>
	<body onload="init()" STYLE="margin:0;padding:0;">
		<div class="mainContainer" style="width:100%;">
			
			<?php if (isset($_GET["show_graph"])){
				// var_dump($GLOBALS["connections"]["".(89)]);
			?>
			
			<h1>NODE MAP</h1>
			<div style="width:100%;height:800px;overflow:hidden;display:flex;flex-direction:row;justify-content:center;">
				<div style="width:80%;height:100%;overflow:hidden;border:1px dotted grey;" id="diagram">
				
				</div>
			</div>
			<?php } 
			
			if (isset($_GET["show_map"])) { ?>
			<h1>GRID MAP</h1>
			<div style="padding:25px;display:inline-block;">
				<div style="display:flex;flex-direction:row;justify-content:space-around;">
				<?php 
					$size =60;
					$map = $GLOBALS["map"];
					$xBounds = [0,0];
					$yBounds = [0,0];
					
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
		</div>
		<?php } ?>
	</body>
</html>