<?php

/**
 * Description of FrameDao
 * 数据库操作基类
 * @author zhangjiulong
 */
abstract class FrameDao extends FrameObject{
    
    static public function find($sql=null,$db=null) {
        return (new FrameQuery(['sql'=>$sql,'db'=>$db]))->from(static::tableName().' as t');
    }
    
    static public function insert($columns) {
        return static::find()->insert(static::tableName(), $columns);
    }
    
    static public function batchInsert($columns, $rows) {
        return static::find()->batchInsert(static::tableName(), $columns, $rows);
    }
    
    static public function update($columns, $condition='', $params=[]) {
        return static::find()->update(static::tableName(), $columns, $condition, $params);
    }
    
    static public function delete($condition='', $params=[]) {
        return static::find()->delete(static::tableName(), $condition, $params);
    }
    
    /**
     * 获取一行信息
     * @param type $param
     * @return type
     */
    static public function getRow($param) {
        $query = static::find()->from(static::tableName());
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
//        p($query->params);
        return $query->queryRow();
    }
    
    static public function getAll(array $param) {
        $query = static::find()->from(static::tableName());
        foreach ($param as $key => $value) {
            if(is_array($value)){
                $query->andWhere(['in',$key,$value]);
            }else{
                $query->andWhere("$key=:$key", [":$key"=>$value]);
            }
        }
        return $query->queryAll();
    }
    
    abstract static public function tableName();
    
    static public function primaryKey() {
        return 'id';
    }
}
