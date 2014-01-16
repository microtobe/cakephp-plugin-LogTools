<?php
/**
 * LogTools Tools Component
 *
 * Copyright (c) ZendForum, Inc. (http://www.zendforum.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) ZendForum, Inc. (http://www.zendforum.com)
 * @link          http://www.zendforum.com
 * @since         LogTools 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class ToolsComponent
 *
 * @since         LogTools 0.1
 */
class ToolsComponent extends Component {

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
			'autoRun' => false,
			'urlEnable' => false,
			'emailConfig' => '',
			'email' => '',
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
 * CacheKey used for the cache file.
 *
 * @var string
 */
	public $cacheKey = 'log_tools_cache';

/**
 * Duration of the log tools cache
 *
 * @var string
 */
	public $cacheDuration = '+24 hours';

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
		$settings = array_merge((array)Configure::read('LogTools'), $settings);
		
		$this->controller = $collection->getController();
		
		parent::__construct($collection, array_merge($this->settings, (array)$settings));
		$this->cacheKey .= $this->Session->read('Config.userAgent');
		if ($this->settings['autoRun']) {
			$this->enabled = true;
		}
		return;
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
		
		if ($this->enabled) {
// 			debug(Cache::read($this->cacheKey, 'log_tools'));
			$this->_saveState();
			CakeLog::write('debug', serialize($this->_sendEmail($this->settings['email'], $this->settings['emailConfig'])));
			if ($this->settings['autoClear'] || ($this->settings['urlEnable'] && isset($this->controller->request->query['logClear']) && ($this->controller->request->query['logClear'] === 'true'))) {
				$this->_clearLog();
			}
		}
		return;
	}
	
	/**
	 * Send email, it contains log files as attachments.
	 * @param string $to
	 */
	protected function _sendEmail($to = '', $emailConfig = 'log') {
		if (is_dir($this->logPath)) {
			App::uses('CakeEmail', 'Network/Email');
			$email = new CakeEmail();
// 			$email->emailFormat('html');
			if (empty($emailConfig)) {
				$emailConfig = 'log';
			}
			$email->config($emailConfig);
			if (!empty($to)) {
				$email->to("$to");
			}
			$email->subject(env('HTTP_HOST') . '--Log files--' . date('Y-m-d H:i:s'));
			$email->attachments(array($this->logPath . 'debug.log', $this->logPath . 'error.log'));
			return $email->send('Log files are in the attachments.');
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
 * Create the cache config for sending email
 *
 * @return void
 */
	protected function _createCacheConfig() {
		if (Configure::read('Cache.disable') === true || Cache::config('log_tools')) {
			return;
		}
		$cache = array(
		    'duration' => $this->cacheDuration,
		    'engine' => 'File',
		    'path' => CACHE
		);
		if (isset($this->settings['cache'])) {
			$cache = array_merge($cache, $this->settings['cache']);
		}
		Cache::config('log_tools', $cache);
	}

/**
 * Check the current state of the tools varibles in the cache file.
 *
 * @return boolean
 */
	protected function _checkState() {
		$config = Cache::config('log_tools');
		if (empty($config)) {
			$this->_createCacheConfig();
		}
		$cacheData = Cache::read($this->cacheKey, 'log_tools');
		if (is_array($cacheData) && !empty($cacheData['sended']) && ($cacheData['sended'] === true)) {
			return true;
		}
		return false;
	}

/**
 * Save the current state of the tools varibles to the cache file.
 *
 */
	protected function _saveState() {
		$config = Cache::config('log_tools');
		if (empty($config)) {
			$this->_createCacheConfig();
		}
		$cacheData = Cache::read($this->cacheKey, 'log_tools');
		if (is_array($cacheData) && !empty($cacheData['sended']) && ($cacheData['sended'] === true)) {
			Cache::delete($this->cacheKey, 'log_tools');
		}
		$cacheData['sended'] = true;
		Cache::write($this->cacheKey, $cacheData, 'log_tools');
	}
}
