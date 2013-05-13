<?php
	session_start();
	require_once('theme/_header.php');
	require_once('_dataInput.php');
		
$exml_file;  								// This refers to the Excel 2003 xml file

$col_del 		= "\n"; 					// Delemter between columns (a.k.a attributes)
$row_del		= "\n";					// Delemeter between rows
$empty			= "[NULL]";				 	// Shows where data is not located.

$temp = array_keys($_SESSION['positions']);	// Contains the locations of required attributes from the excel 2003 xml document.
					// captures the contents from the xml file
$count			= 0;						// Stores the number of attributes read fromt he xml file

$tuples	  		= 0;						// Number of rows skipped. Used to skip the headers
$skip_headers 	= 4; 						// Skips the headers of the excel 2003 file 


$xmlFile 	= "readData.txt";
$fileStream = fopen($xmlFile, 'w') or die("File Failed to Open");

foreach($temp as $t){$count++;}

$exml_file 	= DOMDocument::load($_SESSION['sheetFile']);
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

	insertData($xmlFile, $_SESSION['positions']);
	
	// This is the last thing that should be done when we are done with the user file
	unlink($_SESSION["sheetFile"]);
	session_unset();
	require_once('theme/_footer.php');
?>