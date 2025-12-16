<?php


namespace Imee\Service\Domain\Service\Audit\Processes\CircleReport;

use Imee\Models\Bms\XsstKefuTaskCirclereport;
use Imee\Service\Domain\Service\Csms\Context\Staff\OldTaskInfoContext;

class OldTaskInfoProcess
{
    protected $context;

    public function __construct(OldTaskInfoContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $ids = $this->context->ids;
        $data = XsstKefuTaskCirclereport::find([
            'conditions' => 'id in ({ids:array})',
            'bind' => [
                'ids' => $ids
            ]
        ])->toArray();
        return array_column($data, null, 'id');
    }
}
