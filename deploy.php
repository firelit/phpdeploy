#!/usr/bin/php
<?PHP

define('WEB_ROOT', '/var/www/html'); // What folder Apache is serving up, will become a symbolic link
define('CONFIG_FILE', '/var/www/deploy.json'); // Where to find the deploy.json configuration

if (!isset($argv[1])) die('No commit tag (eg, version number).' ."\n");

$tag = $argv[1];

if (!file_exists(CONFIG_FILE) || !is_readable(CONFIG_FILE)) die('Config file does not exist or is not readable: '. CONFIG_FILE ."\n");

$config = file_get_contents(CONFIG_FILE);
$config = json_decode($config, true);

if (!is_array($config)) die('Config file not valid JSON or is empty.' ."\n");
if (!isset($config['repo']) || !strlen($config['repo'])) die('Repository not specified in config file.' ."\n");

fwrite(STDOUT, "Deploying repository: ". $config['repo'] ."\n");
fwrite(STDOUT, "Selected tag: ". $tag ."\n");

$workingDir = dirname(WEB_ROOT);
$newFolder = 'html_'. preg_replace('/[^A-Za-z0-9\.\-_]+/', '_', $tag);
$newFolderAbs = $workingDir . DIRECTORY_SEPARATOR . $newFolder;

fwrite(STDOUT, "Target folder: ". $newFolderAbs ."\n");

if (!is_dir($newFolderAbs)) {
	// Create and clone the directory

	echo "Cloning project..." ."\n";

	exec('git clone '. $config['repo'] .' '. $newFolderAbs, $out, $res);
	
	if ($res !== 0) {
		fwrite(STDOUT, "Error: Git clone problem\n");
		fwrite(STDOUT, implode("\n", $out) ."\n");
		die;
	}
	
}

if (!chdir($newFolderAbs)) 
	die('Error: Could not change directory to '. $newFolderAbs ."\n");

echo "Checking out tag..." ."\n";

exec('git checkout '. $tag, $out, $res);

if ($res !== 0) {
	fwrite(STDOUT, "Error: Git checkout problem\n");
	fwrite(STDOUT, implode("\n", $out) ."\n");
	die;
}

if (is_array($config['cmds']) && sizeof($config['cmds'])) {
	
	echo "Running pre-deploy commands..." ."\n";
	
	foreach ($config['cmds'] as $command) {
		
		$command = str_replace('{FOLDER}', $newFolderAbs, $command);
		
		fwrite(STDOUT, " -> ". $command ."\n");
		
		exec($command, $out, $res);
			
		if ($res !== 0) {
			fwrite(STDOUT, "Error: Command could not be executed\n");
			fwrite(STDOUT, implode("\n", $out) ."\n");
			die;
		}

	}
	
}

if (!is_link(WEB_ROOT) && is_dir(WEB_ROOT)) {
	
	echo "Moving current web-root..." ."\n";
	
	exec('mv '. WEB_ROOT .' '. dirname(WEB_ROOT) . DIRECTORY_SEPARATOR .'html_orig', $out, $res);
	
	if ($res !== 0) {
		fwrite(STDOUT, "Error: Could not move web root folder\n");
		fwrite(STDOUT, implode("\n", $out) ."\n");
		die;
	}

}

if (is_link(WEB_ROOT)) {

	echo "Un-linking web folder..." ."\n";
	
	exec('unlink '. WEB_ROOT, $out, $res);
	
	if ($res !== 0) {
		fwrite(STDOUT, "Error: Current symbolic link could not be removed\n");
		fwrite(STDOUT, implode("\n", $out) ."\n");
		die;
	}

}

echo "Re-linking web folder..." ."\n";

exec('ln -s '. $newFolderAbs .' '. $workingDir . DIRECTORY_SEPARATOR .'html', $out, $res);

if ($res !== 0) {
	fwrite(STDOUT, "Error: Could not symbolically linking new folder\n");
	fwrite(STDOUT, implode("\n", $out) ."\n");
	die;
}

echo "\n\n". "Deploy complete!" ."\n";