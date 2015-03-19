<?php

/**
 * Description of HomeConsole
 * 帮助命令控制器
 * usage: php index.php home[/hello]
 * @author zhangjiulong
 */
class HomeConsole extends FrameConsole{
    
    public function helloAction($word='world') {
        $str= 'hello,'.$word.'!';
        echo static::ansiFormat($str, [FrameConsole::FG_RED,FrameConsole::BG_GREY,FrameConsole::BOLD]);
    }
}
