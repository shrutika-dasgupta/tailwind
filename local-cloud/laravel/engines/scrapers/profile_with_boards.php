<?php
/**
 * This script scrapes the profile page of a given username and tries to get
 * profile data and the public boards of the user
 *
 * @usage
 *      php profile_with_boards.php tailwind
 *
 * @author  Will
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Symfony\Component\DomCrawler\Crawler,
    Guzzle\Http,
    Pinleague\CLI;

CLI::h1('Starting scraper');

try {
    /*
     * We set the log to the name of the file so it's easier to debug. This
     * basically sets the name of the log to this file name.beaverlog
     *
     * Passing CLI as the channel means that any logs we write will also be
     * printed to the terminal
     */
    Log::setLog(__FILE__, 'CLI');

    /*
     * Engines help us keep track of when the script has last run. It also helps
     * ensure we aren't running it more than once in different places
     */
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    }
    Log::info('Engine started');

    if (count($argv) < 2) {
        throw new InvalidArgumentException(
            "There was no username passed as an argument"
        );
    }
    /**
     * @var string The username, passed in as the first argument
     */
    $username = $argv[1];
    /**
     * @var string Where we are going to scrape
     */
    $endpoint = "http://www.pinterest.com/";
    /**
     * @var string The user agent we will use when we hit the page
     *             We use the user agent string for an iPhone 3 to get the
     *             lightest mobile site we can
     */
    $user_agent =
        'Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_0 like Mac OS X; ' .
        'en-us) AppleWebKit/528.18 (KHTML, like Gecko) ' .
        'Version/4.0 Mobile/7A341 Safari/528.16';

    $guzzle = new Http\Client($endpoint);
    $guzzle->setUserAgent($user_agent);

    $request = $guzzle->get("/$username")->send();

    Log::info('Sent request for pinterest.com/' . $username);

    $headers = $request->getHeaders();

    Log::debug('Pinterest version ' . $headers['pinterest-version']);
    Log::debug('Pinterest breed ' . $headers['Pinterest-Breed']);

    $body = new Crawler($request->getBody(true));

    /**
     * @var string On the page is a bunch of javascript that has most of the
     *             data that we want to grab. It is a javascript function with
     *             a bunch of variables, so we want to grab it and turn it into
     *             json so we can manipulate it
     */
    $js   = $body->filter('#jsInit');
    $text = $js->text();

    Log::info('Found the #jsInit text');

    /**
     * @var string the opening "bracket" of the json we want to grab
     */
    $start_token = 'P.start.start({';
    /**
     * @var string the closing "bracket" of the json we want to grab
     */
    $end_token = '})';

    $start_position = strpos($text, $start_token) + strlen($start_token);
    $end_position   = strrpos($text, $end_token);

    if ($start_position === false OR $end_position === false) {
        throw new Exception('Could not find the json in the jsInit script tag');
    }


    $js   = substr($text, $start_position, $end_position - $start_position);
    $json = preg_replace(
        "/([{,])([a-zA-Z][^: ]+):/",
        "$1\"$2\":",
        '{' . $js . '}'
    );

    $json = json_decode($json);

    /**
     * @var array The boards data we grab from json
     */
    $boards_data = [];
    /**
     * @var string The Pinterest profile we are trying to find
     */
    $profile = new Profile;
    $profile->username = $username;

    Log::info('Finding boards and profile data');

    foreach ($json->tree->children as $key => $child) {

        Log::debug("Checking for:UserProfilePage | found " . $child->name);

        if ($child->name != 'UserProfilePage') {
            continue;
        }

        foreach ($json->tree->children[$key]->children as $k=> $c) {

            Log::debug("Checking for:UserProfileContent | found " . $c->name);

            if ($c->name != 'UserProfileContent') {
                continue;
            }

            $profile->loadAPIData($c->data);

            $boards_data = $json->tree
                ->children[$key]
                ->children[$k]
                ->children[0]
                ->children[0]
                ->data;
            break;
        }

        break;
    }

    if (is_null($profile->user_id)) {
        throw new Exception("The profile user_id wasn't found");
    }

    Log::info('Found profile');

    $boards = new Boards;

    foreach ($boards_data as $board_data) {

        $board = new Board;
        $board->user_id = $profile->user_id;

        foreach ($board->columns as $col) {
            CLI::write($col);
        }

        $board->loadAPIData($board_data);

        $boards->add($board);
    }

    $profile->insertUpdateDB();
    Log::debug('Saved profile');

    $boards->insertUpdateDB();
    Log::debug('Saved boards');

    $engine->complete();
    Log::info('Engine completed');

    Log::runtime();
    Log::memory();

    CLI::stop();
}
catch (Exception $e) {
    Log::error($e);
}

