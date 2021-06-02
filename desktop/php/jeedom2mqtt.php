<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('jeedom2mqtt');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
	<legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
	<div class="eqLogicThumbnailContainer">
		<div class="cursor eqLogicAction logoSecondary" data-action="add">
			<i class="fas fa-plus-circle"></i>
			<br>
			<span>{{Ajouter}}</span>
		</div>
		<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
			<i class="fas fa-wrench"></i>
			<br>
			<span>{{Configuration}}</span>
		</div>
		<div class="cursor pluginAction logoSecondary" data-action="openLocation" data-location="<?=$plugin->getDocumentation()?>">
			<i class="fas fa-book"></i>
			<br>
			<span>{{Documentation}}</span>
		</div>
		<div class="cursor pluginAction logoSecondary" data-action="openLocation" data-location="https://community.jeedom.com/tags/plugin-<?=$plugin->getId()?>">
			<i class="fas fa-comments"></i>
			<br>
			<span>Community</span>
		</div>
	</div>
	<legend><i class="fas fa-table"></i> {{Mes Jeedom2mqtt}}</legend>
	<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>
	<div class="eqLogicThumbnailContainer">
		<?php
		foreach ($eqLogics as $eqLogic) {
			$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
			echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
			echo '<img src="' . $eqLogic->getImage() . '"/>';
			echo "<br>";
			echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
			echo '</div>';
		}
		?>
	</div>
</div>

