<?php namespace API;

use DatabaseInstance;

/**
 * Class BaseController
 *
 * @package API
 */
class BaseController extends \BaseController
{

    /**
     * @var /PDO
     */
    protected $DBH;

    /**
     * @author  Will
     */
    public function __construct()
    {
        $this->DBH = DatabaseInstance::DBO();
    }
}