<?php

/**
 * Description of HomeConsole
 * 帮助命令控制器
 * @author zhangjiulong
 */
class HomeConsole extends FrameConsole{
    
    public function helloAction($word='world') {
//        var_dump(FrameApp::$app->debug);
        $str= 'hello,'.$word.'!';
        echo $this->ansiFormat($str, [FrameConsole::FG_RED,FrameConsole::BG_GREY,FrameConsole::BOLD]);
    }
}
