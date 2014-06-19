<?php
/**
 * Helper Functions accessible globally
 */

function ip()
{
    return array_get($_SERVER,'REMOTE_ADDR',NULL);
}


/**
 * Prints JSON and JSON content type header
 *
 * @author  Will
 * @depreciated Please use Laravel's Response::json() method instead.
 *
 * @param $json
 */
function send_json($json)
{

    header('Content-type: application/json');
    echo json_encode($json);

}


/**
 * @author  Will
 *
 * @param $obj
 *
 * @return string
 */
function get_real_class($obj)
{
    $classname = get_class($obj);

    if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
        $classname = $matches[1];
    }

    return $classname;
}


/**
 * @author  Will
 *
 * @param int $length
 *
 * @return string
 */
function random_string($length = 5)
{

    $code  = "";
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
    srand((double)microtime() * 1000000);
    for ($i = 0; $i < $length; $i++) {
        $code .= substr($chars, rand() % strlen($chars), 1);
    }

    return $code;

}

/**
 * @author  Will
 * @return bool
 *
 */
function all_in_array( /* $array, $posted_var1,$posted_var2,$posted_var3...etc */)
{

    $arguments = func_get_args();
    $array     = $arguments[0];

    unset($arguments[0]);
    foreach ($arguments as $key) {
        if (!isset($array[$key])) {
            return false;
        }
    }

    return true;
}

/**
 * @param      $needle
 * @param      $haystack
 * @param null $strict
 *
 * @return bool
 */
function not_in_array($needle, $haystack, $strict = null)
{
    return !in_array($needle, $haystack, $strict);
}


/**
 * @author  Will
 */
function flat_date($type = 'day', $time = false)
{

    if ($time === false) {
        $time = time();
    }

    switch ($type) {

        default:
        case 'day':
            return mktime(0, 0, 0, date("n", $time), date("j", $time), date("Y", $time));

            break;


        case 'hour':
            return mktime(date("G", $time), 0, 0, date("n", $time), date("j", $time), date("Y", $time));

            break;

    }
}


/**
 * return a timestamp from a date in the format of mm-dd-yyyy
 *
 * @param string $date.
 *
 * @return string
 */
function getTimestampFromDate($date)
{
    $m = substr($date, 0, 2);
    $d = substr($date, 3, 2);
    $y = substr($date, 6, 4);
    $timestamp = mktime(0, 0, 0, $m, $d, $y);

    return $timestamp;
}

/**
 * Pretty print version of print_r().
 * 
 * @param array $data Data to print.
 * 
 * @return void
 */
function dar($data)
{
    echo "<pre>";

    print_r($data);

    echo "</pre>";
}

/**
 * @author  Will
 *
 * @param array $array
 *
 * @return array
 */
function array_keys_multi(array $array)
{
    $keys = array();

    foreach ($array as $key => $value) {
        if (!is_int($key)) {
            $keys[] = $key;
        }

        if (is_array($array[$key])) {
            $keys = array_merge($keys, array_keys_multi($array[$key]));
        }
    }

    return $keys;
}

/**
 * PLEASE USE CARBON IF YOU CAN
 * @param $time
 *
 * @return string
 */
function relativeTime($time)
{

    $d[0] = array(1, "second");
    $d[1] = array(60, "minute");
    $d[2] = array(3600, "hour");
    $d[3] = array(86400, "day");
    $d[4] = array(604800, "week");
    $d[5] = array(2592000, "month");
    $d[6] = array(31104000, "year");

    $w = array();

    $return      = "";
    $now         = time();
    $diff        = ($now - $time);
    $secondsLeft = $diff;

    for ($i = 6; $i > -1; $i--) {
        $w[$i] = intval($secondsLeft / $d[$i][0]);
        $secondsLeft -= ($w[$i] * $d[$i][0]);
        if ($w[$i] != 0) {
            $return .= abs($w[$i]) . " " . $d[$i][1] . (($w[$i] > 1) ? 's' : '') . " ";
        }

    }

    $return .= ($diff > 0) ? "ago" : "left";

    return $return;
}

