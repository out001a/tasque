<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/10
 * Time: 下午12:38
 */

namespace Tasque\Component\Storage\Spec;

interface DictInterface {

    function __construct($name, $connector);

    function set($key, $value);

    function get($key);

    function del($key);
}