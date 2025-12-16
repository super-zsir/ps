<?php
namespace Imee\Service\Domain\Service\Audit\Processes\RiskUser;

use Imee\Models\Xs\XsUserReaudit;
use Imee\Models\Xsst\XsUserReauditLog;
use Imee\Service\Domain\Context\Audit\RiskUser\ModifyContext;
use Imee\Service\Helper;

class ModifyProcess
{
    private $context;
    public function __construct(ModifyContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $id = $this->context->id;
        $status = $this->context->status;

        $data = XsUserReaudit::findFirst($id);
        if (!$data) {
        	return false;
		}

		$data->status = $status;
		if ($data->save()) {
			// 记录日志
			$risk_log = new XsUserReauditLog();
			$risk_log->save([
				'rid' => $id,
				'op_uid' => Helper::getSystemUid(),
				'status' => $status,
				'create_time' => $data->dateline,
				'op_dateline' => time()
			]);
		}
        return true;
    }
}
