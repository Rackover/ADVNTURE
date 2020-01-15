<?php
function get_connection(){
	
	$server = "localhost";
	$user = getEnv("ADVNTURE_DATABASE_USERNAME");
	$pass = getEnv("ADVNTURE_DATABASE_PASS");

	try {
		$driver_options = array(
		   PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
		   PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		   PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		);               
		$conn = new PDO("mysql:host=$server;dbname=adventure", $user, $pass, $driver_options);
		return $conn;
	}
	catch(PDOException $e){
		var_dump($e->getMessage());
		return false;
	}
}

?>
