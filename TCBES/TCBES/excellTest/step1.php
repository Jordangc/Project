<?php
	require_once("Excell2003toMysql.php");
	
	/*DB Connection Strings*/
	$hostname_connDBEHSP = '10.42.42.245:58924';
	$database_connDBEHSP = 'TCBES';
	$username_connDBEHSP = 'TCBESadmin';
	$password_connDBEHSP = 'TCBESpassword';
	
	
	$BULKLOAD_START = 'index.php';
	$NEXT_URL = 'step2.php';
	$LOGIN_PAGE = '../dbinterface';

	session_start();
	
	// not an xml file
	if($_FILES["file"]["type"] != 'text/xml'){
		?>
		not an xml document (must be 2003 XML format) 
		<a href="<?php
			echo $BULKLOAD_START;
		?>">go back </a>
		<?php
	}

	else{
		
		$connDBEHSP = mysql_connect($hostname_connDBEHSP, $username_connDBEHSP, $password_connDBEHSP) or trigger_error(mysql_error(),E_USER_ERROR);
		mysql_select_db($database_connDBEHSP, $connDBEHSP);
		
		$_SESSION["sheetFile"] = '../tmpFiles' . $_FILES["file"]["tmp_name"];
		  
		move_uploaded_file($_FILES["file"]["tmp_name"], $_SESSION["sheetFile"]);
		
		$converter = new Excell2003toMysql($connDBEHSP, $database_connDBEHSP, $_SESSION["sheetFile"]);
		
		$converter->getColumnCandidates('columnconfig.xml');
		
		$converter->findMatches();
		
		$no_match = $converter->getColumnMisses();
		$matches = $converter->getColumns();
		
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
		
		echo '<form action="' . $NEXT_URL . '" method="post">';
		echo "<table border='1' cellpadding='5' cellspacing='0'> \n";
		echo "<tr><td><b>Your spreadsheet</b></td><td><b>Possible matches</b></td></tr>";
		foreach($no_match as $spot=>$attribute){
			echo "<tr> \n";
			echo "<td> $attribute:  </td>";
			echo '<td><select name="' . $spot . '">' . "\n";
			echo '<option value="none">ignore column</option>';
			foreach($db_attribute as $actual_db){
				foreach(array_keys($matches) as $category){
					if(isset($matches[$category][$actual_db])){
						echo '<option value="' . $matches[$category][$actual_db] . '">' . $actual_db . '</option>';
						break;
					}
				}
			}
			echo "</select><br />";
			echo "</td> \n";
			echo "</tr> \n";
		}	
		echo "</table> \n";
		echo '<input type="hidden" name="position" value="4" /> ';
		echo '<input type="submit" name="submit" value="process first 10 rows" />' . "\n";
		echo '</form>';
		
		
		
		/*
		echo "data_positions <br />";
		print_r($converter->data_positions);

		echo "into_database <br />";
		print_r($converter->into_database);
		
		echo "combinations <br />";
		print_r($converter->combinations);		
		*/
		
		// $converter->getRows(372, 0, 4, $connDBEHSP);
		
		/*
		echo "<br /> rows <br />";
		print_r($converter->row_items);
		*/
		
		/*
		echo "<br />row errors <br />";
		print_r($converter->row_errors); */
		
		// $converter->insertData(100, 4, $connDBEHSP);
	   //$converter->row_items[0]['sex.sex_code'] = "XXXYYY";
	   //$converter->row_items[0]['sex.sex_type'] = "unknown";
	   //$converter->row_items[0]['specimen.event_code'] = "test2";
	   //$converter->row_items[0]['specimen.specimen_code'] = "spect2";
	   // $converter->tryInsertion('specimen.event_code',0);
	
	}
?>