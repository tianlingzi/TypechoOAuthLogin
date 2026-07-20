<?php
abstract class ThinkOauth
{
    /**
     * oauth版本
     * @var string
     */
    protected $Version = '2.0';

    /**
     * 申请应用时分配的app_key
     * @var string
     */
    protected $AppKey = '';

    /**
     * 申请应用时分配的 app_secret
     * @var string
     */
    protected $AppSecret = '';

    /**
     * 授权类型 response_type 目前只能为code
     * @var string
     */
    protected $ResponseType = 'code';

    /**
     * grant_type 目前只能为 authorization_code
     * @var string
     */
    protected $GrantType = 'authorization_code';

    /**
     * 回调页面URL  可以通过配置文件配置
     * @var string
     */
    protected $Callback = '';

    /**
     * 获取request_code的额外参数 URL查询字符串格式
     * @var srting
     */
    protected $Authorize = '';

    /**
     * 获取request_code请求的URL
     * @var string
     */
    protected $GetRequestCodeURL = '';

    /**
     * 获取access_token请求的URL
     * @var string
     */
    protected $GetAccessTokenURL = '';

    /**
     * API根路径
     * @var string
     */
    protected $ApiBase = '';

    /**
     * 授权后获取到的TOKEN信息
     * @var array
     */
    protected $Token = null;

    /**
     * 调用接口类型
     * @var string
     */
    private $Type = '';

    /**
     * 构造方法，配置应用信息
     * @param array $token
     */
    public function __construct($token = null, $type = null)
    {
        if ($type !== null) {
            $this->Type = strtoupper($type);
        } elseif (property_exists($this, 'type')) {
            $this->Type = strtoupper($this->type);
        } else {
            $class = get_class($this);
            $this->Type = strtoupper(substr($class, 0, strlen($class)-3));
        }
        
        $typeLower = strtolower($this->Type);

        $config = TypechoOAuthLogin_Plugin::options($typeLower);
        
        if (empty($config['id']) || empty($config['key'])) {
            throw new Exception("请配置您申请的APP_KEY和APP_SECRET");
        } else {
            $this->AppKey    = $config['id'];
            $this->AppSecret = $config['key'];
            $this->Token     = $token;
        }
    }

