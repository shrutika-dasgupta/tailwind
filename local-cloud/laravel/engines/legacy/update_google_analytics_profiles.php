<?php
/**
 * @author Alex
 * Date: 4/24/14 2:54 PM
 *
 * This script runs through any google analytics profiles in the user_analytics table
 *         that might be missing data, and updates their metadata
 *         (i.e. name of the account, timezone, website url, whether they have eCommerce tracking
 *         enabled, etc.).
 * 
 */

use Pinleague\CLI;
use Pinleague\Pinterest;
use Pinleague\PinterestException;

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';
include("classes/googleanalytics.php");
include('includes/functions.php');
include("classes/crawl.php");

Log::setLog(__FILE__, "CLI");

Config::set('app.debug', true);

try {
    CLI::h1(Log::info('Starting update_google_analytics_profiles engine'));

    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        sleep(10);
        Log::notice('Engine running | Sleep 10');
        throw new EngineException('Engine is running');
    }

    $engine->start();

    $analytics_profiles = DB::select(
        "select * from user_analytics where track_type != 'unauthorized_token' and track_type != 'profile_does_not_exist' and name is null and profile != ''"
    );

    $DBH = DatabaseInstance::DBO();

    $count = 0;

    if(count($analytics_profiles) > 0){

        Log::info('Found GA accounts that need to be updated - pulling data from Google Analytics API');

        foreach($analytics_profiles as $profile){

            $analytics = new GoogleAnalytics($profile->token);

            $profile_num = str_replace("ga:","",$profile->profile);

            $account_id = $profile->account_id;
            $profile_string = $profile->profile;
            $token = $profile->token;
            $track_type = $profile->track_type;

            Log::info("Pulling data from GA API for account_id: $account_id, looking for profile: $profile_string");
            $json = $analytics->call("https://www.googleapis.com/analytics/v3/management/accounts/~all/webproperties/~all/profiles?max-results=10000&key=AIzaSyA2hnhXoVCy4mdtPdwT2rpbqOZBzmkkZMM");

            $data = json_decode($json);

            if(!empty($data->error)){

                Log::warning("Got an error: " . $data->error->code . " : " . $data->error->message);

                if($data->error->code == 401){

                    Log::warning(
                       "Error 401: Invalid Credentials, Unauthorized Token.
                       Setting track_type to 'unauthorized_token'"
                    );

                    $STH = $DBH->prepare(
                               "UPDATE user_analytics
                                SET
                                   track_type = :track_type,
                                   timestamp = :timestamp
                                WHERE
                                   account_id = :account_id
                                   AND profile = :profile
                                   AND token = :token"
                    );

                    $STH->execute(
                        array(
                             ':track_type' => "unauthorized_token",
                             ':timestamp' => time(),
                             ':account_id' => $account_id,
                             ':profile' => $profile_string,
                             ':token' => $token
                        )
                    );
                    continue;
                }
            }


            if(count($data->items) != 0){

                $has_profile = false;
                foreach($data->items as $api_profile) {
                    if ($api_profile->id == $profile_num){

                        Log::info(
                           "Found data for this profile! Updating the metadata fields in the table",
                               $api_profile
                        );

                        $has_profile = true;

                        $name = $api_profile->name;
                        $timezone = $api_profile->timezone;
                        $currency = $api_profile->currency;
                        $accountId = $api_profile->accountId;
                        $webPropertyId = $api_profile->webPropertyId;
                        $eCommerceTracking = $api_profile->eCommerceTracking;
                        $websiteUrl = $api_profile->websiteUrl;

                        $STH = $DBH->prepare(
                            "UPDATE user_analytics
                             SET
                                name = :name,
                                timezone = :timezone,
                                currency = :currency,
                                accountId = :accountId,
                                webPropertyId = :webPropertyId,
                                eCommerceTracking = :eCommerceTracking,
                                websiteUrl = :websiteUrl,
                                timestamp = :timestamp
                             WHERE
                                account_id = :account_id
                                AND profile = :profile
                                AND token = :token"
                        );

                        $STH->execute(
                            array(
                                ':name'     => $name,
                                ':timezone' => $timezone,
                                ':currency' => $currency,
                                ':accountId' => $accountId,
                                ':webPropertyId' => $webPropertyId,
                                ':eCommerceTracking' => $eCommerceTracking,
                                ':websiteUrl' => $websiteUrl,
                                ':timestamp' => time(),
                                ':account_id' => $account_id,
                                ':profile' => $profile_string,
                                ':token' => $token
                            )
                        );

                        continue;
                    }
                }
            }

            while(!empty($data->nextLink) && !$has_profile) {
                $json = $analytics->call($data->nextLink);

                $data = json_decode($json);

                Log::debug("Looking through next batch of 1000 profiles");

                if(count($data->items) != 0){

                    $has_profile = false;
                    foreach($data->items as $api_profile) {
                        if ($api_profile->id == $profile_num){

                            Log::info(
                               "Found data for this profile! Updating the metadata fields in the table",
                                   $api_profile
                            );

                            $has_profile = true;

                            $name = $api_profile->name;
                            $timezone = $api_profile->timezone;
                            $currency = $api_profile->currency;
                            $accountId = $api_profile->accountId;
                            $webPropertyId = $api_profile->webPropertyId;
                            $eCommerceTracking = $api_profile->eCommerceTracking;
                            $websiteUrl = $api_profile->websiteUrl;

                            $STH = $DBH->prepare(
                                       "UPDATE user_analytics
                                        SET
                                           name = :name,
                                           timezone = :timezone,
                                           currency = :currency,
                                           accountId = :accountId,
                                           webPropertyId = :webPropertyId,
                                           eCommerceTracking = :eCommerceTracking,
                                           websiteUrl = :websiteUrl,
                                           timestamp = :timestamp
                                        WHERE
                                           account_id = :account_id
                                           AND profile = :profile
                                           AND token = :token"
                            );

                            $STH->execute(
                                array(
                                     ':name'     => $name,
                                     ':timezone' => $timezone,
                                     ':currency' => $currency,
                                     ':accountId' => $accountId,
                                     ':webPropertyId' => $webPropertyId,
                                     ':eCommerceTracking' => $eCommerceTracking,
                                     ':websiteUrl' => $websiteUrl,
                                     ':timestamp' => time(),
                                     ':account_id' => $account_id,
                                     ':profile' => $profile_string,
                                     ':token' => $token
                                )
                            );

                            continue;
                        }
                    }
                }
            }

            if(!$has_profile){

                Log::warning("No match found for this profile! Setting track_type to 'profile_does_not_exist'");

                $STH = $DBH->prepare(
                           "UPDATE user_analytics
                            SET
                               track_type = :track_type,
                               timestamp = :timestamp
                            WHERE
                               account_id = :account_id
                               AND profile = :profile
                               AND token = :token"
                );

                $STH->execute(
                    array(
                         ':track_type' => "profile_does_not_exist",
                         ':timestamp' => time(),
                         ':account_id' => $account_id,
                         ':profile' => $profile_string,
                         ':token' => $token
                    )
                );
            }

            $count++;
        }
    } else {
        Log::notice('No more GA accounts requiring updates!');
    }

    $engine->complete();

    CLI::write(Log::runtime(). 'total runtime');
    CLI::write(Log::memory().' peak memory usage');

    CLI::h1(Log::info('Complete'));

}
catch (EngineException $e) {

    CLI::alert($e->getMessage());
    Log::error($e);
    CLI::stop();

}
catch (PinterestException $e) {

    CLI::alert($e->getMessage());
    Log::error($e);
    $engine->complete();
    CLI::stop();


} catch (PDOException $e) {

    CLI::alert($e->getMessage());
    Log::error($e);
    $engine->fail();
    CLI::stop();

} catch (Exception $e) {

    CLI::alert($e->getMessage());
    Log::error($e);
    $engine->fail();
    CLI::stop();
}