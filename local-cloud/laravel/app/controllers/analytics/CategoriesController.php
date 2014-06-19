<?php namespace Analytics;

use
    Log,
    UserHistory,
    View;

/**
 * Class CategoriesController
 *
 * @package Analytics
 */
class CategoriesController extends BaseController
{

    protected $layout = 'layouts.analytics';

    /**
     * Construct
     * @author  Will
     */
    public function __construct() {

        parent::__construct();

        Log::setLog(__FILE__,'Reporting','Categories_Report');
    }

    public function prepCategoriesPage(){

        $vars         = $this->baseLegacyVariables();
        extract($vars);

        /*
         * Redirect to upgrade page if feature not available
         */
        if(!$customer->hasFeature('nav_category')){
            $query_string = http_build_query(
                array(
                     'ref'  => 'categories',
                     'plan' => $customer->plan()->plan_id
                )
            );
            header('location:/upgrade?'.$query_string);
            exit;
        }

        $most_viral_cat = '';
        $most_viral_board ='';
        $most_viral_board_cat = '';
        $most_engaged_cat = '';
        $most_engaged_board = '';

//    if ($customer->hasFeature('category_history')) {
//        //TODO: feature needs to be built
//    } else {
//
//    }

        if ($customer->hasFeature('category_export')) {
            if (strpos($_SERVER['REQUEST_URI'], '?')) {
                $csv_url = "href='" . $_SERVER['REQUEST_URI'] . "&csv=1'";
            } else {
                $csv_url = "href='" . $_SERVER['REQUEST_URI'] . "?csv=1'";
            }
            $export_class   = "";
            $export_popover = "";
            $export_view_class = "";
            $export_pushout_class = "";
        } else {
            $csv_url        = "";
            $export_class   = "disabled";
            $export_view_class = "inactive";
            $export_pushout_class = "push-out";
            $export_popover = createPopover(".category-export", "hover", "bottom", "<span class=\"text-success\"><strong>Need to Export your Data?</strong></span>", "category-export",
                $customer->plan()->plan_id, "Upgrade to enable exporting data across your dashboard.<ul><li><strong>Instantly download CSV files</strong> of any report</li><li><strong>Take your data with you</strong> anywhere it needs to go!</li></ul>");
        }


//create the boards array
        $boards = array();
        $has_owned_boards = false;

        $acc = "select * from data_boards where user_id=$cust_user_id and track_type != 'deleted'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $board_id = $a['board_id'];

            $boards["$board_id"] = array();
            $boards["$board_id"]['board_id'] = $board_id;
            $boards["$board_id"]['url'] = $a['url'];
            $boards["$board_id"]['is_collaborator'] = $a['is_collaborator'];
            $boards["$board_id"]['is_owner'] = $a['is_owner'];
            $boards["$board_id"]['collaborator_count'] = $a['collaborator_count'];
            $boards["$board_id"]['image_cover_url'] = $a['image_cover_url'];
            $boards["$board_id"]['name'] = preg_replace('/[^A-Za-z0-9 ]/',' ', $a['name']);
            $boards["$board_id"]['description'] = preg_replace('/[^A-Za-z0-9 ]/',' ', $a['description']);

            if($a['category']==""){
                $boards["$board_id"]['category'] = "none";
            }
            else{
                $boards["$board_id"]['category'] = $a['category'];
            }

            $boards["$board_id"]['pin_count'] = $a['pin_count'];
            $boards["$board_id"]['follower_count'] = $a['follower_count'];
            $boards["$board_id"]['created_at'] = $a['created_at'];

            if($a['is_owner']==1){
                $has_owned_boards = true;
            }
        }


        $cache_timestamp = 0;
//get the last date calcs were completed for these boards
        foreach($boards as $b){
            $acc = "select * from status_boards where board_id= " . $b['board_id'] . "";
            $acc_res = mysql_query($acc,$conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                if($a['last_calced'] > $cache_timestamp){
                    $cache_timestamp = $a['last_calced'];
                }
            }
        }

