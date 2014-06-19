<?php

/**
 * Base model
 *
 * @author  Will
 */
class Model {

    /*
     * Miscellaneous place to store appended data for objects
     * The key here is that it's TEMPORARY data we may need to
     * pass around with objects from time to time
     */
    protected $misc = array();

    /**
     * @author Will
     * @author Alex
     *
     * @param name
     *
     * Magic getter. 'Nuff said.
     *
     * @throws ModelException
     * @return
     */
    public function __get($name)
    {
        if(property_exists(get_called_class(), $name)){
            return $this->$name;
        }
        if(array_key_exists($name, $this->misc)){
            return $this->misc[$name];
        }

        throw new ModelException ("$name doesn't exist");
    }

    /**
     *
     * Magic Setter
     */
    public function __set($name, $value)
    {
        if(property_exists(get_called_class(), $name)){
            $this->$name = $value;
            return;
        }

        $this->misc[$name] = $value;
        return;
    }
}

class ModelException extends Exception {}
