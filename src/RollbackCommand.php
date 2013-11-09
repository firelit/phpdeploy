<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('rollback')
            ->setDescription('Rollback to a previous version')
            ->addArgument('tag', InputArgument::OPTIONAL, 'A GIT tag to deploy')
            ->addArgument('branch', InputArgument::OPTIONAL, 'A branch to deploy')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Location of configuration file')
            ->addOption('history', 'y', InputOption::VALUE_REQUIRED, 'Location of history file')
            ->addOption('web-root', 'w', InputOption::VALUE_REQUIRED, 'Root web folder')
            ->addOption('repo', 'r', InputOption::VALUE_REQUIRED, 'A GIT repo to deploy from');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<error>Not yet done!</error>');
    }
}