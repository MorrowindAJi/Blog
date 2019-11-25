# 该方法是获取远程服务器上的日志文件等

客户端使用curl的方式获取 服务器上的特定接口地址，地址里面的数据经过验签等处理，将需要的文件夹目录下的文件返回给客户端

如果需要查看文件内容，可以将返回的内容前端方式打开：
~~~
var x=window.open();
x.document.open();
x.document.write('<div><pre style="white-space: pre-wrap;word-wrap: break-word;">'+内容+'</pre></div>');
x.document.close();
~~~

这样将打开一个新的窗口。

如果文件过大，可以使用`readFile()`来读取大文件，测试后发现6M的文件原来花费40S，优化后仅用2S。
目前还无法解决 传递数据到客户端的问题

## 2019年11月25日更新

+ 优化server和client的代码
+ 获取redis参数的方法
+ 删除redis、文件的方法

