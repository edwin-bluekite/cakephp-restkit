<?php

/**
 * Description of AppExceptionRenderer
 *
 * @author bravo-kernel
 *
 * @todo make the moreInfo URL configurable
 */
App::uses('ExceptionRenderer', 'Error');
App::uses('CakeErrorController', 'Controller');
App::uses('RestKitErrorHandler', 'RestKit.Lib/Error');
App::uses('Controller', 'Controller');
App::uses('RestKitComponent', 'RestKit.Controller/Component');
App::uses('CakeLog', 'Log');

class RestKitExceptionRenderer extends ExceptionRenderer {

	public $controller = null;
	public $request = null;
	public $template = '';
	public $error = null;
	public $method = '';

	/**
	 * $statusCodes contains all additional (non Cake core) HTTP Status Codes that
	 * are required by this plugin. Custom status codes defined in the config file
	 * will be merged with this set.
	 *
	 * @var array
	 */
	private $statusCodes = array(
	    422 => 'Unprocessable Entity' // commonly used REST code for failed validations
	);

	/**
	 * __construct() is used to ......
	 *
	 * @param Exception $exception
	 */
	public function __construct(Exception $exception) {

		// merge required status codes with additional ones found in the configfile
		$this->statusCodes = Hash::mergeDiff(Configure::read('RestKit.statusCodes'), $this->statusCodes);

		parent::__construct($exception);
	}

	/**
	 * _getController() is an override of the default Cake method (in subclasses) and is used
	 * to send CUSTOM HTTP Status Codes
	 *
	 * @param Exception $exception The exception to get a controller for.
	 * @return Controller
	 */
	protected function _getController($exception) {
		$this->controller = parent::_getController($exception);
		$this->request = $this->controller->request;
		$this->controller->response->httpCodes($this->statusCodes); // make custom statuscodes available for use
		return $this->controller;
	}

	/**
	 * restKit() is used when throwing a RestKitException
	 *
	 * Calling it with
	 *
	 * @todo re-implement
	 * @todo (still?) fix crash when throwing RestKitException() without message-string
	 *
	 * @param type RestKitException $error
	 * return void
	 */
	public function restKit(RestKitException $error) {
		$this->restError($error);
	}

	/**
	 * _cakeError() overrides the default Cake function.
	 *
	 * If the request is json/xml we respond with rich XML/JSON errormessages, otherwise
	 * we use the default Cake response (copied 1-on-1 from the Cake class)
	 *
	 *
	 * @note DONE (TESTED SUCCESSFULLY)
	 * @param CakeException $error
	 * @return void
	 */
	protected function _cakeError(CakeException $error) {

		// process dynamic Cake error variables regardless of following logic
		$code = ($error->getCode() >= 400 && $error->getCode() < 506) ? $error->getCode() : 500;

		// handle REST errors
		if ($this->controller->RestKit->isRest) {
			$this->restError($error, array('code' => $code));
		}

		// not REST, render the default Cake HTML error
		$url = $this->request->here();
		$this->controller->response->statusCode($code);
		$this->controller->set(array(
		    'code' => $code,
		    'url' => h($url),
		    'name' => h($error->getMessage()),
		    'error' => $error,
		    '_serialize' => array('code', 'url', 'name')
		));
		$this->controller->set($error->getAttributes());
		$this->_outputMessage($this->template);
	}

	/**
	 * error400() overrides the default Cake function so we can respond with rich XML/JSON errormessages
	 *
	 * @note DONE (TESTED SUCCESSFULLY)
	 * @param CakeException $error
	 * @return void
	 */
	public function error400($error) {

		// process dynamic Cake error variables regardless of following logic
		$message = $error->getMessage();
		if (!Configure::read('debug') && $error instanceof CakeException) {
			$message = __d('cake', 'Not Found');
		}

		// handle REST errors
		if ($this->controller->RestKit->isRest) {
			$this->restError($error, array('message' => $message));
		}

		// not REST, render the default Cake HTML error
		$url = $this->request->here();
		$this->controller->response->statusCode($error->getCode());
		$this->controller->set(array(
		    'name' => h($message),
		    'url' => h($url),
		    'error' => $error,
		    '_serialize' => array('name', 'url')
		));
		$this->_outputMessage('error400');
	}

