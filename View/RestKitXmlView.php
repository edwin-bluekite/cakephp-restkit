<?php

App::uses('RestKitView', 'Plugin/RestKit/View');

/**
 * RestKitXmlView is responsible for the viewless rendering of XML responses in HAL format
 *
 * @author bravo-kernel
 */
class RestKitXmlView extends RestKitView {

	/**
	 * _serializePlain() is here to provide only the most basic functionality for standard xml.
	 * In practice this function will only be used for the 404 errors thrown for clients sending
	 * requests with the "application/xml" Accept Header.
	 *
	 * @param type $data
	 * @return type
	 */
	protected function _serializePlain($data) {
		return Xml::fromArray($data)->asXML();
	}

	/**
	 * _serializeHal() is used to pass Cake find() data to the HAL array-formatters before
	 * returning it as XML.
	 *
	 * @param type $data
	 * @return string
	 */
	protected function _serializeHal($data){
		if ($this->plural) {
			return Xml::fromArray($this->_makeHalPlural($data))->asXML();
		}
		return Xml::fromArray($this->_makeHalSingular($data))->asXML();
	}

	/**
	 * _serializePlural() generates a HAL-formatted (collection) array from $data before returning it as XML
	 *
	 * @param type $data
	 * @return type
	 */
	protected function _makeHalPlural($data) {

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
		return $out;
	}

	/**
	 * _serializeSingular() generates a HAL-formatted (entity) array from $data before returning it as XML
	 *
	 * @param type $data
	 * @return type
	 */
	protected function _makeHalSingular($data) {

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

	/**
	 * _serializeException() generates an error.vnd response as described at
	 * https://github.com/blongden/vnd.error.
	 *
	 * Note: $data is prepared in the RestKitExceptionRenderer
	 *
	 * @param type $data
	 * @return XML in error.vnd format
	 */
	protected function _serializeException($data) {

		$out['error'] = array();
		$debug = Configure::read('debug');

		foreach ($data as $key => $error) {
			$temp = array();

			if ($debug == 0) {
				// vnd.error always requires logRef and message
				$temp += array('@logRef' => $error['logRef']);
				$temp['message'] = $error['message'];

				// OPTIONAL: link
				$temp['link'] = array();
				foreach ($error['links'] as $link => $pair) { // e.g $link = 'help', $pair = array('href' => 'http://your.api.com/help/id')
					$links = array();
					foreach ($pair as $key => $value) {
						$links += array("@rel" => $link, "@$key" => $value);
					}
					array_push($temp['link'], $links);
				}
			} else {
				foreach ($error as $key => $value) {
					$temp[$key] = $value;
				}
			}
			array_push($out['error'], $temp);
		}

		return Xml::fromArray(array('errors' => $out))->asXML();
	}

}