<?php

namespace Imee\Controller\Nocode;

use Imee\Controller\BaseController;
use Imee\Exception\ApiException;
use Imee\Comp\Nocode\Service\Logic\MenuLogic;
use Imee\Comp\Nocode\Service\HelperService;

abstract class AdminBaseController extends BaseController
{

    /**
     * @var string 低代码菜单转化实际模块名称
     */
    protected $realModule;

    /**
     * @var string 低代码菜单转化实际控制器名称
     */
    protected $realController;

    /**
     * @var string 低代码菜单转化实际方法名称
     */
    protected $realAction;

    /**
     * @var array 零代码不需要权限的控制器
     */
    protected $nocodeNotPermissionCtl = [
        'nocode/form',
        'nocode/model',
        'nocode/apijson',
    ];

    protected function onConstruct()
    {
        parent::onConstruct();

        // ncid 默认转为小写
        if (!empty($this->params['ncid'])) {
            $this->params['ncid'] = strtolower($this->params['ncid']);
        }
    }

    /**
     * 检查是否是零代码创建菜单
     * @param $purviewName
     * @return bool
     */
    protected function checkAutoMenu(&$purviewName): bool
    {
        [$controller, $action] = explode('.', $purviewName);
        // 验证是否为零代码相关功能操作
        if ($controller != 'nocode/index') {

            if (!property_exists($this,'notPermissionCtl')) {
                throw new ApiException(ApiException::MSG_ERROR, '请修改BaseController中notPermissionCtl属性修饰符为protected||public');
            }

            $this->notPermissionCtl = array_merge($this->notPermissionCtl, $this->nocodeNotPermissionCtl);
            return false;
        }

        if (empty($this->params['ncid'])) {
            return false;
        }

        // 判断是否是零代码菜单
        $menuInfo = MenuLogic::getInstance()->getInfo(['ncid' => $this->params['ncid']]);
        if (empty($menuInfo)) {
            return false;
        }

        // 零代码功能操作入口需重写action
        if ($action == 'common') {
            $action = $this->params['action'] ?? 'common';
        }

        $controller = HelperService::getRealController($menuInfo['controller']);
        $this->realController = $controller['controller'];
        $this->realModule = $controller['path'];
        $this->realAction = $action;
        $controllerNameSpace = $controller['namespace'];
        if (class_exists($controllerNameSpace) && method_exists($controllerNameSpace, $this->realAction . 'Action')) {
            $this->dispatcher->forward([
                'namespace'  => 'Imee\Controller\\' . $this->realModule,
                'controller' => $this->realController,
                'action'     => $this->realAction,
                'params'     => $this->dispatcher->getParams()
            ]);
        }
        
        // 如果是零代码菜单 需要重写 $purviewName
        $purviewName = $menuInfo['controller'] . '.' . $action;
        return true;
    }

    /**
     * 获取权限列表
     * @return array
     */
    public function getPurview(): array
    {
        if (class_exists('Imee\Comp\Operate\Auth\Models\Cms\CmsModuleUser') && method_exists('Imee\Comp\Operate\Auth\Models\Cms\CmsModuleUser', 'getUserAllAction')) {
            return CmsModuleUser::getUserAllAction($this->uid);
        }

        return $this->session->get('purview');
    }
}