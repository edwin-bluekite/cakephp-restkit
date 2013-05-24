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
	public $template = '';
	public $error = null;
	public $method = '';

	/**
	 * _getController() is an override of the default Cake method (in subclasses) and is used
	 * to send CUSTOM HTTP Status Codes
	 *
	 * @param Exception $exception The exception to get a controller for.
	 * @return Controller
	 */
	protected function _getController($exception) {
		$controller = parent::_getController($exception);
		$controller->response->httpCodes(Configure::read('RestKit.Response.statusCodes'));
		return $controller;
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
	public function restKitDISABLED(RestKitException $error) {

		CakeLog::write('error', 'RestKitExceptionRenderer: entered restKit');
//		pr("_cakeError is of class: " . get_class($error) . "\n");
//		pr("DATA BELOW:\n");
//		$serialized = $error->getMessage();
//		echo "\n\n$serialized";
//		$unserialized = unserialize($serialized);
//		pr($unserialized);
//		die();

		$this->_setRichErrorInformation($error);
		$this->_outputMessage($this->template);  // make sure RestKitView is used
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

		CakeLog::write('error', 'RestKitExceptionRenderer: entered _cakeError');
		if ($this->controller->isRest) {
			$this->_setRichErrorInformation($error);
			$this->_outputMessage($this->template);
		} else {
			$url = $this->controller->request->here();
			$code = ($error->getCode() >= 400 && $error->getCode() < 506) ? $error->getCode() : 500;
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
	}

	/**
	 * error400() overrides the default Cake function so we can respond with rich XML/JSON errormessages
	 *
	 * @note DONE (TESTED SUCCESSFULLY)
	 * @param CakeException $error
	 * @return void
	 */
	public function error400($error) {

		CakeLog::write('error', 'RestKitExceptionRenderer: entered error400');
		if ($this->controller->isRest) {
			$this->_setRichErrorInformation($error);
			$this->_outputMessage($this->template);
		} else {
			$message = $error->getMessage();
			if (!Configure::read('debug') && $error instanceof CakeException) {
				$message = __d('cake', 'Not Found');
			}
			$url = $this->controller->request->here();
			$this->controller->response->statusCode($error->getCode());
			$this->controller->set(array(
			    'name' => h($message),
			    'url' => h($url),
			    'error' => $error,
			    '_serialize' => array('name', 'url')
			));
			$this->_outputMessage('error400');
		}
	}

	/**
	 * error500() overrides the default Cake function so we can respond with rich XML/JSON errormessages
	 *
	 * @param CakeException $error
	 * @return void
	 */
	public function error500($error) {

		CakeLog::write('error', 'RestKitExceptionRenderer: entered error500');
		if ($this->controller->isRest) {
			$this->_setRichErrorInformation($error);
			$this->_outputMessage($this->template);
		} else {

			$message = $error->getMessage();
			if (!Configure::read('debug')) {
				$message = __d('cake', 'An Internal Error Has Occurred.');
			}
			$url = $this->controller->request->here();
			$code = ($error->getCode() > 500 && $error->getCode() < 506) ? $error->getCode() : 500;
			$this->controller->response->statusCode($code);
			$this->controller->set(array(
			    'name' => h($message),
			    'message' => h($url),
			    'error' => $error,
			    '_serialize' => array('name', 'message')
			));
			$this->_outputMessage('error500');
		}
	}

	/**
	 * _setRichErrorInformation() is used to set up extra variables required for producing
	 * rich REST error-information
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
	private function _setRichErrorInformation($error) {

		// respond differently when in debug mode
		$debug = Configure::read('debug');

		// normalize passed array with error-information
		$errorData = json_decode($error->getMessage(), true);

		// add the Exception class to improve error readability
		$errorClass = get_class($error);

		// if no rich error info was passed (eg for RuntimeExceptions, construct it ourselves)
		if (!is_array($errorData)) {

			$errorData['message'] = $error->getMessage();
			$errorData['code'] = $error->getCode();
			if ($debug) {
				$errorData['class'] = $errorClass;
				$errorData['file'] = $error->getFile();
				$errorData['line'] = $error->getLine();
				$errorData['trace'] = $error->getTraceAsString();
			}
		}

		// Handle debug/non-debug mode differently
		if ($debug == 0) {

			// reset message in production mode
			if ($errorData['code'] == 404) {
				$errorData['message'] = 'Not Found';
			}
			if ($errorData['code'] >= 500 && $errorData ['code'] < 506) {
				$errorData['message'] = 'An Internal Error Has Occurred';
			}

			// get variables
			$vndData = $this->_getVndData($errorData);
			$viewData = array(
			    'logRef' => $vndData['error_id'],
			    'message' => $errorData['message'],
			    'links' => array(
				'help' => array(
				    'href' => Configure::read('RestKit.Documentation.errors') . '/' . $vndData['help_id'],
				    'title' => 'Error information'
			)));
		} else {
			// add Exception class to the top of the array for readability
			$errorData = array_merge(array('class' => $errorClass), $errorData);

			//$errorData['class'] = get_class($error); // adding the calling error/exception class seems useful
			$viewData['debug'] = $errorData;  // only pass debug info to the RestKitView
		}

		// set up the 'Exception' viewVar so that RestKitJsonView and RestKitXmlView will
		// detect it and will use _serializeException() instead of default _serialize()
		$exception['Exception'] = array();
		array_push($exception['Exception'], $viewData);
		$this->controller->set($exception);

		// set the correct response header
		$this->_setHttpResponseHeader($errorData['code']);
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
	 * @param type $errorData
	 * @return type
	 */
	private function _getVndData($errorData) {
		$out['hash'] = $this->_getVndErrorHash($errorData['code'], $errorData['message']);

		$vndIds = $this->_getVndErrorIds($out['hash']);
		if ($vndIds) {
			$out['error_id'] = $vndIds['error_id'];
			$out['help_id'] = $vndIds['help_id'];
		} else {
			$result = $this->_saveVndError($out['hash'], $errorData['code'], $errorData['message']);
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

		// see if the vndError
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

		$result = $this->VndError->save(array(
		    'hash' => $hash,
		    'status_code' => $code, //$errorData['code'],
		    'message' => $message  //$errorData['message'],
		));

		if (!empty($result)) {
			$data['VndErrorHelp']['vnd_error_id'] = $this->VndError->id;
			$this->VndError->VndErrorHelp->save($data);

			return array(
			    'error_id' => $this->VndError->id,
			    'help_id' => $this->VndError->VndErrorHelp->id
			);
		}
	}

}