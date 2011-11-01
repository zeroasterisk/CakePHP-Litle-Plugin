<?php
/* LitleRecycler Test cases generated on: 2011-10-31 13:10:34 : 1320082714*/
App::import('model', 'litle.LitleRecycler');

App::import('Lib', 'Templates.AppTestCase');
class LitleRecyclerTestCase extends AppTestCase {
/**
 * Autoload entrypoint for fixtures dependecy solver
 *
 * @var string
 * @access public
 */
	public $plugin = 'app';

/**
 * Test to run for the test case (e.g array('testFind', 'testView'))
 * If this attribute is not empty only the tests from the list will be executed
 *
 * @var array
 * @access protected
 */
	protected $_testsToRun = array();

/**
 * Start Test callback
 *
 * @param string $method
 * @return void
 * @access public
 */
	public function startTest($method) {
		parent::startTest($method);
		$this->
Notice: Undefined variable: localConstruction in /workspace/www/ahm/sp/app/plugins/templates/vendors/shells/templates/cakedc/classes/test.ctp on line 104
LitleRecycler = 		$fixture = new 
Notice: Undefined variable: modelName in /workspace/www/ahm/sp/app/plugins/templates/vendors/shells/templates/cakedc/classes/test.ctp on line 114
Fixture();
		$this->record = array('
Notice: Undefined variable: modelName in /workspace/www/ahm/sp/app/plugins/templates/vendors/shells/templates/cakedc/classes/test.ctp on line 115
' => $fixture->records[0]);
	}

/**
 * End Test callback
 *
 * @param string $method
 * @return void
 * @access public
 */
	public function endTest($method) {
		parent::endTest($method);
		unset($this->LitleRecycler);
		ClassRegistry::flush();
	}


	
}
?>