<?php

namespace Imee\Controller\Operate\Pop;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Pop\PopRecommendCreateValidation;
use Imee\Controller\Validation\Operate\Pop\PopRecommendDeleteValidation;
use Imee\Controller\Validation\Operate\Pop\PopRecommendModifyValidation;
use Imee\Service\Operate\Pop\PopRecommendService;

/**
 * 推荐位配置
 */
class PoprecommendController extends BaseController
{
    /**
     * @var PopRecommendService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PopRecommendService();
    }
    
    /**
     * @page poprecommend
     * @name 每日签到弹窗推荐位
     */
    public function mainAction()
    {
    }
    
    /**
     * @page poprecommend
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            return $this->outputSuccess($this->service->getOptions());
        } else if ($c == 'info') {
            return $this->outputSuccess($this->service->info($this->params));
        }
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
     * @page poprecommend
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'poprecommend', model_id = 'id')
     */
    public function createAction()
    {
        PopRecommendCreateValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page poprecommend
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'poprecommend', model_id = 'id')
     */
    public function modifyAction()
    {
        PopRecommendModifyValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page poprecommend
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'poprecommend', model_id = 'id')
     */
    public function deleteAction()
    {
        PopRecommendDeleteValidation::make()->validators($this->params);
        $data = $this->service->delete($this->params);
        return $this->outputSuccess($data);
    }
}