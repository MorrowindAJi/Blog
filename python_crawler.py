#-*- coding: UTF-8 -*- 


import urllib.request
import re
# page = urllib.request.urlopen('https://tieba.baidu.com/p/1753935195')
# htmlcode = page.read()
# # print(htmlcode)

# pageFile = open('pageCode.txt','wb+')#以写的方式打开pageCode.txt
# pageFile.write(htmlcode)#写入
# pageFile.close()#开了记得关

#获取网页
def getUrl(url):
    page = urllib.request.urlopen(url)
    htmlcode = page.read()
    htmlcode = htmlcode.decode('utf-8')
    return htmlcode


def getUrlPic(url,reg = r'src="(.+?\.jpg)"'):
    # reg = r'src="(.+?\.jpg)" width'#正则表达式
    reg_img = re.compile(reg)#编译一下，运行更快
    imglist = reg_img.findall(getUrl(url))#进行匹配
    x = 0
    for img in imglist:
        urllib.request.urlretrieve(img, '%s.jpg' %x)
        x+=1

# url = input('请输入一个网址:\n')
url = 'https://www.cnblogs.com/Axi8/p/5757270.html'
print('------抓取中-------')
# reg = r'src="(.+?\.jpg)" pic_ext'
getUrlPic(url)
print('抓取完毕')