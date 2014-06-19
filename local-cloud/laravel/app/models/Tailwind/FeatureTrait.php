<?php namespace Models\Tailwind;

/**
 * Class FeatureTrait
 */
trait FeatureTrait
{
    /**
     * Features that have these words will be able to be edited instead
     * of enabled / disabled
     */
    public function isEditable()
    {

        $triggers = array(
            '_version',
            'num_',
        );

        foreach ($triggers as $fragment) {
            if (strpos($this->name, $fragment) !== false) {
                return true;
            }
        }

        return false;

    }

    /**
     * Checks if a feature is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {

        if ($this->value == 0 OR
            strtolower($this->value == 'false')
        ) {
            return false;
        }

        return true;
    }

    /**
     * @author  Will
     * @return bool|int
     */
    public function maxAllowed()
    {
        if (is_numeric($this->value)) {
            return $this->value;
        }

        return false;
    }

}