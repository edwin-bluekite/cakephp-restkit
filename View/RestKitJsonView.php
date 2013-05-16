<?php

App::uses('RestKitView', 'Plugin/RestKit/View');

/**
 * RestKitJsonView is responsible for the viewless rendering of JSON responses in HAL format
 *
 * @author bravo-kernel
 */
class RestKitJsonView extends RestKitView {

	/**
	 * _serializePlural() generates a HAL-formatted (collection) array from $data before returning it json_encoded
	 *
	 * @param type $data
	 * @return type
	 */
	protected function _serializePlural($data) {

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

		// all done, return data as JSON
		return json_encode(array(
		    '_links' => $this->_getJsonHalSelf($this->modelClass),
		    '_embedded' => array(Inflector::tableize($this->rootKey) => $out) // make the key lowercase e.g. user_groups instead of UserGroups
		));
	}

	/**
	 * _serializeSingular() generates a HAL-formatted (entity) array from $data before returning it json_encoded
	 *
	 * @param type $data
	 * @return type
	 */
	protected function _serializeSingular($data) {

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

		// all done, return data as JSON
		return json_encode($out);
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
	 *
	 * @param type $data
	 * @return type
	 */
	protected function _serializeException($data) {

		$out = array();
		foreach ($data as $error) {
			$temp = array();
			$temp['logRef'] = $error['logRef'];
			$temp['message'] = $error['message'];
			$temp['_links'] = array();
			foreach ($error['links'] as $key => $pair) {
				array_push($temp['_links'], array($key => $pair));
			}
			array_push($out, $temp);
		}
		return json_encode(array('errors' => $out));
	}

}