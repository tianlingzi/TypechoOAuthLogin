# TypechoOAuthLogin

typecho第三方OAuth登录插件

# 前言

插件前身：[tianlingzi/TeConnect](https://github.com/tianlingzi/TeConnect)，最后维护时间为2025年10月26日。

由于功能拓展以及后续开发需要，将插件改名为“TypechoOAuthLogin”。

## 一、功能介绍

**Typecho互联登录插件，目前已支持的第三方登录：QQ/微信/Github/Msn/Google/新浪微博/豆瓣/点点/淘宝网/百度/通用OAuth2.0/OIDC登录。**
后续会根据实际需要继续添加新的第三方接口，欢迎大家一起贡献。
如使用过程中遇到问题，可到这篇文章下留言，我会尽快解决。 <https://www.tianlingzi.top/archives/232/>

***

## 二、插件下载

<https://github.com/tianlingzi/TypechoOAuthLogin/releases>

***

## 三、安装步骤

1. 解压插件到`Plugins`目录；
2. 将文件名改为“TypechoOAuthLogin”；
3. 在后台启用插件，并配置插件参数（方法见：参数配置 - 配置示例）；
4. 在当前使用主题的适当位置添加`TypechoOAuthLogin_Plugin::show()`方法，代码：

```php
<?php TypechoOAuthLogin_Plugin::show(); ?>
```

1. 在第三方平台设置网站回调域，注意区分http、https（方法见：参数配置 - 配置示例）。
2. 如果您的主题开启了全站PJAX，需要把以下代码放入PJAX回调函数内：

```
/*PJAX时：来源页写入cookie*/
var exdate = new Date();
exdate.setDate(exdate.getDate() + 1);
document.cookie = "TypechoOAuthLogin_Referer=" + encodeURI(window.location.href) + "; expires=" + exdate.toGMTString() + "; path=/";
```

***

## 三、参数配置

### 配置示例

| 名称           | 类型          | 配置示例                                    | 网站回调域                                               |
| ------------ | ----------- | --------------------------------------- | --------------------------------------------------- |
| 腾讯QQ         | qq          | qq:APP\_KEY,APP\_SECRET,腾讯QQ            | <https://127.0.0.1/oauth_callback?type=qq>          |
| 微信           | Wechat      | qq:APP\_KEY,APP\_SECRET,微信              | <https://127.0.0.1/oauth_callback?type=Wechat>      |
| Github       | github      | github:APP\_KEY,APP\_SECRET,Github      | <https://127.0.0.1/oauth_callback?type=github>      |
| MSN          | msn         | msn:APP\_KEY,APP\_SECRET,MSN            | <https://127.0.0.1/oauth_callback?type=msn>         |
| Google       | google      | google:APP\_KEY,APP\_SECRET,Google      | <https://127.0.0.1/oauth_callback?type=google>      |
| 新浪微博         | sina        | sina:APP\_KEY,APP\_SECRET,新浪微博          | <https://127.0.0.1/oauth_callback?type=sina>        |
| 豆瓣           | douban      | douban:APP\_KEY,APP\_SECRET,豆瓣          | <https://127.0.0.1/oauth_callback?type=douban>      |
| 点点           | diandian    | diandian:APP\_KEY,APP\_SECRET,点点        | <https://127.0.0.1/oauth_callback?type=diandian>    |
| 淘宝网          | taobao      | taobao:APP\_KEY,APP\_SECRET,淘宝网         | <https://127.0.0.1/oauth_callback?type=taobao>      |
| 百度           | baidu       | baidu:APP\_KEY,APP\_SECRET,百度           | <https://127.0.0.1/oauth_callback?type=baidu>       |
| Custom Login | customlogin | customlogin:APP\_KEY,APP\_SECRET,Custom | <https://127.0.0.1/oauth_callback?type=customlogin> |

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

对于通用OAuth2.0/OIDC登录，需要在CustomloginSDK.class.php中设置自己的服务器OpenID Connect 发现文档URL。

### 2：网站回调域配置

您可以复制对应的`配置示例`，把`127.0.0.1`改成您的域名，填写到第三方开发平台的网站回调域设置中，即可完成配置！

以本博客`www.tianlingzi.top`,设置QQ登录，为例：
复制插件中给出的回调地址：`https://www.tianlingzi.top/oauth_callback?type=qq`

## 四、管理页面

在后台的“个人设置”页面中可以看到TypechoOAuthLogin设置，点击“管理第三方登录信息”即可进入。
