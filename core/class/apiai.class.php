<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class apiai extends eqLogic {
	/*     * *************************Attributs****************************** */

	//private static $_PLUGIN_COMPATIBILITY = array('openzwave', 'rfxcom', 'edisio', 'ipx800', 'mySensors', 'Zibasedom', 'virtual', 'camera','apcupsd', 'btsniffer', 'dsc', 'h801', 'rflink', 'mysensors', 'relaynet', 'remora', 'unipi', 'playbulb', 'doorbird','netatmoThermostat');

	/*     * ***********************Methode static*************************** */

	public static function getCustomGenerics(){
		$CUSTOM_GENERIC_TYPE = array(
			'ACTIVE' => array('name' => 'Statut Actif (Homebridge)', 'family' => 'Generic', 'type' => 'Info', 'ignore' => true, 'apiai_type' => true),
		);
		return $CUSTOM_GENERIC_TYPE;
	}


	public static function Pluginsuported() {
		$Pluginsuported = ['openzwave'];
		return $Pluginsuported;
	}
	
	public static function PluginWidget() {
		$PluginWidget = ['alarm','camera','thermostat','netatmoThermostat','weather','mode'];	
		return $PluginWidget;
	}
	
	public static function PluginMultiInEqLogic(){
		$PluginMulti = ['LIGHT_STATE','ENERGY_STATE','FLAP_STATE','HEATING_STATE','SIREN_STATE','LOCK_STATE'];
		return $PluginMulti;
	}
	
	public static function PluginToSend() {
		$PluginToSend=[];
		$plugins = plugin::listPlugin(true);
		$plugin_compatible = apiai::Pluginsuported();
		$plugin_widget = apiai::PluginWidget();
		foreach ($plugins as $plugin){
			$plugId = $plugin->getId();
			if ($plugId == 'apiai') {
				continue;
			} else if (in_array($plugId,$plugin_widget)) {
				array_push($PluginToSend, $plugId);
			} else if (in_array($plugId,$plugin_compatible) && !in_array($plugId,$plugin_widget) && config::byKey('sendToApp', $plugId, 1) == 1){
				array_push($PluginToSend, $plugId);
			} else if (!in_array($plugId,$plugin_compatible) && config::byKey('sendToApp', $plugId, 0) == 1){
				$subClasses = config::byKey('subClass', $plugId, '');
				if ($subClasses != ''){
					$subClassesList = explode(';',$subClasses);
					foreach ($subClassesList as $subClass){
						array_push($PluginToSend, $subClass);
					}
				}
				array_push($PluginToSend, $plugId);
			} else {
				continue;
			}
		}
		return $PluginToSend;
		
	}
	

	/**************************************************************************************/
	/*                                                                                    */
	/*            Permet de decouvrir tout les modules de la Jeedom compatible            */
	/*                                                                                    */
	/**************************************************************************************/

	public static function discovery_eqLogic($plugin = array(),$hash = null){
		
		log::add('apiai', 'debug', 'Entering Plugins discovery loop...');
		$return = array();
		
		foreach ($plugin as $plugin_type) {
			$eqLogics = eqLogic::byType($plugin_type, true);
			
			if (is_array($eqLogics)) {
				log::add('apiai', 'debug', 'Entering EQ discovery loop...');
				foreach ($eqLogics as $eqLogic) {
					if($eqLogic->getObject_id() !== null && object::byId($eqLogic->getObject_id())->getDisplay('sendToApp', 1) == 1 && $eqLogic->getIsEnable() == 1 && ($eqLogic->getIsVisible() == 1 || in_array($eqLogic->getEqType_name(), self::PluginWidget()))){
						$eqLogic_array = utils::o2a($eqLogic);

						$eqLogic_array["type"] = $eqLogic_array['category'];;

						
						/*switch ($type) {
							
							"CAMERA" : eqLogic_array["type"] = "action.devices.types.CAMERA";
							
							action.devices.types.CAMERA	
							action.devices.types.DISHWASHER	
							action.devices.types.DRYER	
							action.devices.types.LIGHT	
							action.devices.types.OUTLET	
							action.devices.types.REFRIGERATOR	
							action.devices.types.SCENE	
							action.devices.types.SWITCH	
							action.devices.types.THERMOSTAT	
							action.devices.types.VACUUM	
							action.devices.types.WASHER	
						}*/

						$eqLogic_array["willReportState"] = false;
						
						// on récupere les commandes pour alimenter les "traits"
						$traitsAndAttributes = apiai::discovery_cmd($eqLogic);
						 $eqLogic_array["traits"] = $traitsAndAttributes["traits"];
						$eqLogic_array["attributes"] = $traitsAndAttributes["attributes"];
						
						
						
						/*"id": "123",
					      "type": "action.devices.types.OUTLET",
					      "traits": [
					        "action.devices.traits.OnOff"
					      ],
					      "name": {
					        "defaultNames": ["My Outlet 1234"],
					        "name": "Night light",
					        "nicknames": ["wall plug"]
					      },
					      "willReportState": true,
					      "deviceInfo": {
					        "manufacturer": "lights-out-inc",
					        "model": "hs1234",
					        "hwVersion": "3.2",
					        "swVersion": "11.4"
					      },
					      "customData": {
					        "fooValue": 74,
					        "barValue": true,
					        "bazValue": "foo"
					      }*/
										
						
						
						// on supprime les infos qui nous interesse pas 
						unset($eqLogic_array['eqReal_id'],$eqLogic_array['configuration'], $eqLogic_array['specificCapatibilities'],$eqLogic_array['timeout'],
								$eqLogic_array['category'],$eqLogic_array['display'],$eqLogic_array['status'],$eqLogic_array['logicalId'],$eqLogic_array['object_id'],
								$eqLogic_array['eqType_name'],$eqLogic_array['isVisible'],$eqLogic_array['isEnable'], $eqLogic_array['order'], $eqLogic_array['comment']);
								
						$eqLogic_array["name"] = array("name" => $eqLogic_array["name"] );
						
						$return[] = $eqLogic_array;
					}

				}
				
			}
		}
		return $return;
	}
	
	public static function discovery_cmd($eqLogic){
		$traitsReturn = array();
		$attributesReturn = array();
		$genericisvisible = array();
	
		log::add('apiai', 'debug', 'Discovering commands of Eq ID ' . print_r($eqLogic->getObject_id(), true) .'...');
			
	
		foreach (jeedom::getConfiguration('cmd::generic_type') as $key => $info) {
			if ($info['family'] !== 'Generic') {
			    array_push($genericisvisible, $key);
			}
		}
	
		$i = 1;
		
		log::add('apiai', 'debug', 'Entering Commands discovery loop...');
		foreach ($eqLogic->getCmd() as $cmd) {
			if($cmd->getDisplay('generic_type') != null && !in_array($cmd->getDisplay('generic_type'),['GENERIC_ERROR','DONT']) && ($cmd->getIsVisible() == 1 || in_array($cmd->getDisplay('generic_type'), $genericisvisible) || in_array($eqLogic->getEqType_name(), self::PluginWidget()))){
			  		$cmd_array = $cmd->exportApi();

					log::add('apiai', 'debug', 'Command ' . print_r($i, true) .'...');
			  					
			    // traitements sur les traits et les attributes
			    
				/* Traits possible:
					https://developers.google.com/actions/smarthome/traits/
					action.devices.traits.Brightness	
					action.devices.traits.CameraStream	
					action.devices.traits.ColorSpectrum	
					action.devices.traits.ColorTemperature	
					action.devices.traits.Dock	
					action.devices.traits.Modes	
					action.devices.traits.OnOff	
					action.devices.traits.RunCycle	
					action.devices.traits.Scene	
					action.devices.traits.StartStop	
					action.devices.traits.TemperatureSetting	
					action.devices.traits.Toggles
				*/			  					
		
			  	switch ($cmd_array['generic_type']) {
			  	case "LIGHT_STATE"	:
			  		$traitsReturn[] = "action.devices.traits.OnOff";
			  		break;
			  	case "LIGHT_SLIDER" :
			  		$traitsReturn[] = "action.devices.traits.Brightness";
					break;
			  	case "LIGHT_COLOR" :
					$traitsReturn[] = "action.devices.traits.ColorSpectrum";
					break;
				case "LIGHT_COLOR_TEMP" :
					$traitsReturn[] = "action.devices.traits.ColorTemperature";
			  		$attributesReturn[]	= '';
			  		break;
			  	case "FLAP_STATE" :
			  		$traitsReturn[] = "action.devices.traits.OnOff";
			  		$attributesReturn[]	= '';
			  		break;
			  	default:
			  		//$traitsReturn[] = $cmd_array['generic_type'];
			  	}
			  	
			  					
				//$cmds_array[] = $cmd_array;
		  		$i++;
		  	}
		}
		  if ($i > 1){
			return array("traits" => $traitsReturn, "attributes" => $attributesReturn);
		}
	
		return [];
	}
	
	public static function discovery_multi($cmds) {
		$array_final = array();
		$tableData = apiai::PluginMultiInEqLogic();
		foreach ($cmds as &$cmd) {
			if(in_array($cmd['generic_type'], $tableData)){
				$keys = array_keys(array_column($cmds,'eqLogic_id'), $cmd['eqLogic_id']);
				$trueKeys = array_keys(array_column($cmds,'generic_type'), $cmd['generic_type']);
				//if(count($keys) > 1 && count($trueKeys) > 1){
					$result =  array_intersect($keys, $trueKeys);
					if(count($result) > 1){
						$array_final = array_merge_recursive($array_final, $result);
					}
				//}
				
			}
		}
		$dif = array();
		$array_cmd_multi = array();
		foreach ($array_final as &$array_fi){
			if(!in_array($array_fi, $dif)){
				array_push($dif, $array_fi);
				array_push($array_cmd_multi,$array_fi);
			}
		}
		
		return $array_cmd_multi;
	}
	
	/*
	public static function change_cmdAndeqLogic($cmds, $eqLogics){
		$plage_cmd = apiai::discovery_multi($cmds);
		$eqLogic_array = array();
		$nbr_cmd = count($plage_cmd);
		//log::add('apiai', 'debug', 'plage cmd > '.json_encode($plage_cmd).' // nombre > '.$nbr_cmd);
		if($nbr_cmd != 0){
			$i = 0;
			while ($i < $nbr_cmd){
				log::add('apiai', 'info', 'nbr cmd > '.$i.' // id > '.$plage_cmd[$i]);
				$eqLogic_id = $cmds[$plage_cmd[$i]]['eqLogic_id'];
				$name_cmd = $cmds[$plage_cmd[$i]]['name'];
				foreach ($eqLogics as &$eqLogic){
					if($eqLogic['id'] == $eqLogic_id){
						$eqLogic_name = $eqLogic['name'].' / '.$name_cmd;
					}
				}
				log::add('apiai', 'debug', 'nouveau nom > '.$eqLogic_name);
				$id = $cmds[$plage_cmd[$i]]['id'];
				$new_eqLogic_id = '999'.$eqLogic_id.''.$id;
				$cmds[$plage_cmd[$i]]['eqLogic_id'] = $new_eqLogic_id;
				$keys = array_keys(array_column($cmds,'eqLogic_id'),$eqLogic_id);
				$nbr_keys = count($keys);
				$j = 0;
				while($j < $nbr_keys){
					if($cmds[$keys[$j]]['value'] == $cmds[$plage_cmd[$i]]['id'] && $cmds[$keys[$j]]['type'] == 'action'){
						log::add('apiai', 'debug', 'Changement de l\'action > '.$cmds[$keys[$j]]['id']);
						$cmds[$keys[$j]]['eqLogic_id'] = $new_eqLogic_id;
					}
					$j++;
				}
				array_push($eqLogic_array,array($eqLogic_id, $new_eqLogic_id, $eqLogic_name));
				$i++;
			}
			
			$column_eqlogic = array_column($eqLogics,'id');
			foreach ($eqLogic_array as &$eqlogic_array_one) {
				$keys = array_keys($column_eqlogic, $eqlogic_array_one[0]);
				$new_eqLogic = $eqLogics[$keys[0]];
				$new_eqLogic['id'] = $eqlogic_array_one[1];
				$new_eqLogic['names'] = array("name" => $eqlogic_array_one[2]);
				array_push($eqLogics, $new_eqLogic);
			}		
		}
		$new_cmds = array('cmds' => $cmds);
		$new_eqLogic = $eqLogics;
		$news = array($new_cmds, $new_eqLogic);
		return $news;
	}
	
	*/
	
	public static function discovery_object() {
		$all = utils::o2a(object::all());
		$return = array();
		foreach ($all as &$object){
			if (isset($object['display']['sendToApp']) && $object['display']['sendToApp'] == "0") {
				continue;
			} else {
				unset($object['configuration'],$object['display']['tagColor'], $object['display']['tagTextColor']);
				$return[]=$object;
			}
		}
		return $return;
	}
	 
	 
	 
	/*
	**
	** Gestion des scénarios (scenes pour Google)
	**
    */
	public static function discovery_scenario() {
		$all = utils::o2a(scenario::all());
		$return = array();
		foreach ($all as &$scenario){
			if (isset($scenario['display']['sendToApp']) && $scenario['display']['sendToApp'] == "0") {
				continue;
			} else {
				$scenario['id'] = "S" . $scenario['id'];
				$scenario['name'] = array ("name" => $scenario['display']['name'] != '' ? $scenario['display']['name'] : "Scene " . $scenario['id']);
				
				$scenario['type'] = "action.devices.types.SCENE";
				$scenario['traits'] = array("action.devices.traits.Scene");
				$scenario['willReportState'] = false;
				$scenario['attributes'] = array("sceneReversible" => true);
				$scenario['customData'] = array('isActive' => $scenario['isActive'],
												'group' => $scenario['group'],
												'mode' => $scenario['mode'],
												'schedule' => $scenario['schedule'],
												'timeout' => $scenario['timeout']);
				
				unset($scenario['object_id'], $scenario['isVisible'], $scenario['state'], $scenario['lastLaunch'],$scenario['isActive'],$scenario['group'],$scenario['mode'],
					  $scenario['schedule'], $scenario['scenarioElement'],$scenario['trigger'],$scenario['timeout'],$scenario['description'],$scenario['configuration'],
					  $scenario['display']['name']);
				if ($scenario['display'] == [] || $scenario['display']['icon'] == ''){
					unset($scenario['display']);
				}
				$return[] = $scenario;
			}	
		}
		return $return;
	}
	
	public static function discovery_message() {
		$all = utils::o2a(message::all());
		$return = array();
		foreach ($all as &$message){
				$return[]=$message;	
		}
		return $return;
	}
	
	public static function discovery_plan() {
		$all = utils::o2a(planHeader::all());
		$return = array();
		foreach ($all as &$plan){
          		if ($plan['image'] !== undefined){
					unset($plan['image']);
				}
          		$return[]=$plan;
		}
		return $return;
	}


	public static function delete_object_eqlogic_null($objectsATraiter,$eqlogicsATraiter){
		$retour = array();
		foreach ($objectsATraiter as &$objectATraiter){
			$id_object = $objectATraiter['id'];
			foreach ($eqlogicsATraiter as &$eqlogicATraiter){
				if ($id_object == $eqlogicATraiter['object_id']){
					array_push($retour,$objectATraiter);
					break;
				}
			}
		}
		return $retour;
	}

	/**************************************************************************************/
	/*                                                                                    */
	/*                                 Pour les notifications                             */
	/*                                                                                    */
	/**************************************************************************************/
	
	public static function jsonPublish($os,$titre,$message,$badge = 'null'){
		if($os == 'ios'){
			if($badge == 'null'){
				$publish = '{"default": "Erreur de texte de notification","APNS": "{\"aps\":{\"alert\": {\"title\":\"'.$titre.'\",\"body\":\"'.$message.'\"},\"badge\":'.$badge.',\"sound\":\"silence.caf\"},\"date\":\"'.date("Y-m-d H:i:s").'\"}"}';
			}else{
				$publish = '{"default": "test", "APNS": "{\"aps\":{\"alert\": {\"title\":\"'.$titre.'\",\"body\":\"'.$message.'\"},\"sound\":\"silence.caf\"},\"date\":\"'.date("Y-m-d H:i:s").'\"}"}';
			}
		}else if($os == 'android'){
			$publish = '{"default": "Erreur de texte de notification", "GCM": "{ \"data\": {\"notificationId\":\"'.rand(3, 5).'\",\"title\":\"'.$titre.'\",\"text\":\"'.$message.'\",\"vibrate\":\"true\",\"lights\":\"true\" } }"}';
		}else if($os == 'microsoft'){
			
		}
		return $publish;
	}
	
	public static function notification($arn,$os,$titre,$message,$badge = 'null'){
		log::add('apiai', 'debug', 'notification en cours !');
		if($badge == 'null'){
			$publish = apiai::jsonPublish($os,$titre,$message,$badge);
		}else{
			$publish = apiai::jsonPublish($os,$titre,$message);
		}
		log::add('apiai', 'debug', 'JSON envoyé : '.$publish);
		$post = [
			'id' => '1',
			'type' => $os,
			'arn' => $arn,
			'publish' => $publish 
		];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,apiai::LienAWS());
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$post);            
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec ($ch);
        curl_close ($ch);
        log::add('apiai', 'debug', 'notification resultat > '.$server_output);
	}
	
	/**************************************************************************************/
	/*                                                                                    */
	/*                         Permet de creer l'ID Unique du téléphone                   */
	/*                                                                                    */
	/**************************************************************************************/
	
	public function postInsert() {
		$key = config::genKey(32);
		$this->setLogicalId($key);
		$this->save();
	}
	
	public function postSave() {
		$this->crea_cmd();
	}
    
    function crea_cmd() {
    	$cmd = $this->getCmd(null, 'notif');
        if (!is_object($cmd)) {
			$cmd = new apiaiCmd();
			$cmd->setLogicalId('notif');
			$cmd->setName(__('Notif', __FILE__));
			$cmd->setIsVisible(1);
			$cmd->setDisplay('generic_type', 'GENERIC_ACTION');
		}
		$cmd->setOrder(0);
		$cmd->setType('action');
		$cmd->setSubType('message');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

    }
	

	/*     * *********************Méthodes d'instance************************* */

	/*     * **********************Getteur Setteur*************************** */
}

class apiaiCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	/*
											 * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
											public function dontRemoveCmd() {
											return true;
											}
											 */

	public function execute($_options = array()) {
		$eqLogic = $this->getEqLogic();
		$arn = $eqLogic->getConfiguration('notificationArn', null);
		$os = $eqLogic->getConfiguration('type_apiai', null);
        if ($this->getType() != 'action') {
			return;
		}
		log::add('apiai', 'debug', 'Notif > '.json_encode($_options).' / '.$eqLogic->getId().' / '.$this->getLogicalId(), 'config');
		if($this->getLogicalId() == 'notif') {
			log::add('apiai', 'debug', 'Commande de notification ', 'config');
			if($arn != null && $os != null){
				apiai::notification($arn,$os,$_options['title'],$_options['message']);
				log::add('apiai', 'debug', 'Action : Envoi d\'une configuration ', 'config');
			}else{
				log::add('apiai', 'debug', 'ARN non configuré ', 'config');	
			}
		};
	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
