<?php

/**
 * Class UserProperty
 *
 * @author  Will
 */
class UserProperty extends PDODatabaseModel
{
    const SIGNED_UP_AT          = 'signed_up_at';
    const LOGIN_COUNT           = 'login_count';
    const VIEW_REPORT           = 'viewed_report_';
    const LAST_VIEWED_REPORT_AT = 'last_viewed_report_';
    const UNSUBSCRIBED_COUNT    = 'unsubscribed_count';
    const LAST_UNSUBSCRIBED_AT  = 'last_unsubscribed';
    const EMAIL_CLICKS          = 'email clicks';
    const LAST_EMAIL_CLICK_AT   = 'last email click at';
    const EMAIL_OPENS           = 'email opens';
    const LAST_EMAIL_OPEN_AT    = 'last email opened at';
    const EMAIL_COMPLAINTS      = 'email complaints';
    const LAST_COMPLAINT_AT     = 'last complaint at';
    const EMAIL_BOUNCES         = 'email bounces';
    const LAST_BOUNCE_AT        = 'last bounce at';
    const EMAIL_DROPS           = 'email drops';
    const LAST_EMAIL_DROP_AT    = 'last email drop at';
    const SESSION_COUNT         = 'session_count';
    const VIEW_DEMO             = 'viewed_demo_';
    const TOTAL_DEMO_VIEWS      = 'total_demo_views';

    public
        $columns = array(
        'cust_id',
        'property',
        'count',
        'created_at',
        'updated_at'
    ),
        $table = 'user_properties',
        $primary_keys = array('cust_id', 'property');

    public
        $cust_id,
        $property,
        $count,
        $created_at,
        $updated_at;

    /**
     * @author  Will
     */
    public function __construct() {
        parent::__construct();
        $this->created_at = time();
        $this->updated_at = time();
    }

    /**
     * @return mixed
     */
    public function __toString() {
        return $this->count;
    }

}

/**
 * Class UserPropertyNotFoundException
 */
class UserPropertyNotFoundException extends DBModelException {}