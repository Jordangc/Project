<?php

class Excell2003toMysql{
	

	private $xml_positions;

	/**
	* Contains the 'table'.'column' names indexed by their column position in the xml spreadsheet
	*/
	public $data_positions;	//change back to PRIVATE when finished DEBUGGING
	
	public $into_database;	//change back to PRIVATE when finished DEBUGGING
	
	private $workbook;
	
	private $column_matches;
	
	private $column_misses;
	
	public $row_items;   //change back to PRIVATE when finished DEBUGGING
	
	public $column_info;   //change back to PRIVATE when finished DEBUGGING
	
	public $row_errors;	//change back to PRIVATE when finished DEBUGGING
	
	public $combinations;	//change back to PRIVATE when finished DEBUGGING
	
	private $combo_key;
	
	private $place_holders;
	
	private $insertion_failure;
	
	private $db_connection;
	
	private $inserted;
	
	private $found_values;
	
	private $up_bound;
	
	private $down_bound;
	
	private $primary_keys;
	
	private $db_name;
	
	private $PLACE_HOLDER = '['; 
	
	private $offset  = 60;	   	// was 65 - ascii for letter 'a'
	
	private $base = 31;			// was 26 number of characters in english alphabet	
	
	/**
	*	used for each row that is inserted and then cleared. This prevents repetition
	*/
	private $data_to_insert;
	
	private $fk_down_query_query = "select REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
					 from information_schema.KEY_COLUMN_USAGE where TABLE_NAME = '%s' and COLUMN_NAME = '%s' and REFERENCED_TABLE_NAME is not null;";

	private $column_query = "describe %s";
	
	private $fk_up_query = "select TABLE_NAME, COLUMN_NAME
					 from information_schema.KEY_COLUMN_USAGE where REFERENCED_TABLE_NAME = '%s' and REFERENCED_COLUMN_NAME = '%s';";
					 
	private $ph_mutation_query;
	
	function __construct($connDBEHSP, $db_name, $xmlFile){
		$this->workbook = DOMDocument::load($xmlFile);
		$this->db_connection = $connDBEHSP;
		$this->db_name = $db_name;
		$this->ph_mutation_query = " select %s from %s where %s like '" . $this->PLACE_HOLDER . "%';";
		
		$this->buildColumnInfo();
		$this->buildDependencies();
		$this->buildPrimaryKeys();
		
	}
	
	
	/**
	*	Grabs the columnconfig.xml file then if there are user specific names grabs those
	*	returns two dimensional array of the column values to look for
	*/
	function getColumnCandidates($configFile){
		// first load the xml configuration
		$configfile = DOMDocument::load($configFile);
		$columns = $configfile->getElementsbyTagName('config')->item(0)->getElementsbyTagName('column');
		
		foreach($columns as $column){
			foreach($column->getElementsbyTagName('matches') as $match){
				// ATTRIBUTE MATCHES
				if($column->getElementsbyTagName('attributename')->item(0)){
					$this->column_matches['attr'][$match->textContent] = $column->getElementsbyTagName('attributename')->item(0)->textContent;
				}
				// PART MATCHES
				if($column->getElementsbyTagName('partname')->item(0)){
					$this->column_matches['part'][$match->textContent] = $column->getElementsbyTagName('partname')->item(0)->textContent;
				}
			}
		}
		$combinations = $configfile->getElementsbyTagName('config')->item(0)->getElementsbyTagName('combined');
		foreach($combinations as $combo){
			foreach($combo->getElementsbyTagName('part') as $i=>$part){
				if($combo->getElementsbyTagName('attributename')->item(0)){
					$this->combinations[$combo->getElementsbyTagName('attributename')->item(0)->textContent][$i]['part'] = $part->textContent;
					$this->combinations[$combo->getElementsbyTagName('attributename')->item(0)->textContent][$i]['length'] = $part->getAttribute('length');
					$this->combo_key[$part->textContent] = $combo->getElementsbyTagName('attributename')->item(0)->textContent;
				}
			}
		}
		
	}
	
	function getColumns(){
		return $this->column_matches;
	}

