<?php namespace Collections\Tailwind;

use Models\Tailwind\OrganizationFeature;


/**
 * Collection of OrganizationFeature models
 *
 * @package Collections
 */
class OrganizationFeatures extends EloquentCollection
{
    /**
     * @return OrganizationFeature
     */
    protected function getRelatedModel()
    {
        return $this->related_model = new OrganizationFeature();
    }
}
