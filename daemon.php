<?php
//!!! Для запуска использовать команду "nohup php daemon.php > /dev/null 2>&1 &"
require_once('socketTask\TaskController.php');

use SocketTask\TaskController;
use Model\Task;

ob_implicit_flush();

$firstTask = new Task();
$firstTask
    ->setName('first')
    ->setPort(10007)
    ->setCommand('php firstTask.php');
$secondTask = new Task();
$secondTask
    ->setName('second')
    ->setPort(10008)
    ->setCommand('php secondTask.php');

$testSocket = new TaskController('0.0.0.0', 10006,['first' => $firstTask, 'second' => $secondTask], 'Task controller');
$testSocket->run();
