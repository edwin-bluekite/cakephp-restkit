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
		$this->controller = $controller; // create local reference to calling controller
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
		self::_forceJSON();   // return 404s for all non-JSON calls
		// allow public access to everything when 'Authenticate' is set to false in the config file
		if (Configure::read('RestKit.Authenticate') == false) {
			$this->controller->Auth->allow();
		}
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
	 * hasOption() checks the query parameters against a given keyname
	 *
	 * @param string $key with name of the query parameter (e.g. order, limit)
	 * @return boolean
	 */
	public function hasOption($key) {
		return array_key_exists($key, $this->controller->request->query);
	}

	/**
	 * validateOption() will validate the value of a query parameter against
	 * the validation rules defined in the RestKitOption model.
	 *
	 * @param string $key with name of the query parameter (e.g. order, limit)
	 * @return boolean
	 */
	public function validateOption($key) {
		$optionModel = ClassRegistry::init('RestKit.RestOption'); // initialize RestOption model
		$optionModel->set($this->controller->request->query);  // set data
		if ($optionModel->validates(array('fieldList' => array($key)))) {
			return true;
		}
		return false;
	}

	/**
	 * routes() is used to provide the functionality normally used in routes.php like
	 * setting the allowed extensions, prefixing, etc.
	 *
	 * @todo automagically mapResources for all parent controllers
	 */
	public static function routes() {

		Router::mapResources(self::_getControllers());
		Router::parseExtensions('json', 'xml');
		//self::_mapResources();
		//self::_enableExtensions();
	}

	/**
	 * _mapResources() is used to enable REST for controllers + enable prefix routing (if enabled)
	 */
	private static function _mapResources() {

		if (Configure::read('RestKit.Request.prefix') == true) {
			Router::mapResources(
				array('users', 'placeholders'), array('prefix' => '/' . Configure::read('RestKit.Request.prefix') . '/')
			);

			// skip loading Cake's default routes when forcePrefix is disabled in config
			if (Configure::read('RestKit.Request.forcePrefix') == false) {
				require CAKE . 'Config' . DS . 'routes.php';
			}
		} else {
			Router::mapResources(
				array('users', 'placeholders')
			);
			require CAKE . 'Config' . DS . 'routes.php'; // load CakePHP''s default routes
		}
	}

	/**
	 * _enableExtensions() is used to make sure that only those extensions defined
	 * in config.php are serviced. All other requests will receive a 404.
	 *
	 * @return void
	 */
	private static function _enableExtensions() {
		//Router::setExtensions('json');
		Router::parseExtensions();
	}

	/**
	 * _getControllers() generates an array with all Controllers in the application.
	 *
	 * The array can be passed to Router::mapResources() so that REST resource routes will
	 * automatically be created for all controllers in the app.
	 *
	 * @return array
	 */
	private static function _getControllers() {
		$controllerList = App::objects('controller');
		$stripped = array();
		foreach ($controllerList as $controller) {
			if ($controller != 'AppController') {
				array_push($stripped, str_replace('Controller', '', $controller));
			}
		}
		return($stripped);
	}

}