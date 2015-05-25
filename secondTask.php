<?php
$loader = include __DIR__ . '/vendor/autoload.php';

use SocketDaemon\ServerTask\SimpleServerTask;

ob_implicit_flush();

$testSocket = new SimpleServerTask('0.0.0.0', 10008, 'First Program', new \Monolog\Logger('firstTask'));
$testSocket->run();
