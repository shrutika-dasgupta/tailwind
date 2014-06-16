@extends('layouts.restLayout')

@section('content')
	<center>
		<h1>Find Me Food</h1>
	</center>
	<center>
	<?php
		$CatOptions = array();
		$list = array('None','Afghan Restaurant','African Restaurant','American Restaurant','Arepa Restaurant','Argentinian Resaurant','Asian Restaurant','Australian Restaurant','BBQ Joint','Bakery','Belarusian Restaurant','Bistro','Breakfast Spot','Bubble Tea Shop','Buffet','Chinese','Coffee Shop','Deli / Bodega','Dessert Shop','Diner','Donut Shop','English Restaurant','Falafel Restaurant','Food Truck','Fried Chicken Joint','Greek Restaurant','Halal Restaurant','Hot Dog Joint','Ice Cream Shop','Indian Restaurant','Indonesian Restaurant','Italian Restaurant','Japanese Restaurant','Juice Bar','Kosher Restaurant','Mac & Cheese Joint','Mexican Restaurant','Pakistani Restaurant','Ramen / Noodle House','Seafood Restaurant','Salad Place','Sandwich Place','Snack Place','Southern / Soul Food Restaurant','Spanish Restaurant','Steakhouse','Sushi Restaurant','Taco Place','Tea Room','Thai Restaurant','Vegetarian / Vegan Restaurant','Wings Joint','Frozen Yogurt');
		foreach($list as $option)
		{
			$CatOptions[$option] = $option;
		}
	?>
	{{ Form::open(array('url'=>'display','method' => 'POST')) }}
	<table>
		<tr><td>Enter what to search	:</td><td> {{ Form::select('userCategory', $CatOptions,'None') }}</td></tr>
		<tr><td>Enter Location			:</td><td> {{ Form::text('userLat','Latitude') }}</td><td>{{ Form::text('userLong','Longitude')}}</td></tr>
	</table>	
	{{ Form::submit('submit') }}
	
	</center>
	{{ Form::close() }}
@stop