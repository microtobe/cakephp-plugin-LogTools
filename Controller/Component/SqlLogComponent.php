<?php
/**
 * Class SqlLogComponent
 */
class SqlLogComponent extends Component {

/**
 * Settings for the Component
 *
 * - enable - If log enable. Default = false
 * - threshold(unit:ms):Sql time more than threshold to be loged.
 * - dataSource:The dataSource wanted to log, default value:default.	 
 *
 * @var array
 */
	public $settings = array(
			'enable' => false,
			'threshold' => 100,
			'dataSource' => array('default'),
	);

/**
 * Controller instance reference
 *
 * @var object
 */
	protected $_controller;

/**
 * Status whether component is enable or disable
 *
 * @var boolean
 */
	public $enabled = false;

/**
 * Constructor
 *
 * @param ComponentCollection $collection
 * @param array $settings
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$this->_controller = $collection->getController();
		
		parent::__construct($collection, array_merge($this->settings, (array)$settings));
		
		if (empty($this->settings['dataSource'])) {
			$this->settings['dataSource'] = array('default');
		}
	}
	
	/**
	 *
	 * @see Component::shutdown()
	 */
	public function shutdown(Controller $controller) {
		if ($this->settings['enable']) {
			$this->enabled = true;
		}
		if ($this->enabled) {
			$message = $this->_getMessages();
			if (!empty($message)) {
				CakeLog::debug('sql-slow : ' . $message);
			}
		}
	}
	
	/**
	 * Sql messages.
	 * @param string $messages
	 */
	protected function _getMessages() {
		$logs = array();
		$counts = 0;
		$times = 0;
		foreach ($this->settings['dataSource'] as $dataSource) {
			App::uses('ConnectionManager', 'Model');
			if (in_array($dataSource, ConnectionManager::sourceList())) {
				$logsArray = ConnectionManager::getDataSource("$dataSource")->getLog(false, false);
				list($log, $count, $time) = array_values($logsArray);
				$logs = am($logs, $log);
				$counts += $count;
				$times += $time;
			}
		}
		if (!empty($logs) && is_array($logs) && ($counts > 0)) {
			$threshold = $this->settings['threshold'];
			if ($times < $threshold) {
				return null;
			}
			$logs = current($logs);
			App::uses('Hash', 'Utility');
			$log = Hash::extract($logs, "{n}[took>=$threshold]");

			return !empty($log)? json_encode($log): null;
		}
	}
}

