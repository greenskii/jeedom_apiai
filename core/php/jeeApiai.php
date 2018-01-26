
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
	
	
	function EXECUTE_commands($commands) {
		
		$return = array();
		
		foreach($commands as $command) {
			$return[] = apiai::execute_command($command);
		}
		return $return;
	}	
	
	function traiteInput($input){
		switch ($input['intent']) {
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
				$payload = EXECUTE_commands($input['payload']);
				return $payload;
				break;
				
		}
	}
	
	$entityBody = file_get_contents('php://input');
	$body = json_decode($entityBody, true);
	$reply['requestId'] = $body['requestId'];
	foreach ($body['inputs'] as $input) {
		$reply['payload'] = traiteInput($input);
	}
	
	$response = $reply;
	
	echo json_encode($response);

?>
