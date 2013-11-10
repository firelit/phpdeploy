<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackCommand extends Command {

    protected function configure() {

        $this
            ->setName('rollback')
            ->setDescription('Rollback to the previous version')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Location of configuration file')
            ->addOption('history', 'y', InputOption::VALUE_REQUIRED, 'Location of history file')
            ->addOption('web-root', 'w', InputOption::VALUE_REQUIRED, 'Root web folder')
            ->addOption('run-cmds', 'r', InputOption::VALUE_NONE, 'Re-run pre-deploy commands on old version');

    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $configFile = $input->getOption('config');

        try {
            
            $config = new DeployConfig($configFile);

        } catch (Exception $e) {
            $output->writeln('<error>'. $e->getMessage() .'</error>');
            exit(1);
        }

        $repo = $input->getOption('repo');
        if (!$repo) $repo = $config->repo;

        if (!$repo) {
            $output->writeln('<error>Repo not specified at CLI nor in config file.</error>');
            exit(1);
        }

        $historyFile = $input->getOption('history');
        if (!$historyFile) $historyFile = $config->history;

        if (!$historyFile) {
            $output->writeln('<error>History file not specified at CLI nor in config file.</error>');
            exit(1);
        }

        try {
            
            $history = new DeployHistory($historyFile);

        } catch (Exception $e) {
            $output->writeln('<error>'. $e->getMessage() .'</error>');
            exit(1);
        }

        if (sizeof($history->history) < 2) {
            $output->writeln('<error>No version available for rollback.</error>');
            exit(1);
        }

        $webRoot = $input->getOption['web-root'];
        if (!$webRoot) $webRoot = $config->web;
        if (!$webRoot) $webRoot = self::DEFAULT_WEB_ROOT;

        $remDeploy = array_shift($history->history);
        $lastDeploy = $history->history[0];
        $tag = $lastDeploy['tag'];

        $workingDir = dirname($webRoot);
        $newFolder = 'html_'. preg_replace('/[^A-Za-z0-9\.\-_]+/', '_', $tag);
        $newFolderAbs = $workingDir . DIRECTORY_SEPARATOR . $newFolder;

        if (!is_dir($newFolderAbs)) {
            $output->writeln($newFolderAbs);
            $output->writeln('<error>Previous version does not exists. Rollback failed.</error>');
            exit(1);
        }

        $output->writeln('<info>Initiating Rollback...</info>');
        $output->writeln('Rolling back to: '. $tag);

        if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
            $output->writeln('First deployed: '. $lastDeploy['date']);
            $output->writeln('Target folder: '. $newFolderAbs);
        }

        $run = $input->getOption('run-cmds');

        if ($run && is_array($config->cmds) && sizeof($config->cmds)) {

            $output->writeln('<info>Re-Running Pre-Deploy Commands...</info>');

            foreach ($config->cmds as $i => $command) {
                
                $command = str_replace('{FOLDER}', $newFolderAbs, $command);
                
                if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                    $output->writeln('['.$i.'] '. $command);
                }

                unset($out);
                exec($command, $out, $res);
                    
                if ($res !== 0) {
                    $output->writeln('<error>Error exit on pre-deploy command</error>');
                    $output->writeln($command);
                    foreach ($out as $oneline) $output->writeln($oneline);
                    exit(1);
                }

            }

        } elseif (!$run) {
            $output->writeln('Skipping pre-deploy commands.');
        } elseif (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
            $output->writeln('No pre-deploy commands. Skipping.');
        }

        if (!is_link($webRoot) && is_dir($webRoot)) {
    
            $output->writeln('<info>Moving Current Web-Root...</info>');

            $exec = 'mv '. $webRoot .' '. dirname($webRoot) . DIRECTORY_SEPARATOR .'html_orig';

            if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity())
                $output->writeln($exec);

            unset($out);
            exec($exec, $out, $res);
            
            if ($res !== 0) {
                $output->writeln('<error>Could not move web root folder</error>');
                $output->writeln($command);
                foreach ($out as $oneline) $output->writeln($oneline);
                exit(1);
            }

        } elseif (is_link($webRoot)) {

            $output->writeln('<info>Un-Linking Web-Root...</info>');
            
            $exec = 'unlink '. $webRoot;
            
            if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity())
                $output->writeln($exec);

            unset($out);
            exec($exec, $out, $res);

            if ($res !== 0) {
                $output->writeln('<error>Web-root\'s symbolic link could not be removed</error>');
                $output->writeln($command);
                foreach ($out as $oneline) $output->writeln($oneline);
                exit(1);
            }

        }

        $output->writeln('<info>Re-Linking Web-Root...</info>');

        $exec = 'ln -s '. $newFolderAbs .' '. $workingDir . DIRECTORY_SEPARATOR .'html';

        if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity())
            $output->writeln($exec);

        unset($out);
        exec($exec, $out, $res);

        if ($res !== 0) {
            $output->writeln('<error>Could not symbolically link new web-root</error>');
            $output->writeln($command);
            foreach ($out as $oneline) $output->writeln($oneline);
            exit(1);
        }

        $output->writeln('<info>Restarting Web Server...</info>');

        $exec = 'apachectl configtest';

        if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity())
            $output->writeln($exec);

        unset($out);
        exec($exec, $out, $res);

        $errorExit = false;

        if ($res !== 0) {

            $output->writeln('<error>Apache configuration failed test; web server not restarted</error>');
            $output->writeln($command);
            foreach ($out as $oneline) $output->writeln($oneline);

            $errorExit = true;

        } else {

            $exec = 'service httpd restart';

            if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity())
                $output->writeln($exec);

            unset($out);
            exec($exec, $out, $res);

            if ($res !== 0) {
                $output->writeln('<error>Apache restart failed!!</error>');
                $output->writeln($command);
                foreach ($out as $oneline) $output->writeln($oneline);

                $errorExit = true;
            }

        }

        $output->writeln('<info>Rollback Complete!</info>');

        $removeOld = !$this->getOption('no-rm');

        $res = $history->save();

        if (!$res) {
            
            $output->writeln('<error>History file could not be updated!</error>');
            
            if ($removeOld) 
                $output->writeln('Skipping removal of old deployments.');

            $errorExit = true;

        } elseif ($removeOld) {

            $output->writeln('<info>Removing Old Deployments...</info>');

            $exec = 'rm -rf '. $remDeploy['dir'];

            if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity())
                $output->writeln($exec);

            unset($out);
            exec($exec, $out, $res);

            if ($res !== 0) {
                $output->writeln('<error>Old deployment could not be removed.</error>');
                $output->writeln($command);
                foreach ($out as $oneline) $output->writeln($oneline);

                $errorExit = true;
            }
              

        }

        $output->writeln('<info>Done.</info>');

        exit($errorExit ? 1 : 0);

    }

}