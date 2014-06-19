<!DOCTYPE html>
<html style="background-image: url('http://www.tailwindapp.com/img/bgnoise_lgblue5.png');">
<head>
	<title>Tailwind Admin Dashboard</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
    <!-- bootstrap -->
    <link href="css/bootstrap/bootstrap.css" rel="stylesheet">
    <link href="css/bootstrap/bootstrap-responsive.css" rel="stylesheet">
    <link href="css/bootstrap/bootstrap-overrides.css" type="text/css" rel="stylesheet">

    <!-- global styles -->
    <link rel="stylesheet" type="text/css" href="css/layout.css">
    <link rel="stylesheet" type="text/css" href="css/elements.css">
    <link rel="stylesheet" type="text/css" href="css/icons.css">

    <!-- libraries -->
    <link rel="stylesheet" type="text/css" href="css/lib/font-awesome.css">
    
    <!-- this page specific styles -->
    <link rel="stylesheet" href="css/compiled/signin.css" type="text/css" media="screen" />

    <!-- open sans font -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>

    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>
<body>



    <div class="row-fluid login-wrapper">
        <a href="/">
            <img class="logo" src="http://analytics.tailwindapp.com/img/tailwind-logo-white.png">
        </a>

        <div class="span4 box">
            <div class="content-wrap">
                <h6>Log in</h6>
                {{ Form::open(array('url' => '/login')) }}
                <!-- check for login error flash var -->
                @if (Session::has('flash_error'))
                <div class="alert alert-error">
                    <i class="icon-remove-sign"></i>
                    {{ Session::get('flash_error') }}
                </div>
                @endif
                @if (Session::has('flash_notice'))
                <div class="alert alert-info">
                    <i class="icon-exclamation-sign"></i>
                    {{ Session::get('flash_notice') }}
                </div>
                @endif
                <input class="span12" type="text" name="email" placeholder="E-mail address">
                <input class="span12" type="password" name="password" placeholder="Your password">
                <a href="#" class="forgot">Forgot password?</a>
                <div class="remember">
                    <input id="remember-me" type="checkbox">
                    <label for="remember-me">Remember me</label>
                </div>
                <input type ="submit" class="btn-flat primary login" value="Login" />
                {{ Form::close() }}
            </div>
        </div>

    </div>

	<!-- scripts -->
    <script src="http://code.jquery.com/jquery-latest.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>