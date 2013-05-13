<?php
	/*DB Connection Strings*/
	$hostname_connDBEHSP = '10.42.42.245:58924';
	$database_connDBEHSP = 'TCBES';
	$username_connDBEHSP = 'TCBESadmin';
	$password_connDBEHSP = 'TCBESpassword';

	$BULKLOAD_START = 'index.php';
	$NEXT_URL = 'step2.php';
	$LOGIN_PAGE = '../dbinterface';

	$connDBEHSP = mysql_connect($hostname_connDBEHSP, $username_connDBEHSP, $password_connDBEHSP) or trigger_error(mysql_error(),E_USER_ERROR);
	mysql_select_db($database_connDBEHSP, $connDBEHSP);

	$result = mysql_query("select * from specimen ", $connDBEHSP);
	if(!$result){
		echo mysql_error($connDBEHSP);
	}

	
?> 
