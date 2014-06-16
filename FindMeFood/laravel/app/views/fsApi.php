<?php
require_once "FoursquareAPI.class.php";
$foursquare = new FoursquareAPI("Z4YI1WLX2P1N3W3KQMCY12VZR3UOKRNZ3ZSJLBU4H3JDLZYA", "3UMKWJGHVGI3QHQCE5C3DACKYCWGSQDC3B3LGWAK1R02IYN0");

// Searching for venues nearby Montreal, Quebec
$endpoint = "venues/search";

// Prepare parameters
$params = array("ll"=>"40.744749,-73.993705","radius"=>"160","v"=>"20141006");

// Perform a request to a public resource
$response = $foursquare->GetPublic($endpoint,$params);
$nameAndCount=array();
$response=json_decode($response);
foreach ($response as $venue){
	foreach ($venue->venues as $name){
				$nameAndCount=array();
				$nameAndCount['name']=$name->name;
				$stats=$name->stats;
				$nameAndCount['count']=$stats->checkinsCount;
				$nameAndCountArr[]=$nameAndCount;
				$data[]=$stats->checkinsCount;
				}
	}
$json = json_encode($data);
echo "<script> var array = $json; </script>"; 
?>
 <!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>D3 Test</title>
        <script type="text/javascript" src="d3/d3.js"></script>
		<style type="text/css">
		
			div.bar {
				display: inline-block;
				width: 20px;
				height: 75px;	/* Gets overriden by D3-assigned height below */
				margin-right: 2px;
				background-color: teal;
			}
		
		</style>
    </head>
    <body>
        <script type="text/javascript">
			d3.select("body").selectAll("div")
				.data(array)
				.enter()
				.append("div")
				.attr("class", "bar")
				.style("height", function(d) {
						var barHeight = d / 100;
						return barHeight + "px";
    });
        </script>
    </body>
</html>  
