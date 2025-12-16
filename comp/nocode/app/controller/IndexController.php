<?php

namespace Imee\Controller\Nocode;

class IndexController extends AdminBaseController
{
    protected function onConstruct()
    {
        parent::onConstruct();
    }

    /**
     * @page nocode
     * @name 零代码首页
     */
    public function mainAction()
    {
        echo 'hello nocode';
    }

    /**
     * @page nocode
     * @point 列表
     */
    public function listAction()
    {
        echo 'Please write data call code in the controller！';
    }

    /**
     * @page nocode
     * @point 功能入口
     */
    public function commonAction()
    {
        echo 'Please write data call code in the controller！';
    }
}