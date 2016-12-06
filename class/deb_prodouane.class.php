<?php

class TDebProdouane extends TObjetStd {
	
	static $TType = array(
							'introduction'=>'Introduction'
							,'expedition'=>'Expédition'
						);
	
	function __construct(&$ATMdb) {
		
		$this->ATMdb = $ATMdb;
		$this->errors = array();
		parent::set_table(MAIN_DB_PREFIX.'deb_prodouane');
		parent::add_champs('numero_declaration,entity','type=entier;');
		parent::add_champs('type_declaration,periode,mode','type=chaine;');
		parent::add_champs('content_xml','type=text;');
		parent::start();
		parent::_init_vars();
		
	}
	
	
	/**
	 * @param $mode O pour création, R pour régénération (apparemment toujours 0 dans la cadre des échanges XML selon la doc)
	 * @param $type introduction ou expedition
	 */
	function getXML($mode='O', $type='introduction', $periode_reference='') {

		global $db, $conf, $mysoc;
		
		/**************Construction de quelques variables********************/
		$party_id = substr(strtr($mysoc->tva_intra, array(' '=>'')), 0, 4).$mysoc->idprof2;
		$declarant = substr($mysoc->managers, 0, 14);
		$id_declaration = self::getNumeroDeclaration($this->numero_declaration);
		/********************************************************************/
		
		/**************Construction du fichier XML***************************/
		$e = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" standalone="yes"?><INSTAT></INSTAT>');
		
		$enveloppe = $e->addChild('Envelope');
		$enveloppe->addChild('envelopeId', $conf->global->EXPORT_PRO_DEB_NUM_AGREMENT);
		$date_time = $enveloppe->addChild('DateTime');
		$date_time->addChild('date', date('Y-m-d'));
		$date_time->addChild('time', date('H:i:s'));
		$party = $enveloppe->addChild('Party');
		$party->addAttribute('partType', $conf->global->EXPORT_PRO_DEB_TYPE_ACTEUR);
		$party->addAttribute('partyRole', $conf->global->EXPORT_PRO_DEB_ROLE_ACTEUR);
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
		
		/**************Ajout des lignes de factures**************************/
		$res = self::addItemsFact($declaration, $type, $periode_reference);
		/********************************************************************/
		
		$this->errors = array_unique($this->errors);

		if(!empty($res)) return $e->asXML();
		else return 0;
		
	}
	
	function addItemsFact(&$declaration, $type, $periode_reference) {
		
		global $db, $conf;
		
		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		
		$sql = $this->getSQLFactLines($type, $periode_reference);
		
		$resql = $db->query($sql);
		
		if($resql) {
			$i=1;
			
			if(empty($resql->num_rows)) {
				$this->errors[] = 'Aucune donnée pour cette période';
				return 0;
			}
			
			if($conf->global->EXPORT_PRO_DEB_CATEG_FRAISDEPORT > 0) {
				$categ_fraisdeport = new Categorie($db);
				$categ_fraisdeport->fetch($conf->global->EXPORT_PRO_DEB_CATEG_FRAISDEPORT);
				$TLinesFraisDePort = array();
			}
			
			while($res = $db->fetch_object($resql)) {
				
				if(empty($res->fk_pays)) {
					// On n'arrête pas la boucle car on veut savoir quels sont tous les tiers qui n'ont pas de pays renseigné
					$this->errors[] = 'Pays non renseigné pour le tiers <a href="'.dol_buildpath('/societe/soc.php',1).'?socid='.$res->id_client.'">'.$res->nom.'</a>';
				} else {
					if($conf->global->EXPORT_PRO_DEB_CATEG_FRAISDEPORT > 0 && $categ_fraisdeport->containsObject('product', $res->id_prod)) {
						$TLinesFraisDePort[] = $res;
					} else $this->addItemXMl($declaration, $res);
				}
				
				$i++;
				
			}
			
			if(!empty($TLinesFraisDePort)) $this->addItemFraisDePort($declaration, $TLinesFraisDePort, $type, $categ_fraisdeport);

			if(count($this->errors) > 0) return 0;
			
		}
		
		return 1;
		
	}

	function getSQLFactLines($type, $periode_reference) {
		
		global $mysoc;
		
		if($type=='expedition') {
			$sql = 'SELECT f.facnumber, f.total as total_ht';
			$table = 'facture';
			$table_extraf = 'facture_extrafields';
			$tabledet = 'facturedet';
			$field_link = 'fk_facture';
		}
		else { // Introduction
			$sql = 'SELECT f.ref_supplier as facnumber, f.total_ht';
			$table = 'facture_fourn';
			$table_extraf = 'facture_fourn_extrafields';
			$tabledet = 'facture_fourn_det';
			$field_link = 'fk_facture_fourn';
		}
		$sql.= ', l.fk_product, l.qty
				, p.weight, p.rowid as id_prod, p.customcode
				, s.rowid as id_client, s.nom, s.zip, s.fk_pays, s.tva_intra
				, c.code
				, ext.mode_transport
				FROM '.MAIN_DB_PREFIX.$tabledet.' l
				INNER JOIN '.MAIN_DB_PREFIX.$table.' f ON (f.rowid = l.'.$field_link.')
				LEFT JOIN '.MAIN_DB_PREFIX.$table_extraf.' ext ON (ext.fk_object = f.rowid)
				INNER JOIN '.MAIN_DB_PREFIX.'product p ON (p.rowid = l.fk_product)
				INNER JOIN '.MAIN_DB_PREFIX.'societe s ON (s.rowid = f.fk_soc)
				LEFT JOIN '.MAIN_DB_PREFIX.'c_country c ON (c.rowid = s.fk_pays)
				WHERE f.fk_statut > 0
				AND (s.fk_pays <> '.$mysoc->country_id.' OR s.fk_pays IS NULL)
				AND f.datef BETWEEN "'.$periode_reference.'-01" AND "'.$periode_reference.'-'.date('t').'"';
		
		return $sql;
		
	}
	