	function findMatches(){
		$rows = $this->workbook->getElementsbyTagName("Row");
		$cells = $rows->item(0)->getElementsbyTagName("Cell");
		foreach($cells as $cell){
			$datas[] = $cell->getElementsbyTagName("Data")->item(0)->textContent;
		}
		$position = 0;
		foreach($datas as $data){
			$this->xml_positions[str_replace(' ', '_', trim($data))] = $position;
			$flag = 0;
			foreach(array_keys($this->column_matches) as $category){
				foreach(array_keys($this->column_matches[$category]) as $xml_name){
					if(strtolower($data) == $xml_name)
					{
						$this->into_database[$category][$xml_name] = $this->column_matches[$category][$xml_name];
						$flag = 1;
						$this->data_positions[$position] = $this->into_database[$category][$xml_name];
						break(2);
					}
				}
			}
			if($flag != 1)
				$this->column_misses[$position] = $data;
			$position++;
		}
	}
	
	function getColumnMisses(){
		return $this->column_misses;
	}
	
	/**
	*	Takes an array that is indexed by the normalized xml spreadsheet column name and contains 
	*	spreadsheet column number as data
	*	
	*/
	function correctColumns($changes){
		foreach(array_keys($changes) as $change_key){
			if(is_numeric($change_key) && $changes[$change_key] != 'none')
				$this->data_positions[$change_key] =  $changes[$change_key];
		}
	}
	
	
	function getRows($number_of_rows, $position, $header_depth, $connDBEHSP){
	
		$this->db_connection = $connDBEHSP;
		
		$empty			= "[NULL]";				 	// Shows where data is not located.
		$tuples	  		= 0;						// Number of rows skipped. Used to skip the headers

		$rows = $this->workbook->getElementsbyTagName("Row");
		for($i = $position; $i < $position + $header_depth + $number_of_rows; $i++){
			$row = $rows->item($i);
		   if($tuples < $header_depth - $position){
				$tuples++;
		   }
		   else{
		   $cells = $row->getElementsbyTagName("Cell");
				foreach(array_keys($this->data_positions) as $data_position){
					if($cells->item($data_position)){
						$cell = $cells->item($data_position);
						$data = $cell->getElementsbyTagName("Data")->item(0)->textContent;
					
						if(!isset($data))
							{$data = $empty;}
						
						$this->row_items[$i][$this->data_positions[$data_position]] = $data;		//store to array
						$this->checkType($data, $this->data_positions[$data_position], $i, $data_position);
					}
				}
			}
		}
	}
	
	function getRowErrors($start, $end){
		for($i = $start; $i < $end; $i++){
			if(isset($this->row_errors[$i])){
				$errors[$i+1] = $this->row_errors[$i];
			}
		}
		return $errors;
	}
	
	
	function buildColumnInfo(){
		$table_result = mysql_query("SELECT TABLE_NAME FROM information_schema.`TABLES` where TABLE_SCHEMA = '" . $this->db_name . "';", $this->db_connection);
		while($tables = mysql_fetch_assoc($table_result)){
			$result = mysql_query('describe '. $tables['TABLE_NAME'], $this->db_connection);
			while($description = mysql_fetch_assoc($result)){
				$info = explode('(',$description['Type']);
				$this->column_info[$tables['TABLE_NAME'].'.'.$description['Field']]['type'] = $info[0];
				$length = explode(')', $info[1]);
				if($info[0] == 'date')
					$this->column_info[$tables['TABLE_NAME'].'.'.$description['Field']]['length'] = 10;
				else
					$this->column_info[$tables['TABLE_NAME'].'.'.$description['Field']]['length'] = $length[0];			
				if(isset($description['Extra']) && $description['Extra'] == 'auto_increment'){
					$this->column_info[$tables['TABLE_NAME'].'.'.$description['Field']]['auto_increment'] = true;
				}
			}
		}
	}
	
