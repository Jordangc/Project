<?php


/**
* @todo:	1.place holder mutation and saving.
*/
class Excell2003toMysql{
	

	private $xml_positions;

	/**
	* Contains the 'table'.'column' names indexed by their column position in the xml spreadsheet
	*/
	private $data_positions;
	
	private $into_database;
	
	private $workbook;
	
	private $column_matches;
	
	private $column_misses;
	
	private $row_items;
	
	private $row_errors;
	
	private $db_connection;
	
	private $found_values;
	
	private $up_bound;
	
	private $down_bound;
	
	private $PLACE_HOLDER = '[PH]'; 
	
	/**
	*	used for each row that is inserted and then cleared. This prevents repetition
	*/
	private $data_to_insert;
	
	private $fk_down_query_query = "select REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
					 from information_schema.KEY_COLUMN_USAGE where TABLE_NAME = '%s' and COLUMN_NAME = '%s' and REFERENCED_TABLE_NAME is not null;";

	private $column_query = "describe %s";
	
	private $fk_up_query = "select TABLE_NAME, COLUMN_NAME
					 from information_schema.KEY_COLUMN_USAGE where REFERENCED_TABLE_NAME = '%s' and REFERENCED_COLUMN_NAME = '%s'";
					 
	private $ph_mutation_query = " select %s from %s where %s like '" . $this->$PLACE_HOLDER . "%'";
	
