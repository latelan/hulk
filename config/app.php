<?php

return [
    'name'=>'这是一个测试的应用',
    'debug'=>true,
    'srcPath'=>  dirname(__DIR__).'/src',
    'controllerPath'=>  function(){return $this->srcPath.'/app/controllers';},
    'logPath'=>  function(){return $this->srcPath.'/../logs';},
//    'defaultRoute'=>'home',
    'timeZone'=>'PRC',
    'components'=>[
        'db'=>[
            'class'=>'FrameDB',
            'dsn' => 'mysql:host=10.16.57.145;dbname=hulk_log;port=5002',
            'username' => 'hulk',
            'password' => '0c1ddf12383bf776',
            'charset' => 'utf8',
        ],
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

