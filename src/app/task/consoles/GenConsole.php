<?php

/**
 * Description of GenConsole
 * 自动生成代码控制器
 * @author zhangjiulong
 */
class GenConsole extends BaseConsole{
    
    //自动生成ar  php index.php gen/ar --table=tablename
    public function arAction($table,$parentClass='BaseAR',$db='db')
    {
        $className = str_replace(' ','',ucwords(str_replace('_', ' ', $table))).'AR';
        $tablescheme = new FrameTableScheme($table, FrameDB::di($db));
        $columns = $tablescheme->columns;
        $properties = [];
        foreach ($columns as $name => $value) {
            $properties[] = ' * @property $'.$name;
        }
        $template = $this->getTemplate();
        $content = strtr($template, ['{table}'=>$table,'{className}'=>$className,'{parentClass}'=>$parentClass,'{properties}'=>  implode("\n", $properties)]);
        $arPath = FrameApp::getAlias('@app/ars');
        $filename = $arPath.'/'.$className.'.php';
        if(file_exists($filename)){
            echo $filename.' exist';
        }else{
            file_put_contents($filename, $content);
            echo $filename. ' generate suceessed';
        }
    }
    
    public function getTemplate()
    {
        return '<?php 
/**
 * Description of {className}
{properties}
 */
class {className} extends {parentClass}{

    public static function tableName($splitKey = "")
    {
        return $splitKey."{table}";
    }
}';
    }
}




