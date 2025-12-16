<?php

namespace Imee\Comp\Nocode\Service\Logic\Menu;

use Imee\Comp\Nocode\Service\Context\Form\InfoContext;
use Imee\Comp\Nocode\Service\Exception\FormException;
use Imee\Comp\Nocode\Service\Logic\Form\InfoLogic as FormInfoLogic;
use Imee\Comp\Operate\Auth\Service\ModulesService;
use Imee\Comp\Operate\Auth\Service\Context\Modules\GetInfoContext;
use Imee\Comp\Nocode\Service\Consts\SchemaJsonConst;

class InfoLogic
{
    /**
     * @var InfoContext $context
     */
    private $context;

    public function __construct(InfoContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $this->verify();

        $logic = new FormInfoLogic($this->context);
        $config = $logic->handle();
        if (empty($config)) {
            return [];
        }

        $modulesService = new ModulesService();
        $moduleInfo = $modulesService->getInfoByGuidAndAction(new GetInfoContext([
            'controller' => $this->context->ncid,
            'action'     => SchemaJsonConst::POINT_MAIN
        ]));

        return $moduleInfo ? $moduleInfo->toArray() : [];
    }

    private function verify()
    {
        if (empty($this->context->ncid)) {
            [$code, $msg] = FormException::NCID_NOT_FOUND;
            throw new FormException($msg, $code);
        }
    }
}