<?php

App::uses('Controller', 'Controller');
App::uses('RequestHandlerComponent', 'Controller/Component');
App::uses('AuthComponent', 'Controller/Component');
App::uses('RestKitComponent', 'RestKit.Controller/Component');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('Router', 'Routing');
App::uses('JsonView', 'View');
App::uses('XmlView', 'View');

/**
 * RestKitTestController class (prev. RequestHandlerTestController)
 *
 * @package       RestKit.Test.Case.Controller.Component
 */
class RestKitTestController extends Controller {

	/**
	 * uses property
	 *
	 * @var mixed null
	 */
	public $uses = null; // does not use a table !!!!!

}

/**
 * CustomJsonView class
 */
class CustomJsonView extends JsonView {

}

/**
 * RestKitComponentTest class (prev. RequestHandlerComponentTest)
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

		$this->RequestHandler = new RequestHandlerComponent($this->Controller->Components);

		// set up Auth
		$collection = new ComponentCollection();
		$collection->init($this->Controller);
		$this->Controller->Auth = new AuthComponent($collection);

		$this->RestKit = new RestKitComponent($this->Controller->Components);
		$this->_extensions = Router::extensions();
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		unset($this->RestKit, $this->Controller);
		if (!headers_sent()) {
			header('Content-type: text/html'); //reset content type.
		}
		call_user_func_array('Router::parseExtensions', $this->_extensions);
	}

	/**
	 * Test custom callback detector for plain JSON
	 *
	 * @return void
	 */
	public function testJsonPlainDetector() {
		$this->assertNull($this->RequestHandler->ext);
		$_SERVER['HTTP_ACCEPT'] = 'application/json';

		$this->RequestHandler->initialize($this->Controller);
		$this->Controller->Auth->initialize($this->Controller);
		$this->RestKit->initialize($this->Controller);
		$this->assertTrue($this->Controller->request->is('json'));
	}

	/**
	 * Test custom callback detector for plain XML
	 *
	 * @return void
	 */
	public function testJsonXmlDetector() {
		$this->assertNull($this->RequestHandler->ext);
		$_SERVER['HTTP_ACCEPT'] = 'application/xml';

		$this->RequestHandler->initialize($this->Controller);
		$this->Controller->Auth->initialize($this->Controller);
		$this->RestKit->initialize($this->Controller);
		$this->assertTrue($this->Controller->request->is('xml'));
	}

	/**
	 * Test custom callback detector for either plain JSON or plain XML
	 *
	 * @todo ADD check for both JSON and XML header
	 * @return void
	 */
	public function testPlainDetector() {
		$this->assertNull($this->RequestHandler->ext);
		$this->RequestHandler->initialize($this->Controller);
		$this->Controller->Auth->initialize($this->Controller);
		$this->RestKit->initialize($this->Controller);

		$_SERVER['HTTP_ACCEPT'] = 'application/xml';
		$this->assertTrue($this->Controller->request->is('plain'));

		$_SERVER['HTTP_ACCEPT'] = 'application/json';
		$this->assertTrue($this->Controller->request->is('plain'));

		$_SERVER['HTTP_ACCEPT'] = 'application/other';
		$this->assertFalse($this->Controller->request->is('plain'));
	}


	/**
	 * Test custom callback detector for json-HAL
	 *
	 * @return void
	 */
	public function testJsonHalDetector() {
		$this->assertNull($this->RequestHandler->ext);
		$_SERVER['HTTP_ACCEPT'] = 'application/hal+json';
		//Router::parseExtensions('json', 'xml');

		$this->RequestHandler->initialize($this->Controller);
		$this->Controller->Auth->initialize($this->Controller);
		$this->RestKit->initialize($this->Controller);
		$this->assertTrue($this->Controller->request->is('jsonHal'));
	}

	/**
	 * Test custom callback detector for xml-HAL
	 *
	 * @return void
	 */
	public function testXmlHalDetector() {
		$this->assertNull($this->RequestHandler->ext);
		$_SERVER['HTTP_ACCEPT'] = 'application/hal+xml';

		$this->RequestHandler->initialize($this->Controller);
		$this->Controller->Auth->initialize($this->Controller);
		$this->RestKit->initialize($this->Controller);
		$this->assertTrue($this->Controller->request->is('xmlHal'));
	}

	/**
	 * Test custom callback detector for JSON vnd.error
	 *
	 * @return void
	 */
	public function testJsonVndErrorDetector() {
		$this->assertNull($this->RequestHandler->ext);
		$_SERVER['HTTP_ACCEPT'] = 'application/vnd.error+json';

		$this->RequestHandler->initialize($this->Controller);
		$this->Controller->Auth->initialize($this->Controller);
		$this->RestKit->initialize($this->Controller);
		$this->assertTrue($this->Controller->request->is('jsonVndError'));
	}

	/**
	 * Test custom callback detector for XML vnd.error
	 *
	 * @return void
	 */
	public function testXmlVndErrorDetector() {
		$this->assertNull($this->RequestHandler->ext);
		$_SERVER['HTTP_ACCEPT'] = 'application/vnd.error+xml';

		$this->RequestHandler->initialize($this->Controller);
		$this->Controller->Auth->initialize($this->Controller);
		$this->RestKit->initialize($this->Controller);
		$this->assertTrue($this->Controller->request->is('xmlVndError'));
	}

}