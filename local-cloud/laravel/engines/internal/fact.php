<?php
/**
 * Displays a fact relevant to TW
 *
 * @author  Will
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

$beaver_facts = Config::get('facts.beaver_facts');
$fact_key     = array_rand($beaver_facts);

echo $beaver_facts[$fact_key];
