<?php
/**
 * @author Alex
 * Date: 2/17/14 6:27 PM
 * 
 */

    if ($follower_location_city != "" && $follower_location_state != "") {
        $location = "<i class='icon-location'></i> " . $follower_location_city . ", " . $follower_location_state . " ";
    } elseif ($follower_location_city != "" && $follower_location_state == "") {
        $location = "<i class='icon-location'></i> " . $follower_location_city . " ";
    } else {
        $location = "&nbsp;";
    }

    $social = ($twitter != "" ? "<a target=_blank href='$twitter' class='social-icons twitter'> <i class='icon-twitter-2'></i> </a>" : " ");
    $social .= ($facebook != "" ? "<a target=_blank href='$facebook' class='social-icons facebook'> <i class='icon-facebook-2'></i> </a> " : " ");
    $social .= ($website != "" ? "<a target='_blank' href='http://$website' class='social-icons website'> <i class='icon-earth'></i>&nbsp;$website_print</a>" : " ");

?>


<div class='influencer-meta'>
    <div class='username'>
        <a target=_blank href="http://www.pinterest.com/<?= $username;?>"><strong><?=$display_name;?></strong></a>

        <?php if ($type == "domain-pinners" || $type == "most-valuable-pinners") { ?>
            <?php if ($username == $cust_username) { ?>
                <span class='badge badge-info'>You</span>
            <?php } ?>
        <?php } ?>

    </div>

    <?php
    if ($show_top_pinners_following) { ?>
        <div class='badges'>

        <?php
        if (($type == "domain-pinners" || $type == "top-repinners" || $type == "most-valuable-pinners")
        && $username != $cust_username) {
            if ($is_following != 0) { ?>
                <span class='badge badge-success'>Following You</span>
        <?php
            } else { ?>
                <div class='badge pull-right'>Not Yet Following You</div>
                <div class='clearfix'></div>
                <div class='pull-right' style='margin-top:5px'>
                <a target=_blank href="http://www.pinterest.com/<?=$username;?>">
                    <button data-toggle='popover'
                            data-original-title='<strong>Turn <?=$follower_display_name;?>
                            into a new follower!</strong>'
                            data-container='body'
                            data-content='<?=$follower_display_name;?>
                            has
                        <?php
                        if ($type == "domain-pinners") { ?>
                            <strong>pinned from <?=$cust_domain;?> <?=$pins;?> times</strong>
                        <?php
                        } else if ($type == "top-repinners") { ?>
                            <strong>repinned your pins <?=$repins;?> times</strong>
                        <?php
                        } else if ($type == "most-valuable-pinners") { ?>
                            <strong>helped drive traffic to you site</strong>
                        <?php
                        } ?>
                            but is not yet following you!
                            <br><br><strong>Repin or follow them</strong>
                            to show appreciation, let them know you are on Pinterest,
                            and turn them into a new follower!'
                            data-placement='bottom'
                            class='btn btn-mini'>
                        Go Engage →
                    </button>
                </a>
            </div>
        <?php }
        }
        if ($follower_username == $cust_username) { ?>
                <span class='badge badge-info'
                     data-toggle='popover'
                     data-container='body'
                     data-content='Pinning a lot from your own domain, huh?  Keep it up!'
                     data-placement='bottom'>This is You!
                </span>
        <?php
        } ?>
        </div>
    <?php
    } ?>


    <br />
    <div class='clearfix'></div>
    <div class='location'>

        <?=$location;?>

    </div>

    <br />
    <div class="pull-left" style="display:inline-block;">

        <?=$social;?>

    </div>
    <div class="dropdown pinner-connect pull-left">
        <button class="btn btn-mini dropdown-toggle pinner-connect-btn" data-toggle="dropdown">
            Connect <b class="caret"></b>
        </button>
        <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
            <li>
                <div class="pinner-social">
                    <a href="http://www.pinterest.com/<?= $username ?>" target="_blank" data-pin-do="buttonFollow" class="pinner-pn-<?= $type ?>">
                        Follow
                    </a>
            <span class="follower-count">
                <?= number_format($followers) ?> followers
            </span>
                </div>
            </li>
            <?php if (!empty($twitter)): ?>
                <li>
                    <div class="pinner-social" style="margin-top:8px;">
                        <a href="<?= $twitter ?>" class="twitter-follow-button pinner-tw" data-show-count="true" data-align="bottom" data-show-screen-name="false">
                            Follow
                        </a>
                    </div>
                </li>
            <?php endif ?>
            <?php if (!empty($facebook) && !is_numeric($facebook_username)): ?>
                <li>
                    <div class="pinner-social" style="margin-top:8px;">
                        <a href="<?= $facebook ?>" target="_blank" class="social-icons facebook pinner-fb">
                            <i class="icon-facebook-2"></i> &nbsp; <?= $facebook ?>
                        </a>
                    </div>
                </li>
            <?php endif ?>
            <?php if (!empty($website)): ?>
                <li>
                    <div class="pinner-social">
                        <a href="<?= $website ?>" target="_blank" class="social-icons pinner-website">
                            <i class='icon-earth'></i> &nbsp; <?= $website_print ?>
                        </a>
                    </div>
                </li>
            <?php endif ?>
        </ul>
    </div>

    <div class='clearfix'></div>
</div>
<?=$footprint;?>



