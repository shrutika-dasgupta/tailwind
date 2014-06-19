<?php

ini_set('memory_limit', '2000M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;

$numberOfCallsInBatch = 10000;

Log::setLog(__FILE__, 'CLI');

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);
    Log::info('Engine started');

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    }

    $decoded_hashes = new MapProfilesCategoryHash();
    $category_footprints = MapProfilesCategoryHash::fetch($numberOfCallsInBatch);

    if (empty($category_footprints)) {
        Log::notice("No more user_ids to decode");
        $engine->complete();
        CLI::stop();
    }

    $completed_footprints = [];

    foreach ($category_footprints as $footprint) {

        $category = [];
        $completed_footprints[] = $footprint->user_id;

        $activity_decoded = CategoryFootprint::decodeHash($footprint->activity_indv_hash);
        $influence_decoded = CategoryFootprint::decodeHash($footprint->influence_indv_hash);
        $board_count_decoded = CategoryFootprint::decodeHash($footprint->board_indv_count_hash);
        $footprint_decoded = CategoryFootprint::decodeHash($footprint->footprint_hash);
        $recency_decoded = CategoryFootprint::decodeHashRecency($footprint->recency_hash);

        foreach ($activity_decoded as $category_name => $activity_count) {
                $category[$category_name]['activity_count'] = $activity_count;
        }

        foreach ($influence_decoded as $category_name => $influence_count) {
                $category[$category_name]['influence_count'] = $influence_count;
        }

        foreach ($board_count_decoded as $category_name => $board_count) {
                $category[$category_name]['board_count'] = $board_count;
        }

        foreach ($footprint_decoded as $category_name => $footprint_count) {
                $category[$category_name]['footprint_count'] = $footprint_count;
        }

        foreach ($recency_decoded as $recency_order => $category_name) {
                $category[$category_name]['recency_order'] = $recency_order;
        }

        foreach ($category as $category_name => $category_details) {
            $decoded_hash = new MapProfileCategoryHash();
            $decoded_hash->user_id  = $footprint->user_id;
            $decoded_hash->category = $category_name;
            if (isset($category_details['activity_count'])) {
                $decoded_hash->activity_count = $category_details['activity_count'];
            }
            if (isset($category_details['influence_count'])) {
                $decoded_hash->influence_count = $category_details['influence_count'];
            }
            if (isset($category_details['board_count'])) {
                $decoded_hash->board_count = $category_details['board_count'];
            }
            if (isset($category_details['recency_order'])) {
                $decoded_hash->recency_order = $category_details['recency_order'];
            }

            $decoded_hash->footprint_count = $category_details['footprint_count'];

            $decoded_hashes->add($decoded_hash);
        }
    }

    $decoded_hashes->saveModelsToDB();


    $completed_footprints = implode(",", $completed_footprints);

    $DBH = DatabaseInstance::DBO();
    Log::debug("Set last_pulled_hash to 1");
    $DBH->query("UPDATE map_profiles_category_footprint
                 SET last_pulled_hash = 1
                 WHERE user_id in ($completed_footprints)");

    $engine->complete();

    CLI::h1(Log::info('Complete'));

    CLI::write(Log::runtime(). 'total runtime');
    CLI::write(Log::memory().' peak memory usage');

} catch (Exception $e) {
    $engine->fail();
    Log::error($e);
    CLI::stop();
}
