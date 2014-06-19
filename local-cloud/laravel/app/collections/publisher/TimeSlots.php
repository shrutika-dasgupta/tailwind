<?php namespace Publisher;

use CollectionException,
    DBCollection,
    Log;

/**
 * Collection of TimeSlot models
 *
 * @author  Yesh
 * @author  Will
 */
class TimeSlots extends DBCollection
{
    const MODEL = 'Publisher\TimeSlot';
    const TABLE = 'publisher_time_slots';

    public $table = 'publisher_time_slots';

    public $columns = array(
        'id',
        'account_id',
        'day_preference',
        'time_preference',
        'timezone',
        'pin_uuid',
        'send_at',
    );

    public $primary_keys = array('id');


    /**
     * We set the next_send_time for time_slots that have been scheduled if they
     * are present on the auto-schedule. If not, they are deleted.
     * @author   Yesh
     * @author  Will
     *
     * @return $this
     *
     */
    public function setNextSendTimes()
    {
        /** @var $time_slot TimeSlot */
        foreach ($this->models as $key=> & $time_slot) {

            /**
             * If the pin_uuid is NOT set for the time_slots that are to be sent,
             * it means that the time slot is recurring. So, we find the next_send_time
             * and we update the collection to the database
             *
             * If the pin_uuid is set, we delete the time slot.
             *
             */
            if (empty($time_slot->pin_uuid)) {

                $time_slot->calculateSendTime();

            } else {
                TimeSlot::delete($time_slot->id);
                unset($this->models[$key]);
            }
        }

        try {
            $this->insertUpdateDB();
        }
        catch (CollectionException $e) {
            Log::notice("No time slots to update");
        }

        return $this;
    }


    /**
     * Sort the time slots by where they appear on the calendar
     *
     * @author  Will
     * @author  Alex
     */
    public function sortByCalendarSlot()
    {

        $this->sortByUpcoming();

        foreach ($this->models as $time_slot) {
            $time_slot->normailzed_time = date('hhmm', $this->next_timestamp);
            $time_slot->normalized_day  = date('d', $this->next_timestamp);
        }

        $this->sort('normalized_day', SORT_ASC, 'normalized_time', SORT_ASC);

        return $this;
    }


    /**
     * Sorts the time slots by what is coming up next
     *
     * @author  Will
     * @author  Alex
     */
    public function sortByUpcoming()
    {

        /** @var $time_slot TimeSlot */
        foreach ($this->models as $time_slot) {

            /*
             * If it is the same day IN THE SAME TIMEZONE
             * and
             * it is later than the time IN THE SAME TIMEZONE
             * then we need to add next
             */
            $time_now = date('Hi', strtotime($time_slot->getTimezone()));
            $day_now  = date('w', strtotime($time_slot->getTimezone()));

            if (
                $day_now == $time_slot->day_preference
                AND $time_now > $time_slot->time_preference
            ) {
                $next = 'next ';
                $day  = $time_slot->getPrettyDay();
            } elseif ($day_now == $time_slot->day_preference) {

                $next = '';
                $day  = 'today';
            } else {
                $next = '';
                $day  = $time_slot->getPrettyDay();
            }

            $time_slot->next_timestamp = strtotime(
                $next .
                $day . ' ' .
                $time_slot->time_preference . ' ' .
                $time_slot->getTimezone()
            );
        }

        $this->sortBy('next_timestamp', SORT_ASC);

        return $this;
    }

    /**
     * Gets the timeslots that are actually manually set Posts
     * as they have a pin_uuid
     *
     * @author  Will
     *
     * @return self
     */
    public function manual()
    {
        $manual = new self;
        foreach ($this->models as $time_slot) {
            if (!empty($time_slot->pin_uuid)) {
                $manual->add($time_slot);
            }
        }

        return $manual;
    }

    /**
     * Gets the timeslots that are auto timeslots
     *
     * @author  Will
     * @return TimeSlots
     */
    public function auto()
    {
        $auto = new self;

        foreach ($this->models as $time_slot) {
            if (empty($time_slot->pin_uuid)) {
                $auto->add($time_slot);
            }
        }

        return $auto;

    }

    /**
     * @author  Will
     *
     * @return Posts
     */
    public function getManualPosts()
    {
        $manual_posts = $this->manual();

        if ($manual_posts->isNotEmpty()) {

            $post_ids = $this->manual()->stringifyField('pin_uuid');

            $STH = $this->DBH->prepare('
                SELECT *
                FROM publisher_posts
                WHERE id IN (' . $post_ids . ')
                AND ((status != :failed_status and status != :awaiting_approval_status and status != :processing_status) or status IS NULL)
            ');

            $STH->execute(
                array(
                     ':failed_status'            => Post::STATUS_FAILED,
                     ':awaiting_approval_status' => Post::STATUS_AWAITING_APPROVAL,
                     ':processing_status'        => Post::STATUS_PROCESSING,
                )
            );

            return Posts::createFromDBData($STH->fetchAll());
        }

        return new Posts;
    }

    /**
     * @author  Will
     *
     * @return Posts
     */
    public function getPosts()
    {
        /**
         * To start, lets get all the manual posts that are connected to this
         * collection of TimeSlots
         */
        $posts = $this->getManualPosts();

        /**
         * For each of the auto time slots, lets find it a queued up pin!
         * YAAAAY
         *
         * Note: I know this is a nested query, I'm not sure how to get around
         * this. If we do a group by and we have multiple timeslots with the same
         * account id, we'll only get one post for two spots.
         */
        foreach ($this->auto() as $time_slot) {

            $where_clause = '';

            /**
             * For each post we find, we add it to this NOT IN list
             * This way, we'll avoid duplicates
             */
            if ($posts->isNotEmpty()) {
                $where_clause = 'AND id NOT IN (' .
                    $posts->stringifyField('id')
                    . ')';
            }

            $STH = $this->DBH->prepare('
                SELECT * from publisher_posts
                WHERE account_id = :account_id ' . $where_clause . '
                AND sent_at IS NULL
                AND ((status != :failed_status and status != :awaiting_approval_status and status != :processing_status) or status IS NULL)
                ORDER BY `order`,`id`;
            ');

            $STH->execute(
                array(
                     ':account_id'               => $time_slot->account_id,
                     ':failed_status'            => Post::STATUS_FAILED,
                     ':awaiting_approval_status' => Post::STATUS_AWAITING_APPROVAL,
                     ':processing_status'        => Post::STATUS_PROCESSING,
                )
            );

            $post_data = $STH->fetch();

            if ($post_data) {
                $posts->add(Post::createFromDBData($post_data));
            }
        }

        return $posts;
    }
}
