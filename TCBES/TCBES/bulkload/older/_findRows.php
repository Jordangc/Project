<?php

	function findMatches($plant, $matches, &$data_position, &$into_database, &$xml_position, $next_url){
		$rows = $plant->getElementsbyTagName("Row");
		foreach ($rows as $row){
			if(!isset($done_once)){
				$done_once = true;
			}
			else{
				
				$cells = $row->getElementsbyTagName("Cell");
				foreach($cells as $cell){
					$datas[] = $cell->getElementsbyTagName("Data")->item(0)->textContent;
				}
				$position = 0;
				foreach($datas as $data){
					
					
					/*$xml_position[str_replace(' ', '_', trim($data))] = $position;
					$flag = 0;
					foreach(array_keys($matches) as $category){
						foreach(array_keys($matches[$category]) as $xml_name){
							if(strtolower($data) == $xml_name)
							{
								$into_database[$category][$xml_name] = $matches[$category][$xml_name];
								$flag = 1;
								$data_position[$position] = $into_database[$category][$xml_name];
								break(2);
							}
						}
					}
					if($flag != 1)
						$no_match[] = $data;
					$position++; */
				}
				
			}
		}
	}
	
	function NoMatchCorrections($data_position, $matches, $no_match, $into_database, $next_url){
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
		echo '<form action="' . $next_url . '" method="post">';
		echo "<table border='1' cellpadding='5' cellspacing='0'> \n";
		echo "<tr><td><b>Your spreadsheet</b></td><td><b>Possible matches</b></td></tr>";
		foreach($no_match as $attribute){
			echo "<tr> \n";
			echo "<td> $attribute:  </td>";
			echo '<td><select name="' . str_replace(' ', '_', trim($attribute)) . '">' . "\n";
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
		echo '<input type="submit" name="submit" value="Submit" />' . "\n";
		echo '</form>';
	}
	
?>