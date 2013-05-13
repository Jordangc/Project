<?php
/*DB Connection Strings*/
$hostname_connDBEHSP = '10.42.42.245:58924';
$database_connDBEHSP = 'TCBES';
$username_connDBEHSP = 'TCBESadmin';
$password_connDBEHSP = 'TCBESpassword';
//insert into `ehsp`.`collector` (abbr3_collector,first_name,last_name,middle_name,email,passwd,abbr4_organization,user_name) values ("ZZZ","Kohei","Miyagi", null, "kmiyagi@hawaii.edu","konagold","UHH","kmiyagi");
/*
delete from specimen_dna;
delete from dna;
delete from specimen;
delete from collection;
delete from life;
delete from container;
delete from event_collector;
delete from event;
delete from collector;
delete from location;
delete from locality;
delete from island;
delete from reserve;
delete from reserve_permission;
delete from sex;
delete from perservation_status;
delete from organization;
*/
  $connDBEHSP = mysql_connect($hostname_connDBEHSP, $username_connDBEHSP, $password_connDBEHSP) or trigger_error(mysql_error(),E_USER_ERROR);
  mysql_select_db($database_connDBEHSP, $connDBEHSP);

  $query = 'select collection.event_code, collection.catalog_code, collector.first_name, 
  collector.last_name, collector.middle_name, collector.abbr3_collector, life.scientific_name,
event.island_name, event.reserve_name, event.locality_name, event.date,
round(location.north_degree,4) as north_degree, round(location.west_degree,4) as west_degree,
specimen.cabinet_code, specimen.box_code, specimen.section_code, specimen.abbr4_organization
from collection, event, location, collector, life, specimen
where collection.event_code = event.event_code and
event.island_name = location.island_name and
event.reserve_name = location.reserve_name and
event.locality_name = location.locality_name and
event.abbr3_collector = collector.abbr3_collector and
collection.short_scientific_name = life.short_scientific_name and
collection.event_code = specimen.event_code and
collection.catalog_code = specimen.catalog_code
order by collection.event_code;';
  $result =  mysql_query($query, $connDBEHSP) or die(mysql_error($connDBEHSP));
  $output = array();
  while($record = mysql_fetch_assoc($result))
  {
    if(count($output[$record['event_code']]['catalog_code'])==0) //array_push requires array, so initialize
	{
	  $output[$record['event_code']]['catalog_code'] = array();
	}
    array_push($output[$record['event_code']]['catalog_code'], $record['catalog_code']);
	if(count($output[$record['event_code']]['first_name'])==0)
	{
	  $output[$record['event_code']]['first_name'] = array();
	}
    array_push($output[$record['event_code']]['first_name'], $record['first_name']);
    if(count($output[$record['event_code']]['last_name'])==0)
	{
	  $output[$record['event_code']]['last_name'] = array();
	}
    array_push($output[$record['event_code']]['last_name'], $record['last_name']);
	if(count($output[$record['event_code']]['middle_name'])==0)
	{
	  $output[$record['event_code']]['middle_name'] = array();
	}
    array_push($output[$record['event_code']]['middle_name'], $record['middle_name']);	
	if(count($output[$record['event_code']]['abbr3_collector'])==0)
	{
	  $output[$record['event_code']]['abbr3_collector'] = array();
	}
    array_push($output[$record['event_code']]['abbr3_collector'], $record['abbr3_collector']);
	if(count($output[$record['event_code']]['scientific_name'])==0)
	{
	  $output[$record['event_code']]['scientific_name'] = array();
	}
    array_push($output[$record['event_code']]['scientific_name'], $record['scientific_name']);
	if(count($output[$record['event_code']]['island_name'])==0)
	{
	  $output[$record['event_code']]['island_name'] = array();
	}
    array_push($output[$record['event_code']]['island_name'], $record['island_name']);
	if(count($output[$record['event_code']]['reserve_name'])==0)
	{
	  $output[$record['event_code']]['reserve_name'] = array();
	}
    array_push($output[$record['event_code']]['reserve_name'], $record['reserve_name']);
	if(count($output[$record['event_code']]['locality_name'])==0)
	{
	  $output[$record['event_code']]['locality_name'] = array();
	}
    array_push($output[$record['event_code']]['locality_name'], $record['locality_name']);
	if(count($output[$record['event_code']]['date'])==0)
	{
	  $output[$record['event_code']]['date'] = array();
	}
    array_push($output[$record['event_code']]['date'], $record['date']);
	if(count($output[$record['event_code']]['north_degree'])==0)
	{
	  $output[$record['event_code']]['north_degree'] = array();
	}
    array_push($output[$record['event_code']]['north_degree'], $record['north_degree']);
	if(count($output[$record['event_code']]['west_degree'])==0)
	{
	  $output[$record['event_code']]['west_degree'] = array();
	}
    array_push($output[$record['event_code']]['west_degree'], $record['west_degree']);
	if(count($output[$record['event_code']]['cabinet_code'])==0)
	{
	  $output[$record['event_code']]['cabinet_code'] = array();
	}
    array_push($output[$record['event_code']]['cabinet_code'], $record['cabinet_code']);
	if(count($output[$record['event_code']]['box_code'])==0)
	{
	  $output[$record['event_code']]['box_code'] = array();
	}
    array_push($output[$record['event_code']]['box_code'], $record['box_code']);
	if(count($output[$record['event_code']]['section_code'])==0)
	{
	  $output[$record['event_code']]['section_code'] = array();
	}
    array_push($output[$record['event_code']]['section_code'], $record['section_code']);
	if(count($output[$record['event_code']]['abbr4_organization'])==0)
	{
	  $output[$record['event_code']]['abbr4_organization'] = array();
	}
    array_push($output[$record['event_code']]['abbr4_organization'], $record['abbr4_organization']);
  }
