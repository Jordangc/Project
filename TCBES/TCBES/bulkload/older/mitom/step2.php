<?php
	session_start();
	require_once('theme/_header.php');

	//print_r($_SESSION['matches']);
	//print_r($_SESSION['positions']);
	print_r($_SESSION['all_positions']);
	//print_r($_SESSION['database_columns']);
	
	// print_r($_POST);
	
	foreach(array_keys($_POST) as $change_key){
		if($_POST[$change_key] == 'none' or $_POST[$change_key] =='Submit')
			continue;
		echo $change_key . ' <br />';
		echo $_SESSION['all_positions'][$change_key];
		$_SESSION['positions'][$_SESSION['all_positions'][$change_key]] =  $_POST[$change_key];
	}
	
	print_r($_SESSION['positions']);
	readFile($_SESSION['sheetFile'], $_SESSION['positions']);

	
	// This is the last thing that should be done when we are done with the user file
	unlink($_SESSION["sheetFile"]);
	session_unset();
	require_once('theme/_footer.php');
	
function readFile($session, $positions)
{

$exml_file;  								// This refers to the Excel 2003 xml file

$col_del 		= "\n"; 					// Delemter between columns (a.k.a attributes)
$row_del		= "";						// Delemeter between rows
$empty			= "[NULL]";				 	// Shows where data is not located.

$temp = array_keys($positions);	// Contains the locations of required attributes from the excel 2003 xml document.
					
$count			= 0;						// Stores the number of attributes read fromt he xml file

$tuples	  		= 0;						// Number of rows skipped. Used to skip the headers
$skip_headers 	= 4; 						// Skips the headers of the excel 2003 file 

$xmlFile 	= "readData.txt";
$fileStream = fopen($xmlFile, 'w') or die("File Failed to Open");

foreach($temp as $t){$count++;}

$exml_file 	= DOMDocument::load($session);
	$rows = $exml_file->getElementsbyTagName("Row");
          foreach($rows as $row){
               if($tuples < $skip_headers){
                    $tuples++;
               }
               else{
               $cells = $row->getElementsbyTagName("Cell");
                    foreach($cells as $cell){
						$data = $cell->getElementsbyTagName("Data")->item(0)->textContent;
				
						if(!isset($data))
							{$data = $empty;}
						
						$xmlText[] = $data;		//store to array   //fwrite($fileStream, $data . $col_del);
						
					}
               }
			   if(isset($xmlText)){
						foreach($temp as $t){
							if( $xmlText[$t] == $empty){
								unset($xmlText);
								break;
							}
							else{
								fwrite($fileStream, $xmlText[$t].$col_del);//echo ($xmlText[$t]."<br/>");// echo($text . " <br/>"); //echo ($text[$t]." <br/>");
							}
						}
						//fwrite($fileStream, $xmlText[$temp[i]].$col_del); // writing only the necessary data fromt the xml file
				}
			
				unset($xmlText);
			    fwrite($fileStream, $row_del);
          }

fclose($fileStream);
echo("File write complete <br/>");

}
?>