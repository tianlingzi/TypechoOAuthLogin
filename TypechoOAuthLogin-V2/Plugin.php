<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}/**
 * Typecho OAuth登录插件V2，已支持的第三方登录：QQ/微信/Github/Msn/Google/新浪微博/豆瓣/点点/淘宝网/百度/通用OpenID Connect登录
 *
 * @package TypechoOAuthLogin
 * @author tianlingzi
 * @version 2.1.1
 * @link https://www.tianlingzi.top/archives/273/
 *
 */
date_default_timezone_set('Asia/Shanghai');

class TypechoOAuthLogin_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        try {
            $info = self::installDb();

            Helper::addRoute('oauth', '/oauth', 'TypechoOAuthLogin_Widget', 'oauth');
            Helper::addRoute('oauth_callback', '/oauth_callback', 'TypechoOAuthLogin_Widget', 'callback');
            Helper::addRoute('connect_manage', '/connect/manage', 'TypechoOAuthLogin_Widget', 'manage');
            Helper::addRoute('connect_toggle', '/connect/toggle', 'TypechoOAuthLogin_Widget', 'toggle');
            Helper::addRoute('connect_clear_table', '/connect/clear-table', 'TypechoOAuthLogin_Widget', 'clearTable');
            Helper::addRoute('connect_remove_table', '/connect/remove-table', 'TypechoOAuthLogin_Widget', 'removeTable');

            Typecho_Plugin::factory('admin/common.php')->begin = array('TypechoOAuthLogin_Plugin', 'handleEarlyLoginInsert');
            Typecho_Plugin::factory('index.php')->end = array('TypechoOAuthLogin_Plugin', 'injectFrontendLoginScript');