?>
<html>
  <head>
    <title>DNA Barcoding Endemic Hawaiian Species Project</title>
	<style>
	  body {background:#ccc;}
	  h1,h2,h3,h4,h5,h6 {border-bottom:1px dotted #ccc; clear:both;}
	  h2 {background:#efefef;}
	  table {width:70%; margin:0 auto 1em; border:1px solid #ccc; font-size:10pt;}
	  th {width:30%;}
	  td {width:70%;}
	  th, td {padding:.25em; border:1px solid #ccc;}
	  div.record {background:#fff;}
	  .g_maps {margin:0 auto 0; background:#ccc;}
	  .popup {font-size:70%;}
	</style>
	
<!--Google Maps-->
	<meta name="viewport" content="initial-scale=1.0, user-scalable=yes" />
    <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript">
    function initialize(){
    <?php
	foreach($output as $id => $element)
	{
	  echo $id.'();',"\n";
	}
	?>
	  //m0001();
	}
    </script>
	<script type="text/javascript">
	
	<?php
	foreach($output as $id => $element)
	{
	  //if($id =='m0001'){
	  echo 'function ',$id,'(){',
	  'var latlng = new google.maps.LatLng(',$element['north_degree'][0],', ',-$element['west_degree'][0],');
       var myOptions = {zoom:12, center:latlng, mapTypeId:google.maps.MapTypeId.HYBRID};
	   var map = new google.maps.Map(document.getElementById("map_canvas_',$id,'"), myOptions);
       setMarkers(map, specimens',$id,');}'
	   ,
	  'var specimens',$id,' = [';
	  foreach($element['north_degree'] as $deg => $north_deg)
	  {
	    $west_deg = -$element['west_degree'][$deg];
		if($deg!=0) echo ',';
	    echo '["'
		     ,$id.'-'.$element['catalog_code'][$deg].': '.$element['scientific_name'][$deg],
			 '", '
			 ,$north_deg,
			 ','
			 ,$west_deg,
			 ', 4]';
	  }
	  echo '];
	  ';	
	  //}
	} 
	 
	  echo 'function setMarkers(map, locations) 
	        {
			  //var infowindow = new Array();
			  //var marker = new Array();
			  for (var i = 0; i < 1; i++) 
			  {
                var specimen = locations[i];
                var myLatLng = new google.maps.LatLng(specimen[1], specimen[2]);
                var marker = new google.maps.Marker({
                  position: myLatLng,
                  map: map,
                  title: specimen[0],
                  zIndex: specimen[3]
                });
				
				var circle = new google.maps.Circle({
				fillColor:"#ffff00",
				fillOpacity: 0.2,
				strokeColor:"#ffff00",
                map: map,
                radius: 3000
                });
                 
				circle.bindTo("center", marker, "position");
				
			   infowindow = new google.maps.InfoWindow({
                  content: "<div class=\"popup\"><h3>" + specimen[0] + "</h3>" + "<p>" + specimen[1] + ", " + specimen[2] + "</p></div>"
                });

				google.maps.event.addListener(marker, "click", function() {
                  infowindow.open(map,marker);
                });
				
              }
			}';

    ?>
	</script>
<!--Google Maps-->

  </head>
  <body onload="initialize()">
    <h1>DNA Barcoding Endemic Hawaiian Species Project</h1>
<?php
  
  
  //print_r($output);
  
  foreach($output as $attrib => $output_element)
  {
    echo '<div class="record">',"\n";
    echo '<h2>Event Code - ',$attrib,': ',$output_element['date'][0],'</h2>',"\n";
    echo '<h3>Location</h3>',"\n";
	echo '<p>',$output_element['locality_name'][0],', ',$output_element['reserve_name'][0],', ',$output_element['island_name'][0],'</p>',"\n";
	echo '<p>',$output_element['north_degree'][0],'&deg;N, ',$output_element['west_degree'][0],'&deg;W</p>',"\n";
	echo '<div class="g_maps" id="map_canvas_',$attrib,'" style="width:70%; height:250px"></div>',"\n";
	echo '<h3>Collector</h3>',"\n";
	echo '<p>',$output_element['abbr3_collector'][0],': ',$output_element['first_name'][0],' ',$output_element['middle_name'][0],' ',$output_element['last_name'][0];
	echo '<h3>List of Speciment</h3>',"\n";
	foreach($output_element['catalog_code'] as $id => $catalog)
	{
	  echo '<table>',"\n";
	  echo '<tr>',"\n";
	  echo '<th>Catalog Code</th>',"\n";
	  echo '<td>',$catalog,'</td>',"\n";
	  echo '</tr>',"\n";
	  echo '<tr>',"\n";
	  echo '<th>Scientific Name</th>',"\n";
	  echo '<td>',$output_element['scientific_name'][$id],'</td>',"\n";
	  echo '</tr>',"\n";
	  echo '<tr>',"\n";
	  echo '<th>Container Location</th>',"\n";
	  echo '<td>',$output_element['cabinet_code'][$id],', ',$output_element['box_code'][$id],', ',$output_element['section_code'][$id],', ',$output_element['abbr4_organization'][$id],'</td>',"\n";
	  echo '</tr>',"\n";
	  echo '</table>',"\n";
	  $num_specimen++;
	}
	echo '</div>',"\n";
	$num_event++;
  }
  echo '<p>Total Number of Events: ', $num_event ,'</p>',"\n";
  echo '<p>Total Number of Specimen: ', $num_specimen ,'</p>',"\n";
?>
  </body>
</html>
