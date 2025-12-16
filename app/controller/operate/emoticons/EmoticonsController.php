<?php

namespace Imee\Controller\Operate\Emoticons;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Emoticons\Emoticons\EmoticonsAddValidation;
use Imee\Controller\Validation\Operate\Emoticons\Emoticons\EmoticonsEditValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Xs\XsEmoticons;
use Imee\Service\Helper;
use Imee\Service\Operate\Emoticons\EmoticonsService;

class EmoticonsController extends BaseController
{
    use ImportTrait;
    /**
     * @var EmoticonsService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new EmoticonsService();
    }
    
    /**
	 * @page emoticons
	 * @name 表情包上架
	 */
    public function mainAction()
    {
    }
    
    /**
	 * @page emoticons
	 * @point 列表
	 */
    public function listAction()
    {
        $data = $this->service->getList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
	 * @page emoticons
	 * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'emoticons', model_id = 'id')
	 */
    public function createAction()
    {
        EmoticonsAddValidation::make()->validators($this->params);
        $data = $this->service->add($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
	 * @page emoticons
	 * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'emoticons', model_id = 'id')
	 */
    public function modifyAction()
    {
        EmoticonsEditValidation::make()->validators($this->params);
        $data = $this->service->edit($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
	 * @page emoticons
	 * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'emoticons', model_id = 'id')
	 */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        $this->service->status($id, XsEmoticons::DELETE_STATUS);
        return $this->outputSuccess(['id' => $id, 'after_json' => []]);
    }

    /**
     * @page emoticons
     * @point 上架
     * @logRecord(content = '上架', action = '1', model = 'emoticons', model_id = 'id')
     */
    public function putAction()
    {
        $id = $this->params['id'] ?? 0;
        $this->service->status($id, XsEmoticons::LISTED_STATUS);
        return $this->outputSuccess(['id' => $id, 'after_json' => ['status' => XsEmoticons::LISTED_STATUS]]);
    }

    /**
     * @page emoticons
     * @point 下架
     * @logRecord(content = '下架', action = '1', model = 'emoticons', model_id = 'id')
     */
    public function lowerAction()
    {
        $id = $this->params['id'] ?? 0;
        $this->service->status($id, XsEmoticons::NOT_LISTED_STATUS);
        return $this->outputSuccess(['id' => $id, 'after_json' => ['status' => XsEmoticons::NOT_LISTED_STATUS]]);
    }

    /**
     * @page emoticons
     * @point 上传ID
     */
    public function uploadAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'tpl') {
            (new Csv())->exportToCsv(['ID', '上传时需删除表头'], [], 'file');
            exit;
        }
        list($result, $msg, $data) = $this->uploadCsv(['id']);
        if (!$result) {
            return [false, $msg, []];
        }
        $ids = Helper::arrayFilter($data['data'], 'id');

        if (count($ids) > 500) {
            return [false, '当前最大限制为500个id, 请分批发放', []];
        }
        if (empty($ids)) {
            return [false, '上传id数据为空', []];
        }

        return $this->outputSuccess([
            'name' => implode(',', $ids)
        ]);
    }

    /**
     * @page emoticons
     * @point 获取可用人群map
     */
    public function getIdentityMapAction()
    {
        return $this->outputSuccess($this->service->getIdentityMap($this->params['group_id']));
    }

    /**
     * @page emoticons
     * @point 下架前校验
     */
    public function checkAction()
    {
        $id = $this->params['id'] ?? 0;
        return $this->outputSuccess($this->service->check($id));
    }
}