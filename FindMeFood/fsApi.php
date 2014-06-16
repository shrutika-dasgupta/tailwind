<?php
require_once "FoursquareAPI.class.php";
//print "hello hi";
$clientKey = "X0CXGZW3L0WX1VO4GTKEKXTNCRDZ3ANTQGK01RQVTRAOGKOV";
$clientSecret =  "QCBGPZTNNW2FBZS1F5P1LHMVFJKBLHPCFO3SRBWYBJJIKSLE";
$foursquare = new FoursquareAPI($clientKey, $clientSecret);

// Searching for venues nearby Montreal, Quebec
$endpoint = "venues/search";

// Prepare parameters
$params = array("section"=>"food","ll"=>"40.744749,-73.993705","v"=>"20141006","query"=>"Indian Restaurant","limit"=>"10","id");

// Perform a request to a public resource
$response = $foursquare->GetPublic($endpoint,$params);

//print "hello again";
print $response;


//$response=array();
$response=json_decode($response);

foreach ($response as $venues)
{
	
	foreach ($venues->venues as $result)
	{
		//$name=$result->name;
		$id = $result->id;
		//$distance=$result->location->distance;


		print $name. "</br>";
		print $id."</br>";
		print $distance."</br>";
/*
		$restro ="venues/".$id;
		$para = array("v"=>"20141006");
		$res = $foursquare->GetPublic($restro,$para);
		print( $res)
		."</br></br></br></br>";

*/
	}
}

//$json = json_encode($data);
//echo "<script> var array = $json; </script>"; 
?>
<!--
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
				height: 75px;	/* Gets overriden by D3-assigned height below *//*
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
-->