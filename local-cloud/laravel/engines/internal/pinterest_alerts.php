<?php

use Pinleague\CLI,
    Pinleague\Pinterest,
    Illuminate\Filesystem\Filesystem,
    HipChat\HipChat;

/**
 * Alerts when things happen
 * sends via email
 *
 * @author  Will
 */
chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

try {

    /*
     * Testing Data
     */
    $pin_id   = '68720068090';
    $username = 'karmaloop';
    $user_id  = '103653366332131182';
    $domain   = 'karmaloop.com';
    $board_id = '137438963137';
    $keyword  = 'wedding';

    /*
    * Global list of table columns to ignore
    * per call ignored columns can be added below
    */
    $global_ignore_list = array(
        'timestamp'
    );

    $storage_path = ROOT_PATH . '../storage';

    $engine = new Engine(__FILE__);
    $engine->start();

    $now = date('g:ia');
    CLI::h1('Starting tests (' . $now . ')');

    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to database');

    $pinterest = new Pinterest\BasePinterest(
        Config::get('pinterest.client_id'),
        Config::get('pinterest.secret'),
        new Pinterest\Transports\CurlAdapter()
    );

    /*
     * List of endpoints + tables to check consistency for
     */
    $calls = array(
        array(
            'call'       => 'getPinLikes',
            'table'      => false,
            'endpoint'   => "pins/$pin_id/likes/",
            'expected'   => array("last_name", "domain_verified", "following_count", "image_medium_url", "like_count", "full_name", "image_small_url", "id", "first_name", "domain_url", "location", "follower_count", "type", "website_url", "board_count", "username", "twitter_url", "facebook_url", "pin_count", "about", "created_at", "image_large_url","gender"),
            'ignore'     => array('pin_id'),
            'map'        => array('id' => 'liker_user_id'),
            'parameters' => array(
                'page_size'  => 150,
                'add_fields' => 'user.follower_count,user.following_count,user.pin_count,user.like_count,user.board_count,user.domain_url,user.domain_verified,user.facebook_url,user.location,user.twitter_url,user.website_url,user.created_at,user.about'
            )
        ),
        array(
            'call'       => 'getPinRepins',
            'table'      => false,
            'endpoint'   => "pins/$pin_id/repinned_onto/",
            'parameters' => array(
                'add_fields' => 'board.owner,board.follower_count',
                'page_size'  => '250'
            ),
            'expected'   => array("category", "is_collaborative", "name", "url", "created_at", "follower_count", "collaborated_by_me", "owner", "followed_by_me", "type", "id", "image_thumbnail_url", "layout"),
            'ignore'     => array(),
            'map'        => array()
        ),
        array(
            'call'       => 'getPinComments',
            'table'      => false,
            'endpoint'   => "pins/$pin_id/comments/",
            'parameters' => array(
                'add_fields' => "user.follower_count,user.following_count,user.pin_count,user.like_count,user.board_count,user.domain_url,user.domain_verified,user.facebook_url,user.location,user.twitter_url,user.website_url,user.created_at,user.about"
            ),
            'expected'   => array("text", "created_at", "commenter", "last_name", "domain_verified", "following_count", "image_medium_url", "like_count", "full_name", "image_small_url", "id", "first_name", "domain_url", "location", "follower_count", "type", "gender", "website_url", "board_count", "username", "twitter_url", "facebook_url", "pin_count", "about", "created_at", "image_large_url", "type", "id"),
            'ignore'     => array(),
            'map'        => array(),
        ),
        array(
            'call'       => 'getUserIDFromUsername',
            'table'      => false,
            'endpoint'   => "users/$username/",
            'parameters' => array(),
            'expected'   => array("status", "code", "host", "generated_at", "message", "data", "last_name", "domain_verified", "following_count", "image_medium_url", "implicitly_followed_by_me", "tag", "full_name", "image_small_url", "id", "first_name", "domain_url", "explicitly_followed_by_me", "location", "pins", "is_partner", "followed_by_me", "type", "website_url", "board_count", "username", "repins_from", "twitter_url", "facebook_url", "follower_count", "pin_count", "about", "followed_boards", "email_verified", "created_at", "like_count", "image_large_url", "debug", "blocked_by_me"),
            'ignore'     => array(),
            'map'        => array(),
        ),
        array(
            'call'       => 'getProfileInformation',
            'table'      => false,
            'endpoint'   => "users/$username/",
            'parameters' => array(
            ),
            'expected'   => array("status", "code", "host", "generated_at", "message", "data", "last_name", "domain_verified", "following_count", "image_medium_url", "implicitly_followed_by_me", "tag", "full_name", "image_small_url", "id", "first_name", "domain_url", "explicitly_followed_by_me", "location", "pins", "is_partner", "followed_by_me", "type", "website_url", "board_count", "username", "repins_from", "twitter_url", "facebook_url", "follower_count", "pin_count", "about", "followed_boards", "email_verified", "created_at", "like_count", "image_large_url", "debug", "blocked_by_me"),
            'ignore'     => array(),
            'map'        => array(),
        ),
        array(
            'call'       => 'getProfileBoards',
            'table'      => false,
            'endpoint'   => "users/$username/boards/",
            'parameters' => array(
                'add_fields' => "board.owner,board.pin_count,board.follower_count,board.description,board.collaborator_count,board.image_cover_url,board.created_at"
            ),
            'expected'   => array("category", "is_collaborative", "name", "url", "pin_count", "created_at", "description", "follower_count", "collaborator_count", "image_cover_url", "collaborated_by_me", "owner", "followed_by_me", "type", "id", "image_thumbnail_url", "layout"),
            'ignore'     => array(),
            'map'        => array(),
        ),
        array(
            'call'       => 'getPinInformation',
            'table'      => 'data_pins_new',
            'endpoint'   => "pins/$pin_id/",
            'parameters' => array(
                'add_fields' => "pin.parent_pin,pin.location,pin.dominant_color,pin.client_id,pin.embed,pin.method,pin.rich_metadata,pin.via_pinner,pin.board,pin.pinner"
            ),
            'expected'   => array(),
            'ignore'     => array(),
            'map'        => array(),
        ),
        array(
            'call'       => 'getUserPins',
            'table'      => false,
            'endpoint'   => "users/$user_id/pins/",
            'parameters' => array(
                'add_fields' => "pin.parent_pin,pin.location,pin.dominant_color,pin.client_id,pin.embed,pin.method,pin.rich_metadata,pin.via_pinner,pin.board,pin.pinner"
            ),
            'expected'   => array(""),
            'ignore'     => array(),
            'map'        => array(),
        ),
        array(
            'call'       => 'getBoardsPins',
            'table'      => false,
            'endpoint'   => "boards/$board_id/pins",
            'parameters' => array(
                'add_fields' => "pin.parent_pin,pin.location,pin.dominant_color,pin.client_id,pin.embed,pin.method,pin.rich_metadata,pin.via_pinner,pin.board,pin.pinner"
            ),
            'expected'   => array("domain", "image_square_size_pixels", "width", "height", "image_medium_url", "like_count", "image_medium_size_points", "width", "height", "id", "image_large_size_points", "width", "height", "price_currency", "pinner", "id", "image_square_size_points", "width", "height", "parent_pin", "comment_count", "board", "id", "type", "method", "image_large_url", "location", "image_large_size_pixels", "width", "height", "attribution", "description", "price_value", "is_playable", "rich_metadata", "id", "via_pinner", "link", "client_id", "is_repin", "liked_by_me", "is_uploaded", "image_square_url", "repin_count", "created_at", "image_medium_size_pixels", "width", "height", "dominant_color", "embed", "is_video"),
            'ignore'     => array(),
            'map'        => array(),
        ),
        array(
            'call'       => 'getBoardInformation',
            'table'      => false,
            'endpoint'   => "boards/$board_id/",
            'parameters' => array(
                'add_fields' => "board.owner,board.pin_count,board.follower_count,board.description,board.collaborator_count,board.image_cover_url"
            ),
            'expected'   => array("status", "code", "host", "generated_at", "message", "data", "image_cover_url", "viewer_invitation", "images", "id", "category", "privacy", "owner", "id", "access", "follower_count", "followed_by_me", "type", "is_collaborative", "description", "pin_thumbnail_urls", "collaborator_count", "collaborated_by_me", "pin_count", "name", "url", "created_at", "cover", "id", "debug", "image_thumbnail_url"),
            'ignore'     => array(),
            'map'        => array(),
        ),
        array(
            'call'       => 'getSearchPinsFromKeyword',
            'table'      => false,
            'endpoint'   => "search/pins/",
            'parameters' => array(
                'add_fields' => "pin.parent_pin,pin.location,pin.dominant_color,pin.client_id,pin.embed,pin.method,pin.rich_metadata,pin.via_pinner,pin.board,pin.pinner",
                'query'      => $keyword,
                'page_size'  => 100
            ),
            'expected'   => array("domain", "image_square_size_pixels", "width", "height", "image_medium_url", "like_count", "image_medium_size_points", "width", "height", "id", "image_large_size_points", "width", "height", "price_currency", "pinner", "id", "image_square_size_points", "width", "height", "parent_pin", "comment_count", "board", "id", "type", "method", "image_large_url", "location", "image_large_size_pixels", "width", "height", "attribution", "description", "price_value", "is_playable", "rich_metadata", "id", "via_pinner", "link", "client_id", "is_repin", "liked_by_me", "is_uploaded", "image_square_url", "repin_count", "created_at", "image_medium_size_pixels", "width", "height", "dominant_color", "embed", "is_video"),
            'ignore'     => array(),
            'map'        => array(),
        ),
        array(
            'call'       => 'getBrandMentions',
            'table'      => false,
            'endpoint'   => "domains/$domain/pins/",
            'parameters' => array(
                'page_size'  => 250,
                'add_fields' => "pin.parent_pin,pin.location,pin.dominant_color,pin.client_id,pin.embed,pin.method,pin.rich_metadata,pin.via_pinner,pin.board,pin.pinner"
            ),
            'expected'   => array("domain", "image_square_size_pixels", "width", "height", "image_medium_url", "like_count", "image_medium_size_points", "width", "height", "id", "image_large_size_points", "width", "height", "price_currency", "pinner", "id", "image_square_size_points", "width", "height", "parent_pin", "comment_count", "board", "id", "type", "method", "image_large_url", "location", "image_large_size_pixels", "width", "height", "attribution", "description", "price_value", "is_playable", "rich_metadata", "id", "via_pinner", "link", "client_id", "is_repin", "liked_by_me", "is_uploaded", "image_square_url", "repin_count", "created_at", "image_medium_size_pixels", "width", "height", "dominant_color", "embed", "is_video"),
            'ignore'     => array(),
            'map'        => array(),
        ),
        array(
            'call'       => 'getProfileFollowers',
            'table'      => false,
            'endpoint'   => "users/$username/followers/",
            'parameters' => array(
                'page_size'  => 50,
                'add_fields' => "user.first_name,user.last_name,user.about,user.domain_url,user.domain_verified,user.website_url,user.facebook_url,user.twitter_url,user.location,user.board_count,user.pin_count,user.like_count,user.follower_count,user.following_count,user.created_at"
            ),
            'expected'   => array("last_name", "domain_verified", "following_count", "image_medium_url", "like_count", "full_name", "image_small_url", "id", "first_name", "domain_url", "location", "follower_count", "type", "website_url", "board_count", "username", "twitter_url", "facebook_url", "pin_count", "about", "created_at", "image_large_url","gender"),
            'ignore'     => array(),
            'map'        => array(),
        ),
        array(
            'call'       => 'getProfileFollowing',
            'table'      => false,
            'endpoint'   => "users/$username/following/",
            'parameters' => array(),
            'expected'   => array("username", "first_name", "last_name", "image_medium_url", "full_name", "image_small_url", "type", "id", "image_large_url", "gender"),
            'ignore'     => array(),
            'map'        => array(),
        ),
    );

    $main_body = '';
    foreach ($calls as $call) {

        /*
         * Taking these out of the array just makes it a little easier to deal with
         */
        $name       = $call['call'];
        $expected   = $call['expected'];
        $table      = $call['table'];
        $endpoint   = $call['endpoint'];
        $parameters = $call['parameters'];

        /*
         * If the test storage path doesn't exist yet, we want to make the dir
         */
        $tests_storage = $storage_path . '/logs/pinterest-test/';

        if (!file_exists($tests_storage)) {
            mkdir($tests_storage,0777,true);
            chmod($tests_storage,'+w');
        }

        $file_path = $tests_storage . "$name.json";

        if (array_key_exists('add_fields', $parameters)) {

            $add_fields = explode(',', $parameters['add_fields']);

            foreach ($add_fields as $field) {
                $column = explode('.', $field);

                if (!array_search($column[1], $expected)) {

                    $expected[] = $column[1];
                }

            }

        }

        if (file_exists($file_path)) {
            $previous = json_decode(file_get_contents($file_path));
            $expected = $previous->returned;
        }

        foreach ($expected as $key => $field) {
            if (
                in_array($field, $global_ignore_list)
                OR in_array($field, $call['ignore'])

            ) {
                unset($expected[$key]);
            }
        }

        /*
         * See what parameters were actually given
         */
        try {
            CLI::h2($name);

            $api_call = $pinterest->call("/v3/$endpoint", $parameters);

            if ($api_call['code'] === 0) {

                if (!$api_call['data']) {

                    throw new Exception($name . '() No data returned');

                } else {

                    $returned_fields = array();

                    if (array_key_exists(0, $api_call['data'])) {

                        ///I apologize for this montrosity of code
                        //if I could do it all over again, I would
                        //womp womp
                        $returned_fields = array_keys_multi($api_call['data'][0]);

                    } else {
                        $returned_fields = array_keys_multi($api_call);
                    }

                    $missing_fields = array();
                    $extra_fields   = array();

                    foreach ($expected as $field) {

                        if ($mapped_key = array_search($field, $call['map'])) {
                            $field = $mapped_key;
                        }

                        if (!in_array($field, $returned_fields)) {
                            $missing_fields[] = $field;
                        }
                    }

                    foreach ($returned_fields as $field) {
                        if (!in_array($field, $expected)) {
                            $extra_fields[] = $field;
                        }
                    }

                    $fields = array(
                        'expected' => $expected,
                        'returned' => $returned_fields
                    );

                    if (!empty($missing_fields) OR !empty($extra_fields)) {
                        if (empty($main_body)) {
                            $main_body = '<h2>There have been some changes made to the Pinterest API</h2>';
                        }
                        $status_mark = '&#10008;'; ///x mark
                        CLI::alert($name);

                        $missing_string  = var_export($missing_fields, true);
                        $extra_string    = var_export($extra_fields, true);
                        $response_string = var_export($returned_fields, true);

                        $main_body .=
                            '-----<br/>' .
                            '<strong>' . $name . '()</strong><br/>' .
                            '-----<br/>' .
                            "Missing Parameters (dump)" .
                            '<br/>' .
                            "<pre>$missing_string</pre>" .
                            "<br/><br/>" .
                            "Extra Parameters (dump)
                            <br/>" .
                            "<pre>$extra_string</pre>" .
                            "<br/><br/>" .
                            "Available Parameters (dump)
                            <br/>" .
                            "<pre>$response_string</pre>";



                    } else {
                        $status_mark = '&#10004;'; ///check mark
                        CLI::yay($name);
                    }

                    CLI::write('Saving results to file');
                    Filesystem::put($file_path,json_encode($fields));
                    //file_put_contents($file_path, json_encode($fields));

                }

            } else {
                throw new PinterestException($name . '() Pinterest API issue:', $api_call['code']);
            }

        }

        catch (PinterestException $e) {
            ///curious as to what the API issue is
            CLI::alert('Code (' . $e->getCode() . ') ' . $e->getMessage());

        }

        catch (Exception $e) {
            CLI::write('general exception');
            CLI::write(Log::error($e));
        }

    }

    $hip_chat = new HipChat(Config::get('hipchat.rooms.engineering.API_TOKEN'));

    if (!empty($main_body)) {



        $hip_chat->message_room(
                 Config::get('hipchat.rooms.engineering.ID'),
                 'Alert Beaver',
                 $main_body,
                 $notify = true,
                 HipChat::COLOR_RED
        );

        Mail::send('shared.emails.templates.blank',
                   array('main_body' => $main_body),

            function ($message) use ($now) {

                $message->from('coredev+bot@tailwindapp.com', 'Pinterest Alert Beaver');
                $message->to('coredev@tailwindapp.com', 'Tailwind Core Development Team');

                $message->subject('Changes to the Pinterest API | ' . $now);

            }

        );

    } else {

        $hip_chat->message_room(
                 Config::get('hipchat.rooms.engineering.ID'),
                 'Alert Beaver',
                 'There have been no changes to the Pinterest API since we last checked',
                 $notify = true,
                 HipChat::COLOR_GREEN
        );

    }

    CLI::seconds();
    CLI::end();
}

catch (Exception $e) {
    CLI::stop(Log::error($e));
}