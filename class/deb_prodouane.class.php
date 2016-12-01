<?php

class TDebProdouane extends TObjetStd {
	
	function __construct(&$ATMdb) {
		
		$this->ATMdb = $ATMdb;
		parent::set_table(MAIN_DB_PREFIX.'deb_prodouane');
		parent::add_champs('numero_declaration,entity','type=entier;');
		parent::add_champs('periode','type=chaine;');
		parent::add_champs('content_xml','type=text;');
		parent::start();
		parent::_init_vars();
		
	}
	
	
	/**
	 * @param $mode O pour création, R pour régénération
	 * @param $type introduction ou expedition
	 */
	function getXML($mode='O', $type='introduction', $periode_reference='') {

		global $db, $conf, $mysoc;
		
		/**************Construction de quelques variables********************/
		$party_id = substr(strtr($mysoc->tva_intra, array(' '=>'')), 0, 4).$mysoc->idprof2;
		$declarant = substr($mysoc->managers, 0, 14);
		$id_declaration = self::getLastIdDeclaration();
		/********************************************************************/
		
		/**************Construction du fichier XML***************************/
		$e = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><INSTAT></INSTAT>');
		
		$enveloppe = $e->addChild('Envelope');
		$enveloppe->addChild('envelopeId', $conf->global->EXPORT_PRO_DEB_NUM_AGREMENT);
		$date_time = $enveloppe->addChild('DateTime');
		$date_time->addChild('date', date('Y-m-d'));
		$date_time->addChild('time', date('H:i:s'));
		$party = $enveloppe->addChild('Party partType="'.$conf->global->EXPORT_PRO_DEB_TYPE_ACTEUR.'" partyRole="'.$conf->global->EXPORT_PRO_DEB_ROLE_ACTEUR.'"');
		$party->addChild('partyId', $party_id);
		$party->addChild('partyName', $declarant);
		$enveloppe->addChild('softwareUsed', 'Dolibarr');
		$declaration = $enveloppe->addChild('Declaration');
		$declaration->addChild('declarationId', $id_declaration);
		$declaration->addChild('referencePeriod', $periode_reference);
		if($conf->global->EXPORT_PRO_DEB_TYPE_ACTEUR === 'PSI') $psiId = $party_id;
		else $psiId = 'NA';
		$declaration->addChild('PSIId', $psiId);
		$function = $declaration->addChild('Function');
		$functionCode = $function->addChild('functionCode', $mode);
		$declaration->addChild('declarationTypeCode', $conf->global->{'EXPORT_PRO_DEB_NIV_OBLIGATION_'.strtoupper($type)});
		$declaration->addChild('flowCode', ($type == 'introduction' ? 'A' : 'D'));
		$declaration->addChild('currencyCode', $conf->global->MAIN_MONNAIE);
		/********************************************************************/
		
		self::getItems($declaration);
		
		pre($e->asXML(), true);
		
	}
	
	private function getItems(&$declaration) {
		
		
		
	}
	
	function getLastIdDeclaration() {
		
		
		
	}
	
}
