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
		} else {
			$data = array(
			    'error' => $error,
			    'message' => htmlspecialchars($description),
			    'file' => $file,
			    'line' => $line,
			    'level' => $log,
			    'code' => $code,
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

	/**
	 * Set as the default exception handler by the CakePHP bootstrap process.
	 *
	 * This will either use custom exception renderer class if configured,
	 * or use the default ExceptionRenderer.
	 *
	 * @param Exception $exception
	 * @return void
	 * @see http://php.net/manual/en/function.set-exception-handler.php
	 */
	public static function handleException(Exception $exception) {

		$config = Configure::read('Exception');
		self::_log($exception, $config);



		$renderer = isset($config['renderer']) ? $config['renderer'] : 'ExceptionRenderer';
		if ($renderer !== 'ExceptionRenderer') {
			list($plugin, $renderer) = pluginSplit($renderer, true);
			App::uses($renderer, $plugin . 'Error');
		}
		try {
			$error = new $renderer($exception);
			$error->render();
		} catch (Exception $e) {
			set_error_handler(Configure::read('Error.handler')); // Should be using configured ErrorHandler
			Configure::write('Error.trace', false); // trace is useless here since it's internal
			$message = sprintf("[%s] %s\n%s", // Keeping same message format
				get_class($e), $e->getMessage(), $e->getTraceAsString()
			);
			trigger_error($message, E_USER_ERROR);
		}
	}

}