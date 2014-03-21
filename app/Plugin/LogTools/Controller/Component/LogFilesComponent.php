<?php
App::uses('Validation', 'Utility');

/**
 * Class LogFilesComponent
 *
 * @since         LogFiles 0.1
 */
class LogFilesComponent extends Component {

/**
 * Settings for the Component
 *
 * - urlEnable - Force the tools to run even if autoRun == false. Default = false
 * - autoRun - Automatically run the tools. If set to false, tools can be triggered by adding
 *    `?logSend=true` to your URL if urlEnable is set to true.
 * - email - Who the log email to.
 *
 * @var array
 */
	public $settings = array(
			'enable' => false,
			'urlEnable' => false,
			'autoClear' => true,
			'emailConfig' => 'send',
			'to' => '',
	);

/**
 * Controller instance reference
 *
 * @var object
 */
	public $controller;

/**
 * Components used by Tools
 *
 * @var array
 */
	public $components = array('RequestHandler', 'Session');

/**
 * CacheKey for the cache file.
 *
 * @var string
 */
	public $cacheKey = 'log_files_cache';

/**
 * Duration of the log files cache
 *
 * @var string
 */
	protected $cacheDuration = '+10 hours';

/**
 * Status whether component is enable or disable
 *
 * @var boolean
 */
	public $enabled = false;
	
/**
 * The path to log file(debug.log and error.log)
 *
 * @var string
 */
	public $logPath = '';

/**
 * Constructor
 *
 * @param ComponentCollection $collection
 * @param array $settings
 * @return \ToolbarComponent
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$this->logPath = ROOT . DS . APP_DIR . DS . 'tmp/logs/';
		$this->controller = $collection->getController();
		
		parent::__construct($collection, array_merge($this->settings, (array)$settings));
// 		$this->cacheKey .= '_' . $this->Session->read('Config.userAgent');
		if ($this->settings['enable']) {
			$this->enabled = true;
		}
	}
	
	/**
	 *
	 * @see Component::shutdown()
	 */
	public function shutdown(Controller $controller) {
		if ($this->_checkState()) {
			$this->enabled = false;
		}
		if ($this->settings['urlEnable'] && isset($this->controller->request->query['logSend']) && ($this->controller->request->query['logSend'] === 'true')) {
			$this->enabled = true;
		}
		
		if ($this->enabled && Validation::email($this->settings['to']) && $this->_checkFileSize()) {
// 			debug(Cache::read($this->cacheKey, 'log_files'));
			$this->_saveState();
			CakeLog::write('debug', serialize($this->_sendEmail($this->settings['to'], $this->settings['emailConfig'])));
			
			//清空log文件
			if ($this->settings['autoClear']) {
				$this->_clearLog();
			}
		}
	}
	
	/**
	 * Send email, it contains log files as attachments.
	 * @param string $to
	 */
	protected function _sendEmail($to = '', $emailConfig = '') {
		if (is_dir($this->logPath) && !empty($to) && !empty($emailConfig)) {
			App::uses('CakeEmail', 'Network/Email');
			$email = new CakeEmail();
			if (empty($emailConfig)) {
				$emailConfig = 'default';
			}
			$email->config($emailConfig);
			$email->to("$to");
			$email->subject($this->controller->request->host() . '--Log files--' . date('Y-m-d H:i:s'));
			$logFiles = array();
			if (file_exists($this->logPath . 'debug.log')) {
				$logFiles[] = $this->logPath . 'debug.log';
			}
			if (file_exists($this->logPath . 'error.log')) {
				$logFiles[] = $this->logPath . 'error.log';
			}
			$email->attachments($logFiles);
			return $email->send('Url: ' . $this->controller->request->here . '<br>Log files are in the attachments.');
		}
	}
	
	/**
	 * This function will be run if autoClear is true
	 */
	protected function _clearLog() {
		if (is_dir($this->logPath)) {
			$this->_clearFile($this->logPath . 'debug.log');
			$this->_clearFile($this->logPath . 'error.log');
		}
	}
	
	/**
	 * This function is set file to be null
	 * @param string $filePath
	 * @return boolean
	 */
	private function _clearFile($filePath = '') {
		if (empty($filePath)) {
			return false;
		}
		$result = false;
		App::uses('File', 'Utility');
		$file = new File($filePath);
		if ($file->exists() && $file->writable()) {
			$file->write(null, 'w');
			$result = true;
		}
		$file->close();
		return $result;
	}
	
	/**
	 * This function is get file size
	 * @return bool
	 */
	private function _checkFileSize() {
		App::uses('File', 'Utility');
		$debug = new File($this->logPath . 'debug.log');
		$error = new File($this->logPath . 'error.log');
		$debugSize = $debug->size();
		$errorSize = $error->size();
		if ($debugSize || $errorSize) {
			return true;
		}
		return false;
	}

/**
 * Create the cache config for sending email
 *
 * @return void
 */
	protected function _createCacheConfig() {
		if (Configure::read('Cache.disable') === true || Cache::config('log_files')) {
			return;
		}
		$cache = array(
		    'duration' => $this->cacheDuration,
		    'engine' => 'File',
		    'path' => CACHE
		);
		if (!empty($this->settings['cache'])) {
			$cache = array_merge($cache, $this->settings['cache']);
		}
		Cache::config('log_files', $cache);
	}

/**
 * Check the current state of the tools varibles in the cache file.
 *
 * @return boolean
 */
	protected function _checkState() {
		$config = Cache::config('log_files');
		
		if (empty($config)) {
			$this->_createCacheConfig();
		}
		$cacheData = Cache::read($this->cacheKey, 'log_files');
		if (!empty($cacheData) && is_array($cacheData) && !empty($cacheData['sended']) && ($cacheData['sended'] === 1)) {
			return true;
		}
		return false;
	}

/**
 * Save the current state of the tools varibles to the cache file.
 *
 */
	protected function _saveState() {
		$config = Cache::config('log_files');
		if (empty($config)) {
			$this->_createCacheConfig();
		}
		$cacheData = Cache::read($this->cacheKey, 'log_files');
		if (is_array($cacheData) && !empty($cacheData['sended']) && ($cacheData['sended'] === 1)) {
			Cache::delete($this->cacheKey, 'log_files');
		}
		$cacheData['sended'] = 1;
		Cache::write($this->cacheKey, $cacheData, 'log_files');
	}
}
