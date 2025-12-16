<?php

namespace Imee\Service\Operate\Play\Redpacket;

use Imee\Exception\ApiException;
use Imee\Service\Rpc\PsService;

class BaseService
{
    const MODIFY_TYPE_DEFAULT = 0;
    const MODIFY_TYPE_ORDINARY = 1;
    const MODIFY_TYPE_CODE = 2;

    const OP_SWITCH = 1;
    const OP_CONFIG = 2;
    const OP_ALL = 3;


    const AMOUNT_PREFIX = 'amount';
    const NUM_PREFIX = 'num_';

    // 目前指定4个档位
    public $keys = [1, 2, 3, 4];

    public function modify($data)
    {
        list($res, $msg) = (new PsService())->setRedPacketConfig($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function formatConfig(&$item, $config)
    {
        if ($config) {
            $k = 1;
            foreach ($config as $gear) {
                $amountKey = self::AMOUNT_PREFIX . $k;
                $item[$amountKey] = $gear[self::AMOUNT_PREFIX] ?? '';
                $nk = 1;
                foreach ($gear['count'] as $num) {
                    $numKey = self::NUM_PREFIX . $k . '_' . $nk;
                    $item[$numKey] = $num ?? '';
                    $nk = $nk + 1;
                }
                $k = $k + 1;
            }
        }
    }

    public function getNumConditions(array $params): array
    {
        $conditions = [];
        if (isset($params['big_area']) && !empty($params['big_area'])) {
            $conditions[] = ['id', '=', $params['big_area']];
        }
        return $conditions;
    }

    public function formatData($params): array
    {
        $data = [];
        foreach ($this->keys as $key => $value) {
            $data[$key][self::AMOUNT_PREFIX] = (int) $params[self::AMOUNT_PREFIX . $value];
            $count = [];
            foreach ($this->keys as $v) {
                $nk = self::NUM_PREFIX . $value . '_' . $v;
                $count[] = (int)$params[$nk];
            }
            $data[$key]['count'] = $count;
        }
        return $data;
    }
}