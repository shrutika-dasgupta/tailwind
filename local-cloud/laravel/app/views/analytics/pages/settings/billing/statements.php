<?= $settings_navigation; ?>

<?= $billing_navigation; ?>

<h3>Statements</h3>

<?php if (empty($statements)): ?>
	No statements available.
	<?php return ?>
<?php endif ?>

<p>Recent billing statements are available as PDF downloads.</p>

<ul>
    <?php foreach ($statements->statement as $statement): ?>
        <?php // Hide statements prior to July's major billing changes. ?>
        <?php if (strtotime($statement->closed_at) < strtotime('07/22/2013')) continue ?>

        <li>
            <a href="/settings/billing/statement/<?= $statement->id ?>">
                <?php if (!empty($statement->closed_at)): ?>
                    <?php echo date('F j, Y', strtotime($statement->closed_at)) ?>
                <?php else: ?>
                    Current
                <?php endif ?>
            </a>
        </li>
    <?php endforeach ?>
</ul>

<br />
<small class="muted">
    Enterprise customers should contact <a href="mailto:help@tailwindapp.com">help@tailwindapp.com</a> for invoice statement requests.
</small>