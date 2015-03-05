<?php
/**
 * 导入自动加载函数
 */
require_once 'auto_load.php';

/**
 * 获取web应用的配置数组
 */
$config = require dirname(__DIR__) . '/../config/app.php';


try {
    /**
     * 实例化一个web应用
     */
    $app = new FrameWebApp($config);
    
    /**
     * 运行应用
     */
    $app->run();
    
} catch (Exception $e) {
    header('content-type:text/html;charset=utf-8');
    echo $e->getMessage();
}

//打印并高亮函数
function p($target, $bool = true) {
    static $iii = 0;
    if ($iii == 0) {
        header('content-type:text/html;charset=utf-8');
    }
    echo '<pre>';
    $result = highlight_string("<?php\n" . var_export($target, true), true);
    echo preg_replace('/&lt;\\?php<br \\/>/', '', $result, 1);
    $iii++;
    if ($bool) {
        exit;
    }
}

?>
