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
 * @package    Zend_Service_Technorati
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id$
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


/**
 * Test helper
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR .'TechnoratiTestHelper.php';

/**
 * @see Zend_Service_Technorati_TagsResult
 */
require_once 'Zend/Service/Technorati/TagsResult.php';


/**
 * @category   Zend
 * @package    Zend_Service_Technorati
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Service_Technorati_TagsResultTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->domElements = Zend_Service_Technorati_TechnoratiTestHelper::getTestFileElementsAsDom('TestTagsResultSet.xml');
    }

    public function testConstruct()
    {
        try {
            $object = new Zend_Service_Technorati_TagsResult($this->domElements->item(0));
            $this->assertType('Zend_Service_Technorati_TagsResult', $object);
        } catch (Exception $e) {
            $this->fail("Exception" . $e->getMessage() . " thrown");
        }
    }

    public function testConstructThrowsExceptionWithInvalidDom() 
    {
        try {
            $object = new Zend_Service_Technorati_TagsResult('foo');
            $this->fail('Expected Zend_Service_Technorati_Exception not thrown');
        } catch (Exception $e) {
            $this->assertContains("DOMElement", $e->getMessage());
        }
    }

    public function testTagsResult()
    {
        $object = new Zend_Service_Technorati_TagsResult($this->domElements->item(2));
        
        // check properties
        $this->assertType('string', $object->getTag());
        $this->assertEquals('Weblog', $object->getTag());
        $this->assertType('integer', $object->getPosts());
        $this->assertEquals(8336350, $object->getPosts());
    }
    
    public function testTagsResultSpecialEncoding()
    {
        $object = new Zend_Service_Technorati_TagsResult($this->domElements->item(0));
        $this->assertEquals('練習用', $object->getTag());
        $this->assertEquals(19655999, $object->getPosts());
    }
}