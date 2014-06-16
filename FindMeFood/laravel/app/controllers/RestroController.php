<?php

class RestroController extends BaseController
{
	public $restful = true;

	public function get_restro_list()
	{
		return View::make('restros.index')->with('title','Restuarant Details');
	}
}