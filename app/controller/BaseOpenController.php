<?php

namespace Imee\Controller;

use Imee\Exception\ApiException;

/**
 * 开放接口基类
 */
class BaseOpenController extends \Phalcon\Mvc\Controller
{
    //接口验证ak=>sk
    private $auth = [
        'risk' => 'C0kSvDGzx5BlgTol',
        'chat' => 'I39S4CGzx7YUDl24',
    ];

    protected function onConstruct()
    {
        if ($this->session->status() != 2) {
            @$this->session->start();
        }
    }

    /**
     * Basic base64_encode(ak:md5(ak:sk:time):time)
     *
     * @throws ApiException
     */
    public function checkAuth(): bool
    {
        $auth = $this->request->getHeader('Authorization');
        if (!preg_match("/Basic\s+(.*)$/i", $auth, $matches)) {
            throw new ApiException(ApiException::VALIDATION_ERROR, '权限校验无效');
        }
        list($ak, $token, $time) = explode(':', base64_decode($matches[1]), 3);
        $authConfig = $this->auth;
        if (!isset($authConfig[$ak])) {
            throw new ApiException(ApiException::NO_PERMISS_ERROR, 'ak无效');
        }
        if (md5($ak . ':' . $authConfig[$ak] . ':' . $time) !== trim($token)) {
            throw new ApiException(ApiException::NO_PERMISS_ERROR, 'sk错误');
        }
        if (ENV != 'dev' && $time + 300 < time()) {
            throw new ApiException(ApiException::TOKEN_INVALID_ERROR);
        }
        return true;
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

    private function getJsonp()
    {
        return $this->request->getQuery('callback');
    }

    protected function outputJson($data)
    {
        if ($cp = $this->getJsonp()) {
            return $this->response->setContent($cp . '(' . json_encode($data) . ');');
        } else {
            return $this->response->setJsonContent($data);
        }
    }

    protected function getOutputSuccess($data = null, $options = null)
    {
        $array = array(
            'success' => true,
            'data'    => $data
        );
        if ($options) $array = array_merge($array, $options);
        return $array;
    }

    protected function outputSuccess($data = null, $options = null)
    {
        return $this->outputJson($this->getOutputSuccess($data, $options));
    }

    protected function outputError($msg = null, $options = null)
    {
        $array = array(
            'success' => false,
            'msg'     => $msg
        );
        if ($options) $array = array_merge($array, $options);
        return $this->outputJson($array);
    }
}
