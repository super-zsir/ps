<?php

namespace Imee\Service\Domain\Service\Pretty;

use Imee\Exception\ApiException;
use Imee\Service\Domain\Context\Pretty\UserCustomize\ListContext;
use Imee\Service\Domain\Service\Pretty\Processes\UserCustomize\ListProcess;

use Imee\Service\Domain\Context\Pretty\UserCustomize\CreateContext;
use Imee\Service\Domain\Context\Pretty\UserCustomize\ModifyContext;
use Imee\Service\Domain\Service\Pretty\Processes\UserCustomize\CreateProcess;
use Imee\Service\Domain\Service\Pretty\Processes\UserCustomize\ModifyProcess;
use Imee\Exception\Operate\PrettyUserCustomizeException;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Models\Xs\XsUserPretty;
use Imee\Service\Helper;

class PrettyUserCustomizeService
{
    /**
     * @var int $_maxCount
     */
    private $_maxCount = 100;

    public function getList($params)
    {
        $context = new ListContext($params);
        $process = new ListProcess($context);
        return $process->handle();
    }
    
    public function create($params)
    {
        $context = new CreateContext($params);
        
        $process = new CreateProcess($context);
        return $process->handle();
    }

    public function modify($params)
    {
        $context = new ModifyContext($params);
        $process = new ModifyProcess($context);
        return $process->handle();
    }

    public function import($params)
    {
        if (count($params['data']) > $this->_maxCount) {
            list($code, $msg) = PrettyUserCustomizeException::IMPORT_NUM_ERROR;
            $msg = sprintf($msg, $this->_maxCount);
            throw new PrettyUserCustomizeException($msg, $code);
        }

        $ids = array_column($params['data'], 'customize_pretty_id');
        $ids = array_map('intval', $ids);

        $disableds = XsCustomizePrettyStyle::getListByWhere([['id', 'in', $ids], ['disabled', '=', XsCustomizePrettyStyle::DISABLED_YES]], 'id');
        if ($disableds) {
            throw new ApiException(ApiException::MSG_ERROR, '自选靓号类型ID 已被禁用：' . implode(',', array_column($disableds, 'id')));
        }
        
        if (!XsUserPretty::hasLengthPurview()) {
            $res = XsCustomizePrettyStyle::findOneByWhere([['id', 'in', $ids], ['short_limit', '<=', XsUserPretty::PRETTY_LENGTH], ['short_limit', '>', 0]], 'id');
            if ($res) {
                throw new ApiException(ApiException::MSG_ERROR, '你需要申请【1位&2位数靓号】权限，才能发放对应靓号');
            }
            $res = XsCustomizePrettyStyle::findOneByWhere([['id', 'in', $ids], ['ar_short_limit', '<=', XsUserPretty::PRETTY_LENGTH], ['ar_short_limit', '>', 0]], 'id');
            if ($res) {
                throw new ApiException(ApiException::MSG_ERROR, '你需要申请【1位&2位数靓号】权限，才能发放对应靓号');
            }
            $res = XsCustomizePrettyStyle::findOneByWhere([['id', 'in', $ids], ['tr_short_limit', '<=', XsUserPretty::PRETTY_LENGTH], ['tr_short_limit', '>', 0]], 'id');
            if ($res) {
                throw new ApiException(ApiException::MSG_ERROR, '你需要申请【1位&2位数靓号】权限，才能发放对应靓号');
            }
        }

        $i = 1;
        foreach ($params['data'] as $data) {
            try {
                $data['admin_id'] = Helper::getSystemUid();
                $data['uid_str'] = $data['uid'];
                $data['give_type'] = is_numeric($data['give_type']) ? $data['give_type'] : 1;
                $this->create($data);
            } catch (PrettyUserCustomizeException $e) {
                list($code, $msg) = PrettyUserCustomizeException::IMPORT_FAIL_ERROR;
                $msg = sprintf($msg, $i, $e->getMessage());
                throw new PrettyUserCustomizeException($msg, $code);
            }
            $i++;
        }
    }
}
