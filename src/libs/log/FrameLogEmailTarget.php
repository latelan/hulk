<?php

/**
 * Description of FrameLogEmailTarget
 * 发送邮件的日志
 * @author zhangjiulong
 */
class FrameLogEmailTarget extends FrameLogTarget {

    public $capacity    = 20;
    public $from;
    public $subject;
    public $to;
    public $cc          = '';
    public $send_method = 'send'; //send|qmsSend

    /**
     * 数组展示的深度
     * @var int 
     */
    public $depth = 3;

    /**
     * 将messages按category分组
     * @var array 
     */
    private $_messageGroup = [];

    /**
     * 自定义的邮件列表
     * @var array
     */
    private $_customMailMessages = [];

    public function export()
    {
        if (empty($this->to) || empty($this->from) || empty($this->subject)) {
            throw new ExceptionFrame('the property "to|from|subject" is required');
        }
        //将message分类
        $this->divideMessages();
        //每个category发一封邮件
        foreach ($this->_messageGroup as $category => $messages) {
            $content  = implode(PHP_EOL . "<br /><br />", array_map([$this, 'formatMessage'], $messages));
            $mailInfo = [
                'from'    => $this->from,
                'to'      => $this->to,
                'subject' => $this->subject . '-' . $category,
                'cc'      => $this->cc,
                'content' => TextWidget::widget(['content' => $content]),
            ];
            $this->send($mailInfo);
        }
        $this->_messageGroup = [];
        //自定义的邮件发送
        foreach ($this->_customMailMessages as $message) {
            list($text, $level, $category, $time) = $message;
            $mailInfo            = array_merge(['from' => $this->from, 'to' => $this->to, 'cc' => $this->cc, 'subject' => $this->subject . '-' . $category], $text['mailinfo']);
            unset($text['mailinfo']);
            $text['logtime']     = date('Y-m-d H:i:s', $time);
            $text['user_ip']     = $_SERVER['REMOTE_ADDR'];
            //只取最外层的三唯数据
            $text                = $this->formatArr($text, $this->depth);
            $mailInfo['content'] = KvWidget::widget(['arr' => $text]);
            $this->send($mailInfo);
        }
        $this->_customMailMessages = [];
    }

    //将message分成有mailinfo和没有mailinfo的
    protected function divideMessages()
    {
        foreach ($this->messages as $message) {
            list($text, $level, $category, $time) = $message;
            if (is_array($text) && isset($text['mailinfo'])) {
                if (is_array($text['mailinfo'])) {
                    //如果有自定义的mailinfo
                    $this->_customMailMessages[] = $message;
                } else {
                    unset($message[0]['mailinfo']);
                    //添加消息到组
                    $this->_messageGroup[$category][] = $message;
                }
            }
        }
        //清空messages
        $this->messages = [];
    }

    //只取数组的depth层
    public function formatArr($arr, $depth)
    {
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                if ($depth <= 1) {
                    $arr[$k] = '[..]';
                } else {
                    $arr[$k] = $this->formatArr($v, $depth - 1);
                }
            }
        }
        return $arr;
    }

    public function formatMessage($message)
    {
        list($text, $level, $category, $time) = $message;
        if (!is_array($text)) {
            $content['message'] = $text;
        } else {
            $content = $text;
        }
        $content['logtime'] = date('Y-m-d H:i:s', $time);
        $content['user_ip'] = $_SERVER['REMOTE_ADDR'];
        $this->formatArr($content, $this->depth);
        return KvWidget::widget(['arr' => $content, 'wrap' => false]);
    }

    protected function send($mailinfo)
    {
        MailUtil::{$this->send_method}($mailinfo);
    }

}
