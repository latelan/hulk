<?php

/**
 * Description of SplitBaseDao
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
 * $res = UserDao::instance(['splitkey'=>$splitkey])->insert(['name'=>'zhangsan']);
 * return : 自增id
 * 
 * 多条语句插入：
 * $res = UserDao::instance(['splitkey'=>$splitkey])->batchInsert(array('id','name'),array(
 *      array('16','lisi'),
 *      array('17','wangwu'),
 * ));
 * return: 影响的行数
 * 
 * 修改：
 * $res = UserDao::instance(['splitkey'=>$splitkey])->update(array(
 *      'name'=>'liuliu'
 * ),['id'=>17]);
 * return: 受影响的行数
 * 
 * 删除：
 * $res = UserDao::instance(['splitkey'=>$splitkey])->delete(['id'=>17]);
 * 
 * 查询一行记录
 * $res = UserDao::instance(['splitkey'=>$splitkey])->queryRow(1); //根据主键id查询
 * $res = UserDao::instance(['splitkey'=>$splitkey])->queryRow(['name'=>'zhangjiulong']);  //根据条件查询
 * return: 一维关联数组
 * ['id'=>1,'name'=>'zs','age'=>20]
 * 
 * 查询多行记录
 * $res = UserDao::instance(['splitkey'=>$splitkey])->queryAll(['id'=>[1,2,3]]);   //查询id in (1,2,3)
 * $res = UserDao::instance(['splitkey'=>$splitkey])->queryAll(['name'=>'zhangjiulong','age'=>4]); //查询年龄4岁，名字为zhangjiulong的用户
 * 
 * 复杂的查询请获取FrameQuery之后用query对象操作
 * $query = UserDao::find();
 * 
 * ~~~~~~
 * @author zhangjiulong
 */
abstract class SplitBaseDao extends FrameObject {
    /**
     * 分隔表名的key
     * @var string 
     */
    public $splitkey='';

    /**
     * 返回的是FrameQuery的对象实例
     * @param string $split_key 分表关键字
     * @param string $sql
     * @param FrameDb $db
     * @return FrameQuery
     */
    static public function find($split_key = '', $sql = null, $db = null)
    {
        return (new FrameQuery(['sql' => $sql, 'db' => $db]))->from(static::tableName($split_key) . ' as t');
    }

    /**
     * 执行一条插入语句
     * @param array $columns
     * @param boolean $return_id 是否要返回自增id，默认为true，false则返回影响的行数 
     * @return int 受影响的行数
     */
    public function insert($columns, $return_id = true)
    {
        $query = static::find();
        $num = $query->insert(static::tableName($this->splitkey), $columns);
        return $return_id ? $query->getLastInsertId() : $num;
    }

    /**
     * 执行多条语句的插入
     * @param array $columns
     * @param array $rows
     * @return int
     */
    public function batchInsert($columns, $rows)
    {
        return static::find()->batchInsert(static::tableName($this->splitkey), $columns, $rows);
    }

    /**
     * 执行数据更新
     * @param array $columns
     * @param string|array $condition
     * @param array $params
     * @return int
     */
    public function update($columns, $condition = '', $params = [])
    {
        return static::find()->update(static::tableName($this->splitkey), $columns, $condition, $params);
    }

    /**
     * 执行数据删除
     * @param string|array $condition
     * @param array $params
     * @return int
     */
    public function delete($condition = '', $params = [])
    {
        return static::find()->delete(static::tableName($this->splitkey), $condition, $params);
    }

    /**
     * 获取一行信息
     * @param int|array $param
     * @param array|string $select 要取出来的字段
     * @return array
     */
    public function queryRow($param, $select = '*')
    {
        $query = static::find($this->splitkey)->select($select);
        //根据主键查询 @TODO 只支持单主键查询
        if (is_numeric($param)) {
            $query->andWhere(static::primaryKey() . '=:id', [':id' => $param]);
        } elseif (is_array($param)) {
            $query->andWhere($param);
        }
        return $query->queryRow();
    }

    /**
     * 返回多行记录
     * @param array $param
     * @param array|string $select 要取出来的字段
     * @return array
     */
    public function queryAll(array $param, $select = '*')
    {
        $query = static::find($this->splitkey)->select($select)->where($param);
        return $query->queryAll();
    }

    /**
     * 取一列数据
     * @param string $select 要取的字段
     * @param array $param 条件
     * @return array
     */
    public function queryColumn($select, $param = [])
    {
        $query = static::find($this->splitkey)->select($select)->where($param);
        return $query->queryColumn();
    }

    /**
     * 返回当前Dao的表名
     */
    abstract static public function tableName($split_key = '');

    /**
     * 返回当前Dao的主键名
     * @return string
     */
    static public function primaryKey()
    {
        return 'id';
    }
}
