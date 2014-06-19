<?php

class Pin {

    public $track_type;

    public
        $id
    , $user_id
    , $board_id
    , $domain
    , $method
    , $is_repin
    , $parent_pin
    , $via_pinner
    , $image_url
    , $image_square_url
    , $link
    , $origin_pin
    , $origin_pinner
    , $description
    , $location
    , $dominant_color
    , $rich_product
    , $repin_count
    , $like_count
    , $comment_count
    , $created_at
    , $image_id;


    public
        $has_attribution
    , $attribution_provider_name
    , $attribution_author_name
    , $attribution_author_url
    , $attribution_title
    , $attribution_url;

    public $keyword;

    function __construct($track_type) {
        if (!$track_type) {
            $this->track_type = "track";
        } else {
            $this->track_type = $track_type;
        }

        $keyword = "";
    }

    function setKeyword($keyword) {
        $this->keyword = $keyword;
    }

    function loadFromAPIBlock($data) {
        if (is_array($data)) {
            $this->id = $data['id'];
            $this->user_id = $this->getUserID($data);
            $this->board_id = $this->getBoardID($data);
            $this->domain = $data['domain'];
            $this->method = $data['method'];
            $this->is_repin = $data['is_repin'];
            $this->parent_pin = $this->getParentPin($data);
            $this->via_pinner = $this->getViaPinner($data);
            $this->image_url = $data['image_medium_url'];
            $this->image_square_url = $data['image_square_url'];
            $this->link = $data['link'];
            $this->description = $data['description'];
//            $this->location = $data['location'];
            $this->origin_pin = $this->getOriginPin($data);
            $this->origin_pinner = $this->getOriginPinner($data);
            $this->dominant_color = $data['dominant_color'];
            $this->rich_product = $this->getRichProduct($data);
            $this->repin_count = $data['repin_count'];
            $this->like_count = $data['like_count'];
            $this->comment_count = $data['comment_count'];
            $this->created_at = parsePinterestCreationDateToTimestamp($data['created_at']);
            $this->image_id = "";

            if ($data['attribution'] == null) {
                $this->has_attribution = false;
            } else {
                $this->has_attribution = true;

                $this->attribution_provider_name = $this->getAttributionProviderName($data);
                $this->attribution_author_name = $this->getAttributionAuthorName($data);
                $this->attribution_author_url = $this->getAttributionAuthorURL($data);
                $this->attribution_title = $this->getAttributionTitle($data);
                $this->attribution_url = $this->getAttributionURL($data);
            }
        }
    }

    function getOriginPin($data) {
        if (array_key_exists("origin_pin", $data)) {
            if (is_array($data['origin_pin'])) {
                if (array_key_exists("id", $data['origin_pin'])) {
                    return $data['origin_pin']['id'];
                }
            }
        }
        return "";
    }

    function getOriginPinner($data) {
        if (array_key_exists("origin_pinner", $data)) {
            if (is_array($data['origin_pinner'])) {
                if (array_key_exists("id", $data['origin_pinner'])) {
                    return $data['origin_pinner']['id'];
                }
            }
        }
        return "";
    }

    function getAttributionProviderName($data) {
        if (array_key_exists("attribution", $data)) {
            if (is_array($data['attribution'])) {
                if (array_key_exists("provider_name", $data['attribution'])) {
                    return $data['attribution']['provider_name'];
                }
            }
        }
        return "";
    }

    function getAttributionAuthorName($data) {
        if (array_key_exists("attribution", $data)) {
            if (is_array($data['attribution'])) {
                if (array_key_exists("author_name", $data['attribution'])) {
                    return $data['attribution']['author_name'];
                }
            }
        }
        return "";
    }

    function getAttributionAuthorURL($data) {
        if (array_key_exists("attribution", $data)) {
            if (is_array($data['attribution'])) {
                if (array_key_exists("author_url", $data['attribution'])) {
                    return $data['attribution']['author_url'];
                }
            }
        }
        return "";
    }

    function getAttributionTitle($data) {
        if (array_key_exists("attribution", $data)) {
            if (is_array($data['attribution'])) {
                if (array_key_exists("title", $data['attribution'])) {
                    return $data['attribution']['title'];
                }
            }
        }
        return "";
    }

    function getAttributionURL($data) {
        if (array_key_exists("attribution", $data)) {
            if (is_array($data['attribution'])) {
                if (array_key_exists("url", $data['attribution'])) {
                    return $data['attribution']['url'];
                }
            }
        }
        return "";
    }

