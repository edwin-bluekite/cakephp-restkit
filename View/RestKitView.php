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
	 * $responseType will hold the generic Media Type for the response to render (success or error)
	 *
	 * @var type
	 */
	public $responseType = null;

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

		// determine what response we will render
		$this->setResponseType();

		// success responses require some extra data
		if (!$this->isException()) {
			$this->_mergeOptions();
			$this->_setPlural();
		}

		// load the RestKitHelper with required data
		$this->loadHelper('RestKit.RestKit', array(
			    'rootKey' => $this->rootKey,
			    'modelClass' => $this->modelClass,
			    'options' => $this->options
		));


		// render the response
		switch ($this->responseType) {
			case 'success.plain':
				return $this->_serializePlain($this->viewVars[$this->rootKey]);
				break;
			case 'success.hal':
				return $this->_serializeHal($this->viewVars[$this->rootKey]);
				break;
			case 'error.plain':
				return $this->_serializePlain(array('error' => $this->viewVars['RestKit']['Exception']));
				break;
			case 'error.vndError':
				return $this->_serializeException($this->viewVars['RestKit']['Exception']);
				break;
			default:
				throw new NotImplementedException('Response Media Type not implemented');
		}
	}

	/**
	 * setResponseType() sets the $responseType attribute by:
	 * - prepending success. or error.
	 * - appending the generic Media Type preferred in the request
	 */
	public function setResponseType() {
		if ($this->isException()) {
			$this->responseType = "error." . $this->RestKitComponent->genericErrorType;
		} else {
			$this->responseType = "success." . $this->RestKitComponent->genericSuccessType;
		}
	}

	/**
	 * isException() returns true if we are processing an exception/error
	 *
	 * @return boolean
	 */
	public function isException() {
		if (isset($this->viewVars['RestKit']['Exception'])) {
			return true;
		}
		return false;
	}

	/**
	 * _mergeOptions() merges the options set in the controller (if any) with the default
	 * options defined in this view's $options attribute
	 */
	private function _mergeOptions() {
		if (isset($this->viewVars['options'])) {
			$this->options = Hash::merge($this->options, $this->viewVars['options']);
		}
	}

	/**
	 * _setPlural() will set the plural attribute to:
	 * - true if we are processing a collection
	 * - false if we are processing a single resource
	 */
	private function _setPlural() {
		if (Hash::numeric(array_keys($this->viewVars[$this->rootKey]))) {
			$this->plural = true;
		} else {
			$this->plural = false;
		}
	}
}