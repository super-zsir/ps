<?php

namespace Imee\Controller\Operate\Emoticons;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Emoticons\Material\EmoticonsMaterialAddValidation;
use Imee\Controller\Validation\Operate\Emoticons\Material\EmoticonsMaterialEditValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\Emoticons\EmoticonsMaterialService;

class EmoticonsmaterialController extends BaseController
{
    use ImportTrait;
    /**
     * @var EmoticonsMaterialService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new EmoticonsMaterialService();
    }
    
    /**
	 * @page emoticonsmaterial
	 * @name 表情包素材配置
	 */
    public function mainAction()
    {
    }
    
    /**
	 * @page emoticonsmaterial
	 * @point 列表
	 */
    public function listAction()
    {
        $data = $this->service->getList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
	 * @page emoticonsmaterial
	 * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'emoticonsmaterial', model_id = 'id')
	 */
    public function createAction()
    {
        $params = $this->service->formatParams($this->params);
        EmoticonsMaterialAddValidation::make()->validators($params);
        $data = $this->service->add($params);
        return $this->outputSuccess($data);
    }
    
    /**
	 * @page emoticonsmaterial
	 * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'emoticonsmaterial', model_id = 'id')
	 */
    public function modifyAction()
    {
        $params = $this->service->formatParams($this->params);
        EmoticonsMaterialEditValidation::make()->validators($params);
        $data = $this->service->edit($params);
        return $this->outputSuccess($data);
    }
    
    /**
	 * @page emoticonsmaterial
	 * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'emoticonsmaterial', model_id = 'id')
	 */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        $this->service->delete($id);
        return $this->outputSuccess(['id' => $id, 'after_json' => []]);
    }

    /**
     * @page emoticonsmaterial
     * @point 详情
     */
    public function infoAction()
    {
        $id = $this->params['id'] ?? 0;
        $data = $this->service->info($id);
        return $this->outputSuccess($data);
    }
}