	function checkType($data, $table_dot_column, $row, $position){
		if(isset($this->combo_key[$table_dot_column]))
			return;
		switch($this->column_info[$table_dot_column]['type']){
			case 'decimal':
				if(!is_numeric($data)){
					$this->row_errors[$row][$position] = ' Not a decimal ';
				}
				//else( CHECK if THERE is e){
				
		//		}
				break;
			case 'int' :
				if(!is_numeric($data) && substr_count('.', $data) == 0 && substr_count('e', $data) == 0){
					$this->row_errors[$row][$position] = ' Not an integer ';
				}
				break;
			case 'date':
				$info = date_parse($data);
				if(count($info['errors']) > 0)
					$this->row_errors[$row][$position] = ' Not a date ';
				else
					return;
				break;
		}
		
		if(strlen($data) > $this->column_info[$table_dot_column]['length']){
			if (isset($this->row_errors[$row][$position])){
				$this->row_errors[$row][$position] .= ' and longer than ' . $this->column_info[$table_dot_column]['length'] . ' characters';
			}
			else{
				$this->row_errors[$row][$position] .= ' Longer than ' . $this->column_info[$table_dot_column]['length'] . ' characters';
			}
		}
	}
	
	function insertData($number_of_rows, $position, $connDBEHSP){
		$this->db_connection = $connDBEHSP;
		for($i = $position; $i < $position + $number_of_rows; $i++){
			if(!isset($this->row_errors[$i])){
				foreach($this->data_positions as $data_position){
					$this->tryInsertion($data_position, $i);
					foreach($this->data_to_insert as $key=>$value){
						$position = strpos($value, $this->PLACE_HOLDER);
						if($position !== false && $position == 0){
							$this->place_holders[$i][$key] = $value;
						}
					}
					unset($this->data_to_insert);
					unset($this->inserted);
				}
			}
		}
	}
	
	function buildInsert($table ,$row_number, &$inc_field){
	
		$column_query = sprintf($this->column_query, $table);
		$column_result = mysql_query($column_query, $this->db_connection);
		
		$insert_string = 'insert into ' . $table . '(';
		$values_string = 'values (';
		$extra_variable = 0;
		while($table_column = mysql_fetch_assoc($column_result)){
			if(isset($this->column_info[$table.".".$table_column['Field']]['auto_increment']) && $this->column_info[$table.".".$table_column['Field']]['auto_increment'])
				$inc_field = $table.".".$table_column['Field'];
			if($extra_variable == 0){
				$insert_string .= $table_column['Field'];
			}
			else
				$insert_string .= "," . $table_column['Field'];
			if(!isset($this->data_to_insert[$table.".".$table_column['Field']])){
				$this->data_to_insert[$table.".".$table_column['Field']] =
				$this->findDependentValue($row_number, $table, $table_column['Field']);
			}
			if($this->column_info[$table.".".$table_column['Field']]['type'] != 'text'){
				$datum = substr($this->data_to_insert[$table.".".$table_column['Field']], 0, $this->column_info[$table.".".$table_column['Field']]['length']);
			}
			else{
				$datum = $this->data_to_insert[$table.".".$table_column['Field']];
			}
			if($this->column_info[$table.".".$table_column['Field']]['type'] == 'char' || $this->column_info[$table.".".$table_column['Field']]['type'] == 'varchar' 
			  || $this->column_info[$table.".".$table_column['Field']]['type'] == 'text'|| $this->column_info[$table.".".$table_column['Field']]['type'] == 'text'
			  || $this->column_info[$table.".".$table_column['Field']]['type'] == 'date'){
				$datum = "'" . $datum . "'";
			}
			else{
				$position = strpos($datum, $this->PLACE_HOLDER);
				if($position !== false && $position == 0)
					$datum = 0;
			}
	
			// echo "<br /> DATA FOR $table . " . $table_column['Field'] . " is $datum";
			
	
			if($extra_variable == 0){
				$values_string .= $datum;
				$extra_variable = 1;
			}
			else{
				$values_string .= ',' . $datum;
			}
		}
		
		$insert_string .= ') ' . $values_string . ');'; 	
	
		return $insert_string;
	}
	
	function getPlaceHolderInfo($start, $end){
		for($i = $start; $i < $end; $i++){
			if(isset($this->place_holders[$i])){
				$errors[$i+1] = $this->place_holders[$i];
			}
		}
		return $errors;
	}
		
