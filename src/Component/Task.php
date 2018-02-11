<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/11
 * Time: 15:57
 */

namespace Tasque\Component;

abstract class Task {

    protected $_id;
    protected $_score;
    protected $_payload;

    public function __construct($id, $score = 0, array $payload = [])
    {
        $this->__set('id', $id);
        $this->__set('score', $score);
        $this->__set('payload', $payload);
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

    abstract function perform();
}