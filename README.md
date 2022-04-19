# MeHttpPush
本插件会在Typecho收到新评论时，按照配置HTTP推送Api进行推送，本质上就是GET请求  
支持Server酱,Bark,Telegram等使用HTTP的推送Api

## 如何使用
右上角绿色按钮下载zip，解压到`/typecho/usr/plugins`中，并把文件夹名字从`MeHttpPush-master`改为`MeHttpPush`  
然后到后台启动插件，设置  
`HTTP推送地址`一行一个，如果地址中包含`{}`，推送的消息将会替换在这个位置，否则就会拼在最后发送请求，举个例子
```
https://api.day.app/key/{}?sound=minuet
https://sc.ftqq.com/key.send?text=
https://api.telegram.org/botkey/sendMessage?chat_id=123&text=
```
