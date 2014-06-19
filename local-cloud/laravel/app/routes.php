<?php

/**
 * For domains like staging.www.tailwindapp.com
 * we need to find the 'staging' prefix so we can perform the routes
 * correctly. This goes through the available subdomains, and once
 * it finds the right one parses out the prefix
 *
 * @author  Will
 */
$prefix = array('');

foreach (Config::get('app.subdomains') as $dir => $subdomain) {
    if (strpos($host = Request::getHost(), $subdomain . '.tailwindapp.com')) {
        $prefix = explode($subdomain . '.tailwindapp.com', $host);
        break;
    }
}

/**
 * Once we define the prefix and tld, we bring in the routes files
 * which have been separated out for clarity
 */
defined('ROUTE_PREFIX') or define('ROUTE_PREFIX', $prefix[0]);
defined('ROUTE_TLD') or define('ROUTE_TLD', Config::Get('app.tld'));
defined('ANALYTICS_URL') or define('ANALYTICS_URL', ROUTE_PREFIX.'analytics.tailwindapp.'.ROUTE_TLD);

/*
 * Get everything in the routes directory and include it
 */
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path().'/routes')) as $file) {
    /** @var SplFileInfo $file */
   if (!$file->isDir())  {
       include_once $file->getPathName();
   }
}
