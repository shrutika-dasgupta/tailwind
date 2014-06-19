<?php
/*
* Logic on if/how to show account selector and "add account" button
* TODO: @Will move to controller
*/

if ($is_multi_account
    && $customer->plan()->plan_id > 1
) {


    /*
     * Logic to create appropriate link with correct parameters
     * TODO: @Will move to controller
     *
     *
     */
    $uri_pass_account = '';
    $uri_pass = "" . $_SERVER['REQUEST_URI'] . "";
    if (strpos($uri_pass, "?")) {
        $get_params = true;
        if (strpos($uri_pass, "&account=") != 0) {
            $uri_pass_account = substr($uri_pass, 0, strpos($uri_pass, "&account="));
        } elseif (strpos($uri_pass, "?account=") != 0) {
            $uri_pass_account = substr($uri_pass, 0, strpos($uri_pass, "?account="));
            $get_params       = false;
        } else {
            $uri_pass_account = $uri_pass;
        }
    } else {
        $get_params = false;
    }
    ?>


    <div style="margin:0 0 5px 5px;">
        <div>
            Change Account:
        </div>
        <select class='input-medium pull-left' name='admin_form'
                ONCHANGE='location = this.options[this.selectedIndex].value;'
                style='margin: 5px 5px 5px 0px;'>
            <option selected='selected' value=''><?php echo $cust_username; ?></option>
            ";

            <?php
            $multi_account_counter = 0;
            foreach ($cust_accounts as $aa) {

                if ($aa['username'] != '' && $aa['username'] != $cust_username) {
                    if ($get_params) {
                        print "<option value='" . $uri_pass_account . "&account=" . $multi_account_counter . "'>" . $aa['username'] . "</option>";
                    } else {
                        print "<option value='" . $uri_pass_account . "?account=" . $multi_account_counter . "'>" . $aa['username'] . "</option>";
                    }
                }

                $multi_account_counter++;
            }
            ?>

        </select>
        <?php if ($cust_is_admin != "V") { ?>
            <span id="" class="pull-left btn  <?= $nav_show_add_account; ?>"
                  style='margin: 5px 0px 5px 5px;padding-bottom:2px;'
                  data-toggle="popover"
                  data-container="body"
                  data-placement="right"
                  data-content="Add More Accounts">
                            <a id="nav-add-account"
                               class="" <?= $nav_add_account_link; ?>
                               style="margin-top:2px;"><i class="icon-plus"></i></a>
                        </span>
        <?php } ?>
    </div>
    <div class="clearfix"></div>
<?php } else { ?>


    <div id="nav-add-account-wrapper" style="margin:0 0 0px 5px;display:inline-block;">

                    <span id=""
                          class="pull-left btn <?= $nav_show_add_account; ?>"
                          style='margin:0px;'>
                        <a id="nav-add-account"
                            <?= $nav_add_account_link; ?>><i
                                class="icon-plus"></i> Add Account</a>
                    </span>

    </div>
    <div class="clearfix"></div>
    <script type="text/javascript">
        $(document).ready(function() {<?= $nav_add_account_jquery ?>});
    </script>
<?php } ?>