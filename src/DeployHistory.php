<?PHP

class DeployHistory {

	const MAX_HISTORY = 15;
	const DEFAULT_FILE = '/var/www/history.json';

	public $history = array();

	protected 
		$file = false,
		$max = self::MAX_HISTORY,
		$old = array(),
		$skipSave = false;

	public function __construct($file = false) {

		if (!$file) {

			// Try a few different possibilities
			$try = array(
				getcwd() . 'history.json',
				getcwd() . 'deploy_history.json',
				'/var/www/history.json',
				'/var/www/deploy_history.json'
			);

			foreach ($try as $file) {
				if (file_exists($file)) break;
			}

			if (!file_exists($file))
				$file = self::DEFAULT_FILE;

		}

		$this->loadFile($file);

	}

	public function loadFile($file) {

		$this->file = $file;

		if (file_exists($file) && is_readable($file)) {
			
			$history = file_get_contents($file);
			$this->history = json_decode($history, true);
			
			if (strlen($history) && is_null($this->history))
				throw new InvalidFileException('History file does not contain valid JSON. Fix or remove file.');
				
		} elseif (file_exists($file) && !is_readable($file)) {
			throw new InvalidFileException('History file is not readable.');
		}

		if (!file_exists($file) && !@touch($file))
			throw new InvalidFileException('History file could not be created.');

		if (!is_writable($file))
			throw new InvalidFileException('History file is not writable.');

		if (!$this->history || !is_array($this->history)) 
			$this->history = array();

	}

	public function __get($name) {
		return $this->$name;
	}

	public function setMaxHistory($max) {
		$this->max = $max;
	}

	public function addHistory($tag, $dir, $remOld = true, $date = false) {
		if (!$date) $date = date('r');

		array_unshift($this->history, array(
			'tag' => $tag,
			'dir' => $dir,
			'date' => $date
		));

		if ($remOld) {

			$this->old = array_slice($this->history, $this->max);
			$this->history = array_slice($this->history, 0, $this->max);

		}
		
	}

	public function save() {
		return file_put_contents($this->file, json_encode($this->history));
	}

}