/**
 * @author  Will
 *
 * @param        $assoc
 * @param string $inglue
 * @param string $outglue
 *
 * @return string
 */
function implode_with_key($assoc, $inglue = '>', $outglue = ',')
{
    $return = '';

    foreach ($assoc as $tk => $tv) {
        $return .= $outglue . $tk . $inglue . $tv;
    }

    return substr($return, strlen($outglue));
}


/**
 * @author  Will
 *
 */
function csv_to_array($filename='', $delimiter=',',$assoc = true) {

    ini_set('auto_detect_line_endings',TRUE);
    if(!file_exists($filename) || !is_readable($filename))
        return FALSE;

    $header = NULL;
    $data = array();
    if (($handle = fopen($filename, 'r')) !== FALSE)
    {
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
        {
            if (!$header) {
                $header = $row;
            }
            else {
                if (count($header) > count($row)) {
                    $difference = count($header) - count($row);
                    for ($i = 1; $i <= $difference; $i++) {
                        $row[count($row) + 1] = $delimiter;
                    }
                }
                if ($assoc) {

                    $data[] = array_combine($header, $row);
                } else {
                    $data[] = $row;
                }
            }
        }
        fclose($handle);
    }
    return $data;
}

/**
 * Will return the number as long as it's within a minimum or maximum, otherwise
 * will return the minimum or maximum
 *
 * @param $original_number int
 * @param $minimum int
 * @param $maximum int
 *
 * @return mixed
 */
function within_limit($original_number,$minimum,$maximum) {

    if ($original_number < $minimum) {
        return $minimum;
    } elseif ($original_number > $maximum) {
        return $maximum;
    }

    return $original_number;
}

/**
 * @author  Will
 *
 * @param $number_to_test
 * @param $min
 * @param $max
 *
 * @throws Exception
 * @return bool
 */
function is_between($number_to_test,$min,$max) {

    if($min > $max) {
        throw new Exception('The min is greater than the max');
    }

    if($min === $max) {
        throw new Exception('The min and max are the same');
    }

    if ($number_to_test > $min AND $number_to_test < $max) {
        return true;
    }

    return false;

}


/**
 * Based on a number, get an arrow
 * @param $metric
 *
 * @return string
 */
function arrow($metric) {
    if ($metric > 0) {
        return '<i class="icon-arrow-up"></i>';
    } else if ($metric == 0) {
        return '<i class="icon-arrow-right"></i>';
    }
    return '<i class="icon-arrow-down"></i>';
}

/**
 * Based on a number, get an arrow
 * @param $metric
 *
 * @return string
 */
function sentiment($metric) {
    if ($metric > 0) {
        return 'positive';
    } else if ($metric == 0) {
        return 'neutral';
    }
    return 'negative';
}

/**
 * @param $bool
 *
 * @return string
 */
function bool_as_string($bool) {
    if($bool) {
        return 'True';
    }
    return 'False';
}

/**
 * Based on a number, return a colored tag
 * @param $metric
 *
 * @return string
 */
function formatAbsolute($metric)
{
    if ($metric > 0) {
        return "<span class='pos'>+" . formatNumberAbbreviation($metric, 0) . "</span>";
    } elseif ($metric == 0) {
        return "<span class='neg'> &nbsp;--</span>";
    } else {
        return "<span class='neg'>" . formatNumberAbbreviation($metric, 0) . "</span>";
    }
}

/**
 * Based on a number, return a colored tag
 * @param $metric
 *
 * @return string
 */
function formatAbsoluteAverage($metric)
{
    if ($metric > 0) {
        return "<span class='pos'>" . formatNumberAbbreviation($metric, 1) . "</span>";
    } elseif ($metric == 0) {
        return "<span class='neg'> &nbsp;--</span>";
    } else {
        return "<span class='neg'>" . formatNumberAbbreviation($metric, 1) . "</span>";
    }
}

/**
 * Based on a number, return an abbreviated form
 * @param $number
 *
 * @return string
 */
