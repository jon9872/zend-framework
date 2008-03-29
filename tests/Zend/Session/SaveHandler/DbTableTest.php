<?php
/**
* Zend Framework
*
* LICENSE
*
* This source file is subject to the new BSD license that is bundled
* with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://framework.zend.com/license/new-bsd
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@zend.com so we can send you a copy immediately.
*
* @category   Zend
* @package    Zend_Session_SaveHandler
* @subpackage UnitTests
* @copyright  Copyright (c) 2007 Jordan Raub <ludicruz@yahoo.com>
* @license    http://framework.zend.com/license/new-bsd     New BSD License
* @version    $Id: SessionTest.php 4773 2007-05-09 19:33:10Z darby $
*/

/**
* @see Zend_Session_SaveHandler_DbTable
* @see Zend_Session_SaveHandler_Interface
*/
require_once 'Zend/Session/SaveHandler/DbTable.php';

/**
 * Zend_Session tests need to be output buffered because they depend on headers_sent() === false
 *
 * @see http://framework.zend.com/issues/browse/ZF-700
 */
ob_start();

/**
* Unit testing for Zend_Session_SaveHandler_DbTable include all tests for regular session handling
*
* @category   Zend
* @package    Zend_Session
* @subpackage UnitTests
* @copyright  Copyright (c) 2007 Jordan Raub <ludicruz@yahoo.com>
* @license    http://framework.zend.com/license/new-bsd     New BSD License
* @see        http://en.wikipedia.org/wiki/Black_box_testing
*/
class Zend_Session_SaveHandler_DbTableTest extends PHPUnit_Framework_TestCase
{
    /**
    * @var array
    */
    protected $_saveHandlerTableConfig =  array(
        'name'              => 'sessions',
        'primary'           => array(
            'id',
            'save_path',
            'name',
        ),
        Zend_Session_SaveHandler_DbTable::MODIFIED_COLUMN    => 'modified',
        Zend_Session_SaveHandler_DbTable::LIFETIME_COLUMN    => 'lifetime',
        Zend_Session_SaveHandler_DbTable::DATA_COLUMN        => 'data',
        Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT => array(
            Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT_SESSION_ID,
            Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT_SESSION_SAVE_PATH,
            Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT_SESSION_NAME,
        ),
    );

    /**
     * @var Zend_Db
     */
    protected $_db;

    /**
     * construction
     */
    protected function setUp()
    {
        $this->_setupDb($this->_saveHandlerTableConfig['primary']);
    }

    /**
    * destruction
    */
    protected function tearDown()
    {
        $this->_dropDb();
    }


    protected function _setupDb(array $config = array())
    {
        $this->_db = Zend_Db::factory('Pdo_Sqlite', array('dbname' => ':memory:'));
        Zend_Db_Table_Abstract::setDefaultAdapter($this->_db);
        $query = array();
        $query[] = 'CREATE TABLE `Sessions` ( ';
        $query[] = '`id` varchar(32) NOT NULL, ';
        if(in_array('save_path', $config))
        $query[] = '`save_path` varchar(32) NOT NULL, ';
        if(in_array('name', $config))
        $query[] = '`name` varchar(32) NOT NULL, ';
        $query[] = '`modified` int(11) default NULL, ';
        $query[] = '`lifetime` int(11) default NULL, ';
        $query[] = '`data` text, ';
        $query[] = 'PRIMARY KEY  (' . implode(',', $config) . ') ';
        $query[] = ');';
        $this->_db->query(implode("\n", $query));
    }

    protected function _dropDb()
    {
        $this->_db->query('DROP TABLE Sessions');
    }

    public function testConfigPrimaryAssignmentFullConfig()
    {
        //everything is set
        $saveHandler = new Zend_Session_SaveHandler_DbTable($this->_saveHandlerTableConfig);
    }
    
