<?php

App::uses('AppHelper', 'View/Helper');

/**
 * Description of RestKitHelper
 */
class RestKitHelper extends AppHelper {

	public $rootKey = null;
	public $modelClass = null;
	public $options = null;

	/**
	 *
	 */
	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);

		// variables will not be filled when exceptions are being handled
		if (isset($this->settings['rootKey'])){
			$this->rootKey = $this->settings['rootKey'];
		}
		if (isset($this->settings['modelClass'])){
			$this->modelClass = $this->settings['modelClass'];
		}
		if (isset($this->settings['options'])){
			$this->options = $this->settings['options'];
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
	public function isExcludedField($fieldName) {

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
	public function isForeignField($fieldName) {

		if (!isset($this->options['foreignFields'])) {
			return false;
		}
		if (!in_array($fieldName, $this->options['foreignFields'])) {
			return false;
		}
		return true;
	}

}