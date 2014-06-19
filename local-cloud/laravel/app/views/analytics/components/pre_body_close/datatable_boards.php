<script type="text/javascript">

    jQuery(document).ready(function ($) {

        <?php if(!$is_free_account){ ?>

        $("tr").attr("role", "row").children("th.datatable_board_name_col").attr('rowspan', '2');
        $('table.dataTable thead').prepend(
            $('<tr></tr>').addClass('header_second_row').attr('role', 'row').prepend(
                $('<th></th>').addClass('board_dummy_column').attr('colspan', '1').attr('tabindex', '0').attr('role', 'columnheader')
            )
        );
        $('.header_second_row').append(
            $('<th class="pins_header header-border">Pins</th>').attr('colspan', '2')
        );
        $('.header_second_row').append(
            $('<th class="followers_header header-border">Followers</th>').attr('colspan', '2')
        );


        $('.header_second_row').append(
            $('<th class="repins_header header-border">Repins</th>').attr('colspan', '2')
        );

        $('.header_second_row').append(
            $('<th class="likes_header header-border">Likes</th>').attr('colspan', '2')
        );

        $('.header_second_row').append(
            $('<th class="comments_header header-border">Comments</th>').attr('colspan', '2')
        );

        $('.header_second_row').append(
            $('<th class="efficiency_header header-border">Virality Score</th>').attr('colspan', '1')
        );
        $('.header_second_row').append(
            $('<th class="efficiency_header header-border">Engagement Score</th>').attr('colspan', '1')
        );

        $('.datatable_likes_current_none').hide();
        $('.datatable_likes_growth_none').hide();
        $('.likes_header').hide();
        
        $('.datatable_comments_current_none').hide();
        $('.datatable_comments_growth_none').hide();
        $('.comments_header').hide();


        $(".board-repin-toggle").click(function(){
            $('.datatable_likes_current_none').hide();
            $('.datatable_likes_growth_none').hide();
            $('.likes_header').hide();

            $('.datatable_comments_current_none').hide();
            $('.datatable_comments_growth_none').hide();
            $('.comments_header').hide();

            $('.datatable_repins_current_none').show();
            $('.datatable_repins_growth_none').show();
            $('.repins_header').show();
        });

        $(".board-like-toggle").click(function(){
            $('.datatable_comments_current_none').hide();
            $('.datatable_comments_growth_none').hide();
            $('.comments_header').hide();

            $('.datatable_repins_current_none').hide();
            $('.datatable_repins_growth_none').hide();
            $('.repins_header').hide();

            $('.datatable_likes_current_none').show();
            $('.datatable_likes_growth_none').show();
            $('.likes_header').show();
        });

        $(".board-comment-toggle").click(function(){
            $('.datatable_likes_current_none').hide();
            $('.datatable_likes_growth_none').hide();
            $('.likes_header').hide();

            $('.datatable_repins_current_none').hide();
            $('.datatable_repins_growth_none').hide();
            $('.repins_header').hide();

            $('.datatable_comments_current_none').show();
            $('.datatable_comments_growth_none').show();
            $('.comments_header').show();
        });

        <?php } ?>

        $('.datatable_likes_current_none').hide();
        $('.datatable_comments_current_none').hide();

        $(".board-repin-toggle").click(function(){
            $('.datatable_likes_current_none').hide();

            $('.datatable_comments_current_none').hide();

            $('.datatable_repins_current_none').show();
        });

        $(".board-like-toggle").click(function(){
            $('.datatable_comments_current_none').hide();

            $('.datatable_repins_current_none').hide();

            $('.datatable_likes_current_none').show();
        });

        $(".board-comment-toggle").click(function(){
            $('.datatable_likes_current_none').hide();

            $('.datatable_repins_current_none').hide();

            $('.datatable_comments_current_none').show();
        });
        
        $(".dataTables_length").html($(".dataTables_length").html().replace("records", "Boards"));

        //$(".boards-sub-header").hide();

        $(".export_show.active").on('click', function () {
            $(".boards-sub-header").toggle('showOrHide');
        });
        
        $(".board-repin-toggle").click(function(){
            
        })

    });

</script>
