<?php

namespace Imee\Comp\Common\Sdk;

use Config\ConfigCompanyWechat;
use Imee\Comp\Common\Log\LoggerProxy;

class SdkCommonLogin extends SdkBase
{
    private $domain;

    const CLIENT_ID = '4941b4ed341dfb1142eb5be0408edfa4';
    const LOGIN_CALLBACK = '/auth/login/callback';
    const CLIENT_ID_DEV = 'b6236c0a3bf61f1badbd0e00fa96aa1e';
    const CLIENT_SECRET = '286cec486fd06dafa37d7f464b864b3b9b062a08';
    const CLIENT_SECRET_DEV = '5f7c02dffc889ba5d4070d10c073838296f2deea';

    const DOMAIN = 'https://uc.aopacloud.sg';
    const DOMAIN_DEV = 'http://192.168.11.121:8864';

    public function __construct($format = SdkBase::FORMAT_JSON, $timeout = 10, $waning = 0.1)
    {
        parent::__construct($format, $timeout, $waning);
        //兼容配置
        if (defined('UC_DOMAIN')) {
            $this->domain = UC_DOMAIN;
        } else {
            $this->domain = self::DOMAIN;
        }
    }

    public function login()
    {
        // 兼容react和ext重定向
        $baseUrl = get_base_url();
        $redirect = urlencode($baseUrl . API_PREFIX . self::LOGIN_CALLBACK);
        return $this->domain . "/su/login?client_id=" . self::CLIENT_ID . "&redirect_url=" . $redirect;
    }

    /**
     * 获取登录用户信息
     * @param string $code
     * @return mixed
     */
    public function getLoginUserInfoByCode(string $code)
    {
        $url = $this->domain . '/ucapi/external/v1/loginApi/loginCode?login_new=1&uc_token=' . $code . "&client_id=" . self::CLIENT_ID;
        $res = $this->request($url, false);
        if (is_array($res) && $res['success'] && $res['code'] == 1) {
            return $res['data']['user'];
            // return [true, $res['data']['user']];
        }
        LoggerProxy::instance()->warning('rep:' . is_array($res) ? json_encode($res) : $res);
        return [];
        // return [false, isset($res['msg']) ? $res['msg'] : ''];
    }

    public function getAccessToken()
    {
        if (ENV == 'dev') {
            $url = self::DOMAIN_DEV . '/external/v1/authApi/getToken';
        } else {
            $url = $this->domain . '/ucapi/external/v1/authApi/getToken';
        }

        $params = [
            'client_id'     => ENV == 'dev' ? self::CLIENT_ID_DEV : self::CLIENT_ID,
            'client_secret' => ENV == 'dev' ? self::CLIENT_SECRET_DEV : self::CLIENT_SECRET
        ];

        $res = $this->request($url, true, $params);

        if (is_array($res) && $res['success'] && $res['code'] == 1) {
            return $res['data'];
        }

        LoggerProxy::instance()->warning('rep:' . is_array($res) ? json_encode($res) : $res);
        return '';
    }

    public function getLeaveUsers()
    {
        $token = $this->getAccessToken();
        if (empty($token)) {
            LoggerProxy::instance()->warning('LeaveUsers get access_token error');
            return [];
        }

        if (ENV == 'dev') {
            $url = self::DOMAIN_DEV . '/external/v1/userApi/resignUser';
        } else {
            $url = $this->domain . '/ucapi/external/v1/userApi/resignUser';
        }

        $params = [
            'client_id'    => ENV == 'dev' ? self::CLIENT_ID_DEV : self::CLIENT_ID,
            'access_token' => $token['access_token'],
            'timestamp'    => $this->getTotalMillisecond(),
            'nonce'        => mt_rand(10000, 99999),
        ];
        $params['signature'] = $this->getSignature($params);

        $res = $this->request($url, true, $params);
        if (is_array($res) && $res['success'] && $res['code'] == 1) {
            return $res['data'];
        }
        LoggerProxy::instance()->warning('LeaveUsers get list error, rep:' . is_array($res) ? json_encode($res) : $res);
        return [];
    }

    public function getLeaveUserCallback($jobNum, $systemName): bool
    {
        $token = $this->getAccessToken();
        if (empty($token)) {
            LoggerProxy::instance()->warning(__FUNCTION__ . ' get access_token error');
            return false;
        }
        if (ENV == 'dev') {
            $url = self::DOMAIN_DEV . '/external/v1/userApi/resignCallback';
        } else {
            $url = $this->domain . '/ucapi/external/v1/userApi/resignCallback';
        }

        $params = [
            'client_id'    => ENV == 'dev' ? self::CLIENT_ID_DEV : self::CLIENT_ID,
            'access_token' => $token['access_token'],
            'timestamp'    => $this->getTotalMillisecond(),
            'nonce'        => mt_rand(10000, 99999),
            'job_num'      => $jobNum,
            'system_name'  => $systemName,
            'app_id'       => defined('APP_ID') ? APP_ID : -1,
        ];
        $params['signature'] = $this->getSignature($params);
        $res = $this->request($url, true, $params);
        if (is_array($res) && $res['success'] && $res['code'] == 1) {
            return true;
        }
        LoggerProxy::instance()->warning(__FUNCTION__ . ' error, rep:' . is_array($res) ? json_encode($res) : $res);
        return false;
    }

    private function getSignature($params): string
    {
        if (empty($params)) {
            return '';
        }

        ksort($params);
        $str = implode('', $params);
        return sha1($str);
    }

    /**
     * 获取时间戳（毫秒级）
     * @return int
     */
    private function getTotalMillisecond(): int
    {
        $time = explode(" ", microtime());
        return (int)sprintf('%.0f', (floatval($time[0]) + floatval($time[1])) * 1000);
    }
}
