<?php

ini_set('memory_limit', '1000M');

use Pinleague\CLI;

chdir(__DIR__);
require_once('../../bootstrap/bootstrap.php');

Log::setLog(__FILE__);

$args = getopt("a:");

$calculator = new ProfileHistoryCalculator($args['a']);
$calculator->calculate();

CLI::write(Log::runtime(). 'total runtime');
CLI::write(Log::memory().' peak memory usage');

/**
 * Calculates historical data for a profile.
 *
 * @author Daniel
 */
class ProfileHistoryCalculator
{
    protected $account;

    protected $profile;

    protected $history_reference;

    protected $history_begin_date;

    protected $history_end_date;
    
    protected $distribution = array(0.43, 0.20, 0.065, 0.015, 0.01);

    public function __construct($account_id)
    {
        if (empty($account_id) || !$account = UserAccount::find($account_id)) {
            CLI::alert(Log::warning('Invalid account_id'));
            CLI::stop();
        }

        $this->account = $account;
        $this->profile = $account->profile();

        // Date of user's Pinterest profile creation.
        $this->history_begin_date = $this->profile->created_at;

        // Date of old profile history calculations.
        $this->history_reference = $this->historyReference();
        $this->history_end_date = $this->history_reference->date;
    }

    /**
     * Run calculations.
     *
     * @return void
     */
    public function calculate()
    {
        // Don't calculate if user is refreshing data.
        if ($this->history_end_date == flat_date(time())) {
            CLI::alert('Estimates Cannot Be Calculated On Same Day As Signup');
            CLI::stop();
        }

        try {
            CLI::h1(Log::debug('Starting History Calculations'));

            $user_id = $this->profile->user_id;

            $follower_history = $this->calculateFollowers();
            $board_history    = $this->calculateBoards();
            $pin_history      = $this->calculatePins();

            $history = array();

            $follower_total = $board_total = $pin_total = 0;
            $repin_total = $like_total = $comment_total = 0;

            $time = $this->history_begin_date;

            while ($time < $this->history_end_date) {
                $date = date('m/d/y', $time);

                $follower_total += array_get($follower_history, $date, 0);
                $board_total    += array_get($board_history, $date, 0);
                $pin_total      += array_get($pin_history['pins'], $date, 0);
                $repin_total    += array_get($pin_history['repins'], $date, 0);
                $like_total     += array_get($pin_history['likes'], $date, 0);
                $comment_total  += array_get($pin_history['comments'], $date, 0);

                $history[$date] = array(
                    'followers' => $follower_total,
                    'boards'    => $board_total,
                    'pins'      => $pin_total,
                    'repins'    => $repin_total,
                    'likes'     => $like_total,
                    'comments'  => $comment_total,
                );

                $time = strtotime('+1 day', $time);
            }

            CLI::yay(Log::info('Completed History Calculations'));
            CLI::seconds();

            $this->store($history);
        }
        catch (Exception $e) {
            CLI::alert($e->getMessage());
            Log::error($e);
            CLI::stop();
        }
    }

    /**
     * Retrieves a user's oldest existing history record.
     * Returns the second oldest (if possible) for accuracy.
     *
     * @return CalcProfileHistory
     */
    protected function historyReference()
    {
        $db = DatabaseInstance::DBO();

        $results = $db->query(
            "SELECT *
             FROM calcs_profile_history
             WHERE user_id = {$this->profile->user_id}
             ORDER BY `date` ASC
             LIMIT 2"
        )->fetchAll();

        if (empty($results)) {
            CLI::alert(Log::warning('User Does Not Have Any Profile History'));
            CLI::stop();
        }

        $data = (count($results) == 2) ? $results[1] : $results[0];

        $history = new CalcProfileHistory();

        return $history->loadDBData($data);
    }

    /**
     * Store calculations.
     *
     * @param array $data The data to store.
     *
     * @return void
     */
    protected function store($data)
    {
        CLI::h1(Log::debug('Starting Storage of History Calculations'));

        $histories = new CalcProfileHistories();

        $total_histories = count($data);

        $i = 1;
        foreach ($data as $date => $event) {
            $history                           = new CalcProfileHistory();
            $history->user_id                  = $this->profile->user_id;
            $history->date                     = strtotime($date);
            $history->follower_count           = $event['followers'];
            $history->following_count          = 0;
            $history->reach                    = 0;
            $history->board_count              = $event['boards'];
            $history->pin_count                = $event['pins'];
            $history->repin_count              = $event['repins'];
            $history->like_count               = $event['likes'];
            $history->comment_count            = $event['comments'];
            $history->pins_atleast_one_repin   = 0;
            $history->pins_atleast_one_like    = 0;
            $history->pins_atleast_one_comment = 0;
            $history->pins_atleast_one_engage  = 0;
            $history->timestamp                = time();
            $history->estimate                 = 1;

            if ($i == $total_histories) {
                // Update the original first history record if we've been using the second.
                $history_oldest = CalcProfileHistory::find($this->profile->user_id, 'first');
                if ($this->history_reference != $history_oldest) {
                    $history->following_count          = $this->history_reference->following_count;
                    $history->reach                    = $this->history_reference->reach;
                    $history->pins_atleast_one_repin   = $this->history_reference->pins_atleast_one_repin;
                    $history->pins_atleast_one_like    = $this->history_reference->pins_atleast_one_like;
                    $history->pins_atleast_one_comment = $this->history_reference->pins_atleast_one_comment;
                    $history->pins_atleast_one_engage  = $this->history_reference->pins_atleast_one_engage;
                    $history->estimate                 = NULL;

                    $history_oldest->loadDBData($history);
                    $history_oldest->insertUpdateDB();
                }
            } else {
                $histories->add($history);
            }

            $i++;
        }

        try {
            $histories->saveModelsToDB();
        }
        catch (CollectionException $e) {
            Log::warning($e);
            CLI::alert($e->getMessage());
            CLI::stop();
        }

        $this->sendEmail();

        CLI::yay(Log::info('Completed Storage of History Calculations'));
        CLI::seconds();
    }

