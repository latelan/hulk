<?php

require_once 'auto_load.php';

$config = require dirname(__DIR__) . '/../../config/console.php';

try {
    $app = new FrameConsoleApp($config);
    $app->run();
    exit(0);
} catch (Exception $e) {
    echo FrameConsole::ansiFormat($e->getMessage(), [FrameConsole::FG_RED]);
//    echo $e->getMessage();
    exit(1);
}
?>
