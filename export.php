<?php

require './config.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
dol_include_once('/exportprodeb/class/deb_prodouane.class.php');

$action = GETPOST('action');

$ATMdb = new TPDOdb;
$ATMform = new TFormCore;
$formother = new FormOther($db);
$year = GETPOST('year');
$month = GETPOST('month');
$type_declaration = GETPOST('type');

switch($action) {
	
	case 'export':
		export_xml($type_declaration, $year, $month);
	default:
		print_form();
		break;

}



function print_form() {
	
	global $langs, $ATMform, $formother, $year, $month, $type_declaration;
	
	$langs->load('exportprodeb@exportprodeb');
	$langs->load('main');
	
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
	$TabMonth = array();
	for($i=1;$i<=12;$i++) $TabMonth[$i] = $langs->trans('Month'.str_pad($i, 2, 0, STR_PAD_LEFT));
	print $ATMform->combo('','month', $TabMonth, empty($month) ? date('m') : $month);
	print $formother->selectyear(empty($year) ? date('Y') : $year,'year',0, 20, 5);
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>';
	print 'Type de déclaration';
	print '</td>';
	print '<td>';
	print $ATMform->combo('','type', array('introduction'=>'Introduction', 'expedition'=>'Expédition'), $type_declaration);
	print '</td>';
	print '</tr>';
	
	print '</table>';
	
	print '<div class="tabsAction">';
	print '<input class="butAction" type="SUBMIT" name="subFormExport" value="Exporter XML" />';
	print '</div>';
	
	print '</form>';
	
}

function export_xml($type_declaration, $period_year, $period_month) {
	
	global $ATMdb, $conf;
	
	$obj = new TDebProdouane($ATMdb);
	$obj->entity = $conf->entity;
	$obj->mode = 'O';
	$obj->periode = $period_year.'-'.$period_month;
	$obj->type_declaration = $type_declaration;
	$obj->numero_declaration = $obj->getNextNumeroDeclaration();
	$obj->content_xml = $obj->getXML('O', $type, $period_year.'-'.$period_month);
	if(empty($obj->errors)) {
		$obj->save($ATMdb);
		$obj->generateXMLFile();
	}
	else setEventMessage($obj->errors, 'warnings');
	
}

llxFooter();
