<?php

/**
 * Description of HomeController
 *
 * @author zhangjiulong
 */
class HomeController extends Controller{
    
    public function rules() {
        return [
            ['pass,username','required','on'=>'index'],
            ['pass','ip','on'=>'index'],
        ];
    }
    
    public function attributeLabels() {
        return array(
            'pass'=>'密码'
        );
    }
    
    public function indexAction($word='world!') {
        p($this->getRequest('pass'));
        return 'hello,'.$word;
    }
    
}
