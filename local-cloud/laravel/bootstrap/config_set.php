<?php

/*
 * This sets the max lifetime of the sessions to 30 days (2,592,000 seconds)
 */
ini_set('session.gc_maxlifetime',2592000);
ini_set('default_charset', 'UTF-8' );
session_set_cookie_params(2592000);

/*
 * We set the default time zone as central for now, since we aren't supporting time zones yet
 */
date_default_timezone_set('America/Chicago');
