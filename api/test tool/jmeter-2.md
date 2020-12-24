[TOC]

**文档基于中文界面，Jmeter5.4版本，windows双击打开`jmeter.bat`**



# 创建线程组
### 1. 右键点击测试计划，选择进程组
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2eaa80b32f.png)

### 2. 右键线程组，添加HTTP请求
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2eb1cc0a68.png)
#### 2.1 添加HTTP头(可选)
用于http头添加参数或其他信息
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2ec0a63f79.png)
#### 2.2 添加结果监听器
该监听器用于监听请求结果，方便用户进行调试。同理可以根据自己需求添加一些图片的`数据汇总`监听器
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2eca7dbe1f.png)

# 配置HTTP请求
### 1. 配置请求的域名等参数
根据接口配置请求`协议`、`服务器`、`请求方式`、`请求路径`、`端口号`(可选)
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2ee5c5b18e.png)
图中服务器地址为${__machineIP()}是Jmeter提供的内置函数，表示**获取本地机器的IP地址**
- 注意：因为是本地配置所以可以使用Jmeter提供的内置函数，如果非本地IP，那么需要填具体的域名地址。Jmeter的内置函数具体可以查看工具栏上的`函数助手`，也可以查看对应文档[函数助手](http://www.jmeter.com.cn/2937.html)
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2eeba60ce8.png)

### 2. 配置请求参数
和postman类似，有多种参数添加方式；根据需要可以手动添加单独参数，也可以使用`消息体数据`，也可以使用文件上传
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2f55b33bd7.png)

> 注意：参数和消息体数据不能共存，你只能选择一个标签选项。文件上传无影响


# 运行线程组
完成上述配置，就可以点击`启动`来进行调试，切换到`结果树`，可以查看本次访问的具体请求参数和返回参数，以及http头部等其他信息
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2f623ccaed.png)

根据上述的配置，你可以创建多个线程组，然后统一进行测试
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2f6c0c7d67.png)