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
     * 设置应用的根目录
     */
    'basePath'=>  dirname(__DIR__),
    /**
     * 控制器目录,默认为basePath/src/app/controllers
     */
//    'controllerPath'=>  function(){return $this->basePath.'/src/app/controllers';},
//    'logPath'=>  function(){return $this->basePath.'/logs';},
//    'defaultRoute'=>'home',
//    'timeZone'=>'PRC',
    /**
     * components中配置的对象或者对象的配置文件会被注入到容器中
     */
    'components'=>[
        'db'=>require __DIR__.'/db.php',
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
