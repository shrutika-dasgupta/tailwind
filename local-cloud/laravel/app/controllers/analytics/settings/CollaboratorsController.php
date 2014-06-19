<?php namespace Analytics\Settings;

use
    Exception,
    Input,
    Redirect,
    RequiredVariableException,
    User,
    UserAlreadyExistsException,
    UserHistory,
    View;

/**
 * Class CollaboratorsController
 *
 * @package Analytics\Settings
 */
class CollaboratorsController extends BaseController
{

    /**
     * /settings/collaborators/edit
     *
     * @author  Will
     * @author  Alex
     */
    public function edit()
    {
        try {
            $legacy = $this->baseLegacyVariables();
            extract($legacy);

            $user = $customer;

            if (!all_in_array($_POST, 'cust_id', 'is_admin')
            ) {
                throw new RequiredVariableException('Not all post variables sent');
            }

            $cust_id = filter_var($_POST['cust_id'], FILTER_SANITIZE_NUMBER_INT);
            $role    = filter_var($_POST['is_admin'], FILTER_SANITIZE_STRING);

            $user_to_edit = User::find($cust_id);

            /*
             * Only edit if the user is in the same org
             */
            if ($user_to_edit->org_id == $user->org_id) {

                $old_role = $user_to_edit->is_admin;
                $user_to_edit->is_admin = $role;
                $user_to_edit->insertUpdateDB();

                $name  = $user_to_edit->getName();
                $email = $user_to_edit->email;
                $id    = $user_to_edit->cust_id;

                $user->recordEvent(
                    UserHistory::UPDATE_COLLABORATOR_ROLE,
                        $parameters = array(
                            '$old_role' => $old_role,
                            '$new_role' => $role
                        ),
                        "Change role of $name ($email) [cust_id: $id] to $role"
                );

                $message = "<strong>Success!</strong>  $name's role has been updated!";

            } else {

                $message = 'unauthorized';

            }

            return Redirect::back()
                   ->with('flash_success', $message);

        }
        catch (Exception $e) {
            return Redirect::back()
                   ->with('flash_error', $e->getMessage());
        }

    }

    /**
     * Invites a user to join as a collaborator.
     *
     * @route settings/collaborators/invite
     *
     * @author Will
     * @author Daniel
     * 
     * @return void
     */
    public function invite()
    {
        $legacy = $this->baseLegacyVariables();
        extract($legacy);

        $admin = $customer;

        try {
            if (!all_in_array($_POST, 'email', 'first_name', 'last_name', 'is_admin')) {
                throw new RequiredVariableException('Post variables not all sent');
            }

            $email      = trim(filter_var(Input::get('email'), FILTER_SANITIZE_STRING));
            $first_name = filter_var(Input::get('first_name'), FILTER_SANITIZE_STRING);
            $last_name  = filter_var(Input::get('last_name'), FILTER_SANITIZE_STRING);
            $is_admin   = filter_var(Input::get('is_admin'), FILTER_SANITIZE_STRING);

            $password = random_string(8);

            try {
                $invitee = User::create(
                    $email,
                    $password,
                    false,
                    $admin->org_id,
                    false,
                    $first_name,
                    $last_name,
                    $is_admin,
                    'pending',
                    $admin->cust_id
                );
            }
            catch (UserAlreadyExistsException $e) {
                $existing_user = User::findByEmail($email);

                if ($existing_user->type != User::DELETED) {
                    throw new UserAlreadyExistsException("$email already exists!");
                }

                $existing_user->type       = User::PENDING;
                $existing_user->first_name = $first_name;
                $existing_user->last_name  = $last_name;
                $existing_user->is_admin   = $is_admin;
                $existing_user->org_id     = $admin->org_id;
                $existing_user->setPassword($password);
                $existing_user->insertUpdateDB();

                $invitee = $existing_user;
            }

            $invitee       = $invitee->setPassword($email, $password);
            $temporary_key = $invitee->createTemporaryKey();

            $invitee = User::find($invitee->cust_id);
            $token   = strlen($invitee->cust_id) . $invitee->cust_id . md5($email . $invitee->password . $temporary_key);

            $account  = $invitee->organization()->primaryAccount();
            $username = $account->username;
            $domain   = $account->mainDomain()->domain;

            $accept_invite_url  = url("/invitation/accept/$token?");
            $accept_invite_url .= http_build_query(array(
                'utm_source'   => 'collaborator',
                'utm_medium'   => 'email',
                'utm_campaign' => 'invitation',
                'utm_content'  => 'button',
            ));

            $email_vars = array(
                'first_name'        => $invitee->first_name,
                'sender_full_name'  => $admin->getName(),
                'username'          => $username,
                'domain'            => $domain,
                'accept_invite_url' => $accept_invite_url,
            );

            $email = \Pinleague\Email::instance();
            $email->subject($admin->getName() . ' has Invited You to their Tailwind Dashboard!');
            $email->body('collaborator_invite', $email_vars);
            $email->to($invitee);
            $email->replyTo($admin);

            if (!$email->send()) {
                return Redirect::back()->with('flash_error', 'Sorry, an error occurred. Please try again.');
            }

            $admin->recordEvent(
                UserHistory::INVITE_COLLABORATOR,
                array(
                    '$role'  => $is_admin,
                    '$email' => $email,
                ),
                "Add $email with role $is_admin"
            );

            return Redirect::back()->with('flash_success', "<strong>Hooray!</strong>  Your invitation to $invitee->first_name is on its way!");
        }
        catch (Exception $e) {
            return Redirect::back()->with('flash_error', $e->getMessage());
        }
    }

