<?php

/**
 * 计划任务列表
 */


//每三分钟执行
if (ifRun('*/3 * * * *')) {
    //runScript('index.php home/hello');    
}


//每分钟执行(放在最下面)
if (ifRun('* * * * *')) {
    for ($i = 1; $i <= 60; $i++) {
        //每秒执行一次
        if ($i % 1 == 0) {
            
        }
        //30秒执行一次
        if ($i % 30 == 0) {
            //runScript('index.php home/hello --sleep=30');
        }
    }
    //每分钟执行一次
    
}

// ##########################  function  ####################
function ifRun($cron)
{
    $min      = date('i'); //分
    $hour     = date('H'); //时
    $day      = date('d'); //日
    $mon      = date('m'); //月
    $week     = date('w'); //周
    $cron_arr = explode(" ", $cron);
    if (count($cron_arr) != 5) {
        echo "Error: $cron\n";
        return false;
    }
    list($a, $b, $c, $d, $e) = $cron_arr;
    $res_a = parseCron($a, $min);
    $res_b = parseCron($b, $hour);
    $res_c = parseCron($c, $day);
    $res_d = parseCron($d, $mon);
    $res_e = parseCron($e, $week);
    if ($res_e && $res_d && $res_c && $res_a && $res_b)
        return true;
}

function parseCron($a, $min)
{
    if ($a == '*' || $a == '*/1') {
        return true;
    } else if (preg_match('/\//', $a)) {
        list($xing, $runm) = explode("/", $a);
        if (0 == ($min % $runm))
            return true;
    }
    else if (preg_match('/^\d+$/', $a)) {
        if ($a == $min)
            return true;
    }
    else if (preg_match("/,/", $a)) {
        $a_arr = explode(",", $a);
        foreach ($a_arr as $stime) {
            if ($stime == $min)
                return true;
        }
    }
    else if (preg_match('/-/', $a)) {
        list($start, $end) = explode('-', $a);
        if (($min >= $start) && ($min <= $end))
            return true;
    }
    else {
        echo "unknow cron $a\n";
        return false;
    }
}

//执行任务
function runScript($file, $bg = true)
{
    $cmd = getCmd($file, $bg);
    if ($cmd === false) {
        return false;
    }
    system($cmd);
    echo date('Y-m-d H:i:s') . " " . $cmd . "\n";
}

//增加lockf机制，保证只执行任务一次
function runScriptOnce($file, $bg = true)
{
    $cmd = getCmd($file, $bg);
    if ($cmd === false) {
        return false;
    }
    $lock = md5($file) . '.lock';
    $cmd  = '/usr/bin/lockf -t 0 ' . $lock . ' ' . $cmd;
    system($cmd);
    echo date('Y-m-d H:i:s') . " " . $cmd . "\n";
}

//获取执行的命令
function getCmd($file, $bg = true)
{
    $runer   = array(
        'sh'  => '/bin/sh',
        'php' => '/usr/local/bin/php',
        'pl'  => '/usr/bin/perl',
        'py'  => '/usr/bin/python',
    );
    $runFile = explode(' ', $file)[0];
    if (!file_exists($runFile)) {
        echo date('Y-m-d H:i:s') . " " . $runFile . " file not found\n";
        return false;
    }
    $exer = $runer[end(explode('.', $runFile))];
    if (empty($exer)) {
        echo date('Y-m-d H:i:s') . " " . $file . " exer not found\n";
        return false;
    }
    $cmd = $exer . ' ' . $file . ($bg ? ' &' : ''); ///usr/local/bin/php index.php home/hello &
    return $cmd;
}
?>

