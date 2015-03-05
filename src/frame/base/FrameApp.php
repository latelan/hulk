<?php

/**
 * Description of FrameApp
 * Frame的应用类
 * @author zhangjiulong
 */
class FrameApp extends FrameDI {

    /**
     * 线上生产环境
     */
    const ENV_PROD = 'production';
    
    /**
     * beta测试环境
     */
    const ENV_BETA = 'beta';
    
    /**
     * 线下开发环境
     */
    const ENV_DEV = 'dev';
    
    /**
     * 应用的单例
     * @var FrameWebApp|FrameConsoleApp 
     */
    public static $app; //当前的应用实例

    /**
     * 是否为debug模式,默认为是
     * @var boolean 
     */
    public $debug = true;
    
    /**
     * 应用的运行环境，详见：FrameApp::ENV_PROD|FrameApp::ENV_BETA|FrameApp::ENV_DEV
     * @var string
     */
    public $env = 'dev';

    /**
     * 默认路由
     * @var string 
     */
    public $defaultRoute = 'home';

    /**
     * 项目的根目录
     * @var string 
     */
    private $_basePath;

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
        if (!isset($config['startTime'])) {
            $this->startTime = microtime(true);
        }

        /**
         * 设置应用的运行环境
         */
        if(!in_array($this->env, [self::ENV_DEV,self::ENV_BETA,self::ENV_PROD])){
            throw new ExceptionFrame('enviroment set error,it must in dev|prod|beta');
        }
        
        /**
         * 设置项目根目录
         */
        if (isset($config['basePath'])) {
            $this->setBasePath($config['basePath']);
            unset($config['basePath']);
        } else {
            throw new ExceptionFrame('the "basePath" configuration in Application is required');
//            throw new ExceptionFrame('配置文件中必须包含basePath');
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
    public function setBasePath($path) {
        $p = realpath($path);
        if ($p !== false && is_dir($path)) {
            $this->_basePath = $path;
        } else {
            throw new ExceptionFrame('the dir of "basePath"(' . $path . ') is not exist!');
//            throw new ExceptionFrame('basePath设置的目录(' . $path . ')不存在');
        }
    }

    /**
     * 获取项目的根目录
     * @return string
     */
    public function getBasePath() {
        return $this->_basePath;
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
            $this->setLogPath($this->getBasePath() . '/logs');
        }
        return $this->_logPath;
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
     * 运行应用
     * @return FrameResponse
     */
    public function run() {
        /**
         * 通过处理FrameRequest对象获取返回结果
         */
        $response = $this->handleRequest($this->getRequest());

        return $response;
    }
    
    
    /**
     * 返回脚本执行到当前花费的毫秒数
     * @return float
     */
    public function getConsumeTime() {
        $spend = microtime(true) - $this->startTime;
        return round($spend*1000, 2);
    }

    /**
     * 脚本结束时，执行的方法
     */
    public function end() {
        echo 1;
        if (static::$app->has('log')) {
            static::$app->get('log')->flush(true);
        }
    }

}
