<?php
require_once('socketTask\SimpleSocketTask.php');

use TestSocket\SimpleSocketTask;

ob_implicit_flush();

$testSocket = new SimpleSocketTask('0.0.0.0', 10007, 'First Program');
$testSocket->run();
