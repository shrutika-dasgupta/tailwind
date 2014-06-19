<?php namespace Collections\Tailwind;

use
    Models\Tailwind\Feature;


/**
 * Collection of Feature models
 *
 * @package Collections
 */
class Features extends EloquentCollection
{
    /**
     * @return Feature
     */
    protected function getRelatedModel()
    {
        return $this->related_model = new Feature();
    }
}
