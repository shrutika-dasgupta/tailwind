namespace Collections;

use
    Collections\Tailwind\EloquentCollection;

 /**
 * Collection of <?= $model_name;?> models
 *
 * @package Collections
 */
class <?= $collection_name;?> extends EloquentCollection
{
    /**
     * @return \Models\<?= $model_name;?>
     */
    protected function getRelatedModel()
    {
        return $this->related_model = new \Models\<?= $model_name;?>();
    }
}
