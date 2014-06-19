<?php
/**
 * Export a user's information to create an export dump
 *
 * @author  Will
 *
 * @example
 *          php export_user_data.php 1748
 */

ini_set('memory_limit', '900M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

Log::setLog(__FILE__, 'CLI');

$cust_id = $argv[1];

try {

    echo 'Beginning export...';
    $file_name = $cust_id . '.json';

    /** @var  $user User */
    $user = User::find($cust_id);

    $export = [
        //'user'          => $user->export(),
        //'organization'  => $user->organization()->export(),
        //'user_accounts' => $user->organization()->connectedUserAccounts()->export()
    ];

    echo 'Compiled user, organization, and user_accounts.';

    /** @var  $user_account UserAccount */
    foreach ($user->organization()->connectedUserAccounts() as $user_account) {
        echo 'Exporting '.$user_account->account_name;

        //$export[$user_account->username . ' profile']         = $user_account->profile()->export();
        //$export[$user_account->username . ' profile history'] = $user_account->profile()->getAllHistoryCalcs()->export();
        //$export[$user_account->username . ' pins']            = $user_account->profile()->getDBPins()->export();
        //$export[$user_account->username . ' boards']          = $user_account->profile()->getDBBoards()->export();

        /** @var $board Board */
        foreach ($user_account->profile()->getDBBoards() as $board) {
            echo 'Exporting board '.$board->board_id;
           $export[$user_account->username . ' board history '.$board->board_id] = $board->getAllHistoryCalcs()->export();
        }
    }
    echo 'Writing export to file';
    file_put_contents($file_name, json_encode($export));
    echo "Exported user data for $user->email to $file_name";

}
catch (Exception $e) {
    Log::error($e);
}


