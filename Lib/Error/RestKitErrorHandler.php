<?php

App::uses('ErrorHandler', 'Error');
App::uses('Debugger', 'Utility');

/**
 * Description of RestKitErrorHandler
 *
 * @author bravo-kernel
 */
class RestKitErrorHandler extends ErrorHandler {

	/**
	 * handleError() is an override of the default CakePHP error handler.
	 *
	 * It abuses $message by using it to pass a serialized array with error-information to
	 * the RestKitExceptionHandler so we can create field-based json/xml responses.
	 *
	 * @param integer $code Code of error
	 * @param string $description Error description
	 * @param string $file File on which error occurred
	 * @param integer $line Line that triggered the error
	 * @param array $context Context
	 * @return boolean true if error was handled
	 */
	public static function handleError($code, $description, $file = null, $line = null, $context = null) {

		if (error_reporting() === 0) {
			return false;
		}
		$errorConfig = Configure::read('Error');
		list($error, $log) = self::mapErrorCode($code);
		if ($log === LOG_ERR) {
			return self::handleFatalError($code, $description, $file, $line);
		}

		if (Configure::read('debug') == 0) {
			$data = array(
			    'code' => 500,
			    'error' => $error,
			    'message' => htmlspecialchars($description),
			);
		}else{
			$data = array(
			    'level' => $log,
			    'code' => $code,
			    'error' => $error,
			    'message' => htmlspecialchars($description),
			    'file' => $file,
			    'line' => $line,
			    'context' => htmlspecialchars($context),
			    'start' => 2,
			    'path' => Debugger::trimPath($file),
			    'trace' => htmlspecialchars(Debugger::trace(array('start' => 1, 'format' => 'log')))
			);
		}

		// clean output buffer
		if (ob_get_level()) {
			ob_end_clean();
		}

		// call the RestKitExceptionRenderer
		$exceptionHandler = Configure::read('Exception.handler');
		call_user_func($exceptionHandler, new FatalErrorException(serialize($data), 500, $file, $line));
		die();
	}

}