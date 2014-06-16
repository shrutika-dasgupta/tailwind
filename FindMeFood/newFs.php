<?php
//connection to the database
$host="localhost"; // Host name 
$username="root"; // Mysql username 
$password="root";

// Connect to server and select database.
$pdo = new PDO('mysql:host=localhost;dbname=restros', $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


require_once "FoursquareAPI.class.php";
$key= "X0CXGZW3L0WX1VO4GTKEKXTNCRDZ3ANTQGK01RQVTRAOGKOV";
$secret="QCBGPZTNNW2FBZS1F5P1LHMVFJKBLHPCFO3SRBWYBJJIKSLE";
$foursquare = new FoursquareAPI( $key,$secret);

// Searching for venues nearby Montreal, Quebec
$endpoint = "venues/explore";

// Prepare parameters
$params = array("section"=>"food","ll"=>"40.744749, -73.993705","v"=>"20141006","limit"=>"1");
// Perform a request to a public resource
$response = $foursquare->GetPublic($endpoint,$params);
//print $response;
$result=json_decode($response);
$group = array();
$item = array();
//var_dump($result);
$group= $result->response;

foreach ($group->groups as $item)
{
	foreach ($item->items as $data) 
	{
		$id = $data->venue->id;
		$name =$data->venue->name;
		$address = $data->venue->location->address.", ".$data->venue->location->crossStreet.", ".$data->venue->location->city.", ".$data->venue->location->state;
		$distance =$data->venue->location->distance;
		$rating=$data->venue->rating;
		foreach($data->venue->categories as $cat)
		{
			$categoriesId = $cat->id;
			$categoriesName = $cat->name;
		}
		print $id."</br>".$name."</br>".$address."</br>".$distance."</br>".$rating."</br>".$categoriesId."</br>".$categoriesName."</br>";

		/*
		try
		{
			$statement = $pdo->prepare('INSERT INTO members (id, name, address,distance,categoriesId,categoriesName,rating) VALUES (:var1,:var2,:var3,:var4,:var5,:var6,:var7)');
					
			$statement->execute(array(':var1'=>$id,':var2'=>$name,':var3'=>$address,':var4'=>$distance,':var5'=>$categoriesId,':var6'=>$categoriesName,':var7'=>$rating));	 
		}
		catch ( PDOException $exception )
		{
			echo "PDO error :" . $exception->getMessage();
		}
			*/
	}
}

?>
