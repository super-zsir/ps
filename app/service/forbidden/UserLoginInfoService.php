<?php

namespace Imee\Service\Forbidden;


use Imee\Models\Xs\XsDeviceForbidden;
use Imee\Models\Xs\XsUserLoginInfo;

class UserLoginInfoService
{


    public $sim = [
        0 => '否',
        1 => '是'
    ];

    public function list($params = [])
    {
        $res = ['data' => [], 'total' => 0];
        $where = [];
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 30;
        $uid = $params['uid'] ?? '';
        $mac = $params['mac'] ?? '';
        $did = $params['did'] ?? '';
        if(!$uid && !$mac && !$did){
            return $res;
        }
        if($uid){
            $where[] = ['uid', '=', trim($uid)];
        }
        if($mac){
            $where[] = ['mac', '=', trim($mac)];
        }
        if($did){
            $where[] = ['did', '=', trim($did)];
        }
        $res = XsUserLoginInfo::getListAndTotal($where, '*', 'id desc', $page, $limit);
        if($res['total']){
            $time = time();
            // 获取所有的mac信息
            $macForbidden = [];
            $macs = array_values(array_unique(array_column($res['data'], 'mac')));
            if($macs){
                $macForbidden = XsDeviceForbidden::getListByWhere([
                    ['object_type', '=', 1],
                    ['object_id', 'in', $macs],
                    ['expire_at', '>', $time]
                ]);
                $macForbidden = array_column($macForbidden, null, 'object_id');
            }
            // 获取所有的did信息
            $didForbidden = [];
            $dids = array_values(array_unique(array_column($res['data'], 'did')));
            if($macs){
                $didForbidden = XsDeviceForbidden::getListByWhere([
                    ['object_type', '=', 2],
                    ['object_id', 'in', $dids],
                    ['expire_at', '>', $time]
                ]);
                $didForbidden = array_column($didForbidden, null, 'object_id');
            }
            foreach ($res['data'] as &$item){
                $item['dateline'] = $item['dateline'] ? date("Y-m-d H:i:s", $item['dateline']) : '';
                $item['sim_name'] = $this->sim[$item['sim']] ?? '';
                $item['mac_status'] = ($item['mac'] && isset($macForbidden[$item['mac']])) ? '封禁' : '正常';
                $item['did_status'] = ($item['did'] && isset($didForbidden[$item['did']])) ? '封禁' : '正常';
                $item['ip'] = $item['ip'] ? long2ip($item['ip']) : '';
            }
        }
        return $res;

    }

}