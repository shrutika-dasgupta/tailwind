<?php namespace Presenters\Dashboard;

use View,
    UserAccount,
    Carbon\Carbon,
    Log;

/**
 * Class Widget
 *
 * @package Presenters\Dashboard
 */
class CommentsWidget extends Widget implements WidgetInterface
{
    /**
     * The number of new followers
     *
     * @var \PinsComments
     */
    protected $comments;

    /**
     * @author  Will
     *
     * @param UserAccount  $user_account
     * @param \UserAccount $reference_time
     */
    public function __construct(UserAccount $user_account, $reference_time)
    {
        parent::__construct($user_account, $reference_time);

        $this->comments
            = $this->user_account->profile()->recentComments(3, $this->reference_time);

        $this->sentiment_metric = $this->comments->count();

        $this->setViewName('recent_comments');
    }

    /**
     * @author  Will
     * @return string
     */
    public function render()
    {
        $vars['comments'] = array();
        foreach ($this->comments as $comment) {

            $c               = new \stdClass();
            $c->pin_id       = $comment->pin_id;
            $c->username     = $comment->commenter()->username;
            $c->comment_text = $comment->comment_text;
            $c->image        = $comment->commenter()->image;
            $c->pin_image    = $comment->pin()->image_url;
            $c->time         = Carbon::createFromFormat('U', $comment->created_at)->diffForHumans();

            $vars['comments'][] = $c;

        }

        $vars['comment_count'] = $this->comments->count();

        return View::make($this->viewPath(), $vars);
    }


}