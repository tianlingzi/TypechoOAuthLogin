<?php

class TemplateSDK extends ThinkOauth
{
    # 第三方平台名称
    protected $displayName = '模板SDK';
    # 将下方链接修改为你的OAuth2/OpenID 配置 URL（发现文档）
    protected $OpenIDConfiguration = 'https://your-oidc-provider.com/.well-known/openid-configuration';
    
    protected $GetRequestCodeURL = '';
    
    protected $GetAccessTokenURL = '';
    
    protected $GetUserInfoURL = '';
    
    protected $Authorize = 'scope=openid profile email';
    
    protected $ApiBase = '';
    
    public function __construct($token = null, $type = null)
    {
        parent::__construct($token, $type);
        try {
            $this->fetchOpenIDConfig();
        } catch (Exception $e) {
            error_log(__CLASS__ . '初始化失败: ' . $e->getMessage());
            throw new Exception(__CLASS__ . '初始化失败: ' . $e->getMessage());
        }
    }
    
    protected function fetchOpenIDConfig()
    {
        $config = $this->http($this->OpenIDConfiguration, array(), 'GET');
        
        if (empty($config)) {
            throw new Exception('获取OpenID Connect配置失败: 服务器返回空数据');
        }
        
        $config = json_decode($config, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('获取OpenID Connect配置失败: JSON解析错误 - ' . json_last_error_msg());
        }
        
        if (empty($config)) {
            throw new Exception('获取OpenID Connect配置失败: 返回数据为空数组');
        }
        
        $requiredFields = array('authorization_endpoint', 'token_endpoint', 'userinfo_endpoint');
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                throw new Exception('获取OpenID Connect配置失败: 缺少必要字段 - ' . $field);
            }
        }
        
        $this->GetRequestCodeURL = $config['authorization_endpoint'];
        $this->GetAccessTokenURL = $config['token_endpoint'];
        $this->GetUserInfoURL = $config['userinfo_endpoint'];
        if (isset($config['issuer'])) {
            $this->ApiBase = $config['issuer'];
        }
    }
    
    public function call($api, $param = '', $method = 'GET', $multi = false)
    {
        $params = array(
            'access_token' => $this->Token['access_token'],
        );
        
        $vars = $this->param($params, $param);
        $data = $this->http($this->url($api), $vars, $method);
        return json_decode($data, true);
    }
    
    public function getUserInfo()
    {
        $headers = array(
            'Authorization: Bearer ' . $this->Token['access_token'],
            'Content-Type: application/json',
        );
        
        $data = $this->http($this->GetUserInfoURL, array(), 'GET', $headers);
        $userInfo = json_decode($data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('获取用户信息失败: JSON解析错误 - ' . json_last_error_msg());
        }
        
        if (empty($userInfo)) {
            throw new Exception('获取用户信息失败: 返回数据为空');
        }
        
        return $userInfo;
    }
    
    protected function parseJWT($jwt)
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return array();
        }
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
        return json_decode($payload, true);
    }
    
    protected function parseToken($result, $extend = null)
    {
        $data = json_decode($result, true);
        if ($data['access_token'] && $data['token_type']) {
            $this->Token = $data;
            
            $payload = $this->parseJWT($data['access_token']);
            $openid = isset($payload['sub']) ? $payload['sub'] : '';
            
            if (empty($openid) && isset($data['id_token'])) {
                $idPayload = $this->parseJWT($data['id_token']);
                $openid = isset($idPayload['sub']) ? $idPayload['sub'] : '';
            }
            
            $data['openid'] = $openid;
            return $data;
        } else {
            throw new Exception('获取ACCESS_TOKEN 失败：' . $result);
        }
    }
    
    public function openid()
    {
        if (isset($this->Token['openid']) && !empty($this->Token['openid'])) {
            return $this->Token['openid'];
        }
        
        $userinfo = $this->getUserInfo();
        return isset($userinfo['sub']) ? $userinfo['sub'] : '';
    }
}