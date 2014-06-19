<?php namespace Publisher;

use DatabaseInstance,
    DBModelException,
    Log,
    PDO,
    PDODatabaseModel,
    Pinleague\Pinterest,
    Session,
    User,
    UserAccount;

/**
 * Post Model
 *
 * Through out the model there are various mentions of automatic saved
 * pins and manual scheduled pins.
 *
 * The main difference between Auto scheduled pins and manually scheduled pins
 * are:
 *  - Auto scheduled pins are the pins which are posted on the pre defined
 *  time slots which are set by the user. (Recurring time slots)
 *  - Manually scheduled pins are the ones, which users save with a time slot
 *  unique to the pin (One-time time slots)
 *
 * @author  Yesh
 * @author  Will
 *
 */
class Post extends PDODatabaseModel
{
    /**
     * Schedule type determines when / how it gets posted
     * @const
     */
    const SCHEDULE_TYPE_AUTO   = 'auto';
    const SCHEDULE_TYPE_MANUAL = 'manual';

    /**
     * The status of the post
     * @const
     */
    const STATUS_SENT              = 'S';
    const STATUS_PROCESSING        = 'P';
    const STATUS_QUEUED            = 'Q';
    const STATUS_CANCELLED         = 'C';
    const STATUS_FAILED            = 'F';
    const STATUS_AWAITING_APPROVAL = 'A';

    /**
     * The session key used to store drafts
     * @const
     */
    const DRAFT_SESSION_KEY = 'publisher-draft-posts';

    public $table = 'publisher_posts';

    public $columns = array(
        'id',
        'account_id',
        'pin_id',
        'parent_pin',
        'board_name',
        'domain',
        'image_url',
        'link',
        'description',
        'added_at',
        'sent_at',
        'status',
        'order',
    );

    public $primary_keys = array('id');

    /**
     * A tailwind generated auto incrementing id
     * @var int
     * @autoincrement
     * @primary
     */
    public $id;
    /**
     * @var int UserAccount->id
     */
    public $account_id;
    /**
     * If the pin is a repin, this will be the Pinterest generated pin id
     * @var int
     */
    public $parent_pin;
    /**
     * After the pin is pinned, it is given a pin id by Pinterest
     * @var int
     */
    public $pin_id;
    /**
     * The Pinterest generated board_id
     * @var int
     */
    public $board_name;
    /**
     * The domain from which this image was found. Can't be blank
     * @var string
     */
    public $domain;
    /**
     * The url of the image we pinned or are about to pin
     * @var string
     */
    public $image_url;
    public $link;
    public $description;
    public $added_at;
    public $sent_at;
    /**
     * @var string 1
     */
    public $status;
    public $order;