        $current_date = getFlatDate($cache_timestamp);



//------------------------// Query for Category Charts //---------------------------------//

//	$acc = "select * from calcs_profile_categories where user_id='" . $cust_accounts[$cust_account_num]['user_id'] . "'";
//	$acc_res = mysql_query($acc,$conn) or die(mysql_error());
//	while ($a = mysql_fetch_array($acc_res)) {
//
//		if($a['category']=="None"){
//			$category = "none";
//		} else {
//			$category = $a['category'];
//		}
//
//		$categories["$category"] = array();
//		$categories["$category"]['category'] = $category;
//		$categories["$category"]['pins'] = $a['pins'];
//		$categories["$category"]['boards'] = $a['boards'];
//		$categories["$category"]['repins'] = $a['repins'];
//		$categories["$category"]['likes'] = $a['likes'];
//		$categories["$category"]['comments'] = $a['comments'];
//
//	}

        $categories = array();

        foreach($boards as $b){

            $board_id = $b['board_id'];
            $category = $b['category'];

            if($category==""){
                $category = "none";
            }
            else{
                $category = $b['category'];
            }

            if($b['is_owner']==1 || !$has_owned_boards){

                $acc = "select * from calcs_board_history where board_id=$board_id AND date=$current_date";
                $acc_res = mysql_query($acc,$conn) or die(mysql_error());
                while ($a = mysql_fetch_array($acc_res)) {
                    $date = $a['date'];

                    $boards[$board_id]['date'] = $date;
                    $boards[$board_id]['followers'] = $a['followers'];
                    $boards[$board_id]['pins'] = $a['pins'];
                    $boards[$board_id]['repins'] = $a['repins'];
                    $boards[$board_id]['likes'] = $a['likes'];
                    $boards[$board_id]['comments'] = $a['comments'];
                    $boards[$board_id]['has_repins'] = $a['pins_atleast_one_repin'];
                    $boards[$board_id]['has_likes'] = $a['pins_atleast_one_like'];
                    $boards[$board_id]['has_comments'] = $a['pins_atleast_one_comment'];
                    $boards[$board_id]['has_actions'] = $a['pins_atleast_one_engage'];

                    if(!isset($categories["$category"])){
                        $categories["$category"] = array();
                        $categories["$category"]['category'] = $category;
                        $categories["$category"]['pins'] = $a['pins'];
                        $categories["$category"]['repins'] = $a['repins'];
                        $categories["$category"]['likes'] = $a['likes'];
                        $categories["$category"]['comments'] = $a['comments'];
                        $categories["$category"]['boards'] = 1;
                        $categories["$category"]['total_followers'] = $a['followers'];
                    } else {
                        $categories["$category"]['pins'] += $a['pins'];
                        $categories["$category"]['repins'] += $a['repins'];
                        $categories["$category"]['likes'] += $a['likes'];
                        $categories["$category"]['comments'] += $a['comments'];
                        $categories["$category"]['total_followers'] += $a['followers'];
                        $categories["$category"]['boards']++;
                    }
                }
            }
        }

//get top categories and boards by iterating through them all first

        $max_board_pins = 0;
        $max_board_repins_per_pin = 0;
        $max_board_engagement = 0;
        $max_cat_pins = 0;
        $max_cat_repins_per_pin = 0;
        $max_cat_engagement = 0;


        foreach($boards as $b){

            if($b['is_owner']==1 || !$has_owned_boards){

                if($b['category']==""){
                    $board_category = "none";
                } else {
                    $board_category = $b['category'];
                }

                $board_category = renameCategories($board_category);
                $board_name = $b['name'];
                $board_id   = $b['board_id'];
                $board_pins = $b['pin_count'];
                $board_repins = $b['repins'];
                @$board_repins_per_pin = number_format($board_repins / $board_pins,1);
                @$board_engagement = number_format($board_repins_per_pin/$b['followers']*1000,2);

                if($board_pins > $max_board_pins){
                    $max_board_pins = $board_pins;
                    $most_active_board = $board_name;
                    $most_active_board_cat = $board_category;
                }

                if($board_repins_per_pin > $max_board_repins_per_pin){
                    $max_board_repins_per_pin = $board_repins_per_pin;
                    $most_viral_board = $board_name;
                    $most_viral_board_cat = $board_category;
                }

                if($board_engagement > $max_board_engagement){
                    $max_board_engagement = $board_engagement;
                    $most_engaged_board = $board_name;
                    $most_engaged_board_cat = $board_category;
                }
            }
        }


