#!/usr/bin/php
<?PHP

require_once('vendor/autoload.php');

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

$application = new Application();
$application->add(new DeployCommand);
$application->add(new RollbackCommand);
$application->run();
