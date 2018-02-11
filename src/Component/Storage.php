<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/10
 * Time: 下午1:09
 */

namespace Tasque\Component;

class Storage {

    private $_prefix = '';
    private $_name = '';

    private $_dict;
    private $_queue;

    private $_components = [];

    public function __construct($prefix)
    {
        $this->_prefix = $prefix;
    }

    final public function __get($name)
    {
        $prop = '_' . lcfirst($name);
        if (property_exists($this, $prop)) {
            return $this->$prop;
        }
        return null;
    }

    final public function __set($name, $value)
    {
        $prop = '_' . lcfirst($name);
        if (property_exists($this, $prop)) {
            $this->$prop = $value;
        }
    }

    public function register($connector = null, $adapter = 'redis', $component = null)
    {
        if ($component) {
            $this->_register($component, $adapter, $connector);
        } else {
            foreach (['dict', 'queue'] as $component) {
                $this->_register($component, $adapter, $connector);
            }
        }
    }

    public function setName($name) {
        $this->_name = $name;
    }

    public function set($key, $value) {
        $this->_initComponent('dict', 'TODO');
        return $this->_dict->set($key, $value);
    }

    public function get($key) {
        $this->_initComponent('dict', 'TODO');
        return $this->_dict->get($key);
    }

    public function push($score, $member) {
        $this->_initComponent('queue', 'TODO');
        return $this->_queue->push($score, $member);
    }

    public function pop($score, $limit) {
        $this->_initComponent('queue', 'TODO');
        return $this->_queue->pop($score, $limit);
    }

    private function _register($component, $adapter = 'redis', $connector = null)
    {
        $component = ucfirst($component);
        $adapter = ucfirst($adapter);

        $class = __NAMESPACE__ . "\\Storage\\Adapter\\{$adapter}\\{$component}";
        if (!class_exists($class)) {
            // TODO throw an exception
            return false;
        }
        $this->_components[lcfirst($component)] = [$class, $connector]; // "{$this->_prefix}:{$component}:{$this->_name}"
        return true;
    }

    private function _initComponent($component, $name) {
        if (is_object($this->$component)) {
            return;
        }
        if (!isset($this->_components[$component])) {
            // TODO throw an exception
        }
        list($class, $connector) = $this->_components[$component];
        $this->$component = new $class($name, $connector);
    }

}