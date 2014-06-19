<?php namespace Composers\Analytics\Pages\Settings;

use
    Task,
    View;

/**
 * Class AnalyticsComposer
 *
 * @package Layouts
 */
class TasksComposer
{

    /**
     * When layout is created, this method is run. Useful for setting defaults,
     * creating assets etc
     *
     * @param $view
     */
    public function create(View $view)
    {
        $view->completeness_percentage = number_format(
            $view->completeness_percentage * 100,
            0, '', ''
        );

        $tasks = $view->tasks->copy();
        $view->tasks = [];

        /** @var $task Task */
        foreach ($tasks as $task) {
            $class = '';

            if ($task->isCompleted()) {
                $class = 'completed-task';
            }

            $view->tasks[] = [
                $class,
                View::make('dashboard::tasks.task',['task'=>$task])
            ];
        }
    }

    /**
     * This fires when the view is rendered
     *
     * @param View $view
     *
     * @author  Will
     */
    public function compose(View $view)
    {

    }
}