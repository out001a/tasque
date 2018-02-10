<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/10
 * Time: 下午8:42
 */

namespace Tasque\Component\Storage\Adapter\Apcu;

use Tasque\Component\Storage\Spec\DictInterface;

class Dict implements DictInterface {

    private $_name;

    public function __construct($name, $connector = null)
    {
        $this->_name = $name;
    }

    public function set($key, $value)
    {
        return apcu_store($key, $value);
    }

    public function get($key)
    {
        return apcu_fetch($key);
    }

    public function del($key)
    {
        return apc_delete($key);
    }
}