<?php namespace Admin;

use
    UserAccount,
    Pinleague\Pinterest,
    willwashburn\table;

/**
 * Class PublisherController
 *
 * @package Admin
 */
class PublisherController extends BaseController
{
    protected $layout = 'layouts.admin';

    public function getAuthStatus()
    {

        $DBH = \DatabaseInstance::DBO();

        $STH = $DBH->query('SELECT * FROM user_accounts WHERE account_id IN (SELECT DISTINCT account_id FROM publisher_posts) order by username asc');

        $table = new table();
        $table->striped()->bordered()->condensed();
        foreach ($STH->fetchAll() as $row) {

            /** @var UserAccount $user_account */
            $user_account = UserAccount::createFromDBData($row);
            $Pinterest    = new Pinterest();

            $Pinterest->setAccessToken($user_account->access_token);

            if ($Pinterest->accessTokenValid()) {
                $valid = '<span class="label label-success">Success</span>';
            } else {
                $valid = '<span class="label label-important">Failed</span>';
            }

            $table->addRow([
                           'name'  => $user_account->username,
                           'account_id' => $user_account->account_id,
                           'access_token' => $user_account->access_token,
                           'valid' => $valid
                           ]);

        }

        $this->layout->main_content = $table->render();

    }


}