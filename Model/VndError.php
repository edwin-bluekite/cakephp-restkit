<?php

App::uses('AppModel', 'Model');

/**
 * VndError Model
 *
 * @property VndErrorInformation $VndErrorInformation
 */
class VndError extends AppModel {

	/**
	 * Validation rules
	 *
	 * @var array
	 */
	public $validate = array(
	    'status_code' => array(
		'numeric' => array(
		    'rule' => array('numeric'),
		),
	    ),
	    'message' => array(
		'notempty' => array(
		    'rule' => array('notempty'),
		),
	    ),
	    'hash' => array(
		'notempty' => array(
		    'rule' => array('notempty'),
		),
	    ),
	);

	/**
	 * hasOne associations
	 *
	 * @var array
	 */
	public $hasOne = array(
	    'VndErrorHelp'
	);

}
