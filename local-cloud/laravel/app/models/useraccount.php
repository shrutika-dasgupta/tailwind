<?php

use
    Pinleague\Pinterest,
    Publisher\Post;

/**
 * Class UserAccount
 */
class UserAccount extends PDODatabaseModel
{
    /**
     * @const track_type is a string that helps us determine how often we should
     *        pull data and run calculations, among other things
     *
     *        'Orphan' denotes accounts that have been soft deleted. They are
     *        not hard deleted for historical purposes.
     */
    const
        TRACK_TYPE_FREE       = 'free',
        TRACK_TYPE_USER       = 'user',
        TRACK_TYPE_COMPETITOR = 'competitor',
        TRACK_TYPE_ORPHAN     = 'orphan';


    public
        /**
         * A unique tailwind id for each Pinterest Profile connected to ana
         * account
         *
         * @var $account_id int autoincrement
         */
        $account_id,
        /**
         * A user inputted name for the account.
         *
         * @var $account_name string
         */
        $account_name,
        /**
         * The tailwind id for the organization that owns this user_account
         *
         * @var $org_id int
         */
        $org_id,
        /**
         * The Pinterest username
         *
         * @var $username string
         */
        $username,
        /**
         * The Pinterest user_id
         *
         * @var $user_id    int
         */
        $user_id,
        $industry_id,
        $account_type,
        $track_type,
        $competitor_of,
        $chargify_id,
        $chargify_id_alt,
        $domain_limit,
        $keyword_limit,
        /**
         * Token from Pinterest used to authenticate and make calls
         *
         * @var $access_token string
         */
        $access_token,
        /**
         * Epoch time when the access token becomes a pumpkin
         *
         * @var $expires_at int
         */
        $expires_at,
        /**
         * Pinterest generated token type OR if we sniffed it
         *
         * @var $access_token_type string
         */
        $token_type,
        /**
         * If The user granted access or not
         *
         * @var $access_token_authorized string
         */
        $token_authorized,
        /**
         * Pinterest generated CSV string of the methods authorized
         *
         * @var $access_token_scope string
         */
        $token_scope,
        $created_at,
        $last_update;

    public
        $table = 'user_accounts',
        $columns =
        array(
            'account_id',
            'account_name',
            'org_id',
            'username',
            'user_id',
            'industry_id',
            'account_type',
            'track_type',
            'competitor_of',
            'chargify_id',
            'chargify_id_alt',
            'domain_limit',
            'keyword_limit',
            'access_token',
            'expires_at',
            'token_type',
            'token_authorized',
            'token_scope',
            'created_at',
            'last_update',
        ),
        $primary_keys = array('account_id');

    protected $_boards;
    protected $_domains = false;
    protected $_profile = false;
    protected $_industry = false;
    protected $_organization;

