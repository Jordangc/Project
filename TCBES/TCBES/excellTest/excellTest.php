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
		
	   //$converter->row_items[0]['sex.sex_code'] = "XXXYYY";
	   //$converter->row_items[0]['sex.sex_type'] = "unknown";
	   //$converter->row_items[0]['specimen.event_code'] = "test2";
	   //$converter->row_items[0]['specimen.specimen_code'] = "spect2";
	   // $converter->tryInsertion('specimen.event_code',0);
	
	}
?>