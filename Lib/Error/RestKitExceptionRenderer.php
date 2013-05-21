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
	 * @todo fix crash when throwing RestKitException() without message-string
	 *
	 * @param type RestKitException $error
	 * return void
	 */
	public function restKit(RestKitException $error) {
		pr("_cakeError is of class: " . get_class($error) . "\n");
		pr("DATA BELOW:\n");
		$serialized = $error->getMessage();
		echo "\n\n$serialized";
		$unserialized = unserialize($serialized);
		pr($unserialized);
		die();

		$this->_setRichErrorInformation($error);
		$this->_outputMessage($this->template);  // make sure RestKitView is used
	}

	/**
	 * _cakeError() overrides the default Cake function so we can respond with rich XML/JSON errormessages
	 *
	 *
	 * @note DONE (TESTED SUCCESSFULLY)
	 * @param CakeException $error
	 * @return void
	 */
	protected function _cakeError(CakeException $error) {
		$this->_setRichErrorInformation($error);
		$this->_outputMessage($this->template);
	}

	/**
	 * error400() overrides the default Cake function so we can respond with rich XML/JSON errormessages
	 *
	 * @param CakeException $error
	 * @return void
	 */
	public function error400($error) {
		pr("Exception is of class: " . get_class($error) . "\n");
		$this->_setRichErrorInformation($error);
		$this->_outputMessage('error400');
	}

	/**
	 * error500() overrides the default Cake function so we can respond with rich XML/JSON errormessages
	 *
	 * @param CakeException $error
	 * @return void
	 */
	public function error500($error) {
		pr("Exception is of class: " . get_class($error) . "\n");
		$this->_setRichErrorInformation($error);
		$this->_outputMessage('error500');
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
	 *
	 * @param CakeException $error
	 */
	private function _setRichErrorInformation($error) {

		// normalize passed array with error-information
		$errorData = unserialize($error->getMessage());

		// prepare view-data
		$debug = Configure::read('debug');
		if ($debug == 0) {

			// get variables
			$vndHash = $this->_getVndErrorHash($errorData['code'], $errorData['description']);
			$vndIds = $this->_getVndErrorIds($vndHash);  //
			$vndErrorId = $vndIds['error'];
			$vndErrorHelpId = $vndIds['help'];

			$viewData = array(
			    'logRef' => $vndErrorId,
			    'message' => $errorData['description'],
			    'links' => array(
				'help' => array(
				    'href' => Configure::read('RestKit.Documentation.errors') . '/' . $vndErrorHelpId,
				    'title' => 'Error information'
			)));

		} else {
			$errorData['class'] = get_class($error);	// adding the calling error/exception class seems useful
			$viewData['debug'] = $errorData;		// only pass debug info to the RestKitView
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
	 * _getMoreInfo() auto-generates a moreInfo link by using the moreInfo base URL
	 * specified in the RestKit configfile) and then appending the HTTP Status Code.
	 *
	 * CakeExceptions will lead to links using the "valid" HTTP Status Code, e.g.:
	 * - http://www.yourapidocs.com/403
	 * - http://www.yourapidocs.com/500
	 *
	 * RestKitExceptions will lead to links using the default 500 or the $code parameter passed, e.g.:
	 * - http://www.apidocs.com/500
	 * - http://www.apidocs.com/12004
	 *
	 * @todo add support for link extension?
	 *
	 * @param type $error
	 * @return string
	 */
	private function _getMoreInfo($code) {
		$moreInfo = Configure::read('RestKit.Response.moreInfo') . '/' . $code;
		return $moreInfo;
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
			'VndError.hash' => $vndHash),
		    'contain' => array('VndErrorHelp')
		));

		// existing error: return IDs
		if ($result) {
			return array(
			    'error' => $result['VndError']['id'],
			    'help' => $result['VndErrorHelp']['id']
			);
		}

		// new error: create vndError with associated vndErrorHelp
		$result = $this->VndError->save(array(
		    'status_code' => $errorData['code'],
		    'message' => $errorData['description'],
		    'hash' => $hash
		));
		if (!empty($result)) {
			$data['VndErrorHelp']['vnd_error_id'] = $this->VndError->id;
			$this->VndError->VndErrorHelp->save($data);

			return array(
			    'error' => $this->VndError->id,
			    'help' => $this->VndError->VndErrorHelp->id
			);
		}
	}

}