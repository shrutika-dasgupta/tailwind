<?php

class Board {
    public $track_type;

    public $id
    , $user_id
    , $owner_user_id
    , $url
    , $is_collaborator
    , $is_owner
    , $collaborator_count
    , $image_cover_url
    , $name
    , $description
    , $category
    , $layout
    , $pins
    , $followers
    , $created_at;

    function __construct($track_type) {
        if (!$track_type) {
            $this->track_type = "track";
        } else {
            $this->track_type = $track_type;
        }
    }


    function loadFromAPIBlockWithUserId($user_id, $data) {
        if (is_array($data)) {
            if ($data['id']) {
                $this->id = $data['id'];
                $this->user_id = $user_id;
                $this->owner_user_id = $this->getUserId($data);
                $this->url = $data['url'];
                $this->is_collaborator = $data['is_collaborative'];
                if ($user_id == $this->getUserId($data)) {
                    $this->is_owner = true;
                } else {
                    $this->is_owner = false;
                }

                $this->collaborator_count = $data['collaborator_count'];
                $this->image_cover_url = $data['image_cover_url'];
                $this->name = $data['name'];
                $this->description = $data['description'];
                $this->category = $data['category'];
                $this->layout = $data['layout'];
                $this->pins = $data['pin_count'];
                $this->followers = $data['follower_count'];
                $this->created_at = parsePinterestCreationDateToTimestamp($data['created_at']);
            }
        }
    }

    function getUserId($data) {
        if (array_key_exists("owner", $data)) {
            if (is_array($data['owner'])) {
                if (array_key_exists("id", $data['owner'])) {
                    return $data['owner']['id'];
                }
            }
        }

        return "";
    }

}


/* RAW DATA INFORMATION

    xxxxxxxxxxxxxxxxxx
    KEY


            ["image_cover_url"]=> string(89) "http://media-cache-is0.pinimg.com/custom_covers/200x150/277745570708237924_1351288352.jpg"
            ["id"]=> string(18) "277745570708237924"
            ["category"]=> string(15) "holidays_events"
            ["privacy"]=> string(6) "public"
            ["owner"]=> array(1)
                ["id"]=> string(18) "277745639427674010"
            ["follower_count"]=> int(68375)
            ["is_collaborative"]=> bool(true)
            ["description"]=> string(335) "~ * TABLESCAPES * ~ Who doesn't love preparing the Dining Room TABLE for a party? Wonderful Centerpieces and beautiful Dishes. That important event that brings together family and friends. This is what memories are made of! * ~ ~ > For Invites please see my ~ Invites and Messages ~ board. Happy Pinning! < ~ Please No Soliciting!"
            ["collaborator_count"]=> int(5374)
            ["pin_count"]=> int(140249)
            ["name"]=> string(28) "TableScapes...Table Settings"
            ["url"]=> string(39) "/doorite579/tablescapes-table-settings/"
            ["created_at"]=> string(31) "Thu, 17 May 2012 23:56:39 +0000"

    xxxxxxxxxxxxxxxxxx
    Images

            ["images"]=> array(5)
                [0]=> string(88) "http://media-cache-ec6.pinterest.com/75x75/08/e4/0a/08e40abd22bfcc907438dba173db2bad.jpg"
                [1]=> string(88) "http://media-cache-ec3.pinterest.com/75x75/20/91/65/2091655b30b00be8c5c41f57deed5d54.jpg"
                [2]=> string(88) "http://media-cache-ec5.pinterest.com/75x75/18/25/ad/1825ada595ab90acbf4688cb1136b29c.jpg"
                [3]=> string(88) "http://media-cache-ec2.pinterest.com/75x75/dc/0a/7c/dc0a7c1db016b944cba22e4ecec5cd3b.jpg"
                [4]=> string(88) "http://media-cache-ec2.pinterest.com/75x75/84/bf/46/84bf46b21a4c6e3b51f49f9b464f89b6.jpg"

            ["pin_thumbnail_urls"]=> array(5) {
                [0]=> string(88) "http://media-cache-ec6.pinterest.com/45x45/08/e4/0a/08e40abd22bfcc907438dba173db2bad.jpg"
                [1]=> string(88) "http://media-cache-ec3.pinterest.com/45x45/20/91/65/2091655b30b00be8c5c41f57deed5d54.jpg"
                [2]=> string(88) "http://media-cache-ec5.pinterest.com/45x45/18/25/ad/1825ada595ab90acbf4688cb1136b29c.jpg"
                [3]=> string(88) "http://media-cache-ec2.pinterest.com/45x45/dc/0a/7c/dc0a7c1db016b944cba22e4ecec5cd3b.jpg"
                [4]=> string(88) "http://media-cache-ec2.pinterest.com/45x45/84/bf/46/84bf46b21a4c6e3b51f49f9b464f89b6.jpg" }

            ["image_thumbnail_url"]=> string(111) "http://media-cache-ec5.pinterest.com/upload/277745570708237924_board_thumbnail_2013-03-28-17-32-42_36173_60.jpg"


    //xxxxxxxxxxxxxxxx
    Miscellaneous


            ["viewer_invitation"]=> NULL
            ["access"]=> array(0) { }
            ["followed_by_me"]=> bool(false)
            ["type"]=> string(5) "board"
            ["collaborated_by_me"]=> bool(false)
            ["cover"]=> array(1)
                ["id"]=> string(18) "277745501991042624"


*/



?>