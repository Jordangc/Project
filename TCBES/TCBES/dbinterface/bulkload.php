<?php
/*DB Connection Strings*/
$hostname_connDBEHSP = '10.42.42.245:58924';
$database_connDBEHSP = 'TCBES';
$username_connDBEHSP = 'TCBESadmin';
$password_connDBEHSP = 'TCBESpassword';

$transaction_log = array();
$place_holder = '[PH]';
$default_organization = 'UHH';

if(isset($_GET['debug']))
{
  if($_GET['debug'] == 1)
  {
  $debug = true;
  }
  else
  {
    $debug = false;
  }
}
$xml_dir = 'xml/';
$excel_dir = '';
$excel_file_name = 'test.xml';
/*store worksheets config*/
$insert_table = array();

function quote($text)
  {
    return 
     "'".
     htmlspecialchars((get_magic_quotes_gpc() ?
	 stripslashes($text) :
	 addslashes($text)))
	 ."'";
  }

/*
//Bulk load configuration file; specifies which workbook(s) to load
//and to also provide correspondence between excel file columns and the database attributes
*/
$config_file_path = 'xml/karl.xml';
$config_file = DOMDocument::load($config_file_path);

//POSSIBLE CONFIG ITEMS
$insert_table['coll'] = new DOMDocument();
$insert_table['coll']->formatOutput = true;
$insert_table['extr'] = new DOMDocument();
$insert_table['extr']->formatOutput = true;

/*
//Bulk load excel file; must be 2003 XML format
*/
$excel_file = DOMDocument::load($excel_dir.$excel_file_name);
$workbook = $excel_file->getElementsbyTagName('Worksheet');
$num_worksheet = $workbook->length;

foreach($insert_table as $table_name=>$table) /*Extract required worksheets*/
{
  for($i=0;$i<$num_worksheet;$i++)
  {
    if($table_name == $workbook->item($i)->getAttribute('ss:Name'))
	{
	  $insert_table[$table_name] = $workbook->item($i)->getElementsByTagName('Table');
	}
  }
}

foreach($insert_table as $table_name => $table)
{
  $rows = $table->item(0)->getElementsByTagName('Row');  /*only one table element in worksheet*/
  $num_rows = $rows->length;
  $cells = $rows->item(0)->getElementsByTagName('Cell'); /*first row contains column name*/
  $num_cells = $cells->length;
  $columns = array();
  $attrib_index= array();
  
  for($j=0;$j<$num_cells;$j++)
  {
    $attribute = preg_replace('/[^a-z^A-Z^0-9^_]/','_',strtolower($cells->item($j)->nodeValue));
	if($attribute=='')$attribute='!BLANK!';
	
	if(array_key_exists($attribute,$columns))
	{
	  $attrib_index[$attribute]++;
	  $attribute = $attribute.'_'.$attrib_index[$attribute];
	}

      $columns[$attribute] = array();

  }
  
  for($k=1;$k<$num_rows;$k++) /*Skip heading row*/
  {
    $values = $rows->item($k)->getElementsByTagName('Cell');
	$num_values = $values->length;
	
	$cell_index = 0;
	$num_skip=0;
	$skip_val='';
	$num_skipped = 0;
	
	foreach($columns as $attrib_name => $attribute)
	{
	  if($cell_index<$num_values)
	  {
	    if($values->item($cell_index)->getAttribute('ss:Index')!=''&&$skip_val=='')  /*There was skipped cell(s)*/
	    {
	      $excel_index = $values->item($cell_index)->getAttribute('ss:Index');
          $num_skip = ($excel_index-1) - ($cell_index+$num_skipped); /*Excel XML counts from 1*/
		  $num_skipped += $num_skip;
		  array_push($columns[$attrib_name],'');
		  $num_skip--;
		  $skip_val = $values->item($cell_index)->nodeValue;
		}
		else if($num_skip>0)
		{
		  array_push($columns[$attrib_name],'');
		  $num_skip--;
		}
		else
		{
		  if($skip_val!='')
		  {
		    array_push($columns[$attrib_name],escape($skip_val));
			$skip_val='';
		  }
		  else
		  {
	        $val = $values->item($cell_index)->nodeValue;
	        array_push($columns[$attrib_name],escape($val));

		  }
		  $cell_index++;
		}
	  }
	}
  }
  
  $insert_table[$table_name] = $columns; /*data table*/
}

