<script type="text/javascript">

    jQuery(document).ready(function ($) {


        $("#example_lenth").ready(function () {
            $(".dataTables_length").html($(".dataTables_length").html().replace("records", "Pins"));
        });
        $("tr").hover(function () {
            $("div.datatable_board_label", this).css("background", "rgba(255, 255, 255, 0.75)");
        });


        /*
        /
        / Tracking - sorting
        /
        */
        $("th.datatable_category_col").click(function(){

        });

        $("th.datatable_board_col").click(function(){

        });

        $("th.datatable_repins_col").click(function(){
        });

        $("th.datatable_likes_col").click(function(){
        });

        $("th.datatable_comments_col").click(function(){
        });

        $("th.datatable_date_col").click(function(){
        });

        $("th.datatable_source_col").click(function(){
        });


        /*
         /
         / Kiss Metrics Click Tracking - Export
         /
         */
        $("a.DTTT_button_copy").click(function(){
            analytics.track('Export Pins Copy', {'Via':'Pin Inspector'});
        });

        $("a.DTTT_button_csv").click(function(){
            analytics.track('Export Pins to CSV', {'Via':'Pin Inspector'});
        });

        $("a.DTTT_button_print").click(function(){
            analytics.track('Export Pins to Print', {'Via':'Pin Inspector'});
        });

        /*
         /
         / Kiss Metrics Click Tracking - Filtering
         /
         */
        $(".column-filter-widget").eq(0).click(function(){
            analytics.track('Filter Pins', {'By':'Category','Via':'Pin Inspector'});
        });

        $(".column-filter-widget").eq(1).click(function(){
            analytics.track('Filter Pins', {'By':'Board','Via':'Pin Inspector'});
        });

        $(".more-filter-btn").eq(1).click(function(){
            analytics.track('Click More Filters Button', {'Via':'Pin Inspector'});
        });

        /*
         /
         / Kiss Metrics Click Tracking - Filtering by Date
         /
         */
        $("#date_btn_week").click(function(){
            analytics.track('Filter Pins', {'By':'Date (Last Week)','Via':'Pin Inspector'});
        });

        $("#date_btn_month").click(function(){
            analytics.track('Filter Pins', {'By':'Date (Last Month)','Via':'Pin Inspector'});
        });

        $("#date_btn_all").click(function(){
            analytics.track('Filter Pins', {'By':'Date (All-time)','Via':'Pin Inspector'});
        });

        $(".dataTables_filter.input-prepend").click(function(){
            analytics.track('Click Search Pins', {'Via':'Pin Inspector'});
        });

        setTimeout(function(){
            $('input#prependedInput').show().blur();
            $('input#prependedInput').focus();
        }, 1000);

    });

</script>