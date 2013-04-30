<?php

/**
 * Description of RestOption
 *
 * @author bravo-kernel
 */
class RestOption extends RestKitAppModel {

	/**
	 * useTable set to false because our model does not use a database table
	 *
	 * @var boolean
	 */
	var $useTable = false;

	/**
	 * Here we define validations for common REST query strings/parameters
	 * so they can be validated from the component before being used in any
	 * database queries.
	 *
	 * @var array
	 */
	public $validate = array(
	    'order' => array(
		'rule' => array('inList', array('asc', 'desc')),
		'allowEmpty' => false,
		'message' => 'Use either asc or desc'
	    ),
	    'limit' => array(
		'numeric' => array(
		    'rule' => array('numeric'))
	    ),
	);

}