	/**
	 * error500() overrides the default Cake function so we can respond with rich XML/JSON errormessages.
	 *
	 * @param CakeException $error
	 * @return void
	 */
	public function error500($error) {

		// process dynamic Cake error variables regardless of following logic
		$message = $error->getMessage();
		if (!Configure::read('debug')) {
			$message = __d('cake', 'An Internal Error Has Occurred.');
		}
		$code = ($error->getCode() > 500 && $error->getCode() < 506) ? $error->getCode() : 500;

		// handle REST errors
		if ($this->controller->RestKit->isRest) {
			$this->restError($error, array('message' => $message, 'code' => $code));
		}

		// not REST, render the default Cake HTML error
		$url = $this->request->here();
		$this->controller->response->statusCode($code);
		$this->controller->set(array(
		    'name' => h($message),
		    'message' => h($url),
		    'error' => $error,
		    '_serialize' => array('name', 'message')
		));
		$this->_outputMessage('error500');
	}

	/**
	 * restError() is used to render all errors in REST format
	 *
	 * @param CakeException $error
	 * @param type $overrides
	 */
	public function restError(CakeException $error, $overrides = null){

		// $message and $code vary if debug = 0
		$message = $error->getMessage();
		if (isset($overrides['message'])){
			$message = $overrides['message'];
		}
		$code = $error->getCode();
		if (isset($overrides['code'])){
			$code = $overrides['code'];
		}

		// set 'Exception' data for the view
		if ($this->controller->RestKit->prefers('vndError')) {
			$this->_setVndError($error);
		} else {
			$this->_setPlainError($code, $message);
		}
		$this->_setHttpResponseHeader($code);
		$this->_outputMessage($this->template);
		die();
	}


	/**
	 * _setPlainError() is used to set the 'Exception' viewVar for plain json/xml errors
	 *
	 * @param int $code
	 * @param string $message
	 */
	private function _setPlainError($code = null, $message = null) {
		$this->controller->set(array('RestKit' => array(
			'Exception' => array(
			    'code' => $code,
			    'message' => $message
		))));
	}

	/**
	 * _setVndError() is used to set the 'Exception' viewVar required for producing rich vnd.error responses
	 *
	 * Please note that only the serialized variables will appear in the JSON/XML output and
	 * will appear in the same order as they are serialized here.
	 *
	 * Also note that we set $name and $url here even though they are not used for JSON/XML because
	 * they are required by the default HTML error-views.
	 *
	 * @todo add support for multiple errors !!!!
	 * @todo maybe remove serialization (seems no longer required now that we use the default ErrorHandler)
	 *
	 * @param CakeException $error
	 */
	private function _setVndError($error) {

		// set up variables
		$class = get_class($error);
		$code = $error->getCode();
		$message = $error->getMessage();

		// reset $message in production-mode
		$debug = Configure::read('debug');
		if ($debug == 0) {
			if ($code == 404) {
				$message = 'Not Found';
			}
			if ($code >= 500 && $code < 506) {
				$message = 'An Internal Error Has Occurred';
			}
		}

		// normalize passed array with error-information
		// @todo: CUSTOM JSON DECODES NOT IMPLEMENTED YET (PROBABLY WHEN RESTKIT-EXCEPTIONS ARE DONE)
		$errorData = json_decode($error->getMessage(), true);

		// NO RICH ERROR INFO passed so construct a single error entity ourselves (e.g. for RuntimeExceptions)
		if (!is_array($errorData)) {

			CakeLog::write('error', 'setVndError: errordata not an array');

			$vndData = $this->_getVndData($code, $message);
			$errorData[0] = array(
			    'logRef' => $vndData['error_id'],
			    'message' => $message,
			    'links' => array(
				'help' => array(
				    'href' => Configure::read('RestKit.Documentation.errors') . '/' . $vndData['help_id'],
				    'title' => 'Error information'
			)));

			if ($debug > 0) {
				$errorData[0]['debug']['code'] = $code;
				$errorData[0]['debug']['class'] = $class;
				$errorData[0]['debug']['message'] = $message;
				$errorData[0]['debug']['file'] = $error->getFile();
				$errorData[0]['debug']['line'] = $error->getLine();
				$errorData[0]['debug']['trace'] = $error->getTraceAsString();
			}
		}else{
		// RICH ERROR INFO PASSED, process per error-entity
			$i = 0;
			foreach ($errorData as $entity) {
				if ($debug == 0) {

					$vndData = $this->_getVndData($code, $entity['message']);
					$errorData[$i] = array(
					    'logRef' => $vndData['error_id'],
					    'message' => $entity['message'],
					    'links' => array(
						'help' => array(
						    'href' => Configure::read('RestKit.Documentation.errors') . '/' . $vndData['help_id'],
						    'title' => 'Error information'
					)));
				} else {
					// set required fields so missing will throw an exception
					$errorData[$i] = array(
					    'code' => $code,
					    'class' => $class,
					    'message' => $entity['message']
					);

					// add any additionaly passed fields
					foreach ($entity as $key => $value) {
						if (!isset($errorData[$i][$key])) {
							//echo "$key is nog niet gezet, toevoegen\n";
							$errorData[$i][$key] = $value;
						}
					}

					// add some addtional debug-information to the FIRST error only
					if ($i == 0) {
						$errorData[0]['file'] = $error->getFile();
						$errorData[0]['line'] = $error->getLine();
						$errorData[0]['trace'] = $error->getTraceAsString();
					}
				}
				$i++; // next error-entity
			}
		}

		// set the 'RestKit.Exception' viewVar so that RestKitJsonView and RestKitXmlView will
		// recognize it and will use _serializeException() instead of the default _serialize()
		$this->controller->set(array('RestKit' => array(
			'Exception' => $errorData)));
	}

