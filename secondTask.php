<?php
require_once('socketTask\SimpleSocketTask.php');

use TestSocket\SimpleSocketTask;

ob_implicit_flush();

$testSocket = new SimpleSocketTask('0.0.0.0', 10008, 'Second Program');
$testSocket->run();