    /**
     * /settings/collaborators/remove
     *
     * @author  Will
     */
    public function remove()
    {
        try {
            $legacy = $this->baseLegacyVariables();
            extract($legacy);

            $user = $customer;

            if (!all_in_array($_POST, 'cust_id')
            ) {
                throw new RequiredVariableException('No cust_id post variable sent');
            }

            $user_id = filter_var($_POST['cust_id'], FILTER_SANITIZE_NUMBER_INT);

            $user_to_remove = User::find($user_id);


            /*
             * Cant remove yourself, or the super admin, yo
             */
            if ($user_to_remove->cust_id == $user->cust_id || $user_to_remove->is_admin == "S") {
                $message = 'cant_delete_self';
            } /*
                 * Only remove the use if they have the same org id as the user
                 * (and eventually if the user has > permissions that this user
                 * to avoid people screwing around
                 */
            elseif ($user_to_remove->org_id == $user->org_id) {

                $user_to_remove->type = 'deleted';
                $user_to_remove->saveToDB();

                $name  = $user_to_remove->getName();
                $email = $user_to_remove->email;
                $id    = $user_to_remove->cust_id;
                $role  = $user_to_remove->is_admin;

                $user->recordEvent(
                    UserHistory::REMOVE_COLLABORATOR,
                        $parameters = array(),
                    "Deleted $name ($email) [cust_id: $id]"
                );

                $message = "You've successfully removed $name from your list of Collaborators.";

            } else {
                $message = 'unauthorized';
            }

            return Redirect::back()
                   ->with('flash_message', $message)
                   ->with('name', $name);


        }
        catch (Exception $e) {
            return Redirect::back()
                   ->with('flash_error', $e->getMessage());
        }
    }

