<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

require_once 'auto_load.php';

$config = require dirname(__DIR__) . '/../../config/console.php';

/**
 * 创建console应用
 */
$app = new FrameConsoleApp($config);
try {
    /**
     * 运行应用
     */
    $app->run();
    exit(0);
} catch (Exception $e) {
    echo FrameConsole::ansiFormat($e->getMessage(), [FrameConsole::FG_RED]);
//    echo $e->getMessage();
    exit(1);
}
?>
