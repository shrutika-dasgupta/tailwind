<?php

namespace API;

use BaseController,
    Response;

class ProfileController extends BaseController
{


    /**
     * GET /profile
     *
     * @param token
     *
     * @return \Illuminate\Http\JsonResponse
     * @author  Will
     */
    public function getProfile()
    {

        $profile = new \Profile();

        $response = array(
            'status'  => true,
            'message' => 'welcome to the v1 api',
            'data'    => $profile
        );

        return Response::json($response);
    }


}