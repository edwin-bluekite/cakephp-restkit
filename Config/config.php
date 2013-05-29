<?php

$config['RestKit'] = array(
    'version' => '1.0.1',

    'Request' => array(
	'prefix' => 'v1',						// prefix string to enable, false to disable
	'forcePrefix' => false						// true will disable the default CakePHP routes (allowing only prefixed access)
    ),

    'Authenticate' => false,						// turn on/off Basic authentication using Cake AuthComponent

    'Documentation' => array(
	'errors' => 'http://www.bravo-kernel.com/docs/errors',		// full URL pointing to your API error documentation
    ),

    'Response' => array(
	'statusCodes' => array(						// override or append the default CakePHP HTTP Status Codes
	    422 => 'Unprocessable Entity',				// commonly used REST code for e.g. failed validations
	    428 => 'Precondition Required',				// proposed draft
	    429 => 'Too Many Requests',					// proposed draft
	    431 => 'Request Header Fields Too Large',			// proposed draft
	    511 => 'Network Authentication Required',			// proposed draft
	    666 => 'Something Very Evil',				// custom (non-standard REST!)
	)
    ),
);