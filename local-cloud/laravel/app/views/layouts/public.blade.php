<!DOCTYPE html>
<!--[if IE 8]>
<html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" lang="en"> <!--<![endif]-->

<head>
    <?= $top_head; ?>

    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width"/>
    <meta property="og:locale" content="en_US">
    <meta property="og:type" content="website">
    

    <link rel="stylesheet" href="/css/app.css"/>
    <!--<link rel="stylesheet" href="http://d3iavkk36ob651.cloudfront.net/css/app.css"/> -->

    <link rel="shortcut icon" href="favicon.ico">

    <script src="http://d3iavkk36ob651.cloudfront.net/js/vendor/custom.modernizr.js"></script>

    <?= $head_append; ?>

    <script type="text/javascript">
        window.analytics=window.analytics||[],window.analytics.methods=["identify","group","track","page","pageview","alias","ready","on","once","off","trackLink","trackForm","trackClick","trackSubmit"],window.analytics.factory=function(t){return function(){var a=Array.prototype.slice.call(arguments);return a.unshift(t),window.analytics.push(a),window.analytics}};for(var i=0;i<window.analytics.methods.length;i++){var key=window.analytics.methods[i];window.analytics[key]=window.analytics.factory(key)}window.analytics.load=function(t){if(!document.getElementById("analytics-js")){var a=document.createElement("script");a.type="text/javascript",a.id="analytics-js",a.async=!0,a.src=("https:"===document.location.protocol?"https://":"http://")+"cdn.segment.io/analytics.js/v1/"+t+"/analytics.min.js";var n=document.getElementsByTagName("script")[0];n.parentNode.insertBefore(a,n)}},window.analytics.SNIPPET_VERSION="2.0.9",

        window.analytics.load("<?= $segmentio_write_key;?>");
        window.analytics.page();

        analytics.ready(function () {
            ga('require', 'linker');
            ga('linker:autoLink', ['tailwindapp.com', 'chargify.com']);

            setTimeout(function() {
                analytics.track('Did not bounce', {
                    category: 'NoBounce',
                    label: 'Over 30 Seconds',
                });
            },30000);
        });
    </script>

</head>
<body id="<?= $page_name; ?>">

<?= $navigation; ?>
<div id="main-content-wrapper">
    <?= $main_content; ?>
    <?= $pop_up_cta; ?>
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

<?= $pre_body_close; ?>

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

<!-- Google Code for Remarketing Tag -->
<!--------------------------------------------------
Remarketing tags may not be associated with personally identifiable information or placed on pages related to sensitive categories. See more information and instructions on how to setup the tag on: http://google.com/ads/remarketingsetup
--------------------------------------------------->
<script type="text/javascript">
    /* <![CDATA[ */
    var google_conversion_id = 1005031077;
    var google_custom_params = window.google_tag_params;
    var google_remarketing_only = true;
    /* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
    <div style="display:inline;">
        <img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/1005031077/?value=0&amp;guid=ON&amp;script=0"/>
    </div>
</noscript>
<!-- End of Google Code for Remarketing Tag -->

</body>
</html>