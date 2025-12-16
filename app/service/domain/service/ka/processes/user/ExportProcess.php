<?php

namespace Imee\Service\Domain\Service\Ka\Processes\User;

use Imee\Service\Lesscode\ExportService;
use Imee\Service\Lesscode\Traits\Curd\ExportTrait;

class ExportProcess
{
    use ExportTrait;

    private $params;
    private $listService;

    public function __construct()
    {
        $this->listService = new ListProcess();
    }

    public function onSetParams($params): void
    {
        $this->params = $params;
        $this->listService->onSetParams($params);
    }

    public function onGetFilter(&$filter)
    {
        $this->listService->onGetFilter($filter);
    }

    public function onListFormat(&$item)
    {
        $this->listService->onListFormat($item);
    }

    public function onAfterList($list): array
    {
        return $this->listService->onAfterList($list);
    }

    public function onGetHeader(): array
    {
        return ExportService::getListFields($this->params['guid'] ?? '');
    }
}
