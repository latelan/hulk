<?php

/**
 * Description of FrameApp
 * Frame的应用类
 * @author zhangjiulong
 */
class FrameApp extends FrameDI {

    /**
     * console应用的单例
     * @var FrameApp 
     */
    public static $app; //当前的应用实例

    /**
     * 应用的名称
     * @var string 
     */
    public $name = 'application';

    /**
     * 是否为debug模式,默认为是
     * @var boolean 
     */
    public $debug = true;

    /**
     * 默认路由
     * @var string 
     */
    public $defaultRoute = 'home';

    /**
     * 当前的控制器对象
     * @var FrameController 
     */
    public $controller;

    /**
     * 项目的src目录
     * @var string 
     */
    private $_srcPath;

    /**
     * 项目的控制器目录
     * @var string 
     */
    private $_controllerPath;

    /**
     * 项目的日志目录
     * @var string 
     */
    private $_logPath;
    
    /**
     * 脚本执行的开始时间 微秒
     * @var float 
     */
    public $startTime;

    public function __construct($config) {
        static::$app = $this;
        $config = $this->preInit($config);
        parent::__construct($config);
        register_shutdown_function([$this, 'end']);
    }

    protected function preInit($config) {
        /**
         * 设置脚本开始时间
         */
        if(!isset($config['startTime'])){
            $this->startTime = microtime(true);
        }
        
        /**
         * 设置src所在的目录路径
         */
        if (isset($config['srcPath'])) {
            $this->setSrcPath($config['srcPath']);
            unset($config['srcPath']);
        } else {
            throw new ExceptionFrame('the "srcPath" configuration in Application is required');
//            throw new ExceptionFrame('配置文件中必须包含srcPath');
        }
        /**
         * 设置timeZone
         */
        if (isset($config['timeZone'])) {
            $this->setTimeZone($config['timeZone']);
            unset($config['timeZone']);
        } elseif (!ini_get('date.timezone')) {
            $this->setTimeZone('PRC');
        }
        


        /**
         * 注入核心应用组件
         */
        foreach ($this->coreComponents() as $id => $component) {
            if (!isset($config['components'][$id])) {
                $config['components'][$id] = $component;
            } elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class'])) {
                $config['components'][$id]['class'] = $component['class'];
            }
        }
        /**
         * 如果是设置路径(以Path结尾的变量)，则将匿名函数绑定到当前应用对象上,并调用匿名函数获取返回值
         */
        foreach ($config as $key => $value) {
            if (($value instanceof Closure) && substr($key, -4) == 'Path') {
                $cb = Closure::bind($value, $this);
                $config[$key] = $cb();
            }
        }
        return $config;
    }

    /**
     * 设置项目的src目录
     * @param string $path
     * @throws ExceptionFrame
     */
    public function setSrcPath($path) {
        $p = realpath($path);
        if ($p !== false && is_dir($path)) {
            $this->_srcPath = $path;
        } else {
            throw new ExceptionFrame('the dir of "srcPath"(' . $path . ') is not exist!');
//            throw new ExceptionFrame('srcPath设置的目录(' . $path . ')不存在');
        }
    }

    /**
     * 获取项目的src目录
     * @return type
     */
    public function getSrcPath() {
        return $this->_srcPath;
    }

    /**
     * 设置日志目录
     * @param string $path
     */
    public function setLogPath($path) {
        $this->_logPath = $path;
    }

    /**
     * 获取日志目录
     * @return string
     */
    public function getLogPath() {
        if ($this->_logPath === null) {
            $this->setLogPath($this->getSrcPath() . '/../logs');
        }
        return $this->_logPath;
    }

    /**
     * 设置控制器目录
     * @param string $path
     */
    public function setControllerPath($path) {
        $this->_controllerPath = $path;
    }

    /**
     * 返回控制器目录
     * @return string
     */
    public function getControllerPath() {
        if ($this->_controllerPath === null) {
            $this->setControllerPath($this->getSrcPath() . '/app/controllers');
        }
        return $this->_controllerPath;
    }

    /**
     * 设置时区
     * @param string $timeZone
     */
    public function setTimeZone($timeZone) {
        date_default_timezone_set($timeZone);
    }

    /**
     * 获取时区
     */
    public function getTimeZone() {
        date_default_timezone_get();
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
     * 运行应用
     * @return FrameResponse
     */
    public function run() {
        /**
         * 通过处理FrameRequest对象获取返回结果
         */
        $response = $this->handleRequest($this->getRequest());
        /**
         * 发送返回结果
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
            throw new ExceptionFrame('unknow route ' . $route);
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
        if (strpos($className, '-') !== false || !class_exists($className)) {
            return null;
        }
        //如果控制器类文件不存在 返回空
        $controllerFile = realpath($this->getControllerPath() . '/' . $prefix . '/' . $className . '.php');
        if (!file_exists($controllerFile)) {
            return null;
        }

        //控制器必须继承自FrameController或其子类
        if (is_subclass_of($className, 'FrameController')) {
            return $this->createObject($className, ['id' => $id]);
        } else {
            return null;
        }
    }
    
    public function end() {
        if(static::$app->has('log')){
            static::$app->get('log')->flush(true);
        }
    }

}
