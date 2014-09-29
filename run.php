<?php
include __DIR__ . '/vendor/autoload.php';

use HappyCode\Core\ApiRunner;
use HappyCode\Core\Boot;

$apiRunner = new ApiRunner(Boot::Load("config/endpoints.yml"));
$report = $apiRunner->run();

echo PHP_EOL . PHP_EOL . "====== REPORT ====== " . PHP_EOL . PHP_EOL;


$resultRow = function($name = null, $time = null, $st = null, $r_len = null, $ct = null){
    $pad = ($name == null) ? "+" : " ";
    return sprintf("+ %s + %s + %s + %s + %s +" .  PHP_EOL,
        str_pad($name, 20, $pad, STR_PAD_RIGHT),
        str_pad($time, 15, $pad, STR_PAD_BOTH),
        str_pad($st, 10, $pad, STR_PAD_BOTH),
        str_pad($r_len, 20, $pad, STR_PAD_BOTH),
        str_pad($ct, 20, $pad, STR_PAD_RIGHT)
    );
};

echo $resultRow();
echo $resultRow("Endpoint", "(Time )secs", "Status", "Content-Length", "Type");
echo $resultRow();
foreach($report as $callReport) {
    if($callReport['success']){
        echo $resultRow($callReport['name'], $callReport['time'], $callReport['status_code'], $callReport['response_length'], $callReport['content_type']);
    }else{
        echo sprintf("+ %s: ERROR: (%s) +" .  PHP_EOL, str_pad($callReport['name'], 20, " ", STR_PAD_RIGHT), $callReport['error']);
    }
}
echo $resultRow();
