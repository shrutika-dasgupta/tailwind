<?php

use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class Restro extends Eloquent 
{
	public $table = 'members';

	//protected $hidden = array('password', 'remember_token');
	protected $fillable = array('id', 'name', 'address','distance','rating','categoriesId','categoriesName');

	public function mainFunction()
	{
		Restro::truncate();
		$restro = new Restro();
		//User Credentials
		$key= "X0CXGZW3L0WX1VO4GTKEKXTNCRDZ3ANTQGK01RQVTRAOGKOV";
		$secret="QCBGPZTNNW2FBZS1F5P1LHMVFJKBLHPCFO3SRBWYBJJIKSLE";

		// Searching for venues nearby Montreal, Quebec
		$endpoint = "venues/explore";

		//The Values that are returned from the View Form
		$userAllValues = Session::get('allValues');
		
		$params = $restro->makeAQuery($userAllValues);
		$response = $restro->callFoursqAPI($endpoint,$params,$key,$secret);
		$Array_ParsedValues = $restro->parseResponse($response);

		foreach($Array_ParsedValues as $oneValue)
		{	
			$final = $restro->inputIntoDatabase($oneValue);
		}
		return $final;
	}

	public function callFoursqAPI($endpoint, $params, $key, $secret)
	{
		require_once "FoursquareAPI.class.php";
		$foursquare = new FoursquareAPI($key,$secret);

		// Perform a request to a public resource
		$response = $foursquare->GetPublic($endpoint,$params);

		return $response;
	}

	public function makeAQuery($userAllValues)
	{
		
		//Data retriieved from the user
		$userCategory = $userAllValues['userCategory'];
		$userLat = $userAllValues['userLat'];
		$userLong = $userAllValues['userLong'];

		$co_od = $userLat.", ".$userLong;

		// Prepare parameters
		if($userCategory =='None')
		{
			$params = array("section"=>"food","ll"=>$co_od,"v"=>"20141006","limit"=>"50");	
		}
		else
		{
			$params = array("query"=>$userCategory,"ll"=>$co_od,"v"=>"20141006","limit"=>"50");
		}

		return $params; 
	}

	public function parseResponse($response)
	{	
		$result=json_decode($response);
		$group = array();
		$item = array();
		$cat = array();
		$rating = 0.0;
		$address = '';

		$group= $result->response;

		$Array_ParsedValues= array();

		foreach ($group->groups as $item)
		{
			foreach ($item->items as $data) 
			{
				try 
				{
					$id = $data->venue->id;
					$name =$data->venue->name;
					$location = $data->venue->location;
					if($location->address)
						{$address = $location->address.", ".$location->city.", ".$location->state;}
					else
						{$address = '';}
					$distance =$location->distance;
					if(empty($data->venue->rating))
						$rating = 1.0;
					else
						{$rating=$data->venue->rating;}
					foreach($data->venue->categories as $cat)
					{
						$categoriesId = $cat->id;
						$categoriesName = $cat->name;
					}

					$parsedValues = array();
					$parsedValues['id'] = $id;
					$parsedValues['name'] = $name;
					$parsedValues['address'] = $address;
					$parsedValues['distance'] = $distance;
					$parsedValues['rating'] = $rating;
					$parsedValues['categoriesId'] = $categoriesId;
					$parsedValues['categoriesName'] = $categoriesName;

					array_push($Array_ParsedValues, $parsedValues);
				} 
				catch (Exception $e) {
					
				}
			}
		}
		return $Array_ParsedValues;
	}

	public function inputIntoDatabase($parsedValues)
	{
		$id = $parsedValues['id'];
		$name = $parsedValues['name'];
		$address = $parsedValues['address'];
		$distance = $parsedValues['distance'];
		$categoriesId = $parsedValues['categoriesId'];
		$categoriesName = $parsedValues['categoriesName'];
		$rating = $parsedValues['rating'];
		
		try
		{
			$rest = Restro::create(array('id'=>$id,'name'=>$name,'address'=>$address,'distance'=>$distance,'categoriesId'=>$categoriesId,'categoriesName'=>$categoriesName,'rating'=>$rating));
		 	$rest ->save(); 
		}
		catch ( PDOException $exception )
		{
			echo "PDO error :" . $exception->getMessage();
		}
		return "Input made in the database";
	}
	
	public function getData()
	{
		$results = Restro::orderBy('rating','DESC')->get();

		return $results;
	}

}			