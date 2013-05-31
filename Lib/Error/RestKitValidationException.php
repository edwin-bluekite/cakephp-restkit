<?php

/**
 * Description of RestException
 *
 * @author bravo-kernel
 */
class RestKitValidationException extends CakeException {

	/**
	 * _construct()
	 *
	 * @param string $message
	 */
	public function __construct($message = null) {

		// set a default message if none was given
		if (empty($message)) {
			$message = 'Validation Error';
		}

		// re-format the ($model->validationErrors) array
		if(is_array($message)){
			$validationErrors = $message;
			$out = array();
			foreach (array_keys($validationErrors) as $field) {
				$validationMessages = $this->_getFieldValidationMessages($field, $validationErrors[$field]);
				$out= array_merge_recursive($out, $validationMessages);
			}
			$message = json_encode($out);
		}
		parent::__construct($message, 422);
	}

	/**
	 * _getFieldValidationMessages() is used to return an array with one or multiple message
	 *  strings per validation error for a given field.
	 *
	 * @param string $field
	 * @param array $invalids
	 * @return array
	 */
	private function _getFieldValidationMessages($field, $invalids) {
		$out = array();
		foreach ($invalids as $key => $value) {
			array_push($out, array('message' => "Validation failed for $field: $value"));
		}
		return $out;
	}


}