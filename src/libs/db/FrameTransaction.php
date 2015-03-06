<?php

/**
 * Description of FrameTransaction
 * 数据库事务类
 * @author zhangjiulong
 */
class FrameTransaction {

    /**
     * 数据库链接类
     * @var FrameDB
     */
    public $db;

    /**
     * 事务的层级，用来控制多层事务嵌套
     * @var type 
     */
    private $_level = 0;

    /**
     * 构造函数，实现对象属性的赋值
     * @param array $config
     */
    public function __construct($config = []) {
        if (!empty($config) && is_array($config)) {
            foreach ($config as $name => $value) {
                $this->{$name} = $value;
            }
        }
        $this->init();
    }
    
    public function init() {
        
    }

    public function getIsActive() {
        return $this->_level > 0 && $this->db && $this->db->getIsActive();
    }

    /**
     * 开启事务
     * @throws Exception
     */
    public function begin() {
        if ($this->db === null) {
            throw new Exception('FrameTransaction::db must be set.');
        }

        $this->db->open();

        if ($this->_level == 0) {
            $this->db->pdo->beginTransaction();
        }

        $this->_level++;
    }

    /**
     * 提交事务
     * @throws Exception
     */
    public function commit() {
        if (!$this->getIsActive()) {
            throw new Exception('Fail to commint transaction: transaction was inactive.');
        }
        $this->_level--;

        //@TODO log
        if ($this->_level == 0) {
            $this->db->pdo->commit();
        }
    }

    /**
     * 事务回滚
     * @return null
     */
    public function rollBack() {
        if (!$this->getIsActive()) {
            //do nothing
            return;
        }
        $this->_level--;

        if ($this->_level == 0) {
            $this->db->pdo->rollBack();
            return;
        }

        throw new Exception('the inner transaction error!');
    }

}
