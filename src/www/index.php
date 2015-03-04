<?php
require_once 'auto_load.php';

$config = require dirname(__DIR__) . '/../config/app.php';


try {
    $app = FrameApp::create($config)->run();
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
    } else {
        echo '<br />';
    }
}

?>
