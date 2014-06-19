 namespace Models;

 use
    \Collections\<?= $collection_name;?>,
    \Models\Tailwind\EloquentModel;

 /**
 * Class User
 *
<?php foreach ($columns as $name => $column): ?> * @property    $<?= $name;?>       <?=$column->type;?>    <?=$column->size;?>[<?= $column->default;?>]<?php echo PHP_EOL; endforeach ?>
 *
 * @package Models
 */
class <?=$model_name;?> extends EloquentModel
 {
     /**
      * @var string the table name
      */
     public $table = '<?= $table_name;?>';
     /**
      * The user table is auto incrementing
      *
      * @var bool
      */
     public $incrementing = false;
     /**
      * The columns in the database for the user object
      *
      * @var array
      */
     protected $columns = array(
         <?php foreach ($columns as $name => $column): ?>'<?= $name;?>' => '<?=$column->type;?>',<?php echo PHP_EOL.'         '; endforeach; ?>);
     /**
      * This is the auto-incrementing key associated with this table
      *
      * @var string
      */
     protected $primary_keys = [<?php foreach ($columns as $name => $column): if($column->primary) {?>'<?= $name;?>',<?php } endforeach;?>];

     /**
      * @param array $models
      *
      * @return \Collections\<?= $collection_name;?>
      */
     public function newCollection(array $models = array()) {
        return new <?= $collection_name;?>($models);
     }
 }
