<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\OpenScreenValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Helper;
use Imee\Service\Operate\OpenScreenService;

class OpenscreenController extends BaseController
{
    use ImportTrait;

    /**
     * @var OpenScreenService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new OpenScreenService();
    }

    /**
     * @page openscreen
     * @name 开屏页配置
     */
    public function mainAction()
    {
    }

    /**
     * @page openscreen
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }

    /**
     * @page openscreen
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'openscreen', model_id = 'id')
     */
    public function createAction()
    {
        OpenScreenValidation::make()->validators($this->params);
        $data = $this->service->add($this->trimParams($this->params));
        return $this->outputSuccess($data);
    }

    /**
     * @page openscreen
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'openscreen', model_id = 'id')
     */
    public function modifyAction()
    {
        OpenScreenValidation::make()->validators($this->params);
        $data = $this->service->edit($this->trimParams($this->params));
        return $this->outputSuccess($data);
    }

    /**
     * @page openscreen
     * @point 禁用
     * @logRecord(content = '禁用', action = '1', model = 'openscreen', model_id = 'id')
     */
    public function disableAction()
    {
        $data = $this->service->disable($this->params['id'] ?? 0);
        return $this->outputSuccess($data);
    }

    /**
     * @page openscreen
     * @point 上传特定用户uid
     */
    public function uploadAction()
    {
        list($result, $msg, $data) = $this->uploadCsv(['uid']);
        if (!$result) {
            return [false, $msg, []];
        }
        $uids = Helper::arrayFilter($data['data'], 'uid');

        if (count($uids) > 500) {
            return [false, '当前最大限制为500个uid, 请分批发放', []];
        }
        if (empty($uids)) {
            return [false, '上传uid数据为空', []];
        }

        return $this->outputSuccess([
            'name' => implode(',', $uids)
        ]);
    }
}