<?php

namespace Imee\Controller;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Comp\Common\Language\Service\LanguageService;
use Imee\Comp\Operate\Auth\Service\LoginService;
use Imee\Exception\ApiException;
use Imee\Service\Helper;

class BaseController extends \Phalcon\Mvc\Controller
{
    /** @var LoginService */
    protected $loginService;
    protected $lang;
    protected $guid;            // 唯一id
    protected $uid;             // 用户ID
    protected $allowSort = array();

    /**
     * @var array 请求所有参数
     */
    protected $params;

    //此类的控制器无需验证登录
    protected $notLoginNeed = array(
        'index'      => ['index'],
        'auth/login' => ['index', 'logout', 'qwindex', 'callback', 'jumpLogin'],
    );

    //不需要权限
    protected $notPermission = [
        'common/upload.image',
        'common/upload.video',
        'common/upload.voice',
        'common/upload.file',
        'lesscode/form.create',
        'lesscode/form.update',
        'lesscode/form.check',
        'lesscode/index.auth',
        'auth/staff.leftMenu',
        'auth/staff.menu',
        'auth/staff.permission',
        'auth/access.points',
        'auth/access.bigArea',
        'auth/access.submitApply',
        'auth/menu.index',
        'auth/access.getAreaList',
        'auth/notice.index',
        'forbidden/forbidden.config',
        'forbidden/forbidden.forbidden',
        'forbidden/psuserdeviceinfo.deviceforbidden',
        'auth/user.modifyPwd',
        
    ];

    // 不需要权限的控制器
    protected $notPermissionCtl = [
        'common/enum',
        'common/unittest',
        'common/apijsonsdktest',
        'common/export',
        'log/operatelog',
        // 'language/multilanguagemanage',
        'operate/activity/activity',

    ];

    // 需要翻译转换的权限点
    protected $translatePermission = [
        'auth/menu.index',
        'auth/access.points',
        'lesscode/index.listConfig',
    ];

    // 不需要记录日志
    protected $notLog = [
        'help' => ['getNewUsers']
    ];

    protected function onConstruct()
    {
        $this->loginService = new LoginService();
        $this->lang = $this->request->getHeader('Lang');
        $this->lang = $this->lang ?: 'zh_cn';
        $this->uid = $this->loginService->getLoginUid($this->request->getQuery('token', 'trim', ''));

        $this->params = array_merge(
            ['admin_uid' => $this->uid],
            ['admin_id' => $this->uid],
            ['lang' => $this->lang],
            ['page' => 1, 'limit' => 15],
            $this->request->getQuery(),
            $this->request->getPost(),
            $this->getFilter()
        );

        $body = $this->request->getRawBody();
        $body = @json_decode($body, true);
        if (is_array($body)) {
            $this->params = array_merge($this->params, $body);
        }

    }

    //兼容filter
    public function getFilter()
    {
        $data = [];
        $filter = $this->request->getQuery('filter', 'trim');
        if (!empty($filter)) {
            $req = @json_decode($filter, true);
            if ($req && is_array($req)) {
                foreach ($req as $val) {
                    if (!isset($val['property']) || !isset($val['value'])) {
                        continue;
                    }
                    $data[$val['property']] = $val['value'];
                }
            }
        }
        return $data;
    }

    public function beforeExecuteRoute()
    {   
        //开始处理权限控制问题
        $controller = $this->getController();
        $action = $this->dispatcher->getActionName();
        $map = $this->notLoginNeed;
        if (isset($map[$controller]) && in_array($action, $map[$controller])) {
            // 因为涉及到密码，所以这里要单独记录操作日志
            $post = array();
            if (isset($_POST["username"])) {
                $post["username"] = $_POST["username"];
            }
            $this->logger->warning('[ip,useid,url,get,post][' . $this->uid .
                '][' . $controller . '/' . $action . '],' .
                json_encode($_GET) . ',' . json_encode($post));

            return true;
        }

        // 记录操作日志
        if (!isset($this->notLog[$controller]) || !in_array($action, $this->notLog[$controller])) {
            $this->logger->warning('[ip,useid,url,get,post][' . $this->uid . '][' . $controller . '/' .
                $action . '],' . json_encode($_GET) . ',' . json_encode($_POST));
        }

        //验证权限
        $purviewName = $controller . '.' . $action;
        if (method_exists($this, 'checkAutoMenu')) {
            $this->checkAutoMenu($purviewName);
        }
        return $this->loginService->checkPermission($this->uid, $purviewName, $this->notPermission, $this->notPermissionCtl);
    }

