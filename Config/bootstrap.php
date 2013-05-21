<?php

App::uses('RestKitException', 'RestKit.Lib/Error');
App::uses('RestKitErrorHandler', 'RestKit.Lib/Error');

// We need to load our config file here because initializing it from the App's bootstrap.php
// using CakePlugin::load(array('RestKit' => array('bootstrap' => true))
// would only do a require() and not a load() making the settings unavailable
// for use inside the plugin.
Configure::load('RestKit.config');

// Override the default ExceptionHandler with our own RestKitExceptionHandler
// so we can respond with rich errors using the vnd.error specification
Configure::write('Exception', array(
    'handler' => 'ErrorHandler::handleException',
    'renderer' => 'RestKit.RestKitExceptionRenderer',
    'log' => true
));

// Override the default ErrorHandler with RestKitErrorHandler so we can also
// process non-defined exceptions (like trigger_error())properly
Configure::write('Error', array(
    'handler' => 'RestKitErrorHandler::handleError',
    'level' => E_ALL & ~E_DEPRECATED,
    'trace' => true
));