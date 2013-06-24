<?php

App::uses('RestKitView', 'RestKit.View');

class RestKitXmlView extends RestKitView {

	/**
	 * _serializePlain() uses the PlainHelper to return a string containing plain XML
	 *
	 * @param array $data
	 * @return string
	 */
	protected function _serializePlain($data) {
		$helper = $this->Helpers->load('RestKit.Plain');
		if ($this->plural) {
			return $helper->makeXmlPlural($data);
		}
		return $helper->makeXmlSingular($data);
	}

	/**
	 * _serializeHal() uses the HalHelper to return a string containing HAL-XML
	 *
	 * @param array $data
	 * @return string
	 */
	protected function _serializeHal($data) {
		$helper = $this->Helpers->load('RestKit.Hal');
		if ($this->plural) {
			return $helper->makeXmlPlural($data);
		}
		return $helper->makeXmlSingular($data);
	}

	/**
	 * _serializeVndError() uses the VndErrorHelper to return a string containing vnd.error XML
	 *
	 * @param array $data
	 * @return string
	 */
	protected function _serializeVndError($data) {
		$helper = $this->Helpers->load('RestKit.VndError');
		return $helper->makeXml($data);
	}

}