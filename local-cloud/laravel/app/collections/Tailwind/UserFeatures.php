<?php namespace Collections\Tailwind;

use
    Models\Tailwind\UserFeature;

/**
 * Collection of UserFeature models
 *
 * @package Collections
 */
class UserFeatures extends EloquentCollection
{
    /**
     * @return UserFeature     */
    protected function getRelatedModel()
    {
        return $this->related_model = new UserFeature();
    }
}
