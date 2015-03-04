<?php

/**
 * Description of FrameConsoleApp
 * 命令行应用
 * @author zhangjiulong
 */
class FrameConsoleApp extends FrameDI {

    /**
     * console应用的单例
     * @var FrameConsoleApp 
     */
    public static $app;

    /**
     * 应用的名称
     * @var string 
     */
    public $name;

    /**
     * 是否为debug模式,默认为是
     * @var boolean 
     */
    public $debug = true;

    /**
     * 默认路由
     * @var string 
     */
    public $defaultRoute = 'help';

    /**
     * 项目的src目录
     * @var string 
     */
    private $_srcPath;

    /**
     * 项目的console控制器目录
     * @var string
     */
    private $_consolePath;

    /**
     * 任务的日志目录
     * @var string 
     */
    private $_logPath;

    /**
     * 当前的console对象
     * @var FrameConsole 
     */
    public $console;

    public function __construct($config = array()) {
        /**
         * 设置应用实例对象到静态属性$app，访问FrameConsoleApp::$app即可获得当前的应用实例
         */
        static::$app = $this;
        $config = $this->preInit($config);
        parent::__construct($config);
    }

    public function preInit($config) {
        /**
         * 设置项目的src目录
         */
        if (isset($config['srcPath'])) {
            $this->setSrcPath($config['srcPath']);
            unset($config['srcPath']);
        } else {
            throw new ExceptionFrame('the "srcPath" configuration in Application is required');
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

    public function init() {
        
    }

    /**
     * 设置src目录
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

    public function getSrcPath() {
        return $this->_srcPath;
    }

    /**
     * 设置命令行控制器目录
     * @param string $path
     */
    public function setConsolePath($path) {
        $this->_consolePath = $path;
    }

    public function getConsolePath() {
        if ($this->_consolePath === null) {
            $this->_consolePath = $this->getSrcPath() . '/app/task';
        }
        return $this->_consolePath;
    }

    /**
     * 设置任务的日志目录
     * @param string $path
     */
    public function setLogPath($path) {
        $this->_logPath = $path;
    }

    public function getLogPath() {
        return $this->_logPath;
    }

    public function coreComponents() {
        return [
            'request' => ['class' => 'FrameConsoleRequest'],
        ];
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

    public function run() {
        $response = $this->handleRequest($this->getRequest());
        return $response;
    }

    public function handleRequest($request) {
        list ($route, $params) = $request->resolve();
        if ($route === '') {
            $route = $this->defaultRoute;
        }
        $parts = $this->createConsole($route);
        if (is_array($parts)) {
            list($console, $actionId) = $parts;
            static::$app->console = $console;
            //@TODO 是否需要返回值
            return $console->run($actionId, $params);
        } else {
            throw new ExceptionFrame('unknow route ' . $route);
//            throw new ExceptionFrame('解析Url请求失败！' . $route);
        }
    }

    public function createConsole($route) {
        if ($route === '') {
            $route = $this->defaultRoute;
        }
        $route = trim($route, '/');
        //禁止url中出现2个/
        if (strpos($route, '//') !== false) {
            //@TODO log
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
            $console = $this->createConsoleById($id);
            if ($console) {
                break;
            }
        }
        if ($console === null) {
            return false;
        }
        $actionId = !empty($path) ? end($path) : '';
        return [$console, $actionId];
    }

    public function createConsoleById($id) {
        $pos = strrpos($id, '/');
        if ($pos === false) {
            $prefix = '';
            $className = $id;
        } else {
            $prefix = substr($id, 0, $pos + 1);
            $className = substr($id, $pos + 1);
        }
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9\\-_]*$/', $className)) {
            //@TODO log
            return null;
        }
        if ($prefix !== '' && !preg_match('/^[a-zA-Z0-9_\/]+$/', $prefix)) {
            //@TODO log
            return null;
        }
        //驼峰命名的控制器可以用短横分隔来请求 admin-hulk/index 相当于 AdminHulkConsole::indexAction()
        $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $className))) . 'Console';
        if (strpos($className, '-') !== false || !class_exists($className)) {
            //@TODO log
            return null;
        }
        //如果控制器类文件不存在 返回空
        $consoleFile = realpath($this->getConsolePath() . '/' . $prefix . '/' . $className . '.php');
        if (!file_exists($consoleFile)) {
            //@TODO log
            return null;
        }

        //控制器必须继承自FrameConsole或其子类
        if (is_subclass_of($className, 'FrameConsole')) {
            return $this->createObject($className, ['id' => $id]);
        } else {
            //@TODO log
            return null;
        }
    }

}