	/**
	 * _setHttpResponseHeader() is used to set the HTTP Response Header.
	 *
	 * Will reset $code to 500 if the passed code is not present in the
	 * RequestResponse::httpCodes() array to prevent an internal error.
	 *
	 * @param int $code
	 * @return void
	 */
	private function _setHttpResponseHeader($code = null) {
		$httpCode = $this->controller->response->httpCodes($code);
		if ($httpCode[$code]) {
			$this->controller->response->statusCode($code);
		} else {
			$this->controller->response->statusCode(500);
			$code = 500;
		}
	}

	/**
	 * getVndData() is a convenience function to retrieve .....
	 *
	 * @param type $code
	 * @param type $message
	 * @return type
	 */
	private function _getVndData($code, $message) {

		// do not create vndError database entries in debug mode
		if (Configure::read('debug') > 0){
			return;
		}

		$hash = $this->_getVndErrorHash($code, $message);

		$vndIds = $this->_getVndErrorIds($hash);
		if ($vndIds) {
			$out['error_id'] = $vndIds['error_id'];
			$out['help_id'] = $vndIds['help_id'];
		} else {
			$result = $this->_saveVndError($hash, $code, $message);
			$out['hash'] = $hash;
			$out['error_id'] = $result['error_id'];
			$out['help_id'] = $result['help_id'];
		}
		return $out;
	}

	/**
	 * _getVndErrorHash() generates a hash to uniquely identify vnd.errors using a
	 * concatenation of both error-code and error-message
	 *
	 * @param int $code
	 * @param string $message
	 * @return string unique MD5 hash
	 */
	private function _getVndErrorHash($code, $message) {
		return Security::hash($code . $message, 'md5', false);
	}

	/**
	 * _getVndErrorIds() return the database ids for VndError and associated VndErrorHelp
	 *
	 * If no existing records are found, they will be created
	 *
	 * @todo: prevent breaking when the VndErrorHelp save() fails
	 * @param type $hash
	 * @return array
	 */
	private function _getVndErrorIds($hash) {

		// see if the vndError already exists
		$this->VndError = ClassRegistry::init('RestKit.VndError');
		$result = $this->VndError->find('first', array(
		    'conditions' => array(
			'VndError.hash' => $hash),
		    'contain' => array('VndErrorHelp')
		));

		// existing error: return IDs
		if ($result) {
			return array(
			    'error_id' => $result['VndError']['id'],
			    'help_id' => $result['VndErrorHelp']['id']
			);
		}
		return false;
	}

	/**
	 * _saveVndError() creates a new VndError and associated VndErrorHelp
	 *
	 * @todo harden failed saves!!!
	 *
	 * @param type $hash (MD5 hash of concatenated $code.$message)
	 * @param type $code
	 * @param type $message
	 * @return type
	 */
	private function _saveVndError($hash, $code, $message) {

		$this->VndError->create();
		$result = $this->VndError->save(array(
		    'hash' => $hash,
		    'status_code' => $code,
		    'message' => $message
		));
		$errorId = $this->VndError->id;

		if (!empty($result)) {
			$data['VndErrorHelp']['vnd_error_id'] = $errorId;
			$this->VndError->VndErrorHelp->create();
			$this->VndError->VndErrorHelp->save($data);

			return array(
			    'error_id' => $errorId,
			    'help_id' => $this->VndError->VndErrorHelp->id
			);
		}
	}

}