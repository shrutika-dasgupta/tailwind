<div class="alert alert-error">
    <a class="close" data-dismiss="alert" href="#">&times;</a>
    <i class="icon-warning"></i>
    This is an <strong>experimental</strong> content discovery engine that aggregates stories from across the web based on social signals.
    <br />Please note that you may come across some content that is irrelevant or inappropriate.
    You can help train the discovery engine by flagging any offensive material.
</div>

<form class="form-horizontal pull-left" id="form-content-search" action="<?= URL::route('content') ?>">
    <input class="input-xxlarge typeahead" id="input-search-js" name="query" type="text" value="<?= htmlentities($query) ?>" placeholder="Discover something great..." />
    <button class="btn btn-info hidden" type="submit">Search</button>
</form>

<div class="btn-group pull-right">
    <button class="btn js-btn-sort js-track-click" id="btn-sort-popular" type="button" data-sort-by="popularity" data-component="Top Nav" data-element="Most Popular Toggle">
        Most Popular
    </button>
    <button class="btn active js-btn-sort js-track-click" id="btn-sort-recent" type="button" data-sort-by="datetime" data-component="Top Nav" data-element="Most Recent Toggle">
        Most Recent
    </button>
    <button class="btn btn-success js-feedback" type="button"><i class="icon-megaphone"></i> Feedback</button>
</div>