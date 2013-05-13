<?php
	session_start();
	echo $_SESSION["tempFileName"];
	//$sheetFile = DOMDocument::load($_SESSION["tempFileName"]);
	//echo $_SESSION["sheetFile"];
	$workbook = $_SESSION["sheetFile"]->getElementsbyTagName('Column');
?>