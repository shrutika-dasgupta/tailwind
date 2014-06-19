<?php
/**
 * Alerts when things happen
 * sends via email
 *
 * @author  Will
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\CLI;
use Guzzle\Http\Client;
use Pinleague\Feed;
use Discover\DataFeedEntries;
use Pinleague\SocialWorth;

try {

    $start = microtime(true);
    $social = new SocialWorth(array('googleplus'));
    $data = DataFeedEntries::fetch($limit = 50);

    foreach ($data as $entry) {

        CLI::write($entry->id . " is running");

        $entry_urls[$entry->id] = $entry->url;

        $entry_ids[] = $entry->id;
    }

    $all_socialworth = $social->value($entry_urls);

    $entries_pulled_implode = '"' . implode('","', $entry_ids) . '"';

    $DBH = DatabaseInstance::DBO();

    $number_of_calls = count($entry_ids);

    $stop = microtime(true);
    $range = $stop - $start;

    $DBH->query("INSERT INTO track_rate_limits
                (facebook, twitter, pinterest, google_plus, time_taken)
                VALUES
                (0, 0, 0, $number_of_calls ,$range)");

    $entries_pulled_implode = '"' . implode('","', $entry_ids) . '"';

    $DBH = DatabaseInstance::DBO();

    $STH = $DBH->prepare("UPDATE data_feed_entries
                          SET updated_at = :current_time
                          WHERE id in ($entries_pulled_implode)");
    $STH->execute(array(":current_time" => time()));
}
catch (Exception $e) {
    var_dump($e);
    die();

}
