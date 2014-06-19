<?php

use Pinleague\Pinterest;
use Pinleague\CLI;

/**
 * Class Profiles
 * Collection of profiles
 *
 * @author Will
 */
class Profiles extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $table = 'data_profiles_new',
        $columns =
        array(
            'user_id',
            'username',
            'first_name',
            'last_name',
            'email',
            'image',
            'about',
            'domain_url',
            'domain_verified',
            'website_url',
            'facebook_url',
            'twitter_url',
            'google_plus_url',
            'location',
            'board_count',
            'pin_count',
            'like_count',
            'follower_count',
            'following_count',
            'created_at',
            'p_gender',
            'last_pulled',
            'track_type',
            'timestamp'
        ),
        $primary_keys = array('user_id');

    /**
     * @author  Will
     * @return string
     */
    public function __toString()
    {
        return $this->stringifyField('username', ', ', '');
    }

    /**
     * @author Yesh
     * @param array $dont_update_these_columns
     * @return $this
     */
    public function insertUpdateDB($dont_update_these_columns = array())
    {
        array_push($dont_update_these_columns, 'user_id', 'email');

        $append = "ON DUPLICATE KEY UPDATE ";

        foreach ($this->columns as $column) {
            if($column != "track_type"){
                if (!in_array($column, $dont_update_these_columns)) {
                    $append .= "$column = VALUES($column),";
                }
            }
        }

        if (!in_array('track_type', $dont_update_these_columns)) {
            $append .=
                "track_type=IF(VALUES(track_type)='user',
                'user',IF(VALUES(track_type)='competitor', 'competitor', track_type))";
        } else {
            $append = rtrim($append,',');
        }

        return $this->saveModelsToDB('INSERT INTO', $append);
    }


    /**
     * @author  Will
     * @todo    remove CLI dependency
     *
     */
    public function updateViaAPI()
    {
        /*
         * We return these to track what happened
         */
        $batched_success = array();
        $batched_failed  = array();

        /*
         * Since we are doing a batch request, we want a persistant instance of the Pinterest
         * object. We also want to designate that the calls should be batched.
         */
        $Pinterest              = Pinterest::getInstance();
        $Pinterest->batch_calls = true;

        CLI::write('Adding update calls to Multi-curl');
        foreach ($this->models as $model) {

            CLI::write("Adding $model->username");
            $batch_number = $Pinterest->getProfileInformation($model->username);

        }

        CLI::write('Sending multi curl request');

        $data = $Pinterest->sendBatchRequests();

        CLI::write('Boom. Multi-curl finished');

        CLI::write('Updating models');

        $xx = 0;
        foreach ($this->models as $key => &$model) {

            try {
                /*
                 * The data is sorted from the multi curl in the same order it was added.
                 * So we can access the correct data by increasing the value as we iterate.
                 */
                $model = $model->update($data[$xx]);
                CLI::write("Updated $model->username's model");

                /*
                 * Store the successfully updated profiles in an array so we can track
                 * it later. 0 is the code for success
                 */
                $batched_success[$model->username] = 0;

            }
            catch (ProfileException $e) {

                CLI::write("$model->username failed to update (" . $e->getCode() . ")");

                /*
                 * Store the failed profiles in an array so we can track them as well. We track
                 * the code offered from pinterest as well.
                 */
                $batched_failed[$model->username] = $e->getCode();

                /*
                 * We remove the model from the collection so it doesn't get updated later
                 * @todo -> make a "save me" attribute in the model so we know when to save
                 * instead of doing this
                 */
                CLI::write("Removing $model->username from collection");
                $this->removeModel($key);

            }

            $xx++;
        }

        return array('success' => $batched_success, 'failed' => $batched_failed);
    }

    /**
     * @author  Will
     */
    public function averageRepinCount() {
        $total = 0;

        foreach ($this->models as $model) {
            $total += $model->getRepinCount();
        }

        return round($total / $this->count());
    }

    /**
     * @author  Will
     */
    public function averageCommentCount() {
        $total = 0;

        foreach ($this->models as $model) {
            $total += $model->getCommentCount();
        }

        return round($total / $this->count());
    }



    /**
     * @author      Yesh
     *              Alex
     *
     * @param       $user_ids
     * @param array $bookmarks
     *
     * @return array
     *
     * This pretty much a direct copy of \Boards->getMissingPinInfo(), with some
     * minor changes to be able to work with the update_pins_info engine.
     *
     * This was written to ensure that any other scripts using the getMissingPinInfo()
     * method would not break.
     *
     */
    public function findPinsFromUsers(
        $user_ids,
        $bookmarks = array())
    {
        $pins_arr    = array();
        $dead_users = array();

        $error_codes  = array(8, 12, 13);
        $rerun_users = array();

        /*
         * Since we are doing a batch request, we want a persistant instance of the Pinterest
         * object. We also want to designate that the calls should be batched.
         */

        $Pinterest              = new Pinterest();
        $Pinterest->batch_calls = true;

        // If loop tries to accomadate all formats of the data sent
        // TODO: Reformat this block when fixing update_pins_info

        if (is_object($user_ids)){
            foreach ($user_ids as $user) {
                if (isset($bookmarks[$user->user_id])){
                    $Pinterest->getUserPins($user->user_id,
                        array('bookmark' => $bookmarks[$user->user_id]));
                } else {
                    $Pinterest->getUserPins($user->user_id);
                };

            }
        } else {
            foreach ($user_ids as $user) {
                if(is_object($user)){
                    if (isset($bookmarks[$user->user_id])){
                        $Pinterest->getUserPins($user->user_id,
                            array('bookmark' => $bookmarks[$user->user_id]));
                    } else {
                        $Pinterest->getUserPins($user->user_id);
                    }
                }
                else {
                    if (isset($bookmarks[$user])){
                        $Pinterest->getUserPins($user,
                            array('bookmark' => $bookmarks[$user]));
                    } else {
                        $Pinterest->getUserPins($user);
                    }
                }
            }
        }

        $data = $Pinterest->sendBatchRequests();

        $bookmark_array = array();

        foreach ($data as $curl_key => $curl_result) {

            CLI::write("code: " . $curl_result->code);

            if (!($curl_result->code === 0)) {


                /**
                 * @todo var dump in the model? we should avoid this practice :)
                 */
                echo var_dump($curl_result->code) . " on " . var_dump($user_ids[$curl_key]) . "\n";

                if ($curl_result->code === 40) {
                    array_push($dead_users, $user_ids[$curl_key]);
                } else if (in_array($curl_result->code, $error_codes)) {
                    array_push($rerun_users, $user_ids[$curl_key]);
                }

                ApiError::create(
                        'User Pins',
                            $user_ids[$curl_key]->user_id,
                            $curl_result->message,
                            'profiles collection.' . __LINE__,
                            $curl_result->code,
                            $curl_result->bookmark
                );

            } else if (count($curl_result->data) == 0) {

                ApiError::create(
                        'User Pins',
                            $user_ids[$curl_key]->user_id,
                            $curl_result->message,
                            'profiles collection. No data returned even with a successful response.' . __LINE__,
                            $curl_result->code,
                            $curl_result->bookmark
                );

                $errors_found = ApiError::numberOfEntriesExplicit(
                                        "User Pins",
                                            $user_ids[$curl_key]->user_id,
                                            $curl_result->bookmark,
                                            $curl_result->code,
                                            flat_date(time())
                );

                CLI::write(Log::debug($errors_found . " similar call errors found"));

                if($errors_found > 2){
                    array_push($dead_users, $user_ids[$curl_key]);
                    CLI::write(Log::debug("Too many similar errors with the same user - marking it dead so we don't keep pulling."));
                }
            }

            /*
             * We want to store this data in a pin object and then ultimately in the
             * Pins collection so we can easily manipulate it
             */
            if (isset($curl_result->data)) {

                foreach ($curl_result->data as $pinData) {

                    $pins_arr[] = $pinData;
                }
            }

            /*
             * If a bookmark exists, we need to start another set of multi curls to capture
             * all of the pins from those users. In an effort to save time, we'll do another
             * batch request in case there are multiple bookmarked returned sets
             */
            if (isset($curl_result->bookmark)) {

                $bookmark_array[$curl_result->data[0]->pinner->id] = $curl_result->bookmark;
            }
        }




        return array(
            'pins_arr' => $pins_arr,
            'dead_users' => $dead_users,
            'rerun_users' => $rerun_users,
            'bookmarks' => $bookmark_array);
    }

}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class ProfilesException extends CollectionException {}
