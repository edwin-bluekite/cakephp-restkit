<?php

$config['RestKit'] = array(
    'version' => '1.0.1',
    'Request' => array(
	'prefix' => 'v1', // prefix string to enable, false to disable
	'forcePrefix' => false     // true will disable the default CakePHP routes (allowing only prefixed access)
    ),
    'Response' => array(
	'mediaTypes' => array(
	    'success' => 'hal', // supported Media Types: 'hal'
	    'error' => 'vnd-error'    // supported Media Types: 'vnd-error'
	)
    ),
    'Authenticate' => false, // turn on/off Basic authentication using Cake AuthComponent
    'Documentation' => array(
	'errors' => 'http://www.bravo-kernel.com/docs/errors', // full URL pointing to your API error documentation
    )
);

// ===================================================================================
// Define custom statusCodes if you:
// - require a HTTP Status Codes that is not already present in the CakePHP
//   core and is not already auto-loaded by the RestKit plugin
// - want to override Status Code messages (not advized)
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