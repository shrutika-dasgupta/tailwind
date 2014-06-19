<?php

namespace Publisher;

use DBCollection;

/**
 * Collection of Post model
 *
 * @author  Yesh
 * @author  Will
 */
class Posts extends DBCollection
{
    const MODEL = 'Publisher\Post';
    const TABLE = 'publisher_posts';

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
     * Sort this posts collection by time slot, including manual and auto-scheduled time slots.
     *
     * @author Janell
     *
     * @return $this
     */
    public function sortByTimeSlots()
    {
        if($this->isEmpty()) {
            return $this;
        }

        // Grab the user account and time slots from the first post in this collection.
        $first_post   = $this->first();
        $user_account = \UserAccount::find($first_post->account_id);
        $time_slots   = TimeSlot::getTimeSlots($user_account);

        // Separate time slots by type for later looping.
        $manual_time_slots = new TimeSlots();
        $auto_time_slots   = new TimeSlots();
        foreach ($time_slots->models as $time_slot) {
            if ($time_slot->pin_uuid) {
                $manual_time_slots->add($time_slot, $time_slot->pin_uuid);
            } else {
                $auto_time_slots->add($time_slot);
            }
        }

        $auto_time_slots->sortByUpcoming();

        $week_count = 0;

        foreach ($this->models as $post) {
            // First, check for a manual time slot for this post.
            $time_slot = $manual_time_slots->getModel($post->id);

            if (!empty($time_slot)) {
                $post->time_slot_timestamp = $time_slot->send_at;
                $post->time_slot_type      = 'manual';
                continue;
            }

            $time_slot = $auto_time_slots->current();

            // If it is the same day and later than the current time, we need to add next.
            $day_now  = date('w', strtotime($time_slot->getTimezone()));
            $time_now = date('Hi', strtotime($time_slot->getTimezone()));

            $next = '';
            if ($day_now == $time_slot->day_preference
                && $time_now > $time_slot->time_preference
            ) {
                $next = 'next ';
            }

            $relative_time = $next .
                $time_slot->getPrettyDay() . ' ' .
                $time_slot->time_preference . ' ' .
                $time_slot->getTimezone();

            if ($week_count > 0) {
                $relative_time .= " +$week_count weeks";
            }

            $post->time_slot_timestamp = strtotime($relative_time);
            $post->time_slot_type      = 'auto';

            // If at the end of this week's time slots, reset the collection and add another week.
            if (empty($auto_time_slots->next())) {
                $auto_time_slots->rewind();
                $week_count++;
            }
        }

        $this->sortBy('time_slot_timestamp', SORT_ASC);

        return $this;
    }

    /**
     * Sort this posts collection by the time each post was sent.
     *
     * @author Janell
     *
     * @param string $sort_order (asc|desc)
     *
     * @return $this
     */
    public function sortByTimeSent($sort_order = 'asc')
    {
        if ($sort_order == 'asc') {
            $this->sortBy('sent_at', SORT_ASC);
        } else {
            $this->sortBy('sent_at', SORT_DESC);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function failed()
    {
        return $this->copy()->filter(function($model) {
            if ($model->status == Post::STATUS_FAILED) {
                return true;
            }
            return false;
        });
    }

    /**
     * Filters this collection of posts and returns those that are awaiting approval.
     *
     * @author Janell
     *
     * @return Posts
     */
    public function awaitingApproval()
    {
        return $this->copy()->filter(function($model) {
            if ($model->status == Post::STATUS_AWAITING_APPROVAL) {
                return true;
            }
            return false;
        });
    }

    /**
     * Filters this collection of posts and returns those that are queued automatically or after
     * being approved by an admin.
     *
     * @author Janell
     *
     * @return Posts
     */
    public function queued()
    {
        return $this->copy()->filter(function($model) {
            if ($model->status == Post::STATUS_QUEUED || $model->status == null) {
                return true;
            }
            return false;
        });
    }

    /**
     * Saves this collection's new sort order to the database. This can be used to fill gaps after
     * updating or removing a post or to reorder the entire collection.
     *
     * @author Janell
     *
     * @return Posts
     */
    public function reorder()
    {
        $i = 1;
        foreach ($this->models as $post) {
            $post->order = $i;
            $this->add($post, true);
            $i++;
        }

        $this->insertUpdateDB();

        return $this;
    }
}
