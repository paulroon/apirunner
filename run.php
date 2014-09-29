<?php
include __DIR__ . '/vendor/autoload.php';

use HappyCode\Core\ApiRunner;
use HappyCode\Core\Boot;

$apirunner = new ApiRunner(Boot::Load("config/endpoints.yml"));
$report = $apirunner->run();

echo PHP_EOL . PHP_EOL . " ====== REPORT ====== " . PHP_EOL . PHP_EOL;
var_export($report);