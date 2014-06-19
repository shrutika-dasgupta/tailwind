<?php namespace Models\Tailwind;

/**
 * Interface DataBaseInterface
 */
interface DatabaseInterface
{

    /**
     * Attempts to insert a new row, if there is a primary key constraint
     * and there is a duplicate then we update the row
     *
     * @param array $ignore_columns
     *
     * @internal param array $ignore_rows
     *
     * @return self
     */
    public function insertUpdate(array $ignore_columns = array());

    /**
     * Attempts to insert a new row, if there is a primary key constraint it
     * will not do... anything....
     *
     * @param array $ignore_columns
     *
     * @internal param array $ignore_rows
     *
     * @return self
     */
    public function insertIgnore(array $ignore_columns = array());

    /**
     * Attempts to insert a row only using the array of columns passed
     * If the key exists, update the columns listed
     *
     * @param  array $columns An array of columns to update
     *
     * @return mixed
     */
    public function insertUpdateOnly(array $columns);

    /**
     * Attempts to insert a row only using the array of columns passed
     * If the key exists, don't do anything
     *
     * @param  array $columns An array of columns to update
     * @return mixed
     */
    public function insertIgnoreOnly(array $columns);

    /**
     * If there is an autoincrementing primary key, don't include that in the
     * insert statement
     *
     * @param array $ignore_columns
     *
     * @return mixed
     */
    public function saveAsNew(array $ignore_columns);
}