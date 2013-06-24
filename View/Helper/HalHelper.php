<?php

App::uses('AppHelper', 'View/Helper');

/**
 *
 */
class HalHelper extends AppHelper {

	public $helpers = array('RestKit.RestKit');

	public function makeJsonPlural($data) {

		$out = array();
		foreach ($data as $index => $record) {

			// self-link first
			$temp['_links'] = $this->_getJsonHalSelf($this->RestKit->modelClass, $record[$this->RestKit->modelClass]['id']);

			// next process record-fields
			foreach ($record[$this->RestKit->modelClass] as $fieldName => $value) {
				if (!$this->RestKit->isExcludedField($fieldName)) {
					if (preg_match('/(.+)_id$/', $fieldName, $matches)) {  // everything before the last '_id' in the string will be in $matches[1], e.g. country
						if ($this->RestKit->isForeignField($fieldName)) {
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
		    '_links' => $this->_getJsonHalSelf($this->RestKit->modelClass),
		    '_embedded' => array(Inflector::tableize($this->RestKit->rootKey) => $out) // make the key lowercase e.g. user_groups instead of UserGroups
		);
	}

	/**
	 * _makeJsonSingular() generates a HAL-formatted (single resource) array from $data before returning it json_encoded
	 *
	 * @param type $data
	 * @return type
	 */
	public function makeJsonSingular($data) {

		$out = array();

		// self-link first
		$out['_links'] = $this->_getJsonHalSelf($this->RestKit->modelClass, $data[$this->RestKit->modelClass]['id']);

		// next process record-fields
		foreach ($data[$this->RestKit->modelClass] as $key => $value) {
			if (!$this->RestKit->isExcludedField($key)) {
				if (preg_match('/(.+)_id$/', $key, $matches)) {  // everything before the last '_id' in the string will be in $matches[1], e.g. country
					if ($this->RestKit->isForeignField($key)) {
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
}