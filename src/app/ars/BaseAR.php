<?php

/**
 * Description of BaseAR
 * ActiveRecord基类
 * @author zhangjiulong
 */
abstract class BaseAR extends FrameObject {

    use ValidateTrait;

    /**
     * 字段键值
     * @var array
     */
    private $_attributes;

    /**
     * 字段键值旧数据
     * @var array|null
     */
    private $_oldAttributes;

    /**
     * 分表关键词
     * @var string 
     */
    private $_splitKey = '';

    /**
     * 表结构对象列表
     * @var array 
     */
    protected static $_tableSchemes = [];

    /**
     * 返回是否为新记录
     * @return boolean
     */
    public function getIsNewRecord()
    {
        return $this->_oldAttributes === null;
    }

    /**
     * 设置分表关键词
     * @param string $value
     * @return \BaseAR
     */
    public function setSplitKey($value)
    {
        $this->_splitKey = $value;
        return $this;
    }

    /**
     * 返回分表关键词
     * @return string
     */
    public function getSplitKey()
    {
        return $this->_splitKey;
    }

    /**
     * 返回当前AR的表名
     */
    abstract static public function tableName($splitKey = '');

    /**
     * 返回db连接
     * @return FrameDB
     */
    static public function getDb()
    {
        return FrameDB::di('db');
    }

    /**
     * 根据分表关键词获取表结构
     * @param string $splitKey
     * @return FrameTableScheme
     */
    static public function tableScheme($splitKey = '')
    {
        $tablename = static::tableName($splitKey);
        if (!isset(self::$_tableSchemes[$tablename])) {
            self::$_tableSchemes[$tablename] = new FrameTableScheme($tablename, static::getDb());
        }
        return self::$_tableSchemes[$tablename];
    }

    /**
     * 返回表结构
     * @return FrameTableScheme
     */
    public function getTableScheme()
    {
        return static::tableScheme($this->getSplitKey());
    }

    /**
     * 返回主键名列表
     * @return array
     */
    public function primaryKey()
    {
        return $this->getTableScheme()->primaryKey;
    }

    /**
     * 返回主键值
     * @return array
     */
    public function getPrimaryKey()
    {
        $values = [];
        foreach ($this->primaryKey() as $name) {
            $values[$name] = isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
        }
        return $values;
    }

    /**
     * 返回旧的主键值
     * @return array
     */
    public function getOldPrimaryKey()
    {
        $values = [];
        foreach ($this->primaryKey() as $name) {
            $values[$name] = isset($this->_oldAttributes[$name]) ? $this->_oldAttributes[$name] : null;
        }
        return $values;
    }

    /**
     * 返回的是FrameQuery的对象实例
     * @param string $sql
     * @param FrameDb $db
     * @return FrameQuery
     */
    static public function find($splitKey = '', $sql = null)
    {
        return (new FrameQuery(['sql' => $sql, 'db' => static::getDb()]))->from(static::tableName($splitKey) . ' as t');
    }

    /**
     * 返回根据条件查询出来的对象
     * @param array|int $condition
     * @param string $splitKey
     * @return \static
     * @throws ExceptionFrame
     */
    static public function findRow($condition, $splitKey = '')
    {
        if (!is_array($condition)) {
            //当做主键查询来处理
            $pks = static::tableScheme($splitKey)->primaryKey;
            if (isset($pks[0])) {
                $pk        = $pks[0];
                $condition = [$pk => $condition];
            } else {
                throw new ExceptionFrame(get_called_class() . ' must cantain primary key');
            }
        }
        $row = static::find($splitKey)->where($condition)->queryRow();
        if (empty($row)) {
            return null;
        }
        $ar = new static(['splitKey' => $splitKey]);
        $ar->setAttributes($row);

        $ar->_oldAttributes = $ar->_attributes;
        return $ar;
    }

