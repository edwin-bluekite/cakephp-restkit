<?php

App::uses('RestKitView', 'RestKit.View');

class RestKitJsonView extends RestKitView {

	/**
	 * _serializePlain() json encodes $data as-is (PlainHelper not needed)
	 *
	 * @param type $data
	 * @return string
	 */
	protected function _serializePlain($data) {
		return json_encode($data);
	}

	/**
	 * _serializeHal() uses the HalHelper to generate an array ready for rendering as HAL-JSON
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
	 * _serializeVndError() uses the VndErrorHelper to generate an array ready for rendering as vnd.error JSON
	 *
	 * @param array $data
	 * @return string
	 */
	protected function _serializeVndError($data) {
		$helper = $this->Helpers->load('RestKit.VndError');
		return json_encode($helper->makeJson($data));
	}
}