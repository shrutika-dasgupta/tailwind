<?php

/*
 *
 * get number of users the org has
 * check max users
 * see how many users they have left
 *
 *
 */



?>
<div class="row-fluid <?=$user_row_enabled;?>">
    <div class='span12'>

        <div class="user-row add-user-row">
            <strong>+ Invite a Collaborator</strong>
        </div>

        <div class="hidden user-row add-user-form">

            <div class="add-user-form-title">
                Invite a Collaborator &nbsp;
            </div>

            <form method="POST" action="/v2/controllers/editUsers.php?action=add">

                <span class="span7">
                    <input class="span3" type="text" name="first_name" placeholder="First Name"
                           pattern='.{2,}'
                           oninvalid="setCustomValidity('Please enter a name so we know who to address your invitation to!')"
                           onchange="try{setCustomValidity('')}catch(e){}" required/>

                    <input class="span4" type="text" name="last_name" placeholder="Last Name" />

                    <input class="span5" type="email" name="email" placeholder="Email"
                           pattern='.{6,}'
                           oninvalid="setCustomValidity('Please enter a valid email address!')"
                           onchange="try{setCustomValidity('')}catch(e){}" required/>
                </span>

                <span class="span5">
                    <span class="btn-group" data-toggle="buttons-radio">
                        <button id="admin-button" type="button" class="btn" type="radio" name="is_admin" value="A"
                                data-toggle="popover" data-container="body" data-placement="left"
                                data-content="Admin Collaborators have <strong>FULL privileges</strong> on your
                                dashboard."
                                onclick="$('#viewer-button').attr('selected','selected'); confirm('Are you sure you want to add an Admin Collaborator?  ' +
                                 'Admin Collaborators will have FULL privileges on your account, ' +
                                 'including the ability to change your plan and adjust your billing details.');
                                 $('#admin-button').attr('selected','selected');">
                            Admin
                        </button>
                        <button id="viewer-button" type="button" class="btn active" type="radio" name="is_admin" value="V">Viewer</button>
                    </span>
                    <input type="hidden" name="is_admin" id="admin_hidden"/>
                    <span class="span6 pull-right">
                        <button type="submit"
                                value="Add user"
                                class="btn btn-success"
                                onclick="$('#admin_hidden').val($('.active[name=is_admin]').val()); <?=$multi_account_confirm;?>">
                            <i class="icon-paperplane"></i>&nbsp; Invite Collaborator
                        </button>
                    </span>
                </span>

            </form>
        </div>

    </div>

    <div class="span10 add-user-alert pull-right muted" style="display:none;">
        <em>When you invite a Collaborator, they'll receive an email containing their new Tailwind account
            details along with instructions on how to access the dashboard.</em>
    </div>
</div>