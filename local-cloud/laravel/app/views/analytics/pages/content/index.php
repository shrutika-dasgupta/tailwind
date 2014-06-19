<?= View::make('analytics.pages.content.nav', array('query' => $query, 'topics' => $topics, 'user' => $user)) ?>

<div id="external-content">
    <?php foreach ($entries as $entry): ?>
        <?= View::make(
            'analytics.pages.content.entry',
            array(
                'entry'        => $entry,
                'posts'        => $posts,
                'user'         => $user,
                'can_schedule' => $can_schedule,
                'can_flag'     => $can_flag,
            )
        ) ?>
    <?php endforeach ?>
</div>

<div class="pagination-loading hidden">
    <img src="http://i.imgur.com/qkKy8.gif" />
    <p>Finding More Content</p>
</div>

<script type="text/javascript">
var appView = "Content Discovery Feed";

var $isoContainer = $('#external-content');

var Content = {
    page: 1,
    canPaginate: true,

    /**
     * Gets the next page number.
     *
     * @return int
     */
    nextPage: function() {
        return ++this.page;
    },

    /**
     * Gets the next page of results.
     *
     * @param int page
     *
     * @return void
     */
    paginate: function(page) {
        page = page || this.nextPage();

        this.canPaginate = false;

        $('.pagination-loading').fadeIn();

        $.ajax({
            type: 'GET',
            url: "<?= $pagination_url ?>/" + page,
            cache: false,
            success: function(html) {
                // If results are returned, allow further pagination.
                if (html.length > 0) {
                    Content.canPaginate = true;
                }

                // Grab the relevant DOM snippets.
                var $newEntries = $(html).filter('.item');

                $isoContainer.append($newEntries.hide());

                // Display the new entries in isotope layout once all images have loaded.
                $newEntries.imagesLoaded(function() {
                    Content.processEntries({images: this.images});

                    $('.pagination-loading').fadeOut();
                    $newEntries.fadeIn();
                    $isoContainer.isotope('appended', $newEntries);

                    // Init tooltips for new entries.
                    $('[data-toggle="tooltip"]').tooltip();

                    /**
                     * Render the new, ajaxically-loaded Pinterest "Pin It" buttons.
                     * 
                     * @link http://opticalcortex.com/ajax-pinterest-buttons/
                     */
                    parsePins();
                });
            }
        });
    },

    /**
     * Performs extra processing on entries.
     *
     * @return void
     */
    processEntries: function(data) {
        // Loop through all of the provided images.
        for (var i = 0, length = data.images.length; i < length; i++) {
            var image = data.images[i];

            // Remove the parent container of any images that failed to load.
            if (!image.isLoaded) {
                $(image.img).parents('.item').remove();
            }
        }

        // Auto truncate full-text entry descriptions.
        $isoContainer.find('.description').expander({
            slicePoint: 255,
            expandText:       '[more]',
            userCollapseText: '[less]',
            // Set expand animation speed to immediate so that afterExpand gets triggered at the best time.
            expandSpeed: 0,
            afterExpand: function() {
                $isoContainer.isotope('layout');
            },
            afterCollapse: function() {
                $isoContainer.isotope('layout');
            }
        });
    },

    /**
     * Expands an entry tile to show more details.
     *
     * @param string selector
     *
     * @return void
     */
    expandEntry: function(selector) {
        var $container = $(selector);

        $container.toggleClass('item-large');
        $container.find('.domain').toggle();
        $container.find('.description').toggle();
        $container.find('.date-source').toggle();
        
        if ($container.hasClass('item-large')) {
            $container.find('.image .btn-pin-it').css({opacity: 1});
            $container.find('.image .btn-schedule').css({opacity: 1});
        } else {
            // Setting an inline style of opacity:0 breaks the hover so just clear the inline style.
            $container.find('.image .btn-pin-it').attr('style', '');
            $container.find('.image .btn-schedule').attr('style', '');
        }

        // Rebuild the isotope layout to accommodate the expanded entry tile.
        $isoContainer.isotope('layout');
    },

    /**
     * Schedules an entry as a publisher post.
     *
     * @param int entryId
     * @param obj entryData
     *
     * @return void
     */
    scheduleEntry: function(entryId, entryData) {
        $.ajax({
            type: 'POST',
            entry_id: entryId,
            data: entryData,
            url: '<?= URL::route("publisher-new-draft-post") ?>',
            cache: false,
            success: function (html) {
                $('body').append(html);

                editPostModal.init();
                editPostModal.submitCallback = function(response) {
                    editPostModal.close();

                    var $entryContainer = $('#entry-' + this.entry_id);

                    // Remove the schedule button (now that the entry has been scheduled).
                    $entryContainer.find('.btn-schedule').remove();

                    // Add a "Scheduled" ribbon.
                    var ribbon_text = 'Scheduled';
                    var ribbon_url = '<?= URL::route("publisher-posts", array('scheduled')) ?>';

                    if (response.post_type == 'pending') {
                        ribbon_text = 'Submitted';
                        ribbon_url = '<?= URL::route("publisher-posts", array('pending')) ?>';
                    }

                    $entryContainer.prepend(
                        '<div class="ribbon-wrapper"><div class="ribbon ribbon-blue">' +
                            '<a href="' + ribbon_url + '">' +
                                ribbon_text +
                            '</a>' +
                        '</div></div>'
                    );
                }.bind(this);
                editPostModal.show();

                // Always remove the edit post modal DOM when closed.
                $('#edit-post-modal').on('hidden', function () {
                    editPostModal.remove();
                });
            }
        });
    },

    /**
     * Flags an entry.
     *
     * @param string selector
     *
     * @return void
     */
    flagEntry: function(selector) {
        // Give immediate UI feedback to the user.
        $(selector).parents('.item').fadeTo('slow', '0.5');

        $.ajax({
            selector: selector,
            url: $(selector).data('url'),
            cache: false,
            success: function () {
                // Remove the flagged entry tile.
                $(selector).parents('.item').fadeOut('slow', function() {
                    $(this).remove();
                    
                    // Rebuild the isotope layout to accommodate the removed entry tile.
                    $isoContainer.isotope('layout');
                });
            }
        });
    }
};

