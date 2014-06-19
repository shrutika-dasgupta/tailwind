<?php

/*
 * Row the holds an Existing user's info and status
 *
 * @author Alex
 */

?>
<div class="row-fluid <?=$user_row_enabled;?>">
    <div class='span12'>

        <div id='user_<?=$user_counter;?>' class="row margin-fix user-row existing-user-row <?=$existing_class;?>">
            <span class='span7'>
                <span class="span6 add-user-name">
                    <?=$first_name;?> <?=$last_name;?>
                </span>
                <span class="span6 add-user-email">
                    <?=$email;?>
                </span>
            </span>

            <span class='span5'>
                <span class='span3 add-user-type'>
                    <?php if($is_admin != false){ ?>
                        <form style="margin-bottom:0px;" method="POST" action="/v2/controllers/editUsers.php?action=edit">
                            <input type="hidden" name="cust_id" value="<?=$cust_id;?>">
                            <select style="margin-bottom:0px;" class="input-small" id="change-role" name="is_admin" onchange="this.form.submit();">
                                <option selected="selected"><?=$is_admin;?></option>
                                <option value="<?=$is_admin_alt_option;?>"><?=$is_admin_alt_name;?></option>
                            </select>
                        </form>
                    <?php } else { ?>
                        <span class="label label-info" style="margin-top:7px;">Super Admin</span>
                    <?php } ?>
                </span>
                <span class='span5 add-user-status'>
                    <span class="label <?=$status_class;?>" <?=$invite_tooltip;?>><?=$status;?></span>
                </span>

                <span class='span4 add-user-options pull-right'>
                    <span class='user-edit <?=$delete_class;?>'>
                        <form style="margin-bottom:0px;" method="POST" action="/v2/controllers/editUsers.php?action=delete">
                            <input type="hidden" name="cust_id" value="<?=$cust_id;?>">
                            <button style="margin-bottom:0px;" class="btn" type="submit" value="Delete user"
                                    onclick="return confirm('Are you sure you want to delete this Collaborator?' +
                                     'They will no longer be able to login and access this dashboard.');">
                                <i class='icon-trash'></i> Delete
                            </button>
                        </form>
                    </span>
                </span>
            </span>

        </div>

    </div>
</div>