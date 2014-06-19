<?php

/**
 * The greatest debugging tool ever
 *
 * @author  Will
 *
 * @param        $var
 * @param string $var_name
 * @param bool   $name
 * @param bool   $show_even_in_production
 */
function ppx($var, $var_name = '', $name = false, $show_even_in_production = false)
{
    if (App::environment() != 'production' OR $show_even_in_production) {
        pp($var, $var_name);
        exit;
    }
}

/**
 * @author  Will
 *
 * @param        $var
 * @param string $var_name
 * @param bool   $name
 * @param bool   $show_even_in_production
 */
function pp($var, $var_name = '', $name = false, $show_even_in_production = false)
{
    if (App::environment() != 'production' OR $show_even_in_production) {
        echo '<pre>';
        echo $var_name . '<br/>';
        var_dump($var);
        echo '</pre>';
    }
}
