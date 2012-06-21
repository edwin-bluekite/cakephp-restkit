# CakePHP RestKit plugin

This plugin will instantly add some spice to the default Cake REST functionality:

* return custom HTTP Status Codes
* return custom XML/Json errors using a custom ExceptionHandler
* use vaidation rules to handle URI options

## Requirements

The RestKit plugin requires CakePHP 2.2

## Installation

Clone the repository into /app/Plugin/RestKit:

     git submodule add git@github.com:bravo-kernel/cakephp-restkit.git Plugin/RestKit


Enable the RestKit plugin in /app/Config/bootstrap.php:

    CakePlugin::load(array(
        'RestKit' => array('bootstrap' => true),
    ));

Change the default ExceptionHandler in /app/Config/core.php:

    Configure::write('Exception', array(
        'handler' => 'ErrorHandler::handleException',
        'renderer' => 'RestKit.RestKitExceptionRenderer',
        'log' => true
    ));


# Documentation

## Configuration

All options can be configurated by editing /app/Plugin/RestKit/Config/bootstrap.php.

* **enableOptionValidation**: set to 'true' to turn validation on
* HTTP Status Codes

## Usage

### Automatic serializing

Never worry about reformatting your arrays for _serialize() again. RestKit will automagically
handle all array reformatting for you.

    function index() {
        $users = $this->User->find('all');
        $this->RestKit->render(array('users' => $users));
    }

### Custom Exceptions
Use the RestKitException to return errors with custom HTTP Status Codes and rich
error information:

    throw new RestKitException(array('message' => 'You are overloading my API', 'errorCode' => 12345), 666);

To return the following XML along with a Response Header using Status Code 666 and message 'Something very evil':

    <response>
      <status>666</status>
      <message>You are overloading my API</message>
      <code>12345</code>
      <moreInfo>http://www.bravo-kernel.com/docs/errors/12345</moreInfo>
    </response>

**Please note** that you can add your own Status Codes and messages by defining them in bootstrap.php

### Validating URI parameters

Validate URI options by simply defining your default options (all others passed
options will be ignored):

    function index() {
        $options = $this->RestKit->parseUriOptions(array(
            'sort' => 'asc',
            'limit' => 10));
        $users = $this->User->find('all');
        $this->RestKit->render(array('users' => $users));
    }

RestKit supports out-of-the box validation for the following URI options

* **sort** either asc or desc

# TODO

* add errorcode system to make them selectable in your IDE and to prevent having to pass them as a parameter
* add an extra 'exception' tag for returned error XML (requires overriding default XmlView somehow)
* extend validations for known URI-options
* enable/disable URI-options from the bootstrap
* add tests