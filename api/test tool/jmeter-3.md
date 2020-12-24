[TOC]

**文档基于中文界面，Jmeter5.4版本，windows双击打开`jmeter.bat`**

# 全局标签
1、一旦线程数量多起来，对于使用者查看请求结果会很不方便，所以添加`结果树`全局标签，用于查看所有线程的调试请求
2、添加http默认配置，`配置元件`->`HTTP请求默认值`,当所有的接口测试的访问域名和端口都一样时，可以使用该元件，一旦服务器地址变更，只需要修改请求默认值即可。**优先线程配置，如果没有则获取http默认配置**
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2fd1fee4f1.png)

# CSV配置使用
只要设置好参数，该配置可以让我们每次请求都是不同用户，不同的请求。可以做到自动化，具体操作请看下面步骤：
### 1. 添加CSV配置
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2ff3fe0ba4.png)
### 2. 创建.csv后缀的文件(其实就是个表格)
- 文本形式如下：
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2ff9155b6c.png)
- 表格形式如下
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2ffbcf2f14.png)

### 3. 配置CSV
选择我们写好的csv文件，配置对应的参数
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2fffb78d56.png)
- 文件编码，选择`UTF-8`
- 变量名称，可以自定义，这里我使用了admin和password。多个变量用西文逗号分隔
- 忽略首行，默认为`false`，但有时候表格需要首行来对参数进行说明，所以可以根据表格的实际情况选择`false`或`true`
- 遇到文件结束符再次循环，默认为`true`，即如果你的csv只有2行数据，但线程有100个，那么这100个就会循环获取这2行的数据来请求
- 遇到文件结束符停止线程，默认为`false`,即如果你的csv只有2行数据，但线程有100个，当读取完csv后不会停止
- 线程共享，模式共有三个，一般选择`当前线程组`

```
所有线程：计划中所有线程，假如说有线程1到线程n (n>1)，线程1取了一次值后，线程2取值时，取到的是csv文件中的下一行，即与线程1取的不是同一行。

当前线程组：当前线程组，假设有线程组A、线程组B，A组内有线程A1到线程An，线程组B内有线程B1到线程Bn。取之情况是：线程A1取到了第1行，线程A2取第2行，现在B1取第1行，线程B2取第2行。

当前线程：当前线程。假设测试计划内有线程1到线程n (n>1)，则线程1取了第1行，线程2也取第1行。
```

### 4. 使用CSV
因为上一步设置了admin和password两个自定义参数，所以http请求就可以使用参数来进行动态加载，具体方式为${参数名}
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe301cb3b939.png)
查看请求结果可以看到参数值是动态变化的，和表格里的一致
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe3033953a1d.png)
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe303427b9a0.png)

# Ramp-Up时间使用
请看下图
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-23/5fe2fde2e2e43.png)
（1）每个线程可以看做是一个用户
（2）循环次数指每个用户的循环次数
（3）Ramp-Up时间指的是需要在这个时间内完成所有的请求
我们设线程数为`a`，Ramp-Up时间为`b`，循环次数为`c`
得出：
最终请求次数= `a*c`
每个请求间隔时间 `b/(a*c)`

这些参数可以方便我们配置每秒的并发量

# 关联测试

在很多测试中，我们都需要从上一个接口获取数据，然后传递到下一个接口，这时候的关联测试需要一个`提取器`来连接参数的传递。所有的提取器都在`后置处理器`标签上
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-24/5fe3f7ceb1044.png)

**那么我们开始使用吧**

### 1. 正则表达式提取器
正则表达式提取器可以让你用正则的方式精确获取参数值
这里模拟的返回数据为`JSON`形式
```
{"code":200,"msg":"success"}
```
创建正则表达式提取器后，进行如下设置:
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-24/5fe3fe5fedce3.png)
- 引用名称，即后续使用的变量名称，使用方式为${引用名称}
- 正则表达式，用于获取数据的正则表达式，这里要获取code后面的数字，所以是`"code":([0-9]*)`。如果要获取msg后面的数据，那么表达式为`"msg":"(.*)"`
- 模板，用于从找到的匹配项创建字符串的模板。这是一个带有特殊元素的任意字符串，用于引用正则表达式中的组。引用组的语法是：`$1$`引用组1，`$2$`引用组2，等等。`$0$`引用整个表达式匹配的内容。
- 匹配数字,指示要使用的匹配项。正则表达式可以匹配多次
- 缺省值，如果正则表达式不匹配，则引用变量将设置为默认值
具体文档[正则表达式提取器](https://jmeter.apache.org/usermanual/component_reference.html#postprocessors)

### 2. JSON提取器

如果返回的类型是`JSON`形式，也可以使用JSON提取器来获取数据
这里模拟的返回数据为`JSON`形式
```
{"code":200,"msg":"success"}
```
创建好JSON提取器进行如下设置：
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-24/5fe40063aa91b.png)
- Names of created Variables：下次传参引用的变量名 
- JSON Path expressions：JSON表达式,根据返回值一层一层的取值
- Match No：匹配哪个，可为空即默认第一个
- Default Value：未取到值的时候默认值

### 3. 边界提取器
如果返回的数据是不规则的加密串，那么就需要边界提取器来获取，
这里模拟的返回数据为`加密串`形式
```
D4sEAA8AA8AC+m1N22ncM9D9B0H4ZBtdLBs2iLKQ5CgPfW/LIpbCXhTfaonZXkP6veIE2gIzeEGYuZyZ84LwVzxpQ281Yum7EGyN1QRPmM8Pvke7zKvuYMyYonEV/PvFWwq9tLiCurxv72OfdgBVB93tmR2mZ+SPn2/A5IcOk4+zxv8k+B/YZBXVWX+yJGBtdK6w0YFLIuuVKN/wGsA6K8BAO63Gwg9wWVTcMNeTFhLqHS8rJlS72eWgcWdOZ/KNHscMYowuq1vi4a9Cm+rI1YaYqNMxVehdOm2AUriQuWmUvktNZt7XgXafUHYMl+x23hf6EKv1/+/52EROkDLKP6c575tIQfFN6Bi38DZQFik4I7mTjV8Xd39KvCAJ8YLsCyb1MT0MlJ/udvZr98HmknwUrN2xrHn2mnJ44Zt3OrM5jaNyVfXTbyh7Z7FXluvx+q5GNF5kshBdWlgtCi7KQueBSljwwwEQ/ZckFUajOmsdZMq0XmmXdtx0n5C5/gV2XpY/KgI8AA==
```
创建好边界模拟器后，进行如下设置：
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-24/5fe40b4c76d5a.png)
- 引用名称，即后续使用的变量名称，使用方式为${引用名称}
- 左边界，参数的左边界
- 右边界，参数的右边界
- 匹配数字，指示要使用的匹配项。边界可以匹配多次
- 缺省值，如果边界不匹配，则引用变量将设置为默认值

### 4. 使用提取器
通过上述三种方法，可以将上个接口的值传递到下一个接口，具体设置为：
![](https://developer-book.leiyangame.com/server/../Public/Uploads/2020-12-24/5fe40c10b70c8.png)
图中，使用的是`边界提取器`，获取到参数后，将值存在res，下个接口请求的时候使用`${res}`来获取值。
- 注意，两个接口要在同一个线程里才可以