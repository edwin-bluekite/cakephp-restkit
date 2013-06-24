<?php

App::uses('AppHelper', 'View/Helper');

/**
 *
 */
class HalHelper extends AppHelper {

	/**
	 * Helpers collection
	 *
	 * @var HelperCollection
	 */
	public $helpers = array('RestKit.RestKit');

	/**
	 * makeJsonPlural() generates a HAL-formatted array from (collection) $data ready for json encoding
	 *
	 * @param array $data
	 * @return array
	 */
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
	 * makeJsonSingular() generates a HAL-formatted array from (single resource) $data ready for json encoding
	 *
	 * @param array $data
	 * @return array
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
	 * @param string $modelName
	 * @param string $id
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
	 * @param string $modelName
	 * @param string $id
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
	 * makeXmlPlural() generates a HAL-formatted array from (collection) $data ready for xml encoding
	 *
	 * @param type $data
	 * @return array
	 */
	public function makeXmlPlural($data) {

		$out = array();
		$out['resource'] = $this->_getXmlHal($this->RestKit->modelClass);
		$out['resource']['resource'] = array();

		foreach ($data as $index => $record) {
			$temp = array();

			// self-link first
			$temp += $this->_getXmlHal($this->RestKit->modelClass, $record[$this->RestKit->modelClass]['id']);

			// next process record-fields
			$temp['link'] = array();
			foreach ($record[$this->RestKit->modelClass] as $fieldName => $value) {

				if (!$this->RestKit->isExcludedField($fieldName)) {
					if (preg_match('/(.+)_id$/', $fieldName, $matches)) {  // everything before the last '_id' in the string will be in $matches[1], e.g. country
						if ($this->RestKit->isForeignField($fieldName)) {
							$temp += array($fieldName => $value);
						} else {
							array_push($temp['link'], $this->_getXmlHal($matches[1], $value));
						}
					} else {
						$temp += array($fieldName => $value);
					}
				}
			}
			// prevent empty <link/> appearing in the XML when no _id fields were present

			if (count($temp['link']) == 0) {
				unset($temp['link']);
			}
			array_push($out['resource']['resource'], $temp);
		}
		return $out;
	}

	/**
	 * makeXmlPlural() generates a HAL-formatted array from (single resource) $data ready for xml encoding
	 *
	 * @param array $data
	 * @return array
	 */
	public function makeXmlSingular($data) {

		// self-link first
		$out = array();
		$out = $this->_getXmlHal($this->RestKit->modelClass, $data[$this->RestKit->modelClass]['id']);

		// record-fields next
		$out['link'] = array();
		foreach ($data[$this->RestKit->modelClass] as $fieldName => $value) {

			if (!$this->RestKit->isExcludedField($fieldName)) {
				if (preg_match('/(.+)_id$/', $fieldName, $matches)) {  // everything before the last '_id' in the string will be in $matches[1], e.g. country
					if ($this->RestKit->isForeignField($fieldName)) {
						$out += array($fieldName => $value);
					} else {
						array_push($out['link'], $this->_getXmlHal($matches[1], $value));
					}
				} else {
					$out += array($fieldName => $value);
				}
			}
		}
		return array('resource' => $out);
	}

	/**
	 * _getXmlHal ....
	 *
	 * @param type $modelName
	 * @param type $id
	 * @return array
	 */
	protected function _getXmlHal($modelName, $id = null) {

		$out = array();

		// set up rel first (only if we have an id to prevent rel popping up for the root)
		if ($id) {
			$rel = Inflector::tableize($modelName);
			$rel = Inflector::singularize($rel);
			$out['@rel'] = $rel;
		}

		// set up the url next
		$url = '/' . Inflector::tableize($modelName);
		if (isset($id)) {
			$url .= '/' . $id;
		}
		$out['@href'] = $url;

		// return data
		return $out;
	}

}