<!DOCTYPE html>
<html lang="en-US" xmlns="http://www.w3.org/1999/html">

<head>

    <title>Tailwind Pinterest Analytics <?= $page; ?> </title>
    <meta name="description" content="<?= $description; ?>">
    <meta name="author" content="<?= $author; ?>">
    <meta charset="utf-8">
    <?php if (App::environment() == 'dev'): ?>
    <meta name="cust" content="<?= $cust_id; ?>">
    <?php endif; ?>

    <link rel="shortcut icon" href="/favicon.ico">

    <link href="/css/bootstrap.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link href="/css/app.css" rel="stylesheet">
    <link href="/css/daterangepicker.css" rel="stylesheet">

    <link rel="stylesheet"
          href="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/themes/base/jquery-ui.css"/>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

    <script src="/js/clarity.js"></script>
    <script type="text/javascript" src="/js/daterangepicker.js"></script>
    <script type="text/javascript" src="/js/moment.min.js"></script>
    <script type="text/javascript" src="/js/fitText.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.js"></script>
    <script type="text/javascript" src="/js/bootstrap.min.js"></script>

    <script>

        $(document).ready(function () {

            $('.collapse.in').addClass('sticky');
            $('.slider').click(function () {

                if ($('#menu').hasClass('slid')) {
                    $('#menu').removeClass('slid');
                    $('#main').removeClass('slid');
                    $('.slider').removeClass('clicked');
                    $('.slider').addClass('unclicked');
                    $('#main').css("left","60px");
                    $('.collapse:not(.sticky)').removeClass('in');
                    //var wide = $('#main').width()+150;
                    //var wide2 = $('#main-content-scroll').width()+200;
                    //$('#main').css('width','');
                } else {
                    $('#menu').addClass('slid');
                    $('#main').addClass('slid');
                    $('.slider').removeClass('unclicked');
                    $('.slider').addClass('clicked');
                    $('#main').css("left","270px");
                    $('.collapse:not(.sticky)').addClass('in');
                    //var narrow = $('#main').width()-150;
                    //var narrow2 = $('#main-content-scroll').width()-200;
                    //$('#main').width(narrow);
                }
            });

//

        });


        $(document).ready(function () {

            //Resize window based on screen size to fit to all elements on the screen correctly


            $(window).resize(function () {

                var windowWidth = parseInt($('.wrapper').width());
                var widthPercent = (windowWidth / 1400) * 100;
                var widthPercentCollapse = (windowWidth / 1250) * 100;
                var widthPercentPrint = widthPercent + "%";
                var widthPercentCollapsePrint = widthPercentCollapse + "%";

                if (windowWidth < 1400 && windowWidth > 766) {
                    $('#main-content-scroll').css('-webkit-transform', 'scale(' + widthPercentPrint + ')');
                    $('#main-content-scroll').css('-moz-transform', 'scale(' + widthPercentPrint + ')');
                    $('#main-content-scroll').css('-ms-transform', 'scale(' + widthPercentPrint + ')');
                    $('#menu').removeClass('slid');
                    $('#main').removeClass('slid');
                }

                if (windowWidth < 1025 && windowWidth > 766) {
                    $('#main').css("left","60px");
                }

            });


            //Resize page elements based on screen size to fit


            $(window).load(function () {

                var windowWidth = parseInt($('.wrapper').width());
                var widthPercent = (windowWidth / 1400) * 100;
                var widthPercentCollapse = (windowWidth / 1250) * 100;
                var widthPercentPrint = widthPercent + "%";
                var widthPercentCollapsePrint = widthPercentCollapse + "%";

                if (windowWidth < 1400 && windowWidth > 1024) {
                    $('#main-content-scroll').css('-webkit-transform', 'scale(' + widthPercentPrint + ')');
                    $('#main-content-scroll').css('-moz-transform', 'scale(' + widthPercentPrint + ')');
                    $('#main-content-scroll').css('-ms-transform', 'scale(' + widthPercentPrint + ')');
                    $('#menu').removeClass('slid');
                    $('#main').removeClass('slid');
                }


                if (windowWidth < 1025 && windowWidth > 766) {
                    if (!$('#main').hasClass('slid')) {
                        $('#main-content-scroll').css('-webkit-transform', 'scale(' + widthPercentCollapsePrint + ')');
                        $('#main-content-scroll').css('-moz-transform', 'scale(' + widthPercentCollapsePrint + ')');
                        $('#main-content-scroll').css('-ms-transform', 'scale(' + widthPercentCollapsePrint + ')');
                        console.log(widthPercentCollapsePrint);
                    } else {
                        $('#main-content-scroll').css('-webkit-transform', 'scale(' + widthPercentPrint + ')');
                        $('#main-content-scroll').css('-moz-transform', 'scale(' + widthPercentPrint + ')');
                        $('#main-content-scroll').css('-ms-transform', 'scale(' + widthPercentPrint + ')');
                        console.log(widthPercentPrint);
                    }

                    $('#main-content-scroll').click(function () {
                        if ($('#menu').hasClass('slid')) {
                            $('#menu').removeClass('slid');
                            $('#main').removeClass('slid');
                            $('.slider').removeClass('clicked');
                            $('.slider').addClass('unclicked');
                            //var wide = $('#main').width()+150;
                            //$('#main').css('width','');
                        }
                    });
                }

            });
        });

    </script>
    <!-- Le HTML5 shim, for IE6-8 support of HTML elements -->
    <!--[if lt IE 9]>
    <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->


    <?= $head; ?>
</head>

<body id="<?= $body_id; ?>">

<?= $loading_overlay; ?>

<?= $top_bar_alert; ?>



<div class="wrapper">
    <div id="inner-wrapper">
        <?= $side_navigation; ?>

        <div id="main">
            <?= $top_navigation; ?>

            <div id="main-content-scroll">
                <div class="main-headline" style='padding-bottom:25px'>
                    <div></div>
                </div>

                <?= $alert; ?>

                <?= $sub_navigation; ?>

                <?= $main_content; ?>

                <footer>
                    <hr style="margin-bottom: 5px;" />
                    <div class="row-fluid">

                        <div class="span8">
                            <small class="muted">
                                Copyright &copy; <?= date('Y') ?> Tailwind. All
                                Rights Reserved.
                            </small>
                        </div>
                        <div class="span4 text-right"><small class="muted"> <?= $last_calculated; ?> </small></div>
                    </div>

                </footer>
            </div>
        </div>
    </div>
</div>

<!-- Include Google's JS API script needed for google charts api -->
<script type="text/javascript" src="//www.google.com/jsapi"></script>
<?= $trada;?>

<?= $pre_body_close; ?>

</body>
</html>