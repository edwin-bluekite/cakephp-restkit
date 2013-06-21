<?php

$config['RestKit'] = array(
    'version' => '1.0.1',
    'Request' => array(
	'enableExtensions' => false,	// false to throw 404s when using .json or .xml
	'prefix' => 'v1',		// prefix string to enable, false to disable
	'forcePrefix' => false		// true will disable the default CakePHP routes (allowing only prefixed access)
    ),
    'Authenticate' => false, // turn on/off Basic authentication using Cake AuthComponent
    'Documentation' => array(
	'errors' => 'http://www.bravo-kernel.com/docs/errors', // full URL pointing to your API error documentation
    )
);

// ===================================================================================
// Define custom statusCodes if you:
// a) require a HTTP Status Code that is either:
//   - not already defined in the CakePHP core (CakeResponse)
//   - not already auto-loaded by the RestKit plugin (RestKitExceptionRenderer)
// b) want to override Status Code messages (not advized)
//
// Additional codes auto-loaded by this plugin are:
//	422 => 'Unprocessable Entity' (commonly used REST code for failed validations)
// ===================================================================================
$config['RestKit']['statusCodes'] = array(
    428 => 'Precondition Required',			// proposed draft
    429 => 'Too Many Requests',				// proposed draft
    431 => 'Request Header Fields Too Large',		// proposed draft
    511 => 'Network Authentication Required',		// proposed draft
    666 => 'Something Very Evil',			// custom (non-standard REST!)
);