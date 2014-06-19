<?php namespace Analytics\Api;

use Analytics\BaseController,
    Exception,
    Input,
    Log,
    Pinleague\Pinterest,
    Pinleague\Pinterest\PinterestException,
    Publisher\Post,
    Publisher\TimeSlot,
    Redirect,
    Request,
    Response,
    UserHistory;

/**
 * Handles Publisher API interactions.
 *
 * @package Analytics
 */
class PublisherController extends BaseController
{

    /**
     * The response we send back for each API request
     *
     * @var array
     */
    protected $response = array(
        'success' => false,
        'message' => 'The response failed'
    );

    /**
     * POST /publisher/post/create
     *
     * @expects
     *      image_url
     *      title
     *      description
     *      link
     *      hour
     *      schedule_type [ auto || manual ]
     *      day_time
     *      minutes
     *      am_pm
     *
     * @author  Yesh
     * @author  Will
     */
    public function createPost()
    {
        try {
            $post_data = Input::all();

            $post = Post::create($this->logged_in_customer, $post_data);

            $this->logged_in_customer->recordEvent(
                UserHistory::ADD_POST,
                array(
                    'post' => $post_data,
                )
            );

            $this->response['success'] = true;
            $this->response['message'] = 'The post was scheduled';
            $this->response['post_id'] = $post->id;

        }
        catch (Exception $e) {

            Log::error($e);
            $this->response['message'] = 'The post could not be scheduled';
        }

        if (Request::ajax()) {
            return Response::json($this->response);
        }

        return Redirect::route('publisher-posts');
    }

    /**
     * GET /publisher/post/{id}
     *
     * @author Will
     * @author Yesh
     *
     */
    public function getPost($id)
    {
        if (Request::ajax() == false) {
            Redirect::route('publisher-posts');
        }

        $post = Post::find($id);

        if($post) {
            $this->response['success'] = true;
            $this->response['message'] = 'Found post '.$id;
            $this->response['data'] = $post;
        }

        return Response::json($this->response);
    }

    /**
     * GET /publisher/post/{id}/delete
     * @author  Will
     * @author  Yesh
     *
     * @param $id
     *
     * @return bool
     */
    public function deletePost($id)
    {
        try {

            Post::delete($id);

            $this->logged_in_customer->recordEvent(
                UserHistory::REMOVE_POST,
                array(
                    'post_id' => $id,
                )
            );

            $this->response['success'] = true;
            $this->response['id']      = $id;
            $this->response['message'] = 'The post was deleted';

        }
        catch (Exception $e) {
            Log::error($e);
            $this->response['message'] = 'The post could not be deleted';
        }

        if (Request::ajax()) {
            return Response::json($this->response);
        }

        return Redirect::route('publisher-posts');

    }

    /**
     * GET /publisher/posts
     *
     * @author Will
     * @author Yesh
     *
     */
    public function getPosts()
    {

    }

    /**
     * GET /publisher/schedule
     *
     * @author Will
     * @author Yesh
     */
    public function getSchedule()
    {

    }

    /**
     * POST /publisher/time-slot/create
     *
     * @author Will
     * @author Yesh
     */
    public function createTimeSlot()
    {
        try {

            $time_slot = new TimeSlot();

            $time_slot->account_id = $this->active_user_account->account_id;
            $time_slot->setTime(Input::get('time'));
            $time_slot->setDay(Input::get('day'));
            $time_slot->timezone = $this->logged_in_customer->getTimezone();
            $time_slot->calculateSendTime();


            if ($time_slot->saveAsNew()) {
                $this->logged_in_customer->recordEvent(
                    UserHistory::ADD_TIMESLOT,
                    array(
                        'time_slot' => $time_slot,
                    )
                );

                $this->response['success'] = true;
                $this->response['id']      = $time_slot->id;
                $this->response['message'] = 'The timeslot was added successfully';
            }

        }
        catch (Exception $e) {
            Log::error($e);
            $this->response['message'] = 'The timeslot could not be added';
        }

        if (Request::ajax()) {
            return Response::json($this->response);
        }

        return Redirect::route('publisher-schedule');
    }

    /**
     * GET /publisher/time-slot/{id}
     *
     * @param $id
     *
     * @author Will
     * @author Yesh
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function getTimeSlot($id)
    {

        if (Request::ajax() == false) {
            Redirect::route('publisher-schedule');
        }

        try {
            $time_slot = TimeSlot::find($id);


            $this->response['success'] = true;
            $this->response['message'] = 'The time slot was found';
            $this->response['data']    = $time_slot;
        }
        catch (Exception $e) {
            Log::error($e);
            $this->response['message'] = 'There was an error finding timeslot' .
                " with ID  $id";
        }

        return Response::json($this->response);
    }

    /**
     * GET /publisher/time-slot/{id}/delete
     *
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @author Will
     * @author Yesh
     */
    public function deleteTimeSlot($id)
    {
        try {

            TimeSlot::delete($id);

            $this->logged_in_customer->recordEvent(
                UserHistory::REMOVE_TIMESLOT,
                array(
                    'time_slot_id' => $id,
                )
            );

            $this->response['success'] = true;
            $this->response['id']      = $id;
            $this->response['message'] = 'The timeslot was deleted';

        }
        catch (Exception $e) {
            Log::error($e);
            $this->response['message'] = 'The timeslot could not be deleted';
        }

        if (Request::ajax()) {
            return Response::json($this->response);
        }

        return Redirect::route('publisher-schedule');
    }

    /**
     * POST /board/create
     * @author  Will
     *          Create a board on pinterest
     */
    public function createBoard() {

        $pinterest = Pinterest::getInstance();

        // TODO make sure there is an access token
        $pinterest->setAccessToken($this->active_user_account->access_token);

        $name        = Input::get('name');
        $description = Input::get('description');
        $category    = Input::get('category');
        $privacy     = Input::get('privacy');

        try {

            $board = $pinterest->putBoard(
                               $name,
                               $description,
                               $category,
                               $privacy
            );

            $board->user_id = $this->active_user_account->user_id;

            $board->insertUpdateDB();

            $this->response['success']  = true;
            $this->response['board_id'] = $board->board_id;
            $this->response['message']  = 'The board was created';

        }
        catch (PinterestException $e) {
            Log::error($e);
            $this->response['message'] = 'The board could not be created';
        }

        if (Request::ajax()) {
            return Response::json($this->response);
        }

        return Redirect::back();
    }
}



