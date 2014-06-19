<?php

/**
 * @author Alex
 * Date: 9/1/13 11:27 AM
 * 
 */


if (isset($dataTable_boards) && $dataTable_boards) {	?>


    <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/themes/base/jquery-ui.css" />
    <!--<script src='http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js'></script>
    <script src='jquery/uijs/jquery.ui.datepicker.js'></script>-->

    <script type="text/javascript" src="/js/datatables/media/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="/js/datatables/extras/TableTools/media/js/TableTools.min.js"></script>
    <script type="text/javascript" src="/js/datatables/extras/bootstrap/paging.js"></script>
    <script type="text/javascript" src="/js/datatables/extras/bootstrap/DT_bootstrap.js"></script>
    <script type="text/javascript" src="/js/datatables/extras/RowGrouping/jquery.dataTables.rowGrouping.js"></script>
    <script type="text/javascript" src="/js/datatables/extras/ColumnFilterWidgets/media/js/ColumnFilterWidgets.js"></script>
    <script type="text/javascript" src="/js/datatables/extras/ColVis/media/js/ColVis.js"></script>
    <script>
        $(function() {
            $( "#date_min" ).datepicker();
        });
        $(function() {
            $( "#date_max" ).datepicker();
        });
    </script>
    <!--		<link rel="stylesheet" href="includes/datatables/media/css/jquery.dataTables.css">-->
    <link rel="stylesheet" href="/css/DT_bootstrap.css">

    <script type="text/javascript" charset="utf-8">
    /* Data set - can contain whatever information you want */

    var aDataSet = [

        <?=$datatable_js;?>

    ];

    $(document).ready(function() {
        $('#dynamic').html( '<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-hover table-bordered"  id="boards_table"></table>' );
        $.extend( true, $.fn.DataTable.TableTools.DEFAULTS.oTags, {
            "collection": {
                "container": "ul",
                "button": "li",
                "liner": "a"
            }
        } );

        $.extend( jQuery.fn.dataTableExt.oSort, {
            "formatted-num-pre": function ( a ) {
                a = (a==="-") ? 0 : a.replace( /[^\d\-\.]/g, "" );
                return parseFloat( a );
            },

            "formatted-num-asc": function ( a, b ) {
                return a - b;
            },

            "formatted-num-desc": function ( a, b ) {
                return b - a;
            }
        } );

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
        } );


        $.extend( $.fn.dataTableExt.oStdClasses, {
            "sWrapper": "dataTables_wrapper form-inline"
        } );
        var oTable = $('#boards_table').dataTable( {
            "aaData": aDataSet,
            "bAutoWidth": false,
            "iDisplayLength": 350,
            "aoColumns": [
                { "sTitle": "Board", "sClass": "datatable_board_name_col", "asSorting": [ "desc", "asc" ] }, //0

                { "sTitle": <?php if(!$is_free_account){ print "\"<div class='dt_header_label'>Total</div>\"";} else { print "\"Pins\"";} ?>, "sClass": <?php if($board_table_type=='none'){ print "\"datatable_pins_current_none\"";} else {print "\"datatable_pins_current\"";} ?>, "sType": "formatted-num", "asSorting": [ "desc", "asc" ],
                    "mRender": function ( data, type, full ) {
                        var data_num = parseInt(data.replace(/,/g,''));
                        var max_pins = <?php print "$max_pins"?>;
                        var min_pins = <?php print "$min_pins"?>;
                        var pin_range = <?php print "$board_pin_range"?>;
                        var bar_width=(((data_num-min_pins)/pin_range)*80)+20;
                        return '<div class="boards_table_label">'+data+'<br><div class="boards_table_label_name">pins</div></div><div class="boards_table_bars_pins" style="width:'+bar_width+'%;"></div>';
                    }
                }, //1

                <?php if(!$is_free_account){ ?>
                { "sTitle": "<div class='dt_header_label'>Pinning Activity</div><div><div class='pull-left dt_header_timerange'><span class='pull-left'>←</span><?php echo $current_chart_label; ?><span class='pull-right'>→</span></div><div class='pull-right dt_header_chg'>Chg.</div></div>", "sClass": "datatable_pins_growth", "asSorting": [ "desc", "asc" ], "sType": "num-html"<?php if($board_table_type=='none'){ print ", \"bVisible\": false";} ?> }, //2
                { "sTitle": "<div class='dt_header_label_alt'><?php echo $current_name; ?></div>", "sClass": "datatable_pins_growth_none", "asSorting": [ "desc", "asc" ],  "sType": "num-html"<?php if($board_table_type!='none'){ print ", \"bVisible\": false";} ?> },
                <?php } ?>

                { "sTitle": <?php if(!$is_free_account){ print "\"<div class='dt_header_label'>Total</div>\"";} else { print "\"Followers\"";} ?>, "sClass": <?php if($board_table_type=='none'){ print "\"datatable_followers_current_none\"";} else {print "\"datatable_followers_current\"";} ?>, "sType": "formatted-num", "asSorting": [ "desc", "asc" ],
                    "mRender": function ( data, type, full ) {
                        var data_num = parseInt(data.replace(/,/g,''));
                        var max_followers = <?php print "$max_followers"?>;
                        var min_followers = <?php print "$min_followers"?>;
                        var follower_range = <?php print "$board_follower_range"?>;
                        var bar_width=(((data_num-min_followers)/follower_range)*80)+20;
                        return '<div class="boards_table_label">'+data+'<br><div class="boards_table_label_name">followers</div></div><div class="boards_table_bars_followers" style="width:'+bar_width+'%;float:left;"></div>';
                    }
                }, //4

                <?php if(!$is_free_account){ ?>
                { "sTitle": "<div class='dt_header_label'>Follower Growth</div><div><div class='pull-left dt_header_timerange'><span class='pull-left'>←</span><?php echo $current_chart_label; ?><span class='pull-right'>→</span></div><div class='pull-right dt_header_chg'>Chg.</div></div>", "sClass": "datatable_followers_growth", "asSorting": [ "desc", "asc" ], "sType": "num-html"<?php if($board_table_type=='none'){ print ", \"bVisible\": false";} ?>}, //4
                { "sTitle": "<div class='dt_header_label_alt'><?php echo $current_name; ?></div>", "sClass": "datatable_followers_growth_none", "asSorting": [ "desc", "asc" ], "sType": "num-html"<?php if($board_table_type!='none'){ print ", \"bVisible\": false";} ?> },
                <?php } ?>


                //show repins
                { "sTitle": <?php if(!$is_free_account){ print "\"<div class='dt_header_label'>Total</div>\"";} else { print "\"Repins\"";} ?>, "sClass": <?php if($board_table_type=='none'){ print "\"datatable_repins_current_none\"";} else {print "\"datatable_repins_current\"";} ?>, "sType": "formatted-num", "asSorting": [ "desc", "asc" ],
                    "mRender": function ( data, type, full ) {
                        var data_num = parseInt(data.replace(/,/g,''));
                        var max_repins = <?php print "$max_repins"?>;
                        var min_repins = <?php print "$min_repins"?>;
                        var repin_range = <?php print "$board_repin_range"?>;
                        var bar_width=(((data_num-min_repins)/repin_range)*80)+20;
                        return '<div class="boards_table_label">'+data+'<br><div class="boards_table_label_name">repins</div></div><div class="boards_table_bars_repins" style="width:'+bar_width+'%;float:left;"></div>';
                    }
                }, //7

                <?php if(!$is_free_account){ ?>
                { "sTitle": "<div class='dt_header_label'>Repin Growth</div><div><div class='pull-left dt_header_timerange'><span class='pull-left'>←</span><?php echo $current_chart_label; ?><span class='pull-right'>→</span></div><div class='pull-right dt_header_chg'>Chg.</div></div>", "sClass": "datatable_repins_growth", "asSorting": [ "desc", "asc" ], "sType": "num-html"<?php if($board_table_type=='none'){ print ", \"bVisible\": false";} ?> }, //6
                { "sTitle": "<div class='dt_header_label_alt'><?php echo $current_name; ?></div>", "sClass": "datatable_repins_growth_none", "asSorting": [ "desc", "asc" ], "sType": "num-html"<?php if($board_table_type!='none'){ print ", \"bVisible\": false";} ?> },
                <?php } ?>

                    

                //show likes
                { "sTitle": <?php if(!$is_free_account){ print "\"<div class='dt_header_label'>Total</div>\"";} else { print "\"Likes\"";} ?>, "sClass": <?php if($board_table_type=='none'){ print "\"datatable_likes_current_none\"";} else {print "\"datatable_likes_current\"";} ?>, "sType": "formatted-num", "asSorting": [ "desc", "asc" ],
                    "mRender": function ( data, type, full ) {
                        var data_num = parseInt(data.replace(/,/g,''));
                        var max_likes = <?php print "$max_likes"?>;
                        var min_likes = <?php print "$min_likes"?>;
                        var like_range = <?php print "$board_like_range"?>;
                        var bar_width=(((data_num-min_likes)/like_range)*80)+20;
                        return '<div class="boards_table_label">'+data+'<br><div class="boards_table_label_name">likes</div></div><div class="boards_table_bars_likes" style="width:'+bar_width+'%;float:left;"></div>';
                    }
                }, //10

                <?php if(!$is_free_account){ ?>
                { "sTitle": "<div class='dt_header_label'>Like Growth</div><div><div class='pull-left dt_header_timerange'><span class='pull-left'>←</span><?php echo $current_chart_label; ?><span class='pull-right'>→</span></div><div class='pull-right dt_header_chg'>Chg.</div></div>", "sClass": "datatable_likes_growth", "asSorting": [ "desc", "asc" ], "sType": "num-html"<?php if($board_table_type=='none'){ print ", \"bVisible\": false";} ?> }, //6
                { "sTitle": "<div class='dt_header_label_alt'><?php echo $current_name; ?></div>", "sClass": "datatable_likes_growth_none", "asSorting": [ "desc", "asc" ], "sType": "num-html"<?php if($board_table_type!='none'){ print ", \"bVisible\": false";} ?> },
                <?php } ?>


                //show comments
                { "sTitle": <?php if(!$is_free_account){ print "\"<div class='dt_header_label'>Total</div>\"";} else { print "\"comments\"";} ?>, "sClass": <?php if($board_table_type=='none'){ print "\"datatable_comments_current_none\"";} else {print "\"datatable_comments_current\"";} ?>, "sType": "formatted-num", "asSorting": [ "desc", "asc" ],
                    "mRender": function ( data, type, full ) {
                        var data_num = parseInt(data.replace(/,/g,''));
                        var max_comments = <?php print "$max_comments"?>;
                        var min_comments = <?php print "$min_comments"?>;
                        var comment_range = <?php print "$board_comment_range"?>;
                        var bar_width=(((data_num-min_comments)/comment_range)*80)+20;
                        return '<div class="boards_table_label">'+data+'<br><div class="boards_table_label_name">comments</div></div><div class="boards_table_bars_comments" style="width:'+bar_width+'%;float:left;"></div>';
                    }
                }, //13

                <?php if(!$is_free_account){ ?>
                { "sTitle": "<div class='dt_header_label'>comment Growth</div><div><div class='pull-left dt_header_timerange'><span class='pull-left'>←</span><?php echo $current_chart_label; ?><span class='pull-right'>→</span></div><div class='pull-right dt_header_chg'>Chg.</div></div>", "sClass": "datatable_comments_growth", "asSorting": [ "desc", "asc" ], "sType": "num-html"<?php if($board_table_type=='none'){ print ", \"bVisible\": false";} ?> }, //6
                { "sTitle": "<div class='dt_header_label_alt'><?php echo $current_name; ?></div>", "sClass": "datatable_comments_growth_none", "asSorting": [ "desc", "asc" ], "sType": "num-html"<?php if($board_table_type!='none'){ print ", \"bVisible\": false";} ?> },
                <?php } ?>
                
               

                { "sTitle": <?php if(!$is_free_account){ print "\"<div class='dt_header_label'>Repins / Pin</div>\"";} else { print "\"Virality Score\"";} ?>, "sClass": <?php if($board_table_type=='none'){ print "\"datatable_eff1_none\"";} else {print "\"datatable_eff1\"";} ?>, "sType": "num-html", "asSorting": [ "desc", "asc" ],
                    "mRender": function ( data, type, full ) {
                        var data_num = parseFloat(data.replace(/,/g,''));
                        var max_rppf = <?php print "$max_rppf"?>;
                        var min_rppf = 0;
                        var bar_width=((data_num/max_rppf)*97)+3;
                        return '<div class="boards_table_label">'+data+'<br><div class="boards_table_label_name">virality score</div></div><div class="boards_table_bars_eff" style="width:'+bar_width+'%;float:left;"></div>';
                    }, "bVisible":true
                }, //16
                { "sTitle": <?php if(!$is_free_account){ print "\"<div class='dt_header_label'>Repins / Pin / Follower</div>\"";} else { print "\"Engagement Score\"";} ?>, "sClass": <?php if($board_table_type=='none'){ print "\"datatable_eff2_none\"";} else {print "\"datatable_eff2\"";} ?>, "asSorting": [ "desc", "asc" ], "sType": "num-html",
                    "mRender": function ( data, type, full ) {
                        var data_num = parseFloat(data.replace(/,/g,''));
                        var max_rpppf = <?php print "$max_rpppf"?>;
                        var min_rpppf = 0;
                        var bar_width=((data_num/max_rpppf)*97)+3;
                        return '<div class="boards_table_label">'+data+'<br><div class="boards_table_label_name">engagement score</div></div><div class="boards_table_bars_eff" style="width:'+bar_width+'%;"></div>';
                    }
                }, //17

                <?php if(!$is_free_account){ ?>
                { "sTitle": "pin percentage", "sClass": "datatable_pin_perc", "asSorting": [ "desc", "asc" ], "sType": "num-html", "bVisible": false},
                { "sTitle": "pin growth percentage", "sClass": "datatable_pin_growth_perc", "asSorting": [ "desc", "asc" ], "sType": "num-html", "bVisible": false},
                { "sTitle": "follower percentage", "sClass": "datatable_follower_perc", "asSorting": [ "desc", "asc" ], "sType": "num-html", "bVisible": false},
                { "sTitle": "follower growth percentage", "sClass": "datatable_follower_growth_perc", "asSorting": [ "desc", "asc" ], "sType": "num-html", "bVisible": false},


                { "sTitle": "repin percentage", "sClass": "datatable_repin_perc", "asSorting": [ "desc", "asc" ], "sType": "num-html", "bVisible": false},
                { "sTitle": "repin growth percentage", "sClass": "datatable_repin_growth_perc", "asSorting": [ "desc", "asc" ], "sType": "num-html", "bVisible": false},

                { "sTitle": "like percentage", "sClass": "datatable_like_perc", "asSorting": [ "desc", "asc" ], "sType": "num-html", "bVisible": false},
                { "sTitle": "like growth percentage", "sClass": "datatable_like_growth_perc", "asSorting": [ "desc", "asc" ], "sType": "num-html", "bVisible": false},

                { "sTitle": "comment percentage", "sClass": "datatable_comment_perc", "asSorting": [ "desc", "asc" ], "sType": "num-html", "bVisible": false},
                { "sTitle": "comment growth percentage", "sClass": "datatable_comment_growth_perc", "asSorting": [ "desc", "asc" ], "sType": "num-html", "bVisible": false},
                <?php } ?>

            ],

            <?php if(!$is_free_account){ ?>

            "aaSorting": [[17,'desc']],
            "oColumnFilterWidgets": {
                "aiExclude": [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27],
                "bGroupTerms": true
            },

            <?php } else { ?>

            "aaSorting": [[7,'desc']],
            "oColumnFilterWidgets": {
                "aiExclude": [ 0, 1, 2, 3, 4, 5, 6, 7],
                "bGroupTerms": true
            },

            <?php } ?>

            "sDom": "<'row-fluid'<'pull-left'W>><'clearfix'>rt<'row-fluid'<'span6 pull-left'l<'span6 pull-right'p>>",
            "sPaginationType": "bootstrap",
            "bStateSave": false,
//			"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
//
//				$('div.boards_table_bars_pins',nRow).replaceWith("<div class='boards_table_bars_pins' style='width:"+aData[12]+"%;'></div><div class='boards_table_bars_pins_growth' style='width:"+aData[13]+"%;'></div>");
//				$('div.boards_table_bars_followers',nRow).replaceWith("<div class='boards_table_bars_followers_growth' style='width:"+aData[15]+"%;'></div>");
//				$('div.boards_table_bars_repins',nRow).replaceWith("<div class='boards_table_bars_repins_growth' style='width:"+aData[17]+"%;'></div>");
//
//			},
            "fnDrawCallback": function( oSettings ) {
                $('tr').hover(
                    function(){
                        $(this).find('div.datatable_board_label').css('background', 'rgba(255, 255, 255, 0.75)');
                        $(this).find('div.boards_table_label_name').css('opacity', '0.85');
                        $(this).find('span.graph_label_pos').addClass('active');
                        $(this).find('span.graph_label_neg').addClass('active');
                    },
                    function(){
                        $(this).find('div.datatable_board_label').css('background', 'rgba(255, 255, 255, 1)');
                        $(this).find('div.boards_table_label_name').css('opacity', '0.15');
                        $(this).find('span.graph_label_pos').removeClass('active');
                        $(this).find('span.graph_label_neg').removeClass('active');
                    }
                );
            }
        });


//:::::::::-------     for css bars in table cells     --------:::::::::::

//		"mRender": function ( data, type, full ) {
//			var max_follower_change = <?php //print "$max_follower_change"?>;
//			var data_num = parseInt(data.replace(/,/g,''));
//			var bar_height=(data_num/max_follower_change)*95;
//			var neg_bar_height = bar_height*-0.5;
//
//			if(Math.abs(data_num) <= max_follower_change){
//				return '<div class="boards_table_change_label" style="margin-bottom:'+neg_bar_height+'%;">'+data+'</div><div class="boards_table_change_bars" style="height:'+bar_height+'%;"></div>';
//			} else {
//				return '<div class="boards_table_change_label" style="margin-bottom:0%;">'+data+'</div><div class="boards_table_change_bars" style="height:0%;"></div>';
//			}
//		}



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


//		$("#new-datatable-filter").ready(function() {
//			$(".ColVis").appendTo($("#new-datatable-filter"));
//			$("div.ColVis button").addClass("btn");
//		});

//		$("#new-datatable-info").ready(function() {
//			$("#boards_table_info").appendTo($("#new-datatable-info"));
//		});

        $("#new-datatable-length").ready(function() {
            $("#boards_table_length").appendTo($("#new-datatable-length"));
        });

//		$("#new-column-filters").ready(function() {
//			$(".column-filter-widgets").appendTo($("#new-column-filters"));
//		});

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
        $("table.boards_table").ready(function() {
            //$("table.boards_table thead").append($("<tr class=\"datatable_second_header\"><th rowspan=\"2\" class=\"datatable_board_name_col2 sorting\">Boards</th><th colspan=\"2\">Pins</th><th colspan=\"2\">Repins</th><th colspan=\"2\">Efficiency</th></tr>"));
        });


        
//		var newRow = document.createElement( 'tr.datatable_second_header' );
//		var newCol1 = document.createElement('th.datatable_board_name_col2.sorting').attr('rowspan',2);
//		var newCol1text = document.createTextNode( "Here's some new text" );
//	  	newCol1.appendChild( newCol1text );
//	  	newRow.appendChild( newCol1 );
//		  $('.boards_table').append( newRow );

    });



    </script>

<?php }

