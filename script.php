<?php declare(strict_types=1);

require_once 'vendor/autoload.php';
require_once 'options.php';

use HMnet\Publisher2\CLI;
use HMnet\Publisher2\CoreController;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$cli = new CLI($CLI_OPTIONS, $argv);

$action = $cli->getAction();
$args = $cli->getArgs();

$controller = new CoreController();
$controller->run($action, $args);