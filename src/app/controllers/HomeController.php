<?php

/**
 * Description of HomeController
 *
 * @author zhangjiulong
 */
class HomeController extends FrameController{
    
    public function indexAction($word='world!') {
        $str = 'Hello, '.$word;
        return $str;
    }
    
    public function dbAction() {
        $query = FrameDB::di()->createQuery();
        $e = new FrameDbExpression('age+1');
        $res = $query->update('user', ['age'=>$e],'id>:id',[':id'=>15]);
        p($res);
    }
}
