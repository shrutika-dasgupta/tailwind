<?php
/**
 * The engine computes the footprints across a user_ids most influential
 * followers
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;

Log::setLog(__FILE__, 'CLI');

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);
    Log::info('Engine started');

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    }

    /*
     * Fetch the user_ids to be computed
     */
    $user_ids_to_compute = StatusProfile::fetchUserIds(
                                        $flag="calculate_influencers_footprint",
                                        $limit = 150);

    if (empty($user_ids_to_compute)) {
        Log::notice("No more users to compute");
        $engine->complete();
        CLI::stop();
    }

    $influencer_footprints = new CacheProfileInfluencersFootprint();

    foreach ($user_ids_to_compute as $user_id_to_compute) {

        $computed_user_ids[] = $user_id_to_compute->user_id;

        $category_aggregate =
            MapProfilesCategoryHash::
                computeFootprint('footprint_count',
                                 $user_id_to_compute->user_id,
                                 'profile');

        foreach ($category_aggregate as $details) {

            if (!empty($details->category)) {
                $influencer_footprint = new CacheProfileInfluencerFootprint();

                $influencer_footprint->user_id  = $user_id_to_compute->user_id;
                $influencer_footprint->category = $details->category;
                $influencer_footprint->footprint_count = $details->sum;

                $influencer_footprints->add($influencer_footprint);
            }
        }
    }

    try {
        $influencer_footprints->saveModelsToDB();
    } catch (CollectionException $e) {
        Log::warning("No more footprints to save");
    }

    /*
     * TODO: Reset computed user_ids in status_profiles
     */

    Log::debug("Updated computed user_ids in status_profiles");
    $computed_user_ids = implode(",", $computed_user_ids);

    $DBH = DatabaseInstance::DBO();

    $current_time = time();

    $STH = $DBH->prepare("UPDATE status_profiles
                          SET calculate_influencers_footprint = :current_time
                          WHERE user_id in ($computed_user_ids)");
    $STH->execute([":current_time" => $current_time]);

    $engine->complete();

    CLI::write(Log::runtime(). 'total runtime');
    CLI::write(Log::memory().' peak memory usage');

    CLI::h1(Log::info('Complete'));


} catch (Exception $e) {
    $engine->fail();
    Log::error($e);
    CLI::stop();
}
