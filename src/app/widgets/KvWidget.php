<?php

/**
 * Description of KvWidget
 * 根据键值对来生成内容
 * @author zhangjiulong
 */
class KvWidget extends BaseWidget {

    /**
     * key=>value数组
     * @var array
     */
    public $arr;

    /**
     * 需要加颜色的key列表
     * @var array 
     */
    public $colors;
    
    /**
     * 是否在外面包裹
     * @var boolean
     */
    public $wrap = true;
    public $border = 0;

    public function run() {
        $content =  $this->render('kv_widget', ['arr' => $this->arr]);
        if($this->wrap){
            return TextWidget::widget(['content'=>$content]);
        }else{
            return $content;
        }
    }
    
    public function getKeyStyle($color=true) {
        if($color){
            return 'width: 30%;padding:15px;color:#d71345;word-wrap:break-word;word-break:break-all;border-bottom:1px solid #ddd;';
        }else{
            return 'width: 30%;padding:15px;color:#000;word-wrap:break-word;word-break:break-all;border-bottom:1px solid #ddd;';
        }
    }
    
    public function getValStyle($color=true) {
        if($color){
            return 'padding:15px;color:#d71345;border-bottom:1px solid #ddd;';
        }else{
            return 'padding:15px;color:#000;border-bottom:1px solid #ddd;';
        }
    }

}
