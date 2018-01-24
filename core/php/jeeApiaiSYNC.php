
<?php


	header('Content-type: application/json');
	require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

	
	function SYNC_devices() {

		$sync_new = array();
		
		$PluginToSend = apiai::PluginToSend();
		
		// devices et equipements
		$devices = apiai::discovery_eqLogic($PluginToSend);
		
		// scenarios (scenes)
		$scenarios = apiai::discovery_scenario($PluginToSend);
		
		$sync_new = array_merge($devices, $scenarios);
		
		return $sync_new;
	}
	
	
	function traiteIntent($intent){
		switch ($intent) {
			case "action.devices.SYNC" : 
				log::add('apiai', 'debug', 'Demande de Sync');
				$payload = array();
				$payload['agentUserId'] = 'jeedom-apiaiplugin-' . jeedom::getApiKey('apiai');
				$payload['devices'] = SYNC_devices();
				return $payload;
				break;
				
			case "action.devices.QUERY" : 
				return "QUERY";
				break;
				
			case "action.devices.EXECUTE" : 
				return "EXECUTE";
				break;
				
		}
	}
	
	$entityBody = file_get_contents('php://input');
	$body = json_decode($entityBody, true);
	$reply['requestId'] = $body['requestId'];
	foreach ($body['inputs'] as $intent) {
		$reply['payload'] = traiteIntent($intent['intent']);
	}
	
	//$reply = interactQuery::tryToReply($body['result']['resolvedQuery'], $param);
	
	/*if (empty($reply['reply'])){
		$reply['reply'] = 'Je n\'ai pas compris la demande';
	}*/

	$response = $reply;
	//file_put_contents('/tmp/bot.log', print_r($_SERVER, true) . ' ' . $entityBody . ' ' . $reply['reply']);
	
	echo json_encode($response);

?>
