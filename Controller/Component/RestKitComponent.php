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
	 * $validationErrors will hold validationErrors
	 *
	 * @var array
	 */
	public $validationErrors = array();

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
	 * beforeRender() is used to make sure HAL requests are rendered as json/xml
	 * using the viewless logic in RestKitView.
	 *
	 * @param Controller $controller
	 */
	public function beforeRender(Controller $controller) {
		if($controller->request->is('jsonHal')){
			$controller->RequestHandler->renderAs($controller, 'json');
		}
		if($controller->request->is('xmlHal')){
			$controller->RequestHandler->renderAs($controller, 'xml');
		}
	}


	/**
	 * setup() is used to configure the RestKit component
	 *
	 * @param Controller $controller
	 * @return void
	 */
	protected function setup(Controller $controller) {

		// active our custom callback-detectors so we can detect HAL requests
		$this->addRequestDetectors();

		// allow public access to everything when 'Authenticate' is set to false in the config file
		if (Configure::read('RestKit.Authenticate') == false) {
			$controller->Auth->allow();
		}
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
	 * validOption() validates the value of a query parameter by checking if:
	 * - the parameter was actually passed
	 * - a matching RestKitOption validation rule is defined in the model
	 * - the parameter passes model validation
	 *
	 * Validation errors will be stored in $this->RestKit->optionValidationErrors.
	 *
	 * @param string $key with name of the query parameter (e.g. order, limit)
	 * @return boolean
	 */
	public function validOption($key) {

		if (!$this->hasOption($key)) {
			return false;
		}
		// initialize the Model
		$optionModel = ClassRegistry::init('RestKit.RestOption');

		// check if a validation rule exists for the given key
		// note: this prevents devs from validating against non-existing rules
		// which would lead to incorrectly returning true.
		$validator = $optionModel->validator();
		if (!$validator->getField($key)) {
			$this->validationErrors = array($key => array(
				"Unsupported RestKit validation"
			    )
			);
			return false;
		}

		// all good so validate the passed value
		$optionModel->set($this->controller->request->query);
		if ($optionModel->validates(array('fieldList' => array($key)))) {
			return true;
		}
		$this->validationErrors = $optionModel->validationErrors;
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

	/**
	 * _addRequestDetectors() ....
	 */
	protected function addRequestDetectors() {
		$this->_addJsonDetector();
		$this->_addJsonHalDetector();
		$this->_addXmlDetector();
		$this->_addXmlHalDetector();
		$this->_addHalDetector();
		$this->_addRestDetector();
	}

	/**
	 * _addJsonDetector() defines a callback-detector for detecting "plain" json requests
	 * by checking for a ".json" extension or a preffered "application/json" Accept Header.
	 */
	private function _addJsonDetector() {
		$this->controller->request->addDetector('json', array('callback' => function(CakeRequest $request) {

			    // check for extension ".json"
			    if (isset($request->params['ext']) && $request->params['ext'] === 'json') {
				    return true;
			    }
			    // check if the preferred Accept Header is json
			    $accepts = $request->accepts();
			    if ($accepts[0] == 'application/json') {
				    return true;
			    }
			    return false;
		    }));
	}

	/**
	 * _addJsonHalDetector() defines a callback-detector for detecting JSON-HAL requests
	 * by checking for an "application/hal+json" Accept Header.
	 */
	private function _addJsonHalDetector() {
		$this->controller->request->addDetector('jsonHal', array('callback' => function(CakeRequest $request) {

			    // check for an explicit JSON-HAL Accept Header
			    if ($request->accepts('application/hal+json')) {
				    return true;
			    }
			    return false;
		    }));
	}

	/**
	 * _addXmlDetector() defines a callback-detector for detecting "plain" xml requests
	 * by checking for an ".xml" extension or preferred "application/xml" Accept Header.
	 */
	private function _addXmlDetector() {
		$this->controller->request->addDetector('xml', array('callback' => function(CakeRequest $request) {

			    // check for extension ".xml"
			    if (isset($request->params['ext']) && $request->params['ext'] === 'xml') {
				    return true;
			    }
			    // check if the prefered Accept Header is xml
			    $accepts = $request->accepts();
			    if ($accepts[0] == 'application/xml') {
				    return true;
			    }
			    return false;
		    }));
	}

	/**
	 * _addXmlHalDetector() defines a callback-detector for detecting XML-HAL requests
	 * by checking for an "application/hal+xml" Accept Header.
	 */
	private function _addXmlHalDetector() {
		$this->controller->request->addDetector('xmlHal', array('callback' => function(CakeRequest $request) {

			    // check for an explicit XML-HAL Accept Header
			    if ($request->accepts('application/hal+xml')) {
				    return true;
			    }
			    return false;
		    }));
	}

	/**
	 * _addRestDetector() defines a callback-detector that will check if a request is REST
	 * by checking for json, xml, jsonHal and xmlHal.
	 */
	private function _addRestDetector() {
		$this->controller->request->addDetector('rest', array('callback' => function(CakeRequest $request) {
			    if ($request->is('json')) {
				    return true;
			    }
			    if ($request->is('jsonHal')) {
				    return true;
			    }
			    if ($request->is('xml')) {
				    return true;
			    }
			    if ($request->is('xmlHal')) {
				    return true;
			    }
			    return false;
		    }));
	}

	/**
	 * _addHalDetector() defines a callback-detector that will check if a request is HAL
	 * by checking for jsonHal and xmlHal.
	 */
	private function _addHalDetector() {
		$this->controller->request->addDetector('hal', array('callback' => function(CakeRequest $request) {
			    if ($request->is('jsonHal')) {
				    return true;
			    }
			    if ($request->is('xmlHal')) {
				    return true;
			    }
			    return false;
		    }));
	}

}