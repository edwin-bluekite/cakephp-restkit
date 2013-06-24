<?php

App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('Router', 'Routing');

App::uses('ComponentCollection', 'Controller');
App::uses('Component', 'Controller');

App::uses('RequestHandlerComponent', 'Controller/Component');
App::uses('AuthComponent', 'Controller/Component');
App::uses('RestKitComponent', 'RestKit.Controller/Component');

/**
 * RestKitTestController
 *
 * @package       RestKit.Test.Case.Controller.Component
 */
class RestKitTestController extends Controller {


}


/**
 * RestKitComponent Test Case
 *
 */
class RestKitComponentTest extends CakeTestCase {

	public $Controller;
	public $RequestHandler;
	public $RestKit;
	public $Auth;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->_init();
	}

	/**
	 * init method
	 *
	 * @return void
	 */
	protected function _init() {
		$request = new CakeRequest('controller_posts/index');
		$response = new CakeResponse();
		$this->Controller = new RestKitTestController($request, $response);
		$this->Controller->constructClasses();

		// set up components
		$this->RequestHandler = new RequestHandlerComponent($this->Controller->Components);
		$this->RequestHandler->initialize($this->Controller);

		$this->Auth = new AuthComponent($this->Controller->Components);
		$this->Auth->initialize($this->Controller);

		$Collection = new ComponentCollection();
		$this->RestKit = new RestKitComponent($Collection);
		$this->RestKit->initialize($this->Controller);
	}


	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->Controller, $this->RequestHandler, $this->Auth, $this->RestKit);
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
		$this->assertEqual($this->Controller->response->getMimeType('json'), 'application/json');
		$this->assertEqual($this->Controller->response->mapType('application/json'), 'json');

		// HAL
		$this->assertEqual($this->Controller->response->getMimeType('jsonHal'), 'application/hal+json');
		$this->assertEqual($this->Controller->response->mapType('application/hal+json'), 'jsonHal');
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