    /**
     * Creates a user_account and seeds the engines
     * with status profiles
     *
     * @author  Will
     *
     * @param \Profile $profile /Profile
     *
     * @param          $org_id
     * @param string   $track_type
     * @param string   $account_type
     * @param bool     $account_name
     * @param int      $industry_id
     *
     * @return \UserAccount
     */
    public static function create(
        Profile $profile,
        $org_id,
        $track_type = 'free',
        $account_type = 'brand',
        $account_name = false,
        $industry_id = 0
    )
    {
        $user_account = new UserAccount();

        if ($account_name) {
            $user_account->account_name = $account_name;
        } else {
            $user_account->account_name = $profile->getName();
        }

        if($track_type == User::TRACK_TYPE_INCOMPLETE) {
            $track_type = User::TRACK_TYPE_FREE;
        }

        $user_account->org_id          = $org_id;
        $user_account->username        = $profile->username;
        $user_account->user_id         = $profile->user_id;
        $user_account->industry_id     = $industry_id;
        $user_account->account_type    = $account_type;
        $user_account->track_type      = $track_type;
        $user_account->competitor_of   = '';
        $user_account->chargify_id     = 0;
        $user_account->chargify_id_alt = 0;
        $user_account->created_at      = time();
        $user_account->last_update     = $profile->last_pulled;

        $user_account->saveAsNew();

        /*
         * Include in status profiles so it is tracked
         */
        StatusProfile::create($profile, $track_type);

        /*
         * Include in status profiles s it is tracked
         */
        StatusProfilePin::create($profile->user_id, $track_type);

        /*
         * Include in status profiles s it is tracked
         */
        StatusProfileFollower::create($profile->user_id, $track_type);



        /*
         * Insert boards into status profiles
         */
        $Pinterest  = new \Pinleague\Pinterest();
        $boardsData = $Pinterest->getProfileBoards($profile->username);

        $boards = Boards::createFromApiDataSet($boardsData,$profile->user_id);

        if ($boards->count() > 0) {

            $boards->setPropertyOfAllModels('track_type', $track_type);

            foreach ($boards as $board) {
                StatusBoard::create($board, $track_type);
            }

            $boards->setPropertyOfAllModels('track_type', $track_type);
            $boards->insertUpdateDB();
        }

        return $user_account;
    }



/**
     * Gets an account's users.
     *
     * @return array
     */
    public function users()
    {
        return User::find(array(
            'org_id' => $this->org_id,
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author  Will
     */
    public function addDomain($url)
    {
        $domain             = new UserAccountsDomain($url);
        $domain->account_id = $this->account_id;
        $domain->saveToDB('insert ignore into');

        $STH = $this->DBH->prepare("
              insert into status_domains
               (
                   domain,
                   track_type,
                   timestamp
               ) VALUES (
                :domain,
                  :track_type,
                  :timestamp
               )
             ON DUPLICATE KEY UPDATE
             track_type = VALUES(track_type),
			 timestamp = VALUES(timestamp)
            ");

        $STH->execute(
            array(
                 ':domain'     => $domain->domain,
                 ':track_type' => $this->track_type,
                 ':timestamp'  => time()
            )
        );

        return $domain;
    }

    /**
     * @author  Will
     * @return Boards
     */
    public function boards()
    {
        $STH = $this->DBH->prepare('
                select * from data_boards where user_id = :user_id
            ');

        $STH->execute(array(':user_id' => $this->user_id));

        $boards = new Boards();

        foreach ($STH->fetchAll() as $row) {
            $board = Board::createFromDBData($row);
            $boards->add($board);
        }

        return $boards;
    }

    /**
     * Change the track type and thus potentially changing the
     * status profiles
     * status domains
     * status boards
     * stauts profile followers
     * status profile pins
     * tables track_types
     *
     * @author  Will
     */
    public function changeTrackType($track_type)
    {
        $this->track_type = $track_type;

        switch ($track_type) {
            /*
             * If we are setting the track type to user, we simply just
             * have to set all the other tables to user
             */
            case 'user':
                $this->profile()->changeTrackType($track_type);
                $this->domains()->changeStatusDomainsTrackType($track_type);
                $this->profile()->changeStatusBoardsTrackType($track_type);

                break;

            /*
             * If we are setting it to competitor, we need to check if there
             * is one listed as a user | otherwise this is highest so we set it
             * as competitor
             */
            case 'competitor':

                $STH = $this->DBH->prepare("
                       select * from user_accounts
                       where user_id = :user_id
                       AND account_id != :account_id
                       AND track_type ='user'
                    ");

                /*
                 * Domains are trickier
                 */
                $domains = $this->domains()->domainsString();
                $DSTH    = $this->DBH->prepare("
                                select * from user_accounts_domains
                                join user_accounts
                                ON user_accounts.account_id = user_accounts_domains.account_id
                                where domain in ($domains)
                                and domain != ''
                                and user_accounts.track_type = 'user'
                        ");

                break;

            /*
             * If we are setting it to free, we should check if it is either tracked
             * as a user OR a competitor
             * at some point we should add track type as orphan
             * but not right now because no
             */
            case 'free':

                $STH = $this->DBH->prepare("
                       select * from user_accounts
                       where user_id = :user_id
                       AND account_id != :account_id
                       AND (track_type ='user' OR track_type = 'competitor')
                    ");

                /*
                 * Domains are trickier
                 */
                $domains = $this->domains()->domainsString();
                $DSTH    = $this->DBH->prepare("
                                select * from user_accounts_domains
                                join user_accounts
                                ON user_accounts.account_id = user_accounts_domains.account_id
                                where domain in ($domains)
                                and domain != ''
                                AND (
                                user_accounts.track_type ='user'
                                OR user_accounts.track_type = 'competitor'
                                )
                        ");

                break;


            case 'orphan':

                $STH = $this->DBH->prepare("
                       select * from user_accounts
                       where user_id = :user_id
                       AND account_id != :account_id
                       AND (track_type ='user' OR track_type = 'competitor' or track_type = 'free')
                    ");

                /*
                 * Domains are trickier
                 */
                $domains = $this->domains()->domainsString();

                if(empty($domains)){
                    /*
                     * We want to make the where clause false if this account doesn't have any
                     * actual domains.  This will make the sql statement return 0 rows, which is
                     * what we need later in this method.
                     */
                    $where_clause = "where 1 = 2";
                } else {
                    $where_clause = "where domain in ($domains)";
                }

                $DSTH = $this->DBH->prepare("
                                select * from user_accounts_domains
                                join user_accounts
                                ON user_accounts.account_id = user_accounts_domains.account_id
                                $where_clause
                                and domain != ''
                                AND (
                                user_accounts.track_type ='user'
                                OR user_accounts.track_type = 'competitor'
                                OR user_accounts.track_type = 'free'
                                )
                        ");

                break;
        }

        $ztrack_type = $track_type;

        if ($track_type == 'competitor' || $track_type == 'free' || $track_type == 'orphan') {

            $STH->execute(
                array(
                     ':user_id'    => $this->user_id,
                     ':account_id' => $this->account_id
                )
            );

            /*
             * If we got 0 results matching our query, we can update the track
             * type. Otherwise, we need to keep it as is
             */
            if ($STH->rowCount() == 0) {

                $this->profile()->changeTrackType($track_type);
                $this->profile()->changeStatusBoardsTrackType($track_type);

            } elseif ($track_type != 'competitor') {
                /*
                 * We need to check if the available accounts are for competitors
                 */
                foreach ($STH->fetchAll() as $account) {
                    if ($account->track_type == 'competitor') {
                        $this->profile()->changeTrackType('competitor');
                        $this->profile()->changeStatusBoardsTrackType('competitor');
                    }
                }

            }

            $this->insertUpdateDB();

            /*
             * Checking the domains here
             */
            if ($this->domains()->count() != 0) {

                $DSTH->execute();
                /*
                 * We found the list of domains that we need to keep the same so if there
                 * are none we are just going to update them to what we want
                 */
                if ($DSTH->rowCount() == 0) {

                    $this->domains()->changeStatusDomainsTrackType($track_type);

                    /*
                     * Else the ones we found we don't want to change
                     * but the rest we do
                     */
                } else {

                    foreach ($this->domains() as $key => $domain) {

                        foreach ($DSTH->fetchAll() as $black_list_domains) {
                            if ($black_list_domains->domain == $domain->domain) {
                                $this->domains()->removeModel($key);
                            }
                        }

                    }
                    $this->domains()->changeStatusDomainsTrackType($track_type);
                }

            }

        }

        $this->insertUpdateDB();
    }

    /**
     * @author  Will
     *
     * @param $url
     *
     * @return bool
     */
    public function doesntHaveDomain($url)
    {
        return !$this->hasDomain($url);
    }

    /**
     * @author  Will
     * @return UserAccountsDomains
     */
    public function domains()
    {
        if ($this->_domains) {
            return $this->_domains;
        }
        $STH = $this->DBH->prepare("
                select * from user_accounts_domains where account_id = :account_id
                and domain != ''
            ");
        $STH->execute(array(':account_id' => $this->account_id));

        $domains = new UserAccountsDomains();

        foreach ($STH->fetchAll() as $row) {
            $domain = UserAccountsDomain::createFromDBData($row);
            $domains->add($domain);
        }

        return $this->_domains = $domains;
    }

    /**
     * Fetches the industry, does not use cache
     *
     * @author  Will
     * @throws UserIndustryNotFoundException
     * @return \UserIndustry /UserIndustry
     */
    public function getIndustry()
    {
        $STH = $this->DBH->prepare('
            select * from user_industries
            where industry_id = :industry_id
            limit 1
        ');

        $STH->execute(
            array(
                 ':industry_id' => $this->industry_id
            )
        );

        if ($STH->rowCount() == 0) {
            Log::warning('Could not find industry:' . $this->industry_id);

            $industry              = new UserIndustry();
            $industry->industry    = 'None';
            $industry->industry_id = 0;

            return $industry;
        }

        $industry = UserIndustry::createFromDBData($STH->fetch());

        return $industry;
    }

    /**
     * Will get the industry if it is cached, or just load it up
     *
     * @author  Will
     */
    public function industry($force_update = false) {
        if ($this->_industry AND !$force_update) {
            return $this->_industry;
        }
        return $this->_industry = $this->getIndustry();
    }

    /**
     * @author  Will
     *
     * @param $url
     *
     * @return bool
     */
    public function hasDomain($url)
    {
        try {
            $domain = new UserAccountsDomain($url);
        }
        catch (UserAccountsDomainException $e) {
            return false;
        }

        $STH = $this->DBH->prepare(
            '  select * from user_accounts_domains
               where account_id =:account_id
               and domain = :domain'
        );

        $STH->execute(
            array(
                 ':account_id' => $this->account_id,
                 ':domain'     => $domain->domain
            )
        );

        if ($STH->rowCount() == 0) {
            return false;
        }

        return true;
    }

    /**
     * @author Will
     * @return UserAccountsDomain
     */
    public function mainDomain()
    {
        if ($this->domains()->count() == 0) {
            return new UserAccountsDomain('');
        } else {
            return $this->domains()->getModel(0);
        }
    }

    /**
     * @author  Will
     * @return bool|\Profile /Profile
     */
    public function profile()
    {
        if ($this->_profile) {
            return $this->_profile;
        }

        try {
            $profile = Profile::findInDB($this->user_id);

            return $this->_profile = $profile;
        }
        catch (ProfileNotFoundException $e) {
            $profile = Profile::createViaApi($this->username);
            $profile->insertUpdateDB();

            $this->user_id = $profile->user_id;
            $this->insertUpdateDB();

            return $this->_profile = $profile;
        }
    }

    /**
     * Replaces the "main" domain associated with the account
     *
     * @author  Will
     */
    public function replaceDomain($url)
    {
        try {
            $domain = new UserAccountsDomain($url);
            $domain = $domain->domain;
        }
        catch (UserAccountsDomainException $e) {
            $domain = '';
        }


        $STH = $this->DBH->prepare('
                update user_accounts_domains set
                domain = :domain
                where account_id = :account_id
                AND domain = :old_domain'
        );

        try {
            $old_domain = $this->mainDomain()->domain;

            return $STH->execute(
                array(
                     ':account_id' => $this->account_id,
                     ':domain'     => $domain,
                     ':old_domain' => $old_domain
                )
            );

        }
        catch (UserAccountsDomainException $e) {
            return false;
        }
    }

    /**
     * Gets this account's organization.
     *
     * @author Daniel
     *
     * @return Organization
     */
    public function organization()
    {
        if (!$this->_organization) {
            $this->_organization = Organization::find($this->org_id);
        }

        return $this->_organization;
    }

    /**
     * Gets the plan associated with this account.
     *
     * @author Daniel
     * 
     * @return Plan
     */
    public function plan()
    {
        return $this->organization()->plan();
    }

    /**
     * Gets the account's keyword limit.
     *
     * @author Daniel
     * @author Will
     *
     * @return int
     */
    public function keywordLimit()
    {
        /*
         * We use is_null instead of empty() because we may
         * at some point want to allow users 0 keywords and empty
         */
        if (is_null($this->keyword_limit)) {
            $this->keyword_limit = $this->organization()->plan()->keywordLimit();
            $this->insertUpdateDB();
        }

        return $this->keyword_limit;
    }

    /**
     * Gets the account's domain limit.
     *
     * @author  Daniel
     * @author  Will
     *
     * @return int
     */
    public function domainLimit()
    {
        /*
         * We use is_null instead of empty() because we may
         * at some point want to allow users 0 keywords and empty
         */
        if (is_null($this->domain_limit)) {
            $this->domain_limit = $this->organization()->plan()->domainLimit();
            $this->insertUpdateDB();
        }

        return $this->domain_limit;
    }

    /**
     * @author  Will
     *
     * @param      $timestamp
     *
     * @param bool $include_boards
     *
     * @return \Pins
     */
    public function getPinsPinnedAfter($timestamp, $include_boards = false)
    {
        $STH = $this->DBH->prepare("
                    SELECT * FROM data_pins_new
                    JOIN data_boards
            	    ON data_pins_new.board_id = data_boards.board_id
                    WHERE data_pins_new.user_id = :user_id
                    AND data_pins_new.created_at >= :created_at
                ");

        $STH->execute(
            array(
                 ':user_id'    => $this->user_id,
                 ':created_at' => $timestamp
            )
        );

        $pins = new Pins();
        foreach ($STH->fetchAll() as $pinData) {
            $pin = Pin::createFromDBData($pinData);
            $pin->preLoad('board', $pinData);
            $pins->add($pin);
        }

        return $pins;


    }

    /**
     * @author  Will
     *
     * @return $this
     */
    public function saveAsNew() {
        $return = parent::saveAsNew();
        $this->account_id = $this->DBH->lastInsertId();
        return $return;
    }

    /**
     * Sets the limits for KWs and Domains
     * based on a give plan
     *
     * @author  Will
     *
     * @param Plan $plan
     */
    public function setLimits(Plan $plan)
    {
        $this->keyword_limit = $plan->keywordLimit();
        $this->domain_limit = $plan->domainLimit();
    }


    /**
     * Gets the posts for the user account.
     *
     * @author Daniel
     *
     * @param bool $published
     *
     * @return Publisher\Posts
     */
    public function getPosts($published = false)
    {
        return Publisher\Post::getPosts($this, $published, $this->DBH);
    }

    /**
     * Gets the number of posts a user account currently has scheduled.
     *
     * @author Alex
     *
     * @param bool $published
     *
     * @return integer
     */
    public function getScheduledPostsCount($published = false)
    {
        $published_clause = ' AND sent_at IS NULL';
        if ($published) {
            $published_clause = ' AND sent_at IS NOT NULL';
        }

        $STH = $this->DBH->prepare("
            select count(*) as count from publisher_posts
            where account_id = :account_id
            $published_clause
            and (status != :awaiting_approval_status or status is null)
        ");

        $STH->execute(array(
            ':account_id'               => $this->account_id,
            ':awaiting_approval_status' => Post::STATUS_AWAITING_APPROVAL,
        ));

        $count = $STH->fetch();

        return $count->count;
    }

    /**
     * Gets the number of pending posts a user account currently has waiting to be approved.
     *
     * @author Janell
     *
     * @return integer
     */
    public function getPendingPostsCount()
    {
        $STH = $this->DBH->prepare("
            select count(*) as count from publisher_posts
            where account_id = :account_id
            and sent_at is null
            and status = :awaiting_approval_status
        ");

        $STH->execute(array(
            ':account_id'               => $this->account_id,
            ':awaiting_approval_status' => Post::STATUS_AWAITING_APPROVAL,
        ));

        $count = $STH->fetch();

        return $count->count;
    }

    /**
     * Gets the next available timeslot
     *
     * @author  Will
     *
     * @return Publisher\TimeSlot
     */
    public function getNextAvailableTimeSlot()
    {
        $time_slots = $this->getTimeSlots('auto');

        $time_slots->sortByUpcoming();

        $upcoming_posts = $this->getUpcomingAutomaticPosts();

        if ($upcoming_posts->isEmpty()) {
            return $time_slots->first();
        }

        /*
         * We want the remainder so we know what index to get
         */
        $index = $upcoming_posts->count() % $time_slots->count();
        /*
         * nth uses the "speaking" index
         * starting at 1 not 0. So we need to add 1
         */
        return current($time_slots->nth($index+1));
    }

    /**
     * Gets time slots for the user account.
     *
     * @author  Will
     *
     * @param string $type (all|auto|manual)
     *
     * @return Publisher\TimeSlots
     */
    public function getTimeSlots($type = 'all') {
        return Publisher\TimeSlot::getTimeSlots($this, $this->DBH, $type);
    }

    /**
     * Gets upcoming automatic posts for the user account.
     *
     * @author Janell
     *
     * @return Publisher\Posts
     */
    public function getUpcomingAutomaticPosts()
    {
        return Publisher\Post::getUpcomingAutomaticPosts($this, $this->DBH);
    }

    /**
     * @return bool
     */
    public function hasOAuthToken() {
        return !is_null($this->access_token);
    }

    /**
     * @author  Will
     */
    public function hasGoogleAnalytics($responseType = 'bool')
    {
        $STH = $this->DBH->query("
              SELECT account_id
              FROM status_traffic
              WHERE account_id = '$this->account_id'
              ORDER BY timestamp desc limit 1 "
        );

        $rows = $STH->rowCount();

        switch ($responseType) {
            default:
            case 'bool':

                if ($rows > 0) {
                    return true;
                }

                return false;

                break;

            case 'string':

                if ($rows > 0) {
                    return 'YES';
                }

                return 'NO';

                break;
        }
    }

    /**
     * @author  Will
     */
    public function tasks() {
        $profile_tasks = $this->profile()->tasks();

        $user_account_tasks = new Tasks();

        foreach (Tasks::$user_account_task_names as $name) {
            $task = new Task($name);
            $task->setType(Task::TYPE_USER_ACCOUNT);
            $task->setIdentifier($this);

            switch ($name) {
                default:
                    Log::warning('A task was added that does not get evaluated',$task);
                    break;

                case   'added_a_domain':

                    if ($this->domains()->count() > 0) {
                        $task->setComplete();
                    }

                    break;

                case 'synced_google_analytics':

                    if ($this->hasGoogleAnalytics()) {
                        $task->setComplete();
                    }

                    break;
                case 'selected_an_industry':
                    if(!empty($this->industry_id)) {
                        $task->setComplete();
                    }

                    break;
                case 'selected_account_type':

                    if(!empty($this->account_type)) {
                        $task->setComplete();
                    }
                    break;
            }
            $user_account_tasks->add($task);
        }

        $user_account_tasks->merge($profile_tasks);

         return $user_account_tasks;
    }

    /**
     * @author  Alex
     *
     * @return  int traffic_id
     */
    public function trafficId()
    {
        $STH = $this->DBH->prepare("
              SELECT traffic_id FROM status_traffic
              where account_id = :account_id
            ");

        $STH->execute(
            array(
                 ':account_id' => $this->account_id
            )
        );

        $traffic = $STH->fetch();

        if (!empty($traffic)) {
            return $traffic->traffic_id;
        } else {
            return false;
        }
    }

    /**
     * Returns whether user account has a traffic_id associated with it
     *
     * @author  Alex
     *
     * @return  bool
     */
    public function hasAnalytics() {

        $STH = $this->DBH->prepare("
              SELECT account_id FROM status_traffic
              WHERE account_id = :account_id
              ORDER BY timestamp DESC
              LIMIT 1
            ");

        $STH->execute(
            array(
                 ':account_id' => $this->account_id
            )
        );

        $traffic = $STH->fetch();

        if (!empty($traffic)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return whether or not this account's google analytics data is ready
     *
     * @author  Alex
     *
     * @return  bool
     *
     */
    public function analyticsReady() {

        $STH = $this->DBH->prepare("
              SELECT last_pulled FROM status_traffic
              WHERE account_id = :account_id
              ORDER BY timestamp DESC limit 1
            ");

        $STH->execute(
            array(
                 ':account_id' => $this->account_id
            )
        );

        $traffic = $STH->fetch();

        if ($traffic->last_pulled != 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the Google Analytics token for an account's traffic_id
     *
     * @author  Alex
     *
     * @return  string $token
     */
    function analyticsToken() {

        $STH = $this->DBH->prepare("
              SELECT token FROM status_traffic
              WHERE account_id = :account_id
              ORDER BY timestamp DESC LIMIT 1
            ");

        $STH->execute(
            array(
                 ':account_id' => $this->account_id
            )
        );

        $traffic = $STH->fetch();

        return $traffic->token;
    }

    /**
     * Returns the Google Analytics Profile for an account's traffic_id
     *
     * @author  Alex
     *
     * @return  string $profile
     */
    function getAnalyticsProfile() {

        $STH = $this->DBH->prepare("
              SELECT profile FROM status_traffic
              WHERE account_id = :account_id
              ORDER BY timestamp DESC LIMIT 1
            ");

        $STH->execute(
            array(
                 ':account_id' => $this->account_id
            )
        );

        $traffic = $STH->fetch();

        return $traffic->profile;
    }

    /**
     * Returns the Google Analytics Profile for an account's traffic_id
     *
     * @author  Alex
     *
     * @return  bool $status_traffic->eCommerceTracking
     */
    function hasECommerceTracking() {

        $STH = $this->DBH->prepare("
              SELECT eCommerceTracking FROM status_traffic
              WHERE account_id = :account_id
              AND profile != ''
              ORDER BY timestamp DESC LIMIT 1
            ");

        $STH->execute(
            array(
                 ':account_id' => $this->account_id
            )
        );

        $status_traffic = $STH->fetch();

        if ($status_traffic->eCommerceTracking == 1){
            return true;
        } else {
            return false;
        }

    }


    /**
     * @internal param bool $redirect
     */
    public function getPinterestOAuthLink($redirect = false) {

        if(!$redirect) {
            $redirect = '/settings/accounts';
        }

        $state = self::encryptOAuthState($this->account_id,$redirect,time(true));

        $pinterest = Pinterest::getInstance();

        return $pinterest->getOauthUrl($state);
    }

    /**
     * @param        $account_id
     * @param        $redirect
     * @param        $time
     * @param string $secret
     *
     * @return string
     */
    public static function encryptOAuthState($account_id, $redirect, $time, $secret = 'barryisabeaver')
    {
        return Crypt::encrypt(json_encode([
                                          'account_id' => $account_id,
                                          'redirect'   => $redirect,
                                          'timestamp'  => $time,
                                          'secret'     => $secret
                                          ]));
    }

    /**
     * @param $state
     *
     * @return array
     */
    public static function decryptOAuthState($state) {
        return json_decode(Crypt::decrypt($state));
    }




}

class UserAccountException extends DBModelException {}
class UserAccountNotFoundException extends UserAccountException {}