	function __construct($xmlFile){
		$this->workbook = DOMDocument::load($xmlFile);
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
				// TAXON MATCHES
				if($column->getElementsbyTagName('taxon')->item(0)){
					$this->column_matches['taxon'][$match->textContent] = $column->getElementsbyTagName('taxon')->item(0)->textContent;
				}
			}
		}
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
				$this->column_misses[] = $data;
			$position++;
		}
	}
	
	/**
	*	Takes an array that is indexed by the normalized xml spreadsheet column name and contains 
	*	spreadsheet column number as data
	*	
	*/
	function correctColumns($changes){
		foreach(array_keys($changes) as $change_key){
			$this->data_positions[$this->xml_positions[$change_key]] =  $changes[$change_key];
		}
	}
	
	
	function getRows($number_of_rows, $position, $header_depth, $connDBEHSP){
	
		$this->db_connection = $connDBEHSP
		
		$empty			= "[NULL]";				 	// Shows where data is not located.
		$tuples	  		= 0;						// Number of rows skipped. Used to skip the headers

		$rows = $this->workbook->getElementsbyTagName("Row");
		for($i = $position; $i < $position + $number_of_rows; $i++;){
			$row = $rows->item($i);
		   if($tuples < $header_depth){
				$tuples++;
		   }
		   else{
		   $cells = $row->getElementsbyTagName("Cell");
				foreach(array_keys($this->data_positions) as $data_position){
				
					$cell = $cells->item($data_position);
					$data = $cell->getElementsbyTagName("Data")->item(0)->textContent;
				
					if(!isset($data))
						{$data = $empty;}
					
					$row_items[$i][$this->data_positions[$data_position]] = $data;		//store to array
					checkType($data, data_positions[$data_position], $i, $data_position);
				}
			}
		}
	}
	
	function checkTypes($data, $table_dot_column, $row, $position){
			$table_column = explode('.', $table_dot_column);
			$result = mysql_query('describe ' . $table_column[0] . ' ' . $table_column[1]);
			while($description = mysql_fetch_assoc($result)){
				$info = explode('(',$description['Type']);
				$dataTypes['type'] = $info[0];
				$length = explode(')', $info[1]);
				$dataTypes['length'] = $length[0];
			}
			mysql_free_result($result);
			
			switch($dataTypes['type']){
				case 'decimal':
					if(!isnumeric($data)){
						$this->row_errors[$row][$position] = ' Not a decimal '
					}
					else(// CHECK if THERE is e){
					
			//		}
					break;
				case 'int' :
					if(!isnumeric($data)){
						$this->row_errors[$row][$position] = ' Not an integer '
					}
					else(//CHECK IF THERE IS . or E ){
			}
			
			if(strln($data) > $dataTypes['length']){
				if (isset($this->row_errors[$row][$position])){
					$this->row_errors[$row][$position] .= ' and longer than ' . $dataTypes['length'] . ' characters';
				}
				else{
					$this->row_errors[$row][$position] .= ' Longer than ' . $dataTypes['length'] . ' characters';
				}
			}	
	}
	
	function insertData($number_of_rows, $position, $connDBEHSP){
		$this->db_connection = $connDBEHSP;
		for($i = $position; $i < $position + $number_of_rows; i++){
			foreach($this->data_positions as $data_position){
				tryInsertion($data_position, $i);
				unset($this->data_to_insert);
			}
		}
	}
		
	function tryInsertion($column_to_insert, $row_number){
		$table_and_column = explode('.', $column_to_insert);
		$column_query = sprintf($this->column_query, $table_and_column[0]);
		$column_result = mysql_query($column_query, $this->db_connection);
		
		$insert_string = 'insert into ' . $table_and_column[0] . '(';
		$extra_variable = 0;
		while($table_column = mysql_fetch_assoc($column_result)){
			if($extra_variable == 0){
				$insert_string .= $table_column['Field'];
				$extra_variable = "can_do";
			}
			else
				$insert_string .= "," . $table_column['Field'];
			if(!isset($this->data_to_insert[$table_column['Field']])){
				$this->data_to_insert[$table_column['Field']] = findDependentValue($row_number, $table_and_column[0], $table_column['Field']);
			}
		}
		
		$insert_string .= ') values ('; 
		
		$first = true;
		foreach($this->row_item[$row_number] as $datum){
			if($first == true){
				$insert_string .= $datum;
				$first == false;
			}
			else{
				$insert_string .= ',' . $datum;
			}
		}
		
		$insert_string .= ')';
		
		// do the query if not result
		$insert_result = mysql_query($insert_query, $this->db_connection);
		
		if(!$insert_result){
			// go down to the next level
			foreach(array_keys($this->data_to_insert) as $the_column){
			
			// WHEN WE HIT THE BOTTOM AND THERE IS A PRIMARY KEY ERROR
			// WE NEED TO CHOOSE A NEW PLACEHOLDER AND THEN REMEMBER IT
			
				$go_down = sprintf($this->fk_down_query, $table_and_column[0], $the_column);
				$down_result = mysql_query($go_down, $this->db_connection);
				
				if$table_column = mysql_fetch_assoc($down_result)){
					tryInsertion($table_column['REFERENCED_TABLE_NAME'] . '.' . $table_column['REFERENCED_COLUMN_NAME'], $row_number);
				}
				else{
					// this bottoms out -- no foreign key dependencies
					
					// get the foreign keys that are like PH and
					
				}
				while($table_column = mysql_fetch_assoc($down_result)){
					tryInsertion($table_column['REFERENCED_TABLE_NAME'] . '.' . $table_column['REFERENCED_COLUMN_NAME'], $row_number);
				}
			}
			$insert_result = mysql_query($insert_query, $this->db_connection);
		}
	}
		
	function buildDependencies($connDBEHSP){
		$this->db_connection = $connDBEHSP;
		$dep_query = "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM `information_schema`.`KEY_COLUMN_USAGE` where REFERENCED_TABLE_NAME is not null";
		$result = mysql_query($dep_query, $this->db_connection);
		while($row = mysql_fetch_assoc($result)){
			$down_dep[$row['TABLE_NAME'].'.'.$row['COLUMN_NAME']] = $row['REFERENCED_TABLE_NAME'].'.'.$row['REFERENCED_COLUMN_NAME'];
			$up_dep[$row['REFERENCED_TABLE_NAME'].'.'.$row['REFERENCED_COLUMN_NAME']] = $row['TABLE_NAME'].'.'.$row['COLUMN_NAME'];
		}
		foreach($up_dep as $current_table_dot_column){
			$table_column_ph = $current_table_dot_column;
			while(isset($up_dep[$table_column_ph)){
				$this->up_bound[$current_table_dot_column][] = $up_dep[$table_column_ph];
				$table_column_ph = $up_dep[$table_column_ph];
			}
		}
		foreach($down_dep as $current_table_dot_column){
			$table_column_ph = $current_table_dot_column;
			while(isset($down_dep[$table_column_ph)){
				$this->down_bound[$current_table_dot_column][] = $down_dep[$table_column_ph];
				$table_column_ph = $down_dep[$table_column_ph];
			}
		}
	}
	
	function upwardsDependencies($table, $column, $row_position){

		$query = sprintf($fk_up, $table, $column);
		$result = mysql_query($query, $this->db_connection);
		foreach($this->data_positions as $attribute){
			if($table . '.' . $column == $attribute){
				return $this->row_items[$row_position][$attribute];
			}
		}
		while($row = mysql_fetch_assoc($result)){
			$match = upwardsDependencies($row['TABLE_NAME'], $row['COLUMN_NAME'], $row_position);
			if($match != $this->PLACE_HOLDER)
				return $match;
		}
		mysql_free_result($result);
		return $this->PLACE_HOLDER;
	}

	function downwardsDependencies($table, $column, $match, $row_position){
		$query = sprintf($this->fk_down_query, $table, $column);
		$result = mysql_query($query, $this->db_connection);
		if($row = mysql_fetch_assoc($result)){
			if($match == $this->PLACE_HOLDER){
				foreach($this->data_position as $attribute){
					if($row['REFERENCED_TABLE_NAME'] . '.' . $row['REFERENCED_COLUMN_NAME'] == $attribute){
						$match = $this->row_items[$row_position][$attribute];
					}
				}
			}
			$match = downwardsDependencies($row['REFERENCED_TABLE_NAME'], $row['REFERENCED_COLUMN_NAME'], $match, $row_position);
		}
		mysql_free_result($result);
		return $match;
	}
	
	function findDependentValue($row_number, $table, $column){
			//$this->up_bound $this->down_bound
			foreach(up_bound[$table . "." $column] as $up_stuff){
			
			}
		}
}

?>