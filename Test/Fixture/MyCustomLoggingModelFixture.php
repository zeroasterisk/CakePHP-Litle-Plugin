<?php
/**
 * MyCustomLoggingModel or whatever table/model name you want to use here
 *
 * This is an "example" of what you might want to use to log API interactions
 * in your own database (recommended)
 */
class MyCustomLoggingModelFixture extends CakeTestFixture {
	/**
	 * Name
	 *
	 * @var string
	 * @access public
	 * @package default
	 */
	public $name = 'MyCustomLoggingModel';

	/**
	 * Table
	 *
	 * @var string
	 * @access public
	 */
	public $table = 'litle_api_log';

	/**
	 * Fields
	 *
	 * @var array
	 * @access public
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'),
		'member_billing_event_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'key' => 'index'),
		'member_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'key' => 'index'),
		'transaction_id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 64, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'url' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'status' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 32, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'error' => array('type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'input' => array('type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'data' => array('type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'response' => array('type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'response_reason' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'avs_response' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'void_date' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'type' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 32, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'refund_amount' => array('type' => 'float', 'null' => false, 'default' => '0.00', 'length' => '8,2'),
		'response_code' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'response_raw' => array('type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'token_response' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'recycle_date' => array('type' => 'datetime', 'null' => true, 'default' => null, 'comment' => 'Recycle Advice Next Transaction Date'),
		'is_recycled' => array('type' => 'boolean', 'null' => false, 'default' => '0'),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'member_id' => array('column' => 'member_id', 'unique' => 0),
			'member_billing_event_id' => array('column' => 'member_billing_event_id', 'unique' => 0)
		),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM')
	);

	/**
	 * Records
	 *
	 * @var array
	 * @access public
	 */
	public $records = array(
	);

}
