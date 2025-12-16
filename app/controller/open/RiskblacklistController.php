<?php

namespace Imee\Controller\Open;

use Imee\Controller\BaseOpenController;
use Imee\Service\Risk\RiskBlacklistService;

/**
 * 风控黑名单开放接口
 */
class RiskblacklistController extends BaseOpenController
{
    public $params;

    public function onConstruct()
    {
        parent::onConstruct();
        $get = $this->request->getQuery();
        $post = $this->request->getPost();
        $this->params = array_merge(
            $get,
            $post
        );
    }

    /**
     * 黑名单列表
     * update_time有值就增量更新
     */
    public function listAction()
    {
        $service = new RiskBlacklistService();
        $result = $service->getOpenListAndTotal(
            $this->params, 'update_time asc', $this->params['page'] ?? 1, $this->params['limit'] ?? 500
        );
        return $this->outputSuccess($result);
    }
}