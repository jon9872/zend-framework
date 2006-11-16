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
 * @package    Zend_TimeSync
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * Zend_TimeSync_Protocol
 */
require_once 'Zend/TimeSync/Protocol.php';

/**
 * Zend_TimeSync_Exception
 */
require_once 'Zend/TimeSync/Exception.php';

/**
 * @category   Zend
 * @package    Zend_TimeSync
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_TimeSync_Sntp extends Zend_TimeSync_Protocol
{
    protected $_timeserver;
    protected $_port;
    protected $_scheme;
    
    public function __construct($timeserver, $port)
    {
        $this->_timeserver = $timeserver;
        $this->_port       = $port;
    }
    
    protected function query()
    {
        // query the sntp server
    }
}