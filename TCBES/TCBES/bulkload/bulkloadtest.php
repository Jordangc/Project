<?php
	$BULKLOAD_START = 'bulkload.html';
	$LOGIN_PAGE = '../dbinterface';

	session_start();
	require_once('theme/_header.php');
	
	if($_FILES["file"]["type"] != 'text/xml'){
		?>
		not an xml document (must be 2003 XML format) 
		<a href="<?php
			echo $BULKLOAD_START;
		?>">go back </a>
		<?php
	}

	else{
		// REMOVE AFTER TESTING
		echo "Upload: " . $_FILES["file"]["name"] . "<br />";
		echo "Type: " . $_FILES["file"]["type"] . "<br />";
		echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
		echo "Stored in: " . $_FILES["file"]["tmp_name"];
		  
		$_SESSION["sheetFile"] = '../tmpFiles' . $_FILES["file"]["tmp_name"];
		  
		move_uploaded_file($_FILES["file"]["tmp_name"], $_SESSION["sheetFile"]);
  
		$workbook = DOMDocument::load($_SESSION["sheetFile"]);
		//$workbook = $_SESSION["sheetFile"]->getElementsbyTagName('Column');		
	}
	
	if(!isset($_SESSION['uid'])){
		echo '<br /> DEBUG you are not logged in. <a href="'. $LOGIN_PAGE .'"> login </a> <br />';
	}
	
	$_SESSION['matches'] = getColumnCandidates();
	findMatches($workbook, $_SESSION['matches'], $_SESSION['positions'], $_SESSION['database_columns']);
	getDataTypes($_SESSION['database_columns']);
	
	require_once('theme/_footer.php');
	

	
	/**
	*	Grabs the columnconfig.xml file then if there are user specific names grabs those
	*	returns two dimensional array of the column values to look for
	*/
	function getColumnCandidates(){
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
			/*DB Connection Strings*/
			$hostname_connDBEHSP = '10.42.42.245:58924';
			$database_connDBEHSP = 'TCBES';
			$username_connDBEHSP = 'TCBESadmin';
			$password_connDBEHSP = 'TCBESpassword';
			$connDBEHSP = mysql_connect($hostname_connDBEHSP, $username_connDBEHSP, $password_connDBEHSP) or trigger_error(mysql_error(),E_USER_ERROR);
			mysql_select_db($database_connDBEHSP, $connDBEHSP);
			$column_query = "select xml_name, attribute_name, name_type from upload_column_names where abbr3_collector = '" . $_SESSION['uid'] . "'";
			$colum_results = mysql_query($column_query, $connDBEHSP) or die(mysql_error($connDBEHSP));
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
	* NEEDS stuff
	*/
	
	function getDataTypes($matches){
		/*DB Connection Strings*/
		$hostname_connDBEHSP = '10.42.42.245:58924';
		$database_connDBEHSP = 'TCBES';
		$username_connDBEHSP = 'TCBESadmin';
		$password_connDBEHSP = 'TCBESpassword';
		$connDBEHSP = mysql_connect($hostname_connDBEHSP, $username_connDBEHSP, $password_connDBEHSP) or trigger_error(mysql_error(),E_USER_ERROR);
		mysql_select_db($database_connDBEHSP, $connDBEHSP);

		foreach($matches['attr'] as $attribute){
			$table_column = explode('.', $attribute);
			$result = mysql_query('describe ' . $table_column[0] . ' ' . $table_column[1]);
			while($description = mysql_fetch_assoc($result)){
				#echo ' <br /> DEBUG ' . $attribute . ' datatype: ' . $description['Type'];
			}
		}
	}
	
	function findMatches($plant, $matches, &$data_position, &$into_database){
		$rows = $plant->getElementsbyTagName("Row");
		$cells = $rows->item(0)->getElementsbyTagName("Cell");
		foreach($cells as $cell){
			$datas[] = $cell->getElementsbyTagName("Data")->item(0)->textContent;
		}
		$position = 0;
		foreach($datas as $data){
			$flag = 0;
			$data = strtolower($data);
			foreach(array_keys($matches) as $category){
				foreach(array_keys($matches[$category]) as $xml_name){
					if($data == $xml_name)
					{
						$into_database[$category][$xml_name] = $matches[$category][$xml_name];
						$flag = 1;
						$data_position[$into_database[$category][$xml_name]] = $position;
						break(2);
					}
				}
			}
			if($flag != 1)
				$no_match[] = $data;
			$position++;
		}
		echo "Matches: <br />";
		echo "<table border='1' cellpadding='5' cellspacing='0'> \n";
		echo "<tr><td><b>Your spreadsheet</b></td><td><b>Database match</b></td></tr>";
		if(isset($into_database)){
			foreach(array_keys($into_database) as $category){
				foreach(array_keys($into_database[$category]) as $successful_match){
					echo "<tr><td>$successful_match</td><td>" . $into_database[$category][$successful_match] . "</td></tr>";
				}
			}
		}
		echo "</table>";
		echo "These did not match any known data type in the database. <br />";
		/*if(isset($no_match)){
			foreach($no_match as $unsuccessful_match){
				#echo "$unsuccessful_match <br />";
			}
		}*/
		NoMatchCorrections($data_position, $matches, $no_match, $into_database);
	}
	
	function NoMatchCorrections($data_position, $matches, $no_match, $into_database){
		$counter = 0;
		$db_attribute[] = '';
		$already_there = 0;
		foreach(array_keys($matches) as $category){
			foreach(array_keys($matches[$category]) as $xml_name){
				foreach($db_attribute as $actual_db){
					if($matches[$category][$actual_db] == $matches[$category][$xml_name]){
						$already_there = 1;
						break;
					}
					else
						$already_there = 0;
				}
				if($already_there == 0){
					$db_attribute[$counter] = $xml_name;
					$counter++;
				}
			}
		}
		echo "<table border='1' cellpadding='5' cellspacing='0'> \n";
		echo "<tr><td><b>Your spreadsheet</b></td><td><b>Possible matches</b></td></tr>";
		foreach($no_match as $attribute){
			echo "<tr> \n";
			echo "<td> $attribute:  </td>";
			echo "<td><select name='$attribute'> \n";
			foreach($db_attribute as $actual_db){
				echo "<option value=$actual_db>$actual_db</option>";
			}
			echo "<option value=none>none</option>";
			echo "</select><br />";
			echo "</td> \n";
			echo "</tr> \n";
		}	
		echo "</table> \n";
	}
	/*
	function taxonomyHandler(){
		$configfile = DOMDocument::load("columnconfig.xml");
		$taxonomy = $configfile->getElementsbyTagName('taxonomy')->item(0); //->getElementsbyTagName('column');
		$taxonomy_db = explode('.',$taxonomy->getElementsbyTagName('attributename')->textContent);
		
		$taxonomy_value = '';
		foreach($taxonomy->getElementsbyTagName('taxon') as $taxon){
			$taxonomy_value .= substr($values_to_isert[$taxon->textContent], 0, $taxon->getAttribute('length');
		}
		
		// incorrect we need to have an array 
		$insert_taxonomy = "insert into " . $taxonomy_db[0] . " (" . $taxonomy_db[1] . ") values (" . 
	} */
?> 