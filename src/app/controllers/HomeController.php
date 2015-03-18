<?php

/**
 * Description of HomeController
 *
 * @author zhangjiulong
 */
class HomeController extends FrameController{
    
    public function indexAction($word='world!') {
        return 'hello,'.$word;
    }
    
}
