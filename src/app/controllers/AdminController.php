<?php

/**
 * Description of AdminController
 *
 * @author zhangjiulong
 */
class AdminController extends FrameController {

    protected function beforeAction() {
//        return false;
        return parent::beforeAction();
    }

    public function indexAction() {
                
        FrameApp::$app->debug = true;
        FrameLog::info(['consume'=>3],'what');
//        FrameLog::debug('3想带上你私奔');
//        FrameLog::error('2北京北京');
//        FrameLog::info('2我要飞得更高2');
//        FrameLog::error('2北京北京2');
//        FrameLog::debug('2想带上你私奔2');
//        FrameLog::info('2我要飞得更高3');
//        FrameLog::error('2北京北京3');
//        FrameLog::debug('2想带上你私奔3');
//        FrameLog::info('2我要飞得更高4');
//        p($this->getParam('id',22),false);
//        p($this->getRequest('id',23),false);
//        p($this->getPost());
        echo $this->id . '/' . $this->actionId;
        exit;
    }

    public function dbAction() {
//        $query = TimeDao::find();
//        $page = 2;
//        $pageSize = 3;
//        $count = $query->count();
//        $query->limit($pageSize, $pageSize*($page-1));
//        $res = $query->queryAll();
//        p($count,false);
//        p($res);
//        $res = TimeDao::getRow(['name'=>'zjl']);
//        p($res);
        
        
        $query = new FrameQuery(['sql'=>'select * from time']);
        $res = $query->execute();
        p($res);
        $trans = FrameDB::di()->beginTransaction();
        try {
            $query = new FrameQuery();
            $query->insert('time', ['name' => 'zp2']);
            $id = $query->getLastInsertId();
            $query->reset();
            p($id,false);
            $query->insert('tname', ['tid' => $id, 'name' => 'zp2']);
            $this->test($trans);
            $trans->commit();
        } catch (Exception $exc) {
            $trans->rollBack();
            echo $exc->getTraceAsString();
        }
    }

    public function test($t) {
        $trans = FrameDB::di()->beginTransaction();
        try {
            $query = new FrameQuery();
            $r = $query->update('tname', ['age'=>6], 'tid>:id',[':id'=>2]);
            $query->reset();
            $r = $query->update('time', ['name'=>'xyzy'], 'id=:id',[':id'=>2]);
            throw new Exception('update error');
            $trans->commit();
        } catch (Exception $exc) {
            $trans->rollBack();
            echo $exc;
        }
    }

}
