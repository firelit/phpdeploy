<?PHP

class DeployConfig {
	
	const DEFAULT_CONFIG_FILE = '/var/www/deploy.json';

	protected
		$file = false,
		$repo = false,
		$history = false,
		$cmds = false;

	public function __construct($file = false) {

		$this->file = $file;

		if (!$file) {
			$file = self::DEFAULT_CONFIG_FILE;
			$usingDefault = true;
		} else $usingDefault = false;

		if (!file_exists($file) || !is_readable($file)) {
			if ($usingDefault) return; // No exception if it is the default file
        	throw new Exception('Configuration file is not readable.');
		}

		$this->readInFile();

	}

	protected function readInFile() {

		$config = file_get_contents($configFile);

		if (!$config) 
			throw new Exception('Configuration file could not be read or is empty.');

		$config = json_decode($config, true);

		if (!is_array($config)) 
			throw new Exception('Configuration file not valid JSON.');

		if (isset($config['repo']))
			$this->repo = $config['repo'];

		if (isset($config['history']))
			$this->history = $config['history'];

		if (isset($config['cmds']))
			$this->cmds = $config['cmds'];

	}

	public function __get($var) {
		return $this->$var;
	}

}