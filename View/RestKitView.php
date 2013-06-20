<?php

App::uses('View', 'View');
App::uses('CakeLogInterface', 'Log');

class RestKitView extends View {

	/**
	 * $controller reference so we can access RestKit and RequestHandler
	 *
	 * @var Controller
	 */
	public $controller = null;

	/**
	 * $modelClass with name of the used Model
	 *
	 * @var string
	 */
	public $modelClass = null;

	/**
	 * $options array will be merged with content of viewVars['options']
	 *
	 * We exclude `id` by default because we know it does not belong in the HAL response
	 *
	 * @var array
	 */
	public $options = array(
	    'excludeFields' => array(
		'id'
	    )
	);

	/**
	 * $rootKey with name of the collection or resource (e.g. Posts or Post)
	 *
	 * @var boolean
	 */
	public $rootKey = null;

	/**
	 * $plural will be true for data-collections, false for a single resource
	 *
	 * @var boolean
	 */
	public $plural = null;

	/**
	 * Constructor
	 *
	 * @param Controller $controller
	 */
	public function __construct(Controller $controller = null) {
		parent::__construct($controller);

		CakeLog::write('error', 'Entered RestKitView: __construct()');
		$this->controller = $controller;

		// Normal (result) response where the Accept-header is autmatically set to
		// the preferred Accept header used in the request.
		if (!isset($this->viewVars['Exception'])) {

			// define some variables to be used while formatting the data
			$this->modelClass = Inflector::singularize(current($this->viewVars['_serialize']));
			$this->rootKey = current($this->viewVars['_serialize']);
		}

		// Exception response
		if (isset($this->viewVars['Exception'])) {
			//echo "wel een exception!!!\n";
			echo "Exception prefers: " . $this->controller->RequestHandler->prefers() . "\n";

			// HERE WE NEED TO SWITCH PREFERS TO AUTOMAGICALLY RENDER THE CORRECT MEDIA TYPE
			//$this->controller->RequestHandler->renderAs($controller, 'json');
			//$this->controller->response->type(array('jsonVndError' => 'application/vnd.error+json'));
			//$this->controller->response->type('jsonVndError');
			//CakeLog::write('error', "prefers = " . $this->controller->RequestHandler->prefers());
//			$this->controller->
			//$controller->response->type($controller->RequestHandler->prefers('jsonVndError'));
		}

		CakeLog::write('error', "Done, controller set");
	}

	/**
	 * render() is used to format the data in the layout required by either XML or JSON
	 *
	 * @todo: catch errors, excecptions, empty data. Now crashes miserably (line 56, "argument 1 is not an array")
	 *
	 * @param string $view The view being rendered.
	 * @param string $layout The layout being rendered.(not used)
	 * @return string The rendered view.
	 */
	public function render($view = null, $layout = null) {
		CakeLog::write('error', "Entered render()");

		// Handle Exceptions first (serialized differently)
		if (isset($this->viewVars['Exception'])) {

			// generate response in vnd.error format
			if ($this->controller->RestKit->prefers('vndError')) {
				return $this->_serializeException($this->viewVars['Exception']);
			}

			// generate error in plain json/xml format
			return $this->_serializePlain(array('error' => $this->viewVars['Exception']));
		}

		// merge passed options (e.g for excluding or 'foreigning' fields)
		if (isset($this->viewVars['options'])) {
			$this->options = Hash::merge($this->options, $this->viewVars['options']);
		}

		// process plain json/xml requests the "standard" way
		if ($this->controller->RestKit->prefers('plain')) {
			return $this->_serializePlain($this->viewVars[$this->rootKey]);
		}

		// specific Media Type requested, determine if we are processing a data-collection or a single resource
		if (Hash::numeric(array_keys($this->viewVars[$this->rootKey]))) {
			$this->plural = true;
		} else {
			$this->plural = false;
		}

		// process HAL requests
		if ($this->controller->RestKit->prefers('hal')) {
			return $this->_serializeHal($this->viewVars[$this->rootKey]);
		}

		throw new NotImplementedException('Response Media Type not implemented');
	}

	/**
	 * _isExcluded() will return true if the field is found in $this->viewVars['options']['excludeFields']
	 *
	 * Note: no need to check for isset here because it is defined as a class variable so always present
	 *
	 * @param string $fieldName
	 * @return boolean
	 */
	protected function _isExcluded($fieldName) {

		if (!in_array($fieldName, $this->options['excludeFields'])) {
			return false;
		}
		return true;
	}

	/**
	 * _isForeign () will return true if the field is found in $this->viewVars['options']['foreign']
	 *
	 * Used when fielname_id is not pointing to external id. A good example would be a `geoname_id` field
	 * with an actual id-pointer to be used with the geonames.org API.
	 *
	 * @param string $fieldName
	 * @return boolean
	 */
	protected function _isForeign($fieldName) {

		if (!isset($this->options['foreignFields'])) {
			return false;
		}
		if (!in_array($fieldName, $this->options['foreignFields'])) {
			return false;
		}
		return true;
	}

}