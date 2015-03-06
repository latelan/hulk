<?php

/**
 * Description of FrameTransaction
 * 数据库事务类
 * @author zhangjiulong
 */
class FrameTransaction extends FrameObject {

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

    public function getIsActive() {
        return $this->_level > 0 && $this->db && $this->db->getIsActive();
    }

    /**
     * 开启事务
     * @throws ExceptionFrame
     */
    public function begin() {
        if ($this->db === null) {
            throw new ExceptionFrame('FrameTransaction::db must be set.');
        }

        $this->db->open();

        if ($this->_level == 0) {
            $this->db->pdo->beginTransaction();
        }

        $this->_level++;
    }

    /**
     * 提交事务
     * @throws ExceptionFrame
     */
    public function commit() {
        if (!$this->getIsActive()) {
            throw new ExceptionFrame('Fail to commint transaction: transaction was inactive.');
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

        throw new ExceptionFrame('the inner transaction error!');
        
    }

}
