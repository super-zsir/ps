<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\IssuedRankAnonymousPrivilegeService;

class IssuedrankanonymousprivilegeController extends BaseController
{
    use ImportTrait;

    /**
     * @var IssuedRankAnonymousPrivilegeService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new IssuedRankAnonymousPrivilegeService();
    }

    /**
     * @page issuedrankanonymousprivilege
     * @name 贡献榜单匿名权益
     */
    public function mainAction()
    {
    }

    /**
     * @page issuedrankanonymousprivilege
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->trimParams($this->params);
        // 校验逻辑，防止生成logRecord
        $c = $params['c'] ?? '';
        if ($c === 'check') {
            $result = $this->service->checkCreate($params);
            if (isset($result['is_info']) && $result['is_info'] === true) {
                return $this->outputSuccess(['is_info' => true, 'confirm_text' => $result['msg'], 'width' => 700]);
            } elseif (isset($result['is_confirm']) && $result['is_confirm'] === true) {
                return $this->outputSuccess(['is_confirm' => true, 'confirm_text' => $result['msg'], 'width' => 700]);
            }
            return $this->outputSuccess(['is_confirm' => false]);
        }
        $result = $this->service->getList($params);
        return $this->outputSuccess($result['data'] ?? [], ['total' => $result['total'] ?? 0]);
    }

    /**
     * @page issuedrankanonymousprivilege
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'issuedrankanonymousprivilege', model_id = 'id')
     */
    public function createAction()
    {
        $params = $this->trimParams($this->params);
        $data = $this->service->create($params);
        return $this->outputSuccess($data);
    }
}
