<?php

class TestController extends BaseController{

	//Default action like a function in java
	public $restful = true; 

	public function get_index()
	{
		return View::make('restros.index')
			->with('title','Restaurants details');
	}
	
}	
