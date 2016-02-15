<?php

/**
 * Description of FrameLog
 *
 * @author zhangjiulong
 */
class FrameLog extends FrameObject {

    const LEVEL_ERROR = 1;
    const LEVEL_INFO  = 4;
    const LEVEL_DEBUG = 8;

    public static $levelNames = [
        self::LEVEL_ERROR => 'error',
        self::LEVEL_INFO  => 'info',
        self::LEVEL_DEBUG => 'debug',
    ];
    public $messages = [];
    public $capacity = 1000;    //总容量
    public $targets  = [];   //消息接收者

    public function init() {

        parent::init();

        foreach ($this->targets as $k => $target) {
            if (!$target instanceof FrameLogTarget) {
                $this->targets[$k] = static::createObject($target);
            }
        }
    }

    /**
     * 返回di容器中log对应的对象
     * @param string $id
     * @param boolean $throwException
     * @return FrameLog
     */
    public static function di($id = 'log', $throwException = true) {
        return parent::di($id, $throwException);
    }

    static public function info($message, $category = 'info') {
        static::di()->log($message, static::LEVEL_INFO, $category);
    }

    /**
     * 记录错误日志
     * @param mixed $message
     * @param string $category
     * @param mixed $mailinfo false则不发邮件，true发邮件，数组可自定义邮件信息
     */
    static public function error($message, $category = 'app', $mailinfo = false) {
        if ($mailinfo) {
            if (!is_array($message)) {
                $message = ['message' => $message];
            }
            $message = array_merge($message, ['mailinfo' => $mailinfo]);
        }
        static::di()->log($message, static::LEVEL_ERROR, $category . '.error');
    }

    static public function debug($message, $category = 'app') {
        if (FrameApp::$app->debug) {
            static::di()->log($message, static::LEVEL_DEBUG, $category . '.debug');
        }
    }

    public function log($message, $level, $category = 'app') {
        $time             = microtime(true);
        $this->messages[] = [$message, $level, $category, $time];
        /**
         * 当消息总量达到容量上限时，进行一次写入
         */
        if (count($this->messages) >= $this->capacity) {
            $this->flush();
        }
    }

    public function flush($final = false) {
        $targetErrors = [];
        foreach ($this->targets as $target) {
            if ($target->enabled) {
                try {
                    $target->collect($this->messages, $final);
                } catch (Exception $e) {
                    $target->enabled = false;
                    $targetErrors[]  = [
                        'Unable to send log via ' . get_class($target) . ':' . $e->getMessage(),
                        FrameLog::LEVEL_ERROR,
                        __METHOD__,
                        microtime(true),
                    ];
                }
            }
        }
        $this->messages = [];
        if (!empty($targetErrors)) {
            $this->messages = $targetErrors;
            $this->flush(true);
        }
    }

    static public function getLevelName($level) {
        if (isset(self::$levelNames[$level])) {
            return self::$levelNames[$level];
        } else {
            return 'unknown level';
        }
    }

}
