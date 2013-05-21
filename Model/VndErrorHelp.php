<?php

App::uses('AppModel', 'Model');

/**
 * VndErrorInformation Model
 *
 * @property VndError $VndError
 */
class VndErrorHelp extends AppModel {

	/**
	 * Validation rules
	 *
	 * @var array
	 */
	public $validate = array(
	    'vnd_error_id' => array(
		'numeric' => array(
		    'rule' => array('numeric'),
		),
	    ),
	);

	/**
	 * belongsTo associations
	 *
	 * @var array
	 */
	public $belongsTo = array(
	    'VndError' => array(
		'className' => 'VndError',
		'foreignKey' => 'vnd_error_id',
	    )
	);

}
