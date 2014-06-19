<?php namespace Collections\Tailwind;

use
    Models\Tailwind\PlanFeature;

/**
 * Collection of PlanFeature models
 *
 * @package Collections
 */
class PlanFeatures extends EloquentCollection
{
    /**
     * @return PlanFeature     */
    protected function getRelatedModel()
    {
        return $this->related_model = new PlanFeature();
    }
}
