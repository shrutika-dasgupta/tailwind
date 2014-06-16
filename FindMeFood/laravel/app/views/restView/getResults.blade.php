@extends('layouts.restLayout')

@section('content')
	<h1>hello everyone</h1>
	@foreach($all as $data)
		{{$data->name}}</br>
	@endforeach
@stop