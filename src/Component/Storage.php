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
    private $_tag = '';

    private $_components = [
        'dict'  => null,
        'queue' => null,
    ];

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

    public function tag($tag)
    {
        $this->_tag = strval($tag);
        return $this;
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

    public function getComponent($component)
    {
        return $this->_components[$component];
    }

    public function set($key, $value)
    {
        return $this->_mount('dict')->set($key, $value);
    }

    public function get($key)
    {
        return $this->_mount('dict')->get($key);
    }

    public function del($key) {
        return $this->_mount('dict')->del($key);
    }

    public function len() {
        return $this->_mount('queue')->len();
    }

    public function push($score, $member)
    {
        return $this->_mount('queue')->push($score, $member);
    }

    public function pop($score, $limit)
    {
        return $this->_mount('queue')->pop($score, $limit);
    }

    public function getName($component)
    {
        return "{$this->_prefix}:{$this->_tag}:{$component}";
    }

    private function _register($component, $adapter = 'redis', $connector = null)
    {
        $adapter = ucfirst($adapter);
        $class = __NAMESPACE__ . "\\Storage\\Adapter\\{$adapter}\\" . ucfirst($component);
        if (!class_exists($class)) {
            // TODO throw an exception
            return false;
        }
        $this->_components[$component] = [$class, $connector];
        return true;
    }

    private function _mount($component)
    {
        static $C = [];

        if (!$this->_tag) {
            // TODO throw an exception
            return false;
        }

        $name = $this->getName($component);

        if (isset($C[$name])) {
            return $C[$name];
        }

        if (isset($this->_components[$component])) {
            list($class, $connector) = $this->_components[$component];
            $C[$name] = new $class($name, $connector);
            return $C[$name];
        }

        // TODO throw an exception
        return false;
    }

}