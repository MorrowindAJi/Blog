感谢binwind8的帮助，弄好了滑动验证
他的git地址是：https://github.com/binwind8/tncode

在他的代码基础上，我微调了一下，
首先是 他提供的index.html里缺少tn_code.js的引入，不过这只是适用于前台的验证通过判断，不怎么影响

其次是我使用TP5框架，将下载下来的文件放到extend下，然后use。成功之后可能要根据实际开发情况修改获取图片信息的url

最后，在需要用到的地方引入对应的JS和CSS，然后在使用的地方添加对应的DIV

完成之后，只需要在后端进行session的验证就行了，记得刷新要充值对应的session的值