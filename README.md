# CakePHP RestKit plugin

A stateless REST API plugin for CakePHP

## Version ##

Version 0.1 (tag 0.1.0) contains a working prototype.

**Features:**

* REST Maturity Level 3 (using HAL specification)
* Rich exception rendering (HAL compatible vnd.error specification)
* Basic authentication (enable in the config, docs will follow)
* Custom HTTP Status Codes
* Validation rules for query parameters
* Automatically REST-enables all controllers in your app
* Please note that this readme is terribly outdated (will update soon)

## Requirements

* CakePHP 2.4 or higher
* CakeDC Migrations plugin

## Support

Do you have a question, want to see a feature or have you found a bug?

Please visit our Github Issue page at https://github.com/alt3/cakephp-restkit/issues.

## Installation

Clone the repository into /app/Plugin/RestKit:

     git submodule add git://github.com/alt3/cakephp-restkit.git

Load the following components in /app/Controller/AppController.php

    public $components = array(
        'RequestHandler' => array(
		'viewClassMap' => array(
		    'json' => 'RestKit.RestKitJson',
		    'xml' => 'RestKit.RestKitXml')),
        'Auth' => array(
             'authenticate' => array(
                 'Basic' => array(
                 'fields' => array('username' => 'username')))),
        'RestKit.RestKit');

Enable the RestKit plugin in /app/Config/bootstrap.php:

    CakePlugin::load(array(
        'RestKit' => array(
            'bootstrap' => true,
            'routes' => true)));

Disable CakePHP default routing in /app/Config/routes.php:

    //require CAKE . 'Config' . DS . 'routes.php';

Run the migration to create the required tables

    ./cake Migrations.migration run all -app /var/www/yourapp --plugin RestKit


# Documentation

## Configuration

All options can be configurated by editing /app/Plugin/RestKit/Config/config.php.

## Usage

### JSON and XML rendering

This plugin uses custom viewless JSON and XML to render the results so no need to (ever) create any view files.
Identical to the Cake viewless rendering as described perfectly here: http://api20.cakephp.org/class/json-view.

A basic example:

    public function index() {
        $result = $this->Country->find('all', array('fields' => array('id')));
        if ($this->isRest){
            $this->set(array(
                'Countries' => $result,
                '_serialize' => array('Countries')));
        }
    }

For more examples of code-usage and corresponding output in HAL-XML and HAL-JSON see:

* **listing collections:** https://gist.github.com/bravo-kernel/5568181
* **viewing entities:** https://gist.github.com/bravo-kernel/5568855

### Default Exceptions

RestKit uses the RestKitExceptionRenderer to respond with rich REST errors using the
vnd.error specification and corresponding HTTP Status Codes.

_TODO: ADD EXAMPLE RESPONSES_

### RestKitExceptions

RestKitExceptions are useful if you want to provide informational feedback about your application's
internal usage because the passed error messages will appear in both debug and non-debug mode.

_OUTDATED EXAMPLES REMOVED TO PREVENT CONFUSION_

### Validating query parameters

All APIs need support for query parameters so that users can easily manipulate results
using simple URL arguments. For those unfamiliar with query parameters take a look
at the following examples:

* my.domain.com/users?order=asc
* my.domain.com/users?limit=10
* my.domain.com/users?order=asc&limit=10

RestKit provides out-of-the-box validation for a set of the most commonly used query parameters
so you can protect your API against stuff like SQL-injections and what have not. Currently the following
 query parameters are implemented:

* **sort** allow either 'asc' or 'desc'
* **limit** allow numeric only

**hasOption():**

    if ($this->RestKit->hasOption('order')){
        echo "Query parameter 'order' exists but might not have a (valid) value";
    }

**validOption():**

validOption() approves the value of a parameter if:

- the parameter is actually being used
- a matching validation rule exist
- the value passes the validation rule

(Without this line the bullets above break the mdown code below, no idea why)

    if ($this->RestKit->validOption('order')){
        echo "Value for parameter 'order' was either exactly asc or desc";
    }


**validationErrors:**

If validation fails you can access the validation errors using $this->RestKit->validationErrors.

    if (!$this->RestKit->validOption('limit')){
        pr($this->RestKit->validationErrors);
    }

### Detectors

The RestKit plugin comes with a couple of useful request detectors you can use in your application.

* **json**: returns true for standard json requests
* **xml**: returns true for standard xml requests
* **jsonHal**: returns true for HAL-specific json requests
* **xmlHal**: returns true for HAL-specific xml requests
* **hal**: returns true if the request is either jsonHal or xmlHal
* **rest**: returns true if the request is either json, xml, jsonHal or xmlHal

Usage example:

    if ($this->request->is('jsonHal')){
        echo "Request was made with the "application/hal+json" Accept Header";
    }

# Links

* REST Maturity Levels: http://martinfowler.com/articles/richardsonMaturityModel.html
* The HAL specification: http://stateless.co/hal_specification.html
* The vnd.error specification: https://github.com/blongden/vnd.error
