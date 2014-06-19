<?php

/*
|--------------------------------------------------------------------------
| View Composers
| This is the mapping for the view files and their composers
| These can be used to set sensible defaults and bring perform actions
| when a view is instantiated or rendered (view creator vs view composers)
|--------------------------------------------------------------------------
*/
View::creator('layouts.analytics', 'Composers\Layouts\AnalyticsComposer');
View::creator('settings::tasks', 'Composers\Analytics\Pages\Settings\TasksComposer');

/**
 * Dashboard
 */
View::creator(['dashboard::tasks.task','dashboard::tasks.dashboard_task'], 'Composers\Analytics\Dashboard\Tasks\TaskComposer');


/**
 * Components
 */
View::creator(['components::pre_nav.demo_bar'], 'Composers\Analytics\Components\DemoBarComposer');
View::creator(['components::head.segmentio'], 'Composers\Analytics\Components\SegmentIOComposer');
View::creator(['components::pre_body_close.olark'], 'Composers\Analytics\Components\OlarkComposer');
View::creator(['components::admin_dropdown'], 'Composers\Analytics\Components\AdminDropdownComposer');
