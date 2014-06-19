<?php namespace Composers\Analytics\Dashboard\Tasks;

use Geocoder\Provider\OIORestProvider;
use
    Task,
    View;

/**
 * Class AnalyticsComposer
 *
 * @package Layouts
 */
class TaskComposer
{

    /**
     * When layout is created, this method is run. Useful for setting defaults,
     * creating assets etc
     *
     * @param $view
     */
    public function create(View $view)
    {
        /** @var Task $task */
        $task = $view->task;
        $vars = [];
        if (
            empty($view->content) &&
            View::exists('dashboard::tasks.' . $task->getName())
        ) {

            $vars['image_source'] = 'http://media-cache-ec0.pinimg.com/avatars/tailwind_1388162942_75.jpg';

            if ($task->getType() == Task::TYPE_USER_ACCOUNT) {
                /**
                 * @var $user_account \UserAccount
                 */
                $user_account         = $task->getIdentifier();
                $vars['image_source'] = $user_account->profile()->getImageUrl();
                $vars['task_source']  = $user_account->profile()->username;
            }

            if ($task->getType() == Task::TYPE_PROFILE) {
                /**
                 * @var $profile \Profile
                 */
                $profile              = $task->getIdentifier();
                $vars['image_source'] = $profile->getImageUrl();
                $vars['task_source']  = $profile->username;
                $vars['board_count']  = $profile->board_count;
            }

            if (in_array(
                $task->getType(),
                array(
                     Task::TYPE_BOARD_DESCRIPTION,
                     Task::TYPE_BOARD_PINS,
                     Task::TYPE_BOARD_CATEGORIES
                )
            )
            ) {
                /**
                 * @var $board \Board
                 */
                $board                = $task->getIdentifier();
                $vars['username']     = $board->getOwnerProfile()->username;
                $vars['image_source'] = $board->image_cover_url;
                $vars['board_url']   = $board->url;
                $vars['board_name']   = $board->name;
                $vars['pin_count']    = $board->pin_count;
            }

            $view->content = View::make('dashboard::tasks.' . $task->getName(), $vars);

        } elseif (empty($view->content)) {
            $view->content = $task->getName();
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