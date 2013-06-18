#!/usr/bin/php
<?PHP

define('DEFAULT_WEB_ROOT', '/var/www/html'); // What folder Apache is serving up, will become a symbolic link
define('DEFAULT_CONFIG_FILE', '/var/www/deploy.json'); // Where to find the deploy.json configuration

// Remove script name
array_shift($argv);

$webRoot = DEFAULT_WEB_ROOT;
$configFile = DEFAULT_CONFIG_FILE;
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
		
	} elseif ($arg == '-w') {
		// Web root option
		
		$webRoot = next($argv);
		
		if ($webRoot === false)
			errorExit('Invalid web root specified.');
		
	} elseif (!$tag) {
		// Tag/commit/branch not set, must be this
		
		$tag = $arg;
		
	} else {
		
		errorExit('Invalid argument specified: '. $arg);
		
	}
	
} while (next($argv));

if (!file_exists($configFile) || !is_readable($configFile)) errorExit('Config file does not exist or is not readable: '. $configFile ."\n");

$config = file_get_contents($configFile);
$config = json_decode($config, true);

if (!is_array($config)) errorExit('Config file not valid JSON or is empty.' ."\n");
if (!isset($config['repo']) || !strlen($config['repo'])) errorExit('Repository not specified in config file.' ."\n");

if (!$tag) {
	
	$tag = 'default/master';
	
	$res = shell_exec('git ls-remote '. $config['repo']);
	
	if (is_null($res)) 
		errorExit('Config could not connect to repo: ' . $config['repo']);
	
	$stringArray = explode("\n", $res);
	$lineArray = explode("\t", $stringArray[0]);
	$tag = trim($lineArray[0]);
	
	if (!preg_match('/^[A-fa-f0-9]{40}$/', $tag)) 
		errorExit("Error: Could not identify last commit", implode("\n", $stringArray));
		
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
		errorExit("Error: Git clone problem", implode("\n", $out));
	
}

if (!chdir($newFolderAbs)) 
	die('Error: Could not change directory to '. $newFolderAbs ."\n");

fwrite(STDOUT, "Checking out tag..." ."\n");

exec('git checkout '. $tag, $out, $res);

if ($res !== 0)
	errorExit("Error: Git checkout problem", implode("\n", $out));

if (is_array($config['cmds']) && sizeof($config['cmds'])) {
	
	fwrite(STDOUT, "Running pre-deploy commands..." ."\n");
	
	foreach ($config['cmds'] as $command) {
		
		$command = str_replace('{FOLDER}', $newFolderAbs, $command);
		
		fwrite(STDOUT, " -> ". $command ."\n");
		
		exec($command, $out, $res);
			
		if ($res !== 0)
			errorExit("Error: Command could not be executed", implode("\n", $out));

	}
	
}

if (!is_link($webRoot) && is_dir($webRoot)) {
	
	fwrite(STDOUT, "Moving current web-root..." ."\n");
	
	exec('mv '. $webRoot .' '. dirname($webRoot) . DIRECTORY_SEPARATOR .'html_orig', $out, $res);
	
	if ($res !== 0) 
		errorExit("Error: Could not move web root folder", implode("\n", $out));

}

if (is_link($webRoot)) {

	fwrite(STDOUT, "Un-linking web folder..." ."\n");
	
	exec('unlink '. $webRoot, $out, $res);
	
	if ($res !== 0)
		errorExit("Error: Current symbolic link could not be removed", implode("\n", $out));

}

fwrite(STDOUT, "Re-linking web folder..." ."\n");

exec('ln -s '. $newFolderAbs .' '. $workingDir . DIRECTORY_SEPARATOR .'html', $out, $res);

if ($res !== 0)
	errorExit("Error: Could not symbolically linking new folder", implode("\n", $out));

fwrite(STDOUT, "\n\n". "Deploy complete!" ."\n");

function errorExit($string, $errInfo = false) {
	fwrite(STDERR, $string ."\n");
	if ($errInfo) fwrite(STDERR, $errInfo ."\n");
	exit(1);
}