    /**
     * /settings/collaborators
     *
     * @author  Will
     * @author  Alex
     */
    public function show()
    {
        /*
         * Extracting these varibles bring them into scope.
         * This is hacky . We know.
         */
        $legacy = $this->baseLegacyVariables();
        extract($legacy);

        /*
         * Redirect to profile settings if user does not have permissions to see this page.
         */
        if ($customer->is_admin == User::PERMISSIONS_VIEWER) {
            return Redirect::to('/settings/profile');
        }

        /*
         * Copying in legacy code...
         */

        $user_counter = 0;
        $all_users    = array();
        $acc          = "select * from users a where org_id='$cust_org_id' and cust_id!=$cust_id and (type is NULL or type!='deleted')";
        $acc_res = mysql_query($acc, $conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $this_cust_id = $a['cust_id'];

            $all_users[$user_counter]['cust_id']    = $this_cust_id;
            $all_users[$user_counter]['email']      = $a['email'];
            $all_users[$user_counter]['first_name'] = $a['first_name'];
            $all_users[$user_counter]['last_name']  = $a['last_name'];
            $all_users[$user_counter]['verified']   = $a['verified'];
            $all_users[$user_counter]['org_id']     = $a['org_id'];
            $all_users[$user_counter]['is_admin']   = $a['is_admin'];
            $all_users[$user_counter]['type']       = $a['type'];
            $all_users[$user_counter]['timestamp']  = $a['timestamp'];

            $user_counter++;
        }

        $html = $this->buildSettingsNavigation('collaborators');


        /*
         * Create User Rows
         */
        $user_counter == 0;
        $collaborator_spots = max($customer->organization()->max_users, 5);
        $max_allowed_collaborators = $customer->maxAllowed('num_users');
        if ($max_allowed_collaborators == 0) {
            $collaborator_spots = 1;
        }


        for ($i = 0; $i < $collaborator_spots; $i++) {

            $vars = array(
                'updated_name'            => '',
                'action_alert'            => '',
                'email_exists'            => false,
                'user_row_enabled'        => '',
                'multi_account_confirm'   => '',
                'upgrade_threshold_class' => ''
            );


            if ($is_multi_account) {
                $vars['multi_account_warning_class'] = "";
                $vars['num_accounts']                = $cust_accounts_count;
                $vars['multi_account_confirm']       = "return confirm('Please note that collaborators will have access to all of your accounts.');";
            } else {
                $vars['multi_account_warning_class'] = "no-show";
                $vars['num_accounts']                = "";
                $vars['multi_account_confirm']       = "";
            }

            /*
             * Get the module header (notifications and column headers)
             */
            if ($i == 0) {

                $vars['collaborator_upgrade_alert'] = "";
                if ($max_allowed_collaborators == 0) {
                    $vars['collaborator_upgrade_alert'] = "
                        <div class='alert alert-info alert-block'>
                            Please upgrade your account in order to add collaborators!
                            <span class='pull-right' style='margin-top: -5px;'>
                                <a href='/upgrade?ref=collab_free'>
                                    <button class='btn'><i class='icon-arrow-right'></i> <strong>Upgrade Now</strong></button>
                                </a>
                            </span>
                        </div>";
                }

                $html .= View::make('analytics.pages.settings.invite_users.user_header', $vars);

                /*
                 * Show row for logged in user to see themselves
                 */

                $vars['user_counter'] = $user_counter;
                $vars['plan_id']      = $customer->plan()->plan_id;
                $vars['first_name']   = $cust_first_name;
                $vars['last_name']    = $cust_last_name;
                $vars['email']        = $cust_email;
                $vars['cust_id']      = $cust_id;
                $vars['status']       = $cust_type;
                $vars['timestamp']    = $cust_timestamp;
                $vars['delete_class'] = "";

                $vars['status']         = "This is You";
                $vars['status_class']   = "";
                $vars['existing_class'] = "user-row-you";
                $vars['invite_tooltip'] = "";
                $vars['delete_class']   = "no-show";

                if ($cust_is_admin == "A") {
                    $vars['is_admin'] = "Admin";
                } else if ($cust_is_admin == "Y") {
                    $vars['is_admin'] = "Admin";
                } else if ($cust_is_admin == "S") {
                    $vars['is_admin']            = false;
                    $vars['is_admin_alt_option'] = false;
                    $vars['is_admin_alt_name']   = false;
                    $vars['existing_class']      = "user-row-admin";
                    $vars['delete_class']        = "no-show";
                } else {
                    $vars['is_admin'] = "Viewer";
                }

                $html .= View::make('analytics.pages.settings.invite_users.existing_user', $vars);
            }


            /*
             * If a given collaborator row is higher than the max allowed for this organization,
             * then inactivate this row (will not be able to add and row will fade out).
             */
            if ($i < $max_allowed_collaborators) {
                $vars['user_row_enabled'] = "clickable";
            } else {
                $vars['user_row_enabled'] = "inactive remove-links";
            }


            /*
             * If this user exists, create a row and display their info
             * using the existing_user.php template
             */
            if (array_key_exists($i, $all_users)) {
                //show existing user's row

                $vars['user_counter'] = $user_counter;
                $vars['plan_id']      = $customer->plan()->plan_id;
                $vars['first_name']   = $all_users[$i]['first_name'];
                $vars['last_name']    = $all_users[$i]['last_name'];
                $vars['email']        = $all_users[$i]['email'];
                $vars['cust_id']      = $all_users[$i]['cust_id'];
                $vars['status']       = $all_users[$i]['type'];
                $vars['timestamp']    = $all_users[$i]['timestamp'];
                $vars['delete_class'] = "";


                if ($all_users[$i]['email'] == $cust_email) {
                    $vars['status']         = "You";
                    $vars['status_class']   = "label-info";
                    $vars['existing_class'] = "user-row-you";
                    $vars['invite_tooltip'] = "";
                } else if ($all_users[$i]['type'] == "pending") {
                    $vars['status']         = "Pending Invitation";
                    $vars['status_class']   = "label-warning";
                    $vars['existing_class'] = "user-row-pending";
                    $invite_sent_at         = date('m/d/Y g:ia', $all_users[$i]['timestamp']);
                    $vars['invite_tooltip'] = "data-toggle='popover'
                                                            data-container='body'
                                                            data-placement='top'
                                                            data-content='Invitation sent:<br>$invite_sent_at (CST)'";

                } else if ($all_users[$i]['type'] == "accepted") {
                    $vars['status']         = "Active";
                    $vars['status_class']   = "label-success";
                    $vars['existing_class'] = "user-row-active";
                    $active_since           = date('m/d/Y g:ia', $all_users[$i]['timestamp']);
                    $vars['invite_tooltip'] = "data-toggle='popover'
                                                            data-container='body'
                                                            data-placement='top'
                                                            data-content='Active since:<br>$active_since (CST)'";;

                }


                if ($all_users[$i]['is_admin'] == "A") {
                    $vars['is_admin']            = "Admin";
                    $vars['is_admin_alt_option'] = "V";
                    $vars['is_admin_alt_name']   = "Viewer";
                } else if ($all_users[$i]['is_admin'] == "V") {
                    $vars['is_admin']            = "Viewer";
                    $vars['is_admin_alt_option'] = "A";
                    $vars['is_admin_alt_name']   = "Admin";
                } else if ($all_users[$i]['is_admin'] == "Y") {
                    $vars['is_admin']            = "Admin";
                    $vars['is_admin_alt_option'] = "V";
                    $vars['is_admin_alt_name']   = "Viewer";
                } else if ($all_users[$i]['is_admin'] == "S") {
                    $vars['is_admin']            = false;
                    $vars['is_admin_alt_option'] = false;
                    $vars['is_admin_alt_name']   = false;
                    $vars['status']              = "";
                    $vars['status_class']        = "";
                    $vars['existing_class']      = "user-row-admin";
                    $vars['delete_class']        = "no-show";
                } else {
                    $vars['is_admin']            = "Viewer";
                    $vars['is_admin_alt_option'] = "A";
                    $vars['is_admin_alt_name']   = "Admin";
                }

                $html .= View::make('analytics.pages.settings.invite_users.existing_user', $vars);

            } else {
                /*
                 * Otherwise, show an empty row that the user will be able to click on to add another collaborator
                 * (if less than max allowed)
                 */
                $vars['user_counter'] = $user_counter;
                $html .= View::make('analytics.pages.settings.invite_users.add_user', $vars);
            }


            /*
             * If this is the second user, throw in a static row representing the threshold between the
             * Free & Light accounts (can add up to 2 collaborators),
             * and the Pro account (can add up to 5 collaborators).
             */
            if ($i == 1) {

                $vars['plan_id'] = $customer->organization()->plan()->plan_id;

                if ($customer->organization()->plan()->plan_id < 3) {
                    $vars['upgrade_active_class']   = "";
                    $vars['upgrade_inactive_class'] = "hidden";
                } else {
                    $vars['upgrade_active_class']   = "hidden";
                    $vars['upgrade_inactive_class'] = "";
                }

                $html .= View::make('analytics.pages.settings.invite_users.upgrade_threshold', $vars);
            }

            $user_counter++;
        }


        /*
         * At the end, insert the javascript necessary for the collaborators module
         */
        $html .= View::make('analytics.pages.settings.invite_users.invite_usersjs', $vars);

        $this->layout->main_content = $html;

    }

}