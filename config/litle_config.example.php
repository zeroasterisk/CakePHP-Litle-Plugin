<?php
/**
*
* NOTE: you can override any of these settings with 
Configure::write('Litle.config', array(
	'user' => 'MyApiUser', 
	'logModel' => 'MyLogModel', 
	'defaults' => array('sale' => array('orderSource' => 'recurring')),
	));
*/
class LITLE_CONFIG {
	var $config = array(
		'datasource' => 'Litle.LitleSource',
		// --- production environment
		'user' => '**********',
		'password' => '**********',
		'merchantId' => '**********',
		'url' => 'https://api.litle.com/vap/communicator/online',
		// --- test environment
		// 'url' => 'https://cert.litle.com/vap/communicator/online',
		// --- Other Configurations
		'logModel' => null, // null to disable transaction logging
		// eg: 'logModel' => 'LitleApiLog', // any model you have, which can save the request/response details
		'auto_orderId_if_missing' => true,
		'auto_id_if_missing' => true, // you should probably keep this as true
		'duplicate_window_in_seconds' => true, // protection against duplicate transactions (only used if auto_transactionId)
		// translate your local fields to special fields
		'field_map' => array(
			// litle ready field name		=> array of your possible field names
			'id' 							=> array('unique_id'),
			'orderId' 						=> array('bill_id', 'order_id'),
			'billToAddress.name' 			=> array('bill_name'),
			'billToAddress.addressLine1'	=> array('bill_address'),
			'billToAddress.addressLine2'	=> array('bill_address_2'),
			'billToAddress.addressLine3'	=> array('bill_address_3'),
			'billToAddress.city'			=> array('bill_city'),
			'billToAddress.state'			=> array('bill_state'),
			'billToAddress.zip'				=> array('bill_zip'),
			'billToAddress.country'			=> array('bill_country'),
			'card.type'						=> array('card_type', 'cc_type', 'account'),
			'card.number'					=> array('card_number', 'cc_account', 'cc_number', 'account'),
			'card.expDate'					=> array('card_expdate', 'card_expire', 'card_expires', 'cc_expires', 'cc_expire', 'expires'),
			'card.cardValidationNum'		=> array('card_cardvalidationnum', 'card_cvv', 'cc_cvv', 'cvv', 'cvvn'),
			),
		// You can assign default values for ANY API interaction (after the translation)
		'defaults' => array(
			// all transactions types with this field will get this default (if not already set)
			'reportGroup' => '1',
			// sale transactions
			'sale' => array(
				'orderSource' => 'ecommerce',
				'customBilling' => array(
					'phone' => '8888888888',
					'descriptor' => 'abc*ABC Company',
					),
				),
			'void' => array(),
			'refund' => array(),
			'token' => array(),
			// etc..
			),
		);
}
?>