    protected function getController()
    {
        $nameSpace = $this->dispatcher->getNamespaceName();
        $controller = $this->dispatcher->getControllerName();

        $preArr = explode('\\', trim(str_replace('Imee\Controller', '', $nameSpace), '\\'));
        $pre = implode('/', array_map(function ($val) {
            return lcfirst($val);
        }, $preArr));

        if (!empty($pre)) {
            $controller = $pre . '/' . $controller;
        }

        return $controller;
    }

    protected function getPost()
    {
        $res = @file_get_contents("php://input");
        if (!empty($res)) {
            $data = json_decode($res, true);
            if (is_array($data)) {
                return $data;
            }

            //兼容传递非json
            parse_str(urldecode($res), $output);
            return $output;
        }
        return [];
    }

    protected function redirect($url)
    {
        if (0 && $this->request->isAjax()) {
            //jsonp获取不到http header
            $this->response->setHeader('Json-Status', '302');
            return $this->response->setHeader('Json-Location', $this->url->get($url));
        } else {
            $prefix = substr($url, 0, 7);
            $isAbsolute = $prefix == 'http://' || $prefix == 'https:/' || substr($url, 0, 1) == '/';
            $url = $this->url->get($url, null, !$isAbsolute);
            return $this->response->redirect($url, true);
        }
    }

    protected function outputJson($data)
    {
        $data = $this->translateOutput($data);
        $out = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($out === false && $data && json_last_error() == JSON_ERROR_UTF8) {
            $out = json_encode($this->utf8ize($data), JSON_UNESCAPED_UNICODE);
        }
        return $this->response->setContent($out);
    }

    protected function translateOutput($data): array
    {
        $controller = $this->getController();
        $action = $this->dispatcher->getActionName();
        if (class_exists('Imee\Comp\Common\Language\Service\LanguageService')
            && in_array($controller . '.' . $action, $this->translatePermission)) {
            $data = LanguageService::translateData($data, $this->params);
        }

        return $data;
    }

    private function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
        }
        return $mixed;
    }

    protected function outputSuccess($data = null, $options = null)
    {
        $array = array(
            'success' => true,
            'code'    => 0,
            'data'    => $data,
            'msg'     => '',
        );
        if ($options) {
            $array = array_merge($array, $options);
        }
        return $this->outputJson($array);
    }

    protected function outputError($code, $msg = null, $options = null)
    {
        $array = array(
            'success' => false,
            'code'    => $code,
            'msg'     => $msg
        );
        if ($options) {
            $array = array_merge($array, $options);
        }
        return $this->outputJson($array);
    }

    protected function responseHTML(string $html)
    {
        $this->response->setHeader('Content-type', 'text/html; charset=utf-8');
        return $this->response->setContent($html);
    }

    /**
     * 导出
     * @param $filePrefix
     * @param $class
     * @param array $paramData
     * @param string $title
     * @return void
     * @throws ApiException
     */
    protected function syncExportWork($filePrefix, $class, array $paramData = [], $title = '')
    {
        //兼容低代码
        if (strstr($class, '.lesscode.export')) {
            $class = '\Imee\Service\Lesscode\CurdService';
        }

        // 验证类是否存在
        if (!class_exists($class)) {
            throw new ApiException(ApiException::MSG_ERROR, "Class \"$class\" does not exist.");
        }

        // 兼容下之前的标题
        if (empty($title)) {
            $title = $filePrefix;
        }

        $class = new $class();
        ExportService::addTask($this->uid, $filePrefix . '.csv', [$class, 'export'], $paramData, $title);

        // 兼容无需输出html，直接跳转到导出列表页
        if ($this->request->getQuery('jump_export_list')) {
            exit(json_encode(['success' => true, 'msg' => 'ok', 'code' => 0]));
        }

        if ($this->request->getQuery('no_echo_html')) {
            exit(json_encode(['success' => false, 'msg' => 'Please go to the export list to view', 'code' => 100]));
        }

        ExportService::showHtml();
    }

    protected function trimParams(array $params)
    {
        return Helper::trimParams($params);
    }
}
