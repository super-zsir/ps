<?php

namespace Imee\Controller\Operate\Emoticons;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Emoticons\Tag\EmoticonsTagAddValidation;
use Imee\Controller\Validation\Operate\Emoticons\Tag\EmoticonsTagEditValidation;
use Imee\Service\Operate\Emoticons\EmoticonsTagService;

class EmoticonstagController extends BaseController
{
    /**
     * @var EmoticonsTagService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new EmoticonsTagService();
    }
    
    /**
	 * @page emoticonstag
	 * @name 标签配置
	 */
    public function mainAction()
    {
    }
    
    /**
	 * @page emoticonstag
	 * @point 列表
	 */
    public function listAction()
    {
        $data = $this->service->getList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
	 * @page emoticonstag
	 * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'emoticonstag', model_id = 'id')
	 */
    public function createAction()
    {
        EmoticonsTagAddValidation::make()->validators($this->params);
        $data = $this->service->add($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
	 * @page emoticonstag
	 * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'emoticonstag', model_id = 'id')
	 */
    public function modifyAction()
    {
        EmoticonsTagEditValidation::make()->validators($this->params);
        $data = $this->service->edit($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
	 * @page emoticonstag
	 * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'emoticonstag', model_id = 'id')
	 */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        $this->service->delete($id);
        return $this->outputSuccess(['id' => $id, 'after_json' => []]);
    }
}