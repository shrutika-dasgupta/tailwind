<?php

/**
 * CategoryFootprint Model
 *
 * @author  Yesh
 */

use Pinleague\Pinterest;

class CategoryFootprint extends PDODatabaseModel
{
    /*
     * DB Attributes
     */
    public
        $user_id,
        $activity_indv_hash,
        $influence_indv_hash,
        $board_indv_count_hash,
        $activity_collab_hash,
        $influence_collab_hash,
        $board_collab_count_hash,
        $recency_hash,
        $footprint_hash,
        $timestamp;

    public $columns = array(
        'user_id',
        'activity_indv_hash',
        'influence_indv_hash',
        'board_indv_count_hash',
        'activity_collab_hash',
        'influence_collab_hash',
        'board_collab_count_hash',
        'recency_hash',
        'footprint_hash',
        'timestamp'
    ),
    $primary_keys = array('user_id');

    public $table = 'map_profiles_category_footprint';


    public static $category_hashmap =
    array(
    "animals"               => "a",
    "architecture"          => "b",
    "art"                   => "c",
    "cars_motorcycles"      => "d",
    "celebrities"           => "e",
    "design"                => "f",
    "diy_crafts"            => "g",
    "education"             => "h",
    "everything"            => "i",
    "film_music_books"      => "j",
    "food_drink"            => "k",
    "for_dad"               => "l",
    "gardening"             => "m",
    "geek"                  => "n",
    "gifts"                 => "o",
    "hair_beauty"           => "p",
    "health_fitness"        => "q",
    "history"               => "r",
    "holidays_events"       => "s",
    "home_decor"            => "t",
    "humor"                 => "u",
    "illustrations_posters" => "v",
    "kids"                  => "w",
    "mens_fashion"          => "x",
    "outdoors"              => "y",
    "other"                 => "z",
    "photography"           => "A",
    "popular"               => "B",
    "products"              => "C",
    "quotes"                => "D",
    "science_nature"        => "E",
    "sports"                => "F",
    "tattoos"               => "G",
    "technology"            => "H",
    "travel"                => "I",
    "videos"                => "J",
    "weddings"              => "K",
    "womens_fashion"        => "L");


    public function __construct()
    {
        parent::__construct();
        $this->timestamp = time();
    }
    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */

    /** Creates hash for the following parameters
     *  activity    : The total count of all the pins across a category
     *  influence   : The average followers across a category
     *  board_count : The total number of boards in a category
     *  recency     : The order of latest to old boards created in a category
     *
     * @author Yesh
     *
     * @param $user
     *
     * @return CategoryFootprint
     */
    public function createHash($user){
        ksort($user);
        $activity_hash           = "";
        $influence_hash          = "";
        $activity_collab_hash    = "";
        $influence_collab_hash   = "";
        $recency_hash            = "";
        $board_indv_count_hash   = "";
        $board_collab_count_hash = "";

        foreach($user as $category_name => $category_data){
                $category_hashmap = new CategoryFootprint();
                if (isset($category_data["activity"]) and
                    isset($category_data["influence"]))
                {
                    $activity_hash          .= self::$category_hashmap[$category_name] .
                                                " " .
                                                $category_data["activity"] .
                                                " ";

                    $influence_hash         .= self::$category_hashmap[$category_name] .
                                                " " .
                                                $category_data["influence"] .
                                                " ";
                    $board_indv_count_hash  .= self::$category_hashmap[$category_name] .
                                                " " .
                                                $category_data["board_count"] .
                                                " ";
                }
                if (isset($category_data["activity_collab"]) and
                    isset($category_data["influence_collab"]))
                {
                    $activity_collab_hash    .= self::$category_hashmap[$category_name] .
                                                " " .
                                                $category_data["activity_collab"] .
                                                " ";

                    $influence_collab_hash   .= self::$category_hashmap[$category_name] .
                                                " " .
                                                $category_data["influence_collab"] .
                                                " ";
                    $board_collab_count_hash .= self::$category_hashmap[$category_name] .
                                                " " .
                                                $category_data["board_count_collab"] .
                                                " ";
                }

                $category_hashmap->activity_indv_hash      = trim($activity_hash);
                $category_hashmap->influence_indv_hash     = trim($influence_hash);

                $category_hashmap->activity_collab_hash    = trim($activity_collab_hash);
                $category_hashmap->influence_collab_hash   = trim($influence_collab_hash);

                $category_hashmap->board_indv_count_hash   = trim($board_indv_count_hash);
                $category_hashmap->board_collab_count_hash = trim($board_collab_count_hash);

        }

        // Creating recency hash

        $user_details = array();

        foreach ($user as $category_name => $category_details) {
            $category_details['category_name'] = $category_name;
            $user_details[] = $category_details;
        }

        usort($user_details, function($a, $b) {
            return $a['recency'] - $b['recency'];
        });

        $user_details = array_reverse($user_details);

        foreach($user_details as $category_data){

                $recency_hash .=
                    self::$category_hashmap[$category_data['category_name']] .
                    " ";

        }
        $category_hashmap->recency_hash = trim($recency_hash);

        return $category_hashmap;
    }


