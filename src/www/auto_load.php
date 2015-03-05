<?php
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
        $classpath = apc_fetch("hulk_app:1425548963");
        if ($classpath) return $classpath;

        $classpath = getClassMapDef();
        apc_store("hulk_app:1425548963",$classpath); 
    }
    else if(function_exists("eaccelerator_get"))
    {
        $classpath = eaccelerator_get("hulk_app:1425548963");
        if ($classpath) return $classpath;

        $classpath = getClassMapDef();
        eaccelerator_put("hulk_app:1425548963",$classpath); 
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
        "HomeController"               => '/home/zhangjiulong/web/lon/src/app//controllers/HomeController.php',
        "TimeDao"                      => '/home/zhangjiulong/web/lon/src/app//daos/TimeDao.php',
        "TestModel"                    => '/home/zhangjiulong/web/lon/src/app//models/TestModel.php',
        "HomeConsole"                  => '/home/zhangjiulong/web/lon/src/app//task/HomeConsole.php',
        "ExceptionFrame"               => '/home/zhangjiulong/web/lon/src/frame//base/ExceptionFrame.php',
        "FrameApp"                     => '/home/zhangjiulong/web/lon/src/frame//base/FrameApp.php',
        "FrameDI"                      => '/home/zhangjiulong/web/lon/src/frame//base/FrameDI.php',
        "FrameObject"                  => '/home/zhangjiulong/web/lon/src/frame//base/FrameObject.php',
        "FrameConsoleApp"              => '/home/zhangjiulong/web/lon/src/frame//console/FrameConsoleApp.php',
        "FrameConsole"                 => '/home/zhangjiulong/web/lon/src/frame//console/FrameConsole.php',
        "FrameConsoleRequest"          => '/home/zhangjiulong/web/lon/src/frame//console/FrameConsoleRequest.php',
        "FrameController"              => '/home/zhangjiulong/web/lon/src/frame//web/FrameController.php',
        "FrameRequest"                 => '/home/zhangjiulong/web/lon/src/frame//web/FrameRequest.php',
        "FrameResponse"                => '/home/zhangjiulong/web/lon/src/frame//web/FrameResponse.php',
        "FrameWebApp"                  => '/home/zhangjiulong/web/lon/src/frame//web/FrameWebApp.php',
        "FrameDao"                     => '/home/zhangjiulong/web/lon/src/libs//db/FrameDao.php',
        "FrameDbExpression"            => '/home/zhangjiulong/web/lon/src/libs//db/FrameDbExpression.php',
        "FrameDB"                      => '/home/zhangjiulong/web/lon/src/libs//db/FrameDB.php',
        "FrameQuery"                   => '/home/zhangjiulong/web/lon/src/libs//db/FrameQuery.php',
        "FrameTransaction"             => '/home/zhangjiulong/web/lon/src/libs//db/FrameTransaction.php',
        "FrameLogDbTarget"             => '/home/zhangjiulong/web/lon/src/libs//log/FrameLogDbTarget.php',
        "FrameLogFileTarget"           => '/home/zhangjiulong/web/lon/src/libs//log/FrameLogFileTarget.php',
        "FrameLog"                     => '/home/zhangjiulong/web/lon/src/libs//log/FrameLog.php',
        "FrameLogTarget"               => '/home/zhangjiulong/web/lon/src/libs//log/FrameLogTarget.php',
    );
}
?>