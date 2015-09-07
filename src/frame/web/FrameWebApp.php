<?php

/**
 * Description of FrameWebApp
 * web应用类
 * @author zhangjiulong
 */
class FrameWebApp extends FrameApp {

    /**
     * 当前的控制器对象
     * @var FrameController 
     */
    public $controller;

    /**
     * 项目的控制器目录
     * @var string 
     */
    private $_controllerPath;


    /**
     * 设置控制器目录
     * @param string $path
     */
    public function setControllerPath($path) {
        $path = static::getAlias($path);
        $this->_controllerPath = $path;
    }

    /**
     * 返回控制器目录
     * @return string
     */
    public function getControllerPath() {
        if ($this->_controllerPath === null) {
            $this->setControllerPath($this->getBasePath() . '/src/app/controllers');
        }
        return $this->_controllerPath;
    }

    /**
     * 返回应用的核心组件
     * @return array
     */
    public function coreComponents() {
        return [
            'request' => ['class' => 'FrameRequest'],
            'response' => ['class' => 'FrameResponse'],
        ];
    }

    /**
     * 运行web应用，返回response对象
     * @return FrameResponse
     */
    public function run() {
        $response = parent::run();
        /**
         * 输出返回结果
         */
        $response->send();
        return $response;
    }

    /**
     * 处理request请求
     * @param object $request FrameRequest或者其扩展子类的实例
     * @return \FrameResponse
     * @throws ExceptionFrame
     */
    protected function handleRequest($request) {
        /**
         * FrameRequest::resolve()方法返回路由和GET参数
         */
        list($route, $params) = $request->resolve();
        if ($route === '') {
            $route = $this->defaultRoute;
        }
        /**
         * 根据路由创建控制器对象和action名字,解析路由失败则抛出异常
         */
        $parts = $this->createController($route);
        if (is_array($parts)) {
            /**
             * 获取生成的controller对象和actionId
             */
            list($controller, $actionId) = $parts;
            /**
             * 将controller对象赋值应用的controller属性，在应用的任何地方，都可以调用FrameApp::$app->controller获取当前控制器对象
             */
            static::$app->controller = $controller;
            /**
             * 根据actionId，访问控制器对应的action方法获取结果集
             */
            $result = $controller->run($actionId, $params);
            /**
             * 将结果集转化为FrameResponse对象返回
             */
            if ($result instanceof FrameResponse) {
                return $result;
            } else {
                $response = $this->getResponse();
                if ($result !== null) {
                    $response->data = $result;
                }
                return $response;
            }
        } else {
            throw new ExceptionFrame('unknow route ' . htmlspecialchars($route));
//            throw new ExceptionFrame('解析Url请求失败！' . $route);
        }
    }

    /**
     * 根据路由创建控制器实例,成功则返回控制器实例和actionId组成的数组
     * @param string $route
     * @return boolean|array
     */
    public function createController($route) {
        if ($route === '') {
            $route = $this->defaultRoute;
        }
        $route = trim($route, '/');
        //禁止url中出现2个/
        if (strpos($route, '//') !== false) {
            return false;
        }
        $path = explode('/', $route);
        $id = '';   //控制器的id
        foreach ($path as $val) {
            $id = ltrim($id . '/' . $val, '/');
            array_shift($path);
            if (count($path) >= 2) {    //表示是个目录
                continue;
            }
            //根据控制器id创建控制器实例 创建成功则跳出循环
            $controller = $this->createControllerById($id);
            if ($controller) {
                break;
            }
        }
        if ($controller === null) {
            return false;
        }
        $actionId = !empty($path) ? end($path) : '';
        return [$controller, $actionId];
    }

    /**
     * 根据路由里的控制器id生成控制器对象
     * @param type $id
     * @return FrameController
     */
    public function createControllerById($id) {
        $pos = strrpos($id, '/');
        if ($pos === false) {
            $prefix = '';
            $className = $id;
        } else {
            $prefix = substr($id, 0, $pos + 1);
            $className = substr($id, $pos + 1);
        }
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9\\-_]*$/', $className)) {
            return null;
        }
        if ($prefix !== '' && !preg_match('/^[a-zA-Z0-9_\/]+$/', $prefix)) {
            return null;
        }
        //驼峰命名的控制器可以用短横分隔来请求 admin-hulk/index 相当于 AdminHulkController::indexAction()
        $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $className))) . 'Controller';
        //类名中不能再有短横
        if(strpos($className, '-') !== false){
            return null;
        }
        
        //如果控制器类文件不存在 返回空
        $controllerFile = realpath($this->getControllerPath() . '/' . $prefix . '/' . $className . '.php');
        if (!file_exists($controllerFile)) {
            return null;
        }else{  //存在则加载文件 以及baseController
            $this->requireBaseControllerFile($prefix);
            include($controllerFile);
        }
        
        //控制器必须继承自FrameController或其子类
        if (is_subclass_of($className, 'FrameController')) {
            //实例化之前先require BaseController如果有的话
            return static::createObject($className, ['id' => $id]);
        } else {
            return null;
        }
    }
    
    /**
     * require baseController
     * @param string $prefix 控制器路由的前缀
     */
    protected function requireBaseControllerFile($prefix) {
        if($prefix==''){
            $file =  realpath($this->getControllerPath() . '/' . $prefix . '/' . $this->baseControllerName.'.php');
            if(file_exists($file)){
                require_once $file;
            }
        }else{
            $path = '';
            foreach (explode('/', $prefix) as $dir) {
                $path .= $dir.'/';
                $file = realpath($this->getControllerPath() . '/' . $path . '/' . $this->baseControllerName.'.php');
                if(file_exists($file)){
                    require_once $file;
                }
            }
        }
    }

}
