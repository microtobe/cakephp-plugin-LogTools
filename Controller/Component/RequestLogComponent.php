<?php
App::uses('DebugTimer', 'LogTools.Lib');

/**
 * Class RequestLogComponent
 *
 * @since         RequestLog 0.1
 */
class RequestLogComponent extends Component {

/**
 * Settings for the Component
 *
 * - enable - if log enable. Default = false
 *
 * @var array
 */
	public $settings = array(
			'enable' => true
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
	}
	
	/**
	 * Called before the Controller::beforeFilter().
	 * @see Component::initialize()
	 */
	public function initialize(Controller $controller) {
		//process_time timing start
		DebugTimer::start('process_time', 'Php process time');
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
			$this->_save();
		}
	}
	
	/**
	 * Save log.
	 * @param string $to
	 */
	protected function _save() {
		//process_time timing stop
		DebugTimer::stop('process_time');
		
		$data['url'] = $this->_getUrl();
		$data['client_ip'] = $this->_getClientIp();
		$data['params'] = $this->_getParams();
		$data['request_time'] = $this->_getRequestTime();
		$data['process_time'] = $this->_getProcessTime();
		$data['created'] = date('Y-m-d H:i:s');
		$data = array_filter($data);
		if (!empty($data)) {
			$this->Log = ClassRegistry::init('LogTools.Log');
			$this->Log->create();
			return $this->Log->save($data);
		}
	}
	
	/**
	 * Fetch process time, Unit:ms
	 */
	private function _getProcessTime() {
		$timeAll = DebugTimer::getAll();
		return $timeAll['process_time']['time'] * 1000;
	}
	
	/**
	 * Fetch request time,Unit:ms
	 */
	private function _getRequestTime() {
		return DebugTimer::requestTime() * 1000;
	}
	
	/**
	 * client IP
	 */
	private function _getClientIp() {
		return $this->_controller->request->clientIp();
	}
	
	/**
	 * Fetch url
	 */
	private function _getUrl() {
		return $this->_controller->request->url;
	}
	
	/**
	 * Fetch request params
	 
	 * @return json
	 */
	private function _getParams() {
		$params = am($this->_controller->request->named, $this->_controller->request->query, $this->_controller->request->data, $this->_controller->request->form);
		//过滤参数：token, password
		unset($params['token'], $params['password']);
		return $params? json_encode($params): '';
	}
}
