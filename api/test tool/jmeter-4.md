[TOC]

**本章主要介绍分布式测试的搭建。文档基于中文界面，Jmeter5.4版本，windows双击打开`jmeter.bat`**

　　在使用Jmeter进行性能测试时，如果并发数比较大(比如最近项目需要支持1000并发)，单台电脑的配置(CPU和内存)可能无法支持，这时可以使用Jmeter提供的分布式测试的功能。

Jmeter分布式执行原理：
　　1. Jmeter分布式测试时，选择其中一台作为调度机(master)，其它机器做为执行机(slave)。
　　2. 执行时，master会把脚本发送到每台slave上，slave 拿到脚本后就开始执行，slave执行时不需要启动GUI，我理解它应该是通过命令行模式执行的。
　　3. 执行完成后，slave会把结果回传给master，master会收集所有slave的信息并汇总。
  
  由于只有一台机，所以测试的时候，master也是slave
  
# slave机的设置
1. 在slave机上安装jmeter
2. 如果是windows系统，需要添加环境变量
3. 完成之后进入`bin/jmeter-server.bat`启动，启动成功出现
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-24/5fe42ba512850.png)
图中箭头表示IP和端口，记住这两个，后续master配置上需要。端口默认1099，可以自定义，不要使用已被占用的端口

4. 多台slave机重复上述步骤

# master机的设置
1. 创建一个基本的线程，这里不做详细说明
2. 打开`bin/jmeter.properties`文件，找到`remote_hosts`配置，添加slave机的IP和端口，多台用西文逗号隔开
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-24/5fe438693063a.png)
3. 完成之后运行jmeter，可以看到新添加的slave机
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-24/5fe438a1bf26c.png)
4. 选择远程启动-->调试slave机IP，可以看到请求详情
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-24/5fe438ff0ad70.png)
如果Thread Name为slave机的，那么说明本次调试成功。此时slave机的jmeter-server.bat上可以看到刚刚的请求信息
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-24/5fe439423dbc9.png)

- 注意：slave机的jmeter-server.bat不可以关闭，不然master请求会报错

# slave机的自定义端口
进入slave机的`bin/jmeter.properties`文件，修改`server_port`和`server.rmi.localport`为端口号，重启`jmeter-server.bat`，看看端口是否生效，一旦生效就可以修改master机的`remote_hosts`配置

# 命令行测试
```
jmeter -n -t jmeter线程文件.jmx -r -l 生成的日志文件.jtl 
```

# 其他说明
　　1. 调度机(master)和执行机(slave)最好分开，由于master需要发送信息给slave并且会接收slave回传回来的测试数据，所以mater自身会有消耗，所以建议单独用一台机器作为mater。
　　2. 参数文件：如果使用csv进行参数化，那么需要把参数文件在每台slave上拷一份且路径需要设置成一样的。
　　3. 每台机器上安装的Jmeter版本和插件最好都一致，否则会出一些意外的问题。