    /**
     * Sends an email notification to the user.
     *
     * @return void
     */
    protected function sendEmail()
    {
        foreach ($this->account->users() as $user) {
            $data = array(
                'first_name'  => $user->first_name,
                'username'    => $this->profile->username,
                'signup_date' => date('F jS, Y', $this->account->created_at),
            );

            // Only send emails to non-competitor accounts.
            if (empty($this->account->competitor_of)) {
                $email = \Pinleague\Email::instance(UserHistory::PROFILE_HISTORY_EMAIL_SENT);
                $email->subject('Your Pinterest Profile History Is Ready!');
                $email->body('profile_history', $data);
                $email->to($user);
                $email->send();
            }
        }

        CLI::yay(Log::info('Estimated History Emails Sent'));
    }

    /**
     * Calculate board growth.
     *
     * @return array
     */
    protected function calculateBoards()
    {
        $data = array();

        $boards = Board::find(array(
            'user_id' => $this->profile->user_id,
        ));

        foreach ($boards as $board) {
            $key = date('m/d/y', $board->created_at);
            $data[$key] = array_get($data, $key, 0) + 1;
        }

        return $data;
    }

    /**
     * Calculate pin, comment and likes (estimate) growth.
     *
     * @return array
     */
    protected function calculatePins()
    {
        $data = array(
            'pins'     => array(),
            'repins'   => array(),
            'comments' => array(),
            'likes'    => array(),
        );

        $pins = Pin::find(array(
            'user_id' => $this->profile->user_id,
        ));

        foreach ($pins as $pin) {
            $key = date('m/d/y', $pin->created_at);
            $data['pins'][$key] = array_get($data['pins'], $key, 0) + 1;

            if (array_sum($data['repins']) < $this->history_reference->repin_count) {
                $repin_count = $pin->repin_count;
                $repin_date  = $pin->created_at;

                // Intelligently distribute repins across a pin's first five days.
                $distributed_repin_count = 0;
                foreach ($this->distribution as $growth) {
                    $repins = round($repin_count * $growth);

                    $key = date('m/d/y', $repin_date);
                    $data['repins'][$key] = array_get($data['repins'], $key, 0) + $repins;

                    $distributed_repin_count += $repins;
                    $repin_date = strtotime('+1 day', $repin_date);
                }

                // Linearly distribute remaining repins across random dates.
                $repin_count -= $distributed_repin_count;
                for ($i = 1; $i <= $repin_count; $i++) {
                    $date = mt_rand($repin_date, $this->history_end_date);

                    $key = date('m/d/y', $date);
                    $data['repins'][$key] = array_get($data['repins'], $key, 0) + 1;

                    if (array_sum($data['repins']) >= $this->history_reference->repin_count) {
                        break;
                    }
                }
            }

            foreach ($pin->comments() as $comment) {
                // Skip comments created after history_end_date.
                if ($comment->created_at > $this->history_end_date) {
                    continue;
                }

                $key = date('m/d/y', $pin->created_at);
                $data['comments'][$key] = array_get($data['comments'], $key, 0) + 1;
            }

            if (array_sum($data['likes']) < $this->history_reference->like_count) {
                $like_count = $pin->like_count;
                $like_date  = $pin->created_at;

                // Intelligently distribute likes across a pin's first five days.
                $distributed_like_count = 0;
                foreach ($this->distribution as $growth) {
                    $likes = round($like_count * $growth);

                    $key = date('m/d/y', $like_date);
                    $data['likes'][$key] = array_get($data['likes'], $key, 0) + $likes;

                    $distributed_like_count += $likes;
                    $like_date = strtotime('+1 day', $like_date);
                }

                // Linearly distribute remaining like across random dates.
                $like_count -= $distributed_like_count;
                for ($i = 1; $i <= $like_count; $i++) {
                    $date = mt_rand($like_date, $this->history_end_date);
                    $key = date('m/d/y', $date);
                    $data['likes'][$key] = array_get($data['likes'], $key, 0) + 1;

                    if (array_sum($data['likes']) >= $this->history_reference->like_count) {
                        break;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Calculate (estimate) follower growth.
     *
     * @return array
     */
    protected function calculateFollowers()
    {
        $data = array();

        $db = DatabaseInstance::DBO();

        $followers = $db->query(
            "SELECT follower_created_at
             FROM data_followers
             WHERE user_id = {$this->profile->user_id}"
        )->fetchAll();

        foreach ($followers as $follower) {
            // Total followers should not exceed that of history_reference.
            if (array_sum($data) >= $this->history_reference->follower_count) {
                break;
            }

            // Smooth out follower growth across random dates.
            if ($follower->follower_created_at < $this->profile->created_at) {
                $date = mt_rand($this->profile->created_at, $this->history_end_date);
            } else {
                $date = mt_rand($follower->follower_created_at, $this->history_end_date);
            }

            $key = date('m/d/y', $date);
            $data[$key] = array_get($data, $key, 0) + 1;
        }

        return $data;
    }
}