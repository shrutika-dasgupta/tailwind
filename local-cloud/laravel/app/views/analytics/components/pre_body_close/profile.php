<script type="text/javascript">

    jQuery(document).ready(function ($) {
//$('.feature-stat').fitText(0.75);
//$('.feature-growth span.left').fitText(0.75);
//$('.feature-growth span.right').fitText(0.75);


        $("#followers-toggle-dash").on('click', function () {
            $("#followers-toggle2").trigger('click');
            $("#followers-toggle2").trigger('click');
            $("#followers-toggle").trigger('click');
            $("#followers-toggle").trigger('click');
            $("#pins-toggle-dash").removeClass('active');
            $("#likes-toggle-dash").removeClass('active');
            $("#repins-toggle-dash").removeClass('active');
            $("#followers-toggle-dash").addClass('active');
            analytics.track('Clicked on followers box on profile page');
        });
        $("#repins-toggle-dash").on('click', function () {
            $("#repins-toggle2").trigger('click');
            $("#repins-toggle2").trigger('click');
            $("#repins-toggle").trigger('click');
            $("#repins-toggle").trigger('click');
            $("#pins-toggle-dash").removeClass('active');
            $("#repins-toggle-dash").addClass('active');
            $("#likes-toggle-dash").removeClass('active');
            $("#followers-toggle-dash").removeClass('active');
            analytics.track('Clicked on repins box on profile page');
        });
        $("#pins-toggle-dash").on('click', function () {
            $("#pins-toggle2").trigger('click');
            $("#pins-toggle2").trigger('click');
            $("#pins-toggle").trigger('click');
            $("#pins-toggle").trigger('click');
            $("#pins-toggle-dash").addClass('active');
            $("#repins-toggle-dash").removeClass('active');
            $("#likes-toggle-dash").removeClass('active');
            $("#followers-toggle-dash").removeClass('active');
            analytics.track('Clicked on pins box on profile page');
        });
        $("#likes-toggle-dash").on('click', function () {
            $("#likes-toggle2").trigger('click');
            $("#likes-toggle2").trigger('click');
            $("#likes-toggle").trigger('click');
            $("#likes-toggle").trigger('click');
            $("#pins-toggle-dash").removeClass('active');
            $("#repins-toggle-dash").removeClass('active');
            $("#likes-toggle-dash").addClass('active');
            $("#followers-toggle-dash").removeClass('active');
            analytics.track('Clicked on likes box on profile page');
        });
        $("#comments-toggle-dash").on('click', function () {
            $("#comments-toggle2").trigger('click');
            $("#comments-toggle2").trigger('click');
            $("#comments-toggle").trigger('click');
            $("#comments-toggle").trigger('click');
            $("#pins-toggle-dash").removeClass('active');
            $("#repins-toggle-dash").removeClass('active');
            $("#likes-toggle-dash").removeClass('active');
            $("#comments-toggle-dash").addClass('active');
            analytics.track('Clicked on comments box on profile page');
        });

    });

</script>
