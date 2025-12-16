<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\WelcomehuntergiftabagService;

class WelcomehuntergiftabagdetailController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }
    
    /**
     * @page welcomehuntergiftabagdetail
     * @name 批量下发任务明细
     */
    public function mainAction()
    {
    }
    
    /**
     * @page welcomehuntergiftabagdetail
     * @point 列表
     */
    public function listAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputSuccess();
        }

        $data = (new WelcomehuntergiftabagService())->getConfigList((int)$this->params['id'], $this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
}