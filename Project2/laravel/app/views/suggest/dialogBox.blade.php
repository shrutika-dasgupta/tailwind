@section('content')

	{{ Form::open(array('action' => 'HomeController@goContext'));}}
	{{ Form::submit('Click Me!');}}
</div>
@stop