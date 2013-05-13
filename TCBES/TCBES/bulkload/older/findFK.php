<?php
	$fk_down = "select REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
					 from information_schema.KEY_COLUMN_USAGE where TABLE_NAME = '%s' and COLUMN_NAME = '%s';";

	$columns = "describe %s";
	
	$fk_up = "select TABLE_NAME, COLUMN_NAME 
					 from information_schema.KEY_COLUMN_USAGE where REFERENCED_TABLE_NAME = '%s' and REFERENCED_COLUMN_NAME = '%s'";
					
	foreach(array_keys($values) as $attribute){
	
		$table_attribute = explode('.',$attribute);
		$query = sprintf($columns, $table_attribute[0]);
		$result = mysql_query($query);
		
		while($row = mysql_fetch_accoc($result)){
		
			$up_query = sprintf($fk_up, $table, $column);
			$up_result = mysql_query($up_query);
			while($up_row = mysql_fetch_accoc($up_result)){
				foreach(array_keys($values) as $attribute){
					if($up_row['TABLE_NAME'] . '.' . $up_row['COLUMN_NAME'] == $attribute){
						$match = 
					}
				}
				
			}
		}
	}

	function upwardsDependencies($table, $column){
		global $fk_up, $values;
		$up_query = sprintf($fk_up, $table, $column);
		$up_result = mysql_query($up_query);
		while($up_row = mysql_fetch_accoc($up_result)){
			foreach(array_keys($values) as $attribute){
				if($up_row['TABLE_NAME'] . '.' . $up_row['COLUMN_NAME'] == $attribute){
					return $attribute;
				}
			}
			$match = upwardsDependencies($up_row['TABLE_NAME'], $up_row['COLUMN_NAME']);
			if($match != 'fail')
				return $match;
		}
		return 'fail';
	}
	
	
	
	}
					
	
	foreach($attributes as $attribute){
		$table_attribute = explode('.',$attribute);
		$query = sprintf($dependencies, $table_attribute[0], $table_attribute[1]);
		$result = mysql_query($query);
		while($row = mysql_fetch_accoc($result)){
			// recurse. base case is NULL
		}
		
		$query = sprintf($columns, $table_attribute[0]);
		$result = mysql_query($query);
		while($row = mysql_fetch_accoc($result)){
			// recurse. base case is NULL
			foreach($attributes as $attribute){
				if($table_attribute[0] . '.' . $row['Field'] == $attribute){
					// go down this fellow's dependencies and place the value at the bottom
					// then come back up and place it in the upper ones!!!
				}
				else{
					// go down this fellow's dependencies 
				}
			}
		}
	}


?>