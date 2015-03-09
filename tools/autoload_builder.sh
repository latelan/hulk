#!/bin/sh
## build system required files : className => FilePath

PHP=/usr/local/bin/php
APP_HOME=`pwd`


BASE_PATH="$APP_HOME/src/app/:$APP_HOME/src/frame/:$APP_HOME/src/libs/"

# create project autoload files
# php exe_php scan_filepath dest_auto_load_file cache_key

$PHP $APP_HOME/tools/find_classes.php "${BASE_PATH}" $APP_HOME/src/www/auto_load.php "hulk_app"

$PHP $APP_HOME/tools/find_classes.php "${BASE_PATH}" $APP_HOME/src/app/task/auto_load.php "hulk_task"