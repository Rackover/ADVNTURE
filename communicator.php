 <?php
 
 
 ini_set('display_errors', 1); 
 ini_set('display_startup_errors', 1); 
 error_reporting(E_ALL);
 
 session_start();
 
 
include "database.php";
include "commands.php";
 
$db = get_connection();
$player = get_player($db);

if ($player["is_banned"]) unset($_POST["action"]);

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
	echo json_encode([
		"type"=>$player["is_new"] ? "intro" : "page",
		"content"=>get_page($db, $player["location"], $player)
	]);
	exit;
}

header('HTTP/1.0 503 Service Unavailable');
echo json_encode(["type"=>"error","content"=>"ERR 503: Service unavailable"]);
exit;

?>
