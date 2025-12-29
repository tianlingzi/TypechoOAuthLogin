# TypechoOAuthLogin
typecho第三方OAuth登录插件
# 前言
插件前身：[tianlingzi/TeConnect][1]，最后维护时间为2025年10月26日。

由于功能拓展以及后续开发需要，将插件改名为“TypechoOAuthLogin”。

2025.12，关于1.0版更新，对数据表的管理逻辑进行了调整，插件禁用时不再主动删除数据表，以方便插件的再次启用。这对升级插件很重要，避免了要重新绑定第三方登录信息的麻烦。将对数据表的操作转移到了插件的配置页中：单独增加了清空数据表和删除数据表。如果你不再使用本插件，请先点击删除数据表，再禁用插件。

## 一、功能介绍
 **Typecho互联登录插件，目前已支持的第三方登录：QQ/微信/Github/Msn/Google/新浪微博/豆瓣/点点/淘宝网/百度。**
 后续会根据实际需要继续添加新的第三方接口，欢迎大家一起贡献。
 如使用过程中遇到问题，可到这篇文章下留言，我会尽快解决。 https://www.tianlingzi.top/archives/232/

----------

## 二、插件下载
https://github.com/tianlingzi/TypechoOAuthLogin/releases

----------

## 三、安装步骤
 1. 解压插件到`Plugins`目录；
 2. 将文件名改为“TypechoOAuthLogin”；
 3. 在后台启用插件，并配置插件参数（方法见：参数配置 - 配置示例）；
 4. 在当前使用主题的适当位置添加`TypechoOAuthLogin_Plugin::show()`方法，代码：
   ```php
<?php TypechoOAuthLogin_Plugin::show(); ?>
   ```
 5. 在第三方平台设置网站回调域，注意区分http、https（方法见：参数配置 - 配置示例）。
 6. 如果您的主题开启了全站PJAX，需要把以下代码放入PJAX回调函数内：

```
/*PJAX时：来源页写入cookie*/
var exdate = new Date();
exdate.setDate(exdate.getDate() + 1);
document.cookie = "TypechoOAuthLogin_Referer=" + encodeURI(window.location.href) + "; expires=" + exdate.toGMTString() + "; path=/";
```

----------

## 三、参数配置
### 配置示例

名称 | 类型 | 配置示例 | 网站回调域
-|-|-|-
腾讯QQ | qq | qq:APP_KEY,APP_SECRET,腾讯QQ | https://127.0.0.1/oauth_callback?type=qq
微信 | Wechat | qq:APP_KEY,APP_SECRET,微信 | https://127.0.0.1/oauth_callback?type=Wechat
Github | github | github:APP_KEY,APP_SECRET,Github | https://127.0.0.1/oauth_callback?type=github
MSN | msn | msn:APP_KEY,APP_SECRET,MSN | https://127.0.0.1/oauth_callback?type=msn
Google | google | google:APP_KEY,APP_SECRET,Google | https://127.0.0.1/oauth_callback?type=google
新浪微博 | sina | sina:APP_KEY,APP_SECRET,新浪微博 | https://127.0.0.1/oauth_callback?type=sina
豆瓣 | douban | douban:APP_KEY,APP_SECRET,豆瓣 | https://127.0.0.1/oauth_callback?type=douban
点点 | diandian | diandian:APP_KEY,APP_SECRET,点点 | https://127.0.0.1/oauth_callback?type=diandian
淘宝网 | taobao | taobao:APP_KEY,APP_SECRET,淘宝网 | https://127.0.0.1/oauth_callback?type=taobao
百度 | baidu | baidu:APP_KEY,APP_SECRET,百度 | https://127.0.0.1/oauth_callback?type=baidu

### 1：后台互联配置
具体格式为：`type:appid,appkey,title`，注释：
 - type：第三方登录帐号类型
 - appid：第三方开放平台申请的应用id
 - appkey：第三方开放平台申请的应用key
 - title：登录按钮的标题
在后台互联配置中，直接以文本形式填写，一行为一个帐号系统的参数；
为减少错误发生，您可以复制对应的`配置示例`，把`APP_KEY`和`APP_SECRET`改成您自己的参数就可以了！
例如：`qq:APP_KEY,APP_SECRET,腾讯QQ`
改成：`qq:123456789,47sa12f8s7df7sd877ji75s78sdfd,腾讯QQ`
粘贴到后台`互联配置`，即完成了腾讯QQ登录的配置，其他类型同理！

### 2：网站回调域配置
您可以复制对应的`配置示例`，把`127.0.0.1`改成您的域名，填写到第三方开发平台的网站回调域设置中，即可完成配置！

以本博客`www.tianlingzi.top`,设置QQ登录，为例：
复制插件中给出的回调地址：`https://www.tianlingzi.top/oauth_callback?type=qq`

## 四、管理页面
在后台的“个人设置”页面中可以看到TypechoOAuthLogin设置，点击“管理第三方登录信息”即可进入。

[1]: https://github.com/tianlingzi/TeConnect