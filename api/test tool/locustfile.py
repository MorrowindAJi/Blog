#-*- coding: UTF-8 -*- 
from locust import HttpLocust, TaskSet

#pc常用
# 用户进入直播间
def findPermission(l):
    l.client.post("/index.php/api/live/findPermission",{"cid":"2256","token":"1d4f590ca3683c853f8cb5b5b8becaf01394ba09afe2f5ea08e3e5374e8cce0d","device_type":"iphone"})
# 用户进入会议室
def comeInMeet(l):
    l.client.post("/index.php/api/Meeting/comeInMeet",{"cid":"2255","token":"1d4f590ca3683c853f8cb5b5b8becaf01394ba09afe2f5ea08e3e5374e8cce0d","device_type":"iphone"})
#App常用
#获取直播间信息
def getChannelsList(l):
    l.client.post("/index.php/api/app/getChannelsList",{"token":"1d4f590ca3683c853f8cb5b5b8becaf01394ba09afe2f5ea08e3e5374e8cce0d","device_type":"iphone"})
#通用
#获取用户列表
def getAdminList(l):
    l.client.post("/index.php/api/live/getAdminList",{"cid":"2256","device_type":"iphone"})
#登陆操作
def login(l):
    l.client.post("/index.php/api/account/login", {"device_type":"web","token":"","company_id":"","target":"+86-13557788496","password":"123456","target_type":"1"})
    
#登出操作
# def logout(l):
#     l.client.post("/index.php/api/account/logout", {"device_type":"web","token":"f1c75cf110b8030f724b7e50a1fc68ceabd4d91086b0a599d01166ee84b4ab41","company_id":""})


#请求index
def index(l):
    l.client.get('/')

class UserBehavior(TaskSet):
    tasks = {index}

#执行登陆和登出
    def on_start(self):
        login(self)
        findPermission(self)
        comeInMeet(self)
        getChannelsList(self)
        getAdminList(self)

    # def on_stop(self):
    #     logout(self)


class WebsiteUser(HttpLocust):
    task_set = UserBehavior
    min_wait = 3000
    max_wait = 6000
    host="XXXXX"
