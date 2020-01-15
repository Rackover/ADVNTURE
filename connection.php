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

?>