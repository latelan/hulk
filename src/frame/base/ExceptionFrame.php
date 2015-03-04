<?php

/**
 * Description of ExceptionFrame
 * Frame异常类
 * @author zhangjiulong
 */
class ExceptionFrame extends Exception {

    public function __construct($message = '', $code = 400, $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}
