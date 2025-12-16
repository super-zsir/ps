<?php

use Imee\Comp\Common\Fixed\Ip2Region;

if (!function_exists('session_status')) {
    function session_status()
    {
        if (ini_get('session.auto_start')) {
            return 2;
        }
        return 1;
    }
}

if (!function_exists("xcache_get")) {
    function xcache_get($key)
    {
        return apcu_fetch($key);
    }

    function xcache_set($key, $value, $ttl = 3600)
    {
        return apcu_add($key, $value, $ttl);
    }

    function xcache_unset($key)
    {
        return apcu_delete($key);
    }

    function xcache_isset($key)
    {
        return apcu_exists($key);
    }

    function xcache_inc($key, $value)
    {
        return apcu_inc($key, $value);
    }

    function xcache_dec($key, $value)
    {
        return apcu_dec($key, $value);
    }

    function xcache_clear_cache()
    {
        return apcu_clear_cache();
    }
}

if (!function_exists("add_tmp_log")) {
    function add_tmp_log($content, $filename = '', $format = '')
    {
        if (empty($filename)) {
            $filePath = '/home/log/admin_' . SYSTEM_FLAG . '_' . date('Ymd') . '.log';
        } else {
            $filePath = '/home/log/admin_' . SYSTEM_FLAG . '_' . date('Ymd') . '_' . $filename;
        }
        if (!is_scalar($content)) {
            switch ($format) {
                case 'json':
                    $content = json_encode($content, JSON_UNESCAPED_UNICODE);
                    break;
                case 'serialize':
                    $content = serialize($content);
                    break;
                default:
                    $content = var_export($content, true);
            }
        }

        file_put_contents($filePath, $content . PHP_EOL, FILE_APPEND);
    }
}

if (!function_exists('is_safe_origin')) {
    function is_safe_origin($url)
    {
        if (!$url) {
            return false;
        }

        $safeUrls = [
            "/\.iambanban\.com/i",
            "/\.yinjietd\.com/i",
            "/\.aopacloud\.sg/i",
            "/\.aopacloud\.net/i",
        ];

        if (defined('SAFE_ORIGINS')) {
            $safeExpand = SAFE_ORIGINS;
            $safeExpand && $safeUrls = array_merge($safeUrls, $safeExpand);
        }

        foreach ($safeUrls as $safeUrl) {
            if (preg_match($safeUrl, $url)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('add_header_origin')) {
    function add_header_origin()
    {
        $allowedHeaders = 'x-requested-with,content-type,user-token,User-Language,jwt-token,websession,ur-lang';
        $exposeHeaders = 'date,CMS-LOGIN';

        // 安全检查HTTP Origin
        $origin = isset($_SERVER['HTTP_ORIGIN']) && is_safe_origin($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;

        if ($origin) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: ' . $allowedHeaders);
            header('Access-Control-Expose-Headers: ' . $exposeHeaders);
            header('Content-Type: text/html; Charset=utf-8');  // 设置通用头放在最后
        } else {
            // 如果HTTP Origin不安全或缺失，不发送跨域相关的头
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // 对OPTIONS请求直接返回header信息
        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, HEAD, OPTIONS');
            header('Access-Control-Max-Age: 600');
            exit;
        }
    }
}

if (!function_exists('fastcgi_finish_request')) {
    function fastcgi_finish_request()
    {
        if (PHP_SAPI == 'cli') {
            exit('不支持命令行使用');
        }
        header("Connection: close\r\n");
        header("Content-Encoding: none\r\n");
        header("Content-Length: " . ob_get_length() . "\r\n");
        ob_end_flush();
        flush();
    }
}

if (!function_exists('finish_request_response')) {
    function finish_request_response($data = [])
    {
        if (PHP_SAPI == 'cli') {
            exit('不支持命令行使用');
        }
        add_header_origin();
        echo json_encode([
            'success' => true,
            'code'    => 0,
            'msg'     => '',
            'data'    => $data,
        ]);
        fastcgi_finish_request();
    }
}

if (!function_exists('create_uuid')) {
    function create_uuid($prefix = "")
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8) . '-'
            . substr($chars, 8, 4) . '-'
            . substr($chars, 12, 4) . '-'
            . substr($chars, 16, 4) . '-'
            . substr($chars, 20, 12);
        return $prefix . $uuid;
    }
}

//通过文件内容获取完整class Imee\Controller\Lesscode\FormController
if (!function_exists('get_class_from_file')) {
    function get_class_from_file($file)
    {
        $contents = file_get_contents($file);
        $namespace = $class = '';
        $gettingNamespace = $gettingClass = false;
        foreach (token_get_all($contents) as $token) {
            if (is_array($token) && $token[0] == T_NAMESPACE) {
                $gettingNamespace = true;
            }

            if (is_array($token) && $token[0] == T_CLASS) {
                $gettingClass = true;
            }

            //namespace name...
            if ($gettingNamespace === true) {
                //If the token is a string or the namespace separator...
                if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {
                    $namespace .= $token[1];
                } elseif ($token === ';') {
                    $gettingNamespace = false;
                }
            }
            //class name ...
            if ($gettingClass === true) {
                //If the token is a string, it's the name of the class
                if (is_array($token) && $token[0] == T_STRING) {
                    //Store the token's value as the class name
                    $class = $token[1];
                    break;
                }
            }
        }
        return $namespace ? $namespace . '\\' . $class : $class;
    }
}

if (!function_exists('get_address_by_ipv4')) {
    /**
     * Notes: 根据Ip获取地址,只支持ipv4
     * @param $ip
     * @return mixed|string 中国|0|黑龙江|哈尔滨|移动
     * @throws Exception
     */
    function get_address_by_ipv4($ip)
    {
        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return '';
        }
        $sdkBase = new \Imee\Comp\Common\Sdk\SdkBase();
        if (!defined('Local_Server_Ip')) {
            $url = 'http://127.0.0.1:7799';
        } else {
            $url = Local_Server_Ip;
        }
        [$response, $httpCode] = $sdkBase->call($url, 'GET', ['query' => ['ipv4' => $ip]]);
        if ($httpCode != 200) {
            return '';
        }
        $response = json_decode($response, true);
        return $response['data'] ?? '';
    }
}

