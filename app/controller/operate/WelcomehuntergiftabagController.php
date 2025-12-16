<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Exception\ApiException;
use Imee\Service\Operate\WelcomehuntergiftabagService;

class WelcomehuntergiftabagController extends BaseController
{
    /** @var WelcomehuntergiftabagService */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new WelcomehuntergiftabagService();
    }
    
    /**
     * @page welcomehuntergiftabag
     * @name 批量下发任务
     */
    public function mainAction()
    {
    }
    
    /**
     * @page welcomehuntergiftabag
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
     * @page welcomehuntergiftabag
     * @point 审核
     * @logRecord(content = '审核', action = '1', model = 'welcomehuntergiftabag', model_id = 'id')
     */
    public function auditAction()
    {
        if (empty($this->params['id']) || empty($this->params['status'])) {
            throw new ApiException(ApiException::MSG_ERROR, '提交有误');
        }

        $data = $this->service->audit($this->params);
        return $this->outputSuccess($data);
    }
}