function formatNumberAbbreviation($number) {
    $abbrevs = array(12 => "T", 9 => "B", 6 => "M", 3 => "K", 0 => "");

    foreach($abbrevs as $exponent => $abbrev) {
        if($number >= pow(10, $exponent) || $number <= (pow(10, $exponent)*-1)) {
            if($exponent == 0) {
                return number_format($number / pow(10, $exponent),0) . $abbrev;
            } else {
                return number_format($number / pow(10, $exponent),1) . $abbrev;
            }
        }
    }
}

/**
 * Returns a pretty category name from a default Pinterest category string
 *
 * @param $category
 *
 * @return string
 */
function prettyCategoryName($category)
{

    if ($category == "womens_fashion") {
        $pretty_category = "womens fashion";
    } elseif ($category == "diy_crafts") {
        $pretty_category = "diy & crafts";
    } elseif ($category == "health_fitness") {
        $pretty_category = "health & fitness";
    } elseif ($category == "holidays_events") {
        $pretty_category = "holidays & events";
    } elseif ($category == "none") {
        $pretty_category = "not specified";
    } elseif ($category == "holiday_events") {
        $pretty_category = "holidays & events";
    } elseif ($category == "home_decor") {
        $pretty_category = "home decor";
    } elseif ($category == "food_drink") {
        $pretty_category = "food & drink";
    } elseif ($category == "film_music_books") {
        $pretty_category = "film, music & books";
    } elseif ($category == "hair_beauty") {
        $pretty_category = "hair & beauty";
    } elseif ($category == "cars_motorcycles") {
        $pretty_category = "cars & motorcycles";
    } elseif ($category == "science_nature") {
        $pretty_category = "science & nature";
    } elseif ($category == "mens_fashion") {
        $pretty_category = "mens fashion";
    } elseif ($category == "illustrations_posters") {
        $pretty_category = "illustrations & posters";
    } elseif ($category == "art_arch") {
        $pretty_category = "art & architecture";
    } elseif ($category == "wedding_events") {
        $pretty_category = "weddings & events";
    } else {
        $pretty_category = $category;
    }

    return $pretty_category;

}

if (!function_exists('fudgeDaNumbersALittle')) {
    /**
     * @param     $array
     * @param int $fudge
     *
     * @return bool|float
     */
    function fudgeDaNumbersALittle(&$array, $fudge = 0)
    {
        if ($fudge == 0) {
            $fudge = [1, 2];
        }
        if (is_array($fudge)) {
            $fudge = float_rand($fudge[0], $fudge[1]);
        }

        if (is_numeric($array)) {
            return (float)$array * $fudge;
        }

        if (is_array($array)) {
            foreach ($array as & $value) {
                $value = fudgeDaNumbersALittle($value, $fudge);
            }

            return $array;
        }

        return $array;
    }
}
if (!function_exists('float_rand')) {
    /**
     * Generate Float Random Number
     *
     * @param float $min   Minimal value
     * @param float $max   Maximal value
     * @param int   $round The optional number of decimal digits to round to. default 0 means not round
     *
     * @return float Random float value
     */
    function float_rand($min, $max, $round = 0)
    {
        //validate input
        if ($min > $max) {
            $min = $max;
            $max = $min;
        }

        $randomfloat = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        if ($round > 0)
            $randomfloat = round($randomfloat, $round);

        return $randomfloat;
    }
}

/*
|--------------------------------------------------------------------------
| STATISTICAL FUNCTION
|--------------------------------------------------------------------------
*/

if (!function_exists('stats_standard_deviation')) {
    /**
     * This user-land implementation follows the implementation quite strictly;
     * it does not attempt to improve the code or algorithm in any way. It will
     * raise a warning if you have fewer than 2 values in your array, just like
     * the extension does (although as an E_USER_WARNING, not E_WARNING).
     *
     * @param array $a
     * @param bool $sample [optional] Defaults to false
     * @return float|bool The standard deviation or false on error.
     */
    function stats_standard_deviation(array $a, $sample = false) {
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);
            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);
            return false;
        }
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double) $val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
            --$n;
        }
        return sqrt($carry / $n);
    }
}