            return $info;
        } catch (Exception $e) {
            return _t('插件激活失败：%s', $e->getMessage());
        }
    }

    public static function deactivate()
    {
        Helper::removeRoute('oauth');
        Helper::removeRoute('oauth_callback');
        Helper::removeRoute('connect_manage');
        Helper::removeRoute('connect_toggle');
        Helper::removeRoute('connect_clear_table');
        Helper::removeRoute('connect_remove_table');
        return _t('插件已禁用，数据表已保留');
    }

    public static function getProviders()
    {
        static $cachedProviders = null;
        if ($cachedProviders !== null) {
            return $cachedProviders;
        }

        $providers = array(
            'qq' => '腾讯QQ',
            'wechat' => '微信',
            'github' => 'Github',
            'msn' => 'MSN',
            'google' => 'Google',
            'sina' => '新浪微博',
            'douban' => '豆瓣',
            'diandian' => '点点',
            'taobao' => '淘宝网',
            'baidu' => '百度'
        );

        $sdkDir = __DIR__ . '/sdk';
        if (is_dir($sdkDir)) {
            $files = scandir($sdkDir);
            foreach ($files as $file) {
                if (preg_match('/^([A-Za-z]+)SDK\.class\.php$/', $file, $matches)) {
                    $type = strtolower($matches[1]);
                    $classPath = $sdkDir . '/' . $file;
                    
                    if (file_exists($classPath)) {
                        $content = file_get_contents($classPath);
                        
                        $customType = null;
                        if (preg_match('/protected\s+\$type\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $typeMatches)) {
                            $customType = strtolower($typeMatches[1]);
                        }
                        
                        $finalType = $customType !== null ? $customType : $type;
                        
                        if (!isset($providers[$finalType])) {
                            $displayName = ucfirst($matches[1]);
                            if (preg_match('/protected\s+\$displayName\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $displayMatches)) {
                                $displayName = $displayMatches[1];
                            }
                            $providers[$finalType] = $displayName;
                        }
                    }
                }
            }
        }

        $cachedProviders = $providers;
        return $providers;
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $autoInsert = new Typecho_Widget_Helper_Form_Element_Radio(
            'autoInsert',
            array(1 => _t('开启'), 0 => _t('关闭')),
            1,
            _t('自动插入登录按钮'),
            _t('是否在默认登录页自动插入第三方登录按钮')
        );
        $form->addInput($autoInsert);

        $displayType = new Typecho_Widget_Helper_Form_Element_Radio(
            'displayType',
            array(
                'circle' => _t('圆形图标'),
                'rect' => _t('矩形图标'),
                'button' => _t('按钮样式')
            ),
            'circle',
            _t('显示类型'),
            _t('选择自动插入登录页时的显示类型')
        );
        $form->addInput($displayType);

        $custom = new Typecho_Widget_Helper_Form_Element_Radio(
            'custom',
            array(1 => _t('是'), 0 => _t('否')),
            1,
            _t('是否需要完善资料'),
            _t('用户使用社会化登录后，是否需要完善昵称、邮箱等信息；选择不需要完善资料则直接使用获取到的昵称。')
        );
        $form->addInput($custom);

        $savedConfig = '';
        try {
            $savedConfig = Typecho_Widget::Widget('Widget_Options')->plugin('TypechoOAuthLogin')->oauthConfig;
        } catch (Exception $e) {
            $savedConfig = '';
        }

        $oauthConfig = new Typecho_Widget_Helper_Form_Element_Textarea('oauthConfig', null, $savedConfig, '', '');
        $oauthConfig->input->setAttribute('style', 'display:none;');
        $form->addInput($oauthConfig);

        $providers = self::getProviders();

        $configs = array();
        if (!empty($savedConfig)) {
            $configs = json_decode($savedConfig, true);
            if (!is_array($configs)) {
                $configs = array();
            }
        }

        $siteUrl = Typecho_Widget::Widget('Widget_Options')->index;

        echo '<style>
            .oauth-config-section { margin-top: 20px; }
            .oauth-config-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
            .oauth-config-header h3 { margin: 0; font-size: 16px; }
            .oauth-add-btn { background: #1677ff; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; cursor: pointer; border: none; }
            .oauth-add-btn:hover { background: #4096ff; }
            .oauth-export-btn { background: #28a745; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; cursor: pointer; border: none; margin-left: 8px; }
            .oauth-export-btn:hover { background: #218838; }
            .oauth-import-btn { background: #ffc107; color: #333; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; cursor: pointer; border: none; margin-left: 8px; }
            .oauth-import-btn:hover { background: #e0a800; }
            .oauth-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .oauth-table th, .oauth-table td { padding: 10px; border: 1px solid #dee2e6; text-align: left; }
            .oauth-table th { background: #e9ecef; font-weight: 600; }
            .oauth-input { width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; }
            .oauth-select { width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; }
            .oauth-delete-btn { color: #dc3545; text-decoration: none; font-size: 14px; cursor: pointer; }
            .oauth-delete-btn:hover { color: #c82333; }
            .oauth-copy-btn { background: #6c757d; color: white; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 12px; cursor: pointer; border: none; }
            .oauth-copy-btn:hover { background: #5a6268; }
            .oauth-toggle { width: 40px; height: 20px; background: #ccc; border-radius: 10px; position: relative; cursor: pointer; }
            .oauth-toggle.active { background: #1677ff; }
            .oauth-toggle::after { content: ""; width: 16px; height: 16px; background: white; border-radius: 50%; position: absolute; top: 2px; left: 2px; transition: left 0.3s; }
            .oauth-toggle.active::after { left: 22px; }
            .oauth-row { transition: background-color 0.2s; }
            .oauth-row:hover { background-color: #f8f9fa; }
        </style>';

        echo '<div class="oauth-config-section">';
        echo '<div class="oauth-config-header">';
        echo '<h3>' . _t('第三方登录配置') . '</h3>';
        echo '<div>';
        echo '<button type="button" class="oauth-add-btn" onclick="oauthAddRow()">' . _t('添加配置') . '</button>';
        echo '<button type="button" class="oauth-export-btn" onclick="oauthExportConfig()">' . _t('导出配置') . '</button>';
        echo '<button type="button" class="oauth-import-btn" onclick="oauthImportConfig()">' . _t('导入配置') . '</button>';
        echo '<input type="file" id="oauthImportFile" accept=".json" style="display:none;" onchange="oauthHandleImportFile(this)" />';
        echo '</div>';
        echo '</div>';

        echo '<table class="oauth-table" id="oauthConfigTable">';
        echo '<thead><tr><th>' . _t('平台类型') . '</th><th>' . _t('显示名称') . '</th><th>' . _t('Client ID') . '</th><th>' . _t('Client Secret') . '</th><th>' . _t('回调地址') . '</th><th>' . _t('启用') . '</th><th>' . _t('操作') . '</th></tr></thead>';
        echo '<tbody>';

        if (empty($configs)) {
            echo '<tr><td colspan="7" style="text-align:center;color:#999;">' . _t('暂无配置，请点击上方按钮添加') . '</td></tr>';
        } else {
            foreach ($configs as $index => $config) {
                $enabled = isset($config['enabled']) ? $config['enabled'] : 1;
                $callbackUrl = $siteUrl . '/oauth_callback?type=' . $config['type'];
                echo '<tr class="oauth-row" data-index="' . $index . '">';
                echo '<td><select class="oauth-select" name="oauth_type[]" onchange="oauthOnChange()">';
                foreach ($providers as $key => $value) {
                    $selected = ($config['type'] == $key) ? 'selected' : '';
                    echo '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
                }
                echo '</select></td>';
                echo '<td><input type="text" class="oauth-input" name="oauth_name[]" value="' . htmlspecialchars($config['name']) . '" placeholder="' . _t('显示名称') . '" oninput="oauthOnChange()" /></td>';
                echo '<td><input type="text" class="oauth-input" name="oauth_appkey[]" value="' . htmlspecialchars($config['app_key']) . '" placeholder="Client ID" oninput="oauthOnChange()" /></td>';
                echo '<td><input type="text" class="oauth-input" name="oauth_appsecret[]" value="' . htmlspecialchars($config['app_secret']) . '" placeholder="Client Secret" oninput="oauthOnChange()" /></td>';
                echo '<td><input type="text" class="oauth-input" value="' . htmlspecialchars($callbackUrl) . '" readonly /> <button type="button" class="oauth-copy-btn" onclick="oauthCopyCallback(this)">' . _t('复制') . '</button></td>';
                echo '<td><div class="oauth-toggle ' . ($enabled ? 'active' : '') . '" onclick="oauthToggle(this)"></div><input type="hidden" name="oauth_enabled[]" value="' . $enabled . '" /></td>';
                echo '<td><a href="#" class="oauth-delete-btn" onclick="oauthDeleteRow(this);return false;">' . _t('删除') . '</a></td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '</div>';

        echo '<div style="margin-top:20px;padding:15px;background:#f8f9fa;border-radius:8px;">';
        echo '<h4 style="margin:0 0 10px 0;">' . _t('代码引用方式') . '</h4>';
        echo '<p style="color:#666;font-size:14px;margin:0;">';
        echo _t('在模板中引用第三方登录按钮：') . '<br/>';
        echo _t('按钮样式：') . '<code>&lt;?php TypechoOAuthLogin_Plugin::showButtons(); ?&gt;</code><br/>';
        echo _t('圆形图标：') . '<code>&lt;?php TypechoOAuthLogin_Plugin::showImages(); ?&gt;</code>';
        echo '<span style="color:#999;font-size:12px;">' . _t('（图标文件：icon_XXX.png，尺寸 35×35）') . '</span><br/>';
        echo _t('矩形图标：') . '<code>&lt;?php TypechoOAuthLogin_Plugin::showRectImages(); ?&gt;</code>';
        echo '<span style="color:#999;font-size:12px;">' . _t('（图标文件：XXX.png，尺寸 76×24）') . '</span><br/>';
        echo '</p>';
        echo '</div>';

        echo '<style>
            .oauth-button {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 16px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 500;
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
                margin-top: 10px;
            }
            .oauth-button:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            .oauth-button.clear {
                background: linear-gradient(90deg, #1677ff, #69adff);
                color: white;
            }
            .oauth-button.clear:hover {
                box-shadow: 0 6px 16px rgba(22,119,255,0.35);
            }
            .oauth-button.remove {
                background: linear-gradient(90deg, #ff4d4f, #ff7875);
                color: white;
            }
            .oauth-button.remove:hover {
                box-shadow: 0 6px 16px rgba(255,77,79,0.35);
            }
        </style>';

        echo '<div class="typecho-option">';
        echo '<div class="typecho-control-group">';
        echo '<div class="description">' . _t('警告：此操作将删除oauth_user表中的所有数据并重建表结构，所有用户绑定的第三方登录信息将丢失，但插件仍可继续使用！请谨慎操作。') . '</div>';
        echo '<div class="typecho-control">';
        $clearUrl = Typecho_Common::url('/connect/clear-table', Typecho_Widget::Widget('Widget_Options')->index);
        echo '<a href="' . $clearUrl . '" class="oauth-button clear" onclick="return confirm(\'确定要清除数据表数据吗？此操作不可恢复！\');">' . _t('清除数据表') . '</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="typecho-option">';
        echo '<div class="typecho-control-group">';
        echo '<div class="description">' . _t('警告：此操作将直接删除oauth_user数据表，所有用户绑定的第三方登录信息将丢失，且插件后续无法正常使用！请谨慎操作。') . '</div>';
        echo '<div class="typecho-control">';
        $removeUrl = Typecho_Common::url('/connect/remove-table', Typecho_Widget::Widget('Widget_Options')->index);
        echo '<a href="' . $removeUrl . '" class="oauth-button remove" onclick="return confirm(\'确定要删除数据表吗？此操作不可恢复，且插件将无法继续使用！\');">' . _t('删除数据表') . '</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        ?>
        <script>
            var oauthProviders = <?php echo json_encode($providers); ?>;
            var oauthSiteUrl = "<?php echo $siteUrl; ?>";
            
            function oauthAddRow() {
                var table = document.getElementById("oauthConfigTable");
                var tbody = table.querySelector("tbody");
                
                var emptyRow = tbody.querySelector("tr td[colspan='7']");
                if (emptyRow) {
                    emptyRow.parentElement.remove();
                }
                
                var row = document.createElement("tr");
                row.className = "oauth-row";
                
                var typesHtml = "";
                for (var key in oauthProviders) {
                    typesHtml += "<option value=\"" + key + "\">" + oauthProviders[key] + "</option>";
                }
                
                row.innerHTML = 
                    '<td><select class="oauth-select" name="oauth_type[]" onchange="oauthOnChange()"></select></td>' +
                    '<td><input type="text" class="oauth-input" name="oauth_name[]" placeholder="显示名称" oninput="oauthOnChange()" /></td>' +
                    '<td><input type="text" class="oauth-input" name="oauth_appkey[]" placeholder="Client ID" oninput="oauthOnChange()" /></td>' +
                    '<td><input type="text" class="oauth-input" name="oauth_appsecret[]" placeholder="Client Secret" oninput="oauthOnChange()" /></td>' +
                    '<td><input type="text" class="oauth-input" value="" readonly /> <button type="button" class="oauth-copy-btn" onclick="oauthCopyCallback(this)">复制</button></td>' +
                    '<td><div class="oauth-toggle active" onclick="oauthToggle(this)"></div><input type="hidden" name="oauth_enabled[]" value="1" /></td>' +
                    '<td><a href="#" class="oauth-delete-btn" onclick="oauthDeleteRow(this);return false;">删除</a></td>';
                
                var select = row.querySelector("select");
                select.innerHTML = typesHtml;
                
                tbody.appendChild(row);
                oauthUpdateCallbackUrls();
                oauthSerializeConfig();
            }
            
            function oauthDeleteRow(el) {
                el.closest("tr").remove();
                oauthUpdateCallbackUrls();
                oauthSerializeConfig();
                
                var tbody = document.getElementById("oauthConfigTable").querySelector("tbody");
                if (tbody.children.length === 0) {
                    tbody.innerHTML = "<tr><td colspan='7' style='text-align:center;color:#999;'>暂无配置，请点击上方按钮添加</td></tr>";
                }
            }
            
            function oauthToggle(el) {
                el.classList.toggle("active");
                var hiddenInput = el.nextElementSibling;
                hiddenInput.value = el.classList.contains("active") ? "1" : "0";
                oauthSerializeConfig();
            }
            
            function oauthCopyCallback(el) {
                var input = el.previousElementSibling;
                input.select();
                document.execCommand("copy");
                alert("已复制回调地址");
            }
            
            function oauthUpdateCallbackUrls() {
                var rows = document.querySelectorAll("#oauthConfigTable tbody tr");
                rows.forEach(function(row) {
                    var typeSelect = row.querySelector("select[name='oauth_type[]']");
                    var callbackInput = row.querySelector("input[readonly]");
                    if (typeSelect && callbackInput) {
                        callbackInput.value = oauthSiteUrl + "/oauth_callback?type=" + typeSelect.value;
                    }
                });
            }
            
            function oauthOnChange() {
                oauthUpdateCallbackUrls();
                oauthSerializeConfig();
            }
            
            function oauthSerializeConfig() {
                var configs = [];
                var rows = document.querySelectorAll("#oauthConfigTable tbody tr");
                rows.forEach(function(row) {
                    var type = row.querySelector("select[name='oauth_type[]']");
                    var name = row.querySelector("input[name='oauth_name[]']");
                    var appKey = row.querySelector("input[name='oauth_appkey[]']");
                    var appSecret = row.querySelector("input[name='oauth_appsecret[]']");
                    var enabled = row.querySelector("input[name='oauth_enabled[]']");
                    
                    if (type && name && appKey && appSecret && enabled) {
                        var t = type.value;
                        var n = name.value.trim();
                        var ak = appKey.value.trim();
                        var as = appSecret.value.trim();
                        var en = enabled.value;
                        
                        if (t && n && ak && as) {
                            configs.push({
                                type: t,
                                name: n,
                                app_key: ak,
                                app_secret: as,
                                enabled: en
                            });
                        }
                    }
                });
                
                var oauthConfig = document.querySelector("textarea[name='oauthConfig']");
                if (oauthConfig) {
                    oauthConfig.value = JSON.stringify(configs);
                }
            }
            
            document.addEventListener("DOMContentLoaded", function() {
                oauthUpdateCallbackUrls();
                oauthSerializeConfig();
            });
            
            var forms = document.querySelectorAll("form");
            forms.forEach(function(form) {
                form.addEventListener("submit", function(event) {
                    oauthSerializeConfig();
                }, false);
            });
            
            window.oauthSerializeConfig = oauthSerializeConfig;
            
            function oauthExportConfig() {
                var oauthConfig = document.querySelector("textarea[name='oauthConfig']");
                var configData = oauthConfig ? oauthConfig.value : '[]';
                
                var autoInsert = document.querySelector("input[name='autoInsert']:checked");
                var displayType = document.querySelector("input[name='displayType']:checked");
                var custom = document.querySelector("input[name='custom']:checked");
                
                var exportData = {
                    version: "2.0",
                    autoInsert: autoInsert ? autoInsert.value : "1",
                    displayType: displayType ? displayType.value : "circle",
                    custom: custom ? custom.value : "1",
                    oauthConfig: JSON.parse(configData)
                };
                
                var blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'typecho-oauth-config-' + new Date().toISOString().slice(0,10) + '.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
            
            function oauthImportConfig() {
                document.getElementById("oauthImportFile").click();
            }
            
            function oauthHandleImportFile(input) {
                if (!input.files || !input.files[0]) {
                    return;
                }
                
                var file = input.files[0];
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    try {
                        var data = JSON.parse(e.target.result);
                        
                        if (!data || typeof data !== 'object') {
                            alert('导入失败：配置文件格式不正确');
                            return;
                        }
                        
                        if (data.version && data.version !== '2.0') {
                            alert('导入失败：配置文件版本不兼容');
                            return;
                        }
                        
                        if (!Array.isArray(data.oauthConfig)) {
                            alert('导入失败：配置数据格式不正确');
                            return;
                        }
                        
                        var validConfigs = [];
                        for (var i = 0; i < data.oauthConfig.length; i++) {
                            var cfg = data.oauthConfig[i];
                            if (cfg.type && cfg.name && cfg.app_key && cfg.app_secret !== undefined && cfg.enabled !== undefined) {
                                validConfigs.push({
                                    type: cfg.type,
                                    name: cfg.name,
                                    app_key: cfg.app_key,
                                    app_secret: cfg.app_secret,
                                    enabled: cfg.enabled
                                });
                            }
                        }
                        
                        if (validConfigs.length === 0) {
                            alert('导入失败：没有有效的配置项');
                            return;
                        }
                        
                        var tbody = document.getElementById("oauthConfigTable").querySelector("tbody");
                        tbody.innerHTML = '';
                        
                        for (var j = 0; j < validConfigs.length; j++) {
                            var cfg = validConfigs[j];
                            var typesHtml = "";
                            for (var key in oauthProviders) {
                                var selected = (cfg.type == key) ? 'selected' : '';
                                typesHtml += "<option value=\"" + key + "\" " + selected + ">" + oauthProviders[key] + "</option>";
                            }
                            
                            var row = document.createElement("tr");
                            row.className = "oauth-row";
                            row.innerHTML = 
                                '<td><select class="oauth-select" name="oauth_type[]" onchange="oauthOnChange()">' + typesHtml + '</select></td>' +
                                '<td><input type="text" class="oauth-input" name="oauth_name[]" value="' + htmlEscape(cfg.name) + '" placeholder="显示名称" oninput="oauthOnChange()" /></td>' +
                                '<td><input type="text" class="oauth-input" name="oauth_appkey[]" value="' + htmlEscape(cfg.app_key) + '" placeholder="Client ID" oninput="oauthOnChange()" /></td>' +
                                '<td><input type="text" class="oauth-input" name="oauth_appsecret[]" value="' + htmlEscape(cfg.app_secret) + '" placeholder="Client Secret" oninput="oauthOnChange()" /></td>' +
                                '<td><input type="text" class="oauth-input" value="" readonly /> <button type="button" class="oauth-copy-btn" onclick="oauthCopyCallback(this)">复制</button></td>' +
                                '<td><div class="oauth-toggle ' + (cfg.enabled ? 'active' : '') + '" onclick="oauthToggle(this)"></div><input type="hidden" name="oauth_enabled[]" value="' + cfg.enabled + '" /></td>' +
                                '<td><a href="#" class="oauth-delete-btn" onclick="oauthDeleteRow(this);return false;">删除</a></td>';
                            tbody.appendChild(row);
                        }
                        
                        if (data.autoInsert !== undefined) {
                            var autoInsertRadios = document.querySelectorAll("input[name='autoInsert']");
                            for (var k = 0; k < autoInsertRadios.length; k++) {
                                if (autoInsertRadios[k].value == data.autoInsert) {
                                    autoInsertRadios[k].checked = true;
                                    break;
                                }
                            }
                        }
                        
                        if (data.displayType) {
                            var displayTypeRadios = document.querySelectorAll("input[name='displayType']");
                            for (var m = 0; m < displayTypeRadios.length; m++) {
                                if (displayTypeRadios[m].value == data.displayType) {
                                    displayTypeRadios[m].checked = true;
                                    break;
                                }
                            }
                        }
                        
                        if (data.custom !== undefined) {
                            var customRadios = document.querySelectorAll("input[name='custom']");
                            for (var n = 0; n < customRadios.length; n++) {
                                if (customRadios[n].value == data.custom) {
                                    customRadios[n].checked = true;
                                    break;
                                }
                            }
                        }
                        
                        oauthUpdateCallbackUrls();
                        oauthSerializeConfig();
                        alert('配置导入成功！请点击「保存设置」完成配置保存');
                    } catch (err) {
                        alert('导入失败：' + err.message);
                    }
                };
                
                reader.onerror = function() {
                    alert('导入失败：无法读取文件');
                };
                
                reader.readAsText(file);
                input.value = '';
            }
            
            function htmlEscape(str) {
                if (!str) return '';
                return str.replace(/&/g, '&amp;')
                          .replace(/</g, '&lt;')
                          .replace(/>/g, '&gt;')
                          .replace(/"/g, '&quot;')
                          .replace(/'/g, '&#039;');
            }
        </script>
        <?php
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        $btnHtml = '<a class="typecho-button primary teconnect-manage-btn" href="/connect/manage" target="_blank">' . _t('管理第三方登录信息') . '</a>';
        $open = new Typecho_Widget_Helper_Form_Element_Text(
            'open_manage',
            null,
            null,
            '',
            _t($btnHtml)
        );
        $open->input->setAttribute('style', 'display:none');
        $open->setAttribute('id', 'open_manage');
        $form->addInput($open);
        echo '<style>
            #open_manage ~ .typecho-option.typecho-option-submit{display:none!important;}
            .teconnect-manage-btn{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(90deg,#1677ff,#69adff);border:0;color:#fff;padding:8px 14px;border-radius:8px;box-shadow:0 6px 12px rgba(22,119,255,.25);transition:transform .15s ease, box-shadow .15s ease;text-decoration:none;}
            .teconnect-manage-btn:hover{transform:translateY(-1px);box-shadow:0 10px 16px rgba(22,119,255,.35);} 
            .teconnect-manage-btn:active{transform:translateY(0);} 
        </style>';
    }

    public static function installDb()
    {
        try {
            self::addTable();
            return '数据表安装成功！';
        } catch (Typecho_Db_Exception $e) {
            if ('42S01' == $e->getCode()) {
                return '数据表已存在!';
            }
            throw $e;
        }
    }

    public static function addTable()
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        if (!in_array($db->getAdapterName(), array('Pdo_Mysql', 'Mysql'))) {
            throw new Typecho_Plugin_Exception(_t('对不起, 本插件仅支持MySQL数据库。'));
        }

        try {
            $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}oauth_user` (
                          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                          `uid` int(10) unsigned NULL DEFAULT 0 COMMENT '用户ID',
                          `uuid` int(10) unsigned NULL DEFAULT 0,
                          `type` char(32) NULL DEFAULT '' COMMENT '第三方登录类型',
                          `openid` char(50) NULL DEFAULT '' COMMENT '第三方登录唯一标识',
                          `access_token` text NULL COMMENT '用户对应access_token',
                          `expires_in` int(10) unsigned NULL DEFAULT 0 COMMENT 'token有效期',
                          `datetime` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后登录',
                          `name` varchar(100) NULL DEFAULT '' COMMENT '用户名',
                          `nickname` varchar(100) NULL DEFAULT '' COMMENT '昵称',
                          `gender` tinyint(1) unsigned NULL DEFAULT 0 COMMENT '性别0未知,1男,2女',
                          `head_img` varchar(255) NULL DEFAULT '' COMMENT '头像',
                          `refresh_token` text NULL COMMENT '刷新有效期token',
                          PRIMARY KEY (`id`),
                          KEY `uuid` (`uuid`),
                          KEY `uid` (`uid`),
                          KEY `type` (`type`),
                          KEY `openid` (`openid`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='第三方登录用户表';";
            $db->query($sql, Typecho_Db::WRITE);

            return "数据表安装成功！";
        } catch (Typecho_Db_Exception $e) {
            throw new Typecho_Plugin_Exception(_t('创建数据表失败：%s', $e->getMessage()));
        } catch (Exception $e) {
            throw new Typecho_Plugin_Exception(_t('系统错误：%s', $e->getMessage()));
        }
    }

    public static function clearTable()
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        try {
            $db->query("DROP TABLE IF EXISTS `" . $prefix . "oauth_user`", Typecho_Db::WRITE);
            self::addTable();
            return "清除数据表数据成功！";
        } catch (Typecho_Exception $e) {
            return "清除数据表数据失败！";
        }
    }

    public static function removeTable()
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        try {
            $db->query("DROP TABLE `" . $prefix . "oauth_user`", Typecho_Db::WRITE);
        } catch (Typecho_Exception $e) {
            return "删除数据表失败！";
        }
        return "删除数据表成功！";
    }

    public static function options($type = '')
    {
        static $options = array();
        if (empty($options)) {
            try {
                $oauthConfig = Typecho_Widget::Widget('Widget_Options')->plugin('TypechoOAuthLogin')->oauthConfig;
                if (!empty($oauthConfig)) {
                    $configs = json_decode($oauthConfig, true);
                    if (is_array($configs)) {
                        foreach ($configs as $config) {
                            $enabled = isset($config['enabled']) ? $config['enabled'] : 1;
                            if ($enabled) {
                                $options[strtolower($config['type'])] = array(
                                    'id' => trim($config['app_key']),
                                    'key' => trim($config['app_secret']),
                                    'title' => trim($config['name'])
                                );
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $options = array();
            }
        }
        return empty($type) ? $options : (isset($options[$type]) ? $options[$type] : array());
    }

    public static function insertLoginButtons()
    {
        echo '<!-- OAUTH_ADMIN_DEBUG: hook fired -->';
        
        $pluginOptions = Typecho_Widget::Widget('Widget_Options')->plugin('TypechoOAuthLogin');
        $autoInsert = $pluginOptions->autoInsert;
        if (!isset($autoInsert) || (int)$autoInsert !== 1) {
            echo '<!-- OAUTH_ADMIN_DEBUG: autoInsert disabled -->';
            return;
        }

        $list = self::options();
        if (empty($list)) {
            echo '<!-- OAUTH_ADMIN_DEBUG: no configs -->';
            return;
        }

        $displayType = $pluginOptions->displayType;
        if (empty($displayType)) {
            $displayType = 'circle';
        }

        $siteUrl = Typecho_Widget::Widget('Widget_Options')->index;

        $configs = array();
        foreach ($list as $type => $v) {
            $configs[] = array(
                'type' => $type,
                'title' => $v['title'],
                'url' => Typecho_Common::url('/oauth?type=' . $type, $siteUrl)
            );
        }

        $configJson = json_encode($configs);
        ?>
        <script>
        window.oauthLoginConfig = {
            configs: <?php echo $configJson; ?>,
            displayType: "<?php echo $displayType; ?>"
        };
        (function(){
            var f = function() {
                var form = document.querySelector("form[name='login']");
                if (!form) {
                    console.log('OAUTH_DEBUG: no login form found');
                    return;
                }
                
                var e = document.querySelector(".oauth-login-section");
                if (e) return;
                
                var c = window.oauthLoginConfig.configs;
                var d = window.oauthLoginConfig.displayType;
                
                var h = "<p>";
                h += "<div style='display:flex;align-items:center;margin:16px 0;'><div style='flex:1;height:1px;background:#ddd;'></div><span style='margin:0 12px;font-size:14px;color:#999;'>第三方登录</span><div style='flex:1;height:1px;background:#ddd;'></div></div>";
                h += "</p>";
                
                var b = "";
                for (var i = 0; i < c.length; i++) {
                    var g = c[i];
                    if (d == "button") {
                        b += "<p><button class='btn btn-l w-100 primary' onclick='location.href=\"" + g.url + "\"'>" + g.title + "</button></p>";
                    } else if (d == "circle") {
                        b += "<a href='" + g.url + "' title='" + g.title + "' style='margin:0 8px;'>";
                        b += "<img src='/usr/plugins/TypechoOAuthLogin/login_ico/icon_" + g.type + ".png' alt='" + g.type + "-" + g.title + "' style='width:35px;height:35px;border-radius:50%;'/>";
                        b += "</a>";
                    } else {
                        b += "<a href='" + g.url + "' title='" + g.title + "' style='margin:0 4px;display:inline-block;'>";
                        b += "<img src='/usr/plugins/TypechoOAuthLogin/login_ico/" + g.type + ".png' alt='" + g.type + "-" + g.title + "' style='width:76px;height:24px;border-radius:8px;'/>";
                        b += "</a>";
                    }
                }
                
                if (d == "button") {
                    h += b;
                } else {
                    h += "<p style='text-align:center;'>" + b + "</p>";
                }
                
                var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                var insertPoint = submitBtn ? submitBtn.parentNode : form;
                insertPoint.insertAdjacentHTML("afterend", h);
                
                if (d == "button" && form.classList.contains('lb-form')) {
                    var oauthBtns = form.querySelectorAll('p:not(.lb-field):not(.lb-remember) button.btn');
                    oauthBtns.forEach(function(btn){
                        var submitWrap = document.createElement('div');
                        submitWrap.className = 'lb-submit';
                        var p = btn.parentNode;
                        p.insertBefore(submitWrap, btn);
                        submitWrap.appendChild(btn);
                    });
                }
            };
            
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", function(){
                    setTimeout(f, 100);
                });
            } else {
                setTimeout(f, 100);
            }
        })();
        </script>
        <?php
    }

    public static function handleEarlyLoginInsert()
    {
        $currentUrl = Typecho_Widget::Widget('Widget_Options')->request->getRequestUrl();
        $isLoginPage = strpos($currentUrl, '/admin/login.php') !== false;
        
        if (!$isLoginPage) {
            return;
        }

        $pluginOptions = Typecho_Widget::Widget('Widget_Options')->plugin('TypechoOAuthLogin');
        $autoInsert = $pluginOptions->autoInsert;
        if (!isset($autoInsert) || (int)$autoInsert !== 1) {
            return;
        }

        $list = self::options();
        if (empty($list)) {
            return;
        }

        $displayType = $pluginOptions->displayType;
        if (empty($displayType)) {
            $displayType = 'circle';
        }

        $siteUrl = Typecho_Widget::Widget('Widget_Options')->index;

        $configs = array();
        foreach ($list as $type => $v) {
            $configs[] = array(
                'type' => $type,
                'title' => $v['title'],
                'url' => Typecho_Common::url('/oauth?type=' . $type, $siteUrl)
            );
        }

        $configJson = json_encode($configs);
        
        $oauthScript = <<<EOSCRIPT
<script>
window.oauthLoginConfig = {
    configs: {$configJson},
    displayType: "{$displayType}"
};
(function(){
    var f = function() {
        var form = document.querySelector("form[name='login'], form.login-form");
        if (!form) {
            console.log('OAUTH_DEBUG: no login form found');
            return;
        }
        
        var e = document.querySelector(".oauth-login-section");
        if (e) return;
        
        var isGateLogin = document.body.classList.contains('dark-body') || document.body.classList.contains('body-100') && form.classList.contains('login-form');
        
        var submitBtn = form.querySelector('button[type="submit"], input[type="submit"], .submit-button, .submit-btn');
        var gateLoginBtnClass = submitBtn && submitBtn.classList.contains('submit-btn') ? 'submit-btn' : 'submit-button';
        
        var c = window.oauthLoginConfig.configs;
        var d = window.oauthLoginConfig.displayType;
        
        var separatorStyle = isGateLogin 
            ? "<div style='display:flex;align-items:center;margin:20px 0;'><div style='flex:1;height:1px;background:linear-gradient(90deg,transparent,var(--color-border-hover, #3f444e),transparent);'></div><span style='margin:0 12px;font-size:13px;color:var(--color-text-muted, #9ca3af);font-weight:500;'>第三方登录</span><div style='flex:1;height:1px;background:linear-gradient(90deg,transparent,var(--color-border-hover, #3f444e),transparent);'></div></div>"
            : "<div style='display:flex;align-items:center;margin:16px 0;'><div style='flex:1;height:1px;background:#ddd;'></div><span style='margin:0 12px;font-size:14px;color:#999;'>第三方登录</span><div style='flex:1;height:1px;background:#ddd;'></div></div>";
        
        var h = isGateLogin ? "<div class='form-group'>" : "<p>";
        h += separatorStyle;
        h += isGateLogin ? "</div>" : "</p>";
        
        var b = "";
        for (var i = 0; i < c.length; i++) {
            var g = c[i];
            if (d == "button") {
                if (isGateLogin) {
                    if (gateLoginBtnClass === 'submit-btn') {
                        b += "<button type='button' class='submit-btn' onclick='location.href=\"" + g.url + "\"'>";
                        b += "<span class='btn-text'>" + g.title + "</span>";
                        b += "<span class='btn-icon'>";
                        b += "<svg width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>";
                        b += "<path d='M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2'/><circle cx='8.5' cy='7' r='4'/><line x1='20' y1='8' x2='20' y2='14'/><line x1='23' y1='11' x2='17' y2='11'/>";
                        b += "</svg></span></button>";
                    } else {
                        b += "<button type='button' class='submit-button' onclick='location.href=\"" + g.url + "\"'>";
                        b += "<span class='button-text'>" + g.title + "</span>";
                        b += "<svg class='button-icon' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>";
                        b += "<path d='M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2'/><circle cx='8.5' cy='7' r='4'/><line x1='20' y1='8' x2='20' y2='14'/><line x1='23' y1='11' x2='17' y2='11'/>";
                        b += "</svg></button>";
                    }
                } else {
                    b += "<p><button class='btn btn-l w-100 primary' onclick='location.href=\"" + g.url + "\"'>" + g.title + "</button></p>";
                }
            } else if (d == "circle") {
                b += "<a href='" + g.url + "' title='" + g.title + "' style='margin:0 8px;'>";
                b += "<img src='/usr/plugins/TypechoOAuthLogin/login_ico/icon_" + g.type + ".png' alt='" + g.type + "-" + g.title + "' style='width:35px;height:35px;border-radius:50%;'/>";
                b += "</a>";
            } else {
                b += "<a href='" + g.url + "' title='" + g.title + "' style='margin:0 4px;display:inline-block;'>";
                b += "<img src='/usr/plugins/TypechoOAuthLogin/login_ico/" + g.type + ".png' alt='" + g.type + "-" + g.title + "' style='width:76px;height:24px;border-radius:8px;'/>";
                b += "</a>";
            }
        }
        
        if (d == "button") {
            if (isGateLogin) {
                h += "<div class='form-group'>" + b + "</div>";
            } else {
                h += b;
            }
        } else {
            h += (isGateLogin ? "<div style='text-align:center;padding-top:8px;'>" : "<p style='text-align:center;'>") + b + (isGateLogin ? "</div>" : "</p>");
        }
        
        if (submitBtn) {
            submitBtn.insertAdjacentHTML("afterend", h);
        } else {
            form.appendChild(document.createRange().createContextualFragment(h));
        }
    };
    
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function(){
            setTimeout(f, 500);
        });
    } else {
        setTimeout(f, 500);
    }
})();
</script>
EOSCRIPT;

        ob_start();
        
        register_shutdown_function(function() use ($oauthScript) {
            $content = ob_get_clean();
            if (strpos($content, '</body>') !== false) {
                $content = str_replace('</body>', $oauthScript . '</body>', $content);
            }
            echo $content;
        });
    }

    public static function showImages()
    {
        $list = self::options();
        if (empty($list)) {
            return '';
        }
        $html = '';
        foreach ($list as $type => $v) {
            $url = Typecho_Common::url('/oauth?type=' . $type, Typecho_Widget::Widget('Widget_Options')->index);
            $html .= '<a href="' . $url . '" title="' . htmlspecialchars($v['title']) . '" style="margin: 0 8px;">';
            $html .= '<img src="/usr/plugins/TypechoOAuthLogin/login_ico/icon_' . $type . '.png" alt="' . htmlspecialchars($type . '-' . $v['title']) . '" style="width: 35px; height: 35px; border-radius: 50%;" />';
            $html .= '</a>';
        }
        echo $html;
    }

    public static function showRectImages()
    {
        $list = self::options();
        if (empty($list)) {
            return '';
        }
        $html = '';
        foreach ($list as $type => $v) {
            $url = Typecho_Common::url('/oauth?type=' . $type, Typecho_Widget::Widget('Widget_Options')->index);
            $html .= '<a href="' . $url . '" title="' . htmlspecialchars($v['title']) . '" style="margin: 0 4px; display: inline-block;">';
            $html .= '<img src="/usr/plugins/TypechoOAuthLogin/login_ico/' . $type . '.png" alt="' . htmlspecialchars($type . '-' . $v['title']) . '" style="width: 76px; height: 24px; border-radius: 8px;" />';
            $html .= '</a>';
        }
        echo $html;
    }

    public static function showButtons()
    {
        $list = self::options();
        if (empty($list)) {
            return '';
        }
        $html = '';
        foreach ($list as $type => $v) {
            $url = Typecho_Common::url('/oauth?type=' . $type, Typecho_Widget::Widget('Widget_Options')->index);
            $html .= '<a href="' . $url . '" class="btn btn-l w-100" style="margin-bottom: 8px; display: block;">' . htmlspecialchars($v['title']) . '</a>';
        }
        echo $html;
    }

    public static function injectFrontendLoginScript()
    {
        // 1. 非 GET 请求（评论提交/搜索POST等）直接跳过
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }
        // 2. AJAX 请求直接跳过
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return;
        }
        // 3. 请求路径明显为 feed/sitemap/robots 等非页面资源直接跳过
        if (!empty($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            if (preg_match('/\.(xml|rss|atom|txt|json|css|js|png|jpg|jpeg|gif|webp|ico|svg|woff2?|ttf|eot)$/i', $uri)) {
                return;
            }
        }
        // 4. 用户已经登录 → 前端页面不会展示登录表单，直接跳过
        try {
            $user = Typecho_Widget::Widget('Widget_User');
            if ($user->hasLogin()) {
                return;
            }
        } catch (Exception $e) {
            // 忽略异常，继续执行
        }

        $pluginOptions = Typecho_Widget::Widget('Widget_Options')->plugin('TypechoOAuthLogin');
        $autoInsert = $pluginOptions->autoInsert;
        if (!isset($autoInsert) || (int)$autoInsert !== 1) {
            return;
        }

        $list = self::options();
        if (empty($list)) {
            return;
        }

        $displayType = $pluginOptions->displayType;
        if (empty($displayType)) {
            $displayType = 'circle';
        }

        $siteUrl = Typecho_Widget::Widget('Widget_Options')->index;

        $configs = array();
        foreach ($list as $type => $v) {
            $configs[] = array(
                'type' => $type,
                'title' => $v['title'],
                'url' => Typecho_Common::url('/oauth?type=' . $type, $siteUrl)
            );
        }

        $configJson = json_encode($configs);
        ?>
        <script>
        window.oauthLoginConfig = {
            configs: <?php echo $configJson; ?>,
            displayType: "<?php echo $displayType; ?>"
        };
        (function(){
            var f = function() {
                var form = document.querySelector("form[name='login']");
                if (!form) {
                    return;
                }
                
                var e = document.querySelector(".oauth-login-section");
                if (e) return;
                
                var c = window.oauthLoginConfig.configs;
                var d = window.oauthLoginConfig.displayType;
                
                var h = "<p>";
                h += "<div style='display:flex;align-items:center;margin:16px 0;'><div style='flex:1;height:1px;background:#ddd;'></div><span style='margin:0 12px;font-size:14px;color:#999;'>第三方登录</span><div style='flex:1;height:1px;background:#ddd;'></div></div>";
                h += "</p>";
                
                var b = "";
                for (var i = 0; i < c.length; i++) {
                    var g = c[i];
                    if (d == "button") {
                        b += "<p><button class='btn btn-l w-100 primary' onclick='location.href=\"" + g.url + "\"'>" + g.title + "</button></p>";
                    } else if (d == "circle") {
                        b += "<a href='" + g.url + "' title='" + g.title + "' style='margin:0 8px;'>";
                        b += "<img src='/usr/plugins/TypechoOAuthLogin/login_ico/icon_" + g.type + ".png' alt='" + g.type + "-" + g.title + "' style='width:35px;height:35px;border-radius:50%;'/>";
                        b += "</a>";
                    } else {
                        b += "<a href='" + g.url + "' title='" + g.title + "' style='margin:0 4px;display:inline-block;'>";
                        b += "<img src='/usr/plugins/TypechoOAuthLogin/login_ico/" + g.type + ".png' alt='" + g.type + "-" + g.title + "' style='width:76px;height:24px;border-radius:8px;'/>";
                        b += "</a>";
                    }
                }
                
                if (d == "button") {
                    h += b;
                } else {
                    h += "<p style='text-align:center;'>" + b + "</p>";
                }
                
                var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                var insertPoint = submitBtn ? submitBtn.parentNode : form;
                insertPoint.insertAdjacentHTML("afterend", h);
                
                if (d == "button" && form.classList.contains('lb-form')) {
                    var oauthBtns = form.querySelectorAll('p:not(.lb-field):not(.lb-remember) button.btn');
                    oauthBtns.forEach(function(btn){
                        var submitWrap = document.createElement('div');
                        submitWrap.className = 'lb-submit';
                        var p = btn.parentNode;
                        p.insertBefore(submitWrap, btn);
                        submitWrap.appendChild(btn);
                    });
                }
            };
            
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", function(){
                    setTimeout(f, 100);
                });
            } else {
                setTimeout(f, 100);
            }
        })();
        </script>
        <?php
    }
}