<?php

/**
 * Description of HomeController
 *
 * @author zhangjiulong
 */
class HomeController extends FrameController {

    public function indexAction($word = 'world!') {
        $str = 'Hello, ' . $word;
        return $str;
    }

    public function dbAction() {
        $db = FrameApp::$app->db;
        $query = new FrameQuery(['db' => $db]);
        
//        $query->select()->from('user')->where('id>:id',[':id'=>2])->order('id desc');
//        $total  = $query->count();
//        $query->limit(5,5*(2-1));
//        $res = $query->queryAll();
//       $res = $query->select('*')->from('user')->where(['id'=>[3,4,5,6,7]])->queryAll();
        
//        $res = $query->select('a.*,b.name')
//            ->from('user as a')
//            ->leftJoin('time as b', 'a.id=b.uid')
//            ->where('a.id>=:id',[':id'=>5])
//            ->andWhere('a.id<=:lid',[':lid'=>10])
//            ->queryAll();
//        $res = $query->select('id')->from('user')->where('id>:id',[':id'=>5])->queryColumn();
//        $res = $query->select('count(1)')->from('user')->queryOne();
//        $res = $query->from('user')->where('name like :name',[':name'=>'%zjl%'])->queryAll();
//        $res = $query->select('id,name,age')->from('user')->where('age>=:age',[':age'=>18])->andWhere(['like','name','%zjl%'])->queryRow();
//        $res = $query->select('*')->from('user')->where('age>:age',[':age'=>18])->queryAll();
//        $res = $query->delete('user',['id'=>34]);
//        $res = $query->update('user', ['name'=>'zhangsanfeng3'], ['id'=>33]);
//        $res = $query->batchInsert('user', array('name', 'age'), array(
//            array('lisi',24),
//            array('wangwu',18),
//        ));
        //单条语句的插入
//        $res = $query->insert('user', ['name'=>'zhangjiulong','age'=>3]);
//        $res = $query->from('user')->where(['id'=>[4,5,6,7],'age'=>18])->queryAll();
//        $query->from('user')->where('id>:id',[':id'=>10]);
//        $query->from('user')->where(['like','name','%zjl%']);
//        p($query);
//        $res = $query->queryAll();
//        $res = $query->batchInsert('user', ['name','age'], [
//            ['name'=>'zjl1','age'=>12],
//            ['name'=>'zjl4','age'=>13],
//        ]);
        //修改
//        $res = $query->insert('user', ['age'=>15,'name'=>'zs']);
//        $res = $query->update('user', ['age'=>22],['or',['id'=>4],['id'=>5]]);
        echo FrameApp::$app->getConsumeTime();
//        $query->reset();
//        $query->from('user')->where(['id'=>[4,5,6,7],'age'=>'19']);
//        $res = $query->queryAll();
        p($res);
//        $res = $this->insert('zhangjiulong');
//        $res = $this->batch();
//        $res = $this->update();
//        $res = $this->delete();
//        $trans = FrameApp::$app->db->beginTransaction();
//        try {
//            $this->insert('zjl1');
//            $this->second();
////            throw new Exception('zjl1');
//            $this->insert('zjl2');
//            $trans->commit();
//        } catch (Exception $exc) {
//            echo $exc->getMessage();
//            $trans->rollback();
//        }
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
        return UserDao::delete(['in', 'id', [1, 2, 3]]);
    }

    public function update() {
        return UserDao::update(['age' => 19], 'id>:id', [':id' => 3]);
    }

    public function insert($name) {
        return UserDao::insert(['name' => $name]);
    }

    public function batch() {
        $res = UserDao::batchInsert(['name', 'age'], [
                    ['name' => 'zs', 'age' => 1],
                    ['name' => 'ls', 'age' => 2],
                    ['name' => 'ww', 'age' => 3],
        ]);
        return $res;
    }

}
