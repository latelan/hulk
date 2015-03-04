<?php

/**
 * Description of FrameConsoleRequest
 * 计划任务命令行请求对象
 * @author zhangjiulong
 */
class FrameConsoleRequest extends FrameObject {
    /**
     * 脚本参数
     * @var array 
     */
    private $_params;
    /**
     * 执行的脚本名
     * @var string 
     */
    private $_scriptName;

    /**
     * 解析请求,返回路由和参数
     * @return array
     */
    public function resolve() {
        $rawParams = $this->getParams();
        if (isset($rawParams[0])) {
            $route = $rawParams[0];
            array_shift($rawParams);
        } else {
            $route = '';
        }
        $params = [];
        foreach ($rawParams as $param) {
            //只取--param=value格式的参数
            if (preg_match('/^--(\w+)(=(.*))?$/', $param, $matches)) {
                $name = $matches[1];
                $params[$name] = isset($matches[3]) ? $matches[3] : true;
            }
        }
        return [$route, $params];
    }

    public function getParams() {
        if ($this->_params === null) {
            if (isset($_SERVER['argv'])) {
                $this->_params = $_SERVER['argv'];
                $scriptName = array_shift($this->_params);
                $this->setScriptName($scriptName);
            } else {
                $this->_params = [];
            }
        }
        return $this->_params;
    }

    public function setParams($params) {
        $this->_params = $params;
    }
    
    
    public function getScriptName() {
        return $this->_scriptName;
    }
    
    public function setScriptName($value) {
        $this->_scriptName = $value;
    }
    

}
