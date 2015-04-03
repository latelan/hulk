<?php

/**
 * Description of FrameRequest
 * Frame的请求类
 * @author zhangjiulong
 */
class FrameRequest extends FrameObject {

    /**
     * PATHINFO
     * @var string
     */
    private $_pathInfo;

    /**
     * 当前Url
     * @var string 
     */
    private $_url;

    /**
     * 入口脚本的url
     * @var string
     */
    private $_scriptUrl;

    /**
     * 入口文件的路径
     * @var string 
     */
    private $_scriptFile;

    /**
     * 基本url，不含入口文件
     * @var string
     */
    private $_baseUrl;

    public function getRequest($name = null, $default = null) {
        if ($name === null) {
            return array_merge($this->getPost(), $this->getQuery());
        }
        return $this->getParam($name, $default);
    }

    public function getParam($name, $default = null) {
        return isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name]) ? $_POST[$name] : $default);
    }

    public function getQuery($name = null, $default = null) {
        if ($name === null) {
            return $_GET;
        }
        return isset($_GET[$name]) ? $_GET[$name] : $default;
    }

    public function getPost($name = null, $default = null) {
        if ($name === null) {
            return $_POST;
        }
        return isset($_POST[$name]) ? $_POST[$name] : $default;
    }

    /**
     * 解析url返回路由和GET参数
     * @return array
     */
    public function resolve() {
        $pathInfo = $this->getPathInfo();
        return [$pathInfo, $this->getRequest()];
    }

    /**
     * 返回pathInfo
     * @return string
     */
    public function getPathInfo() {
        if ($this->_pathInfo === null) {
            $this->_pathInfo = $this->resolvePathInfo();
        }
        return $this->_pathInfo;
    }

    /**
     * 解析pathInfo
     * @return string
     * @throws ExceptionFrame
     */
    protected function resolvePathInfo() {
        $pathInfo = $this->getUrl();
        if (($pos = strpos($pathInfo, '?')) !== false) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }
        $pathInfo = urldecode($pathInfo);
        $scriptUrl = $this->getScriptUrl();
        $baseUrl = $this->getBaseUrl();
        if (strpos($pathInfo, $scriptUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($scriptUrl));
        } elseif ($baseUrl === '' || strpos($pathInfo, $baseUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($baseUrl));
        } elseif (isset($_SERVER['PHP_SELF']) && strpos($_SERVER['PHP_SELF'], $scriptUrl) === 0) {
            $pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
        } else {
            throw new ExceptionFrame('fail to resovle pathinfo');
//            throw new ExceptionFrame('解析PathInfo失败');
        }
        if ($pathInfo[0] === '/') {
            $pathInfo = substr($pathInfo, 1);
        }
        return (string) $pathInfo;
    }

    /**
     * 返回当前Url
     * @return type
     */
    public function getUrl() {
        if ($this->_url === null) {
            $this->_url = $this->resolveRequestUri();
        }
        return $this->_url;
    }

    /**
     * 解析请求的uri,各个web服务器不尽相同
     * @return string
     * @throws ExceptionFrame
     */
    public function resolveRequestUri() {
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {  //IIS
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            if ($requestUri !== '' && $requestUri[0] !== '/') {
                $requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            throw new ExceptionFrame('fail to resolve uri');
//            throw new ExceptionFrame('解析uri失败');
        }
        return $requestUri;
    }

    /**
     * 返回入口url
     * @return string
     * @throws ExceptionFrame
     */
    public function getScriptUrl() {
        if ($this->_scriptUrl === null) {
            $scriptFile = $this->getScriptFile();
            $scriptName = basename($scriptFile);
            if (basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (basename($_SERVER['PHP_SELF']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } elseif (($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
                $this->_scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
            } elseif (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->_scriptUrl = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $scriptFile));
            } else {
                throw new ExceptionFrame('fail to resolve entry_script');
//                throw new ExceptionFrame('解析入口文件Url失败');
            }
        }
        return $this->_scriptUrl;
    }

    /**
     * 返回基础URL
     * @return string
     */
    public function getBaseUrl() {
        if ($this->_baseUrl === null) {
            $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
        }
        return $this->_baseUrl;
    }

    /**
     * 返回入口文件路径
     * @return string
     */
    public function getScriptFile() {
        return isset($this->_scriptFile) ? $this->_scriptFile : $_SERVER['SCRIPT_FILENAME'];
    }

    /**
     * 设置入口文件路径
     * @param string $value
     */
    public function setScriptFile($value) {
        $this->_scriptFile = $value;
    }

}
