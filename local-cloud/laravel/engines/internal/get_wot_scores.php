<?php

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Content\StatusFeeds,
    Guzzle\Http,
    Pinleague\CLI;

Log::setLog(__FILE__, 'CLI');

try {

    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        Log::warning('Engine Already Running');
        CLI::stop();
    }

    $engine->start();
    CLI::write(Log::info('Engine started'));

    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to Database');

    $feeds = StatusFeeds::fetchByWOTFlag($limit = 150);

    if (empty($feeds)) {
        Log::notice("No more feeds to fetch");
        $engine->complete();
        CLI::stop();
    }

    $wot_categories = new MapFeedWotCategories();

    foreach($feeds as $feed) {

        $completed_feeds[] = $feed->id;

        $wot_endpoint = "http://api.mywot.com/0.4/public_link_json2?key=4ec5d75e98e121d714d0d151a28567c2a81871f7&callback=process&hosts=";

        $url_to_send = $wot_endpoint . $feed->url;

        $client = new Http\Client();
        try {
            $request = $client->get($url_to_send)->send();
            $processed_request = str_replace("process(", "", $request->getBody(true));

            $encoded_json = str_replace(strrchr($request->getBody(true),"}"),
                                        "}",
                                        $processed_request);

            $decoded_json = json_decode($encoded_json, True);
            $categories   = array_values($decoded_json)[0]["categories"];
        } catch (Exception $e) {
            Log::warning("{$feed->id} is causing an error during request");
        }

        if(!is_null($categories)) {

            foreach ($categories as $category => $reliability) {

                $wot_category = new MapFeedWotCategory();
                $wot_category->feed_id = $feed->id;
                $wot_category->curated = $feed->curated;
                $wot_category->category_identifier = $category;
                $wot_category->reliability_score = $reliability;
                $wot_category->added_at = time();

                $wot_categories->add($wot_category);
            }
        }
    }

    Log::debug("Saving WOT categories");
    try {
        $wot_categories->insertUpdateDB();
    } catch (CollectionException $e) {
        Log::notice("No categories to save from WOT");
    }

    Log::debug("Set last_pulled_wot in Feeds");

    $completed_feeds = join(",", $completed_feeds);

    $STH = $DBH->prepare("UPDATE status_feeds
                          SET last_pulled_wot = :current_time
                          WHERE id in ($completed_feeds)");
    $STH->execute([":current_time" => time()]);


    $engine->complete();
    CLI::write(Log::info('Complete'));

    CLI::write(Log::runtime(). 'total runtime');
    CLI::write(Log::memory().' peak memory usage');

} catch (Exception $e) {

    Log::error($e->getMessage());
    $engine->fail();
    CLI::stop();

}
