<?php namespace Analytics;

use
    Exception,
    Input,
    Log,
    Pinleague\Pinterest,
    Redirect,
    UserAccount;

/**
 * Class AuthController
 *
 * @package Analytics
 */
class OAuthController extends BaseController
{
    /**
     * GET /oauth/pinterest/response
     *
     * This is the page where Pinterest will send a request to with a auth code
     * and a state parameter. We'll use these to get an access_token
     *
     */
    public function pinterestHandshake()
    {
        Log::setLog(false, 'API', 'Pinterest_OAuth');

        Log::debug('Endpoint hit', Input::all());

        $code  = Input::get('code');
        $state = Input::get('state');

        $pinterest = Pinterest::getInstance();

        try {
            Log::info('Exchanging code for token');
            $token = $pinterest->exchangeCodeForToken($code, $state);

            Log::info('Successfully got access token', $token);
            $state = UserAccount::decryptOAuthState($state);

            /** @var UserAccount $user_account */
            $user_account                   = UserAccount::find($state->account_id);
            $user_account->access_token     = $token['access_token'];
            $user_account->expires_at       = time() + $token['expires_at'];
            $user_account->token_type       = $token['token_type'];
            $user_account->token_authorized = $token['authorized'] ? 'authorized' : 'unauthorized';
            $user_account->token_scope      = $token['scope'];
            $user_account->insertUpdateDB();

            Log::info('Updated access token details in user account',$user_account);
            return Redirect::to($state->redirect)->with('flash_message','Success!');

        }

        catch (Exception $e) {
            Log::error($e);
            Redirect::to('/settings/accounts')->with('flash_error','There was an error connecting that account');
        }
    }
}
