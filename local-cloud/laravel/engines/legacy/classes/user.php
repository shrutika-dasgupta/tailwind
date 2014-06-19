<?php

class User {

    public $track_type;

    public $id
    , $username
    , $first_name
    , $last_name
    , $email
    , $image
    , $about
    , $domain_url
    , $domain_verified
    , $website_url
    , $facebook_url
    , $twitter_url
    , $google_plus_url
    , $location
    , $board_count
    , $pin_count
    , $like_count
    , $follower_count
    , $following_count
    , $created_at
    , $gender;

    public $followed_user_id;

    function __construct($track_type) {
        if (!$track_type) {
            $this->track_type = "track";
        } else {
            $this->track_type = $track_type;
        }

        $this->followed_id = "";
    }

    function setFollowedUserId($id) {
        $this->followed_user_id = $id;
    }

    function loadFromAPIBlock($data) {
        if (is_array($data)) {
            if ($data['id']) {
                $this->id = $data['id'];
                $this->username = $data['username'];
                $this->first_name = $data['first_name'];
                $this->last_name = $data['last_name'];
                // $this->email = $data['email'];
                $this->image = $data['image_medium_url'];
                $this->about = $data['about'];
                $this->domain_url = $data['domain_url'];
                $this->domain_verified = $data['domain_verified'];
                $this->website_url = $data['website_url'];
                $this->facebook = $this->processFacebook($data['facebook_url']);
                $this->twitter = $this->processTwitter($data['twitter_url']);
                $this->google_plus_url = $this->processGooglePlus($data['gplus_url']);
                $this->location = $data['location'];
                $this->board_count = $data['board_count'];
                $this->pin_count = $data['pin_count'];
                $this->like_count = $data['like_count'];
                $this->follower_count = $data['follower_count'];
                $this->following_count = $data['following_count'];
                $this->created_at = parsePinterestCreationDateToTimestamp($data['created_at']);
                $this->gender = $data['gender'];
            }
        }
    }

    function getRichProduct($data) {
        if (array_key_exists("rich_product", $data)) {
            if (is_array($data['rich_product'])) {
                if (array_key_exists("id", $data['rich_product'])) {
                    return $data['rich_product']['id'];
                }
            }
        }

        return "";
    }

    function processFacebook($fb) {
        if (!$fb) {
            return "";
        }

        //"http://www.facebook.com/john.david.busch"
        $fb = str_replace("https://", "", strtolower($fb));
        $fb = str_replace("http://", "", strtolower($fb));
        $fb = str_replace("www.", "", $fb);
        $fb = str_replace("facebook.com", "", $fb);
        $fb = str_replace("/", "", $fb);
        $fb = str_replace("profile.php?id=", "", $fb);

        return $fb;
    }

    function processTwitter($tw) {
        if (!$tw) {
            return "";
        }
        //"http://twitter.com/RainyBoat/"
        $tw = str_replace("https://", "", strtolower($tw));
        $tw = str_replace("http://", "", strtolower($tw));
        $tw = str_replace("www.", "", $tw);
        $tw = str_replace("twitter.com", "", $tw);
        $tw = str_replace("/", "", $tw);
        return $tw;
    }
    /**
     * @author  Will
     *
     * @param $url
     *
     * @input   https://plus.google.com/100783813088885394550
     *
     * @return string
     */
    function processGooglePlus($url) {

        $pieces = parse_url($url);

        return str_replace('/','',$pieces['path']);
    }
}


/* RAW DATA INFORMATION

    xxxxxxxxxxxxxxx
    KEY INFORMATION

    ["id"]=> string(16) "7107449320378329"
    ["username"]=> string(6) "berone"
    ["first_name"]=> string(5) "Rainy"
    ["last_name"]=> string(4) "Boat"
    ["full_name"]=> string(10) "Rainy Boat"
    ["email"]=> string(26) "john.david.busch@gmail.com"

    ["twitter_url"]=> string(29) "http://twitter.com/RainyBoat/"
    ["facebook_url"]=> string(40) "http://www.facebook.com/john.david.busch"

    ["domain_verified"]=> bool(false)
    ["domain_url"]=> string(14) "pinclarity.com"

    ["following_count"]=> int(10)
    ["like_count"]=> int(1)
    ["follower_count"]=> int(7)
    ["board_count"]=> int(5)
    ["pins"]=> array(0) { }
    ["pin_count"]=> int(2)

    ["location"]=> string(17) "Somewhere, Famous"
    ["about"]=> string(6) "ME ME1"
    ["created_at"]=> string(31) "Wed, 28 Mar 2012 15:29:36 +0000"
    ["website_url"]=> string(21) "http://pinclarity.com"

    xxxxxxxxxxxxxxxx
    IMAGES

    ["image_medium_url"]=> string(69) "http://media-cache-ec1.pinterest.com/avatars/berone-1351187098_75.jpg"
    ["image_small_url"]=> string(69) "http://media-cache-ec4.pinterest.com/avatars/berone-1351187098_30.jpg"
    ["image_large_url"]=> string(70) "http://media-cache-ec7.pinterest.com/avatars/berone-1351187098_140.jpg"

    xxxxxxxxxxxxxxxxxx
    MISCELLANEOUS

    ["implicitly_followed_by_me"]=> bool(false)
    ["explicitly_followed_by_me"]=> bool(false)
    ["is_partner"]=> bool(false)
    ["followed_by_me"]=> bool(false)
    ["type"]=> string(4) "user"
    ["repins_from"]=> array(0) { }



    ["followed_boards"]=> array(0) { }
    ["blocked_by_me"]=> bool(false) } }

*/

?>
