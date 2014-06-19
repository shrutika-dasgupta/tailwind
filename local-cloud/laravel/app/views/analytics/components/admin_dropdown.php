<li class='admin-view-detail'>
    <button type='button' class='close admin-detail-close'>&times;</button>
    <strong>cust_id </strong> = <?= $cust_id; ?>
    <br><strong> account_id </strong> = <?= $account_id; ?>
    <br><strong> org_id </strong> = <?= $org_id; ?>
    <br><strong> plan </strong> = <?= $plan_id; ?>
    <br><strong> chargify_id </strong> = <?= $chargify_id; ?>
    <br><strong> type </strong> = <?= $account_type; ?>
    <br><strong> track </strong> = <?= $account_track_type; ?>
    <br><strong> domains </strong> = <?= $domains; ?>
    <br><strong> username </strong> = <?= $username; ?>
    <br><strong> user_id </strong> = <?= $user_id; ?>
    <br><strong> #competitors </strong> = <?= $competitors; ?>
    <br><strong> GA </strong> = <?= $has_ga; ?>
</li>

<li class='divider-vertical'></li>

<li>
    <button class='btn admin-detail-show'
            data-toggle='popover' data-container='body'
            data-placement='bottom'
            data-content='ADMIN: show current user details'>
        <i class='icon-plus'></i>
    </button>
</li>


<li class='divider-vertical'></li>

<li>

    <form id="admin-dropdown" style="margin-bottom: 0px;" action="/admin/switch" method="get">

    <select  style="margin: 5px 0 0 0"id="admin-drop" name="cust_id" onchange="$('#admin-dropdown').submit();">
        <option value="<?= $cust_id; ?>
        "><?= $username; ?> | <?= $cust_id; ?> | [<?= $org_id; ?>] Plan:<?= $plan_id; ?></option>
        <?php foreach ($accounts as $account) { ?>
            <option value="<?=$account->cust_id;?>"><?= $account->username;?> | <?=$account->cust_id;?> | [<?=$account->org_id;?>] Plan:<?= $account->plan;?></option>
        <?php } ?>
    </select>

    </form>

    <form  style="display: none;"
           id="admin-search" onsubmit="search(); return false;">

    <input
        style="margin:5px 0 0 0; "
        type="text"
        id="admin-select"
        />
    <button type="submit" onclick="

   search(); return false;

    ">Go!</button>
        </form>

</li>

<script>

    $(document).ready(function () {
        $('.admin-detail-show').click(function () {
            $('.admin-view-detail').fadeToggle();
            $('#admin-search').toggle();$('#admin-dropdown').toggle();
        });
        $('.admin-detail-close').click(function () {
            $('.admin-view-detail').hide();
        });
    });

    function search() {
        $.ajax({
            url: '/admin/search/?term='+$('#admin-select').val(),
            type: 'get',
            beforeSend:function() {
                $('#account-list').html('<img style="margin:0 auto; display: block;" src="/img/loading-small.gif" />');
                $('#account-list').show();
            },
            success: function(response){
                $('#account-list').html(response);
                $('#account-list').show();
            },
            error:function(response){
                alert('fail');
            }
        });
    }

</script>

<div id="account-list" style="display: none; background: none repeat scroll 0 0 #FFFFFF;
    border: 1px solid #CCCCCC;
    padding: 10px;
    position: absolute;
    top: 40px;
    z-index: 10;
    ">
</div>