    /** Creates the user_footprint based on all the hashes
     *
     *  We unwrap the following parameters:
     *  - activity hash
     *  - influence hash
     *  - board_count hash
     *  - recency hash
     *
     *  We get back a an array for each of these hashes with the
     *  name
     *
     *  We take the top three categories across the parameters and
     *  assign the following scores
     *
     * --------------------------------------------------------
     * | Score | Activity | Influence | Board Count | Recency |
     * |       |          |           |             |         |
     * |  3    |  geek    |  sports   |  cars       | boats   |
     *   ----------------------------------------------------
     * |       |          |           |             |         |
     * |  2    | sports   |  cars     |  health     | geek    |
     *   ----------------------------------------------------
     * |       |          |           |             |         |
     * |  1    | cars     |  geek     |  geek       | health  |
     * --------------------------------------------------------
     *
     *  So, the footprint would have the following score:
     *  geek   = 3 + 2 + 2 + 1
     *  sports = 3 + 2
     *  and so on..
     *
     * @author Yesh
     *
     * @param CategoryFootprint $footprint
     *
     * @return array
     */
    public function userFootprint(CategoryFootprint $footprint)
    {
        $all_hashes = array();

        if (!empty($footprint->board_indv_count_hash)){
            $activity_array =
                CategoryFootprint::unwrapActivityHash($footprint->activity_indv_hash);
            arsort($activity_array);
            $activity_sliced = array_slice($activity_array, 0, 3);

            $influence_array =
                CategoryFootprint::unwrapInfluenceHash($footprint->influence_indv_hash);
            arsort($influence_array);
            $influence_sliced = array_slice($influence_array, 0, 3);

            $board_count_array =
                CategoryFootprint::unwrapBoardCount($footprint->board_indv_count_hash);
            arsort($board_count_array);
            $board_count_sliced= array_slice($board_count_array, 0, 3);

            array_push($all_hashes,
                       $activity_sliced,
                       $influence_sliced,
                       $board_count_sliced);

        }

        $recency_array =
            CategoryFootprint::unwrapRecencyHash($footprint->recency_hash);
        $recency_sliced = array_slice($recency_array, 0, 3);

        array_push($all_hashes, $recency_sliced);

        $footprint_array = array();

        foreach($all_hashes as $hash){

            $scored_hash = CategoryFootprint::setFootprintScore($hash);

            foreach($scored_hash as $key => $value){

                if (!isset($footprint_array[$key])){
                    $footprint_array[$key] = $value;
                } else {
                    $footprint_array[$key] += $value;
                }
            }
        }

        arsort($footprint_array);

        $category_footprint = "";

        foreach($footprint_array as $key => $value){

            $category_footprint .= $key . " " . $value. " ";
        }

        return trim($category_footprint);
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /** It decodes a given hash to do a key-lookup
     *  and give the category names.
     * @param $hash
     *
     * @return array
     */
    public static function decodeHash($hash){

        $hash_exploded = explode(' ', $hash);
        $hash_decoded = array();

        foreach(range(0, (count($hash_exploded) - 1), 2) as $index){
            $category_name = array_search($hash_exploded[$index],
                                          self::$category_hashmap);

            $hash_decoded[$category_name] = $hash_exploded[$index + 1];
        }

        return $hash_decoded;
    }


    /** The recency hash is a little special as it doesn't
     *  have any number in the hash. Thus, deserves its own
     *  special decode method
     * @param $hash
     *
     * @return mixed
     */
    public static function decodeHashRecency($hash){

        $hash_exploded = explode(' ', $hash);


        foreach($hash_exploded as $key => $index){
            $hash_decoded[$key] = array_search($index,
                                               self::$category_hashmap);
        }

        return $hash_decoded;

    }


    /** Calculates the userFootprint score
     *
     * @param $sliced_hash
     *
     * @return mixed
     */
    public static function setFootprintScore($sliced_hash){

        $score = count($sliced_hash);

        foreach($sliced_hash as $key => $value){
            $sliced_hash[$key] = $score;
            $score --;
        }

        return $sliced_hash;
    }

    /** Unwrap the activity hash
     * @param $activity_hash
     *
     * @return array
     */
    public static function unwrapActivityHash($activity_hash){

        $activity_array = array();
        if (!empty($activity_hash)){
            $activity_hash_exploded = explode(' ', $activity_hash);

            // if count of array was 10, we would be iterating over
            // a range of (0, 8)
            // A hash of "z 402 I 126" would translate to {"z" : 402, "I" : 126}

            if (count($activity_hash_exploded) > 2){
                foreach(range(0, (count($activity_hash_exploded) - 1), 2) as $index){
                    $activity_array[$activity_hash_exploded[$index]] =
                                                    $activity_hash_exploded[$index + 1];
                }
            } else {
                    $activity_array[$activity_hash_exploded[0]] =
                        $activity_hash_exploded[1];
            }
        }

        return $activity_array;

    }


    /** Unwrap the influence Hash
     * @param $influence_hash
     *
     * @return array
     */
    public static function unwrapInfluenceHash($influence_hash){

        $influence_array = array();

        if (!empty($influence_hash)){
            $influence_hash_exploded = explode(' ', $influence_hash);

            // if count of array was 10
            // iterating over a range of (0, 8)

            if (count($influence_hash_exploded) > 2){
                foreach(range(0, (count($influence_hash_exploded) - 1), 2) as $index){
                    $influence_array[$influence_hash_exploded[$index]] =
                                                    $influence_hash_exploded[$index + 1];
                    }
                } else {
                        $influence_array[$influence_hash_exploded[0]] =
                            $influence_hash_exploded[0];
            }
        }

        return $influence_array;
    }


    /** Unwrap the board count hash
     * @param $board_count_hash
     *
     * @return array
     */
    public static function unwrapBoardCount($board_count_hash){

        $board_count_array = array();

        if(!empty($board_count_hash)){
            $board_hash_exploded = explode(' ', $board_count_hash);


            // if count of array was 10
            // iterating over a range of (0, 8)

            if (count($board_hash_exploded) > 2){
                foreach(range(0, (count($board_hash_exploded) - 1), 2) as $index){
                    $board_count_array[$board_hash_exploded[$index]] =
                        $board_hash_exploded[$index + 1];
                }
                } else {
                        $board_count_array[$board_hash_exploded[0]] =
                            $board_hash_exploded[1];
                }
            }

        return $board_count_array;

    }

    public static function unwrapRecencyHash($recency_hash){

        $recency_array= array();

        if (!empty($recency_hash)){
            $recency_hash_exploded = explode(' ', $recency_hash);

            $count = count($recency_hash_exploded);

            // if count of array was 10
            // iterating over a range of (0, 8)
            if (count($recency_hash_exploded) > 2){
                foreach(range(0, (count($recency_hash_exploded) - 1), 2) as $index){
                    $recency_array[$recency_hash_exploded[$index]] =
                        $count;
                    $count --;
                }
            } else {
                    $recency_array[$recency_hash_exploded[0]] = 1;
            }
        }

        return $recency_array;
    }

    public function getFootprintByUserID($user_id){

        $STH = $this->DBH->prepare("select footprint_hash
                  from map_profiles_category_footprint
                  where user_id = :user_id");

        $STH->execute(
            array(
                 ":user_id" => $user_id
            )
        );

        if ($STH->rowCount() == 0) {
            return false;
        }

        $footprint = $STH->fetch();

        $footprint = $footprint->footprint_hash;

        return $this->decodeHash($footprint);
    }

}
class CategoryFootprintException extends DBModelException {}
