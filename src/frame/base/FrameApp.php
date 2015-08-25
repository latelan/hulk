<?php

/**
 * Description of FrameApp
 * Frame的应用基类
 * @author zhangjiulong
 */
abstract class FrameApp extends FrameDI {

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
     * 测试环境
     */
    const ENV_TEST = 'test';

    /**
     * 应用的单例
     * @var FrameWebApp|FrameConsoleApp 
     */
    public static $app; //当前的应用实例

    /**
     * 应用中的路径别名
     * @var array 
     */
    public static $aliases = [];

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

    /**
     * 应用配置的其他属性
     * @var array
     */
    private $__attrs__ = [];
    
    /**
     * 业务控制器继承的基类名称
     * @var string
     */
    public $baseControllerName = 'BaseController';
    
    /**
     * 预加载的组件，默认预加载日志组件
     * @var array
     */
    public $preloads = ['log'];

    /**
     * 初始化应用
     * @param array $config 应用的配置数组
     */
    public function __construct($config) {
        /**
         * 将应用的实例赋值给$app静态属性，项目中可以使用FrameApp::$app获取当前应用
         */
        static::$app = $this;

        /**
         * 对配置文件进行预处理
         */
        $config = $this->preInit($config);

        parent::__construct($config);

        /**
         * 注册脚本结束时执行的代码到FrameApp::end()方法
         */
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
        if (!in_array($this->env, [self::ENV_DEV, self::ENV_BETA, self::ENV_PROD,  self::ENV_TEST])) {
            throw new ExceptionFrame('enviroment set error,it must in dev|prod|beta|test');
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
         * 设置别名
         */
        if(isset($config['aliases'])){
            $this->setAliases($config['aliases']);
            unset($config['aliases']);
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
        
        return $config;
    }

    public function init() {
        //实例化log等预加载组件
        foreach ($this->preloads as $id) {
            if($this->has($id)){
                $this->get($id);
            }
        }
    }
    
    /**
     * 设置项目的src目录
     * @param string $path
     * @throws ExceptionFrame
     */
    public function setBasePath($path) {
        $p = realpath($path);
        if ($p !== false && is_dir($p)) {
            $this->_basePath = $p;
            static::setAlias('@root', $p);
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
        $path = static::getAlias($path);
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

    abstract protected function handleRequest($request);

    /**
     * 返回脚本执行到当前花费的毫秒数
     * @return float
     */
    public function getConsumeTime() {
        $spend = microtime(true) - $this->startTime;
        return round($spend * 1000, 2);
    }

    /**
     * 脚本结束时，执行的方法
     */
    public function end() {
        if (static::$app->has('log')) {
            static::$app->get('log')->flush(true);
        }
    }

    /**
     * 获取应用属性的取值顺序 定义的公有属性--DI容器中的对象--getName()---__attrs__属性（非公有）
     * @param string $name
     * @return mixed
     */
    protected function __getException($name)
    {
        return $this->__attrs__[$name];
    }
    
    /**
     * 赋值应用属性的顺序 定义的公有属性--DI容器中的对象--setName($value)---__attrs__属性（非公有）
     * @param string $name
     * @param mixed $value
     */
    protected function __setException($name, $value)
    {
        $this->__attrs__[$name] = $value;
    }

    /**
     * 
     * @param string $alias
     * @return boolean|string
     */
    static public function getAlias($alias) {
        //如果不带@直接返回
        if (strncmp($alias, '@', 1)) {
            return $alias;
        }
        $pos = strpos($alias, '/');
        //获取别名
        $root = $pos === false ? $alias : substr($alias, 0, $pos);
        if (isset(static::$aliases[$root])) {
            //如果存在别名 则返回全路径
            return $pos === false ? static::$aliases[$root] : static::$aliases[$root] . substr($alias, $pos);
        }
        return false;
    }

    /**
     * 设置路径别名
     * @param string $alias
     * @param string $path 别名对应的路径，可以是含别名的路径
     * @throws ExceptionFrame 当$alias中包含/时 抛出异常
     */
    static public function setAlias($alias, $path) {
        //如果别名不带@ 默认加上
        if (strncmp($alias, '@', 1)) {
            $alias = '@'.$alias;
        }
        $pos = strpos($alias, '/');
        if ($pos !== false) {
            throw new ExceptionFrame('the alias name can not contain "/"');
        }
        if ($path !== null) {
            //设置路径
            $path = strncmp($path, '@', 1) ? rtrim($path, '\\/') : static::getAlias($path);
            static::$aliases[$alias] = $path;
        } else {
            //销毁别名
            unset(static::$aliases[$alias]);
        }
    }
    
    //初始化应用时 设置路径别名
    public function setAliases($aliases) {
        foreach ($aliases as $alias => $path) {
            static::setAlias($alias, $path);
        }
    }
    
    //是否为开发环境
    public function isDev($include_test=true)
    {
        $envs = [self::ENV_DEV];
        if($include_test){
            $envs[] = self::ENV_TEST;
        }
        return in_array($this->env, $envs);
    }
    
    //是否为生产环境
    public function isProd($include_beta=true)
    {
        $envs = [self::ENV_PROD];
        if($include_beta){
            $envs[] = self::ENV_BETA;
        }
        return in_array($this->env, $envs);
    }
    
}
