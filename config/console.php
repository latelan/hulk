<?php
/**
 * 命令行应用的配置文件
 */

//defined('HULK_DEBUG') or define('HULK_DEBUG', true);

return [
    'debug' => true,
    'env' => 'dev',
    'basePath' => dirname(__DIR__),
    'consolePath' => function() {
        return $this->basePath . '/src/app/task';
    },
    'logPath' => function() {
        return $this->basePath . '/logs';
    },
//    'defaultRoute' => 'home',
//    'timeZone' => 'PRC',
    'components' => [
        'db' => require __DIR__ . '/db.php',
    ],
];
?>