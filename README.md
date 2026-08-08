# TypechoOAuthLogin

typecho第三方OAuth登录插件

# 版本介绍

随着目前typecho插件的不断发展，出了很多的美化插件，特别是登录页面的美化插件。
对于旧版的TypechoOAuthLogin插件，使用的是代码引用的方式，需要在主题的适当位置添加代码。
并且输出的格式一定，不能自定义。这就造成了对美化插件的冲突，导致整体的不协调。

针对以上问题，尝试构建新版的插件，提供多种输出和引用格式，用于适配各种主题。
如果你也能参与开发，欢迎一起贡献代码。

# 前言

插件前身：[tianlingzi/TeConnect](https://github.com/tianlingzi/TeConnect)，最后维护时间为2025年10月26日。

由于功能拓展以及后续开发需要，将插件改名为“TypechoOAuthLogin”。

# 功能介绍

**Typecho互联登录插件，目前已支持的第三方登录：QQ/微信/Github/Msn/Google/新浪微博/豆瓣/点点/淘宝网/百度/通用OAuth2.0/OIDC登录（Keycloak、Authentik、Authelia等）。**
后续会根据实际需要继续添加新的第三方接口，欢迎大家一起贡献。
如使用过程中遇到问题，可到这篇文章下留言，我会尽快解决。 <https://www.tianlingzi.top/archives/273/>

***

# 插件下载

<https://github.com/tianlingzi/TypechoOAuthLogin/releases>

***
# 安装步骤

1. 下载本仓库文件；
2. 将对应版本的插件文件夹“TypechoOAuthLogin-V*”复制到`Plugins`目录；
3. 将文件名改为“TypechoOAuthLogin”；
4. 在后台启用插件，并配置插件参数（方法见：参数配置 - 配置示例）；
5. 如果是从V1升级到V2，需要提前把代码引用的代码改成V2的代码引用，或者删除。否则系统会报错。

# V2版本使用说明
## 一、使用方法
### 第三方登录配置
在插件设置中，添加“第三方登录配置”：平台类型、显示名称（仅按钮样式生效）、Client ID、Client Secret、是否启用等参数。

### 添加自定义的通用OAuth2.0/OIDC登录配置
1. 进入SDK目录，其中的“TemplateSDK.class.php"文件复制一份，并重命名为：”{平台类型拼音}SDK.class.php"。如：AuthentikSDK.class.php;
2. 修改AuthentikSDK.class.php文件中的`displayName`和`OpenIDConfiguration`对应的参数;
3. 重新进入插件设置，即可自动识别出新的平台类型。
4. 同一类型平台只能有一条配置起作用。对于有多个自定义的第三方平台，需要设置不同的SDK以及平台名称。

### 自动插入说明
1. 由于插件是使用钩子自动插入，因此插件的加载顺序要在美化插件之前（即：先启用本插件，再启用美化插件）。

### 显示格式
1. 默认为按钮样式。
2. 圆形图标样式。仅平台logo。
3. 矩形图标样式。一般为平台logo+文字名称。
4. 圆形图标和矩形图标可自行更换，只需要按照平台名称：XXX。图标名称：icon_XXX.png、XXX.png，类似格式命名即可。

### 代码引用
#### 各个显示样式的代码引用如下：
圆形图标样式
```php
<?php TypechoOAuthLogin_Plugin::showImages(); ?>
```
矩形图标样式
```php
<?php TypechoOAuthLogin_Plugin::showRectImages(); ?>
```
按钮样式
```php
<?php TypechoOAuthLogin_Plugin::showButtons(); ?>
```

#### 代码引用情况下，只会输出最简单的样式，只有图标、文本，没有其他元素。方便用户根据实际情况进行美化。

# V1版本使用说明
## 一、使用方法
1. 在当前使用主题的适当位置添加`TypechoOAuthLogin_Plugin::show()`方法，代码：

```php
<?php TypechoOAuthLogin_Plugin::show(); ?>
```

2. 在第三方平台设置网站回调域，注意区分http、https（方法见：参数配置 - 配置示例）。
3. 如果您的主题开启了全站PJAX，需要把以下代码放入PJAX回调函数内：

```
/*PJAX时：来源页写入cookie*/
var exdate = new Date();
exdate.setDate(exdate.getDate() + 1);
document.cookie = "TypechoOAuthLogin_Referer=" + encodeURI(window.location.href) + "; expires=" + exdate.toGMTString() + "; path=/";
```

***

## 二、参数配置

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

## 三、管理页面

在后台的“个人设置”页面中可以看到TypechoOAuthLogin设置，点击“管理第三方登录信息”即可进入。
