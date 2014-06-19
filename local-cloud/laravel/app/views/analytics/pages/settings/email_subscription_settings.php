<fieldset>

    <legend>Newsletters and Product Updates</legend>

    <div class="row">
        <div class="span5">
            <label for="<?= $username; ?>-blog-rss">Tailwind's Weekly Newsletter</label>
            <p class="muted">
                <small>
                    Get the best social media news, delivered right to your inbox.
                </small>
            </p>
        </div>

        <div class="span2 pull-right align-right">
            <div class="make-switch switch-small">
                <input type="checkbox"
                       name="<?= $username; ?>-blog_rss"
                       id="<?= $username; ?>-blog-rss"
                       value="on"
                       <?= $blog_rss_checked; ?>
                />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="span5">
            <label for="<?= $username; ?>-intercom">Product Updates</label>
            <p class="muted">
                <small>
                    Receive product updates and tips about how to get the most out of your dashboard.
                </small>
            </p>
        </div>

        <div class="span2 pull-right align-right">
            <div class="make-switch switch-small">
                <input type="checkbox"
                       name="<?= $username; ?>-intercom"
                       id="<?= $username; ?>-intercom"
                       value="on"
                       <?= $intercom_checked; ?>
                />
            </div>
        </div>
    </div>

</fieldset>