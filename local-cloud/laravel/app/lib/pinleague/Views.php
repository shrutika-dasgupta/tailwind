<?php namespace Pinleague;

use Illuminate\View\View;

/**
 * Class ViewManager
 *
 * @package Pinleague
 */
class Views implements \ArrayAccess, \Iterator, \Countable
{

    /**
     * @var array of \View objects
     */
    protected $views = array();

    /**
     * Insert a view
     *
     * @param View $view
     * @param int  $position
     *
     * @return $this
     */
    public function insert($view, $position = 0)
    {
        switch ($position) {

            case 'first':
            case 'beginning':
            case 'start':
            case 'unshift':
            case 0:

                array_unshift($this->views, array($view));

                break;

            case 'last':
            case 'end':
            case 'push':

                array_push($this->views, array($view));

                break;

            default:

                $this->views =
                    array_slice($this->views, 0, $position, true) +
                    array($view) +
                    array_slice($this->views, $position, $this->count() - 3, true);

                break;
        }
    }

    /**
     * @param View $view
     *
     * @return $this
     */
    public function add(View $view)
    {
        return $this->insert($view, 'last');
    }

    /**
     * Takes the views, and renders them into one blob
     *
     * @author  Will
     * @return string
     */
    public function render()
    {
        $html = '';

        foreach ($this->views as $view) {

            if ($view instanceof View) {
                $html .= $view->render();
            } elseif (is_string($view)) {
                $html .= $view;
            }
        }

        return $html;
    }

    /**
     * Render the views
     *
     * @author  Will
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * @required for ArrayObject
     */
    public function count()
    {
        return count($this->views);
    }

    /**
     * @required for ArrayObject
     */
    public function current()
    {
        return current($this->views);
    }

    /**
     * Will give you the first model in the set
     *
     * @return mixed
     */
    public function first()
    {
        return call_user_func('reset', array_values($this->views));
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
        return call_user_func('end', array_values($this->views));
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

        return array_slice($this->views, $index, 1, true);
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
        $keys = array_keys($this->views);

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
     * @required for ArrayObject
     */
    public function key()
    {
        return key($this->views);
    }

    /**
     * @required for ArrayObject
     */
    public function next()
    {
        return next($this->views);
    }

    /**
     * @required for ArrayObject
     */
    public function offsetExists($offset)
    {
        return isset($this->views[$offset]);
    }

    /**
     * @required for ArrayObject
     */
    public function offsetGet($offset)
    {
        return isset($this->views[$offset]) ? $this->views[$offset] : null;
    }

    /**
     * Recreate ArrayObject
     *
     * @author Will
     */
    public function offsetSet($offset, $value)
    {
        $this->views[$offset] = $value;
    }

    /**
     * @required for ArrayObject
     */
    public function offsetUnset($offset)
    {
        unset($this->views[$offset]);
    }

    /**
     * @required for ArrayIterator
     * @return bool
     */
    public function rewind()
    {
        reset($this->views);
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