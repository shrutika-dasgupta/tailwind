<?php namespace Analytics;

use
    Carbon\Carbon,
    DateTime,
    Input,
    Lang,
    Log,
    Publisher\Post,
    Publisher\Posts,
    Publisher\UploadedPost,
    Presenters\Publisher\EditPostModal,
    Presenters\TimezoneSelector,
    Redirect,
    Request,
    Response,
    Session,
    URL,
    UserHistory,
    Validator,
    View;

/**
 * Publisher controller.
 * 
 * @author Daniel
 *
 * @package Analytics
 */
class PublisherController extends BaseController
{
    /**
     * The session key used to store post layout preference.
     * @const
     */
    const POST_LAYOUT_SESSION_KEY = 'publisher-post-layout';

    /**
     * Displays a list of scheduled/published posts.
     *
     * @route /publisher
     * @route /publisher/posts
     *
     * @param string $view
     *
     * @param string $layout
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function posts($view = 'scheduled', $layout = null)
    {
        Log::setLog(__FILE__, 'Publisher', 'Publisher_Posts_'.$view);

        if (!$this->logged_in_customer->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        if (empty($layout)) {
            $layout = self::getLayoutPreference();
        } elseif ($layout != self::getLayoutPreference()) {
            self::saveLayoutPreference($layout);
        }

        $this->logged_in_customer->recordEvent(
            UserHistory::VIEW_PUBLISHER_POSTS,
            array(
                'view'   => $view,
                'layout' => $layout,
            )
        );

        $this->buildLayout('publisher-posts-' . $view);

        switch ($view) {
            case 'drafts':
                return $this->viewDrafts();
                break;
            case 'pending':
                $this->viewPendingPosts($layout);
                break;
            case 'published':
                $this->viewPublishedPosts($layout);
                break;
            case 'scheduled':
            default:
                $this->viewScheduledPosts($layout);
                break;
        }
    }

    /**
     * Saves POST data to session for later use.
     *
     * @route /publisher/draft-posts
     *
     * @return Redirect
     */
    public function draftPosts()
    {
        $data = Input::get('data');

        Post::storeDrafts(json_decode($data, true));

        if ($this->logged_in_customer) {
            $this->logged_in_customer->recordEvent(
                UserHistory::ADD_DRAFTS,
                array(
                    'drafts'  => $data,
                    'source'  => Input::get('source'),
                    'browser' => Input::get('browser'),
                )
            );
        }

        return Redirect::route('publisher-posts', array('drafts'));
    }

