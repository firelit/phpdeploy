#!/usr/bin/php
<?PHP

define('DEFAULT_WEB_ROOT', '/var/www/html'); // What folder Apache is serving up, will become a symbolic link
define('DEFAULT_CONFIG_FILE', '/var/www/deploy.json'); // Where to find the deploy.json configuration
define('DEFAULT_HISTORY_FILE', '/var/www/deploy_history.json'); // Where to find the deploy history
define('MAX_HISTORY', 10); // Only store the last 10 deployments in history

// Remove script name
array_shift($argv);

$webRoot = DEFAULT_WEB_ROOT;
$configFile = DEFAULT_CONFIG_FILE;
$historyFile = DEFAULT_HISTORY_FILE;
$historyCLI = false;
$remOld = true;
$tag = false;

do {
	
	$arg = current($argv);
	
	if ($arg === false) // No more arguements
		break;
		
	if ($arg == '-f') {
		// Config file option
		
		$configFile = next($argv);
		
		if ($configFile === false)
			errorExit('Invalid config file specified.');
		
	} elseif ($arg == '-h') {
		// History file option
		
		$historyFile = next($argv);
		
		if ($historyFile === false)
			errorExit('Invalid history file specified.');
		
		$historyCLI = true;
		
	} elseif ($arg == '-w') {
		// Web root option
		
		$webRoot = next($argv);
		
		if ($webRoot === false)
			errorExit('Invalid web root specified.');
		
	} elseif ($arg == '--no-rm') {
		// No-remove old deployments
		
		$remOld = false;
		
	} elseif (!$tag) {
		// Tag/commit/branch not set, must be this
		
		$tag = $arg;
		
	} else {
		
		errorExit('Invalid argument specified: '. $arg);
		
	}
	
} while (next($argv));

if (!file_exists($configFile) || !is_readable($configFile)) 
	errorExit('Config file does not exist or is not readable: '. $configFile);

$config = file_get_contents($configFile);
$config = json_decode($config, true);

if (!is_array($config)) errorExit('Config file not valid JSON or is empty.');
if (!isset($config['repo']) || !strlen($config['repo'])) errorExit('Repository not specified in config file.');

if (!$historyCLI && isset($config['history']) && strlen($config['history'])) $historyFile = $config['history'];

if (file_exists($historyFile) && is_readable($historyFile)) {
	
	$history = file_get_contents($historyFile);
	
	$history = json_decode($history, true);
		
}

if (!$history || !is_array($history)) $history = array();

if (strtolower($tag) == 'rollback') {
	
	if (!file_exists($historyFile) || !is_readable($historyFile))
		errorExit('History file does not exist or is not readable: '. $historyFile);

	$rollback = true;
	
	if (!sizeof($history))
		errorExit('History file contains no records to rollback to: '. $historyFile);
		
	array_shift($history);
	
	$targetDeploy = $history[0];
	
	$tag = $targetDeploy['tag'];
	
	fwrite(STDOUT, "NOTE: Rolling back..." ."\n");
	
} else $rollback = false;

if (!$tag) {
	
	$tag = 'default/master';
	
	$res = shell_exec('git ls-remote '. $config['repo']);
	
	if (is_null($res)) 
		errorExit('Config could not connect to repo: ' . $config['repo']);
	
	$stringArray = explode("\n", $res);
	$lineArray = explode("\t", $stringArray[0]);
	$tag = trim($lineArray[0]);
	
	if (!preg_match('/^[A-fa-f0-9]{40}$/', $tag)) 
		errorExit("Could not identify last commit", implode("\n", $stringArray));
		
}
 
fwrite(STDOUT, "Deploying repository: ". $config['repo'] ."\n");
fwrite(STDOUT, "Selected tag/ref: ". $tag ."\n");

$workingDir = dirname($webRoot);
$newFolder = 'html_'. preg_replace('/[^A-Za-z0-9\.\-_]+/', '_', $tag);
$newFolderAbs = $workingDir . DIRECTORY_SEPARATOR . $newFolder;

