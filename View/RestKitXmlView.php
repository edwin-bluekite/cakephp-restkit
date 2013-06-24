<?php

App::uses('RestKitView', 'RestKit.View');

class RestKitXmlView extends RestKitView {

	/**
	 * _serializePlain() uses the PlainHelper to generate an array ready for rendering as plain XML
	 *
	 * @param array $data
	 * @return string
	 */
	protected function _serializePlain($data) {
		$helper = $this->Helpers->load('RestKit.Plain');
		if ($this->plural) {
			return Xml::fromArray($helper->makeXmlPlural($data))->asXML();
		}
		return Xml::fromArray($helper->makeXmlSingular($data))->asXML();
	}

	/**
	 * _serializeHal() uses HalHelper to generate an array ready for rendering as HAL-XML
	 *
	 * @param array $data
	 * @return string
	 */
	protected function _serializeHal($data) {
		$helper = $this->Helpers->load('RestKit.Hal');
		if ($this->plural) {
			return Xml::fromArray($helper->makeXmlPlural($data))->asXML();
		}
		return Xml::fromArray($helper->makeXmlSingular($data))->asXML();
	}

	/**
	 * _serializeVndError() uses the VndErrorHelper to generate an array ready for rendering as vnd.error XML
	 *
	 * @param array $data
	 * @return string
	 */
	protected function _serializeVndError($data) {
		$helper = $this->Helpers->load('RestKit.VndError');
		return Xml::fromArray($helper->makeXml($data))->asXML();
	}

}