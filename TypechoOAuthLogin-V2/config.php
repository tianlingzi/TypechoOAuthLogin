<?php
define('URL_CALLBACK', Typecho_Common::url('/oauth_callback?type=', Typecho_Widget::Widget('Widget_Options')->index));
return array(
    'THINK_SDK_QQ'      => array(
        'NAME'      => '腾讯QQ',
        'CALLBACK'      => URL_CALLBACK . 'qq',
    ),
    'THINK_SDK_WECHAT'  => array(
        'NAME'      => '微信',
        'CALLBACK'      => URL_CALLBACK. 'wechat',
    ),
    'THINK_SDK_GITHUB'  => array(
        'NAME'      => 'Github',
        'CALLBACK'      => URL_CALLBACK . 'github',
    ),
    'THINK_SDK_MSN'     => array(
        'NAME'      => 'MSN',
        'CALLBACK'      => URL_CALLBACK . 'msn',
    ),
    'THINK_SDK_GOOGLE'  => array(
        'NAME'      => 'Google',
        'CALLBACK'      => URL_CALLBACK . 'google',
    ),
    'THINK_SDK_SINA'    => array(
        'NAME'      => '新浪微博',
        'CALLBACK'      => URL_CALLBACK . 'sina',
    ),
    'THINK_SDK_DOUBAN'  => array(
        'NAME'      => '豆瓣',
        'CALLBACK'      => URL_CALLBACK . 'douban',
    ),
    'THINK_SDK_DIANDIAN'=> array(
        'NAME'      => '点点',
        'CALLBACK'      => URL_CALLBACK . 'diandian',
    ),
    'THINK_SDK_TAOBAO'  => array(
        'NAME'      => '淘宝网',
        'CALLBACK'      => URL_CALLBACK . 'taobao',
    ),
    'THINK_SDK_BAIDU'   => array(
        'NAME'      => '百度',
        'CALLBACK'      => URL_CALLBACK . 'baidu',
    ),

);