<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to version 1.0 of the Zend Framework
 * license, that is bundled with this package in the file LICENSE, and
 * is available through the world-wide-web at the following URL:
 * http://www.zend.com/license/framework/1_0.txt. If you did not receive
 * a copy of the Zend Framework license and are unable to obtain it
 * through the world-wide-web, please send a note to license@zend.com
 * so we can mail you a copy immediately.
 *
 * @package    Zend_Mail
 * @copyright  Copyright (c) 2005-2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://www.zend.com/license/framework/1_0.txt Zend Framework License version 1.0
 */
 
/**
 * Zend_Mail_Transport_Exception
 */
require_once 'Zend/Mail/Transport/Exception.php';
 
 
/**
 * @package    Zend_Mail
 * @copyright  Copyright (c) 2005-2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://www.zend.com/license/framework/1_0.txt Zend Framework License version 1.0
 */
class Zend_Mail_Transport_Imap
{
    /**
     * socket to imap server
     */
    private $_socket;
    

    /**
     * Public constructor
     *
     * @param string $host  hostname of IP address of IMAP server, if given connect() is called
     * @param int    $port  port of IMAP server, default is 143
     */
    function __construct($host = '', $port = null) 
    {
        if($host) {
            $this->connect($host, $port);
        } 
    }

    /**
     * Public destructor
     */
    public function __destruct()
    {
        $this->logout();
    }
    
    /**
     * Open connection to POP3 server
     *
     * @param  string $host  hostname of IP address of POP3 server
     * @param  int    $port  of POP3 server, default is 110 (995 for ssl)
     * @throws Zend_Mail_Transport_Exception
     * @return string welcome message
     */
    public function connect($host, $port = null) 
    {
        if(is_null($port)) {
            $port = 143;
        }
        $this->_socket = @fsockopen($host, $port);
        if(!$this->_socket) {
            throw new Zend_Mail_Transport_Exception('cannot connect to host');
        }

        if(!$this->readLine()) {
            throw new Zend_Mail_Transport_Exception('host doesn\'t allow connection');          
        }
    }
    
    /**
     * read a response "line" (could also be more than one real line if response has {..}<NL>)
     * and do a simple decode
     *
     * @params array  $tokens    decoded tokens are returned by reference
     * @params string $wantedTag check for this tag for response code. Default '*' is 
     *                           continuation tag.
     * @return bool if returned tag matches wanted tag
     */
    public function readLine(&$tokens = array(), $wantedTag = '*') 
    {
        $tokens = array();
        $stack = array();
        
        $line = fgets($this->_socket);
        //  remove <NL>
        $line = rtrim($line) . ' ';
        
        // seperate tage from line
        list($tag, $line) = explode(' ', $line, 2);
        
        /*
            We start to decode the response here. The unterstood tokens are:
                literal
                "literal" or also "lit\\er\"al"
                {bytes}<NL>literal
                (literals*)
            All tokens are returned in an array. Literals in braces (the last unterstood
            token in the list) are returned as an array of tokens. I.e. the following response:
                "foo" baz {3}<NL>bar ("f\\\"oo" bar)
            would be returned as:
                array('foo', 'baz', 'bar', array('f\\\"oo', 'bar'));
        */
        while(($pos = strpos($line, ' ')) !== false) {
            $token = substr($line, 0, $pos);
//          var_dump(__METHOD__, '------', $token, $stack, $tokens, '------');
            if($token[0] == '(') {
                array_push($stack, $tokens);
                $tokens = array();
                $token = substr($token, 1);
            }
            if($token[0] == '{') {
                $endPos = strpos($token, '}');
                $chars = substr($token, 1, $endPos);
                if(is_numeric($chars)) {
                    $token = '';
                    while(strlen($token) < $chars) {
                        $token .= fgets($this->_socket);
                    }
                    $tokens[] = $token;
                    break;
                }
            }
            if($token[0] == '"') {
                if(preg_match('%^"((.|\\\\|\\")*)"%', $line, $matches)) {
                    $tokens[] = $matches[1];
                    $line = substr($line, $pos + strlen($matches[1]) + 1);
                    continue;
                }
            }
            if($stack && substr($token, -1) == ')') {
                $tokens[] = substr($token, 0, -1);
                $token = $tokens;
                $tokens = array_pop($stack);
            }
            $tokens[] = $token;
            $line = substr($line, $pos + 1);
        }
        while($stack) {
            $child = $tokens;
            $tokens = array_pop($stack);
            $tokens[] = $child;
        }
        
//      var_dump(__METHOD__, '------', '***', $stack, $tokens, '------');
//      var_dump(__METHOD__, $wantedTag, $tag, $tokens);

        // if tag is wanted tag we might be at the end of a multiline response
        return $tag == $wantedTag;
    }
    