	function tryInsertion($column_to_insert, $row_number){
		if(isset($this->column_matches['part'][$column_to_insert])){
			$temp = $this->combo_key[$column_to_insert];
			$temp = explode('.', $temp);
			$column_to_insert = $temp[0];
		}
		$table_and_column = explode('.', $column_to_insert);
		$inc = false;
		$insert_string = $this->buildInsert($table_and_column[0], $row_number, $inc);
		
		// do the query if not result
		$insert_result = mysql_query($insert_string, $this->db_connection);
		if($inc != false){
			$new_key = mysql_insert_id();
			foreach($this->up_bound[$inc] as $child){
				//echo "<br /> there is an autoincrement it is $inc";
				$this->data_to_insert[$child] = $new_key;
			}
		}
		if(!$insert_result){
			
			// WHEN WE HIT THE BOTTOM AND THERE IS A PRIMARY KEY ERROR
			// WE NEED TO CHOOSE A NEW PLACEHOLDER AND THEN REMEMBER IT
			if(!$this->inserted[$table_and_column[0]]){
				$bottom = true;
				foreach(array_keys($this->data_to_insert) as $data_tab_col){
					$temp = explode('.', $data_tab_col);
					$the_column = $temp[1];
					if(isset($this->down_bound[$table_and_column[0] . '.' . $the_column][0])){
						$bottom = false;
						// echo "<br /> DOING AN INSERT ON " .$this->down_bound[$table_and_column[0] . '.' . $the_column][0];
						$this->tryInsertion($this->down_bound[$table_and_column[0] . '.' . $the_column][0], $row_number);
						// echo "<br /> DONE DOING AN INSERT ON " .$this->down_bound[$table_and_column[0] . '.' . $the_column][0];
					}
				}					
				mysql_query("LOCK TABLES " . $table_and_column[0] . ';', $this->db_connection);
				// check the primary keys to see if any of them start with place holder, if not then we give an error
				$this->mutatePlaceHolder($table_and_column[0], $the_column);
				if(!isset($MUTATE)){
					//echo "KEY CONFLICT";	
				}
				$inc = false;
				$new_query = $this->buildInsert($table_and_column[0], $row_number, $inc);

				// echo "<br/>------Insert String-----<br/>" . $new_query;
				
				$succesful = mysql_query($new_query);
				if($succesful){
					$this->inserted[$table_and_column[0]] = true;
					if($inc != false){
					$new_key = mysql_insert_id();
						foreach($this->up_bound[$inc] as $child){
							//echo "<br /> there is an autoincrement it is $inc";
							$this->data_to_insert[$child] = $new_key;
						}
					}
				}
				else{
					$this->insertion_failure[$row] = 'Identifying values must be unique';
				}

				mysql_query("UNLOCK TABLES;", $this->db_connection);
				$this->inserted[$table_and_column[0]] = true;	// THIS TABLE HAS BEEN INSERTED. CLEAR THIS BEFORE NEXT ROW
			}
		}
		else{
			$this->inserted[$table_and_column[0]] = true;
		}
	}// end
	
	function mutatePlaceHolder($table, $the_column){
		foreach($this->primary_keys[$table] as $key){
			if(!isset($this->down_bound[$table.'.'.$key])){
				$position = strpos($this->data_to_insert[$table . '.' . $key], $this->PLACE_HOLDER);
				if($position !== false && $position == 0){

					$MUTATE = 'MUTATE';
					$last_ph = mysql_query("SELECT * FROM " . $table . " where " . $the_column . " like '" . $this->PLACE_HOLDER . "%' order by " . $the_column . " desc;", $this->db_connection);
					if($the_ph = mysql_fetch_assoc($last_ph)){
						
						$ph_number = substr($the_ph[$key], strlen($this->PLACE_HOLDER));
						
						$new_key = $this->PLACE_HOLDER . $this->autoKey($this->keyToInt($ph_number) + 1, strlen($ph_number));
						$this->data_to_insert[$table . '.' . $key] = $new_key;
						if(isset($this->up_bound[$table . '.' . $key])){
							foreach($this->up_bound[$table . '.' . $key] as $child){
								$this->data_to_insert[$child] = $new_key;
							}
						}
					}
				}
			}
		}
	}
	