    /**
     * 查找多条AR记录
     * @param array $condition
     * @param string $splitKey
     * @return \static
     * @throws ExceptionFrame
     */
    static public function findAll($condition = [], $splitKey = '')
    {
        if ($condition && !ArrayUtil::is_assoc($condition)) {
            //当做主键查询来处理
            $pks = static::tableScheme($splitKey)->primaryKey;
            if (isset($pks[0])) {
                $pk        = $pks[0];
                $condition = [$pk => $condition];
            } else {
                throw new ExceptionFrame(get_called_class() . ' must cantain primary key');
            }
        }
        $rows = static::find($splitKey)->where($condition)->queryAll();
        if (empty($rows)) {
            return [];
        }
        foreach ($rows as $row) {
            $ar = new static(['splitKey' => $splitKey]);
            $ar->setAttributes($row);

            $ar->_oldAttributes = $ar->_attributes;
            $ars[]              = $ar;
        }
        return $ars;
    }

    /**
     * 插入|更新记录
     * @param boolean $validate 是否先验证
     * @return boolean
     */
    public function save($validate = true)
    {
        if ($validate) {
            $this->validate();
        }
        return $this->getIsNewRecord() ? $this->insert() : $this->update();
    }

    /**
     * 保存前钩子
     * @return boolean
     */
    protected function beforeSave()
    {
        return true;
    }

    /**
     * 保存后钩子
     * @param array $attributes 改动的字段键值
     */
    protected function afterSave($attributes)
    {
        
    }

    /**
     * 插入记录
     * @return boolean
     */
    public function insert()
    {
        if ($this->beforeSave()) {
            $attributes = $this->getAttributes();
            $query      = static::find();
            $num        = $query->insert(static::tableName($this->getSplitKey()), $attributes);
            if (!$num) {
                return false;
            }
            foreach ($this->getPrimaryKey() as $name) {
                if ($this->getAttribute($name) == null && $this->getTableScheme()->isAutoIncrement($name)) {
                    $this->setAttribute($name, $query->getLastInsertId());
                }
            }
            $this->afterSave($attributes);
            $this->_oldAttributes = $this->_attributes;
            return true;
        }
        return false;
    }

    /**
     * 修改记录
     * @return boolean
     */
    public function update()
    {
        if ($this->beforeSave()) {
            $attributes = [];
            foreach ($this->_attributes as $name => $value) {
                if ($this->hasAttribute($name) && (!array_key_exists($name, $this->_oldAttributes) || $value != $this->_oldAttributes[$name])) {
                    $attributes[$name] = $value;
                }
            }
            if (!empty($attributes)) {
                static::find()->update(static::tableName($this->getSplitKey()), $attributes, $this->getOldPrimaryKey());
            }
            $this->afterSave($attributes);
            $this->_oldAttributes = $this->_attributes;
            return true;
        }
        return false;
    }

    /**
     * 返回所有字段键值
     * @return array
     */
    public function getAttributes()
    {
        $attributes = $this->_attributes;
        foreach ($this->getTableScheme()->columns as $name => $value) {
            if (!array_key_exists($name, $attributes)) {
                $attributes[$name] = $value;
            }
        }
        return $attributes;
    }

    /**
     * 返回旧记录
     * @return array
     */
    public function getOldAttributes()
    {
        return $this->_oldAttributes === null ? [] : $this->_oldAttributes;
    }

    /**
     * 设置字段值
     * @param string $name 字段名
     * @param mixed $value 字段值
     * @return boolean
     */
    public function setAttribute($name, $value)
    {
        if ($this->hasAttribute($name)) {
            $this->_attributes[$name] = $value;
        } else {
            return false;
        }
        return true;
    }

    /**
     * 设置ar类属性
     * @param array $values
     */
    public function setAttributes($values)
    {
        foreach ($values as $name => $value) {
            if ($this->hasAttribute($name)) {
                $this->_attributes[$name] = $value;
            }
        }
    }

    /**
     * 返回字段值
     * @param string $name
     * @return mixed
     */
    public function getAttribute($name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        } elseif ($this->hasAttribute($name)) {
            return $this->getTableScheme()->columns[$name];
        }
        return null;
    }

    /**
     * 判断字段是否存在于表结构中
     * @param string $name
     * @return boolean
     */
    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->getTableScheme()->columns);
    }

    public function __set($name, $value)
    {
        if ($this->setAttribute($name, $value) === false) {
            parent::__set($name, $value);
        }
    }

    public function __get($name)
    {
        if ($this->hasAttribute($name)) {
            return $this->getAttribute($name);
        } else {
            return parent::__get($name);
        }
    }

}
