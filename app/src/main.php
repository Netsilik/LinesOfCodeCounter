<?php
error_reporting(~0);

define('VERSION', '1.1.0');

require_once(__DIR__.'/../../vendor/autoload.php');

$app = new \Netsilik\Util\Loc();
$app->main($argc, $argv);
