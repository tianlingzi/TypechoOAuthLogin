<?php
//定义回调URL通用的URL
define('URL_CALLBACK', Typecho_Common::url('/oauth_callback?type=', Typecho_Widget::Widget('Widget_Options')->index));
return array(
    //腾讯QQ登录配置
    'THINK_SDK_QQ'      => array(
        'NAME'      => '腾讯QQ',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'qq',
    ),
    //微信配置
    'THINK_SDK_WECHAT'  => array(
        'NAME'      => '微信',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK. 'wechat',
    ),
    //Github配置
    'THINK_SDK_GITHUB'  => array(
        'NAME'      => 'Github',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'github',
    ),
    //MSN配置
    'THINK_SDK_MSN'     => array(
        'NAME'      => 'MSN',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'msn',
    ),
    //Google配置
    'THINK_SDK_GOOGLE'  => array(
        'NAME'      => 'Google',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'google',
    ),
    //新浪微博配置
    'THINK_SDK_SINA'    => array(
        'NAME'      => '新浪微博',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'sina',
    ),
     //豆瓣配置
    'THINK_SDK_DOUBAN'  => array(
        'NAME'      => '豆瓣',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'douban',
    ),
    //点点配置
    'THINK_SDK_DIANDIAN'=> array(
        'NAME'      => '点点',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'diandian',
    ),
    //淘宝网配置
    'THINK_SDK_TAOBAO'  => array(
        'NAME'      => '淘宝网',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'taobao',
    ),
    //百度配置
    'THINK_SDK_BAIDU'   => array(
        'NAME'      => '百度',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'baidu',
    ),
);
