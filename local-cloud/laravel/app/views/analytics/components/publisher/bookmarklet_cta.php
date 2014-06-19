<div id="publisher-tools">
    <div class="hero-unit">
        <h2>Install the Publisher Chrome Extension!</h2>
        <p>Easily schedule and publish content to Pinterest from wherever you are on the web.</p>
        <p>
            <button class="btn btn-info js-chrome-ext-btn">Install Now</button>
        </p>
    </div>

    <ul class="thumbnails row-fluid">
        <li class="span3">
            <div class="thumbnail">
                <img src="/img/publisher/extension-toolbar.png" alt="Find images to pin on any web page." />
                <p>Find images on the current web page to pin.</p>
            </div>
        </li>
        <li class="span1"></li>
        <li class="span4">
            <div class="thumbnail">
                <img src="/img/publisher/extension-context.png" alt="Schedule any image on the web." />
                <p>Right-click on any image to schedule it with Publisher.</p>
            </div>
        </li>
        <li class="span1"></li>
        <li class="span3">
            <div class="thumbnail">
                <img src="/img/publisher/extension-pinterest.png" alt="Schedule repins from Pinterest.com." />
                <p>Schedule repins directly from Pinterest.com!</p>
            </div>
        </li>
    </ul>

    <div class="description">
        <button class="btn btn-large btn-info pull-right js-chrome-ext-btn">Install the Extension</button>
        <p>
            Scheduling content will take you to your dashboard where you can edit your drafts &mdash; pick boards, schedule
            times, and descriptions. Once you're happy with your drafts, add them to your queue!
            We'll take care of the rest!
        </p>
    </div>

    <div id="bookmarklet">
        <h4>Looking for the Publisher Bookmarklet?</h4>
        <p>Simply <strong>drag and drop</strong> this button into your bookmarks toolbar.</p>
        <a class = "btn btn-primary"
           href = "javascript:(function(){var date = new Date();var timestamp = date.getUTCMonth() + '-' + date.getUTCDate() + '-' + date.getUTCFullYear();var s = document.createElement('script');s.src = window.location.protocol + '//analytics.tailwindapp.com/js/publisher/bookmarklet.js?ts=' + timestamp;document.body.appendChild(s);})()"
            >
            Schedule It!
        </a>
    </div>
</div>

<script type="text/javascript">
    $(function() {
        if (typeof chrome !== "undefined") {
            $('.js-chrome-ext-btn').on('click', function () {
                chrome.webstore.install(
                    'https://chrome.google.com/webstore/detail/gkbhgdhhefdphpikedbinecandoigdel',
                    function () {
                        $('.js-chrome-ext-btn').attr('disabled', 'disabled').html('Installed');
                    }
                );
            });
        } else {
            $('.js-chrome-ext-btn').attr('disabled', 'disabled').html('Google Chrome Required');
        }
    });
</script>