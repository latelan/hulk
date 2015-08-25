<?php

/**
 * Description of FrameTableScheme
 * 表结构
 * @author zhangjiulong
 */
class FrameTableScheme extends FrameObject{
    public $db;
    public $name;
    public $columns;
    public $metaDatas;
    public $primaryKey = [];
    
    
    public function __construct($tablename,  FrameDB $db,$config = array())
    {
        $this->name = $tablename;
        $this->db = $db;
        return parent::__construct($config);
    }
    
    public function init()
    {
        parent::init();
        $this->initColumns();
    }

    public function initColumns()
    {
        $sql = 'SHOW FULL COLUMNS FROM '.$this->db->quoteTableName($this->name);
        $columns = $this->db->createQuery($sql)->queryAll();
        $this->metaDatas = ArrayUtil::index($columns, 'Field');
        foreach ($columns as $column) {
            $name = $column['Field'];
            //如果是主键
            if(strpos($column['Key'], 'PRI')!==false){
                $this->primaryKey[] = $name;
            }
            //设置默认值
            if($column['Type'] == 'timestamp' && $column['Default']=='CURRENT_TIMESTAMP'){
                $value = new FrameDbExpression('CURRENT_TIMESTAMP');
            }else{
                $value = $column['Default'];
            }
            $this->columns[$name] = $value;
        }
    }
    
    //判断某个字段是否自增
    public function isAutoIncrement($field)
    {
        //判断是否为主键
        if(!in_array($field, $this->primaryKey)){
            return false;
        }
        $column = $this->metaDatas[$field];
        return stripos($column['Extra'], 'auto_increment')!==false;
    }

}
