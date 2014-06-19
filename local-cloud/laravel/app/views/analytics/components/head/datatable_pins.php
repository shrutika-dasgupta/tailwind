<?php

ini_set('memory_limit', '200M');
/**
 * @author Alex
 * Date: 9/1/13 11:27 AM
 * 
 */

if (isset($dataTable_pins) && $dataTable_pins) {	?>


    <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/themes/base/jquery-ui.css" />
    <!--<script src='http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js'></script>
    <script src='jquery/uijs/jquery.ui.datepicker.js'></script>-->

    <script type="text/javascript" src="/js/datatables/media/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="/js/datatables/extras/TableTools/media/js/TableTools.min.js"></script>
    <script type="text/javascript" src="/js/datatables/extras/bootstrap/paging.js"></script>
    <script type="text/javascript" src="/js/datatables/extras/bootstrap/DT_bootstrap.js"></script>
    <script type="text/javascript" src="/js/datatables/extras/RowGrouping/jquery.dataTables.rowGrouping.js"></script>
    <script type="text/javascript" src="/js/datatables/extras/ColumnFilterWidgets/media/js/ColumnFilterWidgets.js"></script>
    <script type="text/javascript" src="/js/jquery.sparkline.min.js"></script>

    <?php if($report_url == "pins/owned"){ ?>
        <script>
            $(function() {
                $( "#date_min" ).datepicker();
            });
            $(function() {
                $( "#date_max" ).datepicker();
            });

        </script>
    <?php } ?>
    <!--		<link rel="stylesheet" href="includes/datatables/media/css/jquery.dataTables.css">-->
    <link rel="stylesheet" href="/css/DT_bootstrap.css">

    <script type="text/javascript" charset="utf-8">
    /* Data set - can contain whatever information you want */

    var aDataSet = [

        <?=$datatable_js;?>
    ];

    </script>
    <script type="text/javascript" charset="utf-8">



    $(document).ready(function() {
        $('#dynamic').html( '<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-hover table-bordered"  id="example"></table>' );
        $.extend( true, $.fn.DataTable.TableTools.DEFAULTS.oTags, {
            "collection": {
                "container": "ul",
                "button": "li",
                "liner": "a"
            }
        } );

    <?php if($report_url == "pins/owned"){ ?>
        $.fn.dataTableExt.afnFiltering.push(
            function( oSettings, aData, iDataIndex ) {
                var iMin = parseDate(document.getElementById('date_min').value);
                var iMax = parseDate(document.getElementById('date_max').value);
                var iVersion = parseDate(aData[8]);

                if( iMin != ""){
                    iMin = iMin.getTime()/1000;
                }
                if( iMax != ""){
                    iMax = iMax.getTime()/1000;
                }
                if( iVersion != ""){
                    iVersion = iVersion.getTime()/1000;
                }

                if ( iMin == "" && iMax == "" )
                {
                    return true;
                }
                else if ( iMin == "" && iVersion <= iMax )
                {
                    return true;
                }
                else if ( iMin <= iVersion && "" == iMax )
                {
                    return true;
                }
                else if ( iMin <= iVersion && iVersion <= iMax )
                {
                    return true;
                }
                return false;
            }
        );
    <?php } ?>

        $.fn.dataTableExt.afnFiltering.push(
            function( oSettings, aData, iDataIndex ) {
                var iMin = document.getElementById('repin_min').value*1;
                var iMax = document.getElementById('repin_max').value*1;
                var iVersion = aData[6] == "-" ? 0 : aData[5]*1;
                if ( iMin == "" && iMax == "" )
                {
                    return true;
                }
                else if ( iMin == "" && iVersion <= iMax )
                {
                    return true;
                }
                else if ( iMin <= iVersion && "" == iMax )
                {
                    return true;
                }
                else if ( iMin <= iVersion && iVersion <= iMax )
                {
                    return true;
                }
                return false;
            }
        );

        $.fn.dataTableExt.afnFiltering.push(
            function( oSettings, aData, iDataIndex ) {
                var iMin = document.getElementById('like_min').value*1;
                var iMax = document.getElementById('like_max').value*1;
                var iVersion = aData[7] == "-" ? 0 : aData[6]*1;
                if ( iMin == "" && iMax == "" )
                {
                    return true;
                }
                else if ( iMin == "" && iVersion <= iMax )
                {
                    return true;
                }
                else if ( iMin <= iVersion && "" == iMax )
                {
                    return true;
                }
                else if ( iMin <= iVersion && iVersion <= iMax )
                {
                    return true;
                }
                return false;
            }
        );

        $.fn.dataTableExt.afnFiltering.push(
            function( oSettings, aData, iDataIndex ) {
                var iMin = document.getElementById('comment_min').value*1;
                var iMax = document.getElementById('comment_max').value*1;
                var iVersion = aData[8] == "-" ? 0 : aData[7]*1;
                if ( iMin == "" && iMax == "" )
                {
                    return true;
                }
                else if ( iMin == "" && iVersion <= iMax )
                {
                    return true;
                }
                else if ( iMin <= iVersion && "" == iMax )
                {
                    return true;
                }
                else if ( iMin <= iVersion && iVersion <= iMax )
                {
                    return true;
                }
                return false;
            }
        );

        jQuery.extend( jQuery.fn.dataTableExt.oSort, {
            "num-html-pre": function ( a ) {
                var x = a.replace( /<.*?>/g, "" );
                return parseFloat( x );
            },

            "num-html-asc": function ( a, b ) {
                return ((a < b) ? -1 : ((a > b) ? 1 : 0));
            },

            "num-html-desc": function ( a, b ) {
                return ((a < b) ? 1 : ((a > b) ? -1 : 0));
            }
        });


        $.extend( $.fn.dataTableExt.oStdClasses, {
            "sWrapper": "dataTables_wrapper form-inline"
        } );
        var oTable = $('#example').dataTable( {
            "aaData": aDataSet,
            "bAutoWidth": false,
            "iDisplayLength": 50,
            "aoColumns": [
                { "sTitle": "Category", "sClass": "datatable_category_col", "bVisible": false },
                { "sTitle": "Board ID", "bVisible": false},
                { "sTitle": "Board Name", "bVisible": false  },
                { "sTitle": "Board", "sClass": "datatable_board_col", "sType": "string",
                    "mRender": function ( data, type, full ) {
                        return '<a href="http://pinterest.com'+data+'" target="_blank"></a>';
                    }
                },
                { "sTitle": "Pin",
                    "sClass": "datatable_img_col",
                    "bSortable": false,
                    "mRender": function ( data, type, full ) {
                        return '<div><a class="pin-img" href="http://pinterest.com/pin/'+data+'" target="_blank"><img></a></div>';
                    }
                },
                { "sTitle": "Repins", "sClass": "datatable_repins_col", "asSorting": [ "desc", "asc" ], "sType": "num-html" },
                { "sTitle": "Likes", "sClass": "datatable_likes_col", "asSorting": [ "desc", "asc" ]  },
                { "sTitle": "Comments", "sClass": "datatable_comments_col", "asSorting": [ "desc", "asc" ]  },
                { "sTitle": "Date Pinned", "sClass": "datatable_date_col", "asSorting": [ "desc", "asc" ]  },
                { "sTitle": "Source", "sClass": "datatable_source_col", "bVisible": false, "bSortable": false
//                    "mRender": function ( data, type, full ) {
//                        var pin_domain = data.replace('http://','');
//                        var first_slash = pin_domain.search('/');
//                        if(first_slash != -1){
//                            pin_domain = pin_domain.substring(0,first_slash);
//                        }
//                        if (pin_domain.length > 16){
//                            var pin_domain_text = pin_domain.substring(0,14) + "..";
//                        } else {
//                            var pin_domain_text = pin_domain;
//                        }
//                        return '<a href="' + data + '" target="_blank" data-toggle="tooltip" data-original-title="' + pin_domain + '" data-container="body" data-placement="top">' + pin_domain_text + '&nbsp; <i class="icon-new-tab"></i></a>';
//
//                    }
                },
                { "sTitle": "Pin Image URL","bVisible": false },
                { "sTitle": "Pin Description","bVisible": false },
                { "sTitle": "Analyze", "sClass": "datatable_pin_context_col", "bSortable": false}
            ],
            "aaSorting": [[5,'desc']],
            "sDom": "<'row-fluid'<'pull-left'Wf>><'clearfix'>l<?=$export_insert;?>irt<'row-fluid'<'span6 pull-left'l><'span6 pull-right'p>>",
            "sPaginationType": "bootstrap",
            "bDeferRender": true,
            "bProcessing": true,
            "bStateSave": false,
            "oColumnFilterWidgets": {
                "aiExclude": [ 1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                "bGroupTerms": true
            },
            "oTableTools": {
                "aButtons": [
                    <?php if ($report_url == "pins/owned") { ?>
                    {
                        "sExtends": "copy",
                        "sToolTip": "Copy Data to your Clipboard",
                        "fnCellRender": function ( sValue, iColumn, nTr, iDataIndex ) {
                            // Append the text 'TableTools' to column 5
                            if ( iColumn === 4 || iColumn === 3 || iColumn == 9 ) {
                                return sValue.replace(/<a href=/,'').replace(/ target.*>/, '').replace("\"",'').replace("\"",'');
                            }
                            return sValue;
                        },
                        "mColumns": [ 0, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 ],
                        "sButtonText": "Copy <i class='icon-export'></i> ",
                        "sButtonClass": "btn-mini"
                    },{
                        "sExtends": "print",
                        "sButtonText": "Print <i class='icon-printer'></i>",
                        "sButtonClass": "btn-mini"
                    },
                    <?php } ?>
                    {
                        "sExtends": "csv",
                        "sToolTip": "Save as CSV",
                        "fnCellRender": function ( sValue, iColumn, nTr, iDataIndex ) {
                            // Append the text 'TableTools' to column 5
                            if ( iColumn === 4 || iColumn === 3 || iColumn == 9 ) {
                                return sValue.replace(/<a href=/,'').replace(/ target.*>/, '').replace("\"",'').replace("\"",'');
                            }
                            return sValue;
                        },
                        "mColumns": [ 0, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 ],
                        "sButtonText": "Export <i class='icon-new-tab'></i>",
                        "sButtonClass": "btn-mini"
                    }
                ],
                "sSwfPath": "/js/datatables/extras/TableTools/media/swf/copy_csv_xls.swf"
            },
            "fnCreatedRow": function ( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {

                //loads in image from an invisible column into the pin column
                //this keeps all images from being loaded right away, and only loads the ones displayed on screen
                $('td.datatable_img_col a.pin-img img',nRow).attr("src",aData[10]);

                //add popover with pin description data to each image (must be done in callback)
                $('td.datatable_img_col a.pin-img',nRow).attr("data-toggle","popover");
                $('td.datatable_img_col a.pin-img',nRow).attr("data-content",aData[11]);
                $('td.datatable_img_col a.pin-img',nRow).attr("data-placement","left");
                $('td.datatable_img_col a.pin-img',nRow).attr("data-container","body");
                $('td.datatable_img_col a.pin-img',nRow).attr("data-trigger","hover");

                $('td.datatable_board_col a',nRow).text(aData[2]);
                $('td.datatable_board_col a',nRow).append(" (" + aData[0] + ") ");

                $('[data-toggle=popover]').popover({
                    placement:'left',
                    delay: { show: 0, hide: 0 },
                    animation: false
                });
                $('[data-toggle=tooltip]').tooltip({});

               /**
                * Add domain source link underneath the pin image
                */
                var pin_domain = aData[9].replace('http://','');
                var first_slash = pin_domain.search('/');
                if(first_slash != -1){
                    pin_domain = pin_domain.substring(0,first_slash);
                }
                if (pin_domain.length > 16){
                    var pin_domain_text = pin_domain.substring(0,14) + "..";
                } else {
                    var pin_domain_text = pin_domain;
                }
                var domain_link = '<div><a href="' + aData[9] + '" target="_blank" data-toggle="tooltip" data-original-title="' + pin_domain + '" data-container="body" data-placement="left">' + pin_domain_text + '&nbsp; <i class="icon-new-tab"></i></a></div>';

                $('td.datatable_img_col',nRow).append("" + domain_link + "");


                /**
                 * Handle adding pin history + action details to the "Action" columns based
                 * on plan permission
                 */
                <?php if($history_enabled){ ?>

                    if(aData[5]>0 || aData[6]>0 || aData[7]>0){
                        $('.datatable_pin_context_col',nRow).append('<a href="#pinHistoryModal" role="button" data-toggle="modal" class="history-link">' +
                            '<div class="history-btn" onclick="ga(\'send\', \'Feature Request\', \'Pin History\', \'Click\');">' +
                            '<button class="btn"><i class="icon-uni0430"></i> See Pin Engagement History</button></div></a>');
                    }

                    //add pin_id info to the pin history button to ensure AJAX returns data for the appropriate pin
                    $('a.history-link',nRow).click(function(){
                        $('#pinHistoryModal .modal-body').html('<div class="history"></div>');
                        $('.history').html('<br><img src="/img/loading.gif">');
                        getPinHistory(aData[4], nRow);
                    });

                <?php } else { ?>

                    if(aData[5]>0 || aData[6]>0 || aData[7]>0){
                        $('.datatable_pin_context_col',nRow).append('<div class="history-message" data-toggle="popover" data-container="body" data-placement="top" data-content="<img src=\'/img/pin_history.png\'>"></div>').css({
                            'position':'relative'
                        });
                    }

                    $('.history-message',nRow).css({
                        'min-width':'118px',
                        'cursor':'default'
                    });
                    $('.history-message',nRow).html('<div class="history-message-text"><strong>Pro Plan <i class="icon-lock"></i></strong></div> <i class="icon-uni0430"></i>&nbsp; Analyze this pin\'s progress<br><i class="icon-user-add"></i>&nbsp; Engage Fans who repinned this<br><br>' +
                        '<a class=\"btn-link\" href=\"/upgrade?ref=pin_history\"><button class=\"btn btn-success\"><i class=\"icon-arrow-right\"></i> See Plans & Pricing</button></a>');

                <?php } ?>



            },
            "fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {



                //add pin history popover if feature not enabled for this user


                //add pin_history button to each row, only if professional account, and if pin has engagement

                $('tr').hover(
                    function(){
                        $(this).find('.history-message, .history-message div.history-message-text').addClass('hover');
                        $('td.datatable_pin_context_col', this).css('background-color', 'white');
                    },
                    function(){
                        $(this).find('.history-message, .history-message div.history-message-text').removeClass('hover');
                        $('td.datatable_pin_context_col', this).css('background-color', 'none');
                        $('.popover').css('display', 'none');
                    }
                );

            },
            "fnDrawCallback": function (nRow, aData) {
                $("#example_info").ready(function () {
                    $(".dataTables_info").html($(".dataTables_info").html().replace("entries", "Pins"));
                });
                $("#example_info").ready(function () {
                    $(".dataTables_info").html($(".dataTables_info").html().replace("entries", "Pins"));
                });
            }

        });

        //infinite scroll options parameters for oTable dataTable variable above
//		"bScrollInfinite": true,
//		"bScrollCollapse": true,
//		"sScrollY": "500px"


//		var oTableTools = new TableTools( oTable, {
//			"buttons": [
//					"copy",
//					"print",
//					"csv"
//				],
//			"sSwfPath": "includes/datatables/extras/TableTools/media/swf/copy_csv_xls.swf"
//		});

        oTable.fnResetAllFilters();

        //$('#export').before( oTableTools.dom.container );

        /* Add event listeners to the two range filtering inputs */
//		$('#date_min').datepicker().onClose( function() { oTable.fnDraw(); } );
//		$('#date_max').datepicker().onClose( function() { oTable.fnDraw(); } );

        <?php if($report_url == "pins/owned"){ ?>
            $( "div#ui-datepicker").mouseout(function(){ oTable.fnDraw(); });
            $('#date_min').keyup( function() { oTable.fnDraw(); } );
            $('#date_max').keyup( function() { oTable.fnDraw(); } );

            $( "#date_min" ).change( function() { oTable.fnDraw(); } );
            $( "#date_max" ).change( function() { oTable.fnDraw(); } );


            $('#date_btn_week').click( function() {
                $( "#date_min" ).val( '<?=$last_week_date;?>' );
                $( "#date_max" ).val( '<?=$today_date_print;?>' );
                oTable.fnDraw();
            } );
            $('#date_btn_month').click( function() {
                $( "#date_min" ).val( '<?=$last_month_date;?>' );
                $( "#date_max" ).val( '<?=$today_date_print;?>' );
                oTable.fnDraw();
            } );
            $('#date_btn_all').click( function() {
                $( "#date_min" ).val( '<?=$min_date_print;?>' );
                $( "#date_max" ).val( '<?=$max_date_print;?>' );
                oTable.fnDraw();
            } );


            $( "#date_min" ).val( '<?=$min_date_print;?>' );
            $( "#date_max" ).val( '<?=$max_date_print;?>' );

        <?php } ?>

        /* Add event listeners to the two range filtering inputs */
        //$('#repin_min').keyup( function() { oTable.fnDraw(); } );
        //$('#repin_max').keyup( function() { oTable.fnDraw(); } );


        $( "#repin-slider-range" ).slider({
            "range": true,
            "step": 1,
            "min": 0,
            "max": <?=$max_repins;?>,
            "values": [ 0, <?=$max_repins;?> ],
            "slide": function( event, ui ) {
                $( "#repin_min" ).val( ui.values[ 0 ]);
                $( "#repin_max" ).val( ui.values[ 1 ]);
                $( "#amount-repin" ).text( ui.values[ 0 ] + " - " + ui.values[ 1 ]);
                $('#repin-slider-range').on('slidechange', function() { oTable.fnDraw(); } );
            }
        });

        $( "#like-slider-range" ).slider({
            "range": true,
            "step": 1,
            "min": 0,
            "max": <?=$max_likes;?>,
            "values": [ 0, <?=$max_likes;?> ],
            "slide": function( event, ui ) {
                $( "#like_min" ).val( ui.values[ 0 ]);
                $( "#like_max" ).val( ui.values[ 1 ]);
                $( "#amount-like" ).text( ui.values[ 0 ] + " - " + ui.values[ 1 ]);
                $('#like-slider-range').on('slidechange', function() { oTable.fnDraw(); } );
            }
        });

        $( "#comment-slider-range" ).slider({
            "range": true,
            "step": 1,
            "min": 0,
            "max": <?=$max_comments;?>,
            "values": [ 0, <?=$max_comments;?> ],
            "slide": function( event, ui ) {
                $( "#comment_min" ).val( ui.values[ 0 ]);
                $( "#comment_max" ).val( ui.values[ 1 ]);
                $( "#amount-comment" ).text( ui.values[ 0 ] + " - " + ui.values[ 1 ]);
                $('#comment-slider-range').on('slidechange', function() { oTable.fnDraw(); } );
            }
        });

        $( "#repin_min" ).val( $( "#repin-slider-range" ).slider( "values", 0 ));
        $( "#repin_max" ).val( $( "#repin-slider-range" ).slider( "values", 1 ));
        $( "#amount-repin" ).text( $( "#repin-slider-range" ).slider( "values", 0 ) +
            " - " + $( "#repin-slider-range" ).slider( "values", 1 ));

        $( "#like_min" ).val( $( "#like-slider-range" ).slider( "values", 0 ));
        $( "#like_max" ).val( $( "#like-slider-range" ).slider( "values", 1 ));
        $( "#amount-like" ).text( $( "#like-slider-range" ).slider( "values", 0 ) +
            " - " + $( "#like-slider-range" ).slider( "values", 1 ));

        $( "#comment_min" ).val( $( "#comment-slider-range" ).slider( "values", 0 ));
        $( "#comment_max" ).val( $( "#comment-slider-range" ).slider( "values", 1 ));
        $( "#amount-comment" ).text( $( "#comment-slider-range" ).slider( "values", 0 ) +
            " - " + $( "#comment-slider-range" ).slider( "values", 1 ));



//		.rowGrouping({
//			"bExpandableGrouping": true,
//			fnOnGroupCompleted: function( oGroup ) {
//				var length = $('#example tr' + oGroup.groupItemClass).length;
//				$(oGroup.nGroup).find("td").append("<span> ("+length+")</span>");
//			},
//			"iGroupingColumnIndex":10,
//			"sGroupLabelPrefix":"<i class='icon-arrow-right'></i>"
//		})

//		.columnFilter({
//			"sPlaceHolder": "head:after",
//			"bUseColVis":true,
//			"aoColumns": [
//				{ "type": "text", "sSelector": "#filter1" },
//				{ "sSelector": "#filter2", "type": "text" },
//				null,
//				{ "sSelector": "#filter3", "type": "number-range" },
//				{ "sSelector": "#filter4", "type": "number-range" },
//				{ "sSelector": "#filter5", "type": "number-range" },
//				{ "sSelector": "#filter6", "type": "date-range" },
//				{ "sSelector": "#filter7", "type": "text" }
//			]
//		})

        $("#new-datatable-filter").ready(function() {
            $("#example_filter").appendTo($("#new-datatable-filter"));
            $("#example_filter").addClass("input-prepend");
            $(".add-on").appendTo("#example_filter");
            $("#example_filter").children("label").children("input").appendTo("#example_filter");
            $("#example_filter").children("label").remove();
            $("#example_filter").children("input").attr({
                id: "prependedInput",
                placeholder: "Search for anything!"
            });
        });

        $("#new-datatable-info").ready(function() {
            $("#example_info").appendTo($("#new-datatable-info"));
        });

        $("#new-datatable-length").ready(function() {
            $("#example_length").appendTo($("#new-datatable-length"));
        });

        $("#new-column-filters").ready(function() {
            $(".column-filter-widgets").appendTo($("#new-column-filters"));
        });

        $(".column-filter-tags").ready(function() {
            $(".column-filter-widget-selected-terms").appendTo($(".column-filter-tags"));
        });

        $(".dataTable-export").ready(function() {
            $(".DTTT").appendTo($(".dataTable-export"));
        });

        $(".more-filters").click(function() {
            if($(".all-range-sliders").hasClass("hidden")){
                $(".all-range-sliders").removeClass("hidden");
                $(".more-filters-btn").addClass("active");
            } else {
                $(".all-range-sliders").addClass("hidden");
                $(".more-filters-btn").removeClass("active");
            }
        });

        $('thead').addClass("datatable-header");

        <?php
        if(isset($board_filter)){ ?>

        $('.column-filter-widgets').ready(function(){

            var board = $('option').filter(function() { return $.trim( $(this).text() ) == "<?=$board_filter;?>"; });

            board.attr("selected",true);
            board.trigger("change");
            //oTable.fnDraw();
        });

        <?php } ?>


        //work in progress
//		$('#reset-button').click(function(){
//			$('.dataTables_filter input').val("");
//			$( "#date_min" ).val( '12/31/2011' );
//			$( "#date_max" ).val( '' );
//			oTable.fnResetAllFilters();
//			oTable.fnDraw();
//		});

    });

    function getPinHistory(pin_id, row){

    <?php if ($history_enabled){?>
        $.ajax({
            type: 'POST',
            url: '/ajax/get-pin-history',
            data: {'pin_id':pin_id},
            dataType: 'html',
            success: function(response){

                //console.log(data);
                //console.log($(data).filter('.status').text());
                var resp = $.parseHTML(response);
                if ($(resp).filter('.status').text()==1){
                    var startDate = parseInt($(resp).filter('.start-date').text());
                    var currentDate = parseInt($(resp).filter('.current-date').text());

                    var weekday=new Array(7);
                    weekday[0]="Sun";
                    weekday[1]="Mon";
                    weekday[2]="Tue";
                    weekday[3]="Wed";
                    weekday[4]="Thu";
                    weekday[5]="Fri";
                    weekday[6]="Sat";

                    var range = {};
                    var count = 0;
                    for(var i = startDate; i <= currentDate; i += 86400000){

                        var k = new Date(i);
                        range[count] = '' + (k.getMonth()+1) + '-' + k.getDate() + '-' + k.getFullYear() + ' (' + weekday[k.getDay()] + ')';

                        count++;
                    }


                    var dataValues = $(resp).filter('.repins').text();
                    var valuesArray = eval("["+dataValues+"]");
                    //var valuesArray = dataValues.split(",");

                    $('.history').sparkline(valuesArray, {
                        height: '100px',
                        width: '236px',
                        type: 'bar',
                        barColor: 'green',
                        chartRangeMin: 0,
                        highlightLighten: 1.5,
                        //highlightColor:'white',
                        stackedBarColor: ['#8DC58D','#D77E81','#FFC267'],
                        barWidth:4,
                        barSpacing: 2,
                        myPrefixes: ['comments', 'likes', 'repins'],
                        tooltipValueLookups: {
                            names: range
                        },
                        tooltipFormatter:  function(sp, options, fields) {
                            var format =  $.spformat('<div class="sp-field"><span style="color: {{color}}">&#9679;</span> <strong> {{value}}</strong> {{myprefix}} </div>');
                            var formatHeader = $.spformat('<div class="sp-header"> <u>{{offset:names}}</u> </div><br>');
                            var result = '';
                            var header = '';
                            $.each(fields, function(i, field) {
                                field.myprefix = options.get('myPrefixes')[i];
                                result += format.render(field, options.get('tooltipValueLookups'), options);
                                header = formatHeader.render(field, options.get('tooltipValueLookups'), options);
                            });

                            return header + result;
                        },
                        tooltipOffsetX: 30,
                        tooltipOffsetY: 50




//                            function(sp, options, fields) {
//                            return '<div class="jqsfield"><span style="color: '+fields[0].color+'">&#9679;</span> {{offset:names}} First: '+fields[0].value+'</div>' +
//                                '<div class="jqsfield"><span style="color: '+fields[1].color+'">&#9679;</span> {{offset:names}} Second: '+fields[1].value+'</div>' +
//                                '<div class="jqsfield"><span style="color: '+fields[2].color+'">&#9679;</span> {{offset:names}} Third: '+fields[2].value+'</div>';
                        //'<span style="color: {{color}}"> &#9679;</span>{{offset:names}} <br>  <strong>{{value}} {{prefix}}</strong>',

                    } );
                    //$('.sparklines').sparkline('html');
                    $.sparkline_display_visible();

                    $('.history').css({
                        'background':'rgba(255,255,255,0.9)',
                        'min-width':'470px',
                        'width':'610%',
                        'cursor':'default'

                    });
                    $('.history canvas').css({
                        'bottom':'0px',
                        'left':'0px',
                        'position':'absolute',
                        'padding-left':'5px'
                    });

                } else {
                    $('.history').css({
                        'background':'rgba(255,255,255,0.9)',
                        'min-width':'470px',
                        'width':'610%',
                        'cursor':'default'
                    });
                    $('.history').html('No new engagement since 7/1/13!');
                }
            }
        });

    <?php } else { ?>

        $('.history').css({
            'background':'rgba(255,255,255,0.9)',
            'min-width':'470px',
            'width':'610%',
            'cursor':'default'
        });
        $('.history').html('Individual Pin History Available on Professional Plan <br><br>' +
            '<a class=\"btn-link\" href=\"/upgrade?ref=pin_history\"><button class=\"btn btn-success\"><i class=\"icon-arrow-right\"></i> Learn More</button></a>');


    <?php } ?>
    }

</script>

<?php } ?>