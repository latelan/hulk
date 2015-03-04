<?php

return [
    'name' => '这是一个测试的脚本',
    'debug' => true,
    'srcPath' => dirname(__DIR__) . '/src',
    'consolePath'=>function(){return $this->srcPath.'/app/task';},
    'logPath'=>function(){return $this->srcPath.'/../logs';},
    'basePath'=>function(){return $this->srcPath.'/../../';},
    'defaultRoute' => 'hello',
    'timeZone' => 'PRC',
    'components' => [
//        'db'=>[
//            
//        ]
    ],
];
?>