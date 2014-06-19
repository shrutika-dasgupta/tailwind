<!DOCTYPE html>
<!--[if IE 8]>
<html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" lang="en"> <!--<![endif]-->

<head>

    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width"/>
    <meta property="og:locale" content="en_US">
    <meta property="og:type" content="website">


    <link rel="stylesheet" href="/css/app.css"/>
    <!--<link rel="stylesheet" href="http://d3iavkk36ob651.cloudfront.net/css/app.css"/> -->

    <link rel="shortcut icon" href="favicon.ico">

    <script src="http://d3iavkk36ob651.cloudfront.net/js/vendor/custom.modernizr.js"></script>


    <!-- GOOGLE ANALYTICS SCRIPT -->
    <script type="text/javascript">

        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', 'UA-33652774-1']);
        _gaq.push(['_setDomainName', '.tailwindapp.com']);
        _gaq.push(['_setAllowLinker', true]);
        _gaq.push(['_addIgnoredRef', 'tailwindapp.com']);
        _gaq.push(['_trackPageview']);
        setTimeout('_gaq.push([\'_trackEvent\', \'NoBounce\', \'Over 30 seconds\'])',30000);


        (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'stats.g.doubleclick.net/dc.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
        })();

    </script>
    <!-- END OF GOOGLE ANALYTICS SCRIPT -->

</head>
<body id="error">

<?= $navigation; ?>
<div id="main-content-wrapper">
    <?= $main_content; ?>
</div>
<?= $footer; ?>

<script>
    document.write('<script src=http://d3iavkk36ob651.cloudfront.net' +
        ('__proto__' in {} ? '/js/vendor/jquery' : '/js/vendor/jquery') +
        '.js><\/script>')
</script>

<script src="http://d3iavkk36ob651.cloudfront.net/js/vendor/foundation.min.js"></script>

<script>

    jQuery(document).ready(function () {
        jQuery(".btn-cta-feature").click(function () {
            jQuery(".call-to-action input").eq(0).focus();
        })

        jQuery(".btn-cta-feature.alt").click(function () {
            jQuery(".call-to-action input").eq(1).focus();
        })

        jQuery(".btn-cta-header").click(function () {
            jQuery(".call-to-action input").eq(0).focus();
            window.scrollTo(0, 0);
        })
    })

</script>

<script>
    jQuery(document).foundation();
</script>


<!--Clicky-->
<script type="text/javascript">
    var clicky_site_ids = clicky_site_ids || [];
    clicky_site_ids.push(66623287);
    (function () {
        var s = document.createElement('script');
        s.type = 'text/javascript';
        s.async = true;
        s.src = '//static.getclicky.com/js';
        ( document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0] ).appendChild(s);
    })();
</script>
<noscript><p><img alt="Clicky" width="1" height="1" src="//in.getclicky.com/66623287ns.gif"/></p>
</noscript>
<!--Clicky-->

<script type="text/javascript">
    adroll_adv_id = "LAABJA7JOFBWRB4SN34IBR";
    adroll_pix_id = "SLOX6YNM2RDWPKRE2UGJAM";

    (function () {
        var oldonload = window.onload;
        window.onload = function(){
            __adroll_loaded=true;
            var scr = document.createElement("script");
            var host = (("https:" == document.location.protocol) ? "https://s.adroll.com" : "http://a.adroll.com");
            scr.setAttribute('async', 'true');
            scr.type = "text/javascript";
            scr.src = host + "/j/roundtrip.js";
            ((document.getElementsByTagName('head') || [null])[0] ||
                document.getElementsByTagName('script')[0].parentNode).appendChild(scr);
            if(oldonload){oldonload()}};
    }());
</script>

</body>
</html>