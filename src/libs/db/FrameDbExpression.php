<?php

/**
 * Description of FrameDbExpression
 * db的sql表达式
 * @author zhangjiulong
 */
class FrameDbExpression extends FrameObject{
    public $expression;
    public $params = [];
    
    public function __construct($expression,$params=[]) {
        $this->expression = $expression;
        $this->params = $params;
    }
}
