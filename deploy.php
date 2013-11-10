#!/usr/bin/php
<?PHP

require_once('vendor/autoload.php');

use Symfony\Component\Console\Application;

$application = new Application();
$application->add( new DeployCommand );
$application->add( new RollbackCommand );
$application->run();