<div class="col-xs-12 eqLogic" style="display: none;">
	<div class="input-group pull-right" style="display:inline-flex;">
		<span class="input-group-btn">
			<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
			</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs">  {{Dupliquer}}</span>
			</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
			</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
			</a>
		</span>
	</div>
	<ul class="nav nav-tabs" role="tablist">
		<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
		<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
		<li role="presentation" class="typ-brk"><a href="#brokertab" aria-controls="brokertab" role="tab" data-toggle="tab"><i class="fas fa-rss"></i> {{Broker}}</a></li>
		<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab" id="linkCommand"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
	</ul>
	<div class="tab-content">
		<div role="tabpanel" class="tab-pane active" id="eqlogictab">
			<br/>
			<fieldset>
				<div class="row">
					<div class="col-sm-7">
						<form class="form-horizontal">
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-3">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label" >{{Objet parent}}</label>
								<div class="col-sm-3">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
											$options = '';
											foreach ((jeeObject::buildTree(null, false)) as $object) {
												$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
											}
											echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Catégorie}}</label>
								<div class="col-sm-9">
									<?php
										foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
										echo '</label>';
										}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label"></label>
								<div class="col-sm-9">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable"/>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible"/>{{Visible}}</label>
								</div>
							</div>
							<br/>
						</form>
					</div>
				</div>
			</fieldset>
		</div>
		<div role="tabpanel" class="tab-pane" id="brokertab">
			<br/>
			<fieldset>
				<div class="row">
					<div class="col-sm-7">
						<form class="form-horizontal">
							<legend><i class="fas fa-user-cog"></i> {{Démon}}</legend>
								<div id="div_broker_daemon">
									<table class="table table-bordered">
										<thead>
											<tr>
												<th>{{Configuration}}</th>
												<th>{{Statut}}</th>
												<th>{{(Re)Démarrer}}</th>
												<th>{{Dernier lancement}}</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td class="daemonLaunchable"></td>
												<td class="daemonState"></td>
												<td><a class="btn btn-success btn-sm bt_startDaemon" style="position:relative;top:-5px;"><i class="fa fa-play"></i></a></td>
												<td class="daemonLastLaunch"></td>
											</tr>
										</tbody>
									</table>
								</div>

							<legend><i class="fas fa-user-cog"></i> {{Authentification}}</legend>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Adresse IP}}</label>
								<div class="col-sm-3">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="serverIP" placeholder="{{Saisir l'adresse IP}}">
								</div>
								<label class="col-sm-3 control-label">{{Port}}</label>
								<div class="col-sm-3">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="serverPort" placeholder="1883">
								</div>
							</div>
							<!--<div class="form-group">
								<label class="col-sm-3 control-label help" data-help="{{Pour contacter le serveur en https}}">{{SSL}}</label>
								<div class="col-sm-3">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="enableSSL"/>{{Activer}}</label>
								</div>
								<label class="col-sm-3 control-label help" data-help="{{Seulement valable si SSL est activé}}">{{Validation du certificat SSL}}</label>
								<div class="col-sm-3">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="verifySSL"/>{{Activer}}</label>
								</div>
							</div>-->
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Utilisateur}}</label>
								<div class="col-sm-3">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="user" placeholder="{{Saisir l'utilisateur}}">
								</div>
								<label class="col-sm-3 control-label">{{Mot de passe}}</label>
								<div class="col-sm-3">
									<input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password" placeholder="{{Saisir le mot de passe}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Topic}}</label>
								<div class="col-sm-3">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="topicTemplate" placeholder="">
								</div>
							</div>

							<br/>
							<legend><i class="fas fa-share-alt"></i> {{Envoi}}</legend>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Mode d'envoi}}</label>
								<div class="col-sm-3">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="mode">
										<option value="cron">{{Toutes les minutes}}</option>
										<option value="listener">{{Temps réel}}</option>
									</select>
								</div>
								<label class="col-sm-3 control-label cronSchedule">{{Programmation}}</label>
								<div class="col-sm-3 cronSchedule">
									<div class="input-group">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="cronSchedule" placeholder="* * * * *"/>
										<span class="input-group-btn">
											<a class="btn btn-default cursor jeeHelper" data-helper="cron">
												<i class="fas fa-question-circle"></i>
											</a>
										</span>
									</div>
								</div>
							</div>
							<br/>
						</form>
					</div>
				</div>
			</fieldset>
		</div>
		<div role="tabpanel" class="tab-pane" id="commandtab">
			<br/>
			<fieldset>
				<div class="row">
					<div class="col-sm-7">
						<div class="form-group">
							<center>
							<label class="col-sm-4 control-label">{{Total des commandes sélectionnées}}</label>
							<div class="col-sm-1">
								<span class="label label-default eqLogicAttr" data-l1key="configuration" data-l2key="totalSelectedCmdCount"></span>
							</div>
							</center>
						</div>
					</div>
				</div>
				<table class="table table-bordered table-condensed tablesorter" id="table_jeedom2mqttSelectedCmds">
					<thead>
						<tr>
							<th data-filter="false" data-sorter="checkbox">{{Envoyé}}</th>
							<th>{{Type}}</th>
							<th>{{Nom}}</th>
							<th>{{Plugin}}</th>
							<th><span class="help" data-help="{{Par défaut sera généré par le template de topic}}">{{Topic}}</span></th>
							<th data-sorter="false" data-filter="false">{{Action}}</th>
						</tr>
					</thead>
					<tbody>
					<?php
					$cmds = cmd::all();
					foreach ($cmds as $cmd) {
						if($cmd->getType() != 'info'){
							continue;
						}
						echo '<tr>';
						echo '<td>';
						echo '<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="selectedCmds" data-l3key="'.$cmd->getId().'"/>';
						echo '</td>';
						echo '<td>';
						echo '<span>'.$cmd->getType().' / '.$cmd->getSubType().'</span>';
						echo '</td>';
						echo '<td>';
						echo '<span>'.$cmd->getHumanName().'</span>';
						echo '</td>';
						echo '<td>';
						if(is_object($cmd->getEqLogic())){
							echo '<span>'.$cmd->getEqLogic()->getEqType_name().'</span>';
						}
						echo '</td>';
						echo '<td>';
						echo '<input type="text" class="eqLogicAttr" data-l1key="configuration" data-l2key="topic" data-l3key="'.$cmd->getId().'" placeholder="jeedom/..."/>';
						echo '</td>';
						echo '<td style="width: 100px;">';
						echo '<a class="btn btn-default btn-sm pull-right cursor bt_jeedom2mqttAdvanceCmdConfiguration" data-id="'.$cmd->getId().'" title="{{Configuration de la commande}}"><i class="fas fa-cogs"></i></a>';
						echo '</td>';
						echo '</tr>';
					}
					?>
					</tbody>
				</table>
			</fieldset>
		</div>

	</div>
</div>

<?php include_file('desktop', 'jeedom2mqtt', 'js', 'jeedom2mqtt');?>
<?php include_file('core', 'plugin.template', 'js');?>
