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

        if ($this->db->supportSavepoint()) {
            $this->db->createSavepoint('LEVEL' . $this->_level);
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
            return;
        }
        
        if ($this->db->supportSavepoint()) {
            $this->db->releaseSavepoint('LEVEL' . $this->_level);
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

        if ($this->db->supportSavepoint()) {
            $this->db->rollbackSavepoint('LEVEL' . $this->_level);
        } else {
            throw new ExceptionFrame('rollback fail: nested transaction not supported');
        }
    }

}
