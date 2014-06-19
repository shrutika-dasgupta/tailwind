<div class="row-fluid">
    <div class="">

        <div id="multi-account-user-warning" class="alert alert-info span12 <?=$multi_account_warning_class;?>"
             style="margin-bottom:0px;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><i class="icon-info-2"></i> Invited Collaborators will have access
                to all <?=$num_accounts;?> of your accounts</strong>.
            <br><br>If you need custom permissions for clients or co-workers,
            or need additional collaborator spots, please
            <a href="mailto:help@tailwindapp.com" target="_blank"
               style="text-decoration:underline;color:#4E7E98;"><strong>Contact Us</strong></a>
            and we'll help you make it happen :)
        </div>

        <div id="role-add-success" class="alert alert-success span8" style="margin-top:10px;display:none;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>Hooray!</strong>  Your invitation to <?=$updated_name;?> is on its way!
        </div>

        <div id="delete-user-success" class="alert alert-success span8" style="margin-top:10px;display:none;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            You've successfully removed <?=$updated_name;?> from your list of Collaborators.
        </div>

        <div id="role-update-success" class="alert alert-success span8" style="margin-top:10px;display:none;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>Success!</strong>  <?=$updated_name;?>'s role has been updated!
        </div>

        <div id="email-exists" class="alert alert-error span8" style="margin-top:10px;display:none;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>Whoops!</strong>  Looks like the person you are trying to invite (<?=$email_exists;?>) already
            has a Tailwind account!
        </div>

        <div id="role-update-error" class="alert alert-error span8" style="margin-top:10px;display:none;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>Whoops!</strong>  Something went wrong on our end.  Could you try again?
            If this problem keeps happening, please contact us and we'll
            get to the bottom of it for you.
        </div>

    </div>
</div>

<div class="row-fluid">
    <div class='span12'>

        <div id='user' class="row margin-fix user-row-header">
            <span class='span7'>
                <span class="span6">
                    Name
                </span>
                <span class="span6">
                    Email
                </span>
            </span>

            <span class='span5'>
                <span class='span3'>
                    Role
                    <a class='gauge-icon' style="font-size: 17px;" data-toggle='popover' data-container='body'
                       data-content="<h5><u>Admin</u></h5>
                                Admin Collaborators will have <strong>FULL privelages</strong> on your
                                dashboard, including the ability to:
                                <ul>
                                    <li>Change plans</li>
                                    <li>Change billing details</li>
                                    <li>Add extra Accounts, Competitors and Collaborators</li>
                                    <li>Adjust any other setting</li>
                                </ul>
                                <h5><u>Viewer</u></h5>
                                Viewer Collaborators can see your dashboard exactly like you do, but
                                DO NOT have privelages to make any of the changes listed above."
                       data-placement='bottom'>
                        <i id='gauge-icon' class='icon-help'></i>
                    </a>
                </span>
                <span class='span5'>
                    <span class="">Invitation</span>
                </span>

                <span class='span4 pull-right'>

                </span>
            </span>

        </div>

    </div>
</div>