<?php

//更新代码源
$domain    = 'https://raw.githubusercontent.com/kowloon29320/hulk/master';
//项目根目录
$base_path = __DIR__;

//框架url
$frame_urls = [
    '/src/frame/base/ExceptionFrame.php',
    '/src/frame/base/FrameApp.php',
    '/src/frame/base/FrameDI.php',
    '/src/frame/base/FrameObject.php',
    '/src/frame/web/FrameController.php',
    '/src/frame/web/FrameRequest.php',
    '/src/frame/web/FrameResponse.php',
    '/src/frame/web/FrameWebApp.php',
    '/src/frame/console/FrameConsoleApp.php',
    '/src/frame/console/FrameConsole.php',
    '/src/frame/console/FrameConsoleRequest.php',
];

//组件库url
$libs_urls = [
    '/src/libs/db/FrameDbExpression.php',
    '/src/libs/db/FrameDB.php',
    '/src/libs/db/FrameQuery.php',
    '/src/libs/db/FrameTransaction.php',
    '/src/libs/log/FrameLogDbTarget.php',
    '/src/libs/log/FrameLogEmailTarget.php',
    '/src/libs/log/FrameLogFileTarget.php',
    '/src/libs/log/FrameLog.php',
    '/src/libs/log/FrameLogTarget.php',
];

//工具url
$utils_urls = [
    '/src/libs/utils/Validator.php', //验证器类，必选
    '/src/libs/utils/ArrayUtil.php', //数组操作类，可选
    '/src/libs/utils/MailUtil.php', //数组操作类，可选
    '/update.php', //更新代码，可选
];

$all_urls = array_merge($frame_urls, $libs_urls, $utils_urls);
$urls     = array_map(function($url)use($domain) {
    return $domain . $url;
}, $all_urls);
print_r($urls);
//$result = [];
//foreach ($urls as $url) {
//    $result = array_merge($result, requestUrl([$url]));
//}
$result      = requestUrl($urls);
//var_dump($result);exit;
$new_files   = [];
$diff_files  = [];
$error_files = [];

foreach ($result as $uri => $content) {
    if (empty($content)) {
        $error_files[] = $uri;
        continue;
    }
    $filepath = $base_path . str_replace($domain, '', $uri);
    if (file_exists($filepath)) {
        $oldmd5 = md5(file_get_contents($filepath));
        $newmd5 = md5($content);

        if ($oldmd5 != $newmd5) {
            $diff_files[] = $filepath;
            file_put_contents($filepath, $content);
        }
    } else {
        $new_files[] = $filepath;
        file_put_contents($filepath, $content);
    }
}

print_r(['修改的文件' => $diff_files, '添加的文件' => $new_files, '异常文件' => $error_files]);
exit;

/**
 * 支持多线程获取网页
 *
 * @see http://cn.php.net/manual/en/function.curl-multi-exec.php#88453
 * @param Array/string $urls
 * @param Int $timeout
 * @return Array
 */
function requestUrl($urls)
{
    // 去重
    $urls         = array_unique($urls);
    if (!$urls)
        return [];
    $mh           = curl_multi_init();
    // 监听列表
    $listenerList = [];
    // 返回值
    $result       = [];
    // 总列队数
    $listNum      = 0;
    // 排队列表
    $multiList    = [];
    //最大队列数
    $multiExecNum = 5;
    // 返回结果
    $httpData     = [];
    foreach ($urls as $url) {
        // 创建一个curl对象
        $current = _create($url);

        if ($multiExecNum > 0 && $listNum >= $multiExecNum) {
            // 加入排队列表
            $multiList [] = $url;
        } else {
            // 列队数控制
            curl_multi_add_handle($mh, $current);
            $listenerList [$url] = $current;
            $listNum ++;
        }

        $result [$url]   = null;
        $httpData [$url] = null;
    }
    unset($current);
    $running = null;
    // 已完成数
    $doneNum = 0;
    do {
        while (($execrun = curl_multi_exec($mh, $running)) == CURLM_CALL_MULTI_PERFORM);
        if ($execrun != CURLM_OK) {
            break;
        }
        while (true == ($done = curl_multi_info_read($mh))) {
            foreach ($listenerList as $doneUrl => $listener) {
                if ($listener === $done ['handle']) {
                    // 获取内容
                    $httpData [$doneUrl] = getData(curl_multi_getcontent($done ['handle']), $done ['handle']);
//                    print_r($httpData);exit;
                    if ($httpData [$doneUrl] ['code'] != 200) {
                        $result [$doneUrl] = false;
                    } else {
                        // 返回内容
                        $result [$doneUrl] = $httpData [$doneUrl] ['data'];
                    }
                    curl_close($done ['handle']);
                    curl_multi_remove_handle($mh, $done ['handle']);
                    // 把监听列表里移除
                    unset($listenerList [$doneUrl], $listener);
                    $doneNum++;
                    // 如果还有排队列表，则继续加入
                    if ($multiList) {
                        // 获取列队中的一条URL
                        $currentUrl                 = array_shift($multiList);
                        // 创建CURL对象
                        $current                    = _create($currentUrl);
                        // 加入到列队
                        curl_multi_add_handle($mh, $current);
                        // 更新监听列队信息
                        $listenerList [$currentUrl] = $current;
                        unset($current);
                        // 更新列队数
                        $listNum ++;
                    }

                    break;
                }
            }
        }
        if ($doneNum >= $listNum)
            break;
    } while (true);
    // 关闭列队
    curl_multi_close($mh);
//    return $httpData;
    return $result;
}

function _create($url)
{
    $matches = parse_url($url);
    $ch      = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, true);
    // 抓取跳转后的页面
//    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
//    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
//    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 3000);
    if ($matches ['scheme'] == 'https') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }
//    if ($this->cookie) {
//        if (is_array($this->cookie)) {
//            curl_setopt($ch, CURLOPT_COOKIE, http_build_query($this->cookie, '', ';'));
//        } else {
//            curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
//        }
//    }
//    if ($this->referer) {
//        curl_setopt($ch, CURLOPT_REFERER, $this->referer);
//    } else {
//        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
//    }
//    if ($this->userAgent) {
//        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
//    } elseif (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
//        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER ['HTTP_USER_AGENT']);
//    } else {
//        curl_setopt($ch, CURLOPT_USERAGENT, "PHP/" . PHP_VERSION . " HttpClient/1.2.5");
//    }
//    foreach ($this->option as $k => $v) {
//        curl_setopt($ch, $k, $v);
//    }
//    if ($this->header) {
//        $header = [];
//        foreach ($this->header as $item) {
//            // 防止有重复的header
//            if (preg_match('#(^[^:]*):.*$#', $item, $m)) {
//                $header [$m [1]] = $item;
//            }
//        }
//        curl_setopt($ch, CURLOPT_HTTPHEADER, array_values($header));
//    }
    // 设置POST数据
//    if (isset($this->postData [$url])) {
//        print_r($this->postData);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postData [$url]);
//    }
    return $ch;
}

/**
 * 获取数据
 *
 * @param unknown $data
 * @param unknown $ch
 * @return mixed
 */
function getData($data, $ch)
{
    $headerSize           = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $result ['code']      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $result ['data']      = substr($data, $headerSize);
    $result ['rawHeader'] = substr($data, 0, $headerSize);
    $result ['header']    = explode("\r\n", $result ['rawHeader']);
    $result ['time']      = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    return $result;
}
?>

