<div class="row-fluid">
    <div class=" span8">

        <h2 style="margin-bottom: 20px"><?= $name; ?> (<?= $cust_id; ?>)</h2>
    </div>
</div>

<a class="btn" href="/customer/<?= $cust_id; ?>/features">
    View Features
</a>
<a class="btn" href="/customer/<?= $cust_id; ?>/history">
    View History
</a>
<a class="btn" href="/org/<?= $org_id; ?>/">
    View Org
</a>
<?= $panel; ?>
