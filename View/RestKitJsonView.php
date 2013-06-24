<?php

App::uses('RestKitView', 'RestKit.View');

class RestKitJsonView extends RestKitView {

	/**
	 * _serializePlain() returns a string containing the "as-is" json_encoded $data (no helper needed)
	 *
	 * @param type $data
	 * @return string
	 */
	protected function _serializePlain($data) {
		return json_encode($data);
	}

	/**
	 * _serializeHal() uses the HalHelper to return a string containing HAL-JSON
	 *
	 * @param type $data
	 * @return string
	 */
	protected function _serializeHal($data) {
		$helper = $this->Helpers->load('RestKit.Hal');
		if ($this->plural) {
			return json_encode($helper->makeJsonPlural($data));
		}
		return json_encode($helper->makeJsonSingular($data));
	}

	/**
	 * _serializeVndError() uses the VndErrorHelper to return a string containing vnd.error JSON
	 *
	 * @param array $data
	 * @return string
	 */
	protected function _serializeVndError($data) {
		$helper = $this->Helpers->load('RestKit.VndError');
		return json_encode($helper->makeJson($data));
	}
}