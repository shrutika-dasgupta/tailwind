<?php

namespace Presenters\Publisher;

use Board,
    Publisher\Post,
    Publisher\TimeSlot,
    URL,
    User,
    UserAccount,
    View;

/**
 * Class EditPostModal
 *
 * @author Janell
 *
 * @package Presenters\Publisher
 */
class EditPostModal
{
    /**
     * The user editing this post.
     *
     * @var User
     */
    protected $user;

    /**
     * The user account associated with this post.
     *
     * @var UserAccount
     */
    protected $user_account;

    /**
     * The type of post we are editing.
     *
     * @var string (new|draft|queued)
     */
    protected $post_type;

    /**
     * A queued or draft post object.
     *
     * @var Post
     */
    protected $post;

    /**
     * Optional configurations for the edit post modal.
     *
     * @var array
     */
    protected $options;

    /**
     * Class constructor.
     *
     * @param User        $user
     * @param UserAccount $user_account
     * @param string      $post_type
     * @param Post        $post
     * @param array       $options
     *
     * @return void
     */
    public function __construct(User $user, UserAccount $user_account, $post_type, $post, $options)
    {
        $this->user         = $user;
        $this->user_account = $user_account;
        $this->post_type    = $post_type;
        $this->post         = $post;
        $this->options      = $options;
    }

    /**
     * Returns a new EditPostModal instance.
     *
     * @param User        $user
     * @param UserAccount $user_account
     * @param string      $post_type
     * @param Post        $post
     * @param array       $options
     *
     * @return EditPostModal
     */
    public static function instance(User $user, UserAccount $user_account, $post_type = 'new', $post = null, $options = array())
    {
        return new self($user, $user_account, $post_type, $post, $options);
    }

    /**
     * Renders and returns the view for this component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        if ($this->post_type == 'queued') {
            $form_action = URL::route('publisher-update-post');
        } else {
            $form_action = URL::route('publisher-create-post');
        }

        $schedule_type = 'auto';
        $send_time     = time();

        if ($this->post_type == 'queued') {
            $time_slot = TimeSlot::find(array(
                'pin_uuid' => $this->post->id
            ));

            if (is_array($time_slot)) {
                $time_slot = current($time_slot);
            };

            if (!empty($time_slot)) {
                $schedule_type = 'manual';
                $send_time     = $time_slot->send_at;
            }
        }

        // Set the default button text.
        $close_btn_text  = ($this->post_type == 'draft') ? 'Save Changes' : 'Cancel';
        $submit_btn_text = ($this->post_type == 'queued') ? 'Save Changes' : 'Add to Queue';

        // Check for customized button text.
        $close_btn_text  = array_get($this->options, 'close_btn_text', $close_btn_text);
        $submit_btn_text = array_get($this->options, 'submit_btn_text', $submit_btn_text);

        // Alter submit button text if user is sending post to approval queue.
        if (!$this->user->hasFeature('pin_scheduling_admin')) {
            $submit_btn_text = 'Submit for Approval';
        }

        return View::make('analytics.components.publisher.edit_post_modal', array(
            'form_action'     => $form_action,
            'post_type'       => $this->post_type,
            'post'            => $this->post,
            'boards'          => $this->user_account->profile()->getActiveDBBoards()->sortBy('name'),
            'schedule_type'   => $schedule_type,
            'hours'           => range(1, 12),
            'minutes'         => range(0, 59),
            'hour'            => date('g', $send_time),
            'minute'          => date('i', $send_time),
            'date'            => date('Y-m-d', $send_time),
            'am_pm'           => date('A', $send_time),
            'posts_caldata'   => $this->getCalendarData(),
            'source'          => array_get($this->options, 'source'),
            'close_btn_text'  => $close_btn_text,
            'submit_btn_text' => $submit_btn_text,
        ));
    }

    /**
     * Builds the caldata string used by Calendario to display upcoming scheduled posts.
     *
     * @return string
     */
    private function getCalendarData()
    {
        $upcoming_posts = $this->user_account->getPosts(false)->sortByTimeSlots();
        $user_timezone  = $this->user->getTimezone();

        $posts_by_date = array();
        foreach ($upcoming_posts as $post) {
            $post_date = \Carbon\Carbon::createFromTimestamp($post->time_slot_timestamp, $user_timezone)
                ->format('m-d-Y');

            if (!isset($posts_by_date[$post_date])) {
                $posts_by_date[$post_date] = array();
            }

            $posts_by_date[$post_date][] = $post;
        }

        $posts_caldata = '';
        foreach ($posts_by_date as $post_date => $posts) {
            $calendar_entry = View::make('analytics.components.publisher.calendar_data_entry', array(
                'posts'         => $posts,
                'user_timezone' => $user_timezone,
            ));

            // Strip all sorts of nasty characters that break js.
            $calendar_entry = str_replace("\n", '\n', str_replace('"', '\"', addcslashes(str_replace("\r", '', $calendar_entry), "\0..\37'\\")));

            $posts_caldata .= "'$post_date' : '$calendar_entry',";
        }

        return rtrim($posts_caldata, ',');
    }
}