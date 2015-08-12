#!/usr/bin/env python
#coding=utf8
import httplib
import threading
import Queue
import os
import hashlib
import subprocess
import json
#https://raw.githubusercontent.com/kowloon29320/hulk/master

#下载失败的文件
error_files = []
#新添加的文件
new_files = []
#修改的文件
diff_files = []


#框架url
frame_urls = [
    'src/frame/base/ExceptionFrame.php', 
    'src/frame/base/FrameApp.php',
    'src/frame/base/FrameDI.php',
    'src/frame/base/FrameObject.php',
    'src/frame/web/FrameController.php',
    'src/frame/web/FrameRequest.php',
    'src/frame/web/FrameResponse.php',
    'src/frame/web/FrameWebApp.php',
    'src/frame/console/FrameConsoleApp.php',
    'src/frame/console/FrameConsole.php',
    'src/frame/console/FrameConsoleRequest.php'
]

#组件库url                                                                                                                                                                                  
libs_urls = [ 
    'src/libs/db/FrameDbExpression.php',
    'src/libs/db/FrameDB.php',
    'src/libs/db/FrameQuery.php',
    'src/libs/db/FrameTransaction.php',
    'src/libs/log/FrameLogDbTarget.php',
    'src/libs/log/FrameLogEmailTarget.php',
    'src/libs/log/FrameLogFileTarget.php',
    'src/libs/log/FrameLog.php',
    'src/libs/log/FrameLogTarget.php',
]

#工具urls
utils_urls = [ 
    'src/libs/utils/Validator.php', 
    'src/libs/utils/ArrayUtil.php', 
    'src/libs/utils/MailUtil.php', 
    #'update.php', 
]

urls = frame_urls + libs_urls + utils_urls

q = Queue.Queue(0)
basepath = os.getcwd()

def get_url(url):
    global error_files,q,basepath
    uri = '/kowloon29320/hulk/master/'+url.strip('/');
    httpClient =None
    #https://raw.githubusercontent.com/kowloon29320/hulk/master
    try:
        httpClient = httplib.HTTPSConnection('raw.githubusercontent.com',443,timeout=10)
        httpClient.request('GET',uri);
        response = httpClient.getresponse()
        res = response.read()
        update_file(res,url)

        #print response.status
        #print response.reason
        #print res[20:50]
        print url+" done..."
    except Exception,e:
        q.put(url)
        #error_files.append(url) 
        #print url+":"
        #print e
    finally:
        if httpClient:
            httpClient.close()

def update_file(res,file):
    global basepath,new_files,diff_files
    file = file.lstrip('/')
    filename = basepath+'/'+file
    #print filename
    if os.path.exists(filename):
       with open(filename,'r') as f:
            filestr = f.read()
       old_md5 = hashlib.new('md5',filestr).hexdigest() 
       new_md5 = hashlib.new('md5',res).hexdigest()
       if old_md5 != new_md5:
            with open(filename,'w') as f:
                f.write(res)
            diff_files.append(file)
    else:
        new_files.append(file)
        dir = os.path.dirname(filename)
        try:
            subprocess.call('mkdir -p '+dir,shell=True)
        except:
            print 'mkdir %s fail' % dir
        with open(filename,'w') as f:
            f.write(res)

def worker():
    while True:
        item = q.get()
        get_url(item)
        q.task_done()

if __name__ == '__main__':
    print 'update start...'
    for i in range(len(urls)/2):
        t = threading.Thread(target=worker)
        t.setDaemon(True)
        t.start()
    for url in urls:
       q.put(url)
    q.join()
    msgs = {
        "new_files":new_files,
        "update_files":diff_files,
        "error_files":error_files
    }
    print "\nResult:"
    print json.dumps(msgs,indent=2)
    