    protected $_user_account;

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Instance functions
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Save pins to be scheduled
     *
     * If the pin is set with a manually set_time, we then save the pin to publisher_posts
     * and grab the pin_uuid (i.e; the id) and save it to publisher_time_slots table
     *
     * If the pin has been loaded without manual set_time (i.e; automatic schedule) we
     * just add the pin to the publisher_posts table
     *
     * @param $day_preference
     * @param $time_preference
     * @param $timezone
     * @param $parent_pin
     *
     * @author Yesh
     *
     * @return bool
     */
    public function schedule($day_preference  = null,
                             $time_preference = null,
                             $timezone        = null,
                             $parent_pin      = null
    )
    {

        $current_time = time();

        /*
         * Checking to see if its a repin. If it is we set the parent_pin
         * from which the repin originates
         */
        if (!empty($parent_pin)) {
            $this->parent_pin = $parent_pin;
        }

        if (!empty($day_preference) &&
            !empty($time_preference)
        ) {

            $user_time_pref = new TimeSlot();

            /**
             * Save the created at time
             */
            $this->added_at = $current_time;

            /**
             * Saving the time and account details for the manually set
             * pins to the publisher_time_slots table
             *
             * We then add them to the publisher_time_slots collection
             *
             */
            $user_time_pref->account_id      = $this->account_id;
            $user_time_pref->day_preference  = $day_preference;
            $user_time_pref->time_preference = $time_preference;
            $user_time_pref->timezone        = $timezone;

            $user_time_pref->calculateSendTime();

            $user_time_pref->day_preference  = null;
            $user_time_pref->time_preference = null;

            /**
             * Save the scheduled pin to the publisher_posts table
             */

            $this->saveToDB();

            /**
             * Once the pin has been scheduled, since it has been manually set
             * we would like save its 'id' as 'pin_uuid' into the publisher_time_slots
             * table
             */

            $user_time_pref->pin_uuid = $this->id;

            $user_time_pref->saveToDB();

        } else {

            $this->added_at = $current_time;
            $this->order    = self::getNextOrderNum($this->getUserAccount());

            $this->saveToDB();
        }

        return true;

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Static functions
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Creates a post.
     *
     * @param User  $user
     * @param array $data
     *
     * @return bool
     */
    public static function create(User $user, array $data)
    {
        $day_preference  = null;
        $time_preference = null;

        if (array_get($data, 'schedule_type') == self::SCHEDULE_TYPE_MANUAL) {

            $day_preference = array_get($data, 'day_time');

            /*
             * Pass the time preference back as hh:mm A
             * @WTD add validation
             */
            if (array_get($data, 'hour') < 2) {
                $hour     = str_pad(array_get($data, 'hour'), 2, '0', STR_PAD_LEFT);
            } else {
                $hour = array_get($data, 'hour');
            }

            if (array_get($data, 'minutes') < 2) {
                $min      = str_pad(array_get($data, 'minutes'), 2, '0', STR_PAD_LEFT);
            } else {
                $min = array_get($data, 'minutes');
            }

            $meridiem = trim(strtoupper(array_get($data, 'am_pm')));

            $time_preference = "$hour:$min $meridiem";
        }

        $status = self::STATUS_AWAITING_APPROVAL;
        if ($user->hasFeature('pin_scheduling_admin')) {
            $status = self::STATUS_QUEUED;
        }

        $post              = new self;
        $post->account_id  = $user->getActiveUserAccount()->account_id;
        $post->domain      = array_get($data, 'domain');
        $post->board_name  = array_get($data, 'board');
        $post->image_url   = array_get($data, 'image_url');
        $post->link        = array_get($data, 'link');
        $post->description = array_get($data, 'description');
        $post->status      = $status;
        $post->schedule(
             $day_preference,
             $time_preference,
             $user->getTimezone(),
             array_get($data, 'parent_pin')
        );

        $post->saveAsNew();

        return $post;
    }

    /**
     * Updates a post and its time slot.
     *
     * @author Janell
     *
     * @param \User  $user
     * @param array $data
     *
     * @return bool
     */
    public function update(\User $user, array $data)
    {
        // Update this post's details first.
        $changed    = false;
        $properties = array(
            'link'        => array_get($data, 'link'),
            'domain'      => array_get($data, 'domain'),
            'board_name'  => array_get($data, 'board'),
            'description' => array_get($data, 'description'),
            'status'      => array_get($data, 'status'),
        );

        foreach ($properties as $property => $value) {
            if (!empty($value) && $this->$property != $value) {
                $this->$property = $value;
                $changed = true;
            }
        }

        if ($changed) {
            $this->insertUpdateDB();
        }

        // Check for an existing time slot for this post.
        $time_slot = TimeSlot::find(array(
            'pin_uuid' => $this->id
        ));
        if (is_array($time_slot)) {
            $time_slot = current($time_slot);
        }

        // If auto-scheduling this post, delete any existing time slot and return success.
        if (array_get($data, 'schedule_type') == self::SCHEDULE_TYPE_AUTO) {
            if (!empty($time_slot)) {
                TimeSlot::delete($time_slot->id);

                // Append to the end of the current queue.
                $this->order = self::getNextOrderNum($this->getUserAccount());
                $this->insertUpdateDB();
            }

            return true;
        }

        // Process new day and time preferences.
        $day_preference = array_get($data, 'day_time');

        /*
         * Pass the time preference back as hh:mm A
         * @WTD add validation
         */
        if (array_get($data, 'hour') < 2) {
            $hour     = str_pad(array_get($data, 'hour'), 2, '0', STR_PAD_LEFT);
        } else {
            $hour = array_get($data, 'hour');
        }

        if (array_get($data, 'minutes') < 2) {
            $min      = str_pad(array_get($data, 'minutes'), 2, '0', STR_PAD_LEFT);
        } else {
            $min = array_get($data, 'minutes');
        }

        $meridiem = trim(strtoupper(array_get($data, 'am_pm')));

        $time_preference = "$hour:$min $meridiem";

        // If this is a new manual post, create the time slot, calculate its send time, and return.
        if (empty($time_slot)) {
            $time_slot = new TimeSlot();
            $time_slot->account_id      = $this->account_id;
            $time_slot->day_preference  = $day_preference;
            $time_slot->time_preference = $time_preference;
            $time_slot->timezone        = $user->getTimezone();
            $time_slot->pin_uuid        = $this->id;

            $time_slot->calculateSendTime();

            $time_slot->day_preference  = null;
            $time_slot->time_preference = null;

            $time_slot->saveToDB();

            // Reorder posts to close the gap created by removing an auto post.
            self::getUpcomingAutomaticPosts($this->getUserAccount())->reorder();

            return true;
        }

        // Update the time slot if send time has changed.
        $old_send_time = $time_slot->send_at;

        $time_slot->day_preference  = $day_preference;
        $time_slot->time_preference = $time_preference;
        $time_slot->timezone        = $user->getTimezone();

        $time_slot->calculateSendTime();

        if ($time_slot->send_at != $old_send_time) {
            $time_slot->insertUpdateDB();
        }

        return true;
    }

    /**
     * Publishes this post immediately.
     *
     * @author Janell
     *
     * @return bool
     */
    public function publish()
    {
        try {
            $user_account = $this->getUserAccount();

            if ($user_account->hasOAuthToken() == false) {
                throw new Exception(
                    'This account does not have a valid OAuth token and cannot ' .
                    'post pins or repins.'
                );
            }

            // Check for an existing time slot for this post.
            $time_slot = TimeSlot::find(array(
                'pin_uuid' => $this->id
            ));

            if (is_array($time_slot)) {
                $time_slot = current($time_slot);
            }

            if ($time_slot instanceof TimeSlot) {
                TimeSlot::delete($time_slot->id);
            }

            $pinterest = new Pinterest();
            $pinterest->setAccessToken($user_account->access_token);

            if ($this->isRepin()) {
                $pin = $pinterest->postRepin(
                    $this->parent_pin,
                    $this->board_name,
                    $this->description
                );

                Log::debug('Repinned ' . $this->parent_pin . ' to Pinterest', $pin);
            } else {
                $pin = $pinterest->putPin(
                    $this->board_name,
                    $this->image_url,
                    $this->description,
                    $this->link
                );

                Log::debug('Pinned ' . $this->image_url . ' to Pinterest', $pin);
            }

            $this->pin_id  = $pin->pin_id;
            $this->status  = Post::STATUS_SENT;
            $this->sent_at = time();
        } catch (Exception $e) {
            $this->sent_at = time();
            $this->status  = Post::STATUS_FAILED;

            Log::error($e, $this);
        }

        $this->insertUpdateDB();

        Log::debug('Updated post after posting');

        if ($this->status == Post::STATUS_FAILED) {
            Log::debug('Post failed');

            return false;
        }

        $pin->insertUpdateDB();
        Log::debug('Saved new pin to DB');

        return true;
    }

    /**
     * Deletes a post with the given post id.
     *
     * @author  Will
     * @author  Yesh
     *
     * @param int $id
     * @param PDO $pdo_object
     *
     * @return bool
     */
    public static function delete($id, PDO $pdo_object = null)
    {
        $DBH = is_null($pdo_object) ? DatabaseInstance::DBO() : $pdo_object;

        $post = Post::find($id);
        if (!$post instanceof $post) {
            return false;
        }

        $user_account = $post->getUserAccount();
        $reorder      = true;

        // Remove the manually scheduled post from publisher_time_slots table.
        $STH = $DBH->prepare("
            DELETE FROM publisher_time_slots
            WHERE pin_uuid = :pin_uuid
        ");
        $STH->execute(array(':pin_uuid' => $id));

        // If a timeslot was removed, this was a manually scheduled post. There is no need to reorder.
        if ($STH->rowCount()) {
            $reorder = false;
        }

        // Remove the scheduled post from publisher_posts table.
        $STH = $DBH->prepare("
            DELETE FROM publisher_posts
            WHERE id = :id
        ");
        $STH->execute(array(':id' => $id));

        // Reorder posts to close the gap created by removing an auto post.
        if ($reorder) {
            self::getUpcomingAutomaticPosts($user_account)->reorder();
        }

        return true;
    }

    /**
     * Stores data for draft posts.
     *
     * @param array $data
     *
     * @return void
     */
    public static function storeDrafts(array $data)
    {
        $drafts = [];
        $images = array_get($data,'items',[]);

        foreach ($images as $image) {

            $image['site-url'] = array_get($image, 'site-url', 'http://www.tailwindapp.com');

            $drafts[$image['image-url']] = $image;

        }

        $drafts = array_merge($drafts, (array) Session::get(self::DRAFT_SESSION_KEY));

        Session::put(self::DRAFT_SESSION_KEY, $drafts);
    }

    /**
     * Deletes draft posts.
     *
     * @return void
     */
    public static function deleteDrafts()
    {
        Session::forget(self::DRAFT_SESSION_KEY);
    }

    /**
     * Deletes a draft from the session
     * @author  Will
     */
    public static function removeFromDrafts($image_url) {

        $items = Session::get(self::DRAFT_SESSION_KEY);
        unset($items[$image_url]);

        Session::set(self::DRAFT_SESSION_KEY,$items);
    }

    /**
     * Gets draft posts.
     *
     * @return Posts
     */
    public static function getDrafts()
    {
        $items  = Session::get(self::DRAFT_SESSION_KEY);

        $drafts = new Posts();
        foreach ($items as $item) {
            $draft              = new Post();
            $draft->parent_pin  = array_get($item, 'parent-pin-id');
            $draft->link        = array_get($item, 'site-url');
            $draft->image_url   = array_get($item, 'image-url');
            $draft->description = array_get($item, 'description');
            $drafts->add($draft);
        }

        return $drafts;
    }

    /**
     * Gets a count of draft posts.
     *
     * @return int
     */
    public static function getDraftsCount()
    {
        return count((array) Session::get(self::DRAFT_SESSION_KEY));
    }

    /**
     * Gets the posts for a user account.
     *
     * @author Will
     * @author Daniel
     *
     * @param UserAccount $user_account
     * @param bool        $published
     * @param PDO         $pdo_object
     *
     * @return Posts
     */
    public static function getPosts(UserAccount $user_account, $published = false, PDO $pdo_object = null)
    {
        $DBH = is_null($pdo_object) ? DatabaseInstance::DBO() : $pdo_object;

        $published_clause = ' AND sent_at IS NULL';
        if ($published) {
            $published_clause = ' AND sent_at IS NOT NULL';
        }

        $query = $DBH->prepare("
            SELECT * FROM publisher_posts
            WHERE account_id = :account_id
            $published_clause
            ORDER BY `order`
        ");

        $query->execute(array(
             ':account_id' => $user_account->account_id
        ));

        return Posts::createFromDBData($query->fetchAll());
    }

    /**
     * Gets upcoming automatic posts for a user account.
     *
     * @author Janell
     *
     * @param UserAccount $user_account
     * @param PDO         $pdo_object
     *
     * @return Posts
     */
    public static function getUpcomingAutomaticPosts(UserAccount $user_account, PDO $pdo_object = null)
    {
        $DBH = is_null($pdo_object) ? DatabaseInstance::DBO() : $pdo_object;

        $query = $DBH->prepare("
            SELECT * FROM publisher_posts
            WHERE account_id = :account_id
            AND sent_at IS NULL
            AND id NOT IN (
              SELECT pin_uuid FROM publisher_time_slots
              WHERE account_id = :account_id
              AND pin_uuid IS NOT NULL
            ) ORDER BY `order`
        ");

        $query->execute(array(
            ':account_id' => $user_account->account_id
        ));

        return Posts::createFromDBData($query->fetchAll());
    }

    /**
     * Gets the total number of upcoming automatic posts for a user account.
     *
     * @author Janell
     *
     * @param UserAccount $user_account
     * @param PDO         $pdo_object
     *
     * @return int
     */
    public static function getUpcomingAutomaticPostCount(UserAccount $user_account, PDO $pdo_object = null)
    {
        $DBH = is_null($pdo_object) ? DatabaseInstance::DBO() : $pdo_object;

        $query = $DBH->prepare("
            SELECT count(*) AS total FROM publisher_posts
            WHERE account_id = :account_id
            AND sent_at IS NULL
            AND id NOT IN (
              SELECT pin_uuid FROM publisher_time_slots
              WHERE account_id = :account_id
              AND pin_uuid IS NOT NULL
            )
        ");

        $query->execute(array(
            ':account_id' => $user_account->account_id,
        ));

        $result = $query->fetch();

        if ($result === false) {
            return 0;
        }

        return $result->total;
    }

    /**
     * Gets the next available sort order index for a user account's posts.
     *
     * @author Janell
     *
     * @param UserAccount $user_account
     * @param PDO         $pdo_object
     *
     * @return int
     */
    public static function getNextOrderNum(UserAccount $user_account, PDO $pdo_object = null)
    {
        $DBH = is_null($pdo_object) ? DatabaseInstance::DBO() : $pdo_object;

        $query = $DBH->prepare("
            SELECT `order` FROM publisher_posts
            WHERE account_id = :account_id
            AND sent_at IS NULL
            ORDER BY `order` DESC
            LIMIT 1
        ");

        $query->execute(array(
            ':account_id' => $user_account->account_id,
        ));

        $result = $query->fetch();

        if ($result === false) {
            return 1;
        }

        return $result->order + 1;
    }

    /**
     * Returns a formatted timestamp for the time this post was published (sent_at).
     *
     * @author Janell
     *
     * @param string $user_timezone
     *
     * @return string
     */
    public function getPrettyPublishedTime($user_timezone = 'America\Chicago')
    {
        return \Carbon\Carbon::createFromTimestamp($this->sent_at, $user_timezone)
            ->format('l, F j, Y @ g:i A (T)');
    }

    /**
     * Returns a formatted timestamp for the time this post is scheduled to send.
     *
     * @author Janell
     *
     * @param string $user_timezone
     *
     * @return string
     */
    public function getPrettyScheduledTime($user_timezone = 'America\Chicago')
    {
        return \Carbon\Carbon::createFromTimestamp($this->time_slot_timestamp, $user_timezone)
            ->format('l, F j, Y @ g:i A (T)');
    }

    /**
     * Gets the UserAccount associated with this post
     *
     * @author  Will
     *
     * @param bool $force_update
     *
     * @return UserAccount
     */
    public function getUserAccount($force_update = false)
    {
        if ($this->_user_account AND !$force_update) {
            return $this->_user_account;
        }

        return $this->_user_account = UserAccount::find($this->account_id);
    }

    /**
     * @author  Will
     */
    public function isRepin()
    {
        if (is_null($this->parent_pin)) {
            return false;
        }
        return true;
    }

    /**
     * Returns the board name for this post, since board_name is actually the board id.
     *
     * @author Janell
     *
     * @return string
     */
    public function getBoardName()
    {
        if (is_numeric($this->board_name)) {
            $board = \Board::find($this->board_name);
            if ($board) {
                return $board->name;
            }
        }

        return $this->board_name;
    }
}

class PostException extends DBModelException {}