	function autoKey($id, $key_size){
	
	   $key = "";
	   
		for( $i = 0; $i < $key_size; $i++){
		  $key = chr(($id / pow($this->base, $i)) % $this->base + $this->offset).$key;
	   }

		  return $key;

		  }// end of autoKey
	/**
	*/
	function keyToInt($k){
	 $num = 0;
	   $key_len = strlen($k);
	   for( $i = 0; $i < $key_len; $i++ ){
		  $num += (ord($k[$key_len - ($i + 1)]) - $this->offset) * pow($this->base, $i); 
	   }
		return $num;
	}	
	
		
	function buildDependencies(){
		$dep_query = "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM `information_schema`.`KEY_COLUMN_USAGE` where REFERENCED_TABLE_NAME is not null;";
		$result = mysql_query($dep_query, $this->db_connection);
		while($row = mysql_fetch_assoc($result)){
			$down_dep[$row['TABLE_NAME'].'.'.$row['COLUMN_NAME']] = $row['REFERENCED_TABLE_NAME'].'.'.$row['REFERENCED_COLUMN_NAME'];
			$up_dep[$row['REFERENCED_TABLE_NAME'].'.'.$row['REFERENCED_COLUMN_NAME']][] = $row['TABLE_NAME'].'.'.$row['COLUMN_NAME'];
		}

		foreach($down_dep as $current_table_dot_column => $dep){
			$table_column_ph = $current_table_dot_column;
			while(is_string($table_column_ph) && array_key_exists($table_column_ph,$down_dep)){
				//echo "<br/> table and column: $table_column_ph";
				$this->down_bound[$current_table_dot_column][] = $down_dep[$table_column_ph];
				$this->up_bound[$down_dep[$table_column_ph]][] = $current_table_dot_column;
				$table_column_ph = $down_dep[$table_column_ph];
			}
		}
	}
	
	function buildPrimaryKeys(){
		$result = mysql_query("SELECT * FROM information_schema.KEY_COLUMN_USAGE Where CONSTRAINT_SCHEMA = '".$this->db_name."' and REFERENCED_COLUMN_NAME is null;", $this->db_connection);
		while($row = mysql_fetch_assoc($result)){
			$this->primary_keys[$row['TABLE_NAME']][] = $row['COLUMN_NAME'];
		}
	}
	
	function findDependentValue($row_number, $table, $column){
			//$this->up_bound 
         //$this->down_bound
		 if(isset($this->combinations[$table.'.'.$column])){

			$combined_value = '';
			foreach($this->combinations[$table.'.'.$column] as $combo){
				if(isset($this->row_items[$row_number][$combo['part']])){
					// echo " value is set and is " . $this->row_items[$row_number][$combo['part']] . ' and the length is '. $combo['length'];
					$combined_value .= substr($this->row_items[$row_number][$combo['part']], 0, $combo['length']);
				}
				else{
					//return $this->PLACE_HOLDER;
				}
			}
			return $combined_value;
		 }
		 
		 if(isset($this->row_items[$row_number][$table . "." . $column])){
			return $this->row_items[$row_number][$table . "." . $column];
		}
         if(isset($this->up_bound[$table . "." . $column])){
            foreach($this->up_bound[$table . "." . $column] as $up_stuff){
               if(isset($this->row_items[$row_number][$up_stuff])&& $this->PLACE_HOLDER != $this->row_items[$row_number][$up_stuff]){
                  return $this->row_items[$row_number][$up_stuff];
               }
			   else if(isset($this->combinations[$up_stuff])){
					$combined_value = '';
					$missing = false;
				 	foreach($this->combinations[$up_stuff] as $combo){
						if(isset($this->row_items[$row_number][$combo['part']])){
							$combined_value .= substr($this->row_items[$row_number][$combo['part']], 0, $combo['length']);
						}
						else{
							$missing = true;
						}
					}
					if(!$missing)
						return $combined_value;
			   }
            }
         }
         
         if(isset($this->down_bound[$table . ".". $column])){
            foreach($this->down_bound[$table . "." . $column] as $down_stuff){
               if(isset($this->row_items[$row_number][$down_stuff]) && $this->PLACE_HOLDER != $this->row_items[$row_number][$down_stuff]){
                  return $this->row_items[$row_number][$down_stuff];
               }
			   else if(isset($this->combinations[$down_stuff])){
					$combined_value = '';
					$missing = false;
				 	foreach($this->combinations[$down_stuff] as $combo){
						if(isset($this->row_items[$row_number][$combo['part']])){
							$combined_value .= substr($this->row_items[$row_number][$combo['part']], 0, $combo['length']);
						}
						else{
							$missing = true;
						}
					}
					if(!$missing)
						return $combined_value;	
				}						
            }
         }
         return $this->PLACE_HOLDER;
		}//end
}//end class

?>