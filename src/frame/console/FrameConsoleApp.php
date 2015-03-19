<?php

/**
 * Description of FrameConsoleApp
 * 命令行应用
 * @author zhangjiulong
 */
class FrameConsoleApp extends FrameApp {

    /**
     * 项目的console控制器目录
     * @var string
     */
    private $_consolePath;

    /**
     * 当前的console对象
     * @var FrameConsole 
     */
    public $console;


    /**
     * 设置命令行控制器目录
     * @param string $path
     */
    public function setConsolePath($path) {
        $path = static::getAlias($path);
        $this->_consolePath = $path;
    }

    public function getConsolePath() {
        if ($this->_consolePath === null) {
            $this->_consolePath = $this->getBasePath() . '/src/app/task/consoles';
        }
        return $this->_consolePath;
    }

    public function coreComponents() {
        return [
            'request' => ['class' => 'FrameConsoleRequest'],
        ];
    }

    public function run() {
        return parent::run();
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
            return static::createObject($className, ['id' => $id]);
        } else {
            //@TODO log
            return null;
        }
    }
}
