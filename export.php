<?php

require './config.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
dol_include_once('/exportprodeb/class/deb_prodouane.class.php');

$ATMdb = new TPDOdb;
$ATMform = new TFormCore;
$formother = new FormOther($db);

$obj = new TDebProdouane($ATMdb);
//$res = $obj->getXML('O', 'expedition');
$res = $obj->getXML('O', 'introduction');

switch($action) {
	
	default:
	case 'view':
		print_form();
		break;
	
}

//if(!empty($obj->errors)) setEventMessage(implode('<br />', $obj->errors));

/*$f = fopen('/var/www/test.xml', 'w+');
fwrite($f,$res );*/



function print_form() {
	
	global $langs, $ATMform, $formother;
	
	$langs->load('exportprodeb@exportprodeb');
	
	llxHeader();
	print_fiche_titre($langs->trans('exportprodebTitle'));
	dol_fiche_head();

	print '<form action="'.$_SERVER['PHP_SELF'].'" name="save" method="POST">';
	print '<input type="hidden" name="action" value="export" />';
	
	print '<table width="100%" class="noborder" style="background-color: #fff;">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">';
	print 'Paramètres de l\'export';
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>';
	print 'Période d\'analyse';
	print '</td>';
	print '<td>';
	print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.date('m').'">';
	print $formother->selectyear(date('Y'),'year',0, 20, 5);
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>';
	print 'Type de données';
	print '</td>';
	print '<td>';
	print $ATMform->combo('','type', array(''=>'', 'introduction'=>'Introduction', 'expedition'=>'Expédition'), $conf->global->EXPORT_PRO_DEB_TYPE_ACTEUR);
	print '</td>';
	print '</tr>';
	
	print '</table>';
	
	print '<div class="tabsAction">';
	print '<input class="butAction" type="SUBMIT" name="subFormExport" value="Exporter XML" />';
	print '</div>';
	
	print '</form>';
	
}

llxFooter();