<?php

/*
 * This script is used to go through all the images in the
 * map_feed_entry_images table and get their height and width
 *
 * @author Yesh
 */

chdir(__DIR__);
include '../../../bootstrap/bootstrap.php';

use Content\MapFeedEntryImage,
    Content\MapFeedEntryImages,
    Content\MapFeedEntryDescriptions,
    Pinleague\CLI;

Log::setLog(__FILE__, 'CLI');

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        Log::warning('Engine Already Running');
        CLI::sleep(10);
        CLI::stop();
    }

    $engine->start();
    Log::info('Engine Started');

    $DBH = DatabaseInstance::DBO();
    Log::debug('Connected to Database');

    Log::info('Fetching Feed Entry descriptions');
    $descriptions = MapFeedEntryDescriptions::fetch();

    $parsed_images     = new MapFeedEntryImages();
    $feed_description_ids = [];
    $parsed_image_urls = [];

     // Parse image src URLs from @var $descriptions

    foreach ($descriptions as $description) {

        $feed_description_ids[] = $description->feed_entry_id;

         preg_match_all(
             '/img[^>]+src="([^"]+)"/si',
             $description->description,
             $matches
            );

         $parsed_image_urls = array_merge(
            $parsed_image_urls,
            array_slice(array_get($matches, 1), 0, 10)
         );

         foreach ($parsed_image_urls as $image) {
             $parsed_entry_image                = new MapFeedEntryImage();
             $parsed_entry_image->feed_entry_id = $description->feed_entry_id;
             $parsed_entry_image->url           = $image;
             $parsed_entry_image->primary       = 0;

             $parsed_images->add($parsed_entry_image);
         }
    }

    Log::info("Saving {$parsed_images->count()} entry images that have been parsed");
    try {
        $parsed_images->saveModelsToDB();
    }
    catch (CollectionException $e) {
        CLI::alert(Log::notice('No entry images to save from descriptions.'));
    }

    /*
    * Update the updated_at field in map_feed_entry_descriptions
    */
    Log::info("Updating updated_at in map_feed_entry_descriptions");
    $feed_description_ids_implode = '"' . implode('","', $feed_description_ids) . '"';

    $STH = $DBH->prepare("UPDATE map_feed_entry_descriptions
                          SET updated_at = :updated_at
                          WHERE feed_entry_id in ($feed_description_ids_implode)");
    $STH->execute(array(":updated_at" => time()));

    Log::info('Fetching Feed Entry images');
    $images = MapFeedEntryImages::fetch($limit = 25);

    $map_feed_images = new MapFeedEntryImages();

    if (!empty($images)) {

        $image_urls = [];
        $image_data = [];


        foreach ($images as $image) {
            $image_urls[]            = $image->url;
            $image_data[$image->url] = $image;
        }

        $image_urls = array_unique($image_urls);

        Log::debug('Making batch call to fast image');
        $image_sizes = FastImage::batch($image_urls);

        foreach ($image_sizes as $image_url => $image) {

            Log::debug('Parsing height and width for ' . $image_url);
            list($width, $height) = $image->getSize();

            if (empty($width) && empty($height)) {
                Log::debug('Making single curl for ' . $image_url);
                try {
                    $image_curl = new FastImage($image_url,
                                                 new FastImage\Transports\CurlAdapter());
                    list($width, $height) = $image_curl->getSize();
                } catch (Exception $e) {
                    Log::notice($e);
                }
            }

            if ($width >= 120 || $height >= 120) {

                Log::debug("Updating image url {$image_url}");

                DB::table('map_feed_entry_images')
                    ->where('url', $image_url)
                    ->update(["width" => $width,
                              "height" => $height,
                              "updated_at" => time()]);
            }
        }
    }

    Log::info('Completed');
    $engine->complete();

    Log::runtime();
    Log::memory();

}
catch (Exception $e) {
    Log::error($e);
    $engine->fail();

    CLI::stop();
}
