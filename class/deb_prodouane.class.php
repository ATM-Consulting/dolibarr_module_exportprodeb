<?php

class TDebProdouane extends TObjetStd {
	
	function __construct(&$ATMdb) {
		
		$this->ATMdb = $ATMdb;
		$this->errors = array();
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
		
		self::getItems($declaration, $type);
		
		pre($e->asXML(), true);
		
	}
	
	private function getItems(&$declaration, $type) {
		
		if($type == 'expedition') return $this->addItemsFactClient($declaration, true);
		else return $this->addItemsFactFourn();
		
	}
	
	function addItemsFactFourn() {
		
		global $db;
		
		
	}
	
	function addItemsFactClient(&$declaration, $test=false) {
		
		global $db, $mysoc;
		
		$sql = 'SELECT l.fk_product, l.qty, p.weight, f.total, s.rowid as id_client, s.nom, s.fk_pays, s.tva_intra
				FROM '.MAIN_DB_PREFIX.'facturedet l
				INNER JOIN '.MAIN_DB_PREFIX.'facture f ON (f.rowid = l.fk_facture)
				INNER JOIN '.MAIN_DB_PREFIX.'product p ON (p.rowid = l.fk_product)
				INNER JOIN '.MAIN_DB_PREFIX.'societe s ON (s.rowid = f.fk_soc)
				WHERE s.fk_pays <> '.$mysoc->country_id;
		
		if($test) $sql.= ' AND f.datef >= "'.date('Y-m-d').'"'; // TODO prédiode
		
		$resql = $db->query($sql);
		
		if($resql) {
			$i=0;
			while($res = $db->fetch_object($resql)) {
				if(empty($res->fk_pays)) {
					//TODO langs
					$this->errors[] = 'Pays non renseigné pour le tiers <a href="'.dol_buildpath('/societe/soc.php',1).'?socid='.$res->id_client.'">'.$res->nom.'</a>';
					return 0;
				}
				$item = $declaration->addChild('Item');
				$TData['MSConsDestCode'] = ''; // code iso pays client
				$TData['netMass'] = ''; // Poids du produit
				$TData['quantityInSU'] = $res->qty; // Quantité de produit dans la ligne
				$TData['invoicedAmount'] = $res->total; // Montant total ht de la facture (entier attendu)
				$i++;
			}
			if(!empty($this->errors)) $this->errors = array_unique($this->errors);
		}
		
	}
	
	function getLastIdDeclaration() {
		
		
		
	}
	
}
