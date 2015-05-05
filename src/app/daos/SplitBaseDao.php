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
 * $res = UserDao::load()->insert(['name'=>'zhangsan']);
 * return : 自增id
 * 
 * 多条语句插入：
 * $res = UserDao::load()->batchInsert(array('id','name'),array(
 *      array('16','lisi'),
 *      array('17','wangwu'),
 * ));
 * return: 影响的行数
 * 
 * 修改：
 * $res = UserDao::load()->update(array(
 *      'name'=>'liuliu'
 * ),['id'=>17]);
 * return: 受影响的行数
 * 
 * 删除：
 * $res = UserDao::load()->delete(['id'=>17]);
 * 
 * 查询一行记录
 * $res = UserDao::load()->queryRow(1); //根据主键id查询
 * $res = UserDao::load()->queryRow(['name'=>'zhangjiulong']);  //根据条件查询
 * return: 一维关联数组
 * ['id'=>1,'name'=>'zs','age'=>20]
 * 
 * 查询多行记录
 * $res = UserDao::load()->queryAll(['id'=>[1,2,3]]);   //查询id in (1,2,3)
 * $res = UserDao::load()->queryAll(['name'=>'zhangjiulong','age'=>4]); //查询年龄4岁，名字为zhangjiulong的用户
 * 
 * 复杂的查询请获取FrameQuery之后用query对象操作
 * $query = UserDao::find();
 * 
 * ~~~~~~
 * @author zhangjiulong
 */
abstract class SplitBaseDao extends FrameObject {
    /**
     * @var FrameQuery对象 
     */
    private $_query;

    public function getQuery()
    {
        return $this->_query;
    }

    public function setQuery(FrameQuery $query)
    {
        $this->_query = $query;
    }

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
     * 返回dao的单例,主要做增删改查操作
     * @param string $split_key 分表关键字
     * @param string $sql
     * @param FrameDB $db
     * @return SplitBaseDao  返回BaseDao的子类集合
     */
    static public function load($split_key = '', $sql = null, $db = null)
    {
        $query = (new FrameQuery(['sql' => $sql, 'db' => $db]))->from(static::tableName($split_key));
        $dao   = static::instance(['query' => $query]);
        return $dao;
    }

    /**
     * 执行一条插入语句
     * @param array $columns
     * @param boolean $return_id 是否要返回自增id，默认为true，false则返回影响的行数 
     * @return int 受影响的行数
     */
    public function insert($columns, $return_id = true)
    {
        $num = $this->getQuery()->insert($this->getQuery()->getFrom(), $columns);
        return $return_id ? $this->getQuery()->getLastInsertId() : $num;
    }

    /**
     * 执行多条语句的插入
     * @param array $columns
     * @param array $rows
     * @return int
     */
    public function batchInsert($columns, $rows)
    {
        return $this->getQuery()->batchInsert($this->getQuery()->getFrom(), $columns, $rows);
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
        return $this->getQuery()->update($this->getQuery()->getFrom(), $columns, $condition, $params);
    }

    /**
     * 执行数据删除
     * @param string|array $condition
     * @param array $params
     * @return int
     */
    public function delete($condition = '', $params = [])
    {
        return $this->getQuery()->delete($this->getQuery()->getFrom(), $condition, $params);
    }

    /**
     * 获取一行信息
     * @param int|array $param
     * @param array|string $select 要取出来的字段
     * @return array
     */
    public function queryRow($param, $select = '*')
    {
        $query = $this->getQuery()->select($select);
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
        $query = $this->getQuery()->select($select)->where($param);
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
        $query = $this->getQuery()->select($select)->where($param);
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