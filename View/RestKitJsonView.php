<?php

App::uses('RestKitView', 'RestKit.View');
App::uses('CakeLogInterface', 'Log');

/**
 * RestKitJsonView is responsible for the viewless rendering of JSON responses in HAL format
 *
 * @author bravo-kernel
 */
class RestKitJsonView extends RestKitView {

	/**
	 * _serializePlain() is here to provide only the most basic functionality for standard json.
	 * In practice this function will only be used for the 404 errors thrown for clients sending
	 * requests with the "application/json" Accept Header.
	 *
	 * @param type $data
	 * @return type
	 */
	protected function _serializePlain($data) {
		return json_encode($data);
	}

	/**
	 * _serializeHal() is used to pass Cake find() data to the HAL array-formatters before
	 * returning it as a json encoded string.
	 *
	 * @param type $data
	 * @return string
	 */
	protected function _serializeHal($data) {
		if ($this->plural) {
			return json_encode($this->_makeHalPlural($data));
		}
		return json_encode($this->_makeHalSingular($data));
	}

	/**
	 * _makeHalPlural() generates a HAL-formatted (collection) array from $data before returning it json_encoded
	 *
	 * @param type $data
	 * @return type
	 */
	protected function _makeHalPlural($data) {

		$out = array();
		foreach ($data as $index => $record) {

			// self-link first
			$temp['_links'] = $this->_getJsonHalSelf($this->modelClass, $record[$this->modelClass]['id']);

			// next process record-fields
			foreach ($record[$this->modelClass] as $fieldName => $value) {
				if (!$this->_isExcluded($fieldName)) {
					if (preg_match('/(.+)_id$/', $fieldName, $matches)) {  // everything before the last '_id' in the string will be in $matches[1], e.g. country
						if ($this->_isForeign($fieldName)) {
							$temp[$fieldName] = $value;
						} else {
							$temp['_links'][$matches[1]] = $this->_getJsonHal($matches[1], $value);
						}
					} else {
						$temp[$fieldName] = $value;
					}
				}
			}
			array_push($out, $temp);
		}

		// add array base structure
		return array(
		    '_links' => $this->_getJsonHalSelf($this->modelClass),
		    '_embedded' => array(Inflector::tableize($this->rootKey) => $out) // make the key lowercase e.g. user_groups instead of UserGroups
		);
	}

	/**
	 * _makeHalSingular() generates a HAL-formatted (single resource) array from $data before returning it json_encoded
	 *
	 * @param type $data
	 * @return type
	 */
	protected function _makeHalSingular($data) {

		$out = array();

		// self-link first
		$out['_links'] = $this->_getJsonHalSelf($this->modelClass, $data[$this->modelClass]['id']);

		// next process record-fields
		foreach ($data[$this->modelClass] as $key => $value) {
			if (!$this->_isExcluded($key)) {
				if (preg_match('/(.+)_id$/', $key, $matches)) {  // everything before the last '_id' in the string will be in $matches[1], e.g. country
					if ($this->_isForeign($key)) {
						$out[$key] = $value;
					} else {
						$out['_links'][$matches[1]] = $this->_getJsonHal($matches[1], $value);
					}
				} else {
					$out[$key] = $value;
				}
			}
		}
		return $out;
	}

	/**
	 * getJsonHalSelf() ....
	 *
	 * @param type $modelName
	 * @param type $id
	 * @return array
	 */
	protected function _getJsonHalSelf($modelName, $id = null) {
		$url = '/' . Inflector::tableize($modelName);
		if (isset($id)) {
			$url .= '/' . $id;
		}
		return array(
		    'self' => array(
			'href' => $url
		    )
		);
	}

	/**
	 * getJsonHal ....
	 *
	 * @param type $modelName
	 * @param type $id
	 * @return array
	 */
	protected function _getJsonHal($modelName, $id = null) {
		$url = '/' . Inflector::tableize($modelName);
		if (isset($id)) {
			$url .= '/' . $id;
		}
		return array('href' => $url);
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