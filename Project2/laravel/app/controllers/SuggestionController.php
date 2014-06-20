<?php
class SuggestionController extends BaseController
{
	public function suggestPins()
	{

		return View::make('suggestion.suggestionView',array('title'=>'done'));
	}
}