<?php

namespace API;

use Exception,
    Input,
    Log,
    Response,
    User;

/**
 * For actions concerning a user's history, hit up dis endpoint yo.
 * 
 * @author Will
 */
class UserHistoryController extends BaseController
{
    /**
     * POST /v1/user/history/record/event
     *
     * Records event data to a user's history.
     * 
     * @author Will
     * @author Daniel
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordEvent()
    {
        $event = filter_var(Input::get('event'), FILTER_SANITIZE_STRING);
        $data  = Input::get('data');

        array_walk($data, function (&$var) {
            $var = filter_var($var, FILTER_SANITIZE_STRING);
        });

        $user = User::getLoggedInUser();

        $recorded = false;

        try {
            $recorded = $user->recordEvent($event, $data);
        }
        catch (Exception $e) {
            Log::error($e);
        }

        return Response::json(array(
            'success' => $recorded,
        ));
    }
}