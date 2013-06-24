<?php

App::uses('RestKitView', 'RestKit.View');

/**
 * RestKitXmlView is responsible for the viewless rendering of XML responses in HAL format
 */
class RestKitXmlView extends RestKitView {

	/**
	 * _serializePlain() uses the RestKit.PlainHelper to format find() data into
	 * Xml compatible format before returning it as XML
	 *
	 * @param array $data
	 */
	protected function _serializePlain($data) {
		$helper = $this->Helpers->load('RestKit.Plain');
		if ($this->plural) {
			return Xml::fromArray($helper->makeXmlPlural($data))->asXML();
		}
		return Xml::fromArray($helper->makeXmlSingular($data))->asXML();
	}

	/**
	 * _serializeHal() uses the RestKit.HalHelper to format find() data into
	 * HAL format before returning it as XML
	 *
	 * @param array $data
	 */
	protected function _serializeHal($data) {
		$helper = $this->Helpers->load('RestKit.Hal');
		if ($this->plural) {
			return Xml::fromArray($helper->makeXmlPlural($data))->asXML();
		}
		return Xml::fromArray($helper->makeXmlSingular($data))->asXML();
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