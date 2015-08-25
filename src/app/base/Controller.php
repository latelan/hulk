<?php

/**
 * Description of Controller
 * 在FrameController的基础上增加了验证的逻辑
 * @author zhangjiulong
 */
class Controller extends FrameController {
    use ValidateTrait;
    /**
     * 返回的结果
     * @var array 
     */
    protected $_msg = [
        'errno'=>0,
        'errmsg'=>'',
        'node'=>'',
        'data'=>[
        ]
    ];
    
    public function init()
    {
        parent::init();
        $this->_msg['node'] = posix_uname()['nodename'];
    }
    
    /**
     * 设置返回的_msg里面的data
     * @param array $data
     */
    public function setData($data) {
        $this->_msg['data'] = (array)$data + $this->_msg['data'];
    }
    
    protected function beforeAction() {
        
        //执行验证请求的参数 失败则抛出异常
        $this->setScenario($this->actionId)->validate();
        return parent::beforeAction();
    }

    //返回待验证的值
    public function getValidateValue($attribute)
    {
        return $this->getRequest($attribute);
    }
    
    public function getOriginalId()
    {
        $path = [];
        $ids = explode('/', $this->id);
        foreach ($ids as $id) {
            if(strpos($id, '-')!==false){
                $path[] = str_replace(' ','',ucwords(str_replace('-', ' ', $id)));
            }else{
                $path[] = $id;
            }
        }
        return implode('/', $path);
    }
}
