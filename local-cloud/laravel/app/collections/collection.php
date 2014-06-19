<?php

/**
 * Basic collections class
 * aka a bunch of models (in the club)
 *
 * @author Will
 */
class Collection implements ArrayAccess, Iterator, Countable
{

    /**
     * @var array
     */
    protected $models = array();

    /**
     * Add a model to the collection
     *
     * @author Will
     *
     */
    public function add(Model $model, $index = false)
    {

        if (is_bool($index) && $index === true) {
            $this->models[$model->getIndex()] = $model;
        } else if ($index) {
            $this->models[$index] = $model;
        } else {

            $this->models[] = $model;
        }

        return $this;
    }

    /**
     * Check whether any values in $collection pass $iterator.
     *
     * @param Closure $iterator
     * @return boolean
     */
    public function any(Closure $iterator)
    {
        foreach ($this->models as $node)
        {
            if ($iterator($node))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether all values in $collection pass $iterator.
     *
     * @param Closure $iterator
     * @return boolean
     */
    public function allPass(Closure $iterator)
    {
        foreach ($this-> models as $node)
        {
            if ( ! $iterator($node))
            {
                return false;
            }
        }

        return true;
    }


    /**
     * Calculates the average of the numerical columns in the set
     *
     * @param $column
     *
     * @todo       Sanity check column to make sure it is an integer
     *
     * @return float
     */
    public function average($column)
    {
        return round($this->sum($column) / $this->count());
    }

    /**
     * Take a column and put the values in an array
     *
     * @author  Will
     */
    public function columnToArray($column_name)
    {

        $class = get_called_class();

        $array = array();

        foreach ($this->models as $model) {
            if (!isset($model->$column_name)) {
                throw new CollectionException("$column_name is not a property of $class");
            }
            $array[] = $model->$column_name;
        }

        return $array;

    }

    /**
     * @author  Will
     * @return Collection
     */
    public function copy()
    {
        $class          = get_called_class();
        $new_collection = new $class();
        foreach ($this->models as $key => $model) {
            $new_collection->add($model, $key);
        }

        return $new_collection;
    }

    /**
     * @required for ArrayObject
     */
    public function count()
    {
        return count($this->models);
    }

    /**
     * @required for ArrayObject
     */
    public function current()
    {
        return current($this->models);
    }

    /**
     * true = keep
     * false = remove
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function filter(callable $callback)
    {

        $return = [];

        foreach ($this->models as $key => $model) {
            if ($callback($model)) {
                $return[$key] = $model;
            }
        }

        $this->models = $return;

        return $this;
    }

    /**
     * Will give you the first model in the set
     *
     * @return mixed
     */
    public function first()
    {
        return call_user_func('reset', array_values($this->models));
    }

    /**
     * @author  Will
     *
     * @param $key
     *
     * @return Model | bool
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->models)) {
            return $this->models[$key];
        }

        return false;
    }

    /**
     * @param $key
     *
     * @return bool|\Model
     */
    public function getModel($key)
    {
        return $this->get($key);
    }

    /**
     * Gets all of the collection's models.
     *
     * @return array
     */
    public function getModels()
    {
        return $this->toArray();
    }

    /**
     * Alias for in_array
     *
     * @param $key
     *
     * @author  Will
     *
     * @return bool
     */
    public function inCollection($key)
    {
        return array_key_exists($key, $this->models);
    }

    /**
     * @author  Will
     *
     * @returns bool
     */
    public function isEmpty()
    {
        if ($this->count() === 0) {
            return true;
        }

        return false;
    }

    /**
     * @author  Will
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    /**
     * @required for ArrayObject
     */
    public function key()
    {
        return key($this->models);
    }

    /**
     * Gets the last element in the models array
     * without messing with the pointer
     * (thats why we don't just use end, and get the key at that point)
     *
     *
     * @return mixed
     */
    public function last()
    {
        return call_user_func('end', array_values($this->models));
    }

    /**
     * Removes/unsets all but the first $limit from the collection
     *
     * @author  Will
     *
     */
    public function limit($limit)
    {

        if ($limit >= $this->count()) {
            return $this;
        }

        $models = array_slice($this->models, 0, $limit);

        $this->models = $models;

        return $this;
    }

    /**
     * Takes a collection of profiles and inserts into the models cache
     * so we don't make a million calls
     *
     */
    public function loadCache($object_name, $model_index, Collection $collection)
    {

        foreach ($this->models as $model) {
            $model->setCache($object_name, $collection->getModel($model->$model_index));
        }

    }

    /**
     * Merges a set of models into the existing collection.
     *
     * @param mixed $models
     *
     * @return Collection
     */
    public function merge($models)
    {
        if ($models instanceof Collection) {
            $models = $models->getModels();
        }

        $this->models = array_merge($this->models, $models);

        return $this;
    }

    /**
     * @required for ArrayObject
     */
    public function next()
    {
        return next($this->models);
    }

    /**
     * @param $index
     *
     * @example
     * models(a,b,c,d)
     *
     * $this->nth(1); //a
     * $this->nth(2); //b
     *
     * @return array
     */
    public function nth($index)
    {
        $index--;

        return array_slice($this->models, $index, 1, true);
    }

    /**
     * Returns the key value of the nth index
     *
     * @author  Will
     *
     * @example
     * models('foo'=>'a','7'=>b,'c'=>'9er',d)
     *
     * $this->nthKey(1); //foo
     * $this->nthKey(3); // c
     *
     * @param $index
     *
     * @return string
     */
    public function nthKey($index)
    {
        $index--;
        $keys = array_keys($this->models);

        return $keys[$index];
    }

    /**
     * Removes the model at the nth index
     *
     * @author Will
     *
     * @param $index
     *
     * @return $this
     */
    public function nthRemove($index)
    {
        $this->removeModel($this->nthKey($index));

        return $this;
    }

    /**
     * @required for ArrayObject
     */
    public function offsetExists($offset)
    {
        return isset($this->models[$offset]);
    }

    /**
     * @required for ArrayObject
     */
    public function offsetGet($offset)
    {
        return isset($this->models[$offset]) ? $this->models[$offset] : null;
    }

    /**
     * Recreate ArrayObject
     *
     * @author Will
     */
    public function offsetSet($offset, $value)
    {
        $this->models[$offset] = $value;
    }

    /**
     * @required for ArrayObject
     */
    public function offsetUnset($offset)
    {
        unset($this->models[$offset]);
    }

    /**
     * Extract an array of values associated with $key from $collection.
     *
     * @param string $key
     * @return array
     */
    public function pluck($key)
    {
        return $this->columnToArray($key);
    }

    /**
     * @author  Will
     */
    public function random($amount)
    {

        if ($amount > $this->count()) {
            return $this;
        }

        if ($amount == 1) {
            return $this->getModel(array_rand($this->models));
        }

        $class = get_called_class();

        $random_collection = new $class();

        $keys = array_rand($this->models, $amount);
        foreach ($keys as $key) {
            $model = $this->getModel($key);
            $random_collection->add($model, $key);
        }

        return $random_collection;
    }

    /**
     * @author  Will
     */
    public function removeModel($key)
    {
        if (array_key_exists($key, $this->models)) {
            unset($this->models[$key]);
        }

        return $this;
    }

    /**
     * @required for ArrayIterator
     * @return bool
     */
    public function rewind()
    {
        reset($this->models);

        return $this;
    }

    /**
     * @author  Will
     */
    public function rsort()
    {
        krsort($this->models);

        return $this;
    }

    /**
     * Alias for sort by matching PHP convention
     *
     * @param $column
     *
     * @return array
     */
    public function rsortBy($column)
    {
        return $this->sortBy($column, SORT_DESC);
    }

    /**
     * Sets the given property of every model to a given value
     *
     * @param       $property
     * @param       $value
     * @param array $ignore_keys
     *
     * @return $this
     */
    public function setPropertyOfAllModels($property, $value, $ignore_keys = array())
    {
        foreach ($this->models as $key => &$model) {
            if (!in_array($key, $ignore_keys)) {
                $model->$property = $value;
            }
        }

        return $this;
    }

    /**
     * @author  Will
     *
     * @return $this
     */
    public function shuffle()
    {
        shuffle($this->models);

        return $this;
    }

    /**
     * @author  Will
     *
     * @param $key
     *
     * @return \PDODatabaseModel
     */
    public function slice($key)
    {

        $model = $this->getModel($key);
        $this->removeModel($key);

        return $model;

    }

    /**
     * @author  Will
     */
    public function sort()
    {
        ksort($this->models);
    }

    /**
     * Sorts the models by the column name.
     *
     * @author   Will Washburn
     * @example
     *
     *      $this->sortBy('timestamp');
     *      $this->sortBy('timestamp',SORT_ASC,'name',SORT_DESC);
     *      $this->sortBy(array('timestamp',SORT_ASC,'name',SORT_DESC));
     *
     * @throws InvalidArgumentException
     * @return Collection
     */
    public function sortBy()
    {
        if (func_num_args() == 0) {
            throw new InvalidArgumentException('You need to pass a parameter to sortBy');
        }

        $args = func_get_args();

        if (func_num_args() == 1) {

            if (is_array($args[0])) {
                $args = $args[0];
            } else {
                $args[] = SORT_ASC;
            }
        }

        $data = $this->models;

        foreach ($args as $arg_key => $field) {
            if (is_string($field)) {
                $sort = array();
                foreach ($this->models as $key => $model)
                    $sort[$key] = $model->$field;
                $args[$arg_key] = $sort;
            }
        }
        $args[] = & $data;
        call_user_func_array('array_multisort', $args);
        $this->models = array_pop($args);

        return $this;
    }

    /**
     * Sometimes we store a sort value in the key
     * to sort things by repin for example
     * sometimes we want to get this value back
     *
     * @author  Will
     *
     * @see     getIndex()
     *
     * @param $index
     *
     * @return string
     */
    public function sortValueAtNthKey($index)
    {
        $hash  = explode('@', $this->nthKey($index));
        $value = ltrim($hash[0], 0);

        if (empty($value)) {
            return 0;
        }

        return $value;
    }

    /**
     * Calculates the difference between numerical columns in the set
     * If a set of array keys are given, it will only use those. If not, it will
     * use all of them
     *
     * @todo       Sanity check column to make sure it is an integer
     *
     * @param      $column
     *
     * @return int
     */
    public function spread($column)
    {
        if ($this->count() < 2) {
            return 0;
        }

        return $this->first()->$column - $this->last()->$column;
    }

    /**
     * Take the field name of the model in each model add it to a string
     *
     * @author  Will
     *
     * @param        $field_name
     * @param string $separator
     *
     * @param string $encase_in
     *
     * @return string
     */
    public function stringifyField($field_name, $separator = ',', $encase_in = "'")
    {
        $string = '';
        foreach ($this->models as $model) {
            $string .= $encase_in . $model->$field_name . $encase_in . $separator;
        }
        $string = rtrim($string, $separator);

        return $string;
    }

    /**
     * @param $column
     * @author  Will
     *
     * @return int
     */
    public function sum($column) {
        $total = 0;

        foreach ($this->models as $model) {
            $total += $model->$column;
        }
        return $total;
    }

    /**
     * @author  Will Washburn
     * @return array
     */
    public function toArray()
    {
        return $this->models;
    }

    /**
     * @required for ArrayIterator
     * @return bool
     */
    public function valid()
    {
        return $this->current() !== false;
    }
}

/**
 * Class CollectionException
 */
class CollectionException extends Exception{}

