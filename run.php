<?php
include __DIR__ . '/vendor/autoload.php';

use HappyCode\Core\ApiRunner;
use HappyCode\Core\Boot;

$apiRunner = new ApiRunner(Boot::Load("config/endpoints.yml"));
$apiRunner->run()->report();

