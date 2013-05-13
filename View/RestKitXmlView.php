<?php

App::uses('RestKitView', 'Plugin/RestKit/View');

/**
 * RestKitXmlView is responsible for the viewless rendering of XML responses in HAL format
 *
 * @author bravo-kernel
 */
class RestKitXmlView extends RestKitView {

	/**
	 * _serializePlural() generates a HAL-formatted (collection) array from $data before returning it as XML
	 *
	 * @param type $data
	 * @return type
	 */
	protected function _serializePlural($data) {

		$out = array();
		$out['resource'] = $this->_getXmlHal($this->modelClass);
		$out['resource']['resource'] = array();

		foreach ($data as $index => $record) {
			$temp = array();

			// self-link first
			$temp += $this->_getXmlHal($this->modelClass, $record[$this->modelClass]['id']);

			// next process record-fields
			$temp['link'] = array();
			foreach ($record[$this->modelClass] as $fieldName => $value) {

				if (!$this->_isExcluded($fieldName)) {
					if (preg_match('/(.+)_id$/', $fieldName, $matches)) {  // everything before the last '_id' in the string will be in $matches[1], e.g. country
						if ($this->_isForeign($fieldName)) {
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

		// all done, return data as XML
		return Xml::fromArray($out)->asXML();
	}

	/**
	 * _serializeSingular() generates a HAL-formatted (entity) array from $data before returning it as XML
	 *
	 * @param type $data
	 * @return type
	 */
	protected function _serializeSingular($data) {

		// self-link first
		$out = array();
		$out = $this->_getXmlHal($this->modelClass, $data[$this->modelClass]['id']);

		// record-fields next
		$out['link'] = array();
		foreach ($data[$this->modelClass] as $fieldName => $value) {

			if (!$this->_isExcluded($fieldName)) {
				if (preg_match('/(.+)_id$/', $fieldName, $matches)) {  // everything before the last '_id' in the string will be in $matches[1], e.g. country
					if ($this->_isForeign($fieldName)) {
						$out += array($fieldName => $value);
					} else {
						array_push($out['link'], $this->_getXmlHal($matches[1], $value));
					}
				} else {
					$out += array($fieldName => $value);
				}
			}
		}
		// all done, return data as XML
		return Xml::fromArray(array('resource' => $out))->asXML();
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