    function getRichProduct($data) {
        if (array_key_exists("rich_metadata", $data)) {

            if (is_array($data['rich_metadata'])) {
                if (array_key_exists("id", $data['rich_metadata'])) {
                    return $data['rich_metadata']['id'];
                }
            }

            else if (is_string($data['rich_metadata'])) {
                return $data['rich_metadata'];
            }




        }

        return "";
    }

    function getParentPin($data) {
        if (array_key_exists("parent_pin", $data)) {
            if (is_array($data['parent_pin'])) {
                if (array_key_exists("id", $data['parent_pin'])) {
                    return $data['parent_pin']['id'];
                }
            }
        }

        return "";
    }

    function getViaPinner($data) {
        if (array_key_exists("via_pinner", $data)) {
            if (is_array($data['via_pinner'])) {
                if (array_key_exists("id", $data['via_pinner'])) {
                    return $data['via_pinner']['id'];
                }
            }
        }

        return "";
    }

    function getUserID($data) {
        if (array_key_exists("pinner", $data)) {
            if (is_array($data['pinner'])) {
                if (array_key_exists("id", $data['pinner'])) {
                    return $data['pinner']['id'];
                }
            }
        }

        return "";
    }

    function getBoardID($data) {
        if (array_key_exists("board", $data)) {
            if (is_array($data['board'])) {
                if (array_key_exists("id", $data['board'])) {
                    return $data['board']['id'];
                }
            }
        }

        return "";
    }
}


/* RAW DATA INFORMATION

	xxxxxxxxxxxxxxx
	KEY INFORMATION

			["id"]=> string(18) "277745501992466889"
			["domain"]=> string(9) "ecoki.com"
			["is_uploaded"]=> bool(false)
			["like_count"]=> int(2)
			["comment_count"]=> int(0)
			["description"]=> string(26) "wheat-grass easter bouquet"
			["link"]=> string(44) "http://ecoki.com/growing-easter-wheat-grass/"
			["is_repin"]=> bool(true)
			["repin_count"]=> int(2)
			["created_at"]=> string(31) "Thu, 28 Mar 2013 17:29:02 +0000"
			["pinner"]=> array(1)
				["id"]=> string(18) "193866096367900033"
 			["parent_pin"]=> array(1) {
				["id"]=> string(18) "193865958931825211"
			["via_pinner"]=> array(1)
				["id"]=> string(18) "193866096367900033"
			["board"]=> array(1) {
				["id"]=> string(18) "277745570708237924" }
			["method"]=> string(11) "bookmarklet"


	xxxxxxxxxxxxxxxx
	IMAGES

			["image_square_size_pixels"]
				["width"]=> int(45)
				["height"]=> int(45)

			["image_medium_url"]=> string(87) "http://media-cache-ec6.pinterest.com/200x/08/e4/0a/08e40abd22bfcc907438dba173db2bad.jpg"

			["image_medium_size_points"]
				["width"]=> int(200)
				["height"]=> int(303)

			["image_large_size_points"]
				["width"]=> int(330)
				["height"]=> int(500)

			["image_square_size_points"]
				["width"]=> int(45)
				["height"]=> int(45)

			["image_large_url"]=> string(88) "http://media-cache-ec6.pinterest.com/1200x/08/e4/0a/08e40abd22bfcc907438dba173db2bad.jpg"
			["image_large_size_pixels"]
				["width"]=> int(330)
				["height"]=> int(500)

			["image_square_url"]=> string(88) "http://media-cache-ec6.pinterest.com/45x45/08/e4/0a/08e40abd22bfcc907438dba173db2bad.jpg"

			["image_medium_size_pixels"]=> array(2)
				["width"]=> int(200)
				["height"]=> int(303)


	xxxxxxxxxxxxxxxxxx
	MISCELLANEOUS
			["price_currency"]=> string(3) "USD"
			["type"]=> string(3) "pin"
			["attribution"]=> NULL
			["price_value"]=> float(0)
			["is_playable"]=> bool(false)
			["is_video"]=> bool(false)


	xxxxxxxxxxxxxxxxxxx
	 ["attribution"]=> array(7) {
		["title"]=> string(15) "lego math games"
		["url"]=> string(53) "http://www.flickr.com/photos/46927993@N08/7196056522/"

		["provider_icon_url"]=> string(63) "http://passets-ec.pinterest.com/images/api/attrib/flickr@2x.png"
		["author_name"]=> string(26) "Cathy @ Nurturestore.co.uk"
		["provider_favicon_url"]=> string(60) "http://passets-ak.pinterest.com/images/api/attrib/flickr.png"
		["author_url"]=> string(42) "http://www.flickr.com/photos/46927993@N08/"
		["provider_name"]=> string(6) "flickr"
	}

*/

?>
