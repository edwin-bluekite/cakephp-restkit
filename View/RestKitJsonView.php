<?php

App::uses('RestKitView', 'RestKit.View');
App::uses('CakeLogInterface', 'Log');

/**
 * RestKitJsonView.....
 */
class RestKitJsonView extends RestKitView {

	/**
	 * _serializePlain() encodes $data as-is (PlainHelper not needed)
	 *
	 * @param type $data
	 * @return type
	 */
	protected function _serializePlain($data) {
		return json_encode($data);
	}

	/**
	 * _serializeHal() will use the RestKit.HalHelper to format find() data into HAL format
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
	 * _serializeException() generates an error.vnd response as described at
	 * https://github.com/blongden/vnd.error.
	 *
	 * Note: $data is prepared in the RestKitExceptionRenderer
	 *
	 * @param type $data
	 * @return type
	 */
	protected function _serializeException($data) {

		$out = array();
		$debug = Configure::read('debug');

		foreach ($data as $key => $error) {
			$temp = array();

			// only format as vnd.error in production mode
			if ($debug == 0) {
				$temp['logRef'] = $error['logRef'];
				$temp['message'] = $error['message'];
				$temp['_links'] = array();
				foreach ($error['links'] as $key => $pair) {
					array_push($temp['_links'], array($key => $pair));
				}
			} else {
				$temp = $error;
			}
			array_push($out, $temp);
		}
		return json_encode(array('errors' => $out));
	}

}