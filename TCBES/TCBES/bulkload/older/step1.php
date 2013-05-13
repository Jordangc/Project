<?php
	/*DB Connection Strings*/
	$hostname_connDBEHSP = '10.42.42.245:58924';
	$database_connDBEHSP = 'TCBES';
	$username_connDBEHSP = 'TCBESadmin';
	$password_connDBEHSP = 'TCBESpassword';

	$BULKLOAD_START = 'index.php';
	$NEXT_URL = 'step2.php';
	$LOGIN_PAGE = '../dbinterface';

	session_start();
	require_once('_findMatches.php');
	require_once('_getColumns.php');
	require_once('theme/_header.php');
	
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
  
		$workbook = DOMDocument::load($_SESSION["sheetFile"]);
	
		if(!isset($_SESSION['uid'])){
			echo '<br /> DEBUG you are not logged in. <a href="'. $LOGIN_PAGE .'"> login </a> <br />';
		}
		
		$_SESSION['matches'] = getColumnCandidates($connDBEHSP);
		findMatches($workbook, $_SESSION['matches'], $_SESSION['positions'], $_SESSION['database_columns'], $_SESSION['all_positions'], $NEXT_URL);
		
		//print_r(getDataTypes($_SESSION['database_columns']));
		
	}
	
	require_once('theme/_footer.php');
	
?> 
