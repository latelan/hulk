<?php

/**
 * Description of FrameController
 * Frame控制器基类
 * @author zhangjiulong
 */
class FrameController extends FrameObject {

    /**
     * 当前的控制器名称
     * @var string 
     */
    public $id;
    
    /**
     * 默认的Action名称
     * @var string 
     */
    public $defaultAction = 'index';
    
    /**
     * 当前的action唯一标示
     * @var string
     */
    public $actionId;
    public $actionParams = [];
    
    /**
     * 当前action的名称
     * @var string 
     */
    private $_actionName;
    
    final public function __construct($config = array())
    {
        return parent::__construct($config);
    }

    /**
     * 运行控制器
     * @param string $actionId action的名称
     * @param array $params get参数
     * @return mixed  
     * @throws ExceptionFrame
     */
    public function run($actionId, $params = []) {
        /**
         * 如果没有指定actionid，则使用默认的actionid
         */
        if ($actionId === '') {
            $actionId = $this->defaultAction;
        }
        $this->actionId = $actionId;
        $result = null;
        //根据actionid生成action的方法名
        $action = $this->resolveActionMethod($actionId);
        if ($action === null) {
            throw new ExceptionFrame('fail to resolve ' . htmlspecialchars($this->id . '/' . $actionId));
//            throw new ExceptionFrame('解析请求' . $this->id . '/' . $actionId . '失败');
        }
        //执行action前钩子
        if ($this->beforeAction()) {
            /**
             * 绑定并验证action方法的参数
             */
            $args = $this->bindActionParams($action, $params);
            $result = call_user_func_array(array($this, $action), $args);
            //执行action后钩子
            $this->afterAction();
        }
        return $result;
    }

    /**
     * 根据actionid解析action的方法名
     * @param string $actionId
     * @return string
     */
    protected function resolveActionMethod($actionId) {
        if (preg_match('/^[a-zA-Z0-9\\_-]+$/', $actionId) && strpos($actionId, '--') === false && trim($actionId, '-') === $actionId) {
            $actionName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $actionId))));
            $methodName = $actionName . 'Action';
            $method = new ReflectionMethod($this, $methodName);
            if ($method->isPublic() && $method->getName() === $methodName) {
                $this->setActionName($actionName);
                return $methodName;
            }
        }
        return null;
    }

    /**
     * 从GET参数中绑定action方法的参数，并根据参数的类型进行简单验证
     * @param string $action 方法名
     * @param array $params GET参数
     * @return array 成功则返回action方法的参数列表
     * @throws ExceptionFrame
     */
    public function bindActionParams($action, $params) {
        $method = new ReflectionMethod($this, $action);
        $args = $missing = $actionParams = array();
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $params)) {
                if ($param->isArray()) {
                    $args[] = $actionParams[$name] = is_array($params[$name]) ? $params[$name] : [$params[$name]];
                } else {
                    $args[] = $actionParams[$name] = $params[$name];
                }
                unset($params[$name]);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $actionParams[$name] = $param->getDefaultValue();
            } else {
                $missing[] = $name;
            }
        }
        if (!empty($missing)) {
            throw new ExceptionFrame('miss param ' . implode(', ', $missing));
        }
        $this->actionParams = $actionParams;
        return $args;
    }

    /**
     * action方法的前钩子，子类覆盖时需return true或者parent::beforeAction，切记
     * @return boolean
     */
    protected function beforeAction() {
        return true;
    }

    /**
     * action方法的后钩子
     */
    protected function afterAction() {
        
    }

    /**
     * name=null时，返回$_GET+$_POST，name不为Null时优先取$_GET,取不到再从$_POST中取
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getRequest($name = null, $default = null) {
        $request =  FrameApp::$app->getRequest()->getRequest($name, $default);
        if($name==null){
            $controllerAction = $this->id.'/'.  $this->actionId;
            if(isset($request[$controllerAction])){
                unset($request[$controllerAction]);
            }
        }
        return $request;
    }

    /**
     * 返回$_POST
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getPost($name = null, $default = null) {
        return FrameApp::$app->getRequest()->getPost($name, $default);
    }

    /**
     * 优先取自$_GET,取不到再从$_POST中取
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getParam($name, $default = null) {
        return FrameApp::$app->getRequest()->getParam($name, $default);
    }
    
    /**
     * 返回当前控制器的名称
     * @return string
     */
    public function getControllerName()
    {
        $pos = strrpos($this->id, '/');
        if ($pos === false) {
            $className = $this->id;
        } else {
            $className = substr($this->id, $pos + 1);
        }
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $className)));
    }
    
    /**
     * 返回当前action的名称
     * @return string
     */
    public function getActionName()
    {
        if($this->_actionName!==null){
            return $this->_actionName;
        }
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $this->actionId))));
    }
    
    /**
     * 设置当前action的名称
     * @param string $name
     */
    public function setActionName($name)
    {
        $this->_actionName = $name;
    }

}
