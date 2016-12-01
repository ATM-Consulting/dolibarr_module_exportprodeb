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
	
	function getXML() {

		global $db, $conf;

		$e = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><INSTAT></INSTAT>');
		$enveloppe = $e->addChild('Envelope');
		
		// balise envelopeId
		$enveloppe->addChild('envelopeId', $conf->global->EXPORT_PRO_DEB_NUM_AGREMENT);
		
		// Balise DateTime
		$date_time = $enveloppe->addChild('DateTime');
		$date_time->addChild('date', date('Y-m-d'));
		$date_time->addChild('time', date('H:i:s'));
		
		// Balise Party
		$party = $enveloppe->addChild('Party partType="'.$conf->global->EXPORT_PRO_DEB_TYPE_ACTEUR.'" partyRole="'.$conf->global->EXPORT_PRO_DEB_ROLE_ACTEUR.'"');
		$party->addChild('partyId', 'coucou');
		$party->addChild('partyName', 'coucou');
		
		// Balise software
		$enveloppe->addChild('softwareUsed', 'Dolibarr');
		/*$e->addChild('Name', utf8_encode($product['label']));
		$price = $e->addChild('Price');
		$price->addChild('Value', $TPrices['price_patient']);
		$price1 = $e->addChild('Price1');
		$price1->addChild('Value', $TPrices['price_pro']);
		$e->addChild('TaxRate', $TPrices['tva']);
		$s = $e->addChild('QuantityInStock');
		$s->addChild('Value', $stock);*/
		
		var_dump($e->asXML());
		
	}
	
}
