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
 * @package    Zend_Build_Resource
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Interface.php 3412 2007-02-14 22:22:35Z darby $
 */

/**
 * @see Zend_Build_XMLConvertor
 */
require_once 'Zend/Build/Resource/Interface.php';
require_once 'Zend/Loader.php';

/**
 * Static class to convert from build resources to XML and back.
 * 
 * @category   Zend
 * @package    Zend_Build_Resource
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Build_XmlConvertor
{
    /**
     * Converts build resource to XML.
     * 
     * @param Zend_Build_Resource_Interface Resource to convert to XML
     * @return string String in valid XML 1.0 format
     * @see Zend_Build_Resource_Interface
     */
    public static function resourceToXml (Zend_Build_Resource_Interface $resource)
    {
        // First create the empty DOM document
        // Now recursively create all document elements
        // Format output for readable XML
        return $dom->saveXML();
    }

    /**
     * Converts XML 1.0 string to Zend_Build_Resource
     * 
     * @param string String in valid XML 1.0 format
     * @return Zend_Build_Resource_Interface Resource to convert to XML
     */
    public static function xmlToResource ($xml)
    {
        // First create the DOM document from XML
        $dom->loadXML($xml);
        // Now recursively create all the resources
        // Return resource tree
    }

    private static function _resourceToDOMElement ($dom, Zend_Build_Resource_Interface $resource)
    {
        $dom_element = $dom->createElement(get_class($resource));
        foreach ($resource as $name => $value) {
            $dom_element->setAttribute($name, is_object($value) ? $value->__toString() : $value);
        }
        $dom->appendChild($dom_element);
        // Recurse on children
        if (isset($children)) {
            foreach ($children as $child) {
                $dom_element->appendChild(self::_resourceToDOMElement($dom, $child));
            }
        }
        return $dom_element;
    }

    private static function _domElementToResource ($dom_element)
    {
        if (! isset($dom_element))
            return null;
        $classname = $dom_element->tagName;
        // Load the right class
        // Get the resource name
        // Instantiate the object
        // Set the attributes
            if (! isset($value)) {
                $resource->$name = $dom_element->getAttribute($value);
            }
        }
        // Now create the children
            if ($child instanceof DOMElement) {
                $resource->addChild(self::_domElementToResource($child));
            }
        }
        // Now return the resource
    }
}