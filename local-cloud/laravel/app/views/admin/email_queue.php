<div class="table-wrapper orders-table">
    <div class="row-fluid head">
        <div class="span12">
            <h4>Email Queue</h4>
            <h5><small>Emails from more than 3 days ago have been archived and are not in this list</small></h5>
        </div>
    </div>

    <div class="row-fluid filter-block">
        <div class="pull-right">
            <div class="btn-group pull-right">
                <a href="/email/queue/queued" class="btn glow left large">Queued</a>
                <a href="/email/queue/cancelled/" class="btn glow middle large">Cancelled</a>
                <a href="/email/queue/processing/" class="btn glow middle large">Processing</a>
                <a href="/email/queue/sent/" class="btn glow middle large">Sent</a>
                <a href="/email/queue/all" class="btn glow right large">All</a>

            </div>
            <input type="text" placeholder="Search for an customer or username"
                   class="search order-search"
                   style="width: 300px; margin-bottom: 15px; margin-right: 10px;"
                >
        </div>
    </div>

    <div class="row-fluid">
        <table class="table table-hover">
            <thead>
            <tr>
                <th class="span1">
                    ID
                </th>
                <th class="span2">
                    Customer
                </th>
                <th class="span3">
                    Email
                </th>
                <th class="span2">
                    <span class="line"></span>
                    Username
                </th>
                <th class="span3">
                    <span class="line"></span>
                   Preview
                </th>
                <th class="span1">
                    <span class="line"></span>
                    Status
                </th>
                <th class="span1">
                    <span class="line"></span>
                    Repeat
                </th>
                <th class="span3">
                    <span class="line"></span>
                    Send time
                </th>
                <th class="span1">
                    <span class="line"></span>
                    Action
                </th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <!-- row -->
            <?php foreach ($emails as $email) { ?>

                <tr>
                    <td>
                        <?= $email->id; ?>
                    </td>
                    <td>
                        <a href="/customer/<?= $email->cust_id; ?>/"><?= $email->customer; ?>
                            (<?= $email->cust_id; ?>)</a>
                    </td>
                    <td>
                        <?= $email->email; ?>
                    </td>
                    <td>
                        <a href="http://pinterest.com/<?= $email->username; ?>">
                            <i class="icon-pinterest"></i>
                        </a> &nbsp; <?= $email->username;?>
                    </td>
                    <td>
                       <a target="_blank" href="/email/preview/<?=$email->id;?>/"> <?= $email->name; ?></a>
                    </td>
                    <td>
                        <span
                            class="label label-<?= $email->label; ?>"><?= $email->status; ?></span>
                    </td>
                    <td>
                        <?= $email->repeat; ?>
                    </td>
                    <td>
                        <?= $email->send_at; ?>
                        <?php /*
                        <a style="float:right; " class="btn btn-danger btn-small" href="/email/delete/<?= $email->id;?>/"><i class="icon-trash"></i></a>
                        <? if($email->cancelable) { ?>
                            <a style="float:right; margin-right: 3px;" class="btn btn-small" href="/email/cancel/<?= $email->id;?>/"><i class="icon-minus-sign"></i></a>
                        <? } else { ?>
                        <a style="float:right; margin-right: 3px;" class="btn btn-info btn-small" href="/email/requeue/<?= $email->id;?>/"><i class="icon-mail-forward"></i></a>
                        <? } ?>
                        */?>
                        </td>
                    <td>
                        <a class ="btn btn-info btn-small" href="/email/preview/<?= $email->id;?>/send">Send Preview to <?= $preview_email;?></a>
                        <?= $email->action;?>

                    </td>
                </tr>
            <? } ?>

            </tbody>
        </table>
    </div>
</div>