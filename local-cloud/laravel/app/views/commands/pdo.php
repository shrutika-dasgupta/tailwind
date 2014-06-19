class <?=$model_name;?> extends PDODatabaseModel
{
public
    $table = '<?= $table_name;?>',
    $columns =
        array(<?php foreach ($columns as $name => $column): ?>
            '<?= $name;?>',
<?php endforeach; ?>
        ),
    $primary_keys = array(<?php foreach ($columns as $name => $column): if($column->primary) {?>'<?= $name;?>',<?php } endforeach;?>);

   public
<?php foreach ($columns as $name => $column): ?>/**
         * @var <?= $column->type;?> <?= $column->size.PHP_EOL;?>
         * @default <?= $column->default;?>
         */
        $<?= $name;?>,
<?php endforeach; ?>


}

class <?=$model_name;?>Exception extends DBModelException {}
