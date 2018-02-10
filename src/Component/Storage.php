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

    public function __construct($prefix, $name)
    {
        $this->_prefix = $prefix;
        $this->_name = $name;
    }

    public function register(string $component_class, $connector) {
        if (!class_exists($component_class)) {
            return false;
        }
        $component = strtolower(array_reverse(explode('\\', $component_class))[0]);    // dict or queue
        $property = '_' . $component;
        $this->$property = new $component_class("{$this->_prefix}:{$component}:{$this->_name}", $connector);
        return true;
    }

    public function set($key, $value) {
        $this->_dict->set($key, $value);
    }

    public function get($key) {
        return $this->_dict->get($key);
    }

    public function enqueue($score, $member) {
        return $this->_queue->push($score, $member);
    }

    public function dequeue($score, $limit) {
        return $this->_queue->pop($score, $limit);
    }
}