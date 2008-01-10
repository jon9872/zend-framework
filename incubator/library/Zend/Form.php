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
 * @package    Zend_Form
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * Zend_Form
 * 
 * @category   Aend
 * @package    Zend_Form
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
class Zend_Form implements Iterator
{
    /**#@+
     * Plugin loader type constants
     */
    const DECORATOR = 'DECORATOR';
    const ELEMENT = 'ELEMENT';
    /**#@-*/

    /**
     * Form metadata and attributes
     * @var array
     */
    protected $_attribs = array();

    /**
     * Form elements
     * @var array
     */
    protected $_elements = array();

    /**
     * Plugin loaders
     * @var array
     */
    protected $_loaders = array();

    public function __construct($options = null)
    {
    }

    public function setOptions(array $options)
    {
    }

    public function setConfig(Zend_Config $config)
    {
    }

 
    // Loaders 

    /**
     * Set plugin loaders for use with decorators and elements
     * 
     * @param  Zend_Loader_PluginLoader_Interface $loader 
     * @param  string $type 'decorator' or 'element'
     * @return Zend_Form
     * @throws Zend_Form_Exception on invalid type
     */
    public function setPluginLoader(Zend_Loader_PluginLoader_Interface $loader, $type = null)
    {
        $type = strtoupper($type);
        switch ($type) {
            case self::DECORATOR:
            case self::ELEMENT:
                $this->_loaders[$type] = $loader;
                return $this;
            default:
                require_once 'Zend/Form/Exception.php';
                throw new Zend_Form_Exception(sprintf('Invalid type "%s" provided to setPluginLoader()', $type));
        }
    }

    /**
     * Retrieve plugin loader for given type
     *
     * $type may be one of:
     * - decorator
     * - element
     *
     * If a plugin loader does not exist for the given type, defaults are 
     * created.
     * 
     * @param  string $type 
     * @return Zend_Loader_PluginLoader_Interface
     */
    public function getPluginLoader($type = null)
    {
        $type = strtoupper($type);
        if (!isset($this->_loaders[$type])) {
            switch ($type) {
                case self::DECORATOR:
                    $prefixSegment = 'Form_Decorator';
                    $pathSegment   = 'Form/Decorator';
                    break;
                case self::ELEMENT:
                    $prefixSegment = 'Form_Element';
                    $pathSegment   = 'Form/Element';
                    break;
                default:
                    require_once 'Zend/Form/Exception.php';
                    throw new Zend_Form_Exception(sprintf('Invalid type "%s" provided to getPluginLoader()', $type));
            }

            require_once 'Zend/Loader/PluginLoader.php';
            $this->_loaders[$type] = new Zend_Loader_PluginLoader(
                array('Zend_' . $prefixSegment . '_' => 'Zend/' . $pathSegment . '/')
            );
        }

        return $this->_loaders[$type];
    }

    /**
     * Add prefix path for plugin loader
     *
     * If no $type specified, assumes it is a base path for both filters and 
     * validators, and sets each according to the following rules:
     * - decorators: $prefix = $prefix . '_Decorator'
     * - elements: $prefix = $prefix . '_Element'
     *
     * Otherwise, the path prefix is set on the appropriate plugin loader.
     *
     * If $type is 'decorators', sets the path in the decorator plugin loader 
     * for all elements. Additionally, if no $type is provided, 
     * {@link Zend_Form_Element::addPrefixPath()} is called on each element.
     * 
     * @param  string $path 
     * @return Zend_Form
     * @throws Zend_Form_Exception for invalid type
     */
    public function addPrefixPath($prefix, $path, $type = null) 
    {
        $type = strtoupper($type);
        switch ($type) {
            case self::DECORATOR:
            case self::ELEMENT:
                $loader = $this->getPluginLoader($type);
                $loader->addPrefixPath($prefix, $path);
                return $this;
            case null:
                $prefix = rtrim($prefix, '_');
                $path   = rtrim($path, DIRECTORY_SEPARATOR);
                foreach (array(self::DECORATOR, self::ELEMENT) as $type) {
                    $cType        = ucfirst(strtolower($type));
                    $pluginPath   = $path . DIRECTORY_SEPARATOR . $cType . DIRECTORY_SEPARATOR;
                    $pluginPrefix = $prefix . '_' . $cType;
                    $loader       = $this->getPluginLoader($type);
                    $loader->addPrefixPath($pluginPrefix, $pluginPath);
                }
                foreach ($this->getElements() as $element) {
                    $element->addPrefixPath($prefix, $path);
                }
                return $this;
            default:
                require_once 'Zend/Form/Exception.php';
                throw new Zend_Form_Exception(sprintf('Invalid type "%s" provided to getPluginLoader()', $type));
        }
    }


    // Form metadata:
    
    /**
     * Set form attribute
     * 
     * @param  string $key 
     * @param  mixed $value 
     * @return Zend_Form
     */
    public function setAttrib($key, $value)
    {
        $key = (string) $key;
        $this->_attribs[$key] = $value;
        return $this;
    }

    /**
     * Add multiple form attributes at once
     * 
     * @param  array $attribs 
     * @return Zend_Form
     */
    public function addAttribs(array $attribs)
    {
        foreach ($attribs as $key => $value) {
            $this->setAttrib($key, $value);
        }
        return $this;
    }