    /**
     * 取得Oauth实例
     * @static
     * @return mixed 返回Oauth
     */
    public static function getInstance($type, $token = null)
    {
        $typeLower = strtolower($type);
        $sdkDir = __DIR__ . DIRECTORY_SEPARATOR . 'sdk';
        
        $sdkFiles = glob($sdkDir . DIRECTORY_SEPARATOR . '*.class.php');
        if (empty($sdkFiles)) {
            throw new Exception('SDK目录为空: ' . $sdkDir);
        }
        
        $foundFilePath = null;
        
        foreach ($sdkFiles as $file) {
            $fileName = basename($file, '.class.php');
            $fileTypeLower = strtolower(str_replace('SDK', '', $fileName));
            
            if ($fileTypeLower === $typeLower) {
                $foundFilePath = $file;
                break;
            }
        }
        
        if ($foundFilePath === null) {
            foreach ($sdkFiles as $file) {
                $content = file_get_contents($file);
                if (preg_match('/protected\s+\$type\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                    $customType = strtolower($matches[1]);
                    if ($customType === $typeLower) {
                        $foundFilePath = $file;
                        break;
                    }
                }
            }
        }
        
        if ($foundFilePath === null) {
            $existingSDKs = array();
            foreach ($sdkFiles as $file) {
                $fileName = basename($file, '.class.php');
                $fileTypeLower = strtolower(str_replace('SDK', '', $fileName));
                $existingSDKs[] = $fileTypeLower;
            }
            throw new Exception('未找到匹配的SDK文件，类型: ' . $typeLower . '，可用的SDK: ' . implode(', ', $existingSDKs));
        }
        
        $classesBefore = get_declared_classes();
        require_once $foundFilePath;
        $classesAfter = get_declared_classes();
        
        $newClasses = array_diff($classesAfter, $classesBefore);
        
        foreach ($newClasses as $className) {
            if (class_exists($className)) {
                try {
                    $reflection = new ReflectionClass($className);
                    if ($reflection->isSubclassOf('ThinkOauth')) {
                        $class = new $className($token, $typeLower);
                        $class->Type = strtoupper($typeLower);
                        return $class;
                    }
                } catch (ReflectionException $e) {
                    continue;
                }
            }
        }
        
        foreach ($classesAfter as $className) {
            if (class_exists($className)) {
                try {
                    $reflection = new ReflectionClass($className);
                    if ($reflection->isSubclassOf('ThinkOauth')) {
                        $hasDisplay = property_exists($className, 'displayName');
                        $isAbstract = $reflection->isAbstract();
                        if (!$isAbstract && $hasDisplay) {
                            $class = new $className($token, $typeLower);
                            $class->Type = strtoupper($typeLower);
                            return $class;
                        }
                    }
                } catch (ReflectionException $e) {
                    continue;
                }
            }
        }
        
        throw new Exception('SDK文件 ' . $foundFilePath . ' 中未找到 ThinkOauth 子类');
    }
    /**
     * 初始化配置
     */
    private function config()
    {
        $configPath = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
        if (!file_exists($configPath)) {
            throw new Exception('配置文件不存在: ' . $configPath);
        }
        $configAll = require_once $configPath;
        $configKey = "THINK_SDK_{$this->Type}";
        
        if (isset($configAll[$configKey])) {
            $config = $configAll[$configKey];
            if (!empty($config['AUTHORIZE'])) {
                $this->Authorize = $config['AUTHORIZE'];
            }
            if (!empty($config['CALLBACK'])) {
                $this->Callback = $config['CALLBACK'];
            } else {
                throw new Exception('请配置回调页面地址');
            }
        } else {
            $callbackUrl = Typecho_Common::url('/oauth_callback?type=' . strtolower($this->Type), Typecho_Widget::Widget('Widget_Options')->index);
            $this->Callback = $callbackUrl;
        }
    }
    /**
     * 请求code
     */

    public function getRequestCodeURL($type)
    {
        $this->config();
        //Oauth 标准参数
        if ($type == 'wechat'){
            $params = array(
                'appid'         => $this->AppKey,
                'redirect_uri'  => $this->Callback,
                'response_type' => $this->ResponseType,
            );
        }else{
            $params = array(
                'client_id'     => $this->AppKey,
                'redirect_uri'  => $this->Callback,
                'response_type' => $this->ResponseType,
            );
        }

        //获取额外参数
        if ($this->Authorize) {
            parse_str($this->Authorize, $_param);
            if (is_array($_param)) {
                $params = array_merge($params, $_param);
            } else {
                throw new Exception('AUTHORIZE配置不正确！');
            }
        }
        return $this->GetRequestCodeURL . '?' . http_build_query($params);
    }

    /**
     * 获取access_token
     * @param string $code 上一步请求到的code
     */
    public function getAccessToken($type, $code, $extend = null)
    {
        $this->config();
        if ($type == 'wechat') {
            $params = array(
                'appid'         => $this->AppKey,
                'secret'        => $this->AppSecret,
                'grant_type'    => $this->GrantType,
                'code'          => $code
            );
        } else {
            $params = array(
                'client_id'     => $this->AppKey,
                'client_secret' => $this->AppSecret,
                'grant_type'    => $this->GrantType,
                'code'          => $code,
                'redirect_uri'  => $this->Callback,
            );
        }

        $data = $this->http($this->GetAccessTokenURL, $params, 'POST');
        $this->Token = $this->parseToken($data, $extend);
        return $this->Token;
    }

    /**
     * 合并默认参数和额外参数
     * @param array $params  默认参数
     * @param array/string $param 额外参数
     * @return array:
     */
    protected function param($params, $param)
    {
        if (is_string($param)) {
            parse_str($param, $param);
        }
        return array_merge($params, $param);
    }

    /**
     * 获取指定API请求的URL
     * @param  string $api API名称
     * @param  string $fix api后缀
     * @return string      请求的完整URL
     */
    protected function url($api, $fix = '')
    {
        return $this->ApiBase . $api . $fix;
    }

    /**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     * @param  string $url    请求URL
     * @param  array  $params 请求参数
     * @param  string $method 请求方法GET/POST
     * @return array  $data   响应数据
     */
    protected function http($url, $params, $method = 'GET', $header = array(), $multi = false)
    {
        $opts = array(
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => $header
        );

        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                $params = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                throw new Exception('不支持的请求方式！');
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new Exception('请求发生错误：' . $error);
        }
        return  $data;
    }

    /**
     * 抽象方法，在SNSSDK中实现
     * 组装接口调用参数 并调用接口
     */
    abstract protected function call($api, $param = '', $method = 'GET', $multi = false);

    /**
     * 抽象方法，在SNSSDK中实现
     * 解析access_token方法请求后的返回值
     */
    abstract protected function parseToken($result, $extend);

    /**
     * 抽象方法，在SNSSDK中实现
     * 获取当前授权用户的SNS标识
     */
    abstract public function openid();

    /**
     * 获取用户信息
     * 子类可以重写此方法来提供特定平台的用户信息
     * @return array 用户信息数组，包含name, nickname, head_img, gender字段
     */
    public function getUserInfo()
    {
        $userInfo = array(
            'name' => '',
            'nickname' => '',
            'head_img' => '',
            'gender' => 0
        );
        
        try {
            $openid = $this->openid();
            $userInfo['name'] = $openid;
            $userInfo['nickname'] = $openid;
        } catch (Exception $e) {
            error_log('获取用户信息失败: ' . $e->getMessage());
        }
        
        return $userInfo;
    }
}
