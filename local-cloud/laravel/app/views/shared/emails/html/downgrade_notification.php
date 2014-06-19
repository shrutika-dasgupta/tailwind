<a href="mailto:<?= $customer_email ?>"><?= $customer_name ?></a> has downgraded from
<em><?= $old_plan_name ?></em> to <em><?= $new_plan_name ?></em> <strong>(Customer ID: <?= $cust_id ?>)</strong>
<br />
<br />
<strong>Org ID:</strong> <?= $org_id ?>
<br />
<strong>Chargify ID:</strong> <?= $chargify_id ?>
<br />
<strong>Signup Date:</strong> <?= $signup_date ?>
<br />
<strong>Trial Start:</strong> <?= $trial_start ?>
<br />
<strong>Trial End:</strong> <?= $trial_end ?>
<br />
<strong>Number of times billed:</strong> <?= $billed ?>
<br />
<strong>Total Revenue:</strong> <?= $revenue ?>
<br />
<br/>
<strong>Reason:</strong> <?= $reason ?>
<br />
<?= $reason_text ?>
<br />
<br />
<br />
<br />
<?= $fact ?>