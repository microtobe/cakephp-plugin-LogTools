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
 * - log - if log enable. Default = false
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
 * @return \ToolbarComponent
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
		//process_time计时开始
		DebugTimer::start('process_time', '接口执行时间');
	}
	
	/**
	 *
	 * @see Component::shutdown()
	 */
	public function shutdown(Controller $controller) {
		if ($this->settings['enable']) {
			$this->enabled = true;
		}
// 		debug(DebugTimer::getAll());
		if ($this->enabled) {
			$this->_save();
		}
	}
	
	/**
	 * Save log.
	 * @param string $to
	 */
	protected function _save() {
		//process_time计时结束
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
	 * 请求时间（接口实际执行时间），单位：毫秒
	 */
	private function _getProcessTime() {
// 		return $this->_getRequestTime() - DebugTimer::getAll()['process_time_compute']['time'] * 1000;
		$timeAll = DebugTimer::getAll();
		return $timeAll['process_time']['time'] * 1000;
	}
	
	/**
	 * 请求时间（整个过程），单位：毫秒
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
	 * 接口地址
	 */
	private function _getUrl() {
		return $this->_controller->request->url;
	}
	
	/**
	 * 请求参数
	 * @return string
	 */
	private function _getParams() {
		$params = array_filter(am($this->_controller->request->query, $this->_controller->request->data, $this->_controller->request->form));
		//过滤参数：token, password
		unset($params['token'], $params['password']);
		return $params? json_encode($params): '';
	}
}