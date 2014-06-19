<?php
    $anonymous = $anonymous ? true : false;
    $pinterest_username = !empty($profile->influencer_username) ? $profile->influencer_username : $profile->username;
    $facebook_username = !empty($profile->facebook) ? $profile->facebook : $profile->facebook_url;
    $twitter_username = !empty($profile->twitter) ? $profile->twitter : $profile->twitter_url;
    $website = !empty($profile->website) ? strip_tags($profile->website) : strip_tags($profile->website_url);
?>

<?php if ($anonymous): ?>
    <div class="pinner-profile">
        <img class="pull-left pinner-photo" src="http://passets-ak.pinterest.com/images/user/default_75.png" />

        <div class="pull-left pinner-info">
            <span class="pinner-name">Pinner</span>
        </div>
    </div>

    <?php return ?>
<?php endif ?>

<div class="pinner-profile" <?= ($commenter === true ? "style='border:none;'" : "") ?>>


    <a href="http://pinterest.com/<?= $pinterest_username ?>" target="_blank" class="<?= ($commenter === true ? "pull-right" : "pull-left") ?> pinner-profile-<?= $type ?>">
        <img class="pinner-photo" src="<?= $profile->image ?>" />
    </a>

    <div class="<?= ($commenter === true ? "pull-right" : "pull-left") ?> pinner-info" <?= ($commenter === true ? "style='text-align:right;margin-right:25px;'" : "") ?>>

        <a href="http://pinterest.com/<?= $pinterest_username ?>" target="_blank" class="pinner-name pinner-profile-<?= $type ?> <?= ($commenter === true ? 'pull-right" style="margin-left:10px;"' : '"') ?>>
            <?= $profile->first_name . ' ' . $profile->last_name ?>
        </a>
        <div class="dropdown pinner-connect">
            <button class="btn btn-mini dropdown-toggle track-click" data-toggle="dropdown" data-component="<?= $component ?>" data-element="Pinner Connect Button">
                Connect <b class="caret"></b>
            </button>
            <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                <li>
                    <div class="pinner-social">
                        <i class='icon-pinterest-2'></i> <a href="http://www.pinterest.com/<?= $pinterest_username ?>" target="_blank" data-pin-do="buttonFollow" class="pinner-pn-<?= $type ?>">
                            Follow
                        </a>
                        <span class="follower-count">
                            <?= number_format($profile->follower_count) ?> followers
                        </span>
                    </div>
                </li>
                <?php if (!empty($twitter_username)): ?>
                    <li>
                        <div class="pinner-social" style="margin-top:8px;">
                            <i class='icon-twitter-2'></i> <a href="https://twitter.com/<?= $twitter_username ?>" class="twitter-follow-button pinner-tw-<?= $type ?>" data-show-count="true" data-align="bottom" data-show-screen-name="false">
                                Follow
                            </a>
                        </div>
                    </li>
                <?php endif ?>
                <?php if (!empty($facebook_username) && !is_numeric($facebook_username)): ?>
                    <li>
                        <div class="pinner-social" style="margin-top:8px;">
                            <a href="http://facebook.com/<?= $facebook_username ?>" target="_blank" class="social-icons facebook pinner-fb-<?= $type ?>">
                                <i class="icon-facebook-2"></i> <?= $facebook_username ?>
                            </a>
                        </div>
                    </li>
                <?php endif ?>
                <?php if (!empty($website)): ?>
                    <li>
                        <div class="pinner-social">
                            <a href="<?= $website ?>" target="_blank" class="social-icons pinner-website">
                                <i class='icon-earth'></i>&nbsp;<?= $website ?>
                            </a>
                        </div>
                    </li>
                <?php endif ?>
                <?php if (!empty($profile->location)): ?>
                    <li>
                        <div class="pinner-location">
                            <i class="icon-location-2"></i> <?= $profile->location ?>
                        </div>
                    </li>
                <?php endif ?>
            </ul>
        </div>

        <div class="pinner-counts">
            <span>
                <i class="icon-users"></i> <?= number_format($profile->follower_count) ?> Followers
            </span>
            <span>
                <i class="icon-pin"></i> <?= number_format($profile->pin_count) ?> Pins
            </span>

        </div>
        <?php if(!empty($profile->reach)): ?>
            <div class="pinner-counts well" style="padding:5px; margin-top:15px;">
                <div class="">
                    <span class="label label-success" style="margin-right:0px;">
                        <?= number_format($profile->reach); ?>
                    </span> Potential Impressions generated

                    from
                    <span class="label" style="margin-right:0px;">
                        <?= number_format($profile->count); ?>
                    </span> Pins.
                </div>
            </div>
        <?php endif ?>
    </div>

</div>