<?php

namespace Analytics;

use Input,
    Log,
    Redirect,
    Request,
    Response,
    URL,
    UserHistory,
    View,
    Content\DataFeedEntry,
    Content\DataFeedEntries,
    Publisher\Post;

/**
 * Content discovery controller.
 * 
 * @author Daniel
 *
 * @package Analytics
 */
class ContentController extends BaseController
{

    /**
     * Construct
     *
     * @author  Will
     */
    public function __construct()
    {

        parent::__construct();

        Log::setLog(__FILE__, 'Content', 'Content_Discovery');
    }

    /**
     * Displays a feed of external (non-Pinterest) content.
     *
     * @route /content
     *
     * @return void
     */
    public function index()
    {
        if (!$this->logged_in_customer->hasFeature('content_enabled')) {
            return Redirect::to('/');
        }

        // Setup the page layout.
        $this->buildLayout('content-feed');

        $query   = Input::get('query', 'food');
        $entries = $this->search($query);

        // Check the user's feature permissions.
        $user         = $this->logged_in_customer;
        $can_schedule = $user->hasFeature('pin_scheduling_enabled');
        $can_flag     = $user->hasFeature('content_flagging');

        $this->layout->main_content = View::make('analytics.pages.content.index', array(
            'query'          => $query,
            'entries'        => $entries,
            'posts'          => Post::find(array('account_id' => $this->active_user_account->account_id)),
            'pagination_url' => URL::route('content-paginate', array($query)),
            'user'           => $user,
            'can_schedule'   => $can_schedule,
            'can_flag'       => $can_flag,
        ));
    }

    /**
     * Builds the HTML output for a set of feed entries.
     *
     * @route /content/{query}/{page}/{num?}
     *
     * @param string $query
     * @param int    $page
     * @param int    $num
     *
     * @return string
     */
    public function paginate($query, $page, $num = 100)
    {
        $entries = $this->search($query, $page, $num);

        $posts = Post::find(array('account_id' => $this->active_user_account->account_id));

        // Check the user's feature permissions.
        $user         = $this->logged_in_customer;
        $can_schedule = $user->hasFeature('pin_scheduling_enabled');
        $can_flag     = $user->hasFeature('content_flagging');

        $response = '';
        foreach ($entries as $entry) {
            $response .= View::make(
                'analytics.pages.content.entry',
                array(
                    'entry'        => $entry,
                    'posts'        => $posts,
                    'user'         => $user,
                    'can_schedule' => $can_schedule,
                    'can_flag'     => $can_flag,
                )
            );
        }

        return $response;
    }

    /**
     * Flags an entry.
     *
     * @route /content/entry/{entry_id}/flag
     *
     * @param int $entry_id
     *
     * @return bool
     */
    public function flagEntry($entry_id)
    {
        $entry = DataFeedEntry::find_one((int) $entry_id);
        if ($entry) {
            $flagged = $entry->flag() ? true : false;

            if (Request::ajax()) {
                return Response::json(array(
                    'entry_id' => $entry->id,
                    'success'  => $flagged,
                ));
            }
        }

        return Redirect::back();
    }

    /**
     * Finds entries that match a given search query.
     *
     * @param string $query
     * @param int    $page
     * @param int    $num
     * 
     * @return array
     */
    protected function search($query, $page = 1, $num = 100)
    {
        $this->logged_in_customer->recordEvent(
            UserHistory::VIEW_REPORT,
            array(
               'report' => 'Content-Feed',
               'query'  => $query,
               'page'   => $page,
            )
        );

        return DataFeedEntries::search($query, $page, $num);
    }

    /**
     * Builds common layout elements.
     *
     * @param string $page
     *
     * @return void
     */
    protected function buildLayout($page)
    {
        $this->layout_defaults['page']          = 'Content Discovery';
        $this->layout_defaults['top_nav_title'] = 'Content Discovery';
        $this->layout->top_navigation           = $this->buildTopNavigation();
        $this->layout->side_navigation          = $this->buildSideNavigation($page);
        $this->layout->pre_body_close          .= View::make('analytics.components.pre_body_close.content');
    }
}