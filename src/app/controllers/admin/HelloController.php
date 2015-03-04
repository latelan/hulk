<?php

/**
 * Description of HelloController
 *
 * @author zhangjiulong
 */
class HelloController extends FrameController{
    
    public function indexAction($id,array $p = array()) {
        p($this->actionParams);
    }
}
