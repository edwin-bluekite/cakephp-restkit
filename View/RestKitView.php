<?php

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
	 * $rootKey with name of the collection/entity. E.g. Posts or Post
	 *
	 * @var boolean
	 */
	public $rootKey = null;

	/**
	 * $contentType will hold the requested (generic) Media Type (e.g. hal, collection, etc.)
	 *
	 * @var boolean
	 */
	public $mediaType = null;


	/**
	 * Constructor
	 *
	 * @param Controller $controller
	 */
	public function __construct(Controller $controller = null) {
		parent::__construct($controller);

		if (!isset($this->viewVars['Exception'])) {
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

			// @todo MAKE NICE !!!!
			if ($this->request->is('rest')) {
				$this->_setVndErrorContentTypeHeader();
				return $this->_serializeException($this->viewVars['Exception']);
			} else {
				return $this->_serializePlain(array('error' => array(
						'code' => 404,
						'message' => 'Not Found')));
			}
		}

		$this->_setContentType(); // set required Content-Type response header

		// merge passed options (e.g for excluding or 'foreigning' fields)
		if (isset($this->viewVars['options'])) {
			$this->options = Hash::merge($this->options, $this->viewVars['options']);
		}

		// Data is automagically passed to the corresponding function in either RestKitJsonView or RestKitXmlView
		switch ($this->mediaType){
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
		if ($this->request->is('jsonHal')) {
			$this->response->type(array('jsonHal' => 'application/hal+json; charset=' . Configure::read('App.encoding')));
			$this->response->type('jsonHal');
			$this->mediaType = 'hal';
			return;
		}
		if ($this->request->is('xmlHal')) {
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
		if ($this->request->is('json') || $this->request->is('jsonHal')) {
			$this->response->type(array('jsonVndError' => 'application/vnd.error+json'));
			$this->response->type('jsonVndError');
			return;
		}
		$this->response->type(array('xmlVndError' => 'application/vnd.error+xml'));
		$this->response->type('xmlVndError');
	}

}