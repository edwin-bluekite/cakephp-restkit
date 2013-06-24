<?php

App::uses('Component', 'Controller');
App::uses('RestOption', 'Model');

/**
 * Description of RestKitComponent
 */
class RestKitComponent extends Component {

	/**
	 * $controller will hold a reference to the current controller
	 *
	 * @var Controller
	 */
	protected $controller;

	/**
	 * $successMediaTypes holds all supported/implemented "success" Media Types
	 *
	 * @var array
	 */
	protected $successMediaTypes = array(
	    'json',
	    'jsonHal',
	    'xml',
	    'xmlHal'
	);

	/**
	 * $errorMediaTypes holds all supported/implemented "error" Media Types
	 *
	 * @var array
	 */
	protected $errorMediaTypes = array(
	    'json',
	    'jsonVndError',
	    'xml',
	    'xmlVndError'
	);

	/**
	 * $isRest will become true when the request passes all checks
	 *
	 * @var boolean
	 */
	protected $isRest = false;

	/**
	 * $genericSuccessType holds the generic Media Type name to be used in the RestKitView to
	 * determine the format in which to render the success response.
	 *
	 * @var string
	 */
	protected $genericSuccessType = null;

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
		$this->setup();
	}

	/**
	 * beforeRender() is used to make sure HAL requests are rendered as json/xml
	 * using the viewless logic in RestKitView.
	 *
	 * NOTE: renderAs() is absolutely required here to be able to respond with Media Types
	 * that are different that the preferred Media Type (e.g. when handling exceptions)
	 *
	 * @param Controller $controller
	 */
	public function beforeRender(Controller $controller) {
		if ($this->isRest) {
			if ($this->isException()) {
				$controller->RequestHandler->renderAs($controller, $this->getPreferredSuccessType());
			} else {
				$controller->RequestHandler->renderAs($controller, $this->getPreferredErrorType());
			}
		}
	}

	/**
	 * setup() is used to configure the RestKit component.
	 *
	 * RequestHandler::prefers() will be set based on the extension or Accept Header
	 *
	 * @return void
	 */
	protected function setup() {

		// disable/enable usage of .json and .xml extensions in the config-file
		if (Configure::read('RestKit.Request.enableExtensions') == false) {
			if ($this->_usesExtensions()) {
				throw new NotFoundException;
			}
		}

		// define all supported (custom) Media Types so we can render based on Accept headers
		$this->_addMimeTypes();

		// map viewClasses to either RestKitJsonView or RestKitXmlView
		$this->_setViewClassMap();

		// allow public access to everything when 'Authenticate' is set to false in the config file
		if (Configure::read('RestKit.Authenticate') == false) {
			$this->controller->Auth->allow();
		}

		// Render a REST response (only if the request headers pass validations)
		if ($this->prefers('rest')) {
			if ($this->isValidRestKitRequest()) {
				$this->isRest = true;
				$this->genericSuccessType = $this->getGenericSuccessType();
			} else {
				throw new Exception("Unsupported Media Type", 415);
			}
		}
	}

	/**
	 * _addMimeTypes() is used to define our custom Media Types so they become
	 * available in getMimeType() and mapType()
	 */
	private function _addMimeTypes() {
		$this->controller->response->type(array(
		    'json' => 'application/json',
		    'xml' => 'application/xml',
		    'jsonHal' => 'application/hal+json',
		    'xmlHal' => 'application/hal+xml',
		    'jsonVndError' => 'application/vnd.error+json',
		    'xmlVndError' => 'application/vnd.error+xml'
		));
	}

	/**
	 * _setViewClassMap() is used to...
	 *
	 * @return type
	 */
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
	 * _usesExtensions() checks if the .json or .xml extensions is being used to access the resource.
	 *
	 * @return boolean
	 */
	private function _usesExtensions() {
		if ($this->_usesExtension('json')) {
			return true;
		}
		if ($this->_usesExtension('xml')) {
			return true;
		}
		return false;
	}

	/**
	 * _usesExtension() checks if a specific extension is being used
	 *
	 * @param type $ext either 'json' or 'xml'
	 * @return boolean
	 */
	private function _usesExtension($ext) {
		if (isset($this->controller->request->params['ext']) && $this->controller->request->params['ext'] === $ext) {
			return true;
		}
		return false;
	}

	/**
	 * getPreferredSuccessType() determines the preferred Media Type to be used for
	 * success responses by:
	 * -
	 * -
	 * -
	 *
	 * @return string when a matching alias is detected
	 * @return boolean false when the type is not implemented
	 */
	public function getPreferredSuccessType() {

		// if the .json or .xml extension is being used the preferred success-type will be $ext unless
		// an Accept header is found matching one of the more specific (non-plain) Media Types.
		if ($this->_usesExtensions()) {
			if ($this->getSpecificSuccessType($this->controller->RequestHandler->ext)) {
				return $this->getSpecificSuccessType($this->controller->RequestHandler->ext);
			}
			return $this->controller->RequestHandler->ext;
		}

		// prevent standard browser access rendering xml
		if ($this->controller->RequestHandler->prefers() === 'html') {
			return false;
		}

		// if the preferred Media Types is supported
		if (in_array($this->controller->RequestHandler->prefers(), $this->successMediaTypes)) {
			return $this->controller->RequestHandler->prefers();
		}
		return false;
	}

	/**
	 * getSpecificSuccessTypes() will return all non-plain success Media Types for either
	 * json or xml
	 *
	 * @param type $type
	 * @return type
	 */
	public function getSpecificSuccessType($type) {
		foreach ($this->controller->request->accepts() as $accept) {
			$alias = $this->controller->RequestHandler->mapType($accept);
			if ($alias != $type) {
				if (in_array($alias, $this->getSuccessMediaTypes($type))) {
					return $alias;
				}
			}
		}
	}

	/**
	 * getSuccessMediaTypes() will return an array with all matching success Media Types
	 * for either json or xml.
	 *
	 * @param type $type either 'json' or 'xml'
	 * @return array
	 */
	private function getSuccessMediaTypes($type) {
		$out = array();
		foreach ($this->successMediaTypes as $mediaType) {
			if (preg_match('/^' . $type . '/', $mediaType)) {
				array_push($out, $mediaType);
			}
		}
		return $out;
	}

	/**
	 * getPreferredErrorType() determines the preferred Media Type to be used for
	 * error responses by:
	 * - looping through all Accept Headers
	 * - mathing each header against the supported/implemented Media Types
	 * - returning the alias for the first match it finds
	 *
	 * @return string when a matching alias is detected
	 * @return boolean false when the type is not implemented
	 */
	public function getPreferredErrorType() {

		if ($this->controller->RequestHandler->prefers() === 'json') {
			if ($this->getSpecificErrorType('json')) {
				return $this->getSpecificErrorType('json');
			}
			return 'json';
		}

		if ($this->controller->RequestHandler->prefers() === 'xml') {
			if ($this->getSpecificErrorType('xml')) {
				return $this->getSpecificErrorType('xml');
			}
			return 'xml';
		}
		return false;
	}

	/**
	 * getSpecificErrorTypes() will return all non-plain error Media Types for either
	 * json or xml
	 *
	 * @param type $type
	 * @return type
	 */
	public function getSpecificErrorType($type) {
		foreach ($this->controller->request->accepts() as $accept) {
			$alias = $this->controller->RequestHandler->mapType($accept);
			if ($alias != $type) {
				if (in_array($alias, $this->getErrorMediaTypes($type))) {
					return $alias;
				}
			}
		}
	}

	/**
	 * getErrorMediaTypes() will return an array with all matching error Media Types
	 * for either json or xml.
	 *
	 * @param type $type either 'json' or 'xml'
	 * @return array
	 */
	public function getErrorMediaTypes($type) {
		$out = array();
		foreach ($this->errorMediaTypes as $mediaType) {
			if (preg_match('/^' . $type . '/', $mediaType)) {
				array_push($out, $mediaType);
			}
		}
		return $out;
	}

	/**
	 * _isValidRestKitRequest() is used to make sure we will only respond to/using
	 * Media Types implemented/supported by this plugin.
	 *
	 * @return boolean
	 */
	public function isValidRestKitRequest() {

		// make sure we only respond to/using implemented Media Types
		if (!$this->_isSupportedSuccessType($this->getPreferredSuccessType())) {
			return false;
		}

		if (!$this->_isSupportedErrorType($this->getPreferredErrorType())) {
			return false;
		}
		return true;
	}

	/**
	 * _isSupportedSuccessType() checks if the preferred request Accept header is
	 * one of the supported/implemented Media Types.
	 *
	 * @return boolean
	 */
	private function _isSupportedSuccessType($typeAlias) {
		if (in_array($typeAlias, $this->successMediaTypes)) {
			return true;
		}
		return false;
	}

	/**
	 * _isSupportedErrorType() checks if the preferred (error) request Accept header is
	 * one of the supported/implemented error Media Types.
	 *
	 * @return boolean
	 */
	private function _isSupportedErrorType($typeAlias) {
		if (in_array($typeAlias, $this->errorMediaTypes)) {
			return true;
		}
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
			case 'json':
				return $this->_prefersGeneric('json');
				break;
			case 'xml':
				return $this->_prefersGeneric('xml');
				break;
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
	 * _prefersGeneric() returns true if any of the passed Accept header match the implemented
	 * success Media Types for $type.
	 *
	 * @param type $type either 'json' or 'xml'
	 * @return boolean
	 */
	private function _prefersGeneric($type) {

		// prevent false positive when extensions are disabled in the configuration file
		if (Configure::read('RestKit.Request.enableExtensions') == false) {
			if ($this->_usesExtension($type)) {
				return false;
			}
		}

		// return true if any of the implemented json Accept headers are found
		foreach ($this->getAcceptTypes($type) as $accept) {
			if (in_array($accept, $this->getSuccessMediaTypes($type))) {
				return true;
			}
		}
		return false;
	}

	/**
	 * _getGenericSuccessType() returns the generic name for the success Media Type (e.g. plain, hal, etc)
	 *
	 * @return string|boolean
	 */
	private function getGenericSuccessType() {

		switch ($this->getPreferredSuccessType()) {
			case 'json':
				return 'plain';
				break;
			case 'xml':
				return 'plain';
				break;
			case 'jsonHal':
				return 'hal';
				break;
			case 'xmlHal':
				return 'hal';
				break;
			default:
				return false;
		}
	}

	/**
	 * getAcceptTypes() returns an array with all available Media Types passed in
	 * the request matching $type
	 *
	 * @param type $type either 'json' or 'xml'
	 * @return array
	 */
	public function getAcceptTypes($type) {
		$out = array();
		foreach ($this->controller->RequestHandler->accepts() as $mediaType) {
			if (preg_match('/^' . $type . '/', $mediaType)) {
				array_push($out, $mediaType);
			}
		}
		return $out;
	}

	/**
	 * _prefersPlain() checks if the prefered response type is is plain json/xml.
	 *
	 * @return boolean
	 */
	private function _prefersPlain() {

		// prevent false positive when extensions are disabled in the configuration file
		if (Configure::read('RestKit.Request.enableExtensions') == false) {
			if ($this->_usesExtensions()) {
				return false;
			}
		}
		if ($this->controller->RequestHandler->prefers('json')) {
			return true;
		}
		if ($this->controller->RequestHandler->prefers('xml')) {
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
		if ($this->controller->RequestHandler->prefers('jsonHal')) {
			return true;
		}
		if ($this->controller->RequestHandler->prefers('xmlHal')) {
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

		// this prevents direct webbrowser access causing xml to be rendered (since
		// most browsers also send xml Accept headers along with every request)
		if ($this->controller->RequestHandler->prefers() === 'html') {
			return false;
		}

		// extensions only valid as REST when enabled in the configuration file
		if (Configure::read('RestKit.Request.enableExtensions') == true) {
			if ($this->_usesExtensions()) {
				return true;
			}
		}

		if ($this->_prefersPlain()) {
			return true;
		}
		if ($this->_prefersHal()) {
			return true;
		}
		return false;
	}

	/**
	 * isException() becomes true when the current controller is of class CakeErrorController
	 *
	 * @return boolean
	 */
	public function isException() {
		if (get_class($this->controller) === 'CakeErrorController') {
			return true;
		}
		return false;
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

}