<?php
/**
 * Web应用的配置文件
 */

return [
    /**
     * 是否开启debug
     */
    'debug'=>true,
    /**
     * 设置应用的运行环境，取值范围 dev|beta|prod
     */
    'env'=>'dev',
    /**
     * 设置应用的根目录 默认会生成@root的路径别名 在配置的其他地方可以使用@root
     */
    'basePath'=>  dirname(__DIR__),
    /**
     * 路径别名 
     */
    'aliases'=>[
        '@app'=>'@root/src/app',    //@root对应的是basePath的路径
    ],
    /**
     * 控制器目录,默认为basePath/src/app/controllers
     */
//    'controllerPath'=>  '@root/src/app/controllers',
    /**
     * 日志目录 默认为basePath/logs
     */
//    'logPath'=>  '@root/logs',
    /**
     * 默认路由 当前url中没有pathinfo时采用，默认为HomeController
     */
//    'defaultRoute'=>'home',
    /**
     * 时区设置，默认取php.ini中的时区，如果php.ini中没有设置，则取PRC时区
     */
//    'timeZone'=>'PRC',
    
    /**
     * components中配置的对象或者对象的配置文件会被注入到容器中
     */
    'components'=>[
        //数据库对象配置
        'db'=>[
            'class'=>'FrameDB',
            'dsn' => 'mysql:host=10.16.57.145;dbname=hulk_log;port=5002',
            'username' => 'hulk',
            'password' => '0c1ddf12383bf776',
            'charset' => 'utf8',
        ],
        //日志对象配置
        'log'=>[
            'class'=>'FrameLog',
            'targets'=>[
                [
                    'class'=>'FrameLogFileTarget',
//                    'levels'=>['info','error'],
                ],
                [
                    'class'=>'FrameLogDbTarget',
                    'tableName'=>'loger',
//                    'levels'=>['info','error'],
                ],
            ],
        ]
    ],
];

?>
