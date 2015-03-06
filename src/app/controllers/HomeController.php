<?php

/**
 * Description of HomeController
 *
 * @author zhangjiulong
 */
class HomeController extends FrameController{
    
    public function indexAction($word='world!') {
        $str = 'Hello, '.$word;
        return $str;
    }
    
    public function dbAction() {
//        $res = $this->insert('zhangjiulong');
//        $res = $this->batch();
//        $res = $this->update();
//        $res = $this->delete();
        $trans = FrameApp::$app->db->beginTransaction();
        try {
            $this->insert('zjl1');
            $this->second();
//            throw new Exception('zjl1');
            $this->insert('zjl2');
            $trans->commit();
        } catch (Exception $exc) {
            echo $exc->getMessage();
            $trans->rollback();
        }
    }
    
    public function second() {
        $trans = FrameApp::$app->db->beginTransaction();
        try {
            $this->insert('zjl3');
//            throw new Exception('zjl3');
            $this->insert('zjl4');
            $trans->commit();
        } catch (Exception $exc) {
            echo $exc->getMessage();
            $trans->rollback();
        }
    }
    
    public function delete() {
        return UserDao::delete(['in','id',[1,2,3]]);
    }
    
    public function update() {
        return UserDao::update(['age'=>19], 'id>:id', [':id'=>3]);
    }
    
    public function insert($name) {
        return UserDao::insert(['name'=>$name]);
    }
    
    public function batch() {
        $res = UserDao::batchInsert(['name','age'], [
            ['name'=>'zs','age'=>1],
            ['name'=>'ls','age'=>2],
            ['name'=>'ww','age'=>3],
        ]);
        return $res;
    }
}
