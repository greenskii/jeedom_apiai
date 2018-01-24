<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'apiai');
$eqLogics = eqLogic::byType('apiai');
?>

<div class="row row-overflow">
  <div class="col-lg-2 col-md-3 col-sm-4">
    <div class="bs-sidebar">
      <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
        <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un apiai}}</a>
        <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
        <?php
foreach ($eqLogics as $eqLogic) {
	$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
	echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"  style="' . $opacity . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
}
?>
     </ul>
   </div>
 </div>
 
 
    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
    <legend>{{ApiAI}}</legend>
  <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
  <div class="eqLogicThumbnailContainer">
      <!--div class="cursor eqLogicAction" data-action="add" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
        <i class="fa fa-plus-circle" style="font-size : 6em;color:#94ca02;"></i>
        <br>
        <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02">{{Ajouter}}</span>
    </div-->
      <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
    	<i class="fa fa-wrench" style="font-size : 6em;color:#767676;"></i>
    	<br>
    	<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676">{{Configuration}}</span>
    	<br /><br />
	  </div>
  </div>
<br />
<br />

<legend><i class="icon maison-modern13"></i>  {{Types Génériques par Pièce}}</legend>
<div class="eqLogicThumbnailContainer">
	<?php
		$allObject = object::buildTree(null, false);
		foreach ($allObject as $object) {
			$opacity = '';
			if ($object->getDisplay('sendToApp', 1) == 0) {
				$opacity = 'opacity:0.3;';
			}
			echo '<div class="objectDisplayCard cursor" data-object_id="' . $object->getId() . '" onclick="clickobject(\''. $object->getId(). '\')" style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;'.$opacity.'">';
			echo "<center>";
			echo str_replace('></i>', ' style="font-size : 6em;color:#767676;"></i>', $object->getDisplay('icon', '<i class="fa fa-lemon-o"></i>'));
			echo "</center>";
			echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $object->getName() . '</center></span>';
			echo '</div>';
		}
	?>
</div>
</div>

<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
  <form class="form-horizontal">
    <fieldset>
      <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}  <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i></legend>
      <div class="form-group">
        <label class="col-sm-2 control-label">{{Nom de l'équipement}}</label>
        <div class="col-sm-3">
          <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
          <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-2 control-label" >Objet parent</label>
        <div class="col-sm-3">
            <select class="eqLogicAttr form-control" data-l1key="object_id">
                 <option value="">Aucun</option>
                 <?php
foreach (object::all() as $object) {
	echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
}
?>
            </select>
        </div>
     </div>
      <div class="form-group">
        <label class="col-sm-2 control-label" >{{Activer}}</label>
        <div class="col-sm-9">
         <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-label-text="{{Activer}}" data-l1key="isEnable" checked/>
         <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-label-text="{{Visible}}" data-l1key="isVisible" checked/>
       </div>
     </div>
     <div class="form-group">
     <label class="col-sm-2 control-label">{{Page ID}}</label>
      <div class="col-sm-3">
        <input type="text" class="eqLogicAttr form-control" data-l1key="logicalId" placeholder="{{Page ID}}"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-lg-2 control-label">{{URL de retour}}</label>
      <div class="col-lg-9">
        <span><?php echo network::getNetworkAccess('external') . '/plugins/apiai/core/php/jeeApiai.php';?></span>
  </div>
    </div>
    <div class="form-group">
      <label class="col-lg-2 control-label">{{Verify Token}}</label>
      <div class="col-lg-9">
        <span><?php echo jeedom::getApiKey('apiai');?></span>
      </div>
    </div>
    <div class="form-group">
      <label class="col-lg-2 control-label">{{App secret}}</label>
      <div class="col-lg-9">
        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="app_secret" placeholder="{{App secret}}"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-lg-2 control-label">{{Facebook API Graph Access Token}}</label>
      <div class="col-lg-9">
        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="access_token" placeholder="{{Access token}}"/>
      </div>
    </div>
	<div class="form-group">
      <label class="col-lg-2 control-label">{{Créer les nouveaux contacts}}</label>
      <div class="col-lg-9">
        <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="isAccepting" checked/>{{Activer}}</label>
      </div>
    </div>    
  </fieldset>
</form>
<p></p>
<legend>{{Commandes}}</legend>
<a class="btn btn-success btn-sm cmdAction" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter une commande}}</a><br/><br/>
<table id="table_cmd" class="table table-bordered table-condensed">
  <thead>
    <tr>
      <th>{{Nom}}</th><th>{{Facebook User ID}}</th><th>{{Jeedom username}}</th><th>{{Autorisations}}</th><th>{{Options}}</th>
    </tr>
  </thead>
  <tbody>
  </tbody>
</table>

<form class="form-horizontal">
  <fieldset>
    <div class="form-actions">
      <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
      <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
    </div>
  </fieldset>
</form>

</div>
</div>

<?php include_file('desktop', 'apiai', 'js', 'apiai');?>
<?php include_file('core', 'plugin.template', 'js');?>

<br />
<br />