        foreach($categories as $c){

            $cat_pins = $c['pins'];

            $cat_repins_per_pin = $c['repins']/$c['pins'];

            $cat_avg_followers = $c['total_followers']/$c['boards'];

            $cat_engagement = $cat_repins_per_pin/$cat_avg_followers*1000;

            $cat_name = $c['category'];
            @$categories["$cat_name"]['repins_per_pin'] = number_format($cat_repins_per_pin,2);
            @$categories["$cat_name"]['avg_followers'] = number_format($cat_avg_followers,0);
            @$categories["$cat_name"]['engagement_score'] = number_format($cat_engagement,2);
            $cat_name = renameCategories($cat_name);

            if($cat_pins > $max_cat_pins){
                $max_cat_pins = $cat_pins;
                $most_active_cat = $cat_name;
            }

            if($cat_repins_per_pin > $max_cat_repins_per_pin){
                $max_cat_repins_per_pin = $cat_repins_per_pin;
                $most_viral_cat = $cat_name;
            }

            if($cat_engagement > $max_cat_engagement){
                $max_cat_engagement = $cat_engagement;
                $most_engaged_cat = $cat_name;
            }

            $max_cat_repins_per_pin = number_format($max_cat_repins_per_pin,1);
            $max_cat_engagement = number_format($max_cat_engagement,2);
        }


        $max_board_pins = number_format($max_board_pins,0);
        $max_cat_pins = number_format($max_cat_pins,0);



        /*
         * Create data tables for virality heatmap
         */
        $virality_categories = array();
        foreach($categories as $c){



            $category_name = $c['category'];
            $category_rpp = $c['repins_per_pin'];
            $category_pins = $c['pins'];
            $category_name = renameCategories($category_name);

            if($category_name!=''){
                $virality_categories[] = ",['$category_name ($category_pins pins, $category_rpp Virality Score)','Virality per Board by Category (click to drill down, right click to go back)',0,0]";
            }

        }

        $max_repins_per_pin = 0;

        $virality_boards = array();
        foreach($boards as $b){


            if($b['is_owner']==1 || !$has_owned_boards){

                if($b['category']==""){
                    $board_cat = "none";
                } else {
                    $board_cat = $b['category'];
                }
                $board_category = renameCategories($board_cat);

                $board_name = $b['name'];

                if (strlen($board_name) > 25) {
                    $board_name = substr($board_name, 0, 23) . "..";
                }



                $board_pins = $b['pins'];
                $board_repins = $b['repins'];
                @$board_repins_per_pin = number_format($board_repins / $board_pins,1);

                if($board_repins_per_pin > $max_repins_per_pin){
                    $max_repins_per_pin = $board_repins_per_pin;
                }



                $virality_boards[] = ",['". $board_name ." (".$board_pins ." pins, ".$board_repins_per_pin." Virality Score)','$board_category (".$categories[$board_cat]['pins']." pins, ".$categories[$board_cat]['repins_per_pin']." Virality Score)',$board_pins,". number_format($board_repins_per_pin,1,'.','') . "]";
            }
        }


        /*
         * Create datatables for engagement heatmap
         */
        $engagement_categories = array();
        foreach($categories as $c){



            $category_name = $c['category'];
            $category_rpp = $c['repins_per_pin'];
            $category_pins = $c['pins'];
            $category_followers = $c['avg_followers'];
            $category_engagement = $c['engagement_score'];
            $category_name = renameCategories($category_name);

            if($category_name!=''){
                $engagement_categories[] = ",['$category_name ($category_engagement Engagement Score)','Engagement per Board by Category (click to drill down, right click to go back)',0,0]";
            }

        }

        $max_repins_per_pin_per_follower = 0;

        $engagement_boards = array();
        foreach($boards as $b){


            if($b['is_owner']==1 || !$has_owned_boards){

                if($b['category']==""){
                    $board_cat = "none";
                } else {
                    $board_cat = $b['category'];
                }
                $board_category = renameCategories($board_cat);

                $board_name = $b['name'];

                if (strlen($board_name) > 25) {
                    $board_name = substr($board_name, 0, 23) . "..";
                }



                $board_pins = $b['pins'];
                $board_repins = $b['repins'];
                $board_followers = $b['follower_count'];
                @$board_repins_per_pin_per_follower = number_format($board_repins / $board_pins / $board_followers * 1000,2);

                if($board_repins_per_pin_per_follower > $max_repins_per_pin_per_follower){
                    $max_repins_per_pin_per_follower = $board_repins_per_pin_per_follower;
                }

                $engagement_boards[] = ",['". $board_name ." (". number_format($board_followers,0) ." Followers, ".$board_repins_per_pin_per_follower." Engagement Score)','$board_category (".$categories[$board_cat]['engagement_score']." Engagement Score)',$board_followers,". $board_repins_per_pin_per_follower . "]";
            }
        }



