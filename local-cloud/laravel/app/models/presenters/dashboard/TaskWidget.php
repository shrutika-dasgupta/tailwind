<?php namespace Presenters\Dashboard;

use
    User,
    View;

/**
 * Class Widget
 *
 * @package Presenters\Dashboard
 */
class TaskWidget extends Widget implements WidgetInterface
{
    /**
     * @var $user User
     */
    public $user;

    /**
     * @param User $user
     * @param      $reference_time
     */
    public function __construct(User $user, $reference_time)
    {
        $this->user = $user;

        return parent::__construct($user->getActiveUserAccount(), $reference_time);
    }

    /**
     * Every widget needs to be able to be rendered
     *
     * @return string
     */
    public function render()
    {

        $tasks = $this->user->tasks();

        return View::make(
                   'dashboard::tasks.dashboard_task',
                   [
                   'task'                    => $tasks->copy()->pending()->random(1),
                   'completed_tasks_count'   => $tasks->copy()->completed()->count(),
                   'total_tasks_count'       => $tasks->count(),
                   'completeness_percentage' => number_format($tasks->percentComplete() * 100, 0, '', '')
                   ]
        );
    }
}