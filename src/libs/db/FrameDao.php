<?php

/**
 * Description of FrameDao
 * 数据库Dao基类
 * 
 * for example
 * ~~~~~~~
 * 有一个user表
 * CREATE TABLE `user` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL DEFAULT '',
    `age` tinyint(3) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * 
 * 创建一个UserDao类
 * class UserDao extends FrameDao{
        public static function tableName() {
            return 'user';
        }
    }
 * 
 * 单条语句插入
 * $res = UserDao::insert(['name'=>'zhangsan']);
 * return : 自增id
 * 
 * 多条语句插入：
 * $res = UserDao::batchInsert(array('id','name'),array(
 *      array('16','lisi'),
 *      array('17','wangwu'),
 * ));
 * return: 影响的行数
 * 
 * 修改：
 * $res = UserDao::update(array(
 *      'name'=>'liuliu'
 * ),'id=:id',[':id'=>17]);
 * return: 受影响的行数
 * 
 * 删除：
 * $res = UserDao::delete('id=:id',[':id'=>17]);
 * return: 返回受影响的行数
 * 
 * 查询一行记录
 * $res = UserDao::queryRow(1); //根据主键id查询
 * $res = UserDao::queryRow(['name'=>'zhangjiulong']);  //根据条件查询
 * return: 一维关联数组
 * ['id'=>1,'name'=>'zs','age'=>20]
 * 
 * 查询多行记录
 * $res = UserDao::queryAll(['id'=>[1,2,3]]);   //查询id in (1,2,3)
 * $res = UserDao::queryAll(['name'=>'zhangjiulong','age'=>4]); //查询年龄4岁，名字为zhangjiulong的用户
 * 
 * 复杂的查询请获取FrameQuery之后用query对象操作
 * $query = UserDao::find();
 * 
 * ~~~~~~
 * @author zhangjiulong
 */
abstract class FrameDao {
    
    /**
     * 返回的是FrameQuery的对象实例
     * @param string $sql
     * @param FrameDb $db
     * @return FrameQuery
     */
    static public function find($sql=null,$db=null) {
        return (new FrameQuery(['sql'=>$sql,'db'=>$db]))->from(static::tableName().' as t');
    }
    
    /**
     * 执行一条插入语句
     * @param array $columns
     * @return int 受影响的行数
     */
    static public function insert($columns) {
        $query = static::find();
        $query->insert(static::tableName(), $columns);
        return $query->getLastInsertId();
    }
    
    /**
     * 执行多条语句的插入
     * @param array $columns
     * @param array $rows
     * @return int
     */
    static public function batchInsert($columns, $rows) {
        return static::find()->batchInsert(static::tableName(), $columns, $rows);
    }
    
    /**
     * 执行数据更新
     * @param array $columns
     * @param string|array $condition
     * @param array $params
     * @return int
     */
    static public function update($columns, $condition='', $params=[]) {
        return static::find()->update(static::tableName(), $columns, $condition, $params);
    }
    
    
    /**
     * 执行数据删除
     * @param string|array $condition
     * @param array $params
     * @return int
     */
    static public function delete($condition='', $params=[]) {
        return static::find()->delete(static::tableName(), $condition, $params);
    }
    
    /**
     * 获取一行信息
     * @param int|array $param
     * @return array
     */
    static public function queryRow($param) {
        $query = static::find();
        //根据主键查询 @TODO 只支持单主键查询
        if(is_numeric($param)){
            $query->andWhere(static::primaryKey().'=:id',[':id'=>$param]);
        }elseif(is_array($param)){
            foreach ($param as $key => $value) {
                if(is_array($value)){
                    $query->andWhere(['in',$key,$value]);
                }else{
                    $query->andWhere("$key=:$key", [":$key"=>$value]);
                }
            }
        }
        return $query->queryRow();
    }
    
    /**
     * 返回多行记录
     * @param array $param
     * @return array
     */
    static public function queryAll(array $param) {
        $query = static::find();
        foreach ($param as $key => $value) {
            if(is_array($value)){
                $query->andWhere(['in',$key,$value]);
            }else{
                $query->andWhere("$key=:$key", [":$key"=>$value]);
            }
        }
        return $query->queryAll();
    }
    
    /**
     * 返回当前Dao的表名
     */
    abstract static public function tableName();
    
    /**
     * 返回当前Dao的主键名
     * @return string
     */
    static public function primaryKey() {
        return 'id';
    }
}
