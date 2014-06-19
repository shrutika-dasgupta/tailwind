<?php

/**
 * Class Tasks
 */
class Tasks extends Collection
{
    /**
     * @var array List of the tasks for a profile
     */
    public static $profile_task_names = array(
        'has_description',
        'has_facebook',
        'has_website',
        'has_website_verified',
        'has_location',
        'has_image',
        'board_count',
        'boards_less_than_ten_pins',
        'boards_no_categories',
        'boards_no_description'
    );

    public static $user_account_task_names = array(
        'added_a_domain',
        'synced_google_analytics',
        'selected_an_industry',
        'selected_account_type',
    );

    public static $organization_task_names = array(

    );

    public static $user_task_names = array(
        'invited_collaborator',
        'confirmed_email',
    );

    /**
     * @param Task $task
     * @param null $key
     *
     * @return $this
     */
    public function add(Task $task, $key = null)
    {
        if (is_null($key)) {
            $key = $task->getKey();
        }

        return parent::add($task, $key);
    }

    /**
     * @author  Will
     * @return $this
     */
    public function completed()
    {
        return $this->filter(function (Task $task) {
            return $task->isCompleted();
        });
    }

    /**
     * @author  Will
     * @return $this
     */
    public function pending()
    {
        return $this->filter(function (Task $task) {
            return !$task->isCompleted();
        });
    }

    /**
     * @return float
     */
    public function percentComplete() {
        return $this->copy()->completed()->count()/$this->count();
    }
}