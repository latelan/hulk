<?php

/**
 * Description of FrameLogDbTarget
 *
 * @author zhangjiulong
 */
class FrameLogDbTarget extends FrameLogTarget {

    public $db       = 'db';
    public $capacity = 300;

    /**
     * ~~~
     * CREATE TABLE log (
     *     id       BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
     *     level    int(10) unsigned NOT NULL,
     *     category VARCHAR(255) NOT NULL DEFAULT '' COMMENT '分类',
     *     log_time timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '日志记录的时间',
     *     ctime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     *     message  TEXT,
     *     INDEX idx_log_level (level),
     *     INDEX idx_log_category (category)
     * )
     * ~~~
     * @var string      
     */
    public $tableName = 'log';

    /**
     * 执行日志写入
     */
    public function export()
    {
        //写的时候再连接库
        if (is_string($this->db)) {
            $this->db = FrameApp::$app->get($this->db);
        }
        $query   = new FrameQuery(['db' => $this->db]);
        $columns = ['level', 'category', 'message', 'log_time'];
        $rows    = [];
        foreach ($this->messages as $message) {
            list($text, $level, $category, $time) = $message;
            if (!is_string($text)) {
                $text = var_export($text, true);
            }
            $rows[] = [
                'level'    => $level,
                'category' => $category,
                'message'  => $text,
                'log_time' => date('Y-m-d H:i:s', $time),
            ];
        }
        $query->batchInsert($this->tableName, $columns, $rows);
    }

}
