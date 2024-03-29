 <?php

ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);

ini_set('session.gc_probability', 0);
ini_set('session.gc_maxlifetime', 315360000);
ini_set('session.cookie_lifetime', 315360000);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);

session_save_path(realpath('/tmp/advnture'));

session_start();
 
include_once "database.php";
include_once "commands.php";
include_once "player.php";
 
$db = get_connection();
$player = get_player($db);

if ($player["is_banned"]){
	unset($_POST["action"]);
	return_503_banned();
}

$actions = array(
	"RECOVER"=>'recover',
	"COMMAND"=>'identify_command',
	"SUBMISSION"=>'receive_submission'
);

if (isset($_POST["action"])){	
	$action = $_POST["action"];
	if ($db && isset($actions[$action])){
		$actions[$action]($db, $_POST, $player);
		exit;
	}
	else{
		header('HTTP/1.0 400 Bad Request');
		echo json_encode(["type"=>"error","content"=>"ERR 400: Bad request"]);
		exit;
	}
}

function recover($db, $p, $player){
    player_give_vision_on_page($db, $player["id"], $player["location"]);
    
	$content = get_page($db, $player["location"], $player);
	
    $content["dimension_type"] = is_player_dimension_grid_based($db, $player) ? "GRID" : "BRANCH";
	$content["dimension_name"] = $player["dimension_name"];
	
	// Starting page name
	$starting_page_id = get_starting_page_id_for_player($db, $player);	
	$starting_page = get_page($db, $starting_page_id, $player);
	
	$content["dimension_starting_page_name"] = explode("\n", $starting_page["content"])[0];
	$content["pages_count"] = get_page_count_in_dimension($db, $player["dimension"]);
    
	echo json_encode([
		"type"=>$player["is_new"] ? "intro" : "recovery",
		"content"=>$content
	]);
	exit;
}

header('HTTP/1.0 500 Server error');
echo json_encode(["type"=>"error","content"=>"ERR 500: Server error"]);
exit;

?>
