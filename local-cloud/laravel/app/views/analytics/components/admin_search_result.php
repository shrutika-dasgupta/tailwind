<a href="/admin/switch/?cust_id=<?= $customer->cust_id; ?>">
    <div class="row-fluid">
        <div class="span3">
            <img
                src="<?= $customer->organization()->primaryAccount()->profile()->image; ?>"/>
        </div>
        <div class="span9">
            <div class
                ></div><?= $customer->organization()->primaryAccount()->username; ?>
            (cust_id: <?= $customer->cust_id; ?>)
            (org_id: <?=$customer->org_id; ?>)
            (plan: <?=$customer->organization()->plan;?>)
        </div>
    </div>
    <div>
</a>

