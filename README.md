# MeHttpPush
本插件会在Typecho收到新评论时，按照配置HTTP推送Api进行推送，本质上就是GET请求  
支持Server酱,Bark,Telegram等使用HTTP的推送Api  
2022-06-29 v2版本增加了reCAPTCHA v2的验证码验证和验证通过后自动表单自动提交的功能，完爆现有的任何验证插件

## 如何使用
右上角绿色按钮下载zip，解压到`/typecho/usr/plugins`中，并把文件夹名字从`MeHttpPush-master`改为`MeHttpPush`  
然后到后台启动插件，设置  
`HTTP推送地址`一行一个，如果地址中包含`{}`，推送的消息将会替换在这个位置，否则就会拼在最后发送请求，举个例子
```
https://api.day.app/key/{}?sound=minuet
https://sc.ftqq.com/key.send?text=
https://api.telegram.org/botkey/sendMessage?chat_id=123&text=
```

如果需要使用reCAPTCHAv2，需要配置那两个Key  
自动提交的form的id可以在提交评论按钮上右键，点击检查，然后往上找一个叫form的标签，默认是`comment-form`，不会找的可以先空着试试能不能用  
需要编辑主题的`comments.php`，并将以下内容加在需要显示验证按钮的位置  
```
<div style="display: inline-block;"><?php MeHttpPush_Plugin::auth(); ?></div>
```
管理员登录的情况下不会验证验证，所以可以将上面的按钮这样包起来，就只会在没有登录的时候显示了，通常主题会为游客单独写一个提交的from，可以自行找一下这个php标签  
```
<?php if(!$this->user->hasLogin()): ?>

<div style="display: inline-block;"><?php MeHttpPush_Plugin::auth(); ?></div>

<?php endif; ?>
```
然后将提交评论的按钮这样包起来，这样提交评论的按钮只有在管理员登录的时候显示了  
```
<?php if($this->user->hasLogin()): ?>

<button type="submit" class="submit">提交评论</button>

<?php endif; ?>
```