	function addItemXMl(&$declaration, &$res, $code_douane_spe='') {
		
		$item = $declaration->addChild('Item');
		$item->addChild('ItemNumber', $i);
		$cn8 = $item->addChild('CN8');
		if(empty($code_douane_spe)) $code_douane = $res->customcode;
		else $code_douane = $code_douane_spe;
		$cn8->addChild('CN8Code', $code_douane);
		if(!empty($res->tva_intra)) $item->addChild('partnerId', $res->tva_intra);
		$item->addChild('MSConsDestCode', $res->code); // code iso pays client
		$item->addChild('netMass', $res->weight * $res->qty); // Poids du produit
		$item->addChild('quantityInSU', $res->qty); // Quantité de produit dans la ligne
		$item->addChild('invoicedAmount', round($res->total_ht)); // Montant total ht de la facture (entier attendu)
		$item->addChild('invoicedNumber', $res->facnumber); // Numéro facture
		$item->addChild('statisticalProcedureCode', '11');
		$nature_of_transaction = $item->addChild('NatureOfTransaction');
		$nature_of_transaction->addChild('natureOfTransactionACode', 1);
		$nature_of_transaction->addChild('natureOfTransactionBCode', 1);
		$item->addChild('modeOfTransportCode', $res->mode_transport);
		$item->addChild('regionCode', substr($res->zip, 0, 2));
		
	}

	/**
	 * Cette fonction ajoute un item en récupérant le code douane du produit ayant le plus haut montant dans la facture
	 */
	function addItemFraisDePort(&$declaration, &$TLinesFraisDePort, $type, &$categ_fraisdeport) {
		
		global $db;
		
		if($type=='expedition') {
			$table = 'facture';
			$tabledet = 'facturedet';
			$field_link = 'fk_facture';
			$more_sql = 'f.facnumber';
		}
		else { // Introduction
			$table = 'facture_fourn';
			$tabledet = 'facture_fourn_det';
			$field_link = 'fk_facture_fourn';
			$more_sql = 'f.ref_supplier as facnumber';
		}
		
		foreach($TLinesFraisDePort as $res) {
			
			$sql = 'SELECT p.customcode
					FROM '.MAIN_DB_PREFIX.$tabledet.' d
					INNER JOIN '.MAIN_DB_PREFIX.$table.' f ON (f.rowid = d.'.$field_link.')
					INNER JOIN '.MAIN_DB_PREFIX.'product p ON (p.rowid = d.fk_product)
					WHERE d.fk_product IS NOT NULL
					AND '.$more_sql.' = "'.$res->facnumber.'"
					AND d.total_ht =
					(
						SELECT MAX(d.total_ht)
						FROM '.MAIN_DB_PREFIX.$tabledet.' d
						INNER JOIN '.MAIN_DB_PREFIX.$table.' f ON (f.rowid = d.'.$field_link.')
						WHERE d.fk_product IS NOT NULL
						AND '.$more_sql.' = "'.$res->facnumber.'"
						AND d.fk_product NOT IN
						(
							SELECT fk_product
							FROM '.MAIN_DB_PREFIX.'categorie_product
							WHERE fk_categorie = '.$categ_fraisdeport->id.'
						) 
					)';
			
			$resql = $db->query($sql);
			$ress = $db->fetch_object($resql);
			
			$this->addItemXMl($declaration, $res, $ress->customcode);
			
		}
		
	}

	function getNextNumeroDeclaration() {
		
		global $db;
		$resql = $db->query('SELECT MAX(numero_declaration) as max_numero_declaration FROM '.$this->get_table());
		if($resql) $res = $db->fetch_object($resql);
		
		return ($res->max_numero_declaration + 1);
		
	}

	static function getNumeroDeclaration($numero) {
	
		return str_pad($numero, 6, 0, STR_PAD_LEFT);
		
	}

	function generateXMLFile() {
		
		$name = $this->periode.'.xml';
		$fname = sys_get_temp_dir().'/'.$name;
		$f = fopen($fname, 'w+');
		fwrite($f, $this->content_xml);
		fclose($f);
		
		header('Content-Description: File Transfer');
	    header('Content-Type: application/xml');
	    header('Content-Disposition: attachment; filename="'.$name.'"');
	    header('Expires: 0');
	    header('Cache-Control: must-revalidate');
	    header('Pragma: public');
	    header('Content-Length: ' . filesize($fname));
	    readfile($fname);
		exit;
		
	}
	
}
