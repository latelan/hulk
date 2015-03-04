<?php

define( "FILE_DEFAULT_DIRECTORY_CREATION_MODE", 0755 );
if($argc == 4 )
{
    $a = new AutoloadBuilder();
    $a->run($argv[1],$argv[2],$argv[3]);
}
else
{
    echo "Usage: /usr/local/bin/php build_includes <root_path> <outfile> <cache_key>\n";
}

class AutoloadBuilder
{/*{{{*/
    private static $_paths = array();
    private static $_skipFolders = array('web-inf', 'tmp', '.svn', 'sqls', 'logs', 'project');
    private static $_skipFiles= array();
    private static $_fileNameTemplate = array('php');
    
    public function getCodeTpl()
    {/*{{{*/
return '<?php
spl_autoload_register("myAutoLoad");
function myAutoLoad($classname)
{
    $classpath = getClassPath();
    if (isset($classpath[$classname]))
    {
        include($classpath[$classname]);
    }
}
function getClassPath()
{
    static $classpath=array();
    if (!empty($classpath)) return $classpath;
    if(function_exists("apc_fetch"))
    {
        $classpath = apc_fetch("___CACHEKEY___");
        if ($classpath) return $classpath;

        $classpath = getClassMapDef();
        apc_store("___CACHEKEY___",$classpath); 
    }
    else if(function_exists("eaccelerator_get"))
    {
        $classpath = eaccelerator_get("___CACHEKEY___");
        if ($classpath) return $classpath;

        $classpath = getClassMapDef();
        eaccelerator_put("___CACHEKEY___",$classpath); 
    }
    else
    {
        $classpath = getClassMapDef();
    }
    return $classpath;
}
function getClassMapDef()
{
    return array(
___DATA___
    );
}
?>';
    }/*}}}*/

    public function run($rootPath, $outfile, $cacheKey)
    {/*{{{*/
        $map = $exist = array();
        $filelist = array();
        foreach(explode(':', $rootPath) as $dir)
        {
            $filelist = $this->findFiles($dir, $filelist);
        }
        foreach ($filelist as $file)
        {
            $classes = $this->findClassFromAFile($file);
            if(!$classes) continue;
            
            foreach ($classes as $class)
            {
                if(isset($map[$class]))
                {
                    $exist[$class . '_' . md5($file)] = $file;
                    $exist[$class . '_' . md5($map[$class])] = $map[$class];
                }
                else 
                {
                    $map[$class] = $file;    
                }
            }
        }
        
        if($exist)
        {
            $arr['警告'] = '有相同的类名在不同的文件中，后者会覆盖前者';
            $exist = $arr + $exist;
            $msg = print_r($exist, true);
            $color = "[31m";  
            echo "\n" . chr(27) . "$color" . "$msg" . chr(27) . "[0m \n\n";  
        }
        
        self::generatorAssemblyFile($map, $outfile, $this->getCodeTpl(), $cacheKey);
        $msg = "$outfile done!";
        $color = "[32m";  
        echo "\n" . chr(27) . "$color" . "$msg" . chr(27) . "[0m \n\n";  
    }/*}}}*/

    static private function generatorAssemblyFile($classes,$outFile,$code,$cacheKey)
    {/*{{{*/
        $arrayCode = $char = '';;
        foreach ($classes as $key => $value)
        {
            $key = '"' . $key . '"';
            $key = str_pad($key, 30, ' ', STR_PAD_RIGHT);
            $arrayCode  .= "{$char}        $key => '{$value}',";
            $char = "\n";
        }
        $cacheKey = $cacheKey.":".time();
        $code = str_replace("___DATA___", $arrayCode, $code);
        $code = str_replace("___CACHEKEY___", $cacheKey, $code);
        file_put_contents($outFile, $code);
    }/*}}}*/
    
    static private function findClassFromAFile($file)
    {/*{{{*/
        $classes = array();
        $lines = file($file);
        foreach ($lines as $line)
        {
            if (preg_match("/^\s*class\s+(\S+)\s*/i", $line, $match))
            {
                $classes[] = trim($match[1], '{}');
            }
            if (preg_match("/^\s*abstract\s*class\s+(\S+)\s*/i", $line, $match))
            {
                $classes[] = trim($match[1], '{}');
            }
            if (preg_match("/^\s*interface\s+(\S+)\s*/i", $line, $match))
            {
                $classes[] = trim($match[1], '{}');
            }
        }
        return $classes;
    }/*}}}*/
    

    static private function findClassesByFile($file)
    {/*{{{*/
        //extends 的类无法include，此方法不可用by zlp
        //本来是为了解决 Class, class Pdo{ 这一类的问题的
        $cnt = count(get_declared_classes());
        include $file;
        $new = get_declared_classes();
        return array_slice($new, $cnt);
    }/*}}}*/

    static private function skipFiles($file)
    {/*{{{*/
        foreach(self::$_skipFiles as $fileRule)
        {
            if(preg_match("/$fileRule/i",$file))
                return ;
        }
        $path_parts = pathinfo($file);
        $suffix = $path_parts["extension"];
        
        return ( false == in_array($suffix, self::$_fileNameTemplate) || (1 == preg_match("/\.svn/", $file)) || (0 == preg_match("/.+\.php/", $file)) ); 
    }/*}}}*/

    static private function isSkipFolders($file)
    {/*{{{*/
        foreach (self::$_skipFolders as $skip)
        {
            $skip = quotemeta($skip);
            if (1 == preg_match("/$skip/", $file))
            {
                return true;
            }
        }
        return false;
    }/*}}}*/
    
    static private function findFiles($dirname, $filelist)
    {/*{{{*/
         $currentfilelist = scandir($dirname);
         if(is_array($currentfilelist ))
         foreach ($currentfilelist as $file)
         {
             if ($file == "." || $file == "..")
             {
                 continue;
             }

             if (is_dir($file) && self::isSkipFolders($file)) continue;
             
             $file = "$dirname/$file";
             if (is_dir($file))
             {
                 $filelist = self::findFiles($file, $filelist);
                 continue;
             }

             if (false == self::skipFiles($file))
             {
                $filelist[] = $file;
             }
         }
         return $filelist;
    }/*}}}*/
}/*}}}*/

?>
