<?php namespace Admin;

use DatabaseInstance;

class BaseController extends \BaseController
{

    /**
     * @var $DBH \PDO Database handle
     */
    protected $DBH;

    public function __construct()
    {
        $this->DBH = DatabaseInstance::DBO();
    }
}