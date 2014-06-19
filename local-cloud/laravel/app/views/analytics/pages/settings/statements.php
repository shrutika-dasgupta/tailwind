<fieldset>

    <legend>Billing</legend>

    <div class="row">
        <div class="span5">
            <label for="<?= $username; ?>-statement-monthly">Monthly Statement</label>

            <p class="muted">
                <small>
                    A PDF of your last month's Tailwind statement (for Credit Card customers only).
                </small>
            </p>
        </div>

        <div class="span2 pull-right align-right">
            <div class="make-switch switch-small">
                <input type="checkbox"
                       name="<?= $username; ?>-monthly_statement"
                       id="<?= $username; ?>-statement-monthly"
                       value="monthly"
                       <?= $monthly_statement_disabled; ?>
                       <?= $monthly_statement_checked; ?>
                />
            </div>
        </div>
    </div>

</fieldset>