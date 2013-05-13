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
location.north_degree, location.west_degree,
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
    if(count($output[$record['event_code']]['catalog_code'])==0)
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
	</style>
	<!--Google Maps-->
	<meta name="viewport" content="initial-scale=1.0, user-scalable=yes" />
    <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript">
  function initialize() {
  
    <?php
	foreach($output as $id => $element)
	{
	  echo $id.'();';
	}
	?>
	}
	<?php
	foreach($output as $id => $element)
	{
	'function '.$id.'(){
    var latlng = new google.maps.LatLng(19.701188427564357, -155.08096042690278);
    var myOptions = {
      zoom: 11,
      center: latlng,
      mapTypeId: google.maps.MapTypeId.HYBRID
    };
	var map = new google.maps.Map(document.getElementById("map_canvas_m0001"), myOptions);
    setMarkers(map, specimen);
  }

var specimen = [["Hilo1, HI", 19.701188427564357, -155.08096042690278, 4]];

function setMarkers(map, locations) {
	  
	  var image = "images/mark.png";

  for (var i = 0; i < locations.length; i++) {
    var beach = locations[i];
    var myLatLng = new google.maps.LatLng(beach[1], beach[2]);
    var marker = new google.maps.Marker({
        position: myLatLng,
        map: map
    });
  }
  
  var myLatlng = new google.maps.LatLng(19.701188427564357, -155.08096042690278);

var contentString = [
    ('')
	];

var infowindow = new google.maps.InfoWindow({
    content: contentString[1]
});

var marker = new google.maps.Marker({
    position: myLatlng,
    map: map,
    title:"'.element['locality_name'][0].'"
});

google.maps.event.addListener(marker, 'click', function() {
  infowindow.open(map,marker);
});

}'
}
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
    echo '<div class="record">';
    echo '<h2>Event Code - ',$attrib,': ',$output_element['date'][0],'</h2>';
    echo '<h3>Location</h3>';
	echo '<p>',$output_element['locality_name'][0],', ',$output_element['reserve_name'][0],', ',$output_element['island_name'][0],'</p>';
	echo '<p>',$output_element['north_degree'][0],'&deg;N, ',$output_element['west_degree'][0],'&deg;W</p>';
	echo '<div class="g_maps" id="map_canvas_',$attrib,'" style="width:70%; height:250px"></div>';
	echo '<h3>Collector</h3>';
	echo '<p>',$output_element['abbr3_collector'][0],': ',$output_element['first_name'][0],' ',$output_element['middle_name'][0],' ',$output_element['last_name'][0];
	echo '<h3>List of Speciment</h3>';
	foreach($output_element['catalog_code'] as $id => $catalog)
	{
	  echo '<table>';
	  echo '<tr>';
	  echo '<th>Catalog Code</th>';
	  echo '<td>',$catalog,'</td>';
	  echo '</tr>';
	  echo '<tr>';
	  echo '<th>Scientific Name</th>';
	  echo '<td>',$output_element['scientific_name'][$id],'</td>';
	  echo '</tr>';
	  echo '<tr>';
	  echo '<th>Container Location</th>';
	  echo '<td>',$output_element['cabinet_code'][$id],', ',$output_element['box_code'][$id],', ',$output_element['section_code'][$id],', ',$output_element['abbr4_organization'][$id],'</td>';
	  echo '</tr>';
	  echo '</table>';
	  $num_specimen++;
	}
	echo '</div>';
	$num_event++;
  }
  echo '<p>Total Number of Events: ', $num_event ,'</p>';
  echo '<p>Total Number of Specimen: ', $num_specimen ,'</p>';
?>
  </body>
</html>