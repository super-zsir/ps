<?php

namespace Imee\Models\Xss;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;

class XssNewUserValid extends BaseModel
{
    const USER_MAC_INVALID = 1;
    const USER_BIND_MOBILE_INVALID = 2;
    const USER_SAFE_MOBILE_INVALID = 3;
    const USER_PAY_ACCOUNT_INVALID = 4;
    const USER_IDENTITY_INVALID = 5;

    public static function getField($params)
    {
        if (empty($params['columns']) || count(explode(',', $params['columns'])) > 1) {
            return self::findFirst($params)->toArray();
        } else {
            $result = self::findFirst($params)->toArray();
            return $result[$params['columns']] ?: '';
        }
    }

    /**
     * NOTES: 批量塞入队列
     * @param $params
     * @param string $operate
     */
    public static function addNewUserVaildList($params, string $operate = 'valid')
    {
        foreach ($params as $data) {
            NsqClient::publish(NsqConstant::TOPIC_XSS_NEW_USER_VALID, array(
                'cmd'  => $operate,
                'data' => $data
            ));
        }
    }

    public static function insertRows($data)
    {
        try {
            $rec = self::findFirst(array(
                "uid =:uid:",
                "bind" => array("uid" => $data['uid'])
            ));
            if ($rec) {
                foreach ($data as $k => $v) {
                    $rec->{$k} = $v;
                }
                $d = $rec->save();
                if ($d) return true;
            } else {
                $rec = new XssNewUserValid();
                foreach ($data as $k => $v) {
                    $rec->{$k} = $v;
                }
                $d = $rec->save();
                if ($d) return true;
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }
}
