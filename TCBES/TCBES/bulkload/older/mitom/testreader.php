    
<?php
readRow();

function readRow()//( $xml_name, $session[] )
{
$exml_file;  								// This refers to the Excel 2003 xml file

$col_del 		= "\n"; 					// Delemter between columns (a.k.a attributes)
$row_del		= "----\n";					// Delemeter between rows
$empty			= "[NULL]";				 	// Shows where data is not located.

$temp = array_keys($_SESSION['positions']);	// Contains the locations of required attributes from the excel 2003 xml document.
$xmlText		= array();					// captures the contents from the xml file
$count			= 0;						// Stores the number of attributes read fromt he xml file

$tuples	  		= 0;						// Number of rows skipped. Used to skip the headers
$skip_headers 	= 4; 						// Skips the headers of the excel 2003 file 

echo "Hello test readers <br/>" ;

$xmlFile 	= "readData.txt";
$fileStream = fopen($xmlFile, 'w') or die("File Failed to Open");

$exml_file 	= DOMDocument::load("plant.xml");
	$rows = $exml_file->getElementsbyTagName("Row");
          foreach($rows as $row){
               if($tuples < $skip_headers){
                    $tuples++;
               }
               else{
               $cells = $row->getElementsbyTagName("Cell");
					$count = 0;
                    foreach($cells as $cell){
						$data = $cell->getElementsbyTagName("Data")->item(0)->textContent;
						if(!isset($data))	
							{$data = $empty;}
						$xmlText[$count] = $data;  //store to array   //fwrite($fileStream, $data . $col_del);
						$count++;
					}
					for($i = 0; $i < count; count++){
						fwrite($fileStream, $xmlText[$temp[i]].$col_del); // writing only the necessary data fromt the xml file
					}
               }

			  
			   fwrite($fileStream, $row_del);
          }

fclose($fileStream);
echo("File write complete <br/>");
}
?>