    /**
     * read all lines of response until given tag is found (last line of response)
     *
     * @params string       $tag    the tag of your request
     * @params string|array $filter you can filter the response so you get only the 
     *                              given response lines
     * @return null|bool|array tokens if success, false if error, null if bad request
     */
    public function readResponse($tag, $filter = '') 
    {
        $lines = array();
        while(!$this->readLine($tokens, $tag)) {
            if($filter) {
//              var_dump(__METHOD__, $tokens, $filter);
                if(is_array($filter)) {
                    if(!in_array($tokens[0], $filter)) {
                        continue;
                    }
                } else {
                    if($tokens[0] == $filter) {
                        array_shift($tokens);
                    } else {
                        continue;
                    }
                }
            }
            $lines[] = $tokens;
        }
        
        // last line has response code
        if($tokens[0] == 'OK') {
            return $lines;
        } else if($tokens[0] == 'NO'){
            return false;
        }
        return null;
    }
    
    /**
     * send a request
     *
     * @params string $command your request command
     * @params array  $tokens  additional parameters to command, use escapeString() to prepare
     * @params string $tag     provide a tag otherwise an autogenerated is returned
     *
     * @throws Zend_Mail_Transport_Exception
     */
    public function sendRequest($command, $tokens = array(), &$tag = null) 
    {
        if(!$tag) {
            $tag = 'TAG' . rand(100, 999);
        }
        
        fputs($this->_socket, $tag . ' ' . $command);
        
        foreach($tokens as $token) {
            if(is_array($token)) {
                fputs($this->_socket, ' ' . $token[0] . "\r\n");
                if(!$this->readLine($response, '+') || $response[0] != 'OK') {
//                  var_dump(__METHOD__, $response);
                    throw new Zend_Mail_Transport_Exception('cannot send literal string');
                }
                fputs($this->_socket, $token[1]);
            } else {
                fputs($this->_socket, ' ' . $token);
            }
        }
        
        fputs($this->_socket, "\r\n");
    }
    
    /**
     * send a request and get response at once
     * 
     * @params string $command command as in sendRequest()
     * @params array  $tokens  parameters as in sendRequest()
     * @return mixed response as in readResponse()
     */
    public function requestAndResponse($command, $tokens = array()) 
    {
        $this->sendRequest($command, $tokens, $tag);
        $response = $this->readResponse($tag, $command);
        return is_array($response) && !$response ? true: $response;
    }
    
    /**
     * escape one or more literals i.e. for sendRequest
     *
     * @params string|array $string the literal/-s
     * @return string|array escape literals, literals with newline ar returned 
     *                      as array('{size}', 'string');
     */
    public function escapeString($string) 
    {
        if(func_num_args() < 2) {
            if(strpos($string, "\n")) {
                return array('{' . strlen($string) . '}', $string);
            } else {
                return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $string) . '"';
            }
        }
        $result = array();
        foreach(func_get_args() as $string) {
            $result[] = $this->escapeString($string);
        }
        return $result;
    }
    
    /**
     * Login to IMAP server.
     *
     * @param  string $user      username
     * @param  string $password  password
     * @return bool success
     */
    public function login($user, $password) 
    {
        return $this->requestAndResponse('LOGIN', $this->escapeString($user, $password));
    }
    
    /**
     * logout of imap server
     *
     * @return bool success
     */
    public function logout() 
    {
        return $this->requestAndResponse('LOGOUT') && fclose($this->_socket);
    }
    
    
    /**
     * Get capabilities from IMAP server
     *
     * @return array list of capabilities
     */
    public function capability() 
    {
        $response = $this->requestAndResponse('CAPABILITY');
        
        if(!$response) {
            return $response;
        }

        $capabilities = array();
        foreach($response as $line) {
            $capabilities = array_merge($capabilities, $line);
        }
        return $capabilities;
    }
    
    /**
     * Examine and select have the same response. The common code for both
     * is in this method
     *
     * @params string can be 'EXAMINE' or 'SELECT' and this is used as command
     * @params string which folder to change to or examine
     * 
     * @return bool|array false if error, array with returned information 
     *                    otherwise (flags, exists, recent, uidvalidity)
     */
    public function examineOrSelect($command = 'EXAMINE', $box = 'INBOX') 
    {
        $this->sendRequest($command, (array)$this->escapeString($box), $tag);
        
        $result = array();
        while(!$this->readLine($tokens, $tag)) {
            if($tokens[0] == 'FLAGS') {
                array_shift($tokens);
                $result['flags'] = $tokens;
                continue;
            }
            switch($tokens[1]) {
                case 'EXISTS':
                case 'RECENT':
                    $result[strtolower($tokens[1])] = $tokens[0];
                    break;
                case '[UIDVALIDITY':
                    $result['uidvalidity'] = (int)$tokens[2];
                    break;
                default:
                    // ignore
            }
        }
        
        if($tokens[0] != 'OK') {
            return false;
        }
        return $result;
    }
    
    /**
     * change folder
     * 
     * @params string     $box change to this folder
     * @return bool|array see examineOrselect()
     */
    public function select($box = 'INBOX') 
    {
        return $this->examineOrSelect('SELECT', $box);
    }

    /**
     * examine folder
     * 
     * @params string     $box examine this folder
     * @return bool|array see examineOrselect()
     */
    public function examine($box = 'INBOX') 
    {
        return $this->examineOrSelect('EXAMINE', $box);
    }   
}