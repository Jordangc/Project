<?php

require_once("Excell2003toMysql.php");

class TCBESBulkload extends Excell2003toMysql{
		
	/**
	*	The taxonomy handler should be different 
	*	What needs to be done is overriding the get rows method and building that $row_items array
	*   where the item are tacked into the one entry for what taxonomy matches
	*/
	
	function taxonomyHandler($values_to_insert){
		$configfile = DOMDocument::load("columnconfig.xml");
		$taxonomy = $configfile->getElementsbyTagName('taxonomy')->item(0); //->getElementsbyTagName('column');
		$taxonomy_db = explode('.',$taxonomy->getElementsbyTagName('attributename')->textContent);
		
		$taxonomy_value = '';
		foreach($taxonomy->getElementsbyTagName('taxon') as $taxon){
			$taxonomy_value .= substr($values_to_insert[$taxon->textContent], 0, $taxon->getAttribute('length'));
		}
		return $taxonomy_value;
	}
}

?>