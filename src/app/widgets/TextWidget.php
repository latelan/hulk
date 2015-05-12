<?php

/**
 * Description of TextWidget
 * 文本挂件
 * @author zhangjiulong
 */
class TextWidget extends BaseWidget{
    public $title = '360云 - RDS';
    public $content;//实际输出的内容
    
    public function run() {
        return $this->render('text_widget');
    }
}
