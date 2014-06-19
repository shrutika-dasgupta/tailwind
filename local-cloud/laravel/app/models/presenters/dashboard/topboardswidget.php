<?php namespace Presenters\Dashboard;

use View,
    Str,
    Log;

/**
 * Class Widget
 *
 * @package Presenters\Dashboard
 */
class TopBoardsWidget extends Widget implements WidgetInterface
{
    /**
     * The top boards
     *
     * @var \Boards
     */
    protected $top_boards;

    /**
     * The widget should return a string of HTML
     *
     * @return string
     */
    public function render()
    {

        $top_board          = $this->top_boards->first();

        if(!$top_board) {
            return '';
        }

        $top_sort_value     = $this->top_boards->sortValueAtNthKey(1);

        $vars = array(
            'top_board'         => $top_board,
            'top_board_category' => prettyCategoryName($top_board->category),
            'top_board_metric'   => $top_sort_value,
            'top_total_metric'   => $top_board->getRepins(),
            'top_board_virality' => $top_board->viralityScore(),
            'cover_image'        => $top_board->image_cover_url,
            'boards'             => $this->top_boards
        );

        return View::make($this->viewPath(), $vars);
    }

    /**
     * @author Will
     */
    public function byMostRepins()
    {
        $this->top_boards = $this->user_account
            ->profile()
            ->topBoardsByRepins($this->reference_time)
            ->limit(3);

        $this->setViewName('most_repinned_boards');

        return $this;
    }

    /**
     * @author Will
     */
    public function byMostFollows()
    {
        $this->top_boards = $this->user_account
            ->profile()
            ->topBoardsByFollowers($this->reference_time)
            ->limit(3);

        $this->setViewName('most_followed_boards');

        return $this;
    }

}