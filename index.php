<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
ini_set('max_execution_time', 300);
require_once(__DIR__."/Parser.php");

if (isset($argv)) {
    $parameters = $argv[1]."&".$argv[2];
    parse_str($parameters,$params);
}
$operation = (isset($params['op']))? $params['op'] : $_GET['op'];
$rss_id = (isset($params['rss_id']))? $params['rss_id'] : $_GET['rss_id'];
$start_date = (isset($_GET['start_date']))? $_GET['start_date'] : date("Y-m-d");
$end_date = (isset($_GET['end_date']))? $_GET['end_date'] : null;


switch ($operation) {
    case 'parse':
        $parser = new Parser($rss_id);
        
        break;
    
    case 'feed':
        # code...
        break;  
    
    default:
        echo "Invalid/Missing operand";
        break;
}
?>