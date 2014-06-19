<!DOCTYPE html>
<html style="background-image: url('http://www.tailwindapp.com/img/bgnoise_lgblue5.png');">
<head>
	<title>Tailwind - Pinterest Marketing Suite | Sign In</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
    <!-- bootstrap -->
    <link href="/css/bootstrap.css" rel="stylesheet">
    <!-- global styles -->

    <link rel="shortcut icon" href="favicon.ico">

    <!-- this page specific styles -->
    <link rel="stylesheet" href="/css/signin.css" type="text/css" media="screen" />
    <link href="/css/style.css" rel="stylesheet">

    <!-- open sans font -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>

    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

    <script type="text/javascript">

        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', 'UA-33652774-1']);
        _gaq.push(['_setDomainName', '.tailwindapp.com']);
        _gaq.push(['_setAllowLinker', true]);
        _gaq.push(['_addIgnoredRef', 'tailwindapp.com']);
        _gaq.push(['_trackPageview']);

        (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'stats.g.doubleclick.net/dc.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
        })();

    </script>

</head>
<body >


<?= $main_content; ?>

	<!-- scripts -->
    <script src="/js/bootstrap.min.js"></script>

    <script type="text/javascript">

        jQuery(document).ready(function($){

            $("[data-toggle='tooltip']").tooltip({html: true, trigger:'hover'});
            $("[data-toggle='popover']").popover({html: true, delay: { show: 0, hide: 0 }, animation: false, trigger: 'hover'});
            $("[data-toggle='popover-click']").popover({html: true, delay: { show: 0, hide: 0 }, animation: false, trigger: 'click'});

            $("div.popover.right.in").addClass('in-front');

            $('a[data-toggle="tab"]').on('show', function () {
                $('span:regex(class,embed_board_bd$)').attr('style', function(i,s) { return s + 'overflow-y: scroll !important;box-shadow: inset 0px 0px 20px rgba(0, 0, 0, 0.1);' });
            });
        });

    </script>
    <script src="//static.getclicky.com/js" type="text/javascript"></script>
    <script type="text/javascript">
        try {
            clicky.init(66632693);
        } catch (e) {
        }
    </script>
    <noscript><p><img alt="Clicky" width="1" height="1" src="//in.getclicky.com/66632693ns.gif"/></p>
    </noscript>
</body>
</html>