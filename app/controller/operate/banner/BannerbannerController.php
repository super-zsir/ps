<?php

namespace Imee\Controller\Operate\Banner;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Banner\CreateShopValidation;
use Imee\Controller\Validation\Operate\Banner\ModifyShopValidation;
use Imee\Service\Operate\BannerService;

class BannerbannerController extends BaseController
{
    /** @var BannerService $service */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new BannerService();
    }

    /**
     * @page bannerbanner
     * @name 运营系统-banner管理-首页banner
     */
    public function mainAction()
    {
    }

    /**
     * @page  bannerbanner
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->params;
        $c = trim($params['c'] ?? '');
        $params['position'] = 'live';

        switch ($c) {
            case 'preview':
                return $this->outputSuccess($this->service->getPreview($params));
            default:
                $result = $this->service->getListAndTotal(
                    $params, 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15
                );
                return $this->outputSuccess($result['data'], ['total' => $result['total']]);
        }
    }

    /**
     * @page  bannerbanner
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'bannerbanner', model_id = 'id')
     */
    public function createAction()
    {
        CreateShopValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);

        return $this->outputSuccess($data);
    }

    /**
     * @page  bannerbanner
     * @point 详情
     */
    public function infoAction()
    {
        if (empty($this->params['id']) || $this->params['id'] < 1) {
            return $this->outputSuccess();
        }
        return $this->outputSuccess($this->service->getInfo((int)$this->params['id']));
    }

    /**
     * @page  bannerbanner
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'bannerbanner', model_id = 'id')
     */
    public function modifyAction()
    {
        ModifyShopValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);

        return $this->outputSuccess($data);
    }
}