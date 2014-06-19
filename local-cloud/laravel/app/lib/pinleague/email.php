<?php

namespace Pinleague;

use User;
use View;
use Mail;

/**
 * Email wrapper class.
 * 
 * @author Daniel
 */
class Email
{
    /**
     * UserHistory event.
     *
     * @var string
     */
    protected $event;

    /**
     * UserHistory event data.
     *
     * @var array
     */
    protected $event_data = array();

    /**
     * Email template.
     *
     * @var string
     */
    protected $template = 'shared.emails.templates.main';

    /**
     * Email subject.
     *
     * @var string
     */
    protected $subject;

    /**
     * Email html body.
     *
     * @var string
     */
    protected $html;

    /**
     * Email plaintext body.
     *
     * @var string
     */
    protected $plaintext;

    /**
     * Email recipient User.
     *
     * @var User
     */
    protected $to;

    /**
     * Email reply to User.
     *
     * @var User
     */
    protected $reply_to;

    /**
     * Email tags for categorization and tracking.
     *
     * @var array
     */
    protected $tags = array();

    /**
     * Initializes the class.
     *
     * @param string $event
     * @param array  $event_data
     * 
     * @return void
     */
    public function __construct($event, $event_data)
    {
        $this->event      = $event;
        $this->event_data = $event_data;
    }

    /**
     * Gets protected variables - magically.
     *
     * @param string $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    /**
     * Gets a new mail instance.
     *
     * @param string $event
     * @param array  $event_data
     *
     * @return \Pinleague\Email
     */
    public static function instance($event = null, $event_data = array())
    {
        return new self($event, $event_data);
    }

    /**
     * Sets the email template
     *
     * @param string $template
     *
     * @return void
     */
    public function template($template)
    {
        $this->template = $template;
    }

    /**
     * Sets the email subject.
     *
     * @param string $subject
     *
     * @return void
     */
    public function subject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Sets the email body.
     *
     * @param string $view
     * @param array  $data Optional data to pass to views.
     *
     * @return void
     */
    public function body($view, $data = array())
    {
        $this->html = (string) View::make("shared.emails.html.$view", $data);
        $this->plaintext = (string) View::make("shared.emails.plaintext.$view", $data);
    }

    /**
     * Sets the email recipient user.
     *
     * @param User $user
     *
     * @return void
     */
    public function to(User $user)
    {
        $this->to = $user;
        $this->event_data['email'] = $user->email;
    }

    /**
     * Sets the email reply to user.
     *
     * @param User $user
     *
     * @return void
     */
    public function replyTo(User $user)
    {
        $this->reply_to = $user;
    }

    /**
     * Sets tags for categorization and tracking.
     *
     * @param array $tags
     *
     * @return void
     */
    public function tags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Sends the email.
     *
     * @return bool
     */
    public function send()
    {
        $instance = $this;

        $sent = Mail::send(
            $this->template,
            array('main_body' => $this->html),
            function ($message) use ($instance) {
                $message->subject($instance->subject);
                $message->to($instance->to->email, $instance->to->getName());

                if ($instance->reply_to) {
                    $message->replyTo($instance->reply_to->email, $instance->reply_to->getName());
                }

                $message->addPart($instance->plaintext, 'text/plain');

                foreach ($instance->tags as $tag) {
                    $message->getHeaders()->addTextHeader('X-Mailgun-Tag', $tag);
                }
            }
        );

        if (!$sent) {
            Log::alert("Failed to send email \"{$instance->subject}\" to {$instance->to->email}.");
            return false;
        }

        // Record the user history event.
        if ($this->event) {
            $this->to->recordEvent($this->event, $this->event_data);
        }

        return true;
    }
}
