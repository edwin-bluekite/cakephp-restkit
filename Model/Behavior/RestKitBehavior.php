<?php

/**
 * Description of RestKitBehavior
 *
 * @author bravo-kernel
 */
class RestKitBehavior extends ModelBehavior {

	/**
	 * afterValidate() is used to re-format Cake's default validationErrors array so it
	 * can be passed to the RestKitExceptionRenderer for generating multi-error
	 * vnd.error responses.
	 *
	 * @todo ignore if the controller request is not REST
	 *
	 * @param Model $model
	 */
	public function afterValidate(Model $model) {

		if ($model->validationErrors) {
			$out = array();
			foreach (array_keys($model->validationErrors) as $field) {
				$messages = $this->_getFieldValidationMessages($field, $model->validationErrors[$field]);
				$out = array_merge_recursive($out, $messages);
			}
			$model->validationErrors = $out;
		}
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