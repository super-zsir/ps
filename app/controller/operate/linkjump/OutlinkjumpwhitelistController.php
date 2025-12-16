<?php

namespace Imee\Controller\Operate\Linkjump;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Linkjump\OutLinkJumpWhitelistService;

class OutlinkjumpwhitelistController extends BaseController
{
    /**
     * @var OutLinkJumpWhitelistService
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new OutLinkJumpWhitelistService();
    }
    
    /**
	 * @page outlinkjumpwhitelist
	 * @name 外部链接跳转白名单
	 */
    public function mainAction()
    {
    }
    
    /**
	 * @page outlinkjumpwhitelist
	 * @point 列表
	 */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }
    
    /**
	 * @page outlinkjumpwhitelist
	 * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'outlinkjumpwhitelist', model_id = 'id')
	 */
    public function createAction()
    {
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
	 * @page outlinkjumpwhitelist
	 * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'outlinkjumpwhitelist', model_id = 'id')
	 */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID错误');
        }
        $this->service->delete($id);
        return $this->outputSuccess(['id' => $id, 'after_json' => $this->params]);
    }
}