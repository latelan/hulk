#!/bin/sh
## App Env Init Script


DIRS=""
EXECUTES="tools/autoload_builder.sh"
#SUBSYS="nginx"
SUBSYS=""
APPS="web console"

#if test $# -lt 1
#then
#    echo Usage: env.init.sh who
#    echo    eg: env.init.sh cc
#    exit
#fi

USR=$1
ROOT=`pwd`

echo create application environment for $USR

if [ "$USR" == '' ] || [ "$USR" == 'prod' ]
then
    USR='prod'
fi

# link app config file
cd $ROOT/config

for SUBSYS in $SUBSYS
do
    if test -e $SUBSYS\_conf.php
    then 
        rm $SUBSYS\_conf.php
    fi
    if (test -s $SUBSYS/$SUBSYS\_conf.php.$USR)
    then
        ln -s $SUBSYS/$SUBSYS\_conf.php.$USR $SUBSYS\_conf.php
        echo link -s $SUBSYS/$SUBSYS\_conf.php ........... OK
    else
        echo link -s $SUBSYS/$SUBSYS\_conf.php  ........... Fail
    fi 
done

#应用配置
for APP in $APPS
do
    #删除软连
    if test -e "$APP".php
    then
        rm "$APP".php
    fi
    
    if (test -s common/"$APP".php.$USR)
    then
        ln -s common/"$APP".php.$USR "$APP".php
        echo link -s "$APP".php ........... OK
    else
        echo link -s "$APP".php ........... Fail
    fi
done

cd $ROOT
for dir in $DIRS
do
    if (test ! -d $dir)
    then
        mkdir -p $dir
        chmod -R 777 $dir
        echo mkdir $dir ................ OK
    fi
done

for execute in $EXECUTES
do
    sh $execute > /dev/null
    if test $? -eq 0
    then
        echo "sh $execute ................ OK"
    fi
done
