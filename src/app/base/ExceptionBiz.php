<?php

/**
 * Description of ExceptionBiz
 * 业务异常
 * @author zhangjiulong
 */
class ExceptionBiz extends Exception {

    private $_ext_info;

    public function __construct($err, $ext_info = array()) {
        $this->_ext_info = $ext_info;
        $arr = explode('=>', $err);
        parent::__construct($arr[1], $arr[0]);
    }

    public function getExtInfo() {
        return $this->_ext_info;
    }

}
