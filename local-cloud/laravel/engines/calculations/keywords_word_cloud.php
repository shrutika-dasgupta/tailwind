<?php
/**
 * Create word clouds for keywords
 *
 * @author Yesh
 */

ini_set('memory_limit', '200M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;

Log::setLog(__FILE__);

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    } else {
        $engine->start();
        $start = microtime(true);

        CLI::write(Log::info('Engine started'));

        $numberOfCallsInBatch = 10;

        CLI::write('Connect to DB');
        $DBH = DatabaseInstance::DBO();

        $current_date = flat_date('day');

        CLI::write(Log::info('Pulling keywords for calculations'));

        $STH = $DBH->prepare("SELECT keyword
                            FROM status_keywords
                            WHERE last_calced_wordcloud < :current_date
                            AND track_type != 'orphan'
                            AND (track_type = 'user'
                            OR track_type = 'keyword_tracking')
                            GROUP BY keyword
                            LIMIT $numberOfCallsInBatch");
        $STH->execute(array(':current_date' => $current_date));
        $keywords_from_db = $STH->fetchAll();


        // If no more keywords to pull, exit.
        if (empty($keywords_from_db)){
            CLI::alert(Log::notice("No more keywords to pull"));
            $engine->complete();
            exit;
        }

        foreach($keywords_from_db as $keyword){
            $keywords[] = $keyword->keyword;
        }

        $word_clouds = new \Caches\KeywordsWordClouds();

        // Ignore certain words when parsing pin descriptions.
        $ignored_words = Config::get('keywords.ignored_words');

        foreach($keywords as $keyword){
            CLI::write(Log::debug('Creating Word Cloud for ' . $keyword));

            // Setting the flag for pulled keyword to completed
            $STH = $DBH->prepare("UPDATE status_keywords
                                SET last_calced_wordcloud = :time
                                WHERE keyword = :keyword");
            $STH->execute(array(':time' => time(),
                                ':keyword' => $keyword));

            $STH = $DBH->prepare("SELECT added_at
                                FROM status_keywords
                                WHERE keyword = :keyword");
            $STH->execute(array(':keyword' => $keyword));
            $keyword_added_at = $STH->fetchAll();
            // A week to the date the keyword was first added
            $keyword_added_plus_week = strtotime("+1 week",
                                                 $keyword_added_at[0]->added_at);

            $STH = $DBH->prepare("SELECT max(created_at) as newest_created_at,
                                min(created_at) as oldest_created_at
                                FROM map_pins_keywords
                                WHERE keyword = :keyword
                                LIMIT 1");
            $STH->execute(array(':keyword' => $keyword));
            $date_from_db = $STH->fetchAll();

            if (!empty($date_from_db)) {

                /** Here we check to see if the keyword has been added for less than a
                * week.
                *
                * The $oldest_date variable is very crucial as it is the one that
                * sets the date that keywords are calculated from.
                *
                * If the current_date is lesser than a week from when it was added
                * ($keyword_added_plus_week), the $oldest_date is set to the oldest
                * created_at date.
                *
                * If the current_date is more than a week, we take the created_at
                * date to a date that is exactly a month old and run the keyword calcs
                * for each then from then.
                */

                if ($current_date < $keyword_added_plus_week){

                    $created_at_date = $date_from_db[0]->oldest_created_at;

                    $oldest_date = flat_date('day', $created_at_date);

                } else {

                    $created_at_date = $date_from_db[0]->newest_created_at;

                    // Finding the date from the latest created_at to
                    // a month before
                    $date_one_month_old = strtotime("-1 Month",
                                              $created_at_date);

                    // The flat date for the $date_one_month_old.
                    $oldest_date = flat_date('day', $date_one_month_old);
                }

                /** In here we have a while loop which is satisfied only when
                 * we have calculated all the days from the $oldest_date to
                 * $current_date.
                 *
                 * So, all the pins descriptions with in each day is parsed and
                 * all the words are given a weight based on the frequency of their
                 * occurrence.
                 *
                 * Once that is done we send the data to the database
                 */

                CLI::write(Log::debug('Starting to loop through each day'));

                while($current_date > $oldest_date){

                    $next_date = strtotime("+1 day", $oldest_date);

                    $STH = $DBH->prepare("
                                        SELECT a.description
                                        FROM map_pins_descriptions as a
                                        JOIN map_pins_keywords as b
                                        ON a.pin_id = b.pin_id
                                        WHERE b.keyword = :keyword
                                        AND b.created_at
                                        BETWEEN :oldest_date and :next_date");
                    $STH->execute(array(':keyword'     => $keyword,
                                        ':oldest_date' => $oldest_date,
                                        ':next_date'   => $next_date));

                    $descriptions = $STH->fetchAll();


                    if(!empty($descriptions)){

                        /*
                         * concatenate all pin descriptions together
                         */
                        CLI::write(Log::debug("Concatenation all descriptions for the day to break down into word counts."));
                        $start_concat = microtime(true);

                        unset($wordcloud);
                        foreach($descriptions as $desc){
                            if (!isset($wordcloud)) {
                                $remove_char = array(",", "[", "]", "{", "}", "\\", '"', "'", "!", "|");
                                $wordcloud = str_replace($remove_char, "", strtolower($desc->description));
                                $wordcloud = preg_replace("/\.$/","",$wordcloud);
                                $wordcloud = preg_replace("/\.\s/","",$wordcloud);
                                $wordcloud = preg_replace("/\?$/","",$wordcloud);
                                $wordcloud = preg_replace("/\?\s/","",$wordcloud);
                                $wordcloud = preg_replace("/\:$/","",$wordcloud);
                                $wordcloud = preg_replace("/\:\s/","",$wordcloud);
                            } else {
                                $remove_char = array(",", "[", "]", "{", "}", "\\", '"', "'", "!");
                                $wordcloud = str_replace($remove_char, "", strtolower($desc->description));
                                $wordcloud = preg_replace("/\.$/","",$wordcloud);
                                $wordcloud = preg_replace("/\.\s/","",$wordcloud);
                                $wordcloud = preg_replace("/\?$/","",$wordcloud);
                                $wordcloud = preg_replace("/\?\s/","",$wordcloud);
                            }
                        }

                        $stop_concat = microtime(true);
                        $range_concat = $stop_concat - $start_concat;
                        CLI::write(Log::debug('Description Concatenation time: ' . $range_concat));

                        $word          = array();
                        $keyword_words = array();
                        $words         = explode(' ', $wordcloud);

                        $start_count_words = microtime(true);
                        $keyword_words_count = array_count_values(str_word_count($wordcloud, 1));

                        $stop_count_words = microtime(true);
                        $range_count_words = $stop_count_words - $start_count_words;

                        CLI::write(Log::debug('Keywords implode and count time: ' . $range_count_words));

                        $start_sort_words = microtime(true);
                        CLI::write(Log::debug("Sort and add word counts to collections"));
                        arsort($keyword_words_count);

                        $number_words_to_save = 0;
                        foreach($keyword_words_count as $key => $value){
                                $number_words_to_save ++;
                                if (strlen($key) > 2 && !in_array($key, $ignored_words)){
                                    $word_cloud = new \Caches\KeywordWordCloud();
                                    $word_cloud->keyword = $keyword;
                                    $word_cloud->date = $oldest_date;
                                    $word_cloud->word = $key;
                                    $word_cloud->word_count = $value;
                                    $word_clouds->add($word_cloud);
                                    if ($number_words_to_save == 100){
                                        break;
                                    }
                                }
                            }
                        CLI::write(Log::debug("Finish Sort and add word counts to collections"));

                        $stop_sort_words = microtime(true);
                        $range_sort_words = $stop_sort_words - $start_sort_words;

                        CLI::write(Log::debug('Sort words calc time: ' . $range_sort_words));
                    }
                $oldest_date = $next_date;
                }
            }

            CLI::write(Log::debug('Save word cloud to DB'));
            try{
                $word_clouds->insertUpdateDB();
            } catch (CollectionException $e){
                CLI::alert(Log::warning('No data to save'));
            }
        }

        $engine->complete();

        CLI::write(Log::runtime(). 'total runtime');
        CLI::write(Log::memory().' peak memory usage');

        CLI::h1(Log::info('Complete'));
    }
} catch (EngineException $e){

    CLI::alert($e->getMessage());
    Log::warning($e);
    CLI::stop();

} catch (PDOException $e){

    CLI::alert($e->getMessage());
    Log::error($e);
    $engine->fail();

} catch (PinterestException $e) {

    CLI::alert($e->getMessage());
    Log::error($e);
    $engine->fail();
    CLI::stop();

} catch (Exception $e){

    CLI::alert($e->getMessage());
    Log::error($e);
    $engine->fail();
    CLI::stop();

}
