<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/10
 * Time: 下午1:04
 */

namespace Tasque\Component\Storage\Adapter\Redis;

use Tasque\Component\Storage\Spec\DictInterface;

class Dict implements DictInterface {

    private $_name;
    private $_connector;

    public function __construct($name, $connector)
    {
        $this->_name = $name;
        $this->_connector = $connector;
    }

    public function set($key, $value)
    {
        return $this->_connector->hSet($this->_name, $key, $value);
    }

    public function get($key)
    {
        return $this->_connector->hGet($this->_name, $key);
    }

    public function del($key)
    {
        return $this->_connector->hDel($this->_name, $key);
    }
}