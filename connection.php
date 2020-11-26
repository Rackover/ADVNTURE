<?php 
	function return_503(){
		header('HTTP/1.0 503 Service Unavailable');
		echo json_encode(["type"=>"error","content"=>"ERR 503: Service unavailable"]);
		exit;	
	}

	function return_404($cmd=""){
		header('HTTP/1.0 404 Not Found');
		echo json_encode(["type"=>"error","content"=>"ERR 404: Unrecognized command: ".$cmd]);
		exit;
	}
	
	function return_200($type, $content){
		echo json_encode([
			"type"=>$type,
			"content"=>$content
		]);
		exit;
	}
	
	function return_200_hourglass($type, $content, $hourglass){
		echo json_encode([
			"type"=>$type,
			"content"=>$content,
			"hourglass"=>$hourglass
		]);
		exit;
	}

	function return_503_banned(){
		header('HTTP/1.0 503 Service Unavailable');
		echo json_encode(["type"=>"error","content"=>"<span style='color:red;'>ERR 503: Service unavailable</span><br><br>
		<b style='color:white;'>The world of ADVNTURE had to shut itself to you, unknown advnturer, as to protect itself from harm.</b><br>You may come back to it later, maybe - but there is no guarantee these gates will ever open to you again.<br>The higher instances wish you better luck in your next adventures!"]);
		exit;
	}

?>