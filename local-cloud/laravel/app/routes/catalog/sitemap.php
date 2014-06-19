<?php
Route::group(array('domain' => ROUTE_PREFIX . 'www.tailwindapp.' . ROUTE_TLD), function () {
    Route::get('/sitemap.xml', function () {

        $sitemapDirectory = array(
            '/'              => array(
                'timestamp' => '10am September 23rd, 2013',
                'priority'  => '1.0',
                'freq'      => 'monthly'
            ),
            '/features'      => array(
                'timestamp' => '10am September 23rd, 2013',
                'priority'  => '0.9',
                'freq'      => 'monthly'
            ),
            '/pricing'       => array(
                'timestamp' => '10am September 23rd, 2013',
                'priority'  => '0.8',
                'freq'      => 'monthly'
            ),
            '/agencies'      => array(
                'timestamp' => '10am September 23rd, 2013',
                'priority'  => '0.7',
                'freq'      => 'monthly'
            ),
            '/about'         => array(
                'timestamp' => '10am September 23rd, 2013',
                'priority'  => '0.6',
                'freq'      => 'monthly'
            ),
            '/blog'          => array(
                'priority' => '0.5',
                'freq'     => 'daily'
            ),
            '/login'         => array(
                'timestamp' => '10am September 23rd, 2013',
                'priority'  => '0.4',
                'freq'      => 'monthly'
            ),
            '/about/careers' => array(
                'timestamp' => '10am September 23rd, 2013',
                'priority'  => '0.3',
                'freq'      => 'monthly'
            ),
            '/about/privacy' => array(
                'timestamp' => '10am September 23rd, 2013',
                'priority'  => '0',
                'freq'      => 'monthly'
            ),
            '/about/terms'   => array(
                'timestamp' => '10am September 23rd, 2013',
                'priority'  => '0',
                'freq'      => 'monthly'
            ),
        );

        $sitemap = App::make("sitemap");

        foreach ($sitemapDirectory as $page => $details) {

            $sitemap->add(

            // set item's url, date, priority, freq
                    URL::to($page),
                    date('c', strtotime(array_get($details, 'timestamp', '12am today'))),
                    $details['priority'],
                    $details['freq']

            );
        }

        $sitemap->add(
                'http://blog.tailwindapp.com',
                date('c', flat_date('day')),
                '0.5',
                'daily'
        );

        // show your sitemap (options: 'xml' (default), 'html', 'txt', 'ror-rss', 'ror-rdf')
        return $sitemap->render('xml');

    });

});