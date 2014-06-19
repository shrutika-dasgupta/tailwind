<?php
/**
 * @author Alex
 * Date: 2/14/14 5:27 PM
 * 
 */
?>

<?php $popular_feeds_enabled = $customer->hasFeature('domain_popularfeeds') ?>

<div class="navbar navbar-listening">
    <div class="navbar-inner">
        <ul class="nav">

            <li<?= ($type == 'insights') ? ' class="active"' : '' ?>>
                <a href="<?= URL::route('domain-insights', array($query_string)) ?>">
                    <strong>Insights</strong>
                </a>
            </li>

            <?php $popular = in_array($type, array('most-repinned', 'most-liked', 'most-commented')) ? 'active ' : '' ?>
            <li class="active dropdown">
                <a href="javascript:void(0)" data-toggle="dropdown" class="dropdown-toggle track-click" data-component="Top Nav" data-element="Popular Feed Toggle">
                    <strong>Feeds</strong> <b class="caret"></b>
                </a>

                <ul class="dropdown-menu">
                        <li<?= ($type == 'trending') ? ' class="active"' : '' ?>>
                            <a href="<?= URL::route('domain-feed', array('trending', $query_string)) ?>">
                                Latest Pins
                            </a>
                        </li>
                    <?php if ($popular_feeds_enabled): ?>
                        <li<?= ($type == 'most-repinned') ? ' class="active"' : '' ?>>
                            <a href="<?= URL::route('domain-feed', array('most-repinned', $query_string)) ?>">Most Repinned</a>
                        </li>
                        <li<?= ($type == 'most-liked') ? ' class="active"' : '' ?>>
                            <a href="<?= URL::route('domain-feed', array('most-liked', $query_string)) ?>">Most Liked</a>
                        </li>
                        <li<?= ($type == 'most-commented') ? ' class="active"' : '' ?>>
                            <a href="<?= URL::route('domain-feed', array('most-commented', $query_string)) ?>">Most Commented</a>
                        </li>
                        <li class="active">
                            <a href="/pins/domain/trending">Trending Images</a>
                        </li>
                    <?php else: ?>
                        <li class="active">
                            <a href="/pins/domain/trending">Trending Images</a>
                        </li>
                        <li class="disabled"><a href="javascript:void(0)">Most Repinned</a></li>
                        <li class="disabled"><a href="javascript:void(0)">Most Liked</a></li>
                        <li class="disabled"><a href="javascript:void(0)">Most Commented</a></li>
                    <?php endif ?>
                </ul>
            </li>
        </ul>

    </div>
</div>