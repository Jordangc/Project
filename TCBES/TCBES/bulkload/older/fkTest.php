<?php

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
	$values['specimen.specimen_code'] = 'test';
	$values['organization.abbr4_organization'] = 'test';
	
	//echo upwardsDependencies('event', 'event_code');
	
	echo downwardsDependencies('specimen', 'abbr4_organization', upwardsDependencies('specimen', 'abbr4_organization'));
	echo downwardsDependencies('event', 'event_code', upwardsDependencies('event', 'event_code'));
	
	//echo downwardsDependencies('specimen', 'sex_code', $PLACE_HOLDER);
	
/*	
	function fillTable($table, $column, $match){
		global $fk_down, $columns, $connDBEHSP;
		$query = sprintf($columns, $table);
		$result = mysql_query($query, $connDBEHSP);
		while($row = mysql_fetch_assoc($result)){
			$down_query = sprintf($fk_down, $table, $row['Field']);
			$down_result = mysql_query($down_query, $connDBEHSP);
			if($down_row = mysql_fetch_assoc($down_result)){
				echo "<br />" . $table . "." . $row['Field'] . " has more deps. ";
				
				// 'select * from ' . $down_row['REFERENCED_TABLE_NAME'] ' where ' . $down_row['REFERENCED_COLUMN_NAME'] . ' = ' . 
				
				
				
				//$match = upwardsDependencies($table, $row['Field']);
				//$dep_match = downwardsDependencies($table, $row['Field']);
				//'select * from ' . $down_row['REFERENCED_TABLE_NAME'] ' where ' . $down_row['REFERENCED_COLUMN_NAME'] . ' = ' . 
				// check if the downward dep has this attribute in its table
				// if not do downwards deps
				// then make the insert query for this attribute because now it will
				// downwardDependencies returns the match for this 
			}
			else{
				// base case of this recursive mess get the upward thing for
				// upwardsDependencies($table, $row['Field']);
				if($column == $row['field']){
					// value to insert is $match
				}
				echo "<br />" . $table . "." . $row['Field'] . " has no more deps with value " . upwardsDependencies($table, $row['Field']);
			}
		}
	}*/
	
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
		return $match;
	}
	
?>