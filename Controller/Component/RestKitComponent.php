<?php

App::uses('Component', 'Controller');
App::uses('RestOption', 'Model');

/**
 * Description of RestKitComponent
 */
class RestKitComponent extends Component {

	/**
	 * $controller holds a reference to the current controller
	 *
	 * @var Controller
	 */
	protected $controller;

	/**
	 * $request holds a reference to the current request
	 *
	 * @var CakeRequest
	 */
	protected $request;

	/**
	 * $validationErrors will hold validationErrors
	 *
	 * @var array
	 */
	public $validationErrors = array();

	/**
	 * initialize() is run before the calling Controller's beforeFilter()
	 *
	 * @param Controller $controller
	 * @return void
	 */
	public function initialize(Controller $controller) {
		$this->controller = $controller; // create local reference to calling controller
		$this->request = $controller->request;
		$this->setup($controller);
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
	 * setup() is used to configure the RestKit component.
	 *
	 * RequestHandler::prefers() will be set based on the extension or Accept Header
	 *
	 * @param Controller $controller
	 * @return void
	 */
	protected function setup(Controller $controller) {

		// disable usage of .json and .xml extensions
		$this->_disableExtensions();

		// define all supported (custom) Media Types so we can render based on Accept headers
		$this->_addMimeTypes();

		// map viewClasses to either RestKitJsonView or RestKitXmlView
		$this->_setViewClassMap();

		// allow public access to everything when 'Authenticate' is set to false in the config file
		if (Configure::read('RestKit.Authenticate') == false) {
			$controller->Auth->allow();
		}

		// renderAs() absolutely required to render viewless based on Accept headers
		$this->controller->RequestHandler->renderAs($controller, $this->controller->RequestHandler->prefers());
	}

	/**
	 * _disableExtensions() is used to disable the use of the .json and .xml extensions.
	 *
	 * Note: this not only enforces a single URL for each resource (true REST) but also
	 * prevents unforeseen issues with the RequestHandlerComponent.
	 *
	 * @return boolean
	 * @throws NotFoundException
	 */
	private function _disableExtensions(){
		if (isset($this->controller->request->params['ext']) && $this->controller->request->params['ext'] === 'json') {
			throw new NotFoundException;
		}
		if (isset($this->controller->request->params['ext']) && $this->controller->request->params['ext'] === 'xml') {
			throw new NotFoundException;
		}
		return true;
	}


	/**
	 * _addMimeTypes() is used to define our custom Media Types so they become
	 * available in getMimeType() and mapType()
	 */
	private function _addMimeTypes() {
		$this->controller->response->type(array(
		    'jsonHal' => 'application/hal+json',
		    'xmlHal' => 'application/hal+xml',
		    'jsonVndError' => 'application/vnd.error+json',
		    'xmlVndError' => 'application/vnd.error+xml'
		));
	}

	private function _setViewClassMap() {
		return($this->controller->RequestHandler->viewClassMap(array(
			    'json' => 'RestKit.RestKitJson',
			    'xml' => 'RestKit.RestKitXml',
			    'jsonHal' => 'RestKit.RestKitJson',
			    'xmlHal' => 'RestKit.RestKitXml',
			    'jsonVndError' => 'RestKit.RestKitJson',
			    'xmlVndEror' => 'RestKit.RestKitXml',
		)));
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
	 * NOTE: parseExtensions() MUST be set or viewless rendering will fail
	 */
	public static function routes() {

		Router::mapResources(self::_getControllers());
		Router::parseExtensions('json', 'xml');
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
	 * prefers() checks Accept Headers against the generic Media Type name (regardless of json/xml)
	 *
	 * @param type $type
	 * @return boolean
	 */
	public function prefers($type = null) {
		switch ($type) {
			case 'plain':
				return $this->_prefersPlain();
				break;
			case 'hal':
				return $this->_prefersHal();
				break;
			case 'vndError':
				return $this->_prefersVndError();
				break;
			case 'rest':
				return $this->_prefersRest();
				break;
			default:
				return false;
		}
	}

	/**
	 * _prefersPlain() checks if the prefered response type is is plain json/xml.
	 *
	 * @return boolean
	 */
	private function _prefersPlain() {

		// specific Media Types alwasys supersede plain requests
		if ($this->_prefersHal()) {
			return false;
		}
		if ($this->controller->RequestHandler->accepts('json')) {
			return true;
		}
		if ($this->controller->RequestHandler->accepts('xml')) {
			return true;
		}
		return false;
	}

	/**
	 * _prefersPlain() checks if the prefered response Media Type is HAL
	 *
	 * @return boolean
	 */
	private function _prefersHal() {
		if ($this->controller->RequestHandler->accepts('jsonHal')) {
			return true;
		}
		if ($this->controller->RequestHandler->accepts('xmlHal')) {
			return true;
		}
		return false;
	}

	/**
	 * _prefersVndError() checks if the prefered error response Media Type is vnd.error
	 *
	 * @return boolean
	 */
	private function _prefersVndError() {
		if ($this->controller->RequestHandler->accepts('jsonVndError')) {
			return true;
		}
		if ($this->controller->RequestHandler->accepts('xmlVndError')) {
			return true;
		}
		return false;
	}

	/**
	 * _prefersRest() checks if the prefered response Media Type is one of the
	 * supported/implemented (non-error) REST types
	 *
	 * @return boolean
	 */
	private function _prefersRest() {
		if ($this->_prefersPlain()){
			return true;
		}
		if ($this->_prefersHal()){
			return true;
		}
		return false;
	}


}