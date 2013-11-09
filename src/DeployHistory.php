<?PHP

class DeployHistory {

	const MAX_HISTORY = 15;

	protected 
		$history = array(),
		$file = false,
		$max = MAX_HISTORY,
		$old = array();

	public function __construct($file) {

		$this->file = $file;

		if (file_exists($file) && is_readable($file)) {
			
			$history = file_get_contents($historyFile);
			$this->history = json_decode($history, true);

			if (strlen($history) && !$this->history) 
				throw new Exception('History file does not contain valid JSON. Fix or remove file.');
				
		} elseif (file_exists($file) && !is_readable($file)) {
			throw new Excpetion('History file is not readable.');
		}

		if (!file_exists($file) && !@touch($file))
			throw new Exception('History file could not be created.');

		if (!is_writable($file))
			throw new Exception('History file is not writable.');

		if (!$this->history || !is_array($this->history)) 
			$this->history = array();

	}

	public function __get($name) {
		return $this->$name;
	}

	public function setMaxHistory($max) {
		$this->maxHistory = $max;
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
		
		return file_put_contents($this->file, json_encode($this->history));

	}

}