    /**
     * Removes a draft from session.
     *
     * @author Janell
     *
     * @route /publisher/draft/delete
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function deleteDraft()
    {
        if (!$this->logged_in_customer->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        if (Request::ajax() == false) {
            return Redirect::route('publisher-schedule');
        }

        $image_url = Input::get('image_url');

        Post::removeFromDrafts($image_url);

        $this->logged_in_customer->recordEvent(
            UserHistory::DELETE_DRAFT,
            array(
                'image_url' => $image_url,
            )
        );

        return Response::json(array(
            'success' => true,
            'message' => 'Draft deleted.',
        ));
    }

    /**
     * Displays the create post form.
     *
     * @route /publisher/post/new
     *
     */
    public function newPost()
    {
        if (!$this->logged_in_customer->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        if (Request::ajax() == false) {
            return Redirect::route('publisher-schedule');
        }

        $this->logged_in_customer->recordEvent(
            UserHistory::VIEW_EDIT_POST_MODAL,
            array(
                'post_type' => 'new',
            )
        );

        $edit_post_modal = EditPostModal::instance(
            $this->logged_in_customer,
            $this->active_user_account
        );

        return Response::json(array(
            'success' => true,
            'html'    => $edit_post_modal->render()->render(),
        ));
    }

    /**
     * Displays the create draft post form.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function newDraftPost()
    {
        $data = (object) Input::all();

        $post = new Post();
        $post->loadDBData($data);
        
        return \Presenters\Publisher\EditPostModal::instance(
            $this->logged_in_customer,
            $this->active_user_account,
            'draft',
            $post,
            object_get($data, 'options', array())
        )->render();
    }

    /**
     * Handles the creation of posts.
     *
     * @route /publisher/post/create
     *
     * @return Redirect
     */
    public function createPost()
    {
        if (!$this->logged_in_customer->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        $data        = Input::all();
        $total_posts = Input::get('total', 1);
        $posts       = array();

        if ($total_posts == 1 && !empty($data['image_url']) && !is_array($data['image_url'])) {
            $boards = (array) $data['board'];
            foreach ($boards as $board_id) {
                $posts[] = array(
                    'parent_pin'    => $data['parent_pin'],
                    'link'          => $data['link'],
                    'image_url'     => $data['image_url'],
                    'description'   => $data['description'],
                    'board'         => $board_id,
                    'schedule_type' => $data['schedule_type'],
                    'day_time'      => $data['date'],
                    'hour'          => $data['hour'],
                    'minutes'       => $data['minute'],
                    'am_pm'         => $data['am_pm'],
                );
            }
        } else {
            for ($i = 1; $i <= $total_posts; $i++) {
                if (array_get($data, "link.$i")) {
                    $boards = (array) array_get($data, "board.$i");
                    foreach ($boards as $board_id) {
                        $posts[] = array(
                            'parent_pin'    => array_get($data, "parent_pin.$i"),
                            'link'          => array_get($data, "link.$i"),
                            'image_url'     => array_get($data, "image_url.$i"),
                            'description'   => array_get($data, "description.$i"),
                            'board'         => $board_id,
                            'schedule_type' => array_get($data, "schedule_type.$i"),
                            'day_time'      => array_get($data, "date.$i"),
                            'hour'          => array_get($data, "hour.$i"),
                            'minutes'       => array_get($data, "minute.$i"),
                            'am_pm'         => array_get($data, "am_pm.$i"),
                        );
                    }
                }
            }
        }

        foreach ($posts as $post_data) {
            $post_data['domain'] = str_replace('www.', '', parse_url($post_data['link'], PHP_URL_HOST));

            $validator = Validator::make($post_data, array(
                'link'          => 'required|url|max:500',
                'domain'        => 'required',
                'image_url'     => 'required|url|max:500',
                'description'   => 'required',
                'board'         => 'required',
                'schedule_type' => 'sometimes|in:auto,manual',
            ));

            $validator->sometimes('day_time', 'required|date|after:yesterday', function($input) {
                return $input->schedule_type == 'manual';
            });

            $validator->sometimes('hour', 'required|numeric|between:1,12', function($input) {
                return $input->schedule_type == 'manual';
            });

            $validator->sometimes('minutes', 'required|numeric|between:0,59', function($input) {
                return $input->schedule_type == 'manual';
            });

            $validator->sometimes('am_pm', 'required|in:AM,PM', function($input) {
                return $input->schedule_type == 'manual';
            });

            if ($validator->passes()) {
                Post::create($this->logged_in_customer, $post_data);
                Post::removeFromDrafts($post_data['image_url']);

                $this->logged_in_customer->recordEvent(
                    UserHistory::ADD_POST,
                    array(
                        'source' => Input::get('source'),
                        'post'   => $post_data,
                    )
                );
            } else if (Request::ajax()) {
                return Response::json(array(
                    'success' => false,
                    'message' => 'Your post could not be created',
                    'errors'  => $validator->messages()->all(),
                ));
            } else {
                return Redirect::back()->withErrors($validator);
            }
        }

        if (!$this->logged_in_customer->hasFeature('pin_scheduling_admin')) {
            $post_type = 'pending';
            $message   = ($total_posts > 1) ? 'Posts submitted for approval.' : 'Post submitted for approval.';
        } else {
            $post_type = 'scheduled';
            $message   = ($total_posts > 1) ? 'Posts successfully scheduled.' : 'Post successfully scheduled.';
        }

        if (Request::ajax()) {
            Session::flash('flash_message', $message);
            $response = array(
                'success'   => true,
                'message'   => $message,
                'post_type' => $post_type,
            );

            $response['redirect'] = URL::route('publisher-posts', array($post_type));

            return Response::json($response);
        }

        return Redirect::route('publisher-posts', array($post_type))->with(
            'flash_message',
            $message
        );
    }

    /**
     * Returns the edit post modal via ajax request.
     *
     * @author Janell
     *
     * @param $id
     *
     * @return mixed
     */
    public function editPost($id)
    {
        if (!$this->logged_in_customer->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        if (Request::ajax() == false) {
            return Redirect::route('publisher-schedule');
        }

        $post = Post::find($id);
        if (empty($post)) {
            return Response::json(array(
                'success' => false,
                'message' => 'Could not find post',
            ));
        }

        $this->logged_in_customer->recordEvent(
            UserHistory::VIEW_EDIT_POST_MODAL,
            array(
                'post_type' => 'queued',
                'post'      => $post,
            )
        );

        $edit_post_modal = EditPostModal::instance(
            $this->logged_in_customer,
            $this->active_user_account,
            'queued',
            $post
        );

        return Response::json(array(
            'success' => true,
            'message' => 'Found post',
            'html'    => $edit_post_modal->render()->render(),
        ));
    }

    /**
     * Updates an existing post.
     *
     * @author Janell
     *
     * @return mixed
     */
    public function updatePost()
    {
        if (!$this->logged_in_customer->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        $data = Input::all();

        $post = Post::find($data['id']);

        if (empty($post)) {
            if (Request::ajax() == false) {
                return Redirect::route('publisher-posts')->with(
                    'flash_error',
                    'Could not find post'
                );
            }

            return Response::json(array(
                'success' => false,
                'message' => 'Could not find post',
            ));
        }

        $post_data = array(
            'link'          => $data['link'],
            'domain'        => str_replace('www.', '', parse_url($data['link'], PHP_URL_HOST)),
            'description'   => $data['description'],
            'board'         => $data['board'],
            'schedule_type' => $data['schedule_type'],
            'day_time'      => $data['date'],
            'hour'          => $data['hour'],
            'minutes'       => $data['minute'],
            'am_pm'         => $data['am_pm'],
        );

        $validator = Validator::make($post_data, array(
            'link'          => 'required|url|max:500',
            'domain'        => 'required',
            'description'   => 'required',
            'board'         => 'required',
            'schedule_type' => 'sometimes|in:auto,manual',
        ));

        $validator->sometimes('day_time', 'required|date|after:yesterday', function($input) {
            return $input->schedule_type == 'manual';
        });

        $validator->sometimes('hour', 'required|numeric|between:1,12', function($input) {
            return $input->schedule_type == 'manual';
        });

        $validator->sometimes('minutes', 'required|numeric|between:0,59', function($input) {
            return $input->schedule_type == 'manual';
        });

        $validator->sometimes('am_pm', 'required|in:AM,PM', function($input) {
            return $input->schedule_type == 'manual';
        });

        if ($validator->passes()) {
            $post->update($this->logged_in_customer, $post_data);

            $this->logged_in_customer->recordEvent(
                UserHistory::UPDATE_POST,
                array(
                    'post' => $post,
                )
            );
        } else if (Request::ajax()) {
            return Response::json(array(
                'success' => false,
                'message' => 'Your post could not be updated',
                'errors'  => $validator->messages()->all(),
            ));
        } else {
            return Redirect::back()->withErrors($validator);
        }

        if (Request::ajax()) {
            return Response::json(array(
                'success' => true,
                'message' => 'Post successfully updated',
            ));
        }

        return Redirect::route('publisher-posts')->with(
            'flash_message',
            'Post successfully updated'
        );
    }

    /**
     * Schedules a post to send at the current time.
     *
     * @author Janell
     *
     * @return mixed
     */
    public function publishPost()
    {
        if (!$this->logged_in_customer->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        $data = Input::all();

        $post = Post::find($data['id']);

        if (empty($post)) {
            if (Request::ajax() == false) {
                return Redirect::route('publisher-posts')->with(
                    'flash_error',
                    'Could not find post'
                );
            }

            return Response::json(array(
                'success' => false,
                'message' => 'Could not find post',
            ));
        }

        $post_data = array(
            'link'          => $data['link'],
            'domain'        => str_replace('www.', '', parse_url($data['link'], PHP_URL_HOST)),
            'description'   => $data['description'],
            'board'         => $data['board'],
        );

        $validator = Validator::make($post_data, array(
            'link'        => 'required|url|max:500',
            'domain'      => 'required',
            'description' => 'required',
            'board'       => 'required',
        ));

        if (!$validator->passes()) {
            if (Request::ajax()) {
                return Response::json(array(
                    'success' => false,
                    'message' => 'Your post could not be published',
                    'errors'  => $validator->messages()->all(),
                ));
            }

            return Redirect::back()->withErrors($validator);
        }

        $post->link        = $post_data['link'];
        $post->domain      = $post_data['domain'];
        $post->description = $post_data['description'];
        $post->board_name  = $post_data['board'];

        $published = $post->publish();
        if (!$published) {
            if (Request::ajax()) {
                return Response::json(array(
                    'success' => false,
                    'message' => 'An error occurred while publishing your post. Please try again.',
                ));
            }

            return Redirect::back()->with(
                'flash_error',
                'An error occurred while publishing your post. Please try again.'
            );
        }

        $this->logged_in_customer->recordEvent(
            UserHistory::PUBLISH_POST,
            array(
                'post' => $post,
            )
        );

        if (Request::ajax()) {
            return Response::json(array(
                'success' => true,
                'message' => 'Your post has been published.',
            ));
        }

        return Redirect::route('publisher-posts')->with(
            'flash_message',
            'Your post has been published.'
        );
    }

    /**
     * Approves posts, possibly with changes from an admin.
     *
     * @author Janell
     *
     * @return mixed
     */
    public function approvePost()
    {
        if (!$this->logged_in_customer->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        if (!$this->logged_in_customer->hasFeature('pin_scheduling_admin')) {
            return Redirect::route('publisher');
        }

        $data        = Input::all();
        $total_posts = Input::get('total', 1);
        $posts       = array();

        if ($total_posts == 1 && !empty($data['link']) && !is_array($data['link'])) {
            $posts[] = array(
                'id'            => $data['id'],
                'link'          => $data['link'],
                'description'   => $data['description'],
                'board'         => $data['board'],
                'schedule_type' => $data['schedule_type'],
                'day_time'      => $data['date'],
                'hour'          => $data['hour'],
                'minutes'       => $data['minute'],
                'am_pm'         => $data['am_pm'],
            );
        } else {
            for ($i = 1; $i <= $total_posts; $i++) {
                if (array_get($data, "link.$i")) {
                    $posts[] = array(
                        'id'            => array_get($data, "id.$i"),
                        'link'          => array_get($data, "link.$i"),
                        'description'   => array_get($data, "description.$i"),
                        'board'         => array_get($data, "board.$i"),
                        'schedule_type' => array_get($data, "schedule_type.$i"),
                        'day_time'      => array_get($data, "date.$i"),
                        'hour'          => array_get($data, "hour.$i"),
                        'minutes'       => array_get($data, "minute.$i"),
                        'am_pm'         => array_get($data, "am_pm.$i"),
                    );
                }
            }
        }

        foreach ($posts as $post_data) {
            $post_data['domain'] = str_replace('www.', '', parse_url($post_data['link'], PHP_URL_HOST));
            $post_data['status'] = Post::STATUS_QUEUED;

            $validator = Validator::make($post_data, array(
                'id'            => 'required',
                'link'          => 'required|url|max:500',
                'domain'        => 'required',
                'description'   => 'required',
                'board'         => 'required',
                'schedule_type' => 'sometimes|in:auto,manual',
            ));

            $validator->sometimes('day_time', 'required|date|after:yesterday', function($input) {
                return $input->schedule_type == 'manual';
            });

            $validator->sometimes('hour', 'required|numeric|between:1,12', function($input) {
                return $input->schedule_type == 'manual';
            });

            $validator->sometimes('minutes', 'required|numeric|between:0,59', function($input) {
                return $input->schedule_type == 'manual';
            });

            $validator->sometimes('am_pm', 'required|in:AM,PM', function($input) {
                return $input->schedule_type == 'manual';
            });

            if ($validator->passes()) {
                $post = Post::find($post_data['id']);

                if (empty($post)) {
                    if (Request::ajax() == false) {
                        return Redirect::route('publisher-posts')->with(
                            'flash_error',
                            'Could not find post'
                        );
                    }

                    return Response::json(array(
                        'success' => false,
                        'message' => 'Could not find post',
                    ));
                }

                $post->update($this->logged_in_customer, $post_data);

                $this->logged_in_customer->recordEvent(
                    UserHistory::APPROVE_POST,
                    array(
                        'post' => $post,
                    )
                );
            } else if (Request::ajax()) {
                return Response::json(array(
                    'success' => false,
                    'message' => 'Your post could not be scheduled',
                    'errors'  => $validator->messages()->all(),
                ));
            } else {
                return Redirect::back()->withErrors($validator);
            }
        }

        $message = 'Post successfully scheduled';
        if ($total_posts > 1) {
            $message = 'Posts successfully scheduled';
        }

        if (Request::ajax()) {
            Session::flash('flash_message', $message);
            return Response::json(array(
                'success'  => true,
                'message'  => $message,
                'redirect' => URL::route('publisher-posts', array('scheduled')),
            ));
        }

        return Redirect::route('publisher-posts', array('scheduled'))->with(
            'flash_message',
            $message
        );
    }

    /**
     * Reorders an account's scheduled posts.
     *
     * @author Janell
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function orderPosts()
    {
        if (!$this->logged_in_customer->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        if (!$this->logged_in_customer->hasFeature('pin_scheduling_admin')) {
            return Redirect::route('publisher');
        }

        $post_ids = Input::get('post_ids');
        $posts    = new Posts();

        foreach ($post_ids as $post_id) {
            if (empty($post_id)) {
                continue;
            }

            $post = Post::find($post_id);
            if ($post instanceof Post) {
                $posts->add($post);
            }
        }

        $posts->reorder();

        if (Request::ajax()) {
            return Response::json(array(
                'success' => true,
                'message' => 'Posts successfully reordered.',
            ));
        }

        return Redirect::route('publisher-posts', array('scheduled'))->with(
            'flash_message',
            'Posts successfully reordered.'
        );
    }

    /**
     * Displays the publisher schedule (days and times).
     *
     * @route /publisher/schedule
     *
     * @return void
     */
    public function schedule()
    {
        Log::setLog(__FILE__, 'Publisher', 'Publisher_Schedule');

        if (!$this->logged_in_customer->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        $this->logged_in_customer->recordEvent(
            UserHistory::VIEW_PUBLISHER_SCHEDULE
        );

        $this->buildLayout('publisher-schedule');

        $days    = Lang::get('dates.days');
        $hours   = range(1, 12);
        $minutes = range(0, 59);

        $timezone_selector = TimezoneSelector::instance('timezone', 'account-timezone', 'input-large');
        $timezone_selector->fillForUser($this->logged_in_customer);
        $timezone_selector->setShowLocalTime(true);
        $timezone_selector->setSuccessCallback('TimezoneHandler.updateLocation');

        $timeslots = $this->active_user_account->getTimeSlots('auto')
            ->sortBy('day_preference', SORT_ASC, 'time_preference', SORT_ASC)
            ->getModels();

        $daily_counts = array_fill_keys($days, 0);
        foreach ($timeslots as $timeslot) {
            $daily_counts[$timeslot->getPrettyDay()]++;
        }

        $admin_user = $this->logged_in_customer->hasFeature('pin_scheduling_admin');

        $suggested_times = array();
        if ($admin_user) {
            $peak_days_times_data = $this->active_user_account->profile()->getPeakDaysAndTimesData();

            // Sort pin data by repins per pin so that we can suggest some viral times.
            $repins_per_pin = array();
            foreach ($peak_days_times_data['pin_counter'] as $period => $pin_data) {
                $repins_per_pin[$period] = $pin_data['repins_per_pin'];
            }

            $pin_counter_by_repins = $peak_days_times_data['pin_counter'];
            array_multisort($repins_per_pin, SORT_DESC, $pin_counter_by_repins);

            foreach (array_slice($pin_counter_by_repins, 0, 5) as $period => $pin_data) {
                list($day, $time) = explode('-', $period);

                if (strlen($time) == 1) {
                    $time = '0' . $time;
                }
                $time .= '00';

                // Adjust day and time for user's timezone.
                $date = Carbon::createFromTimestamp(
                    strtotime($days[$day] . ' ' . $time),
                    $this->logged_in_customer->getTimezone()
                );

                $day         = $date->format('w');
                $time        = $date->format('Hi');
                $pretty_day  = $days[$day];
                $pretty_time = $date->format('g:i A');

                $suggested_times[] = array(
                    'day'            => $day,
                    'pretty_day'     => $pretty_day,
                    'time'           => $time,
                    'pretty_time'    => $pretty_time,
                    'repins_per_pin' => $pin_data['repins_per_pin'],
                );
            }
        }

        $this->mainContent('analytics.pages.publisher.schedule', array(
            'navigation'        => $this->buildNavigation('schedule'),
            'timeslots'         => $timeslots,
            'days'              => $days,
            'hours'             => $hours,
            'minutes'           => $minutes,
            'timezone_selector' => $timezone_selector->render(),
            'user_timezone'     => $this->logged_in_customer->getTimezone(),
            'daily_counts'      => $daily_counts,
            'suggested_times'   => $suggested_times,
            'admin_user'        => $admin_user,
        ));
    }

    /**
     * GET /publisher/post/new/upload
     * @author  Will
     */
    public function showUploadPost()
    {
        $this->mainContent('analytics.pages.publisher.upload');
    }

    /**
     * POST /publisher/post/upload
     */
    public function processUpload()
    {
        if (!$this->logged_in_customer->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        $path = storage_path() . '/uploaded_posts/';

        if (!file_exists($path)) {
            mkdir($path);
        }

        $site_url = $this->active_user_account->mainDomain()->domain;
        if (!empty($site_url)) {
            $site_url = 'http://' . $site_url;
        }

        $posts = array();

        /** @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
        foreach (Input::file() as $file) {

            if ($file == NULL) {
                continue;
            }

            $validator = Validator::make(
                array('uploaded_file' => $file),
                array('uploaded_file' => 'image')
            );

            if ($validator->fails()) {
                continue;
            }

            $new_name = uniqid() . '_' . $file->getFilename();

            $file->move($path, $new_name);

            $post = UploadedPost::create(
                $this->active_user_account,
                $path . $new_name
            );

            $posts[] = array(
                'site-url'    => $site_url,
                'image-url'   => $post->getUrl(),
                'description' => 'Uploaded via @tailwind',
            );

        }

        if (count($posts) == 0) {
            Redirect::back()->with('flash_error', 'No images included');
        }

        Post::storeDrafts(array('items' => $posts));

        return Redirect::route('publisher-new-post');
    }

    /**
     * @author  Will
     *
     * @param $uploaded_post_id
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function showUploadedPost($uploaded_post_id)
    {
        $post = UploadedPost::find($uploaded_post_id);

        return Response::download($post->location);
    }

    /**
     * Displays information for the bookmarklet and extensions.
     *
     * @return void
     */
    public function tools()
    {
        Log::setLog(__FILE__, 'Publisher', 'Publisher_Tools');

        $user = $this->logged_in_customer;
        if (!$user || !$user->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        $this->buildLayout('publisher-tools');

        $this->layout->main_content = View::make('analytics.pages.publisher.tools', array(
            'navigation' => $this->buildNavigation('tools'),
        ));
    }

    /**
     * Displays the publisher permissions page where an admin may update users' roles and
     * permissions.
     *
     * @author Janell
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function permissions()
    {
        Log::setLog(__FILE__, 'Publisher', 'Publisher_Permissions');

        $user = $this->logged_in_customer;
        if (!$user || !$user->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        if (!$user->hasFeature('pin_scheduling_admin')) {
            return Redirect::route('publisher');
        }

        $users = $this->active_user_account->organization()->users()->active();

        $this->buildLayout('publisher-permissions');

        $this->layout->main_content = View::make('analytics.pages.publisher.permissions', array(
            'navigation'   => $this->buildNavigation('permissions'),
            'current_user' => $this->logged_in_customer,
            'users'        => $users,
        ));
    }

    /**
     * Updates publisher permissions for this organization's users.
     *
     * @author Janell
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePermissions()
    {
        $user = $this->logged_in_customer;
        if (!$user || !$user->hasFeature('pin_scheduling_enabled')) {
            return Redirect::to('/');
        }

        if (!$user->hasFeature('pin_scheduling_admin')) {
            return Redirect::route('publisher');
        }

        $user_roles = Input::get('user_roles', array());

        $users = $this->active_user_account->organization()->users();
        $approval_queue = 0;
        foreach ($users as $user) {
            if (isset($user_roles[$user->cust_id]) && $user_roles[$user->cust_id] == 'admin') {
                $user->enableFeature('pin_scheduling_admin');
            } else {
                $user->disableFeature('pin_scheduling_admin');
                $approval_queue = 1;
            }
        }

        $this->active_user_account->organization()->editFeature(
            'pin_scheduling_approval_queue',
            $approval_queue
        );

        return Redirect::route('publisher-permissions')->with(
            'flash_message',
            'Your permissions have been updated!'
        );
    }

    /**
     * Fetches the user's post layout preference from session data.
     *
     * @author Janell
     *
     * @return string
     */
    protected static function getLayoutPreference()
    {
        return Session::get(self::POST_LAYOUT_SESSION_KEY, 'feed');
    }

    /**
     * Saves a user's post layout preference to their session.
     *
     * @author Janell
     *
     * @param string $layout
     *
     * @return void
     */
    protected static function saveLayoutPreference($layout)
    {
        Session::put(self::POST_LAYOUT_SESSION_KEY, $layout);
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
        $admin_user = $this->logged_in_customer->hasFeature('pin_scheduling_admin');

        $queued_timeslots = array();
        if ($page == 'publisher-posts-scheduled' && $admin_user) {
            $posts = $this->active_user_account->getPosts()->queued()->sortByTimeSlots();

            foreach ($posts as $post) {
                if ($post->time_slot_type == 'manual') {
                    continue;
                }

                $queued_timeslots[] = $post->time_slot_timestamp;
            }
        }

        $this->layout->body_id                  = 'publisher';
        $this->layout_defaults['page']          = 'Publisher';
        $this->layout_defaults['top_nav_title'] = 'Publisher';
        $this->layout->head                    .= View::make('analytics.components.head.publisher');
        $this->layout->top_navigation           = $this->buildTopNavigation();
        $this->layout->side_navigation          = $this->buildSideNavigation($page);
        $this->layout->pre_body_close          .= View::make('analytics.components.pre_body_close.publisher', array(
            'page'             => $page,
            'boards'           => $this->active_user_account->profile()->getActiveDBBoards()->sortBy('name'),
            'admin_user'       => $admin_user,
            'user_timezone'    => $this->logged_in_customer->getTimezone(),
            'queued_timeslots' => $queued_timeslots,
        ));
    }

    /**
     * Builds the top navigation.
     *
     * @param string $page
     * @param string $view
     *
     * @return View
     */
    protected function buildNavigation($page, $view = null)
    {
        $scheduled_count = $this->active_user_account->getScheduledPostsCount();
        $pending_count   = $this->active_user_account->getPendingPostsCount();
        $drafts_count    = Post::getDraftsCount();
        $approval_queue  = $this->active_user_account->organization()->hasFeature('pin_scheduling_approval_queue');
        $admin_user      = $this->logged_in_customer->hasFeature('pin_scheduling_admin');

        return View::make('analytics.components.publisher.nav', array(
            'page'            => $page,
            'view'            => $view,
            'scheduled_count' => $scheduled_count,
            'drafts_count'    => $drafts_count,
            'pending_count'   => $pending_count,
            'approval_queue'  => $approval_queue,
            'admin_user'      => $admin_user,
        ));
    }

    /**
     * Builds the drafts view of the posts page.
     *
     * @author Janell
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function viewDrafts()
    {
        $auto_time_slots = $this->active_user_account->getTimeSlots('auto')->sortByUpcoming();

        if ($auto_time_slots->isEmpty()) {
            return Redirect::route('publisher-schedule')->with(
                'flash_message',
                'Add at least one scheduled time, then click Drafts to schedule your posts!'
            );
        }

        $drafts                  = Post::getDrafts();
        $next_available_timeslot = $this->active_user_account->getNextAvailableTimeSlot();
        $upcoming_auto_posts     = $this->active_user_account->getUpcomingAutomaticPosts()->sortByTimeSlots();
        $upcoming_timeslots      = array();
        $user_timezone           = $this->logged_in_customer->getTimezone();

        if ($auto_time_slots->count() > 1) {
            // Advance our auto time slots collection to the next available time.
            while (object_get($auto_time_slots->current(), 'id') !== $next_available_timeslot->id) {
                $auto_time_slots->next();
            }
        }

        // Determine how many weeks are between now and the next available scheduled time.
        $week_count = 0;
        if ($auto_time_slots->count() == 1) {
            $week_count = $upcoming_auto_posts->count();
        } else if ($upcoming_auto_posts->isNotEmpty()) {
            $today = new DateTime();
            $last_post_time = new DateTime();
            $last_post_time
                ->setTimestamp($upcoming_auto_posts->last()->time_slot_timestamp)
                ->setTimezone($user_timezone);

            $week_count = floor($today->diff($last_post_time)->days/7);
        }

        // Get timestamps for each upcoming time slot to give context when scheduling.
        for ($i = 0; $i < $drafts->count(); $i++) {
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

            $upcoming_timeslots[strtotime($relative_time)] = $time_slot;

            // If at the end of this week's time slots, reset the collection and add another week.
            if ($auto_time_slots->next() == false) {
                $auto_time_slots->rewind();
                $week_count++;
            }
        }

        $edit_post_modal = EditPostModal::instance(
            $this->logged_in_customer,
            $this->active_user_account,
            'draft'
        );

        $this->layout->main_content = View::make('analytics.pages.publisher.drafts', array(
            'navigation'         => $this->buildNavigation('posts', 'drafts'),
            'drafts'             => $drafts,
            'edit_post_modal'    => $edit_post_modal->render(),
            'upcoming_timeslots' => $upcoming_timeslots,
            'user_timezone'      => $user_timezone,
            'user_accounts'      => $this->logged_in_customer->organization()->connectedUserAccounts('active'),
            'current_account'    => $this->active_user_account,
            'hours'              => range(1, 12),
            'minutes'            => range(0, 59),
            'hour'               => date('g', time()),
            'minute'             => date('i', time()),
            'date'               => date('Y-m-d', time()),
            'am_pm'              => date('A', time()),
            'admin_user'         => $this->logged_in_customer->hasFeature('pin_scheduling_admin'),
        ));
    }

    /**
     * Builds the pending view of the posts page. Pending posts are those that are waiting to be
     * approved by an admin.
     *
     * @author Janell
     *
     * @param string $layout
     *
     * @return void
     */
    protected function viewPendingPosts($layout)
    {
        $posts         = $this->active_user_account->getPosts()->sortByTimeSlots()->awaitingApproval();
        $user_timezone = $this->logged_in_customer->getTimezone();

        $view_vars = array(
            'navigation'    => $this->buildNavigation('posts', 'pending'),
            'view'          => 'pending',
            'layout'        => $layout,
            'posts'         => $posts,
            'user_timezone' => $user_timezone,
            'boards'        => $this->active_user_account->profile()->getActiveDBBoards()->sortBy('name'),
            'hours'         => range(1, 12),
            'minutes'       => range(0, 59),
            'date'          => date('Y-m-d', time()),
            'hour'          => date('g', time()),
            'minute'        => date('i', time()),
            'am_pm'         => date('A', time()),
            'admin_user'    => $this->logged_in_customer->hasFeature('pin_scheduling_admin'),
        );

        if ($layout == 'list') {
            // Organize posts into groups by date.
            $post_group_names = array(
                'today'     => 'Today',
                'tomorrow'  => 'Tomorrow',
                'this-week' => 'This Week',
                'next-week' => 'Next Week',
                'later'     => 'Later',
            );

            $post_groups = array_fill_keys(array_keys($post_group_names), array());

            $end_of_week   = Carbon::create()->setTimezone($user_timezone)->endOfWeek();
            $start_of_week = Carbon::create()->setTimezone($user_timezone)->startOfWeek();

            $start_of_next_week = Carbon::create()->setTimezone($user_timezone)->addDays(7)->startOfWeek();
            $end_of_next_week   = Carbon::create()->setTimezone($user_timezone)->addDays(7)->endOfWeek();

            foreach ($posts as $post) {
                $post_date = Carbon::createFromTimestamp($post->time_slot_timestamp, $user_timezone);

                if ($post_date->isToday()) {
                    $post_groups['today'][] = $post;
                } elseif ($post_date->isTomorrow()) {
                    $post_groups['tomorrow'][] = $post;
                } elseif ($post_date->between($start_of_week, $end_of_week, false)) {
                    $post_groups['this-week'][] = $post;
                } elseif ($post_date->between($start_of_next_week, $end_of_next_week, false)) {
                    $post_groups['next-week'][] = $post;
                } elseif ($post_date->gte($end_of_next_week)) {
                    $post_groups['later'][] = $post;
                }
            }

            $view_vars['post_group_names'] = $post_group_names;
            $view_vars['post_groups']      = $post_groups;
        }

        $this->layout->main_content = View::make('analytics.pages.publisher.posts', $view_vars);
    }

    /**
     * Builds the scheduled view of the posts page.
     *
     * @author Janell
     *
     * @param string $layout
     *
     * @return void
     */
    protected function viewScheduledPosts($layout)
    {
        $posts         = $this->active_user_account->getPosts()->queued()->sortByTimeSlots();
        $user_timezone = $this->logged_in_customer->getTimezone();

        $view_vars = array(
            'navigation'    => $this->buildNavigation('posts', 'scheduled'),
            'view'          => 'scheduled',
            'layout'        => $layout,
            'posts'         => $posts,
            'user_timezone' => $user_timezone,
            'hours'         => range(1, 12),
            'minutes'       => range(0, 59),
            'date'          => date('Y-m-d', time()),
            'hour'          => date('g', time()),
            'minute'        => date('i', time()),
            'am_pm'         => date('A', time()),
            'admin_user'    => $this->logged_in_customer->hasFeature('pin_scheduling_admin'),
        );

        if ($layout == 'list') {
            // Organize posts into groups by date.
            $post_group_names = array(
                'today'     => 'Today',
                'tomorrow'  => 'Tomorrow',
                'this-week' => 'This Week',
                'next-week' => 'Next Week',
                'later'     => 'Later',
            );

            $post_groups = array_fill_keys(array_keys($post_group_names), array());

            $end_of_week   = Carbon::create()->setTimezone($user_timezone)->endOfWeek();
            $start_of_week = Carbon::create()->setTimezone($user_timezone)->startOfWeek();

            $start_of_next_week = Carbon::create()->setTimezone($user_timezone)->addDays(7)->startOfWeek();
            $end_of_next_week   = Carbon::create()->setTimezone($user_timezone)->addDays(7)->endOfWeek();

            foreach ($posts as $post) {
                $post_date = Carbon::createFromTimestamp($post->time_slot_timestamp, $user_timezone);

                if ($post_date->isToday()) {
                    $post_groups['today'][] = $post;
                } elseif ($post_date->isTomorrow()) {
                    $post_groups['tomorrow'][] = $post;
                } elseif ($post_date->between($start_of_week, $end_of_week, false)) {
                    $post_groups['this-week'][] = $post;
                } elseif ($post_date->between($start_of_next_week, $end_of_next_week, false)) {
                    $post_groups['next-week'][] = $post;
                } elseif ($post_date->gte($end_of_next_week)) {
                    $post_groups['later'][] = $post;
                }
            }

            $view_vars['post_group_names'] = $post_group_names;
            $view_vars['post_groups']      = $post_groups;
        }

        $this->layout->main_content = View::make('analytics.pages.publisher.posts', $view_vars);
    }

    /**
     * Builds the published view of the posts page.
     *
     * @author Janell
     *
     * @param string $layout
     *
     * @return void
     */
    protected function viewPublishedPosts($layout)
    {
        $posts         = $this->active_user_account->getPosts(true)->sortByTimeSent('desc');
        $user_timezone = $this->logged_in_customer->getTimezone();

        $view_vars = array(
            'navigation'    => $this->buildNavigation('posts', 'published'),
            'view'          => 'published',
            'layout'        => $layout,
            'posts'         => $posts,
            'user_timezone' => $user_timezone,
        );

        if ($layout == 'list') {
            // Organize posts into groups by date.
            $post_group_names = array(
                'today'     => 'Today',
                'yesterday' => 'Yesterday',
                'this-week' => 'This Week',
                'last-week' => 'Last Week',
                'earlier'   => 'Earlier',
            );

            $post_groups = array_fill_keys(array_keys($post_group_names), array());

            $end_of_week   = Carbon::create()->setTimezone($user_timezone)->endOfWeek();
            $start_of_week = Carbon::create()->setTimezone($user_timezone)->startOfWeek();

            $start_of_last_week = Carbon::create()->setTimezone($user_timezone)->subDays(7)->startOfWeek();
            $end_of_last_week   = Carbon::create()->setTimezone($user_timezone)->subDays(7)->endOfWeek();

            foreach ($posts as $post) {
                $post_date = Carbon::createFromTimestamp($post->sent_at, $user_timezone);

                if ($post_date->isToday()) {
                    $post_groups['today'][] = $post;
                } elseif ($post_date->isYesterday()) {
                    $post_groups['yesterday'][] = $post;
                } elseif ($post_date->between($start_of_week, $end_of_week, false)) {
                    $post_groups['this-week'][] = $post;
                } elseif ($post_date->between($start_of_last_week, $end_of_last_week, false)) {
                    $post_groups['last-week'][] = $post;
                } else {
                    $post_groups['earlier'][] = $post;
                }
            }

            $view_vars['post_group_names'] = $post_group_names;
            $view_vars['post_groups']      = $post_groups;
        }

        $this->layout->main_content = View::make('analytics.pages.publisher.posts', $view_vars);
    }
}