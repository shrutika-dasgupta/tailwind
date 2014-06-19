<script type="text/javascript">
    var accountTags = <?= json_encode($tags) ?>;
</script>

<?php if (empty($tags)) return ?>

<button id="tags-btn" class="btn dropdown-toggle track-click topic-bar-btn" data-toggle="dropdown" data-component="Topic Bar" data-element="Collections Button">
    <i class="icon-star"></i><b class="caret"></b>
</button>

<ul id="tags-dropdown-menu" class="dropdown-menu btn-dropdown-menu">
    <li class="dropdown-menu-title">Collections</li>

    <?php foreach ($tags as $name => $tag): ?>
        <li class="tag" data-tag-name="<?= $name ?>">
            <i class="icon-cancel hidden" data-url="<?= URL::route('discover-remove-tag', array(urlencode($name))) ?>"></i>
            <a href="javascript:void(0)" class="track-click" data-component="Topic Bar" data-element="Collection Link">
            	<?= $name ?>
            </a>
        </li>
    <?php endforeach ?>
</ul>