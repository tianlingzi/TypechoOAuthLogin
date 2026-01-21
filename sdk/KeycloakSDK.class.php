<?php
// +----------------------------------------------------------------------
// | TypechoOAuthLogin Plugin
// +----------------------------------------------------------------------
// | KeycloakSDK.class.php 2026-01-21
// +----------------------------------------------------------------------

class KeycloakSDK extends ThinkOauth
{
    /**
     * Keycloak OpenID Connect 发现文档URL
     * 注意：用户需要根据自己的Keycloak服务器情况自行更改
     * @var string
     */
    protected $OpenIDConfiguration = 'https://YourKeycloakServer/realms/YourRealm/.well-known/openid-configuration';
    
    /**
     * 获取requestCode的api接口
     * 注意：用户需要根据自己的Keycloak服务器情况自行更改
     * @var string
     */
    protected $GetRequestCodeURL = 'https://YourKeycloakServer/realms/YourRealm/protocol/openid-connect/auth';  

    /**
     * 获取access_token的api接口
     * 注意：用户需要根据自己的Keycloak服务器情况自行更改
     * @var string
     */
    protected $GetAccessTokenURL = 'https://YourKeycloakServer/realms/YourRealm/protocol/openid-connect/token'; 

    /**
     * 获取用户信息的api接口
     * 注意：用户需要根据自己的Keycloak服务器情况自行更改
     * @var string
     */
    protected $GetUserInfoURL = 'https://YourKeycloakServer/realms/YourRealm/protocol/openid-connect/userinfo';

    /**
     * 获取request_code的额外参数,可在配置中修改 URL查询字符串格式
     * @var string
     */
    protected $Authorize = 'scope=openid profile email';

    /**
     * API根路径
     * @var string
     */
    protected $ApiBase = '';
    
    /**
     * 构造函数
     * @param array $token
     */
    public function __construct($token = null)
    {
        parent::__construct($token);
    }

    /**
     * 组装接口调用参数 并调用接口
     * @param  string $api    Keycloak API
     * @param  string $param  调用API的额外参数
     * @param  string $method HTTP请求方法 默认为GET
     * @return json
     */
    public function call($api, $param = '', $method = 'GET', $multi = false)
    {
        /* Keycloak调用公共参数 */
        $params = array(
            'access_token' => $this->Token['access_token'],
        );
        
        $vars = $this->param($params, $param);
        $data = $this->http($this->url($api), $vars, $method);
        return json_decode($data, true);
    }
    
    /**
     * 获取Keycloak用户信息
     * @return array 用户信息
     */
    public function getUserInfo()
    {
        $headers = array(
            'Authorization: Bearer ' . $this->Token['access_token'],
            'Content-Type: application/json',
        );
        
        $data = $this->http($this->GetUserInfoURL, array(), 'GET', $headers);
        $userInfo = json_decode($data, true);
        
        // 确保返回的数据格式正确
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('获取Keycloak用户信息失败: JSON解析错误 - ' . json_last_error_msg());
        }
        
        if (empty($userInfo)) {
            throw new Exception('获取Keycloak用户信息失败: 返回数据为空');
        }
        
        return $userInfo;
    }
    
    /**
     * 解析JWT获取payload
     * @param string $jwt JWT token
     * @return array payload
     */
    protected function parseJWT($jwt)
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return array();
        }
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
        return json_decode($payload, true);
    }
    
    /**
     * 解析access_token方法
     * @param string $result 获取access_token的方法
     */
    protected function parseToken($result, $extend = null)
    {
        $data = json_decode($result, true);
        if ($data['access_token'] && $data['token_type']) {
            $this->Token = $data;
            
            // 直接从access_token中解析出sub作为openid
            $payload = $this->parseJWT($data['access_token']);
            $openid = isset($payload['sub']) ? $payload['sub'] : '';
            
            // 如果JWT解析失败，尝试从id_token中获取
            if (empty($openid) && isset($data['id_token'])) {
                $idPayload = $this->parseJWT($data['id_token']);
                $openid = isset($idPayload['sub']) ? $idPayload['sub'] : '';
            }
            
            // 如果还是失败，记录错误并继续，后续会通过userinfo获取
            if (empty($openid)) {
                error_log('KeycloakSDK: 无法从JWT中解析openid');
            }
            
            $data['openid'] = $openid;
            return $data;
        } else {
            throw new Exception('获取Keycloak ACCESS_TOKEN 失败：' . $result);
        }
    }
    
    /**
     * 获取当前授权用户的OpenID
     * @return string OpenID
     */
    public function openid()
    {
        // 首先尝试从Token中获取
        if (isset($this->Token['openid']) && !empty($this->Token['openid'])) {
            return $this->Token['openid'];
        }
        
        // 从用户信息中获取sub作为openid
        $userinfo = $this->getUserInfo();
        return isset($userinfo['sub']) ? $userinfo['sub'] : '';
    }
}