<?php

App::uses('AppHelper', 'View/Helper');

/**
 * PlainHelper is used to render standard/plain XML.
 *
 * Note: makeJsonPlural() and makeJsonSingular() because data would not be changed.
 */
class PlainHelper extends AppHelper {

	public $helpers = array('RestKit.RestKit');

	/**
	 * _makeXmlPlural() reformats collection find() data into an array ready for rendering as plain XML
	 *
	 * @param type $data
	 * @return array
	 */
	public function makeXmlPlural($data) {

		// prepare $out array
		$out = array();
		$rootNode = Inflector::tableize($this->RestKit->modelClass);
		$subNode = Inflector::singularize($rootNode);
		$out[$rootNode][$subNode] = array();  // e.g $out['countries']['country']
		// fill $out array
		foreach ($data as $index => $record) {
			$temp = array();
			foreach ($record[$this->RestKit->modelClass] as $fieldName => $value) {

				if (!$this->RestKit->isExcludedField($fieldName)) {
					if (preg_match('/(.+)_id$/', $fieldName, $matches)) {  // everything before the last '_id' in the string will be in $matches[1], e.g. country
						if ($this->RestKit->isForeignField($fieldName)) {
							$temp += array($fieldName => $value);
						}
					} else {
						$temp += array($fieldName => $value);
					}
				}
			}
			array_push($out[$rootNode][$subNode], $temp);
		}
		return Xml::fromArray($out)->asXML();
	}

	/**
	 * _makeXmlPlural() reformats single resource find() data into an array ready for rendering as plain XML
	 *
	 * @param type $data
	 * @return array
	 */
	public function makeXmlSingular($data) {
		$arrayKey = key($data);    // e.g. Country
		$xmlRoot = strtolower($arrayKey);  // e.g. country
		$out = array($xmlRoot => $data[$arrayKey]);
		return Xml::fromArray($out)->asXML();
	}

}