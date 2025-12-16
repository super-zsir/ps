<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\CategoryService;

/**
 * 品类
 * 认证中心》申请资质 下展示
 */
class CategoryController extends BaseController
{
    /**
     * @var CategoryService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CategoryService();
    }

    /**
     * @page category
     * @name 品类管理
     * @point 菜单树
     */
    public function indexAction()
    {
        $result = $this->service->treeList($this->params);
        return $this->outputSuccess($result);
    }

    /**
     * @page category
     * @point 列表
     */
    public function listAction()
    {
        if (!empty($this->params['c']) && $this->params['c'] == 'tag') {
            return $this->outputSuccess($this->service->getCategoryTagList($this->params));
        }
        if (!empty($this->params['c']) && $this->params['c'] == 'level') {
            return $this->outputSuccess($this->service->getCategoryLevelList($this->params));
        }
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }

    /**
     * @page category
     * @point 详情
     */
    public function detailAction()
    {
        if (!empty($this->params['c']) && $this->params['c'] == 'options') {
            return $this->outputSuccess($this->service->options());
        }
        if (empty($this->params['cid'])) {
            return $this->outputError(-1, 'cid 必须');
        }
        $data = $this->service->detail($this->params['cid']);
        return $this->outputSuccess($data);
    }

    /**
     * @page category
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'category', model_id = 'id')
     */
    public function createAction()
    {
        [$result, $data] = $this->service->create($this->params);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($result);
    }

    /**
     * @page category
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'category', model_id = 'id')
     */
    public function modifyAction()
    {
        [$result, $data] = $this->service->modify($this->params['cid'], $this->params);
        if (!$result) {
            return $this->outputError(-1, $data);
        }

        return $this->outputSuccess($result);
    }

    /**
     * @page category
     * @point 状态
     * @logRecord(content = '状态', action = '3', model = 'category', model_id = 'id')
     */
    public function deleteAction()
    {
        if (empty($this->params['cid']) || !is_numeric($this->params['cid'])) {
            return $this->outputError(-1, 'cid 必须');
        }
        if (!isset($this->params['deleted'])) {
            return $this->outputError(-1, 'deleted 必须');
        }

        return $this->outputSuccess($this->service->status($this->params['cid'], $this->params['deleted']));
    }

    /**
     * @page category
     * @point 创建标签
     * @logRecord(content = '创建', action = '0', model = 'categorytag', model_id = 'id')
     */
    public function createTagAction()
    {
        [$result, $data] = $this->service->addCategoryTag($this->params);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($result);
    }

    /**
     * @page category
     * @point 修改标签
     * @logRecord(content = '修改', action = '1', model = 'categorytag', model_id = 'id')
     */
    public function modifyTagAction()
    {
        [$result, $data] = $this->service->modifyCategoryTag($this->params);
        if (!$result) {
            return $this->outputError(-1, $data);
        }

        return $this->outputSuccess($result);
    }

    /**
     * @page category
     * @point 创建等级
     * @logRecord(content = '创建等级', action = '0', model = 'categorylevel', model_id = 'id')
     */
    public function createLevelAction()
    {
        [$result, $data] = $this->service->addCategoryLevel($this->params);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($result);
    }

    /**
     * @page category
     * @point 修改等级
     * @logRecord(content = '修改', action = '1', model = 'categorylevel', model_id = 'id')
     */
    public function modifyLevelAction()
    {
        [$result, $data] = $this->service->modifyCategoryLevel($this->params);
        if (!$result) {
            return $this->outputError(-1, $data);
        }

        return $this->outputSuccess($result);
    }
}