        $category_vars = array(
            'csv_url' => $csv_url,
            'export_class' => $export_class,
            'export_popover' => $export_popover,
            'boards' => $boards,
            'has_owned_boards' => $has_owned_boards,
            'cache_timestamp' => $cache_timestamp,
            'current_date' => $current_date,
            'categories' => $categories,
            'max_board_pins' => $max_board_pins,
            'max_board_repins_per_pin' => $max_board_repins_per_pin,
            'max_board_engagement' => $max_board_engagement,
            'max_cat_pins' => $max_cat_pins,
            'max_cat_repins_per_pin' => $max_cat_repins_per_pin,
            'max_cat_engagement' => $max_cat_engagement,
            'most_active_cat' => $most_active_cat,
            'most_active_board' => $most_active_board,
            'most_active_board_cat' => $most_active_board_cat,
            'most_viral_cat' => $most_viral_cat,
            'most_viral_board' => $most_viral_board,
            'most_viral_board_cat' => $most_viral_board_cat,
            'most_engaged_cat' => $most_engaged_cat,
            'most_engaged_board' => $most_engaged_board,
            'most_engaged_board_cat' => $most_engaged_board_cat,
            'uri_pass' => $uri_pass,
            'get_params' => $get_params,
            'virality_categories' => $virality_categories,
            'virality_boards' => $virality_boards,
            'engagement_categories' => $engagement_categories,
            'engagement_boards' => $engagement_boards,
            'max_repins_per_pin' => $max_repins_per_pin,
            'max_repins_per_pin_per_follower' => $max_repins_per_pin_per_follower
        );

        return $category_vars;
    }

    public function showCategories(){

        $vars = $this->baseLegacyVariables();

        $vars = array_merge($vars, $this->prepCategoriesPage());

        $this->layout_defaults['page'] = 'categories';
        $this->layout_defaults['top_nav_title'] = 'Category Heatmaps';
        $this->layout->top_navigation = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('categories');

        if(isset($_GET['csv'])) {
            return $this->downloadCategories('csv');
        }

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'categories',
                                 )
        );

        $this->layout->body_id = 'categories';
        $vars['nav_categories_class'] .= ' active';
        $vars['report_url'] = 'categories';

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.optimize', $vars);

        $this->layout->main_content = View::make('analytics.pages.categories', $vars);
    }

    public function downloadCategories($type){
        $vars = $this->baseLegacyVariables();
        $vars = array_merge($vars, $this->prepCategoriesPage());

        $html = View::make('analytics.pages.categories', $vars);

        $date = date("F-j-Y");

        $this->logged_in_customer->recordEvent(
                                 UserHistory::EXPORT_REPORT,
                                 $parameters = array(
                                     'report' => 'categories',
                                 )
        );

        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"Tailwind-Analytics-Category-Heatmap-$date.csv\"");
        echo $html;
        exit;


    }

}

function renameCategories($a) {

    if($a=="womens_fashion"){
        $b="womens fashion";
    }
    elseif($a=="diy_crafts"){
        $b="diy & crafts";
    }
    elseif($a=="health_fitness"){
        $b="health & fitness";
    }
    elseif($a=="holidays_events"){
        $b="holidays & events";
    }
    elseif($a=="none"){
        $b="not specified";
    }
    elseif($a=="holiday_events"){
        $b="holidays & events";
    }
    elseif($a=="home_decor"){
        $b="home decor";
    }
    elseif($a=="food_drink"){
        $b="food & drink";
    }
    elseif($a=="film_music_books"){
        $b="film, music & books";
    }
    elseif($a=="hair_beauty"){
        $b="hair & beauty";
    }
    elseif($a=="cars_motorcycles"){
        $b="cars & motorcycles";
    }
    elseif($a=="science_nature"){
        $b="science & nature";
    }
    elseif($a=="mens_fashion"){
        $b="mens fashion";
    }
    elseif($a=="illustrations_posters"){
        $b="illustrations & posters";
    }
    elseif($a=="art_arch"){
        $b="art & architecture";
    }
    elseif($a=="wedding_events"){
        $b="weddings & events";
    }
    else{
        $b=$a;
    }

    return $b;
}