<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/16
 * Time: 下午2:00
 */

// ./vendor/bin/phpunit --bootstrap ./tests/bootstrap.php tests/
// ./vendor/bin/phpunit --bootstrap tests/

require_once dirname(__DIR__) . "/vendor/autoload.php";

ini_set('default_socket_timeout', -1);

spl_autoload_register(function($class) {
    $prefix = 'Tests\\';
    if (substr($class, 0, strlen($prefix)) == $prefix) {
        $file = __DIR__ . str_replace('\\', DIRECTORY_SEPARATOR, str_replace($prefix, '\\', $class)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

$task_classes = [];
$task_files = glob(__DIR__ . '/Task/*.php');
if ($task_files) {
    foreach ($task_files as $file) {
        $task_class = pathinfo($file, PATHINFO_FILENAME);
        if (class_exists($task_class)) {
            $task_classes[] = $task_class;
        }
    }
}