    /**
     * Set multiple form attributes at once
     *
     * Overwrites any previously set attributes.
     * 
     * @param  array $attribs 
     * @return Zend_Form
     */
    public function setAttribs(array $attribs)
    {
        $this->clearAttribs();
        return $this->addAttribs($attribs);
    }

    /**
     * Retrieve a single form attribute
     * 
     * @param  string $key 
     * @return mixed
     */
    public function getAttrib($key)
    {
        $key = (string) $key;
        if (!isset($this->_attribs[$key])) {
            return null;
        }

        return $this->_attribs[$key];
    }

    /**
     * Retrieve all form attributes/metadata
     * 
     * @return array
     */
    public function getAttribs()
    {
        return $this->_attribs;
    }

    /**
     * Remove attribute
     * 
     * @param  string $key 
     * @return bool
     */
    public function removeAttrib($key)
    {
        if (isset($this->_attribs[$key])) {
            unset($this->_attribs[$key]);
            return true;
        }

        return false;
    }

    /**
     * Clear all form attributes
     * 
     * @return Zend_Form
     */
    public function clearAttribs()
    {
        $this->_attribs = array();
        return $this;
    }

 
    // Element interaction: 

    /**
     * Add a new element
     *
     * $element may be either a string element type, or an object of type 
     * Zend_Form_Element. If a string element type is provided, $name must be 
     * provided, and $options may be optionally provided for configuring the 
     * element.
     *
     * If a Zend_Form_Element is provided, $name may be optionally provided, 
     * and any provided $options will be ignored.
     * 
     * @param  string|Zend_Form_Element $element 
     * @param  string $name 
     * @param  array|Zend_Config $options 
     * @return Zend_Form
     */
    public function addElement($element, $name = null, $options = null)
    {
        if (is_string($element)) {
            if (null === $name) {
                require_once 'Zend/Form/Exception.php';
                throw new Zend_Form_Exception('Elements specified by string must have an accompanying name');
            }
            $class = $this->getPluginLoader(self::ELEMENT)->load($element);
            $this->_elements[$name] = new $class($name, $options);
        } elseif ($element instanceof Zend_Form_Element) {
            if (null === $name) {
                $name = $element->getName();
            }
            $this->_elements[$name] = $element;
        }
        return $this;
    }

    public function addElements(array $elements)
    {
    }

    public function setElements(array $elements)
    {
    }

    /**
     * Retrieve a single element
     * 
     * @param  string $name 
     * @return Zend_Form_Element|null
     */
    public function getElement($name)
    {
        if (isset($this->_elements[$name])) {
            return $this->_elements[$name];
        }
        return null;
    }

    /**
     * Retrieve all elements
     * 
     * @return array
     */
    public function getElements()
    {
        return $this->_elements;
    }

    public function removeElement($name)
    {
    }

    public function setDefaults(array $defaults)
    {
    }

    public function setDefault($name, $value)
    {
    }

    public function getValue($name)
    {
    }

    public function getValues()
    {
    }

    public function getUnfilteredValue($name)
    {
    }

    public function getUnfilteredValues()
    {
    }

    public function __get($name)
    {
    }

 
    // Element groups: 
    public function addGroup(Zend_Form $form, $name, $order = null)
    {
    }

    public function addGroups(array $groups)
    {
    }

    public function setGroups(array $groups)
    {
    }

    public function getGroup($name)
    {
    }

    public function getGroups()
    {
    }

    public function removeGroup($name)
    {
    }

    public function clearGroups()
    {
    }


    // Display groups:
    public function addDisplayGroup(array $elements, $name, $order = null)
    {
    }

    public function addDisplayGroups(array $groups)
    {
    }

    public function setDisplayGroups(array $groups)
    {
    }

    public function getDisplayGroup($name)
    {
    }

    public function getDisplayGroups()
    {
    }

    public function removeDisplayGroup($name)
    {
    }

    public function clearDisplayGroups()
    {
    }

     
    // Processing 

    /**
     * Populate form
     *
     * Proxies to {@link setDefaults()}
     * 
     * @param  array $values 
     * @return Zend_Form
     */
    public function populate(array $values)
    {
    }

    public function isValid(array $data)
    {
    }

    public function isValidPartial(array $data)
    {
    }

    public function processAjax($request)
    {
    }

    public function persistData()
    {
    }

    public function getErrors($name = null)
    {
    }

    public function getMessages($name = null)
    {
    }

 
    // Rendering 
    public function setView(Zend_View_Interface $view)
    {
    }

    public function getView()
    {
    }

    public function addDecorator($decorator, $options = array())
    {
    }

    public function addDecorators(array $decorator)
    {
    }

    public function setDecorators(array $decorator)
    {
    }

    public function getDecorator($name)
    {
    }

    public function getDecorators()
    {
    }

    public function removeDecorator($name)
    {
    }

    public function clearDecorators()
    {
    }

    public function render(Zend_View_Interface $view = null)
    {
    }

    public function __toString()
    {
    }

 
    // Localization: 
    public function setTranslator(Zend_Translate_Adapter $translator)
    {
    }

    public function getTranslator()
    {
    }

 
    // For iteration, countable: 
    public function current()
    {
    }

    public function key()
    {
    }

    public function next()
    {
    }

    public function rewind()
    {
    }

    public function valid()
    {
    }

    public function count()
    {
    }
}