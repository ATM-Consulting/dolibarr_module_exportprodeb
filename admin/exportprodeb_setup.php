<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/exportprodeb.php
 * 	\ingroup	exportprodeb
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */

// Libraries
require '../config.php';
require_once '../lib/exportprodeb.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Translations
$langs->load("exportprodeb@exportprodeb");

// Access control
if (! $user->admin) {
    accessforbidden();
}

$action=__get('action','');

if($action=='save') {
	
	foreach($_REQUEST['TParamProDeb'] as $name=>$param) {
		
		dolibarr_set_const($db, $name, $param);

	}
	
}

llxHeader('',"Paramétrage de l'export proDEB",'');

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre("Paramétrage de l'export proDEB",$linkback,'setup');

$form=new TFormCore;

showParameters($form);

function showParameters(&$form) {
	global $db,$conf,$langs;
	
	$langs->load('exportprodeb@exportprodeb');
	
	$html=new Form($db);
	
	?><form action="<?php echo $_SERVER['PHP_SELF'] ?>" name="save" method="POST">
		<input type="hidden" name="action" value="save" />
	<table width="100%" class="noborder" style="background-color: #fff;">
		<tr class="liste_titre">
			<td colspan="2"><?php echo $langs->trans('Parameters') ?></td>
		</tr>
		
		<tr>
			<td><?php echo $langs->trans('EXPORT_PRO_DEB_NUM_AGREMENT') ?></td><td><?php echo $form->texte('','TParamProDeb[EXPORT_PRO_DEB_NUM_AGREMENT]',$conf->global->EXPORT_PRO_DEB_NUM_AGREMENT,30,255); ?></td>				
		</tr>
		
		<tr>
			<td><?php echo $langs->trans('EXPORT_PRO_DEB_TYPE_ACTEUR') ?></td><td><?php echo $form->combo('','TParamProDeb[EXPORT_PRO_DEB_TYPE_ACTEUR]', array(''=>'', 'PSI'=>'Déclarant pour son compte', 'TDP'=>'Tiers déclarant'), $conf->global->EXPORT_PRO_DEB_TYPE_ACTEUR); ?></td>				
		</tr>
		
		<tr>
			<td><?php echo $langs->trans('EXPORT_PRO_DEB_ROLE_ACTEUR') ?></td><td><?php echo $form->combo('','TParamProDeb[EXPORT_PRO_DEB_ROLE_ACTEUR]', array(''=>'', 'sender'=>'Emetteur', 'PSI'=>'Déclarant'), $conf->global->EXPORT_PRO_DEB_ROLE_ACTEUR); ?></td>				
		</tr>
		
		<tr>
			<td><?php echo $langs->trans('EXPORT_PRO_DEB_NIV_OBLIGATION_INTRODUCTION') ?></td><td><?php echo $form->combo('','TParamProDeb[EXPORT_PRO_DEB_NIV_OBLIGATION_INTRODUCTION]', array(0=>'', 1=>'Seuil de 460 000 €', 2=>'En dessous de 460 000 €'), $conf->global->EXPORT_PRO_DEB_NIV_OBLIGATION_INTRODUCTION); ?></td>
		</tr>
		
		<tr>
			<td><?php echo $langs->trans('EXPORT_PRO_DEB_NIV_OBLIGATION_EXPEDITION') ?></td><td><?php echo $form->combo('','TParamProDeb[EXPORT_PRO_DEB_NIV_OBLIGATION_EXPEDITION]', array(0=>'', 3=>'Seuil de 460 000 €', 4=>'En dessous de 460 000 €'), $conf->global->EXPORT_PRO_DEB_NIV_OBLIGATION_EXPEDITION); ?></td>
		</tr>
		
		
	</table>
	<p align="right">
		
		<input type="submit" name="bt_save" value="<?php echo $langs->trans('Save') ?>" /> 
		
	</p>
	
	</form>
	
	
	<br /><br />
	<?php
}
?>

<table width="100%" class="noborder">
	<tr class="liste_titre">
		<td>A propos</td>
		<td align="center">&nbsp;</td>
		</tr>
		<tr class="impair">
			<td valign="top">Module développé par </td>
			<td align="center">
				<a href="http://www.atm-consulting.fr/" target="_blank">ATM Consulting</a>
			</td>
		</td>
	</tr>
</table>
<?php

llxFooter();

$db->close();