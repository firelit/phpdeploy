<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends Command {

    const DEFAULT_WEB_ROOT = '/var/www/html';

    protected function configure() {
        
        $this
            ->setName('deploy')
            ->setDescription('Deploy a new version')
            ->addArgument('tag|branch', InputArgument::OPTIONAL, 'A GIT tag or branch to deploy')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Location of configuration file')
            ->addOption('history', 'y', InputOption::VALUE_REQUIRED, 'Location of history file')
            ->addOption('web-root', 'w', InputOption::VALUE_REQUIRED, 'Root web folder')
            ->addOption('repo', 'r', InputOption::VALUE_REQUIRED, 'A GIT repo to deploy from')
            ->addOption('no-rm', null, InputOption::VALUE_NONE, 'Do not remove old deployments');

    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $configFile = $input->getOption('config');

        try {
            
            $config = new DeployConfig($configFile);

        } catch (InvalidFileException $e) {
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

        try {
            
            $history = new DeployHistory($historyFile);

        } catch (InvalidFileException $e) {
            $output->writeln('<error>'. $e->getMessage() .'</error>');
            exit(1);
        }

        $webRoot = $input->getOption('web-root');
        if (!$webRoot) $webRoot = $config->web;
        if (!$webRoot) $webRoot = self::DEFAULT_WEB_ROOT;

        $tag = $input->getArgument('tag|branch');

        if (!$tag) {

            $res = shell_exec('git ls-remote '. $repo);

            if (is_null($res)) {
                $output->writeln('<error>Config could not connect to repo: '. $repo .'</error>');
                exit(1);
            }

            $stringArray = explode("\n", $res);
            $lineArray = explode("\t", $stringArray[0]);
            $tag = trim($lineArray[0]);

            if (!preg_match('/^[A-fa-f0-9]{40}$/', $tag)) {
                $output->writeln('<error>Config could not identify last commit of repo: '. $repo .'</error>');
                exit(1);
            }

        }

        $workingDir = dirname($webRoot);
        $newFolder = 'html_'. preg_replace('/[^A-Za-z0-9\.\-_]+/', '_', $tag);
        $newFolderAbs = $workingDir . DIRECTORY_SEPARATOR . $newFolder;

        if (!is_dir($newFolderAbs)) {
            // Create and clone the directory

            $output->writeln('<info>Cloning Repository...</info>');

            $exec = 'git clone --recurse-submodules -b '. $tag .' --depth 1 '. $repo .' '. $newFolderAbs;

            if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity())
                $output->writeln($exec);
            
            unset($out);
            exec($exec, $out, $res);
            
            if ($res !== 0) {
                $output->writeln('<error>GIT clone error</error>');
                foreach ($out as $oneline) $output->writeln($oneline);
                exit(1);
            }
            
        } else {

            $output->writeln('Target folder already exists. Skipping clone.');

        }

        if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
            $output->writeln('Repo: '. $repo);
            $output->writeln('Tag/branch: '. $tag);
            $output->writeln('Target folder: '. $newFolderAbs);
        }

        if (is_array($config->cmds) && sizeof($config->cmds)) {

            $output->writeln('<info>Running Pre-Deploy Commands...</info>');

            foreach ($config->cmds as $i => $command) {
                
                $command = str_replace('{FOLDER}', $newFolderAbs, $command);
                $command = str_replace('{TAG}', $tag, $command);
                
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

        $output->writeln('<info>Deploy Complete!</info>');

        $removeOld = !$input->getOption('no-rm');

        $history->addHistory($tag, $newFolderAbs, $removeOld);
        $res = $history->save();
        
        if (!$res) {
            
            $output->writeln('<error>History file could not be updated!</error>');
            
            if ($removeOld) 
                $output->writeln('Skipping removal of old deployments.');

            $errorExit = true;

        } elseif ($removeOld) {

            if (sizeof($history->old)) {
    
                $output->writeln('<info>Removing Old Deployments...</info>');

                foreach ($history->old as $remDeploy) {
                    
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

            }

        }

        $output->writeln('<info>Done.</info>');

        exit($errorExit ? 1 : 0);

    }
}
