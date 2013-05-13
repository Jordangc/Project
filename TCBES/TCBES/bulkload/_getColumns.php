<?php

	/**
	*	Grabs the columnconfig.xml file then if there are user specific names grabs those
	*	returns two dimensional array of the column values to look for
	*/
	function getColumnCandidates($connDBEHSP){
		// first load the xml configuration
		$configfile = DOMDocument::load("columnconfig.xml");
		$columns = $configfile->getElementsbyTagName('config')->item(0)->getElementsbyTagName('column');
		
		foreach($columns as $column){
			foreach($column->getElementsbyTagName('matches') as $match){
				// ATTRIBUTE MATCHES
				if($column->getElementsbyTagName('attributename')->item(0)){
					$matches['attr'][$match->textContent] = $column->getElementsbyTagName('attributename')->item(0)->textContent;
				}
				// TAXON MATCHES
				if($column->getElementsbyTagName('taxon')->item(0)){
					$matches['taxon'][$match->textContent] = $column->getElementsbyTagName('taxon')->item(0)->textContent;
				}
			}
		}
		
		// then if logged in load the user settings from the DB
		if(isset($_SESSION['uid'])){
			$column_query = "select xml_name, attribute_name, name_type from upload_column_names where abbr3_collector = '" . $_SESSION['uid'] . "'";
			$column_results = mysql_query($column_query, $connDBEHSP) or die(mysql_error($connDBEHSP));
			while ($row = mysql_fetch_assoc($column_results)){
				// is a taxon 
				if($row['name_type'] == 'taxon') {
					$matches['taxon'][$row['xml_name']] = $row['attribute_name'];
				}
				// is a db_name
				else if($row['name_type'] == 'column') {
					$matches['attr'][$row['xml_name']] = $row['attribute_name'];
				}
			}
			mysql_free_result($column_results);
		}
		return $matches;
	}
	
	/**
	* NEEDS to return an array that has the types and the lengths
	*/
	function getDataTypes($matches){
		global $connDBEHSP;
		foreach($matches['attr'] as $attribute){
			$table_column = explode('.', $attribute);
			$result = mysql_query('describe ' . $table_column[0] . ' ' . $table_column[1]);
			while($description = mysql_fetch_assoc($result)){
				$info = explode('(',$description['Type']);
				$dataTypes[$attribute]['type'] = $info[0];
				$length = explode(')', $info[1]);
				$dataTypes[$attribute]['length'] = $length[0];
			}
			mysql_free_result($result);
		}
		return $dataTypes;
	}
	
	/**
	* 
	*/
	// function typeChecker()
		
	/*function taxonomyHandler(){
		$configfile = DOMDocument::load("columnconfig.xml");
		$taxonomy = $configfile->getElementsbyTagName('taxonomy')->item(0); //->getElementsbyTagName('column');
		$taxonomy_db = explode('.',$taxonomy->getElementsbyTagName('attributename')->textContent);
		
		$taxonomy_value = '';
		foreach($taxonomy->getElementsbyTagName('taxon') as $taxon){
			$taxonomy_value .= substr($values_to_isert[$taxon->textContent], 0, $taxon->getAttribute('length');
		}
		
		// incorrect we need to have an array 
		// $insert_taxonomy = "insert into " . $taxonomy_db[0] . " (" . $taxonomy_db[1] . ") values (" . 
	} */
?>