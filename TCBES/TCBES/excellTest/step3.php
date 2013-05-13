<?php

	require_once("Excell2003toMysql.php");
	
	/*DB Connection Strings*/
	$hostname_connDBEHSP = '10.42.42.245:58924';
	$database_connDBEHSP = 'TCBES';
	$username_connDBEHSP = 'TCBESadmin';
	$password_connDBEHSP = 'TCBESpassword';
	
	$NEXT_URL = 'step2.php';
	
	session_start();
	
	$connDBEHSP = mysql_connect($hostname_connDBEHSP, $username_connDBEHSP, $password_connDBEHSP) or trigger_error(mysql_error(),E_USER_ERROR);
	mysql_select_db($database_connDBEHSP, $connDBEHSP);
	
	$converter = new Excell2003toMysql($connDBEHSP, $database_connDBEHSP, $_SESSION["sheetFile"]);
	
	$converter->getColumnCandidates('columnconfig.xml');
	
	$converter->findMatches();
	
	
	$converter->correctColumns($_SESSION['corrections']);
	
	$converter->getRows($_POST['position'] + 10, $_POST['position'], 4, $connDBEHSP);
	
	$converter->insertData($_POST['position'] + 10, $_POST['position'], $connDBEHSP);
	
	$errors = $converter->getPlaceHolderInfo($_POST['position'], $_POST['position'] + 10);
	
	echo '<form action="' . $NEXT_URL . '" method="post">';
	echo "<table border='1' cellpadding='5' cellspacing='0'> \n";
	echo "<tr><td><b>The following rows where given these place holders</b></td><td><b>place holders</b></td></tr>";	
	foreach($errors as $row=>$error_row){
		echo "<tr> \n";
		echo "<td>Row number $row </td>";
		echo '<td>';
		print_r($error_row);
		echo '</td>' . "\n" . '</tr>';
	}
	$new_position = $_POST['position'] + 10;
	echo '</table><br /> <input type="hidden" name="position" value="' . $new_position . '" />';
	echo '</br><input type="submit" name="submit" value="whatever" />' . "\n";
	echo '</form>';
	
?>