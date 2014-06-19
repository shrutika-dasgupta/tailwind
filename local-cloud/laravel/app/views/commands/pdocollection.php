
/**
 * Collection of <?= $model_name;?> model
 */
class <?= $collection_name;?> extends DBCollection
{
    const MODEL = '<?= $model_name;?>';
    const TABLE = '<?= $table_name;?>';

    public $table = '<?= $table_name;?>';

    public $columns =
array(<?php foreach ($columns as $name => $column): ?>
    '<?= $name;?>',
<?php endforeach; ?>
);

    public $primary_keys = array(<?php foreach ($columns as $name => $column): if($column->primary) {?>'<?= $name;?>',<?php } endforeach;?>);
}

class <?= $collection_name;?>Exception extends CollectionException {}
