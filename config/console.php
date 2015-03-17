<?php

/**
 * 命令行应用的配置文件
 */

return [
    /**
     * 是否开启debug
     */
    'debug' => true,
    /**
     * 设置应用的运行环境，取值范围 dev|beta|prod
     */
    'env' => 'dev',
    /**
     * 设置应用的根目录 默认会生成@root的路径别名 在配置的其他地方可以使用@root
     */
    'basePath' => dirname(__DIR__),
    /**
     * 路径别名 
     */
    'aliases' => [
        '@app' => '@root/src/app', //@root对应的是basePath的路径
    ],
    /**
     * 控制器目录,默认为basePath/src/app/task
     */
//    'consolePath' => '@root/src/app/task',
    /**
     * 日志目录 默认为basePath/logs
     */
    'logPath' => '@root/logs/task',
    /**
     * 默认路由 当前url中没有pathinfo时采用，默认为HomeController
     */
//    'defaultRoute' => 'home',
    /**
     * 时区设置，默认取php.ini中的时区，如果php.ini中没有设置，则取PRC时区
     */
//    'timeZone' => 'PRC',
    /**
     * components中配置的对象或者对象的配置文件会被注入到容器中
     */
    'components' => [
        'db' => require __DIR__ . '/db.php',
        'log' => [
            'class' => 'FrameLog',
            'targets' => [
                [
                    'class' => 'FrameLogFileTarget',
//                    'levels'=>['info','error'],
                ],
                [
                    'class' => 'FrameLogDbTarget',
                    'tableName' => 'loger',
//                    'levels'=>['info','error'],
                ],
            ],
        ]
    ],
];
?>