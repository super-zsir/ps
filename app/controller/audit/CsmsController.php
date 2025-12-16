<?php


namespace Imee\Controller\Audit;

use Imee\Controller\BaseController;
use Imee\Exception\Audit\AuditWorkbenchException;
use Imee\Service\Domain\Service\Csms\CsmsBenchService;

/**
 * 审核工作台
 * Class CsmsController
 * @package Imee\Controller\Audit
 */
class CsmsController extends BaseController
{
    public $params;

    public function onConstruct()
    {
        parent::onConstruct();
        $get = $this->request->getQuery();
        $post = $this->request->getPost();
        $this->params = array_merge(
            ['admin' => $this->uid, 'lang' => $this->lang],
            $get,
            $post
        );
    }

    /**
     * @page csms
     * @name 审核系统-内容安全-审核工作台
     * @point 内容安全工作台
     */
    public function indexAction()
    {
        $ref = new \ReflectionObject($this);
        $handle = (isset($this->params['handle']) && $this->params['handle']) ? $this->params['handle'] : '';
        if (!$handle) {
            AuditWorkbenchException::throwException(AuditWorkbenchException::HANDLE_NOT_EXIST);
        }
        if ($ref->hasMethod($handle)) {
            return $this->{$handle}();
        } else {
            return $this->special();
        }
    }

    /**
     * @desc 用户审核模块权限
     */
    public function config()
    {
        $service = new CsmsBenchService();
        $res = $service->userModule($this->params);
        return $this->outputSuccess($res);
    }


    /**
     * @desc 工作台列表
     */
    public function workmodule()
    {
        $service = new CsmsBenchService();
        $res = $service->workModule($this->params);
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }

    /**
     * @desc 获取配置
     */
    public function getconfig()
    {
        $service = new CsmsBenchService();
        $config = $service->getConfig($this->params);
        return $this->outputSuccess($config);
    }


    /**
     * @desc 获取新任务
     */
    public function gettask()
    {
        $service = new CsmsBenchService();
        $res = $service->getTask($this->params);
        return $this->outputSuccess($res);
    }


    /**
     * @desc 清除任务
     */
    public function cleartask()
    {
        $service = new CsmsBenchService();
        $res = $service->clearTask($this->params);
        return $this->outputSuccess($res);
    }


    /**
     * @desc 任务列表
     */
    public function tasklist()
    {
        $service = new CsmsBenchService();
        $res = $service->tasklist($this->params);
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }


    /**
     * @desc 审核操作（通过或清空处理）
     */
    public function multpass()
    {
        $service = new CsmsBenchService();
        $res = $service->multPass($this->params);
        return $this->outputSuccess($res);
    }


    /**
     * @desc 类型工作台
     */
    public function typebench()
    {
        $service = new CsmsBenchService();
        $res = $service->typeBench($this->params);
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }


    /**
     * 获取审核项附加详情
     */
    public function attach()
    {
        $service = new CsmsBenchService();
        $res = $service->attach($this->params);
        return $this->outputSuccess($res);
    }


    /**
     * @desc 日志
     */
//	public function history()
//	{
//		$service = new StaffService();
//		$res = $service->history($this->params);
//		return $this->outputSuccess($res['data'], ['total' => $res['total']]);
//	}


    /**
     * @desc 模块特殊处理
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
//	public function special()
//	{
//		$service = new StaffService();
//		$res = $service->special($this->params);
//		return $this->outputJson($res);
//	}
}
