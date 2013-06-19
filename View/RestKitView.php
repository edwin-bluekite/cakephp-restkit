IS(<?php

App::uses('View', 'View');
App::uses('CakeLogInterface', 'Log');

class RestKitView extends View {

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
	 * $mediaType will hold the requested (generic) Media Type (e.g. hal, collection, etc.)
	 *
	 * @var boolean
	 */
	public $mediaType = null;

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

		echo "Entered RestKitView\n";
		
		parent::__construct($controller);

		if (!isset($this->viewVars['Exception'])) {
			echo "EXCEPTION\n";
			pr ($this->viewVars['_serialize']);

			$this->modelClass = Inflector::singularize(current($this->viewVars['_serialize']));
			$this->rootKey = current($this->viewVars['_serialize']);
		}
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

		// Handle Exceptions first (serialized differently)
		if (isset($this->viewVars['Exception'])) {

			// generate response in vnd.error format
			if ($this->request->accepts('vndError')) {
				$this->_setVndErrorContentTypeHeader();
				return $this->_serializeException($this->viewVars['Exception']);
			}

			// generate response in plain json/xml format
			return $this->_serializePlain(array('error' => $this->viewVars['Exception']));
		}

		// set the required Content-Type response header AND fill $this->mediaType
		//$this->_setContentType();
		$this->mediaType = 'hal';

		// merge passed options (e.g for excluding or 'foreigning' fields)
		if (isset($this->viewVars['options'])) {
			$this->options = Hash::merge($this->options, $this->viewVars['options']);
		}

		// process plain json/xml requests the "standard" way
		// @todo RestKit function prefers('plain') not available somehow
		if ($this->request->accepts('json')||$this->request->accepts('xml')){
			return $this->_serializePlain($this->viewVars[$this->rootKey]);
		}

		// specific Media Type requested, determine if we are processing a data-collection or a single resource
		if (Hash::numeric(array_keys($this->viewVars[$this->rootKey]))) {
			$this->plural = true;
		} else {
			$this->plural = false;
		}

		// Data is automagically passed to the corresponding function in either RestKitJsonView or RestKitXmlView
		switch ($this->mediaType) {
			case 'hal':
				return $this->_serializeHal($this->viewVars[$this->rootKey]);
				break;
			default:
				throw new NotImplementedException('Response Media Type not implemented');
		}
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

	/**
	 * _setContentType() automatically sets the correct Content-Type in the response
	 * based on the requested Media Type.
	 */
	private function _setContentType() {

		if ($this->request->accepts('application/hal+json')) {
			$this->response->type(array('jsonHal' => 'application/hal+json; charset=' . Configure::read('App.encoding')));
			$this->response->type('jsonHal');
			$this->mediaType = 'hal';
			return;
		}
		if ($this->request->accepts('application/hal+xml')) {
			$this->response->type(array('xmlHal' => 'application/hal+xml; charset=' . Configure::read('App.encoding')));
			$this->response->type('xmlHal');
			$this->mediaType = 'hal';
		}
	}

	/**
	 * _setVndErrorContentTypeHeader() is used to respond with the correct vnd.error
	 * Content-Type header ("application/vnd.error+json" or "application/vnd.error+xml").
	 */
	private function _setVndErrorContentTypeHeader() {
		if ($this->request->accepts('json') || $this->request->accepts('jsonHal')) {
			$this->response->type(array('jsonVndError' => 'application/vnd.error+json'));
			$this->response->type('jsonVndError');
			return;
		}
		$this->response->type(array('xmlVndError' => 'application/vnd.error+xml'));
		$this->response->type('xmlVndError');
	}

}