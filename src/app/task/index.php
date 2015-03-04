<?php

require_once 'auto_load.php';

$config = require dirname(__DIR__) . '/../../config/console.php';

try {
    (new FrameConsoleApp($config))->run();
    exit(0);
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
?>