    public function testEmptyConfig()
    {
	$this->setExpectedException('Zend_Session_SaveHandler_Exception');
	$saveHandler = new Zend_Session_SaveHandler_DbTable(null);
    }
    
    public function testTableNameSchema()
    {
	//this is thrown AFTER what we want to test... 
	$this->setExpectedException('Zend_Db_Statement_Exception');
	$config = $this->_saveHandlerTableConfig;
	$config['name'] = 'schema.session'; 
	$saveHandler = new Zend_Session_SaveHandler_DbTable($config);
    }

    public function testTableEmptyNamePullFromSavePath()
    {
	$config = $this->_saveHandlerTableConfig;
	unset($config['name']);
	try
	{
	    $savepath = ini_get('session.save_path');
	    ini_set('session.save_path', dirname(__FILE__));
	    $saveHandler = new Zend_Session_SaveHandler_DbTable($config);
	    $this->fail();
	}
	catch(Zend_Session_SaveHandler_Exception $e)
	{
	    ini_set('session.save_path', $save_path);
	}
    }
    
    public function testPrimaryAssignmentIdNotSet()
    {
	$this->setExpectedException('Zend_Session_SaveHandler_Exception');
	$config = $this->_saveHandlerTableConfig;
	$config['primary'] = array('id');
	$config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT]
	  = Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT_SESSION_SAVE_PATH;
	$saveHandler = new Zend_Session_SaveHandler_DbTable($config);
    }
    
    public function testPrimaryAssignmentNotArray()
    {
	$config = $this->_saveHandlerTableConfig;
	$config['primary'] = array('id');
	$config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT]
	  = Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT_SESSION_ID;
	$saveHandler = new Zend_Session_SaveHandler_DbTable($config);
    }
    
    public function testModifiedColumnNotSet()
    {
	$this->setExpectedException('Zend_Session_SaveHandler_Exception');
	$config = $this->_saveHandlerTableConfig;
	unset($config[Zend_Session_SaveHandler_DbTable::MODIFIED_COLUMN]);
	$saveHandler = new Zend_Session_SaveHandler_DbTable($config);
    }
    
    public function testLifetimeColumnNotSet()
    {
	$this->setExpectedException('Zend_Session_SaveHandler_Exception');
	$config = $this->_saveHandlerTableConfig;
	unset($config[Zend_Session_SaveHandler_DbTable::LIFETIME_COLUMN]);
	$saveHandler = new Zend_Session_SaveHandler_DbTable($config);
    }
    
    public function testDataColumnNotSet()
    {
	$this->setExpectedException('Zend_Session_SaveHandler_Exception');
	$config = $this->_saveHandlerTableConfig;
	unset($config[Zend_Session_SaveHandler_DbTable::DATA_COLUMN]);
	$saveHandler = new Zend_Session_SaveHandler_DbTable($config);
    }
    
    public function testDifferentArraySize()
    {
        //different number of args between primary and primaryAssignment
        try
        {
            $config = $this->_saveHandlerTableConfig;
            array_pop($config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT]);
            $saveHandler = new Zend_Session_SaveHandler_DbTable($config);
            $this->fail();
        }
        catch(Zend_Session_SaveHandler_Exception $e) {}
    }

    public function testEmptyPrimaryAssignment()
    {
        //test the default - no primaryAssignment
        $config = $this->_saveHandlerTableConfig;
        unset($config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT]);
        $config['primary'] = $config['primary'][0];
        $saveHandler = new Zend_Session_SaveHandler_DbTable($config);
    }

    public function testSessionIdPresent()
    {
        //test that the session Id must be in the primary assignment config
        try {
            $config = $this->_saveHandlerTableConfig;
            $config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT] = array(
            Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT_SESSION_NAME,
            );
            $saveHandler = new Zend_Session_SaveHandler_DbTable($config);
            $this->fail();
        }
        catch(Zend_Session_SaveHandler_Exception $e) {}
    }

    public function testModifiedColumnDefined()
    {
        //test the default - no primaryAssignment
        try {
            $config = $this->_saveHandlerTableConfig;
            unset($config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT]);
            unset($config[Zend_Session_SaveHandler_DbTable::MODIFIED_COLUMN]);
            $saveHandler = new Zend_Session_SaveHandler_DbTable($config);
            $this->fail();
        }
        catch(Zend_Session_SaveHandler_Exception $e) {}
    }

    public function testLifetimeColumnDefined()
    {
        //test the default - no primaryAssignment
        try {
            $config = $this->_saveHandlerTableConfig;
            unset($config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT]);
            unset($config[Zend_Session_SaveHandler_DbTable::LIFETIME_COLUMN]);
            $saveHandler = new Zend_Session_SaveHandler_DbTable($config);
            $this->fail();
        }
        catch(Zend_Session_SaveHandler_Exception $e) {}
    }

    public function testDataColumnDefined()
    {
        //test the default - no primaryAssignment
        try {
            $config = $this->_saveHandlerTableConfig;
            unset($config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT]);
            unset($config[Zend_Session_SaveHandler_DbTable::DATA_COLUMN]);
            $saveHandler = new Zend_Session_SaveHandler_DbTable($config);
            $this->fail();
        }
        catch(Zend_Session_SaveHandler_Exception $e) {}
    }

    public function testLifetime()
    {
        $config = $this->_saveHandlerTableConfig;
        unset($config['lifetime']);
        $saveHandler = new Zend_Session_SaveHandler_DbTable($config);
        $this->assertSame($saveHandler->getLifetime(), (int) ini_get('session.gc_maxlifetime'),
            'lifetime must default to session.gc_maxlifetime'
        );

        $config = $this->_saveHandlerTableConfig;
        $lifetime = $config['lifetime'] = 1242;
        $saveHandler = new Zend_Session_SaveHandler_DbTable($config);
        $this->assertSame($lifetime, $saveHandler->getLifetime());
    }

    public function testOverrideLifetime()
    {
        try {
            $config = $this->_saveHandlerTableConfig;
            $config['overrideLifetime'] = true;
            $saveHandler = new Zend_Session_SaveHandler_DbTable($config);
        } catch(Zend_Session_SaveHandler_Exception $e) {}

        $this->assertTrue($saveHandler->getOverrideLifetime(), '');
    }

    public function testSessionSaving()
    {
        $this->_dropDb();

        $config = $this->_saveHandlerTableConfig;
        unset($config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT]);
        $config['primary'] = array($config['primary'][0]);

        $this->_setupDb($config['primary']);

        $saveHandler = new Zend_Session_SaveHandler_DbTable($config);
        Zend_Session::setSaveHandler($saveHandler);
        Zend_Session::start();

        require_once 'Zend/Session/Namespace.php';

        $session = new Zend_Session_Namespace('SaveHandler');
        $session->testArray = $this->_saveHandlerTableConfig;

        $tmp = array('SaveHandler' => serialize(array('testArray' => $this->_saveHandlerTableConfig)));
        $testAgainst = '';
        foreach($tmp as $key => $val)
        {
            $testAgainst .= $key . "|" . $val;
        }

        session_write_close();

        foreach($this->_db->query('SELECT * FROM Sessions')->fetchAll() as $row)
        {
            $this->assertSame($row[$config[Zend_Session_SaveHandler_DbTable::DATA_COLUMN]],
                $testAgainst, 'Data was not saved properly'
            );
        }
    }
    
    public function testReadWrite()
    {
        $config = $this->_saveHandlerTableConfig;
	unset($config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT]);
	$config['primary'] = array($config['primary'][0]);
	$this->_setupDb($config['primary']);
	$saveHandler = new Zend_Session_SaveHandler_DbTable($config);
	
	$id = '242';
	
	$this->assertTrue($saveHandler->write($id, serialize($config)));
	
	$this->assertSame($config, unserialize($saveHandler->read($id)));
    }
    
    public function testReadWriteComplex()
    {
        $config = $this->_saveHandlerTableConfig;
	$this->_setupDb($config['primary']);
	$saveHandler = new Zend_Session_SaveHandler_DbTable($config);
	$saveHandler->open('savepath', 'sessionname');
	
	$id = '242';
	
	$this->assertTrue($saveHandler->write($id, serialize($config)));
	
	$this->assertSame($config, unserialize($saveHandler->read($id)));
    }
    
    public function testReadWriteTwice()
    {
        $config = $this->_saveHandlerTableConfig;
	unset($config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT]);
	$config['primary'] = array($config['primary'][0]);
	$this->_setupDb($config['primary']);
	$saveHandler = new Zend_Session_SaveHandler_DbTable($config);
	
	$id = '242';
	
	$this->assertTrue($saveHandler->write($id, serialize($config)));
	
	$this->assertSame($config, unserialize($saveHandler->read($id)));
	
	$this->assertTrue($saveHandler->write($id, serialize($config)));
	
	$this->assertSame($config, unserialize($saveHandler->read($id)));
    }
    
    public function testReadWriteTwiceAndExpire()
    {
        $config = $this->_saveHandlerTableConfig;
	unset($config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT]);
	$config['primary'] = array($config['primary'][0]);
	$config['lifetime'] = 1;
	
	$this->_setupDb($config['primary']);
	$saveHandler = new Zend_Session_SaveHandler_DbTable($config);
	
	$id = '242';
	
	$this->assertTrue($saveHandler->write($id, serialize($config)));
	
	$this->assertSame($config, unserialize($saveHandler->read($id)));
	
	$this->assertTrue($saveHandler->write($id, serialize($config)));
	
	sleep(2);
	
	$this->assertSame(false, unserialize($saveHandler->read($id)));
    }
    
    public function testReadWriteThreeTimesAndGc()
    {
        $config = $this->_saveHandlerTableConfig;
	unset($config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT]);
	$config['primary'] = array($config['primary'][0]);
	$config['lifetime'] = 1;
	
	$this->_setupDb($config['primary']);
	$saveHandler = new Zend_Session_SaveHandler_DbTable($config);
	
	$id = 242;
	
	$this->assertTrue($saveHandler->write($id, serialize($config)));
	
	$this->assertSame($config, unserialize($saveHandler->read($id)));
	
	$id++;
	$this->assertTrue($saveHandler->write($id, serialize($config)));
	
	$this->assertSame($config, unserialize($saveHandler->read($id)));
	
	$id++;
	$this->assertTrue($saveHandler->write($id, serialize($config)));
	
	$this->assertSame($config, unserialize($saveHandler->read($id)));
	
        foreach($this->_db->query('SELECT * FROM Sessions')->fetchAll() as $row)
        {
	    $this->assertSame($config, unserialize($row['data']));
        }	

	sleep(2);
	
	$saveHandler->gc(false);
	
        foreach($this->_db->query('SELECT * FROM Sessions')->fetchAll() as $row)
        {
	    //should be empty!
	    $this->fail();
        }	
    }
    
    public function testSetLifetime()
    {
        $config = $this->_saveHandlerTableConfig;
	unset($config[Zend_Session_SaveHandler_DbTable::PRIMARY_ASSIGNMENT]);
	$config['primary'] = array($config['primary'][0]);
	$config['lifetime'] = 1;
	
	$this->_setupDb($config['primary']);
	$saveHandler = new Zend_Session_SaveHandler_DbTable($config);
	$this->assertSame(1, $saveHandler->getLifetime());
	
	$saveHandler->setLifetime(27);
	
	$this->assertSame(27, $saveHandler->getLifetime());
    }
    
    public function testZendConfig()
    {
        $saveHandler = new Zend_Session_SaveHandler_DbTable(new Zend_Config($this->_saveHandlerTableConfig));
    }
}
