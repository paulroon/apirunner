<?php
include __DIR__ . '/vendor/autoload.php';

use HappyCode\Core\ApiRunner;
use HappyCode\Core\Boot;

$token = isset($argv[1]) ? $argv[1] : false;

$apiRunner = new ApiRunner(Boot::Load("config/endpoints.yml"), $token);

$apiRunner->run()->report();




