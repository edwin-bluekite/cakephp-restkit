<?php

App::uses('View', 'View');
App::uses('CakeLogInterface', 'Log');

class RestKitView extends View {

	/**
	 * $RestKitComponent holds a reference to the calling controllers RestKitComponent
	 *
	 * Note: direct RestKitComponent access absolutely required because the viewVars are
	 * not available for exceptions.
	 *
	 * @var RestKitComponent
	 */
	public $RestKitComponent = null;

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

		// set up a reference to the RestKitComponent
		$this->RestKitComponent = $controller->RestKit;

		// Set up some variables for normal (non-error) responses
		if (!isset($this->viewVars['RestKit']['Exception'])) {
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

		// Handle exceptions first (because they are serialized differently)
		if (isset($this->viewVars['RestKit']['Exception'])) {
			switch ($this->RestKitComponent->genericErrorType) {
				case 'vndError':
					return $this->_serializeException($this->viewVars['RestKit']['Exception']);
					break;
				default:
					return $this->_serializePlain(array('error' => $this->viewVars['RestKit']['Exception']));
			}
		}

		// Not an exception, render normal response
		if (isset($this->viewVars['options'])) {
			$this->options = Hash::merge($this->options, $this->viewVars['options']);
		}

		// determine whether we are processing a collection or a single resource
		if (Hash::numeric(array_keys($this->viewVars[$this->rootKey]))) {
			$this->plural = true;
		} else {
			$this->plural = false;
		}

		// respond in the preferred format
		switch ($this->RestKitComponent->genericSuccessType) {
			case 'plain':
				return $this->_serializePlain($this->viewVars[$this->rootKey]);
				break;
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

}