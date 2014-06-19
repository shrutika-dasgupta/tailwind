<?php

namespace API;

use BaseController,
    Response;

class AuthController extends BaseController
{

    /**
     * POST /login
     *
     * @param email
     * @param password
     *
     * @return \Illuminate\Http\JsonResponse
     * @author  Will
     */
    public function processLogin()
    {
        $response = array(
            'status'  => true,
            'message' => 'welcome to the v1 api',
            'token'   => ''
        );

        return Response::json($response);
    }

    /**
     * POST /reset-password
     *
     * @param email
     * @param token
     *
     * @author   Will
     */
    public function processResetPassword()
    {

        $response = array(
            'status'  => true,
            'message' => 'welcome to the v1 api',
        );

        return Response::json($response);

    }

}