if (!function_exists('get_ip_address_batch')) {
    /**
     * Notes: 根据Ip批量获取地址
     * @param array $ip
     * @param string $area 接口服务所在地区国家码 SG.新加坡  DE.德国
     * @return array eg: [{'ip': '223.76.184.188','continent': '亚洲','country': '中国', 'province': '湖北省', 'lat': 30.589, 'lon': 114.2681, 'strategy': 0}]
     */
    function get_ip_address_batch($ip, $area = 'SG')
    {
        if (empty($ip)) {
            return [];
        }
        $area = strtoupper($area);
        if (ENV == 'dev') {
            $config = ['url' => 'https://rtc-dev-mid.aopacloud.net:6480/ip/geo/batch', 'key' => '123', 'appid' => 123];
        } elseif ($area == 'DE') {
            $config = ['url' => 'http://bc-ip-geo-de.aopacloud.private:8300/ip/geo/batch', 'key' => 'g8joe9q86l3dy9uv', 'appid' => 888];
        } else {
            $config = ['url' => 'http://bc-ip-geo.aopacloud.private:8300/ip/geo/batch', 'key' => 'g8joe9q86l3dy9uv', 'appid' => 888];
        }
        $sdkBase = new \Imee\Comp\Common\Sdk\SdkBase();
        // 分批查询
        $limit = 50;
        $ipAddress = [];
        foreach (array_chunk($ip, $limit) as $items) {
            [$result, $httpCode] = $sdkBase->call(
                $config['url'],
                'POST',
                ['json' => ['key' => $config['key'], 'appid' => $config['appid'], 'ips' => $items]]
            );
            if ($httpCode != 200) {
                continue;
            }
            $address = @json_decode($result, true) ?? [];
            $ipAddress = array_merge($ipAddress, array_column($address, null, 'ip'));
            count($items) == $limit && usleep(50000);
        }
        return $ipAddress;
    }
}

/**
 * comp 组件化的task 脚本加载
 */
if (!function_exists('task_dir_load')) {
    function task_dir_load($params, $loader)
    {
        $taskDir = $params['task_dir_load'] ?? '';
        if ($taskDir) {
            // 如果加载组件化task ，默认只加载项目的cliapp 一个基础文件
            $loader->registerFiles([
                ROOT . DS . 'cli/tasks/CliApp.php',   //tasks目录需要引入，无法使用注册命名空间引入
            ]);
            $loader->registerDirs(array(
                ROOT . DS . $taskDir,
            ));
        } else {
            $tasksDir = defined('TASKS_DIRS') ? TASKS_DIRS : [ROOT . DS . 'cli/tasks/'];
            $loader->registerDirs($tasksDir, true);
        }
    }
}

if (!function_exists('get_base_url')) {
    function get_base_url()
    {
        $schema = ENV === 'dev' ? 'http' : 'https';

        // 从代理头或服务器环境变量中获取主机名
        $host = !empty($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST'];

        // 如果在本地开发环境中有特定的配置，直接返回定义的 BASE_URL
        if (strpos($host, 'private.local') !== false) {
            return BASE_URL;
        }

        // 确定协议
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $schema = 'https';
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $schema = $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ? 'https' : 'http';
        }

        // 检查端口，优先从 X-Forwarded-Port 中获取
        $port = '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
            $port = $_SERVER['HTTP_X_FORWARDED_PORT'];
        } elseif (isset($_SERVER['SERVER_PORT'])) {
            $port = $_SERVER['SERVER_PORT'];
        }

        // 省略标准的 HTTP 和 HTTPS 端口
        if ($port && !($port == 80 || $port == 443)) {
            $port = ':' . $port;
        } else {
            $port = '';
        }

        // 返回完整的 URL
        return $schema . '://' . $host . $port . '/';
    }
}

if (!function_exists('get_browser_info')) {
    function get_browser_info()
    {
        $ua = $_SERVER['HTTP_USER_AGENT'];

        // 获取浏览器信息
        $browser = 'Unknown Browser';
        if (strpos($ua, 'MSIE') !== false) {
            $browser = 'Internet Explorer';
        } elseif (strpos($ua, 'Firefox') !== false) {
            $browser = 'Mozilla Firefox';
        } elseif (strpos($ua, 'Chrome') !== false) {
            $browser = 'Google Chrome';
        } elseif (strpos($ua, 'Safari') !== false) {
            $browser = 'Apple Safari';
        } elseif (strpos($ua, 'Opera') !== false) {
            $browser = 'Opera';
        }

        // 获取操作系统信息
        $os = 'Unknown OS';
        if (strpos($ua, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (strpos($ua, 'Macintosh') !== false) {
            $os = 'Macintosh';
        } elseif (strpos($ua, 'Linux') !== false) {
            $os = 'Linux';
        } elseif (strpos($ua, 'Unix') !== false) {
            $os = 'Unix';
        }

        return ['browser' => $browser, 'os' => $os];
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        $regex = '/' . preg_quote($needle, '/') . '$/';
        return preg_match($regex, $haystack) > 0;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($string, $startString) {
        $regex = '/^' . preg_quote($startString, '/') . '/';
        return preg_match($regex, $string) === 1;
    }
}
