<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/11
 * Time: 12:04
 */

die(__FILE__ . ' 不需要了！');

spl_autoload_register(function ($class) {
    $prefix = 'Tasque';
    if (substr($class, 0, 6) == $prefix) {
        $file = __DIR__ . '\src' . str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class, $prefix)) . '.php';
        if (file_exists($file)) {
            var_dump($file);
            require_once $file;
        }
    }
});