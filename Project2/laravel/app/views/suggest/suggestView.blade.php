<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"> 
		<link href="/Project2/laravel/vendor/twitter/bootstrap/dist/css/bootstrap.css" rel="stylesheet"/>
	</head>

	<body>
		<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
		<script src="/Project2/laravel/vendor/twitter/bootstrap/dist/js/bootstrap.js"></script>
		<div class="container">
			<div class="jumbotron">
				<h1>Exciting New Feature Under Way!!</h1>
				<p>Would you like suggetions regarding recent trending pins?</p>
				@yield('content')
				<!-- <h1>Exciting New Feature Under Way!!</h1>
				<p>Would you like suggetions regarding recent trending pins?</p>
				{{ Form::open(array('action' => 'HomeController@goContext'));}}
				{{ Form::submit('Click Me!');}} -->
			</div>
		</div>
	</body>	
</html>