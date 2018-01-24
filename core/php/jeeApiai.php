<?php
	
	
	require_once dirname(__FILE__) . "/core/php/core.inc.php";
	if (user::isBan() && false) {
		header("Status: 404 Not Found");
		header('HTTP/1.0 404 Not Found');
		$_SERVER['REDIRECT_STATUS'] = 404;
		echo "<h1>404 Not Found</h1>";
		echo "The page that you have requested could not be found.";
		die();
	}
	
	
	$apikey = !empty($_SERVER['HTTP_APIKEY']) ? $_SERVER['HTTP_APIKEY'] : init('apikey','');
	file_put_contents('/tmp/bot.log', $apikey);
	
	
	if ($apikey === '' || !jeedom::apiAccess($apikey)) {
		header("HTTP/1.1 401 Unauthorized" );
		die;
	}
	
	
	$param = array(); 
	
	$entityBody = file_get_contents('php://input');
	
	$body = json_decode($entityBody, true);
	
	$reply = interactQuery::tryToReply($body['result']['resolvedQuery'], $param);
	
	if (empty($reply['reply'])){
		$reply['reply'] = 'Je n\'ai pas compris la demande';
	}
	
	header('Content-Type', 'application/json');
	$response = array('speech' => $reply['reply'], 'displayText' => $reply['reply']);
	//file_put_contents('/tmp/bot.log', print_r($_SERVER, true) . ' ' . $entityBody . ' ' . $reply['reply']);
	
	echo json_encode($response);

?>