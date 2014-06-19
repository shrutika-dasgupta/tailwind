<?php

/**
 * These are the 301 redirects for pinleague.com
 *
 * The left side of the array is what USED TO BE on pinleague.com
 * The right side is where we send them on tailwindapp.com
 *
 */

$redirects = array(
    ''                                                           => '/',
    '/'                                                          => '/',
    '/pricing-plans'                                             => '/pricing',
    '/pricing-plans/pinterest-analytics-tools'                   => '/pricing',
    '/pinterest-tools'                                           => '/features',
    '/pinterest-tools/free-pinterest-analytics'                  => '/features',
    '/pinterest-tools/pinterest-analytics-dashboard'             => '/features',
    '/features'                                                  => '/features',
    '/agencies'                                                  => '/agencies',
    '/pricing-plans/get-pinterest-followers'                     => '/',
    '/pinterest-tools/audience-building-get-pinterest-followers' => '/',
    '/pinterest-tools/pinmail-pinterest-email-marketing'         => '/',
    '/enterprise'                                                => '/features',
    '/agency-solutions'                                          => '/agencies',
    '/about-us'                                                  => '/about',
    '/were-hiring'                                               => '/about#hiring',
    '/about-us/were-hiring'                                      => '/about#hiring',
    '/about-us/pinleague-team'                                   => '/about#team',
    '/about-us/our-mission-help-brands-on-pinterest'             => '/about#mission',
    '/about-us/pinleague-team-2'                                 => '/about#team',
    '/terms-conditions'                                          => '/about/terms',
    '/privacy-policy'                                            => '/about/privacy',
    '/category/pinleague-case-studies'                           => '/category/tailwind-case-studies',

);

$analytics_redirects = array(
    '/login.php'   => '/',
    '/profile.php' => '/profile',
    '/website.php' => '/website'
);


/*
 * Get the uri, redirect based on that
 */
$parsed = parse_url($_SERVER["REQUEST_URI"]);
$url    = $parsed['path'];

$url = rtrim($url, '/');


header("HTTP/1.1 301 Moved Permanently");

if (array_key_exists($url, $redirects)) {

    header("Location: http://www.tailwindapp.com" . $redirects[$url]);

    exit;
} elseif (array_key_exists($url, $analytics_redirects)) {
    header("Location: http://analytics.tailwindapp.com" . $redirects[$url]);
} else {

    $pieces = explode('/', $url);
    if (array_key_exists(2,$pieces)) {
        if ($pieces[2] == 'pinleague-blog') {
            $pieces[2] = 'tailwind-blog';
            $url       = implode('/', $pieces);
        }
    }

    if ($url == '/pinleague-blog') {
        $url = '';
    }

    header("Location: http://blog.tailwindapp.com" . $url);

    exit;
}

/***
 * Could do this one layer up in apache, but um, is it REALLY that big of deal
 * This feels easier, so that's what I'm doing.. so shoot me
 *
 *
 *
//*301 Redirect: xyz-site.com to www.xyz-site.com

RewriteEngine On
RewriteBase /
RewriteCond %{HTTP_HOST} !^www.xyz-site.com$ [NC]
RewriteRule ^(.*)$ http://www.xyz-site.com/$1 [L,R=301]

//*301 Redirect: www.xyz-site.com to xyz-site.com

RewriteEngine On
RewriteBase /
RewriteCond %{HTTP_HOST} !^xyz-site.com$ [NC]
RewriteRule ^(.*)$ http://xyz-site.com/$1 [L,R=301]

//*301 Redirect: Redirecting Individual pages

Redirect 301 /previous-page.html http://www.xyz-site.com/new-page.html
 */