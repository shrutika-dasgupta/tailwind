<?php

class RestTestController extends BaseController
{
	public $restful = true;

	public function get_restro_list()
	{
		return View::make('restView.restaurant')->with('title','Restuarant Details');
	}

	public function display_list()
	{
		$user = new Restro();

		$userAllValues  = Input::all();
		Session::flash('allValues',$userAllValues);
		Redirect::to('models/restro');

		$value = $user->mainFunction();
		$result = $user ->getData();

		return View::make('restView.displayData',array('title'=>$value,'result'=>$result));
	}

	public function get_data()
	{
		$user = new Restro();

		$value = $user->getData();

		return 	View::make('restView.getResults',array('all'=>$value,'title'=>'getData'));
	}
}