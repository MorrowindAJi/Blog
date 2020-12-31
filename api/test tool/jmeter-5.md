[TOC]

**本章主要介绍分布式测试的搭建。文档基于中文界面，Jmeter5.4版本，windows双击打开`jmeter.bat`**

- 本章主要说明数据库流程，其他流程可以自行测试
- 不需要的流程方法请禁用，避免压测时进行干扰

# 流程图
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-31/5fed3273d6fb4.png)

- 除开初始化，其他接口的参数都可以通过登录接口返回获取


# 数据库连接和获取参数


jmeter使用的是JAVA编写，所以数据库连接是jdbc方式
### 1. jar下载
首先需要添加jdbc的jar包，下载地址为[JDBC](https://dev.mysql.com/downloads/connector/j/),下载系统选择Platform Independent，包选择tar类型，根据本地JAVA版本进行下载
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-31/5fed34a0f4015.png)
下载完成在jmeter下添加对应jar包
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-31/5fed343c2dcf3.png)

### 2. 配置jdbc
在所在项目添加JDBC Connection Config
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-31/5fed352abaa71.png)
然后按下图配置
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-31/5fed35ce13dc7.png)
- 1.连接池名称，可以自定义，后续数据库查询需要
- 2.连接地址，格式为：`jdbc:mysql://host:端口/数据库名称` 除非你的数据库不是mysql
- 3.一般选择com.mysql.jdbc.Drive,除非你的数据库不是mysql
- 4.数据库的账号和密码

### 3. 读取数据库
在需要用到查询的地方添加JDBC Request
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-31/5fed369e507a1.png)
然后如图设置
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-31/5fed36f800a83.png)
- 1.使用的数据库名称，填写上一步的`连接池`名称即可
- 2.sql语句
- 3.自定义的变量名称，用于存放sql查出的字段，多个用西文逗号隔开

### 4. 动态参数
在需要用到数据库参数的地方添加`循环控制器`和`计数器`，其中`计数器`按如下设置：
- 1.开始下标默认为1
- 2.递增根据需要设置，这里设置为1，说明是逐条读取
- 3.引用名称可以自定义，这里设置为L
- 4.将需要数据库参数的请求放入循环器里
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-31/5fed37a696c77.png)

由于`3）`定义了变量名称为Operate，所以这时候参数请求的参数设置为`${__V(Operate_${L},)}`，该参数可以用函数助手生成。Operate表示我们要去的数据库参数，${L}标识第几个下标，每次循环都会取Operate列表里L下标的值，因为循环的递增设置为1，所以L的值为1、2、3...
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-31/5fed390296faf.png)
可以添加`调试取样器`来查看获取到的值
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-31/5fed39a592c8f.png)

# 并发测试单用户流程
- 根据需要写好要测试的流程，如下图，这里模拟不同用户进行登录+下单流程。其中登录JSON提取器提取返回的参数，并将参数透传到下单接口
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-31/5fed3a5b25975.png)

- 多用户请设置线程组的线程数和循环控制器里的循环次数
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-31/5fed3ae5e96ef.png)
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-31/5fed3afe9aae6.png)

每个线程表示一个用户， 每次循环表示用户进行了一次登录+下单

注意：假设你循环次数设置为`5`，线程数设置为`200`，那么一共执行了 `1000`条数据库数据，需要保证你的sql语句有1000条的查询结果


