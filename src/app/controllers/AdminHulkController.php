<?php

/**
 * Description of AdminHulkController
 *
 * @author zhangjiulong
 */
class AdminHulkController extends FrameController{
    
    public function indexHaAction() {
        return $this->id.'/'.$this->actionId;
    }
    
    public function indexAction() {
        return $this->id.'/'.$this->actionId;
    }
}
