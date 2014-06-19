<?php
date_default_timezone_set('America/Chicago');
@session_start();
//    $conn = mysql_connect("localhost", "root", "root");
//mysql_select_db("datastore",$conn);
$conn = mysql_connect("162.209.31.103", "root", "N@3!zpq1$51gto");
mysql_select_db("datastore",$conn);
mysql_set_charset("UTF8", $conn);

include_once 'config.php';
include_once 'classes/databaseinstance.php';

$pinterest = new Pinterest($conn);