fwrite(STDOUT, "Target folder: ". $newFolderAbs ."\n");

if (!is_dir($newFolderAbs)) {
	// Create and clone the directory

	fwrite(STDOUT, "Cloning project..." ."\n");

	exec('git clone '. $config['repo'] .' '. $newFolderAbs, $out, $res);
	
	if ($res !== 0)
		errorExit("Git clone problem", implode("\n", $out));
	
	$newClone = true;
	
} else
	$newClone = false;

if (!chdir($newFolderAbs)) 
	errorExit('Could not change directory to '. $newFolderAbs);

if ($newClone) {
	
	fwrite(STDOUT, "Checking out tag..." ."\n");
	
	exec('git checkout '. $tag, $out, $res);
	
	if ($res !== 0)
		errorExit("Git checkout problem", implode("\n", $out));

} else
	fwrite(STDOUT, "Folder exists, skipping clone and checkout..." ."\n");

if (is_array($config['cmds']) && sizeof($config['cmds'])) {
	
	fwrite(STDOUT, "Running pre-deploy commands..." ."\n");
	
	foreach ($config['cmds'] as $command) {
		
		$command = str_replace('{FOLDER}', $newFolderAbs, $command);
		
		fwrite(STDOUT, " -> ". $command ."\n");
		
		exec($command, $out, $res);
			
		if ($res !== 0)
			errorExit("Command could not be executed", implode("\n", $out));

	}
	
}

if (!is_link($webRoot) && is_dir($webRoot)) {
	
	fwrite(STDOUT, "Moving current web-root..." ."\n");
	
	exec('mv '. $webRoot .' '. dirname($webRoot) . DIRECTORY_SEPARATOR .'html_orig', $out, $res);
	
	if ($res !== 0) 
		errorExit("Could not move web root folder", implode("\n", $out));

}

if (is_link($webRoot)) {

	fwrite(STDOUT, "Un-linking web folder..." ."\n");
	
	exec('unlink '. $webRoot, $out, $res);
	
	if ($res !== 0)
		errorExit("Current symbolic link could not be removed", implode("\n", $out));

}

fwrite(STDOUT, "Re-linking web folder..." ."\n");

exec('ln -s '. $newFolderAbs .' '. $workingDir . DIRECTORY_SEPARATOR .'html', $out, $res);

if ($res !== 0)
	errorExit("Could not symbolically link new folder", implode("\n", $out));

fwrite(STDOUT, "\n\n". "Deploy complete!" ."\n");

if (!$rollback) {
		
	$targetDeploy = array(
		'tag' => $tag,
		'dir' => $newFolderAbs,
		'date' => date('r')
	);
	
	array_unshift($history, $targetDeploy);
	
	$oldHistory = array_slice($history, MAX_HISTORY);
	$history = array_slice($history, 0, MAX_HISTORY);
	
} else 
	$oldHistory = array();

$res = file_put_contents($historyFile, json_encode($history));

if (!$res) fwrite(STDOUT, "\n". "WARNING: History file could not be written. Rollback command will not work.");

if ($remOld && is_array($oldHistory) && sizeof($oldHistory)) {
	
	fwrite(STDOUT, "\n". "Removing old deployments..." ."\n");
	
	foreach ($oldHistory as $remDeploy) {
		
		exec('rm -rf '. $remDeploy['dir'], $out, $res);
		
		if ($res !== 0)
			fwrite(STDOUT, "NOTE: Old deployment '". $remDeploy['dir'] ."' could not be removed." ."\n");
			
	}
	
	fwrite(STDOUT, "Done." ."\n");
	
}

function errorExit($string, $errInfo = false) {
	fwrite(STDERR, 'Error: '. $string ."\n");
	if ($errInfo) fwrite(STDERR, $errInfo ."\n");
	exit(1);
}