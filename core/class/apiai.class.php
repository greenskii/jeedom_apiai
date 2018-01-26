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


/*
** Correspondance netre les tyes génériques Jeedom et les traits et attributs Google
** 
** Traits possible:
** https://developers.google.com/actions/smarthome/traits/
** action.devices.traits.Brightness	
** action.devices.traits.CameraStream	
** action.devices.traits.ColorSpectrum	
** action.devices.traits.ColorTemperature	
** action.devices.traits.Dock	
** action.devices.traits.Modes	
** action.devices.traits.OnOff	
** action.devices.traits.RunCycle	
** action.devices.traits.Scene	
** action.devices.traits.StartStop	
** action.devices.traits.TemperatureSetting	
** action.devices.traits.Toggles
**
**
** DEVICE TYPE :
** action.devices.types.CAMERA	
** action.devices.types.DISHWASHER	
** action.devices.types.DRYER	
** action.devices.types.LIGHT	
** action.devices.types.OUTLET	
** action.devices.types.REFRIGERATOR	
** action.devices.types.SCENE	
** action.devices.types.SWITCH	
** action.devices.types.THERMOSTAT	
** action.devices.types.VACUUM	
** action.devices.types.WASHER	
*/
	public static function getGATraitsAndAttributesCorrespondance() {
		$TRAITS_AND_ATTRIBUTES_CORRESPONDANCE = array(
					"LIGHT_STATE" => array("traits" => array("action.devices.traits.OnOff"),
										   "attributes" => null,
										   "type" => "action.devices.types.LIGHT"),
				    "LIGHT_SLIDER" => array("traits" => array("action.devices.traits.Brightness"),
										   "attributes" => null),
				    "LIGHT_COLOR" => array("traits" => array("action.devices.traits.ColorSpectrum"),
										   "attributes" => null),
				    "LIGHT_COLOR_TEMP" => array("traits" => array("action.devices.traits.ColorTemperature"),
										   "attributes" => null),
				    "FLAP_STATE" => array("traits" => array("action.devices.traits.OnOff"),
										   "attributes" => null,
										   "type" => "action.devices.types.SWITCH")
				);
		return $TRAITS_AND_ATTRIBUTES_CORRESPONDANCE;
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
	

/*
** Cherche et liste les eq
*/

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

						$eqLogic_array["willReportState"] = false;
						
						// on récupere les commandes pour alimenter les "traits"
						$traitsAndAttributes = apiai::discovery_cmd($eqLogic);
						$eqLogic_array["traits"] = $traitsAndAttributes["traits"];
						$eqLogic_array["attributes"] = $traitsAndAttributes["attributes"];
						$eqLogic_array["type"] = $traitsAndAttributes['EQtype'];

						$object = $eqLogic->getObject();
						if ($object != null) {
							$eqLogic_array["roomHint"] = $object->getName();	
						}
						
						
						// on supprime les infos qui nous interesse pas 
						unset($eqLogic_array['eqReal_id'],$eqLogic_array['configuration'], $eqLogic_array['specificCapatibilities'],$eqLogic_array['timeout'],
								$eqLogic_array['category'],$eqLogic_array['display'],$eqLogic_array['status'],$eqLogic_array['logicalId'],$eqLogic_array['object_id'],
								$eqLogic_array['eqType_name'],$eqLogic_array['isVisible'],$eqLogic_array['isEnable'], $eqLogic_array['order'], $eqLogic_array['comment']);
								
						$eqLogic_array["name"] = array("name" => $eqLogic_array["name"] );
						
						if ($eqLogic_array["type"] != null) {
							$return[] = $eqLogic_array;
						}
						
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
		$EQtype = null;
	
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
			   $correspondance = apiai::getGATraitsAndAttributesCorrespondance();
		
				if (array_key_exists($cmd_array['generic_type'], $correspondance)) {
					if ($correspondance[$cmd_array['generic_type']]["traits"] != null) 
						foreach($correspondance[$cmd_array['generic_type']]["traits"] as $trait) $traitsReturn[] = $trait;

					if ($correspondance[$cmd_array['generic_type']]["attributes"] != null) 
						foreach($correspondance[$cmd_array['generic_type']]["attributes"] as $attribute) $attributesReturn[] = $attribute;

					if (array_key_exists("type", $correspondance[$cmd_array['generic_type']]) && $correspondance[$cmd_array['generic_type']]['type'] != null)
						$EQtype = $correspondance[$cmd_array['generic_type']]['type'];
				} else {
					log::add('apiai', 'debug', 'Unkown generic command type ' . print_r($cmd_array['generic_type'], true) .'...');
				}

		  		$i++;
		  	}
		}
		  if ($i > 1 && $EQtype != null){
			return array("traits" => $traitsReturn, "attributes" => $attributesReturn, "EQtype" => $EQtype);
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
	
	

	/*
	**
	** Execution Jeedom de la commande
	**
    */
	public static function execute_command($command) {
		
		$return = $command["execution"][0]["command"];
		
		return $return;
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
