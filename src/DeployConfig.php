<?PHP

class DeployConfig {
	
	const DEFAULT_FILE = '/var/www/deploy.json';

	protected
		$file = false,
		$repo = false,
		$history = false,
		$cmds = false,
		$web = false;

	public function __construct($file = false) {

		if (!$file) {

			// Try a few different possibilities
			$try = array(
				getcwd() . 'deploy.json',
				getcwd() . 'deploy_config.json',
				'/var/www/deploy.json',
				'/var/www/deploy_config.json'
			);

			foreach ($try as $file) {
				if (file_exists($file)) break;
			}

			if (!file_exists($file))
				$file = self::DEFAULT_FILE;

		}

		$this->loadFile($file);

	}

	protected function loadFile($file) {

		$this->file = $file;

		if (!file_exists($file) || !is_readable($file)) {
        	throw new InvalidFileException('Configuration file does not exist or is not readable.');
		}

		$config = file_get_contents($file);

		if (!$config || !strlen($config)) 
			throw new InvalidFileException('Configuration file could not be read or is empty.');

		$config = json_decode($config, true);

		if (is_null($config) || !is_array($config)) 
			throw new InvalidFileException('Configuration file not valid JSON or is empty.');

		if (isset($config['repo']))
			$this->repo = $config['repo'];

		if (isset($config['history']))
			$this->history = $config['history'];

		if (isset($config['cmds']))
			$this->cmds = $config['cmds'];

		if (isset($config['web-root']))
			$this->web = $config['web-root'];

	}

	public function __get($var) {
		return $this->$var;
	}

}