$(function() {
    // Initialize Isotope.
    $isoContainer.isotope({
        itemSelector: '.item',
        layoutMode: 'masonry',
        masonry: {
            columnWidth: 236,
            gutter: 15
        },
        getSortData: {
            popularity: '[data-popularity] parseInt',
            datetime: '[data-datetime] parseInt'
        },
        sortAscending: false
    });

    $isoContainer.imagesLoaded(function() {
        Content.processEntries({images: this.images});

        // Render Isotope once all images have loaded.
        $isoContainer.isotope('layout');
    });

    $('#main-content-scroll').on('scroll', function() {
        var $scrollView  = $(this);
        var $contentView = $('#external-content');
        var offset       = $contentView.height() * 0.15;

        // Auto paginate a little before the very bottom of the page.
        if ($scrollView.scrollTop() + $scrollView.height() + offset >= $contentView.height()) {
            if (Content.canPaginate) {
                Content.paginate();
            }
        }
    });

    $('.js-btn-sort').on('click', function () {
        // Toggle selected state for sort buttons.
        $('#btn-sort-popular').toggleClass('active');
        $('#btn-sort-recent').toggleClass('active');

        $isoContainer.isotope({
            sortBy: $(this).data('sort-by')
        });
    });

    $('body').on('click', '#external-content .item', function (event) {
        // Ignore clicks on certain elements.
        var $target = $(event.target);
        if (   $target.hasClass('btn')
            || $target.hasClass('more-link')
            || $target.hasClass('less-link')
            || $target.parent().hasClass('ribbon')
            || $target.parent().hasClass('btn-pin-it')
        ) {
            return;
        }

        Content.expandEntry(this);
    });

    $('body').on('click', '.js-schedule-it', function () {
        Content.scheduleEntry($(this).data('entry-id'), $(this).data('entry-data'));
    });

    $('body').on('click', '.js-flag-it', function () {
        Content.flagEntry(this);
    });

    // Custom social score popover configuration.
    $('.popularity').popover({
        trigger: 'hover',
        title: '<strong>Popularity</strong>',
        content: function () {
            return $('#entry-score-popover-' + $(this).data('entry-id')).html();
        },
        html: true
    });

    var searchClient = new AlgoliaSearch("<?= Config::get('algolia.app_id') ?>", "<?= Config::get('algolia.read_api_key') ?>");
    var searchIndex  = searchClient.initIndex('topics');

    // Add topic auto-completion, suggestions to the search input.
    $('#input-search-js').typeahead(
        {
            autoselect: false,
            highlight: true
        },
        {
            source: searchIndex.ttAdapter(),
            displayKey: 'topic'
        }
    ).bind("typeahead:selected", function(obj, datum, name) {
        // Submit the search form when a topic suggestion is selected.
        $('#form-content-search').submit();
    });

    // Track inline click interactions.
    $('body').on('click', '.js-track-click', function () {
        element = $(this);
        if (!element.data('component') || !element.data('element')) {
            return;
        }

        $.ajax({
            type: 'POST',
            url: '<?= URL::route("api-record-event") ?>',
            data: {
                event: 'Content Discovery Click',
                data: {
                    view: appView,
                    component: element.data('component'),
                    element: element.data('element')
                }
            },
            cache: false
        });
    });
});
</script>