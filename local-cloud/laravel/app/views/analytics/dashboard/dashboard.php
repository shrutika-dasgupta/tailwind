<div id="dashboard">
    <?php /*
    <div class="navbar navbar-listening">
        <div class="navbar-inner">
            <ul class="nav pull-right">
                <li class="dropdown">
                    <a data-toggle="dropdown"
                       class="dropdown-toggle dropdown-view-latest" href="#">
                        <strong>Last 7 Days</strong> <b class="caret"></b>
                    </a>

                    <ul class="dropdown-menu">
                        <li>
                            <a href="/dashboard/last-14-days">Last 14 days</a>
                        </li>
                        <li>
                            <a href="/dashboard/last-30-days">Last 30 days</a>
                        </li>
                        <li class="disabled">
                            <a href="/dashboard/all-time">All time</a>
                        </li>
                    </ul>
                </li>
            </ul>

        </div>
    </div>
 */ ?>

    <div class="row-fluid">
        <div class="span8">
            <?= $left_column; ?>
        </div>
        <div class="span4" id="rightcol">
            <?= $right_column; ?>
        </div>
    </div>
</div>
