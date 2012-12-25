<?php

App::uses('Component', 'Controller');
App::uses('RestOption', 'Model');

/**
 * Description of RestKitComponent
 *
 * @todo (might have to) build in a check in validateUriOptions for this->controller->$modelName->validates() because it will break if the model has $uses = false or array()
 *
 * @author bravo-kernel
 */
class RestKitComponent extends Component {

	/**
	 * $controller holds a reference to the current controller
	 *
	 * @var Controller
	 */
	protected $controller;

	/**
	 * initialize() is used to setup references to the the calling Controller, add
	 * Cake Detectors and to enforce REST-only
	 *
	 * Note: initialize() is run before the calling Controller's beforeFilter()
	 *
	 * @param Controller $controller
	 * @return void
	 */
	public function initialize(Controller $controller) {
		self::setup($controller); // create references and add Cake Detectors
	}

	/**
	 * startup() is used to handle Authentication, Autorization, etc.
	 *
	 * Note: startup() is called after the calling Controller's beforeFilter()
	 *
	 * @param Controller $controller
	 * @return void
	 */
	public function startup(Controller $controller) {

	}

	/**
	 * setup() is used to configure the RestKit component
	 *
	 * @param Controller $controller
	 * @return void
	 */
	protected function setup(Controller $controller) {

		$this->controller = $controller; // create local reference to calling controller
		self::_forceJSON();   // return 404s for all non-JSON calls
		self::_authenticate();   // deny-unless (if Authentication is enabled)
	}

	/**
	 * _forceJSON() will always throw a 404 for ALL non-JSON requests (instead of default
	 * Cake behavior that would for example throw a 500 for requests not using an extension)
	 *
	 * @param void
	 */
	protected function _forceJSON() {

		// allow if .json extension is used
		if (in_array($this->controller->params['ext'], array('json'))) {
			return;
		}
		// allow if a JSON Accept Header is used
		if ($this->controller->RequestHandler->accepts('json')) {
			return;
		}

		// definitely not JSON so throw a 404
		throw new NotFoundException();
	}

	/**
	 * authenticate() is used to
	 *
	 * @return type
	 * @throws ForbiddenException
	 *
	 * @todo document
	 */
	protected function _authenticate() {

		// Log in user using dummy data if authentication is TURNED OFF COMPLETELY
		if (Configure::read('RestKit.Authenticate') == false) {
			$this->controller->Auth->login('dummy-data');
			return;
		}

		// Log in user using dummy data if current action is DEFINED IN CONTROLLER ALLOW-PUBLIC
		if (in_array($this->controller->action, $this->controller->allowPublic)) {
			$this->controller->Auth->login('dummy-data');
			return;
		}

		// Authentication required; let AuthComponent handle passed username/password
		if ($this->controller->Auth->login()) {
			return;
		} else {
			throw new ForbiddenException('Permission denied, invalid credentials');
		}
	}

	/**
	 * _parseUriOptions() will use passed array as default options and will validate passed URI options against the Model's validation rules
	 *
	 * @param type $default_options
	 * @return type array
	 */
	public function parseUriOptions($default_options) {
		$options = $this->_validateUriOptions($default_options);
		return $options;
	}

	/**
	 * setError() is used to buffer error-messages to be included in the response
	 *
	 * @param string $optionName is the exact name of the URI option (e.g. limit, sort, etc)
	 * @param string $type to specify the type of error (e.g. optionValidation)
	 * @param string $message with informative information about the error
	 */
	public function setError($type, $optionName, $message) {
		array_push($this->_errors, array('Error' => array(
			'type' => $type,
			'option' => $optionName,
			'message' => $message,
			'moreInfo' => 'http://ecloud.alt3.virtual/errors/23532'
			)));
	}

	/**
	 * _getErrors() ......
	 *
	 * @todo add documentation
	 */
	public function getErrors() {
		return $this->_errors;
	}

	/**
	 * validateUriOptions() merges passed URI options with default options, validates them against the model and resets unvalidated options to the default value.
	 */
	private function _validateUriOptions($default_options = array()) {

		// no URI parameters passed so return (and use) default values
		if (!$this->controller->request->query) {
			return $default_options;
		}

		// construct new arrays with keynames as used in the Model´s validation rules (e.g. option_index_limit)
		$modelDefaults = $default_options;
		$modelDirties = $this->controller->request->query;

		// Merge values (only for dirty keys existing in $default?options)
		$modelMerged = array_intersect_key($modelDirties + $modelDefaults, $modelDefaults);

		// Set data and return the merged array if validation is instantly successfull
		$this->Model = ClassRegistry::init('RestKit.RestOption');
		$this->Model->set($modelMerged);
		if ($this->Model->validates(array('fieldList' => array_keys($modelDefaults)))) {
			return $modelMerged;
		}

		// reset non-validating fields to default values + fill the debug array
		foreach ($this->Model->validationErrors as $key => $value) {
			$modelMerged[$key] = $modelDefaults[$key];       // reset invalidated key
			$key = preg_replace('/.+_/', '', $key);
			$this->setError('optionValidation', $key, $value[0]);
		}
		return $modelMerged;
	}

	/**
	 * routes() is used to provide the functionality normally used in routes.php like
	 * setting the allowed extensions, prefixing, etc.
	 */
	public static function routes() {
		self::_mapResources();
		self::_enableExtensions();
	}

	/**
	 * _mapResources() is used to enable REST for controllers + enable prefix routing (if enabled)
	 */
	private static function _mapResources() {

		if (Configure::read('RestKit.Request.prefix') == true) {
			Router::mapResources(
				array('Users', 'Exampreps'), array('prefix' => '/' . Configure::read('RestKit.Request.prefix') . '/')
			);

			// skip loading Cake's default routes when forcePrefix is disabled in config
			if (Configure::read('RestKit.Request.forcePrefix') == false) {
				require CAKE . 'Config' . DS . 'routes.php';
			}
		} else {
			require CAKE . 'Config' . DS . 'routes.php'; // load CakePHP''s default routes
			Router::mapResources(
				array('Users', 'Exampreps')
			);
		}
	}

	/**
	 * _enableExtensions() is used to make sure that only those extensions defined
	 * in config.php are serviced. All other requests will receive a 404.
	 *
	 * @return void
	 */
	private static function _enableExtensions() {
		Router::parseExtensions('json');
		Router::setExtensions(array('json'));
	}

}