<?php

/**
 * Description of BaseConsole
 * 命令行应用控制器基类
 * @author zhangjiulong
 */
class BaseConsole extends FrameConsole{
    
    //如果有--sleep=1的参数 则先sleep再执行
    protected function beforeAction($params)
    {
        if(isset($params['sleep']) && $params['sleep']>=1){
            sleep($params['sleep']);
        }
        return parent::beforeAction($params);
    }
}
