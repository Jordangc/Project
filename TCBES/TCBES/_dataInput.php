<?php

	$DEBUG = false;

	session_start();

$insert[] = "INSERT INTO specimen(specimen_code,event_code, catalog_code, cabinet_code, box_code, section_code, abbr4_organization, sex_code, status_code) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)";
$insert[] = "INSERT INTO collection(catalog_code,event_code, short_scientific_name, number_sample, number_male, number_female, collection_note)VALUES (%s,%s,%s,%s,%s,%s,%s)";
$insert[] = "INSERT INTO collector(abbr3_collector, first_name, last_name, middle_name, email, passwd, abbr4_organization, user_name)VALUES (%s,%s,%s,%s,%s,%s,%s,%s)";
$insert[] = "INSERT INTO reserve(reserve_name, permission_type)VALUES (%s,%s)";
$insert[] = "INSERT INTO location(island_name, reserve_name, locality_name, location_detail_code)VALUES (%s,%s,%s,%s)";
$insert[] = "INSERT INTO event(event_code, abbr3_collector, locality_name, reserve_name, island_name, date, event_note)VALUES (%s,%s,%s,%s,%s,%s,%s)";
$insert[] = "INSERT INTO container(cabinet_code, box_code, section_code,abbr4_organization)VALUES(%s,%s,%s,%s)";
$insert[] = "INSERT INTO organization(abbr4_organization, organization_name, organization_street, organization_city, organization_state, organization_zip, organization_note, organization_country)VALUES (%s,%s,%s,%s,%s,%s,%s,%s)";
$insert[] = "INSERT INTO sex(sex_code, sex_type)VALUES (%s,%s)";
$insert[] = "INSERT INTO preservation_status(status_code, status_description)VALUES (%s,%s)";
$insert[] = "INSERT INTO dna(extraction_code, section_code, box_code, cabinet_code, abbr4_organization, sequence, sequence_name, primer_name)VALUES (%s,%s,%s,%s,%s,%s,%s,%s)";
$insert[] = "INSERT INTO dna_media(extraction_code, media_id)VALUES (%s,%s)";
$insert[] = "INSERT INTO event_collector(event_code, abbr3_collector)VALUES (%s,%s)";
$insert[] = "INSERT INTO island(island_name)VALUES (%s)";
$insert[] = "INSERT INTO life(short_scientific_name, scientific_name)VALUES (%s,%s)";
$insert[] = "INSERT INTO life_media(short_scientific_name, media_id)VALUES (%s,%s)";
$insert[] = "INSERT INTO life_taxonomy(rank_type, rank_name, short_scientific_name)VALUES (%s,%s,%s)";
$insert[] = "INSERT INTO locality(locality_name)VALUES (%s)";
$insert[] = "INSERT INTO location_details(location_detail_code, north_degree, west_degree, elevation_foot, waypoint, utm_zone, utm_band, utm_northing, utm_easting)VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)";
$insert[] = "INSERT INTO media(media_id, file_format, directory_path, file_name, media_note)VALUES (%s,%s,%s,%s,%s)";
$insert[] = "INSERT INTO media_meta(meta_item, media_id, meta_numeric_value)VALUES (%s,%s,%s)";
$insert[] = "INSERT INTO media_type(file_format, media_type_note)VALUES (%s,%s)";
$insert[] = "INSERT INTO meta(meta_item)VALUES (%s)";
$insert[] = "INSERT INTO rank_type(rank_type)VALUES (%s)";
$insert[] = "INSERT INTO reserve_permission(permission_type)VALUES (%s)";
$insert[] = "INSERT INTO specimen_dna(extraction_code, specimen_code)VALUES (%s,%s)";
$insert[] = "INSERT INTO specimen_location_code(specimen_code, location_detail_code)VALUES (%s,%s)";
$insert[] = "INSERT INTO specimen_media(media_id, specimen_code)VALUES (%s,%s)";
$insert[] = "INSERT INTO taxonomy(rank_name, rank_name_2, mandatory)VALUES (%s,%s,%s)";
$insert[] = "INSERT INTO taxonomy_rank(rank_name, rank_type)VALUES (%s,%s)";
$insert[] = "INSERT INTO upload_column_names(abbr3_collector, xml_name, attribute_name, name_type)VALUES (%s,%s,%s,%s)";


	/*DB Connection Strings*/
	$hostname_connDBEHSP = '10.42.42.245:58924';
	$database_connDBEHSP = 'TCBES';
	$username_connDBEHSP = 'TCBESadmin';
	$password_connDBEHSP = 'TCBESpassword';

	$BULKLOAD_START = 'index.php';
	$NEXT_URL = 'step2.php';
	$LOGIN_PAGE = '../dbinterface';

	$PLACE_HOLDER = '[PH]';
	
	$fk_down = "select REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
					 from information_schema.KEY_COLUMN_USAGE where TABLE_NAME = '%s' and COLUMN_NAME = '%s' and REFERENCED_TABLE_NAME is not null;";

	$columns = "describe %s";
	
	$fk_up = "select TABLE_NAME, COLUMN_NAME
					 from information_schema.KEY_COLUMN_USAGE where REFERENCED_TABLE_NAME = '%s' and REFERENCED_COLUMN_NAME = '%s'";
	
	$connDBEHSP = mysql_connect($hostname_connDBEHSP, $username_connDBEHSP, $password_connDBEHSP) or trigger_error(mysql_error(),E_USER_ERROR);
	mysql_select_db($database_connDBEHSP, $connDBEHSP);

	$values['specimen.event_code'] = 'test';
	//$values['specimen.specimen_code'] = 'test';
	//$values['organization.abbr4_organization'] = 'test';
	$values['[PH]'] = "[PH";
	

	recursiveFail('specimen.event_code', $connDBEHSP);

	
	
	
	function recursiveFail($column_to_insert){
		global $insert, $columns, $values, $fk_down, $connDBEHSP, $PLACE_HOLDER;
		$table_and_column = explode('.', $column_to_insert);
		$column_query = sprintf($columns, $table_and_column[0]);
		$column_result = mysql_query($column_query, $connDBEHSP);
		
		while($table_column = mysql_fetch_assoc($column_result)){
		
			$data_to_insert[$table_column['Field']] = downwardsDependencies($table_and_column[0], $table_column['Field'], 
										upwardsDependencies($table_and_column[0], $table_column['Field']));	
			if($DEBUG) echo "<br /> " . $data_to_insert[$table_column['Field']];
		}
		
		// build the insert query
		foreach($insert as $individual_insert){
			if (strpos($individual_insert, 'INTO ' . $table_and_column[0] . '(') === false or  strpos($individual_insert, $table_and_column[1]) === false){
				continue;
			}
			else{
				foreach(array_keys($data_to_insert) as $field){
					if($temp_pos = strpos($individual_insert, $field))
						$positions[$field] = $temp_pos;
				}
				asort($positions);
				if($DEBUG) print_r($positions);
				$to_eval = 'return sprintf( $individual_insert';
				foreach(array_keys($positions) as $position){
					$type = getDataTypes($table_and_column[0]. '.' . $position, $connDBEHSP);
					if($type['type'] == 'int'){
						if($data_to_insert[$position] == $PLACE_HOLDER){
							$to_eval .= ",1";
						}
					}
					if($type['type'] == 'decimal'){
						if($data_to_insert[$position] == $PLACE_HOLDER){
							$to_eval .= ",1.0";
						}
					}
					else{
						$to_eval .= ",\"'" . $values[$data_to_insert[$position]] . "'\"";
					}
				}
				$to_eval .= ');';
				if($DEBUG) echo '<br />' . $to_eval;
				$insert_query = eval($to_eval);
				
			}
		}
		
		
		// do the query if not result
		if($DEBUG) echo '<br />DEBUG ' . $insert_query;
		$insert_result = mysql_query($insert_query, $connDBEHSP);
		
		if(!$insert_result){
			if($DEBUG) echo '<br />on the way in database error' . mysql_error($connDBEHSP);
			// go down to the next level
			foreach(array_keys($data_to_insert) as $the_column){
			
				$go_down = sprintf($fk_down, $table_and_column[0], $the_column);
				$down_result = mysql_query($go_down, $connDBEHSP);
				
				while($table_column = mysql_fetch_assoc($down_result)){
					recursiveFail($table_column['REFERENCED_TABLE_NAME'] . '.' . $table_column['REFERENCED_COLUMN_NAME']);
				}
			}
			$insert_result = mysql_query($insert_query, $connDBEHSP);
			if($DEBUG) echo'<br /> on the way out db error' . mysql_error($connDBEHSP);
		}
	}
		
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
	
	function getDataTypes($matches, $connDBEHSP){
			$table_column = explode('.', $matches);
			$result = mysql_query('describe ' . $table_column[0] . ' ' . $table_column[1]);
			while($description = mysql_fetch_assoc($result)){
				$info = explode('(',$description['Type']);
				$dataTypes['type'] = $info[0];
				$length = explode(')', $info[1]);
				$dataTypes['length'] = $length[0];
			}
			mysql_free_result($result);
		return $dataTypes;
	}
	
	
	function upwardsDependencies($table, $column){
		global $fk_up, $values, $connDBEHSP, $PLACE_HOLDER;
		$query = sprintf($fk_up, $table, $column);
		$result = mysql_query($query, $connDBEHSP);
		foreach(array_keys($values) as $attribute){
			if($table . '.' . $column == $attribute){
				return $attribute;
			}
		}
		while($row = mysql_fetch_assoc($result)){
			$match = upwardsDependencies($row['TABLE_NAME'], $row['COLUMN_NAME']);
			if($match != $PLACE_HOLDER)
				return $match;
		}
		mysql_free_result($result);
		return $PLACE_HOLDER;
	}

	function downwardsDependencies($table, $column, $match){
		global $fk_down, $values, $connDBEHSP, $PLACE_HOLDER;
		$query = sprintf($fk_down, $table, $column);
		$result = mysql_query($query, $connDBEHSP);
		if($row = mysql_fetch_assoc($result)){
			if($match == $PLACE_HOLDER){
				foreach(array_keys($values) as $attribute){
					if($row['REFERENCED_TABLE_NAME'] . '.' . $row['REFERENCED_COLUMN_NAME'] == $attribute){
						$match = $attribute;
					}
				}
			}
			$match = downwardsDependencies($row['REFERENCED_TABLE_NAME'], $row['REFERENCED_COLUMN_NAME'], $match);
		}
		mysql_free_result($result);
		return $match;
	}
?>