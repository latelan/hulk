<?php

/**
 * Description of HelpConsole
 * 帮助命令控制器
 * @author zhangjiulong
 */
class HelpConsole extends FrameConsole{
    
    public function testAction($a,array $b,$c=222) {
        print_r($a);
        print_r(func_get_args());
    }
    
    public function helloAction($word='world') {
//        var_dump(chr(27));
        $str= 'hello,'.$word.'!';
//        $str = ['a'=>3,'b'=>4];
//        $str = true;
//        $str =33;
//        echo var_export($arr,true);
        echo $this->ansiFormat($str, [FrameConsole::FG_RED,FrameConsole::BG_GREY,FrameConsole::BOLD]);
    }
    
    //测试单例功能
    public function insAction() {
        //1.测试instance功能 不带参数
//        $model1 = TestModel::instance();
//        $model2 = TestModel::instance();
//        var_dump($model1===$model2);
        //2.测试instance功能 一个不带参数，一个带参数
        $model1 = TestModel::instance();
        $model2 = TestModel::instance(['id'=>2]);
        var_dump($model1===$model2);
        var_dump($model1->id);
        var_dump($model2->id);
    }
    
    //测试非单例功能
    public function newAction() {
        $model1 = TestModel::create();
        $model2 = TestModel::create();
        var_dump($model1===$model2);
    }
    
    public function pathAction() {
        $re = FrameConsoleApp::$app->console;
        var_dump($re);
    }
}
