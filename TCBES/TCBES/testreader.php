    
<?php
$a1 = array();
$a2 = array();
$i = 0;
$j = 0;

echo "hello test readers <br/>" ;

for( $i = 0; $i < 10; $i++ ){
for( $j = 0; $j < 10; $j++ ){
     $a2[$j];
     }
     $a1[$i] = $a2;
}

for( $i = 0; $i < 10; $i++ ){
for( $j = 0; $j < 10; $j++ ){
      $a1[$i][$j] = $j;
     }
}
 for( $i = 0; $i < 10; $i++ ){
for( $j = 0; $j < 10; $j++ ){
     echo $a1[$i][$j] . " ";
     }
	 echo " <br/>";
}
?>
