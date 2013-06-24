<?php

App::uses('ComponentCollection', 'Controller');
App::uses('Component', 'Controller');
App::uses('RestKitComponent', 'RestKit.Controller/Component');

/**
 * RestKitComponent Test Case
 *
 */
class RestKitComponentTest extends CakeTestCase {

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$Collection = new ComponentCollection();
		$this->RestKit = new RestKitComponent($Collection);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->RestKit);

		parent::tearDown();
	}

	/**
	 * testGetSuccessMediaTypes() expects an array with all success Media Types for either json or xml
	 *
	 * @return void
	 */
	public function testGetSuccessMediaTypes() {
		$expected = array('json', 'jsonHal');
		$this->assertEqual($this->RestKit->getSuccessMediaTypes('json'), $expected);

		$expected = array('xml', 'xmlHal');
		$this->assertEqual($this->RestKit->getSuccessMediaTypes('xml'), $expected);
	}

	/**
	 * testGetSuccessMediaTypes() expects an array with all error Media Types for either json or xml
	 *
	 * @return void
	 */
	public function testGetErrorMediaTypes() {
		$expected = array('json', 'jsonVndError');
		$this->assertEqual($this->RestKit->getErrorMediaTypes('json'), $expected);

		$expected = array('xml', 'xmlVndError');
		$this->assertEqual($this->RestKit->getErrorMediaTypes('xml'), $expected);
	}

	/**
	 * testMimeTypes() tests if all implemented Media Types are added to the CakeResponse by
	 * mapping types to aliases (and vice versa)
	 */
	public function testMimeTypes() {

		// plain
		$this->assertEqual($this->controller->response->getMimeType('json'), 'application/json');
		$this->assertEqual($this->controller->response->getAlias('application/json'), 'json');
		$this->assertEqual($this->controller->response->getMimeType('xml'), 'application/xml');
		$this->assertEqual($this->controller->response->getAlias('application/xml'), 'xml');

		// HAL
		$this->assertEqual($this->controller->response->getMimeType('jsonHal'), 'application/hal+json');
		$this->assertEqual($this->controller->response->getAlias('application/hal+json'), 'jsonHal');
		$this->assertEqual($this->controller->response->getMimeType('xmlHal'), 'application/hal+xml');
		$this->assertEqual($this->controller->response->getAlias('application/hal+xml'), 'xmlHal');

		// vnd.error
		$this->assertEqual($this->controller->response->getMimeType('jsonVndError'), 'application/vnd.error+json');
		$this->assertEqual($this->controller->response->getAlias('application/vnd.error+json'), 'jsonVndError');
		$this->assertEqual($this->controller->response->getMimeType('xmlVndError'), 'application/vnd.error+xml');
		$this->assertEqual($this->controller->response->getAlias('application/vnd.error+xml'), 'xmlVndError');
	}

	/**
	 * testGetPreferredSuccessType method
	 *
	 * @return void
	 */
	public function testGetPreferredSuccessType() {

	}

	/**
	 * testGetPreferredErrorType method
	 *
	 * @return void
	 */
	public function testGetPreferredErrorType() {

	}

	/**
	 * testGetSpecificSuccessType method
	 *
	 * @return void
	 */
	public function testGetSpecificSuccessType() {

	}

	/**
	 * testGetSpecificErrorType method
	 *
	 * @return void
	 */
	public function testGetSpecificErrorType() {

	}

	/**
	 * testIsValidRestKitRequest method
	 *
	 * @return void
	 */
	public function testIsValidRestKitRequest() {

	}

	/**
	 * testRoutes method
	 *
	 * @return void
	 */
	public function testRoutes() {

	}

	/**
	 * testPrefers method
	 *
	 * @return void
	 */
	public function testPrefers() {

	}

	/**
	 * testPreferredFamilyIs method
	 *
	 * @return void
	 */
	public function testPreferredFamilyIs() {

	}

	/**
	 * testGetAcceptTypes method
	 *
	 * @return void
	 */
	public function testGetAcceptTypes() {

	}

	/**
	 * testIsException method
	 *
	 * @return void
	 */
	public function testIsException() {

	}

	/**
	 * testHasOption method
	 *
	 * @return void
	 */
	public function testHasOption() {

	}

	/**
	 * testValidOption method
	 *
	 * @return void
	 */
	public function testValidOption() {

	}

}
