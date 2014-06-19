<?php
    /**
     * This script is going to calculate the user engagement for all
     * the clients
     *
     * @author  Yesh
     *
     */

    $number_of_accounts  = 25;
    $number_of_repinners = 105;
    $calcs_engagers      = 10;

    use Pinleague\CLI;

    chdir(__DIR__);
    include '../../bootstrap/bootstrap.php';
    
    Log::setLog(__FILE__);

    try {

        CLI::h1('Starting Program');
        $engine = new Engine(__FILE__);

        if ($engine->running()) {

            throw new EngineException('Engine is running');

        } else {
            $engine->start();
            CLI::write(Log::info('Engine started'));

            $DBH = DatabaseInstance::DBO();
            CLI::write(Log::debug('Connected to Database'));

            $limit        = $number_of_accounts;
            $time         = flat_date('day');
            $current_time = time();

            CLI::write(Log::debug('Finding accounts which have not been updated'));

            $tracked_accounts = $DBH->query("
                                    SELECT user_id
                                    FROM status_profiles
                                    WHERE track_type='user' and last_calced_engagement < $time
                                    LIMIT $limit")
                ->fetchAll();

            if (count($tracked_accounts) == 0) {
                $tracked_accounts = $DBH->query("
                                    SELECT user_id
                                    FROM status_profiles
                                    WHERE track_type='competitor' and last_calced_engagement < $time
                                    LIMIT $limit")
                    ->fetchAll();
            }

            if (count($tracked_accounts) == 0) {
                $tracked_accounts = $DBH->query("
                                    SELECT user_id
                                    FROM status_profiles
                                    WHERE track_type='pinmail' and last_calced_engagement < $time
                                    LIMIT $limit")
                                    ->fetchAll();
            }


            if (count($tracked_accounts) == 0) {
                CLI::write(Log::notice('No more accounts left to analyze'));
                $engine->complete();
                CLI::stop();
            } else {
                $periods = array(0, 30, 14, 7);

                foreach ($tracked_accounts as $acc) {

                    $STH = $DBH->prepare("UPDATE status_profiles
                                  SET last_calced_engagement = $current_time
                                  WHERE user_id = :user_id");
                    $STH->execute(array(":user_id" => $acc->user_id));

                    foreach ($periods as $period) {

                        $STH = $DBH->prepare("
                              SELECT q1.user_id,
                              q1.repinner_user_id,
                              q1.count as overall_engagement,
                              p.username,
                              p.first_name,
                              p.last_name,
                              p.follower_count,
                              p.following_count,
                              p.image,
                              p.website_url,
                              p.facebook_url,
                              p.twitter_url,
                              p.location,
                              p.board_count,
                              p.pin_count,
                              p.like_count,
                              unix_timestamp(now()) timestamp
                              FROM
                                (select b.user_id as user_id, a.repinner_user_id as repinner_user_id,
                                    count(a.repinner_user_id) as count
                                    from data_pins_repins a use index (pin_id_timestamp_repinner_user_id_idx)
                                    left join data_pins_new b
                                    use index (username)
                                    on a.pin_id = b.pin_id
                                    where b.user_id = :user_id
                                    ".($period==0 ? "" : "and a.timestamp > ".strtotime("-$period Days",$time)   )."
                                    GROUP BY a.repinner_user_id
                                    ORDER BY count(a.repinner_user_id) desc
                                    LIMIT $number_of_repinners)
                                as q1
                              left join data_profiles_new p
                              on q1.repinner_user_id = p.user_id
                              where p.user_id is not null");


                        $STH->execute(array(":user_id" => $acc->user_id));
                        $overall_repinners = $STH->fetchAll();

                        /*
                         * Delete old records after we've queried for the new ones right before
                         * we insert them
                         */
                        $STH = $DBH->prepare("DELETE FROM cache_engagement_influencers
                                  WHERE user_id = :user_id and period = :period");
                        $STH->execute(array(":user_id" => $acc->user_id, ":period" => $period));


                        foreach ($overall_repinners as $overall) {
                            $STH = $DBH->prepare("INSERT INTO cache_engagement_influencers (user_id,
                                                                    repinner_user_id,
                                                                    date,
                                                                    period,
                                                                    overall_engagement,
                                                                    username,
                                                                    first_name,
                                                                    last_name,
                                                                    follower_count,
                                                                    following_count,
                                                                    image,
                                                                    website_url,
                                                                    facebook_url,
                                                                    twitter_url,
                                                                    location,
                                                                    board_count,
                                                                    pin_count,
                                                                    like_count,
                                                                    timestamp)
                                          VALUES (:user_id,
                                                  :repinner_user_id,
                                                  :date,
                                                  :period,
                                                  :overall_engagement,
                                                  :username,
                                                  :first_name,
                                                  :last_name,
                                                  :follower_count,
                                                  :following_count,
                                                  :image,
                                                  :website_url,
                                                  :facebook_url,
                                                  :twitter_url,
                                                  :location,
                                                  :board_count,
                                                  :pin_count,
                                                  :like_count,
                                                  :timestamp)


                                          ON DUPLICATE KEY UPDATE
                                          overall_engagement = VALUES(overall_engagement),
                                          follower_count = VALUES(follower_count),
                                          following_count = VALUES(following_count),
                                          pin_count = VALUES(pin_count),
                                          timestamp = VALUES(timestamp)");


                            $STH->execute(array(':user_id'            => $overall->user_id,
                                                ':repinner_user_id'   => $overall->repinner_user_id,
                                                ':date'               => $time,
                                                ':period'             => $period,
                                                ':overall_engagement' => $overall->overall_engagement,
                                                ':username'           => $overall->username,
                                                ':first_name'         => $overall->first_name,
                                                'last_name'           => $overall->last_name,
                                                'follower_count'      => $overall->follower_count,
                                                'following_count'     => $overall->following_count,
                                                'image'               => $overall->image,
                                                ':website_url'        => $overall->website_url,
                                                ':facebook_url'       => $overall->facebook_url,
                                                ':twitter_url'        => $overall->twitter_url,
                                                ':location'           => $overall->location,
                                                ':board_count'        => $overall->board_count,
                                                ':pin_count'          => $overall->pin_count,
                                                ':like_count'         => $overall->like_count,
                                                ':timestamp'          => $overall->timestamp
                                          ));
                        }
                    }
                }

                foreach ($tracked_accounts as $acc) {
                    $STH = $DBH->prepare("SELECT *
                                  FROM cache_engagement_influencers
                                  WHERE user_id = :user_id
                                  LIMIT $calcs_engagers");
                    $STH->execute(array(":user_id" => $acc->user_id));
                    $calcs_table = $STH->fetchAll();

                    foreach ($calcs_table as $calcs) {

                        $STH = $DBH->prepare("INSERT INTO calcs_engager_history (user_id,
                                                                    repinner_user_id,
                                                                    date,
                                                                    period,
                                                                    overall_engagement,
                                                                    username,
                                                                    first_name,
                                                                    last_name,
                                                                    follower_count,
                                                                    following_count,
                                                                    image,
                                                                    website_url,
                                                                    facebook_url,
                                                                    twitter_url,
                                                                    location,
                                                                    board_count,
                                                                    pin_count,
                                                                    like_count,
                                                                    timestamp)
                                          VALUES (:user_id,
                                                  :repinner_user_id,
                                                  :date,
                                                  :period,
                                                  :overall_engagement,
                                                  :username,
                                                  :first_name,
                                                  :last_name,
                                                  :follower_count,
                                                  :following_count,
                                                  :image,
                                                  :website_url,
                                                  :facebook_url,
                                                  :twitter_url,
                                                  :location,
                                                  :board_count,
                                                  :pin_count,
                                                  :like_count,
                                                  :timestamp)

                                          ON DUPLICATE KEY UPDATE
                                          overall_engagement = VALUES(overall_engagement),
                                          follower_count = VALUES(follower_count),
                                          following_count = VALUES(following_count),
                                          pin_count = VALUES(pin_count),
                                          timestamp = VALUES(timestamp)");

                        $STH->execute(array(':user_id'            => $calcs->user_id,
                                            ':repinner_user_id'   => $calcs->repinner_user_id,
                                            ':date'               => $calcs->date,
                                            ':period'             => $calcs->period,
                                            ':overall_engagement' => $calcs->overall_engagement,
                                            ':username'           => $calcs->username,
                                            ':first_name'         => $calcs->first_name,
                                            ':last_name'          => $calcs->last_name,
                                            ':follower_count'     => $calcs->follower_count,
                                            ':following_count'    => $calcs->following_count,
                                            ':image'              => $calcs->image,
                                            ':website_url'        => $calcs->website_url,
                                            ':facebook_url'       => $calcs->facebook_url,
                                            ':twitter_url'        => $calcs->twitter_url,
                                            ':location'           => $calcs->location,
                                            ':board_count'        => $calcs->board_count,
                                            ':pin_count'          => $calcs->pin_count,
                                            ':like_count'         => $calcs->like_count,
                                            ':timestamp'          => $calcs->timestamp));
                    }
                }

            }
            $engine->complete();
            CLI::write(Log::debug('Completed Analysis of current batch of accounts'));
        }
    }
    catch (EngineException $e) {

        CLI::alert($e->getMessage());
        CLI::stop();
    }
    catch (Exception $e) {

        CLI::alert($e->getMessage());
        Log::error($e);
        $engine->fail();
        CLI::stop();

    }
CLI::write(Log::runtime(). 'total runtime');
CLI::write(Log::memory().' peak memory usage');