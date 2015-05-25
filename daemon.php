<?php
//!!! Для запуска использовать команду "nohup php daemon.php > /dev/null 2>&1 &"
$loader = include __DIR__ . '/vendor/autoload.php';

use SocketDaemon\ClientTask\ControllerClientTask;
use SocketDaemon\ServerTask\ControllerServerTask;
use Monolog\Logger;

ob_implicit_flush();

$firstTask = new ControllerClientTask();
$firstTask
    ->setName('first')
    ->setHost('0.0.0.0')
    ->setPort(10009)
    ->setCommand('php firstTask.php');
$secondTask = new ControllerClientTask();
$secondTask
    ->setName('second')
    ->setHost('0.0.0.0')
    ->setPort(10009)
    ->setCommand('php secondTask.php');

$testSocket = new ControllerServerTask(
    '0.0.0.0',
    10003,
    ['first' => $firstTask, 'second' => $secondTask],
    'Task controller',
    new Logger('TaskController')
);
$testSocket->run();
