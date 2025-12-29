<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}/**
 * Typecho OAuth登录插件，已支持的第三方登录：QQ/微信/Github/Msn/Google/新浪微博/豆瓣/点点/淘宝网/百度
 *
 * @package TypechoOAuthLogin
 * @author tianlingzi
 * @version 1.0
 * @link https://www.tianlingzi.top
 *
 */
class TypechoOAuthLogin_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        $info = self::installDb();

        //添加oauth路由
        Helper::addRoute('oauth', '/oauth', 'TypechoOAuthLogin_Widget', 'oauth');
        Helper::addRoute('oauth_callback', '/oauth_callback', 'TypechoOAuthLogin_Widget', 'callback');
        // 新增：管理页与开关路由
        Helper::addRoute('connect_manage', '/connect/manage', 'TypechoOAuthLogin_Widget', 'manage');
        Helper::addRoute('connect_toggle', '/connect/toggle', 'TypechoOAuthLogin_Widget', 'toggle');
        // 新增：清除和删除数据表路由
        Helper::addRoute('connect_clear_table', '/connect/clear-table', 'TypechoOAuthLogin_Widget', 'clearTable');
        Helper::addRoute('connect_remove_table', '/connect/remove-table', 'TypechoOAuthLogin_Widget', 'removeTable');

        return _t($info);
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeRoute('oauth');
        Helper::removeRoute('oauth_callback');
        // 新增：移除管理页与开关路由
        Helper::removeRoute('connect_manage');
        Helper::removeRoute('connect_toggle');
        // 新增：移除清除和删除数据表路由
        Helper::removeRoute('connect_clear_table');
        Helper::removeRoute('connect_remove_table');
        return _t('插件已禁用，数据表已保留');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $config = require_once 'config.php';
        $text = $html ='';
        $text.= "互联配置示例 | 网站回调域 | 平台名称"."\r\n";
        $text.= "-|-|-"."\r\n";
        $num = 0;
        foreach ($config as $k => $v) {
            $num++;
            $type = strtolower(substr($k, 10));
            $text.= $type.':APP_KEY,APP_SECRET,'.$v['NAME'].' | '.$v['CALLBACK'].' | '.$v['NAME']."\r\n";
        }
        $html = Markdown::convert($text);

        //互联配置
        $connect = new Typecho_Widget_Helper_Form_Element_Textarea('connect', null, null, _t('互联配置'), _t('文本形式，一行一个账号系统配置，目前共支持'.$num.'种第三方登录！<br/>
                您可以复制对应的互联配置示例，把<strong class="warning">APP_KEY</strong>和<strong class="warning">APP_SECRET</strong>改成您申请的参数，粘贴到上方配置框。<br/>
                最后，复制对应的网站回调域，粘贴到第三方开发平台的网站回调域设置中。'.$html));
        $form->addInput($connect);

        //强制绑定
        $custom = new Typecho_Widget_Helper_Form_Element_Radio('custom', array(1=>_t('是'),0=>'否'), 1, _t('是否需要完善资料'), _t('用户使用社会化登录后，是否需要完善昵称、邮箱等信息；选择不需要完善资料则直接使用获取到的昵称。'));
        $form->addInput($custom);
        
        //添加清除和删除按钮，使用HTML直接输出
        echo '<div class="typecho-option">';
        echo '<div class="typecho-control-group">';
        echo '<div class="description">' . _t('警告：此操作将删除oauth_user表中的所有数据并重建表结构，所有用户绑定的第三方登录信息将丢失，但插件仍可继续使用！请谨慎操作。') . '</div>';
        echo '<div class="typecho-control">';
        $clearUrl = Typecho_Common::url('/connect/clear-table', Typecho_Widget::Widget('Widget_Options')->index);
        echo '<a href="' . $clearUrl . '" class="typecho-primary" onclick="return confirm(\'确定要清除数据表数据吗？此操作不可恢复！\');">' . _t('清除数据表') . '</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="typecho-option">';
        echo '<div class="typecho-control-group">';
        echo '<div class="description">' . _t('警告：此操作将直接删除oauth_user数据表，所有用户绑定的第三方登录信息将丢失，且插件后续无法正常使用！请谨慎操作。') . '</div>';
        echo '<div class="typecho-control">';
        $removeUrl = Typecho_Common::url('/connect/remove-table', Typecho_Widget::Widget('Widget_Options')->index);
        echo '<a href="' . $removeUrl . '" class="typecho-danger" onclick="return confirm(\'确定要删除数据表吗？此操作不可恢复，且插件将无法继续使用！\');">' . _t('删除数据表') . '</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * 个人用户的配置面板（精简版）
     *
     * 仅显示标题与按钮链接到管理页面，不添加任何表单输入，隐藏保存按钮。
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // 使用表单元素以兼容Typecho渲染流程，避免null->value()报错
        $btnHtml = '<a class="typecho-button primary teconnect-manage-btn" href="/connect/manage" target="_blank">管理第三方登录信息</a>';
        $open = new Typecho_Widget_Helper_Form_Element_Text(
            'open_manage',
            null,
            null,
            '',
            _t($btnHtml)
        );
        // 隐藏文本输入框本体，仅展示描述中的按钮
        $open->input->setAttribute('style', 'display:none');
        // 不再隐藏label，保留空标题以保证描述内容可见
        $form->addInput($open);
        // 隐藏保存按钮，保持极简UI + 按钮美化样式
        echo '<style>
            .typecho-option .submit{display:none!important;}
            .teconnect-manage-btn{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(90deg,#1677ff,#69adff);border:0;color:#fff;padding:8px 14px;border-radius:8px;box-shadow:0 6px 12px rgba(22,119,255,.25);transition:transform .15s ease, box-shadow .15s ease;text-decoration:none;}
            .teconnect-manage-btn:hover{transform:translateY(-1px);box-shadow:0 10px 16px rgba(22,119,255,.35);} 
            .teconnect-manage-btn:active{transform:translateY(0);} 
        </style>';
    }

    /**
     * 安装数据库
     */
    public static function installDb()
    {
        try {
            return self::addTable();
        } catch (Typecho_Db_Exception $e) {
            if ('42S01' == $e->getCode()) {
                $msg = '数据表oauth_user已存在!';
                return $msg;
            }
        }
    }

    //添加数据表
    public static function addTable()
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        if ("Pdo_Mysql" === $db->getAdapterName() || "Mysql" === $db->getAdapterName()) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}oauth_user` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `uid` int(10) unsigned  NULL DEFAULT '0' COMMENT '用户ID',
                  `uuid` int(10) unsigned  NULL DEFAULT '0',
                  `type` char(32)  NULL DEFAULT '0',
                  `openid` char(50) NULL DEFAULT '0',
                  `access_token` text NULL COMMENT '用户对应access_token',
                  `expires_in` int(10) unsigned NULL DEFAULT '0',
                  `datetime` timestamp  NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后登录',
                  `name` varchar(100) NULL DEFAULT '0',
                  `nickname` varchar(100) NULL DEFAULT '0',
                  `gender` tinyint(1) unsigned NULL DEFAULT '0' COMMENT '性别0未知,1男,2女',
                  `head_img` varchar(255) NULL DEFAULT '0' COMMENT '头像',
                  `refresh_token` text NULL COMMENT '刷新有效期token',
                  PRIMARY KEY (`id`),
                  KEY `uuid` (`uuid`),
                  KEY `uid` (`uid`),
                  KEY `type` (`type`),
                  KEY `openid` (`openid`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=1";
            $db->query($sql);
        } else {
            throw new Typecho_Plugin_Exception(_t('对不起, 本插件仅支持MySQL数据库。'));
        }
        return "数据表oauth_user安装成功！";
    }

    //清除数据表（删除数据+重建表）
    public static function clearTable()
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        try {
            //先删除表
            $db->query("DROP TABLE IF EXISTS `" . $prefix . "oauth_user`", Typecho_Db::WRITE);
            //再重建表
            if ("Pdo_Mysql" === $db->getAdapterName() || "Mysql" === $db->getAdapterName()) {
                $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}oauth_user` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `uid` int(10) unsigned  NULL DEFAULT '0' COMMENT '用户ID',
                      `uuid` int(10) unsigned  NULL DEFAULT '0',
                      `type` char(32)  NULL DEFAULT '0',
                      `openid` char(50) NULL DEFAULT '0',
                      `access_token` text NULL COMMENT '用户对应access_token',
                      `expires_in` int(10) unsigned NULL DEFAULT '0',
                      `datetime` timestamp  NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后登录',
                      `name` varchar(100) NULL DEFAULT '0',
                      `nickname` varchar(100) NULL DEFAULT '0',
                      `gender` tinyint(1) unsigned NULL DEFAULT '0' COMMENT '性别0未知,1男,2女',
                      `head_img` varchar(255) NULL DEFAULT '0' COMMENT '头像',
                      `refresh_token` text NULL COMMENT '刷新有效期token',
                      PRIMARY KEY (`id`),
                      KEY `uuid` (`uuid`),
                      KEY `uid` (`uid`),
                      KEY `type` (`type`),
                      KEY `openid` (`openid`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=1";
                $db->query($sql);
                return "清除oauth_user表数据成功！";
            } else {
                return "对不起, 本插件仅支持MySQL数据库。";
            }
        } catch (Typecho_Exception $e) {
            return "清除oauth_user表数据失败！";
        }
    }
    
    //删除数据表
    public static function removeTable()
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        try {
            $db->query("DROP TABLE `" . $prefix . "oauth_user`", Typecho_Db::WRITE);
        } catch (Typecho_Exception $e) {
            return "删除oauth_user表失败！";
        }
        return "删除oauth_user表成功！";
    }

    //在前端调用显示登录按钮
    public static function show($text = false)
    {
        if ($text) {
            //文本样式
            $format= '<a href="{url}" title="{title}">{title}</a>';
        } else {
            //登录按钮样式
            $format= '<a href="{url}"><img src="/usr/plugins/TypechoOAuthLogin/login_ico/{type}.png" alt="{type}-{title}" style="margin-top: 0.8em;"></a>';
        }

        $list = self::options();
        if (empty($list)) {
            return '';
        }
        $html = '';
        foreach ($list as $type=>$v) {
            $url = Typecho_Common::url('/oauth?type='.$type, Typecho_Widget::Widget('Widget_Options')->index);
            $html .= str_replace(
                array('{type}','{title}','{url}'),
                array($type,$v['title'],$url),
                $format
            );
        }
        echo $html;
    }

    //读取插件配置，返回数组
    public static function options($type='')
    {
        static $options = array();
        if (empty($options)) {
            $connect = Typecho_Widget::Widget('Widget_Options')->plugin('TypechoOAuthLogin')->connect;
            $connect = preg_split('/[;\r\n]+/', trim($connect, ",;\r\n"));
            foreach ($connect as $v) {
                $v = explode(':', $v);
                if (isset($v[1])) {
                    $tmp = explode(',', $v[1]);
                }
                if (isset($tmp[1])) {
                    $options[strtolower($v[0])] = array(
                        'id'=>trim($tmp[0]),
                        'key'=>trim($tmp[1]),
                        'title'=>isset($tmp[2]) ? $tmp[2] : $v[0]
                        );
                }
            }
        }
        return empty($type) ? $options : (isset($options[$type]) ? $options[$type] : array());
    }
}
