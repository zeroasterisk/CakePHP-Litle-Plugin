<?php
/**
* Plugin model for "Litle Recycler".
*
* The Litle Recycling Engine is a managed service that automatically retries 
* declined authorization attempts on your behalf. It requires little or no IT 
* investment on your part. Also, implementing the Litle service removes the 
* need to plan your own recycling strategy.
* 
* Litle provides the results of the recycling efforts to you in a batch file 
* posted daily to an FTP site. This file contains transactions that either 
* approved or exhausted the recycling pattern on the previous day. If you 
* submit an Authorization for a transaction in the recycling queue, Litle 
* returns the response from the last automatic recycling attempt. 
* To halt recyling of a particular transaction, submit either an Authorization 
* reversal transaction, if the original transaction was an Auth, or a Void 
* transaction, if the original transaction was a Sale (conditional deposit).
*
* @author Alan Blount <alan@zeroasterisk.com>
* @link http://zeroasterisk.com
* @copyright (c) 2011 Alan Blount
* @license MIT License - http://www.opensource.org/licenses/mit-license.php
*/
class LitleRecycler extends LitleAppModel {
	/**
	* The name of this model
	* @var name
	*/
	public $name ='LitleRecycler';
	/**
	* The name of this model
	* @var name
	*/
	public $useTable = false;
	/**
	* The fields and their types for the form helper
	* @var array
	*/
	public $_schema = array(
		);
	
	/**
	* Parses the recycler results file
	*/
	public function parse() {
		// TODO: establish location of file in config
		// TODO: import file
		// TODO: update billing record for each transaction & log actions
		die('WIP');
	}
}
?>