if($debug) echo '<h1>The system is in DEBUG MODE</h1>';
if($debug) echo '<h2>Bulkload status:</h2>';

if($debug) echo '<p>Worksheet loaded into the memory -> OK</p>';

/*Insert*/

  $connDBEHSP = mysql_connect($hostname_connDBEHSP, $username_connDBEHSP, $password_connDBEHSP) or trigger_error(mysql_error(),E_USER_ERROR);
  mysql_select_db($database_connDBEHSP, $connDBEHSP);

    $query = 
    sprintf('select "yes" as organization from organization where abbr4_organization = %s;', quote($default_organization));
    $result =  mysql_query($query, $connDBEHSP) or die(mysql_error($connDBEHSP));
	$does_exist = mysql_fetch_assoc($result);

	if($does_exist['organization']=='yes')
	{
	  //Ignore list for performance
	}
	else
	{
      $query = 
      sprintf('insert into organization 
             (abbr4_organization)
             values (%s);', quote($default_organization));
      mysql_query($query, $connDBEHSP) or die(mysql_error($connDBEHSP));
    }

//collector
/*
collector:abbr3_collector = coll:Collector
collector:first_name = coll:-
collector:last_name = coll:-
collector:middle_name = coll:-
collector:email = coll:-
collector:passwd = coll:-
collector:user_name = coll:-
*/

  
  foreach($insert_table['coll']['collector'] as $id => $collector)
  {
    $collector = preg_replace('/[^a-z^A-Z]/','',$collector);
	$abbr3 = strtoupper(substr($collector,0,3));
	
	$insert_table['coll']['collector'][$id] = $abbr3;
	
    $query_collector = 
    sprintf('select "yes" as collector from collector where abbr3_collector = %s;', quote($abbr3));
    $result_collector =  mysql_query($query_collector, $connDBEHSP) or die(mysql_error($connDBEHSP));
	$does_exist = mysql_fetch_assoc($result_collector);

	if($does_exist['collector']=='yes')
	{
	  //Ignore list for performance
	}
	else
	{
	  //$transaction_log['collector'][$id] = $abbr3;
      $query_collector = 
      sprintf('insert into collector 
             (abbr3_collector,first_name,last_name,middle_name,email,user_name,passwd,abbr4_organization)
             values (%s,%s,%s,%s,%s,null,null,"UHH");', quote($abbr3),quote($place_holder),quote($place_holder),quote($place_holder),quote($place_holder));
      mysql_query($query_collector, $connDBEHSP) or die(mysql_error($connDBEHSP));
      //$num_collector_result = mysql_num_rows($raw_result);
    }
  }
  
if($debug) echo '<p>collector table -> OK</p>';
  
//location, island, reserve, locality
//island
/*
island:island_name = coll:island
*/
  foreach($insert_table['coll']['island'] as $id => $island)
  {
    $query_island = 
    sprintf('select "yes" as island from island where island_name = %s;', quote($island));
    $island_result = mysql_query($query_island, $connDBEHSP) or die(mysql_error($connDBEHSP));
	$does_exist = mysql_fetch_assoc($island_result);

	if($does_exist['island']=='yes')
	{
	  //Ignore list for performance
	}
	else
	{
	  //$transaction_log['island'][$id] = quote($island);
      $query_island = 
      sprintf('insert into island 
             (island_name)
             values (%s);', quote($island));
      $island_result = mysql_query($query_island, $connDBEHSP) or die(mysql_error($connDBEHSP));
    }
  }
  
if($debug) echo '<p>island table -> OK</p>';
  
//locality
/*
locality:locality_name = coll:locality
*/
  function escape($string)
  {
    return preg_replace('/["\']/','',$string);
  }

  foreach($insert_table['coll']['locality'] as $id => $locality)
  {
    $query_locality = 
    sprintf('select "yes" as locality from locality where locality_name = %s;', quote($locality));
    $locality_result = mysql_query($query_locality, $connDBEHSP) or die(mysql_error($connDBEHSP));
	$does_exist = mysql_fetch_assoc($locality_result);

	if($does_exist['locality']=='yes')
	{
	  //Ignore list for performance
	}
	else
	{
	  //$transaction_log['locality'][$id] = quote($locality);
      $query_locality = 
      sprintf('insert into locality 
             (locality_name)
             values (%s);', quote($locality));
      $locality_result = mysql_query($query_locality, $connDBEHSP) or die(mysql_error($connDBEHSP));
    }
  }
  
  if($debug) echo '<p>locality table -> OK</p>';

  //permission
  foreach($insert_table['coll']['type'] as $id => $permission_type)
  {
/*     if($permission_type=='')
	{
	  $permission_type = 'auto_fill';
	} */

      $query_permission = 
      sprintf('select "yes" as permission from reserve_permission where permission_type = %s;', quote($permission_type));
      $permission_result = mysql_query($query_permission, $connDBEHSP) or die(mysql_error($connDBEHSP));
	  $does_exist = mysql_fetch_assoc($permission_result);

	  if($does_exist['permission']=='yes')
	  {
	    //Ignore list for performance
	  }
	  else
	  {
	    //$transaction_log['permission'][$id] = quote($permission_type);
        $query_permission = 
        sprintf('insert into reserve_permission 
             (permission_type)
             values (%s);', quote($permission_type));
        $permission_result = mysql_query($query_permission, $connDBEHSP) or die(mysql_error($connDBEHSP));
      }
  }
  
  if($debug) echo '<p>permission table -> OK</p>';
  
  //reserve
  /*
  reserve:reserve_name = coll:reserve
  reserve:permission_type = coll:type
  */
  $num_rec = count($insert_table['coll']['event']);
  
  for($i=0;$i<$num_rec;$i++)
  {
    $reserve = $insert_table['coll']['reserve'][$i];
    $type = $insert_table['coll']['type'][$i];  //thtw
	
	$query_reserve = 
      sprintf('select "yes" as reserve from reserve where reserve_name = %s;', quote($reserve));
      $reserve_result = mysql_query($query_reserve, $connDBEHSP) or die(mysql_error($connDBEHSP));
	  $does_exist = mysql_fetch_assoc($reserve_result);

	  if($does_exist['reserve']=='yes')
	  {
	    //Ignore list for performance
	  }
	  else
	  {
	    //$transaction_log['reserve'][$i] = quote($reserve);
        $query_reserve = 
        sprintf('insert into reserve 
             (reserve_name, permission_type)
             values (%s, %s);', quote($reserve),quote($type));
        $reserve_result = mysql_query($query_reserve, $connDBEHSP) or die(mysql_error($connDBEHSP));
      }
  }
  
  if($debug) echo '<p>reserve table -> OK</p>';
  
  //location
  /*
  location:island_name = coll:island
  location:reserve_name = coll:reserve
  location:locality_name = coll:locality
  location:longitude = coll:N
  location:latitude = coll:W
  location:elevation_meter = coll:elev
  location:waypoint = coll:waypoint
  location:utm_zone = coll:zone
  location:utm_band = coll:band
  location:utm_northing = coll:northing
  location:utm_eastin = coll:easting
  */
  for($i=0;$i<$num_rec;$i++)
  {
    $island = $insert_table['coll']['island'][$i];
    $reserve = $insert_table['coll']['reserve'][$i];
    $locality = $insert_table['coll']['locality'][$i];
	$north_degree = $insert_table['coll']['__n'][$i];
	$west_degree = $insert_table['coll']['__w'][$i];
	$elevation = $insert_table['coll']['elev__ft_'][$i];
	$waypoint = $insert_table['coll']['waypoiny'][$i];
	$zone = $insert_table['coll']['zone'][$i];
	$band = $insert_table['coll']['band'][$i];
	$northing = $insert_table['coll']['northing'][$i];
	$easting = $insert_table['coll']['easting'][$i];
	
	$query_location = 
      sprintf('select "yes" as location from location where island_name = %s and reserve_name = %s and locality_name = %s;', 
	           quote($island),quote($reserve),quote($locality));
      $location_result = mysql_query($query_location, $connDBEHSP) or die(mysql_error($connDBEHSP));
	  $does_exist = mysql_fetch_assoc($location_result);

	  if($does_exist['location']=='yes')
	  {
	    //Ignore list for performance
	  }
	  else
	  {
	    //$transaction_log['location'][$i] = quote($reserve);
        $query_location = 
        sprintf('insert into location 
             (island_name,reserve_name,locality_name,north_degree,west_degree,elevation_foot,waypoint,utm_zone,utm_band,utm_northing,utm_easting)
             values (%s, %s, %s, %f, %f, %f, %s, %s, %s, %f, %f);', 
			 quote($island),quote($reserve),quote($locality),$north_degree,$west_degree,$elevation,quote($waypoint),quote($zone),quote($band),$northing,$easting);
        $location_result = mysql_query($query_location, $connDBEHSP) or die(mysql_error($connDBEHSP));
      }
  }
  
  if($debug) echo '<p>location table -> OK</p>';
  
  //event
  /*
  event:event_code = coll:event
  event:abbr3_collector = coll:collector
  event:locality_name = coll:locality
  event:reserve_name = coll:reserve
  event:date = coll:date
  event:event_note = coll:notes
  */
  for($i=0;$i<$num_rec;$i++)
  {
    $event = $insert_table['coll']['event'][$i];
    $collector = $insert_table['coll']['collector'][$i];
    $locality = $insert_table['coll']['locality'][$i];
    $reserve = $insert_table['coll']['reserve'][$i];
    $island = $insert_table['coll']['island'][$i];
    $date = substr($insert_table['coll']['date'][$i],0,10);
    $notes = $insert_table['coll']['notes'][$i];
	
	$query_event = 
      sprintf('select "yes" as event from event where event_code = %s', 
	           quote($event));
      $event_result = mysql_query($query_event, $connDBEHSP) or die(mysql_error($connDBEHSP));
	  $does_exist = mysql_fetch_assoc($event_result);

	  if($does_exist['event']=='yes')
	  {
	    //Ignore list for performance
	  }
	  else
	  {
	    //$transaction_log['event'][$i] = quote($reserve);
        $query_event = 
        sprintf('insert into event 
             (event_code,abbr3_collector,locality_name,reserve_name,island_name,date,event_note)
             values (%s, %s, %s, %s, %s, %s, %s);', 
			 quote($event),quote($collector),quote($locality),quote($reserve),quote($island),quote($date),quote($notes));
        $location_result = mysql_query($query_event, $connDBEHSP) or die(mysql_error($connDBEHSP));
      }
  }
  
  if($debug) echo '<p>event table -> OK</p>';
  
//life
/*
life:short_scientific_name = coll:order(3) + coll:family(3) + coll:genus(3)
life:scientific_name = coll:order + coll:family + coll:genus
*/

  for($i=0;$i<$num_rec;$i++)
  {
    $short_scientific[$i] = substr($insert_table['coll']['genus'][$i],0,3);  //$short_scientific is used also in collection
	$short_scientific[$i] .= substr($insert_table['coll']['family'][$i],0,3);
	$short_scientific[$i] .= substr($insert_table['coll']['order'][$i],0,3);
	$scientific_name = $insert_table['coll']['genus'][$i].' ';
	$scientific_name .= $insert_table['coll']['family'][$i].' ';
	$scientific_name .= $insert_table['coll']['order'][$i];
	
	$query_life = 
      sprintf('select "yes" as life from life where short_scientific_name = %s', 
	           quote($short_scientific[$i]));
      $life_result = mysql_query($query_life, $connDBEHSP) or die(mysql_error($connDBEHSP));
	  $does_exist = mysql_fetch_assoc($life_result);

	  if($does_exist['life']=='yes')
	  {
	    //Ignore list for performance
	  }
	  else
	  {
	    //$transaction_log['event'][$i] = quote($reserve);
        $query_life = 
        sprintf('insert into life 
             (short_scientific_name, scientific_name)
             values (%s, %s);', 
			 quote($short_scientific[$i]),quote($scientific_name));
        $life_result = mysql_query($query_life, $connDBEHSP) or die(mysql_error($connDBEHSP));
      }
  }
  
  if($debug) echo '<p>life table -> OK</p>';
  
//collection
/*
collection:catalog_code = coll:cat__no_
collection:event_code = coll:event
collection:short_scientific_name = DEFINED IN LIFE ABOVE
collection:number_sample = coll:n
collection:number_male = coll:m
collection:number_female = coll:f
collection:collection_note = coll:notes_1 
*/

  for($i=0;$i<$num_rec;$i++)
  {
	$catalog = $insert_table['coll']['cat__no_'][$i];
	$event = $insert_table['coll']['event'][$i];
	$n = $insert_table['coll']['n'][$i];
	$m = $insert_table['coll']['m'][$i];
	$f = $insert_table['coll']['f'][$i];
	$note = $insert_table['coll']['notes_1'][$i];
	
	$query_collection = 
      sprintf('select "yes" as collection from collection where catalog_code = %s and event_code = %s', 
	           quote($catalog),quote($event));
      $collection_result = mysql_query($query_collection, $connDBEHSP) or die(mysql_error($connDBEHSP));
	  $does_exist = mysql_fetch_assoc($collection_result);

	  if($does_exist['collection']=='yes')
	  {
	    //Ignore list for performance
	  }
	  else
	  {
	    //$transaction_log['event'][$i] = quote($reserve);
        $query_collection = 
        sprintf('insert into collection 
             (catalog_code,event_code, short_scientific_name,number_sample,number_male,number_female, collection_note)
             values (%s,%s,%s,%d,%d,%d,%s);', 
			 quote($catalog),quote($event),quote($short_scientific[$i]),$number_sample,$number_male,$number_female, quote($note));
        $collection_result = mysql_query($query_collection, $connDBEHSP) or die(mysql_error($connDBEHSP));
      }
  }
  
  if($debug) echo '<p>collection table -> OK</p>';
  
//container
/*
container:cabinet_code = $place_holder
container:box_code = coll:box
container:section_code = coll:order_in_box	
container:abbr4_organization = $default_organization
*/
  for($i=0;$i<$num_rec;$i++)
  {
	$cabinet = $place_holder;
	$box = $insert_table['coll']['box'][$i];
	$section = $insert_table['coll']['order_in_box'][$i];
	$organization = $place_holder;
	
	$query_container = 
      sprintf('select "yes" as container from container where cabinet_code = %s and box_code = %s and section_code = %s and abbr4_organization = %s', 
	           quote($cabinet),quote($box),quote($section),quote($default_organization));
      $container_result = mysql_query($query_container, $connDBEHSP) or die(mysql_error($connDBEHSP));
	  $does_exist = mysql_fetch_assoc($container_result);

	  if($does_exist['container']=='yes')
	  {
	    //Ignore list for performance
	  }
	  else
	  {
	    //$transaction_log['event'][$i] = quote($reserve);
        $query_container = 
        sprintf('insert into container 
             (cabinet_code,box_code,section_code,abbr4_organization)
             values (%s,%s,%s,%s);', 
			 quote($cabinet),quote($box),quote($section),quote($default_organization));
        $container_result = mysql_query($query_container, $connDBEHSP) or die(mysql_error($connDBEHSP));
      }
  }
  
  $query_container = 
      sprintf('select "yes" as container from container where cabinet_code = %s and box_code = %s and section_code = %s and abbr4_organization = %s', 
	           quote($place_holder),quote($place_holder),quote($place_holder),quote($default_organization));
      $container_result = mysql_query($query_container, $connDBEHSP) or die(mysql_error($connDBEHSP));
	  $does_exist = mysql_fetch_assoc($container_result);

	  if($does_exist['container']=='yes')
	  {
	    //Ignore list for performance
	  }
	  else
	  {
	    //$transaction_log['event'][$i] = quote($reserve);
        $query_container = 
        sprintf('insert into container 
             (cabinet_code,box_code,section_code,abbr4_organization)
             values (%s,%s,%s,%s);', 
			 quote($place_holder),quote($place_holder),quote($place_holder),quote($default_organization));
        $container_result = mysql_query($query_container, $connDBEHSP) or die(mysql_error($connDBEHSP));
      }
  
if($debug) echo '<p>container table -> OK</p>';
 
//sex
/*
*/
  $num_rec_extr = count($insert_table['extr']['specimen_no_']);
  
  for($i=0;$i<$num_rec_extr;$i++)
  {
	$sex = $event = $insert_table['extr']['sex'][$i];
	
	$query_sex = 
      sprintf('select "yes" as sex from sex where sex_code = %s', quote($sex));
      $sex_result = mysql_query($query_sex, $connDBEHSP) or die(mysql_error($connDBEHSP));
	  $does_exist = mysql_fetch_assoc($sex_result);

	  if($does_exist['sex']=='yes')
	  {
	    //Ignore list for performance
	  }
	  else
	  {
	    //$transaction_log['event'][$i] = quote($reserve);
        $query_sex = 
        sprintf('insert into sex 
             (sex_code)
             values (%s);', 
			 quote($sex));
        $sex_result = mysql_query($query_sex, $connDBEHSP) or die(mysql_error($connDBEHSP));
      }
  }


if($debug) echo '<p>sex table -> OK</p>';

//specimen
/*
specimen:event_code = extr:event
specimen:catalog_code = extr:cat__no_
specimen:specimen_code = extr:specimen_no_
specimen:cabinet_code = $place_holder
specimen:box_code = coll:box
specimen:section_code = coll:order_in_box
specimen:abbr4_organization = $default_organization
specimen:sex_code = extr:sex
specimen:status_code = null
*/

  
  for($i=0;$i<$num_rec;$i++)
  {
	
	$event = $insert_table['coll']['event'][$i];
	$catalog = $insert_table['coll']['cat__no_'][$i];
	$cabinet = $place_holder;
	$box = $insert_table['coll']['box'][$id];
	$section = $insert_table['coll']['order_in_box'][$i];
	
	$matched = array();
	$matched = array_keys($insert_table['extr']['event'] ,$event, true);
	
	foreach($matched as $id)
	{
	  if($insert_table['extr']['cat__no_'][$id]==$catalog)
	  {
	    $specimen = $insert_table['extr']['specimen_no_'][$id];
		$sex = $insert_table['extr']['sex'][$id];
		
		$specimen_dna[$id]['event'] = $event;
	    $specimen_dna[$id]['catalog'] = $catalog;
     	$specimen_dna[$id]['extraction'] = $insert_table['extr']['extr__no_'][$id];
	    $specimen_dna[$id]['specimen'] = $insert_table['extr']['specimen_no_'][$id];
	  }
	}

	$query_specimen = 
      sprintf('select "yes" as specimen from specimen where event_code = %s and catalog_code = %s and specimen_code = %s', 
	           quote($event),quote($catalog),quote($specimen));
      $specimen_result = mysql_query($query_specimen, $connDBEHSP) or die(mysql_error($connDBEHSP));
	  $does_exist = mysql_fetch_assoc($specimen_result);

	  if($does_exist['specimen']=='yes')
	  {
	    //Ignore list for performance
	  }
	  else
	  {
	    //$transaction_log['event'][$i] = quote($reserve);
        $query_specimen = 
        sprintf('insert into specimen 
             (event_code,catalog_code,specimen_code,cabinet_code,box_code,section_code,abbr4_organization,sex_code,status_code)
             values (%s,%s,%s,%s,%s,%s,%s,%s,null);', 
			 quote($event),quote($catalog),quote($specimen),quote($cabinet),quote($box),quote($section),quote($default_organization),quote($sex));
        $specimen_result = mysql_query($query_specimen, $connDBEHSP) or die(mysql_error($connDBEHSP));
      }
  }
  //echo 'DEBUG:', print_r($specimen_dna);
  
if($debug) echo '<p>specimen table -> OK</p>';

//dna
/*
dna:extraction_code = extr:extr__no_
dna:section_code = $place_holder
dna:box_ccode = $place_holder
dna:cabinet_code = $place_holder
dna:abbr4_organization = $default_organization
dna:sequence = $place_holder
dna:sequnce_name = $place_holder 
dna:primer_name = $place_holder
*/

  for($i=0;$i<$num_rec_extr;$i++)
  {
	$extraction = $insert_table['extr']['extr__no_'][$i];
    $section = $place_holder;
    $box = $place_holder;
    $cabinet = $place_holder;
    $abbr4_organization = $default_organization;
    $sequence = $place_holder;
    $sequnce_name = $place_holder;
    $primer = $place_holder;
	
	$query_dna = 
      sprintf('select "yes" as dna from dna where extraction_code = %s', quote($extraction));
      $dna_result = mysql_query($query_dna, $connDBEHSP) or die(mysql_error($connDBEHSP));
	  $does_exist = mysql_fetch_assoc($dna_result);

	  if($does_exist['dna']=='yes')
	  {
	    //Ignore list for performance
	  }
	  else
	  {
	    //$transaction_log['event'][$i] = quote($reserve);
        $query_dna = 
        sprintf('insert into dna 
             (extraction_code,section_code,box_code,cabinet_code,abbr4_organization,sequence,sequence_name,primer_name)
             values (%s,%s,%s,%s,%s,%s,%s,%s);', 
			 quote($extraction),quote($section),quote($box),quote($cabinet),quote($abbr4_organization),quote($sequence),quote($sequnce_name),quote($primer));
        $dna_result = mysql_query($query_dna, $connDBEHSP) or die(mysql_error($connDBEHSP));
      }
  }

if($debug) echo '<p>extraction table -> OK</p>';

//specimen_dna
foreach($specimen_dna as $ref)
{
  $query_specimen_dna = 
      sprintf('select "yes" as specimen_dna from specimen_dna where event_code = %s and catalog_code = %s and extraction_code = %s and specimen_code = %s', 
	  quote($ref['event']),quote($ref['catalog']),quote($ref['extraction']),quote($ref['specimen']));
      $specimen_dna_result = mysql_query($query_specimen_dna, $connDBEHSP) or die(mysql_error($connDBEHSP));
	  $does_exist = mysql_fetch_assoc($specimen_dna_result);

	  if($does_exist['specimen_dna']=='yes')
	  {
	    //Ignore list for performance
	  }
	  else
	  {
	    //$transaction_log['event'][$i] = quote($reserve);
        $query_specimen_dna = 
        sprintf('insert into specimen_dna 
             (event_code,catalog_code,extraction_code,specimen_code)
             values (%s,%s,%s,%s);', 
			 quote($ref['event']),quote($ref['catalog']),quote($ref['extraction']),quote($ref['specimen']));
        $specimen_dna_result = mysql_query($query_specimen_dna, $connDBEHSP) or die(mysql_error($connDBEHSP));
      }
}
if($debug) echo '<p>specimen_dna table -> OK</p>';

/*END OF BULKLOAD*/



if($debug)
{
/*   foreach($transaction_log as $id => $log)
  {
    echo '<h2>',$id,'</h2>';
	echo '<ul><li>';
    print (implode('</li><li>',$log));
	echo '</li></ul>';
  } */

  foreach($insert_table as $table)
  {
    echo '<table style="border:1px solid #ccc;font-size:8pt;">';
    foreach($table as $attrib=>$values)
    {
      echo '<tr>';
	  echo '<th style="background:#efefef;">'.$attrib.'</th>';
	  foreach($values as $val_index=>$value)
	  {
	    echo '<td style="border:1px solid #ccc;">';
	    echo ($value=='')?'&nbsp;':$value;
	    echo '</td>';
	  }
	  echo '<tr>';
    }
    echo '</table>';
  }
}

echo '<p>Success! Go to the <a href="read.php">query window</a>.</p>';

//Display load result




?>