<?php

namespace Imee\Service\Operate;

use Imee\Comp\Common\Log\LoggerProxy;
use Imee\Comp\Common\Sdk\SdkBase;
use Imee\Comp\Operate\Auth\Models\Cms\CmsModuleUser;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsPropCardConfig;
use Imee\Models\Xs\XsSendPropCardLog;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsVipRecord;
use Imee\Models\Xsst\BmsVipSend;
use Imee\Models\Xsst\BmsVipSendDetail;
use Imee\Models\Xsst\XsstVipSendLimit;
use Imee\Service\Domain\Context\Vipsend\CreateContext;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Comp\Operate\Auth\Service\StaffService;

class VipsendService
{

    public static $vipAmountMap = [
        7 => [
            // 'recv_gift_value' => ENV == 'dev' ? 1000000 : 5000000, // 收礼
            // 'charge_amt'      => ENV == 'dev' ? 1000000 : 5000000, // 充值
            'recv_gift_value' => 0, // 收礼
            'charge_amt'      => 0, // 充值
        ],
        8 => [
            // 'recv_gift_value' => ENV == 'dev' ? 1000000 : 5000000, // 收礼
            // 'charge_amt'      => ENV == 'dev' ? 1000000 : 5000000, // 充值
            'recv_gift_value' => 0, // 收礼
            'charge_amt'      => 0, // 充值
        ],
    ];

    public function getState()
    {
        $format = [];

        foreach (BmsVipSend::$displayState as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format[] = $tmp;
        }
        return $format;
    }

    public function getVipLevel()
    {
        $format = [];

        $vipLevels = BmsVipSendDetail::$displayVipLevel;

        if (!self::hasVip7Purview()) {
            unset($vipLevels[7]);
            unset($vipLevels[8]);
        }

        foreach ($vipLevels as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = (string)$k;
            $format[] = $tmp;
        }
        return $format;
    }

    public static function hasVip7Purview(): bool
    {
        $user = Helper::getSystemUserInfo();
        if ($user['super'] != 1) {
            $purviews = CmsModuleUser::getUserAllAction(Helper::getSystemUid());
            $auth = 'operate/vipsend.vip7';
            if (!in_array($auth, $purviews)) {
                return false;
            }
        }
        return true;
    }

    public static function noCheckPurview(): bool
    {
        $user = Helper::getSystemUserInfo();
        if ($user['super'] != 1) {
            $purviews = CmsModuleUser::getUserAllAction(Helper::getSystemUid());
            $auth = 'operate/vipsend.nocheck';
            if (!in_array($auth, $purviews)) {
                return false;
            }
        }
        return true;
    }

    public function getAllowDays()
    {
        $format = [];

        foreach (BmsVipSendDetail::$allowDays as $k => $v) {
            $tmp['label'] = $v . '天';
            $tmp['value'] = (string)$v;
            $format[] = $tmp;
        }
        return $format;
    }

    public function getAllStaff()
    {
        $format = [];
        $staffService = new StaffService();
        $staffList = $staffService->getAllStaff();
        if (empty($staffList)) {
            return $format;
        }
        foreach ($staffList as $k => $v) {
            $tmp['label'] = $v['user_name'];
            $tmp['value'] = $v['user_id'];
            $format[] = $tmp;
        }
        return $format;
    }

    public function getList($params)
    {
        $conditon = [];
        $conditon[] = ['state', '!=', BmsVipSend::STATE_UNVALID];
        if (isset($params['id']) && is_numeric($params['id'])) {
            $conditon[] = ['id', '=', $params['id']];
        }

        if (isset($params['state']) && is_numeric($params['state'])) {
            $conditon[] = ['state', '=', $params['state']];
        }

        if (isset($params['op_uid']) && is_numeric($params['op_uid'])) {
            $conditon[] = ['op_uid', '=', $params['op_uid']];
        }

        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])) {
            $conditon[] = ['dateline', '>=', strtotime($params['dateline_sdate'])];
        }

        if (isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditon[] = ['dateline', '<', strtotime($params['dateline_edate']) + 86400];
        }

        $conditon2 = [];
        if (!empty($params['uid']) && is_numeric($params['uid'])) {
            $conditon2[] = ['uid', '=', $params['uid']];
        }
        if (!empty($params['vip_level']) && is_numeric($params['vip_level'])) {
            $conditon2[] = ['vip_level', '=', $params['vip_level']];
        }

        if ($conditon2) {
            $res = BmsVipSendDetail::getListByWhere($conditon2, 'send_id');
            if (!$res) {
                return [];
            }
            $ids = array_values(array_unique(array_column($res, 'send_id')));
            if (isset($params['id']) && is_numeric($params['id'])) {
                if (!in_array($params['id'], $ids)) {
                    return [];
                }
            } else {
                $conditon[] = ['id', 'in', $ids];
            }
        }

        $result = BmsVipSend::getListAndTotal($conditon, '*', 'id desc', $params['page'], $params['limit']);
        if ($result['total'] == 0 || !$result['data']) {
            return $result;
        }
        $opUids = [];
        foreach ($result['data'] as $v) {
            $opUids[] = $v['op_uid'];
        }
        $staffMap = CmsUser::getAdminUserBatch($opUids);

        foreach ($result['data'] as &$v) {
            $v['staff_name'] = isset($staffMap[$v['op_uid']]) ? $staffMap[$v['op_uid']]['user_name'] : '';

            $v['dateline'] = $v['dateline'] > 0 ? date('Y-m-d H:i:s', $v['dateline']) : '';
            $v['update_time'] = $v['update_time'] > 0 ?
                date('Y-m-d H:i:s', $v['update_time']) : '';

            $v['display_state'] = isset(BmsVipSend::$displayState[$v['state']]) ?
                BmsVipSend::$displayState[$v['state']] : '';
        }
        return $result;
    }

    public function checkCreate(array $params): array
    {
        $data = $this->getCreateData($params);

        // 检查VIP7条件
        [$success, $result] = self::checkVip7($data);
        if (!$success) {
            return ['is_info' => true, 'msg' => $result['msg']];
        }

        [$success, $result] = $this->checkLimit($data);
        if (!$success) {
            $url = $result['url'];
            $uids = $result['uids'];
            $errors = $result['errors'];

            $str = '';
            $str .= sprintf('UID：%s 发放次数超过所在大区周期限制 ', implode(',', $uids));
            if ($url) {
                if (ENV == 'dev') {
                    $url = explode('?', $url);
                    $url = str_replace('http:', 'https:', $url[0]);
                }
                $str .= sprintf('<a href="%s" rel="noopener noreferrer">下载明细</a>', $url);
            }
            $str .= '<br/>';
            if ($errors) {
                if (count($errors) > 30) {
                    $str .= '超出发放限制用户过多，发放历史记录请下载表格查看<br/>';
                } else {
                    $table = [];
                    $table[] = '<table border="1" style="border-collapse: collapse">';
                    $table[] = '<tr>';
                    $table[] = '<td>UID</td><td>昵称</td><td>VIP等级</td><td>下发天数</td><td style="width: 170px">下发时间</td><td>发放数量</td><td>操作人</td>';
                    $table[] = '</tr>';
                    foreach ($errors as $v) {
                        $table[] = sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', $v['uid'], $v['name'], $v['vip_level'], $v['vip_day'], $v['dateline'], $v['send_num'], $v['operator']);
                    }
                    $table[] = '</tr>';
                    $table[] = '</table>';
                    $str .= implode('', $table);
                }
            }

            return ['is_info' => true, 'msg' => $str];
        }

        [$success, $result] = $this->checkLog(array_column($data, 'uid'));
        if (!$success) {
            $url = $result['url'];
            $logs = $result['logs'];

            $str = '以下是用户15日内的发放记录，确认发放吗？';
            $str .= sprintf(' ');
            if ($url) {
                if (ENV == 'dev') {
                    $url = explode('?', $url);
                    $url = str_replace('http:', 'https:', $url[0]);
                }
                $str .= sprintf('<a href="%s" rel="noopener noreferrer">下载明细</a>', $url);
            }
            $str .= '<br/>';
            if ($logs) {
                if (count($logs) > 30) {
                    $str .= '发放历史记录过多，请下载表格查看<br/>';
                } else {
                    $table = [];
                    $table[] = '<table border="1" style="border-collapse: collapse">';
                    $table[] = '<tr>';
                    $table[] = '<td>UID</td><td>昵称</td><td>VIP等级</td><td>下发天数</td><td style="width: 170px">下发时间</td><td>发放数量</td><td>操作人</td>';
                    $table[] = '</tr>';
                    foreach ($logs as $v) {
                        $table[] = sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', $v['uid'], $v['name'], $v['vip_level'], $v['vip_day'], $v['dateline'], $v['send_num'], $v['operator']);
                    }
                    $table[] = '</tr>';
                    $table[] = '</table>';
                    $str .= implode('', $table);
                }
            }

            return ['is_confirm' => true, 'msg' => $str];
        }

        return [];
    }

    public function checkLog(array $uids): array
    {
        $startTime = strtotime(date('Y-m-d')) - 15 * 86400;

        $sends = BmsVipSend::getListByWhere([['dateline', '>=', $startTime], ['state', '=', BmsVipSend::STATE_SUC]], 'id,op_uid,dateline');
        if ($sends) {
            $sendIds = array_column($sends, 'id');
            $logs = BmsVipSendDetail::getListByWhere([
                ['send_id', 'in', $sendIds],
                ['dateline', '>=', $startTime],
                ['uid', 'in', $uids]
            ], 'send_id,vip_level,vip_day,uid,send_num,dateline', 'uid asc');

            if ($logs) {
                $users = XsUserProfile::getUserProfileBatch(array_column($logs, 'uid'));
                $operators = CmsUser::getAdminUserBatch(array_column($sends, 'op_uid'));
                $sends = array_column($sends, 'op_uid', 'id');

                $filePath = PUBLIC_DIR . DS . 'vip_send_uid_log_' . Helper::getSystemUid() . '.csv';

                @file_put_contents($filePath, 'UID,昵称,VIP等级,下发天数,下发时间,发放数量,操作人' . PHP_EOL);
                $errorExcel = [];

                foreach ($logs as &$v) {
                    $opUid = $sends[$v['send_id']] ?? 0;
                    $name = $users[$v['uid']]['name'] ?? '';
                    $operator = $operators[$opUid]['user_name'] ?? '';
                    $dateline = Helper::now($v['dateline']);

                    $v['name'] = $name;
                    $v['dateline'] = $dateline;
                    $v['operator'] = $operator;

                    $errorExcel[] = sprintf('%s,%s,%s,%s,%s,%s' . PHP_EOL, $v['uid'], $name, $v['vip_level'], $v['vip_day'], $dateline, $v['send_num'], $operator);
                }

                @file_put_contents($filePath, $errorExcel, FILE_APPEND);

                $logUrl = Helper::uploadOss($filePath);
                @unlink($filePath);

                return [false, ['url' => $logUrl, 'logs' => $logs]];
            }
        }
        return [true, []];
    }

    public function create($params)
    {
        $detailDatas = $this->getCreateData($params);
        $this->saveData($detailDatas, $params['admin_id']);
    }

    private function getCreateData(array $params): array
    {
        $context = new CreateContext($params);

        $sendNum = $context->sendNum;
        if ($context->type == 0) {
            $sendNum = 1;//直接生效，默认为1
        }
        if ($sendNum < 1 || $sendNum > 100) {
            throw new ApiException(ApiException::MSG_ERROR, "VIP发放数量必须为1-100之间的整数，请检查后重试");
        }

        $detailDatas = [];
        foreach (explode(',', $context->uids) as $uid) {
            $tmp = [
                'vip_level'   => $context->vipLevel,
                'vip_day'     => $context->vipDay,
                'send_num'    => $sendNum,
                'uid'         => trim($uid),
                'remark'      => $context->remark,
                'type'        => $context->type,
                'dateline'    => time(),
                'update_time' => time(),
            ];
            $detailDatas[] = $tmp;
        }

        $this->valid($detailDatas);

        return $detailDatas;
    }

    private function valid(array $detailDatas): void
    {
        if (count($detailDatas) > 500) {
            throw new ApiException(ApiException::MSG_ERROR, "单次最多可以下发500个，请修改后重试。");
        }
        $uids = array_column($detailDatas, 'uid');
        $uidMap = array_count_values($uids);
        foreach ($uidMap as $k => $v) {
            if (!is_numeric($k)) {
                throw new ApiException(ApiException::MSG_ERROR, "存在非整型数据，请检查后重试。" . $k);
            }
            if ($v > 1) {
                throw new ApiException(ApiException::MSG_ERROR, "存在重复记录，请检查后重试。" . $k);
            }
        }
        //检测uid存在不
        $userMap = XsUserProfile::getUserProfileBatch($uids);
        $existUids = array_keys($userMap);
        $diffUids = array_diff($uids, $existUids);
        if ($diffUids) {
            throw new ApiException(ApiException::MSG_ERROR, "以下uid不存在，请检查后重试。" . implode(',', $diffUids));
        }
    }

    public static function checkIfSendable(int $level, array $uids): array
    {
        if (!$uids) {
            throw new ApiException(ApiException::MSG_ERROR, "uid不存在，请检查后重试。");
        }

        if (in_array($level, [7, 8])) {
            $hasVip7Purview = self::hasVip7Purview();
            if (!$hasVip7Purview) {
                throw new ApiException(ApiException::MSG_ERROR, sprintf("VIP%s下发失败，暂无VIP%s下发权限，请先申请", $level, $level));
            }
        }
        
        if (isset(self::$vipAmountMap[$level])) {
            $purview = self::noCheckPurview();
            if ($purview) {
                return [];
            }
            // 调用RPC接口进行VIP发送检查
            $psService = new PsService();
            list($success, $msg, $rpcData) = $psService->vipSendCheck([
                'level' => $level,
                'uids' => $uids,
                'operator' => intval(Helper::getSystemUid())
            ]);
            
            if (!$success) {
                throw new ApiException(ApiException::MSG_ERROR, 'VIP发送检查RPC调用失败: ' . $msg);
            }
            $cannotSendList = [];
            if (!empty($rpcData)) {

                foreach ($rpcData as $userData) {
                    $uid = $userData['uid'] ?? '';
                    $userRecvGiftValue = (float)($userData['recv_gift_value'] ?? 0);
                    $userChargeAmt = (float)($userData['charge_amt'] ?? 0);
                    $reasons = $userData['reasons'] ?? [];
                    
                
                    $cannotSendList[] = [
                        'uid' => $uid,
                        'recv_gift_value' => $userRecvGiftValue,
                        'charge_amt' => $userChargeAmt,
                        'required_recv_gift_value' => $userData['require_recv_gift_value'] ?? 0,
                        'required_charge_amt' => $userData['require_charge_amt'] ?? 0,
                        'reason' => implode(', ', $reasons),
                        'level' => $level
                    ];
                }
            }
            
            return $cannotSendList;
        }

        return [];
    }

    // 请求数仓那边拉取当前条件下的用户数据
    private static function requestDataServiceGetUidList(array $uids, string $startDate, string $endDate): array
    {
        // 请求数仓接口
        $url = ENV == 'dev' ? 'http://223.76.184.188:8766/api/data-tunnel/fetch' : 'http://serv-data-sg-services.aopacloud.private/api/data-tunnel/fetch';
        
        // 更新后的认证参数
        $code = ENV == 'dev' ? 'Ev8tlJa7' : 'iSGIJKTM';
        $authKey = ENV == 'dev' ? '405SN1HL' : 'KJeyDaUW';
        $isTest = ENV == 'dev';
        
        // 构造请求参数
        $requestParams = [
            [
                "fieldName" => "start_date",
                "fieldType" => "STRING",
                "fieldValue" => $startDate,
                "fieldDescription" => "开始日期"
            ],
            [
                "fieldName" => "end_date", 
                "fieldType" => "STRING",
                "fieldValue" => $endDate,
                "fieldDescription" => "结束日期"
            ],
            [
                "fieldName" => "uids",
                "fieldType" => "LIST_NUMBER",
                "fieldValue" => implode(',', $uids),
                "fieldDescription" => "uid列表"
            ]
        ];
        
        $params = [
            "code"      => $code,
            "authKey"   => $authKey,
            "versionId" => 1,
            "fireTime"  => date('Y-m-d'), // 添加fireTime参数
            "parameter" => $requestParams,
            "isTest"    => $isTest
        ];
        
        // 确保JSON编码不转义斜杠、中文等
        $jsonParams = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        $sdk = new SdkBase(SdkBase::FORMAT_JSON, 30);
        $result = $sdk->httpRequest($url, true, $jsonParams, null, null, null, true);

        if (null === $result) {
            LoggerProxy::instance()->error('Request to data service failed: ' . json_encode(['url' => $url, 'http_code' => $sdk->getLastCode(), 'error' => $sdk->getLastError()], JSON_UNESCAPED_UNICODE));
            return [];
        }
        
        if ($result['statusCode'] != 200) {
            return [];
        }
        
        // 解析返回的数据结构
        $responseData = $result['data']['data'] ?? [];
        $total = $result['data']['total'] ?? 0;
        
        return [
            'data' => $responseData, // 返回完整的用户数据（包含uid, recv_gift_value, charge_amt等）
            'total' => $total
        ];
    }

    private function saveData($detailDatas, $adminId)
    {
        $model = new BmsVipSend;

        $model->op_uid = $adminId;
        $model->dateline = time();
        $model->update_time = time();
        $model->state = BmsVipSend::STATE_UNVALID;
        $model->save();

        foreach ($detailDatas as &$detailData) {
            $detailData['send_id'] = $model->id;
        }
        unset($detailData);
        list($flag, $msg) = BmsVipSendDetail::addBatch($detailDatas);
        if (!$flag) {
            throw new ApiException(ApiException::MSG_ERROR, "保存失败，原因" . $msg);
        }
        $model->num = count($detailDatas);
        $model->save();
        $this->commonCurl($model, $detailDatas, $adminId);
    }

    private function commonCurl($model, $detailDatas, $adminId)
    {
        $buildParams = [
            'order_id' => (int)$model->id,
            'operator' => Helper::getAdminName($adminId),
        ];

        foreach ($detailDatas as $v) {
            $tmp = [
                'uid'            => (int)$v['uid'],
                'vip_level'      => (int)$v['vip_level'],
                'validity_value' => (int)$v['vip_day'],
                'remark'         => $v['remark'],
                'type'           => $v['type'],
                'num'            => (int)$v['send_num'],
            ];
            $buildParams['operate_user_vip'][] = $tmp;
        }

        list($res, $msg) = (new PsService())->batchAddVIP($buildParams);
        if (!$res) {
            $model->state = BmsVipSend::STATE_FAIL;
            $model->update_time = time();
            $model->save();
            throw new ApiException(ApiException::MSG_ERROR, "API错误，原因:" . $msg);
        }
        $model->state = BmsVipSend::STATE_SUC;
        $model->update_time = time();
        $model->save();
    }

    public function retry($params)
    {
        $id = isset($params['id']) ? $params['id'] : 0;
        $model = BmsVipSend::findFirst($id);
        if (empty($model) || $model->state == BmsVipSend::STATE_UNVALID) {
            throw new ApiException(ApiException::MSG_ERROR, "数据不存在");
        }
        if ($model->state == BmsVipSend::STATE_SUC) {
            throw new ApiException(ApiException::MSG_ERROR, "该数据已成功发送，无需多次操作");
        }

        $detailDatas = BmsVipSendDetail::find([
            'conditions' => 'send_id=:send_id:',
            'bind'       => [
                'send_id' => $id,
            ],
        ])->toArray();
        $this->commonCurl($model, $detailDatas, $params['admin_id']);
    }

    public function checkImport(array $data)
    {
        $data = $this->getImportData($data);
        
        // 检查VIP7条件
        [$success, $result] = self::checkVip7($data);
        if (!$success) {
            return ['is_info' => true, 'msg' => $result['msg']];
        }
        
        [$success, $result] = $this->checkLimit($data);
        if (!$success) {
            $url = $result['url'];
            $uids = $result['uids'];
            $errors = $result['errors'];

            $str = '';
            $str .= sprintf('UID：%s 发放次数超过所在大区周期限制 ', implode(',', $uids));
            if ($url) {
                if (ENV == 'dev') {
                    $url = explode('?', $url);
                    $url = str_replace('http:', 'https:', $url[0]);
                }
                $str .= sprintf('<a href="%s" rel="noopener noreferrer">下载明细</a>', $url);
            }
            $str .= '<br/>';
            if ($errors) {
                if (count($errors) > 30) {
                    $str .= '超出发放限制用户过多，发放历史记录请下载表格查看<br/>';
                } else {
                    $table = [];
                    $table[] = '<table border="1" style="border-collapse: collapse">';
                    $table[] = '<tr>';
                    $table[] = '<td>UID</td><td>昵称</td><td>VIP等级</td><td>下发天数</td><td style="width: 170px">下发时间</td><td>发放数量</td><td>操作人</td>';
                    $table[] = '</tr>';
                    foreach ($errors as $v) {
                        $table[] = sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', $v['uid'], $v['name'], $v['vip_level'], $v['vip_day'], $v['dateline'], $v['send_num'], $v['operator']);
                    }
                    $table[] = '</tr>';
                    $table[] = '</table>';
                    $str .= implode('', $table);
                }
            }

            return ['is_info' => true, 'msg' => $str];
        }

        [$success, $result] = $this->checkLog(array_column($data, 'uid'));
        if (!$success) {
            $url = $result['url'];
            $logs = $result['logs'];

            $str = '以下是用户15日内的发放记录，确认发放吗？';
            $str .= sprintf(' ');
            if ($url) {
                if (ENV == 'dev') {
                    $url = explode('?', $url);
                    $url = str_replace('http:', 'https:', $url[0]);
                }
                $str .= sprintf('<a href="%s" rel="noopener noreferrer">下载明细</a>', $url);
            }
            $str .= '<br/>';
            if ($logs) {
                if (count($logs) > 30) {
                    $str .= '发放历史记录过多，请下载表格查看<br/>';
                } else {
                    $table = [];
                    $table[] = '<table border="1" style="border-collapse: collapse">';
                    $table[] = '<tr>';
                    $table[] = '<td>UID</td><td>昵称</td><td>VIP等级</td><td>下发天数</td><td style="width: 170px">下发时间</td><td>发放数量</td><td>操作人</td>';
                    $table[] = '</tr>';
                    foreach ($logs as $v) {
                        $table[] = sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', $v['uid'], $v['name'], $v['vip_level'], $v['vip_day'], $v['dateline'], $v['send_num'], $v['operator']);
                    }
                    $table[] = '</tr>';
                    $table[] = '</table>';
                    $str .= implode('', $table);
                }
            }

            return ['is_confirm' => true, 'msg' => $str];
        }

        return [];
    }

    public static function checkVip7(array $data): array
    {
        //检测vip7是否可下发
        $vip7Uids = [];
        foreach ($data as $v) {
            if ($v['vip_level'] == 7) {
                $vip7Uids[] = $v['uid'];
            }
        }
        $vip8Uids = [];
        foreach ($data as $v) {
            if ($v['vip_level'] == 8) {
                $vip8Uids[] = $v['uid'];
            }
        }
        $cannotSendList = [];

        if (!empty($vip7Uids)) {
            $cannotSendList = array_merge($cannotSendList, self::checkIfSendable(7, $vip7Uids));
        }
        if (!empty($vip8Uids)) {
            $cannotSendList = array_merge($cannotSendList, self::checkIfSendable(8, $vip8Uids));
        }

        if (!empty($cannotSendList)) {
            // 批量获取用户昵称
            $cannotSendUids = array_column($cannotSendList, 'uid');
            $userProfiles = XsUserProfile::getUserProfileBatch($cannotSendUids);
            $userNameMap = [];
            foreach ($userProfiles as $profile) {
                $userNameMap[$profile['uid']] = $profile['name'];
            }

            // 从第一条记录获取阈值，动态计算显示（不转换单位，0位小数-千分位）
            $firstRecord = $cannotSendList[0];
            $requiredChargeAmtFormatted = number_format($firstRecord['required_charge_amt'], 0);
            $requiredRecvGiftFormatted = number_format($firstRecord['required_recv_gift_value'], 0);

            $str = "以下uid不满足发放条件，本次上传均发放失败，";
            $str .= "请调整条件后上传：上个自然月 充值≥{$requiredChargeAmtFormatted} 钻石 或者 收礼 ≥{$requiredRecvGiftFormatted} 钻石。<br/><br/>";

            // 构建HTML表格
            $table = [];
            $table[] = '<table border="1" style="border-collapse: collapse; width: 100%; max-width: 600px;">';
            $table[] = '<tr>';
            $table[] = '<td style="width: 100px; text-align: center; font-weight: bold;">UID</td>';
            $table[] = '<td style="width: 120px; text-align: center; font-weight: bold;">昵称</td>';
            $table[] = '<td style="width: 140px; text-align: center; font-weight: bold;">收礼钻石数</td>';
            $table[] = '<td style="width: 140px; text-align: center; font-weight: bold;">充值钻石数</td>';
            $table[] = '</tr>';

            foreach ($cannotSendList as $item) {
                // 格式化数值显示（不转换单位，0位小数-千分位）
                $recvGiftFormatted = number_format($item['recv_gift_value'], 0);
                $chargeAmtFormatted = number_format($item['charge_amt'], 0);

                // 获取用户真实昵称
                $userName = $userNameMap[$item['uid']] ?? '未知用户';

                $table[] = sprintf('<tr><td style="text-align: center;">%s</td><td style="text-align: center;">%s</td><td style="text-align: center;">%s</td><td style="text-align: center;">%s</td></tr>',
                    $item['uid'],
                    mb_substr($userName, 0, 8), // 限制昵称长度，避免格式错乱
                    $recvGiftFormatted,
                    $chargeAmtFormatted
                );
            }

            $table[] = '</table>';
            $str .= implode('', $table);

            return [false, ['msg' => $str]];
        }

        return [true, []];
    }

    private function checkLimit(array $data): array
    {
        $vips = array_values(array_unique(array_column($data, 'vip_level')));
        $limits = XsstVipSendLimit::getByVips($vips);

        if ($limits) {
            $bigareaIds = array_column($limits, 'bigarea_id');
            $uids = array_column($data, 'uid');

            $userBigarea = XsUserBigarea::getListByWhere([['uid', 'in', $uids], ['bigarea_id', 'in', $bigareaIds]], 'uid,bigarea_id');
            $userBigarea = array_column($userBigarea, 'bigarea_id', 'uid');

            if ($userBigarea) {
                $monthStart = strtotime(date('Y-m-01'));

                $day = intval(date('d'));
                $startTime = $day > 15 ? strtotime(date('Y-m-16')) : $monthStart;

                $sends = BmsVipSend::getListByWhere([['dateline', '>=', $monthStart], ['state', '=', BmsVipSend::STATE_SUC]], 'id,op_uid,dateline');
                $halfSends = $sends;
                if ($monthStart != $startTime) {
                    $halfSends = array_filter($sends, function ($send) use ($startTime) {
                        return $send['dateline'] >= $startTime;
                    });
                }

                $halfUsers = [];
                $monthUsers = [];
                $data = array_column($data, null, 'uid');

                foreach ($userBigarea as $uid => $bigareaId) {
                    $vip = $data[$uid]['vip_level'];
                    if (!isset($limits[$vip . '_' . $bigareaId])) {
                        continue;
                    }
                    $limit = $limits[$vip . '_' . $bigareaId];
                    if ($limit['period'] == XsstVipSendLimit::PERIOD_MONTH) {
                        $monthUsers[] = [
                            'uid' => $uid,
                            'num' => $limit['num'],
                            'vip' => $vip,
                        ];
                    } else {
                        $halfUsers[] = [
                            'uid' => $uid,
                            'num' => $limit['num'],
                            'vip' => $vip,
                        ];
                    }
                }

                $monthSum = [];
                $halfSum = [];
                if ($sends) {
                    $sendIds = array_column($sends, 'id');
                    if ($monthUsers) {
                        $monthSum = BmsVipSendDetail::getListByWhere([
                            ['send_id', 'in', $sendIds],
                            ['dateline', '>=', $monthStart],
                            ['uid', 'in', array_column($monthUsers, 'uid')],
                            ['vip_level', 'in', array_column($monthUsers, 'vip')]
                        ], 'uid,vip_level,sum(send_num) as send_num', '', 0, 0, 'uid,vip_level');

                        $format = [];
                        foreach ($monthSum as $ms) {
                            $key = $ms['uid'] . '_' . $ms['vip_level'];
                            $format[$key] = $ms['send_num'];
                        }

                        $monthSum = $format;
                    }

                    if ($halfSends) {
                        $sendIds = array_column($halfSends, 'id');
                        if ($halfUsers) {
                            $halfSum = BmsVipSendDetail::getListByWhere([
                                ['send_id', 'in', $sendIds],
                                ['dateline', '>=', $startTime],
                                ['uid', 'in', array_column($halfUsers, 'uid')],
                                ['vip_level', 'in', array_column($halfUsers, 'vip')]
                            ], 'uid,vip_level,sum(send_num) as send_num', '', 0, 0, 'uid,vip_level');

                            $format = [];
                            foreach ($halfSum as $ms) {
                                $key = $ms['uid'] . '_' . $ms['vip_level'];
                                $format[$key] = $ms['send_num'];
                            }

                            $halfSum = $format;
                        }
                    }
                }

                $limitMonthUid = $limitHalfUid = [];

                foreach ($monthUsers as $user) {
                    $uid = $user['uid'];
                    $key = $uid . '_' . $user['vip'];
                    if ($user['vip'] != $data[$uid]['vip_level']) {
                        continue;
                    }

                    $total = ($monthSum[$key] ?? 0) + $data[$uid]['send_num'];
                    if ($total > $user['num']) {
                        $limitMonthUid[] = $uid;
                    }
                }

                foreach ($halfUsers as $user) {
                    $uid = $user['uid'];
                    $key = $uid . '_' . $user['vip'];
                    if ($user['vip'] != $data[$uid]['vip_level']) {
                        continue;
                    }

                    $total = ($halfSum[$key] ?? 0) + $data[$uid]['send_num'];
                    if ($total > $user['num']) {
                        $limitHalfUid[] = $uid;
                    }
                }

                $errors = [];
                if ($limitMonthUid && $sends) {
                    $sendIds = array_column($sends, 'id');
                    $monthUsers = BmsVipSendDetail::getListByWhere([['send_id', 'in', $sendIds], ['dateline', '>=', $monthStart], ['uid', 'in', $limitMonthUid]], 'send_id,vip_level,vip_day,uid,send_num,dateline', 'uid asc');
                    $errors = array_merge($errors, $monthUsers);
                }

                if ($limitHalfUid && $halfSends) {
                    $sendIds = array_column($halfSends, 'id');
                    $monthUsers = BmsVipSendDetail::getListByWhere([['send_id', 'in', $sendIds], ['dateline', '>=', $startTime], ['uid', 'in', $limitHalfUid]], 'send_id,vip_level,vip_day,uid,send_num,dateline', 'uid asc');
                    $errors = array_merge($errors, $monthUsers);
                }

                if ($errors) {
                    $users = XsUserProfile::getUserProfileBatch(array_column($errors, 'uid'));
                    $operators = CmsUser::getAdminUserBatch(array_column($sends, 'op_uid'));
                    $sends = array_column($sends, 'op_uid', 'id');

                    $filePath = PUBLIC_DIR . DS . 'vip_send_uid_import_check_' . Helper::getSystemUid() . '.csv';

                    @file_put_contents($filePath, 'UID,昵称,VIP等级,下发天数,下发时间,发放数量,操作人' . PHP_EOL);
                    $errorExcel = [];

                    foreach ($errors as &$v) {
                        $opUid = $sends[$v['send_id']] ?? 0;
                        $name = $users[$v['uid']]['name'] ?? '';
                        $operator = $operators[$opUid]['user_name'] ?? '';
                        $dateline = Helper::now($v['dateline']);

                        $v['name'] = $name;
                        $v['dateline'] = $dateline;
                        $v['operator'] = $operator;

                        $errorExcel[] = sprintf('%s,%s,%s,%s,%s,%s' . PHP_EOL, $v['uid'], $name, $v['vip_level'], $v['vip_day'], $dateline, $v['send_num'], $operator);
                    }

                    @file_put_contents($filePath, $errorExcel, FILE_APPEND);

                    $errorUrl = Helper::uploadOss($filePath);
                    @unlink($filePath);

                    return [false, ['url' => $errorUrl, 'uids' => array_merge($limitMonthUid, $limitHalfUid), 'errors' => $errors]];
                } else {
                    if ($limitMonthUid || $limitHalfUid) {
                        return [false, ['url' => '', 'uids' => array_merge($limitMonthUid, $limitHalfUid), 'errors' => []]];
                    }
                }
            }
        }
        return [true, []];
    }

    public function import($params)
    {
        $detailDatas = $this->getImportData($params['data']);
        $this->saveData($detailDatas, Helper::getSystemUid());
    }

    private function getImportData(array $params)
    {
        $detailDatas = [];
        $msg = [];
        foreach ($params as $line => $data) {
            $tmp = [
                'vip_level'   => $data['vip_level'],
                'vip_day'     => $data['vip_day'],
                'uid'         => $data['uid'],
                'remark'      => $data['remark'],
                'dateline'    => time(),
                'update_time' => time(),
                'type'        => (int)array_get($data, 'type', 0),
                'send_num'    => max(intval($data['send_num']), 1),
            ];

            if ($tmp['type'] == 0 && $tmp['send_num'] != 1) {
                $msg[] = '第' . $line . '行数据，直接生效类型的VIP发放数量只能是1';
            }
            if ($tmp['send_num'] < 1 || $tmp['send_num'] > 100) {
                $msg[] = '第' . $line . '行数据，VIP发放数量必须为1-100之间的整数';
            }

            $detailDatas[] = $tmp;
        }

        if (!empty($msg)) {
            throw new ApiException(ApiException::MSG_ERROR, implode(';', $msg) . '请检查后重试');
        }

        $this->valid($detailDatas);

        return $detailDatas;
    }

    public function getDetailList($params)
    {
        $conditon = [];
        if (isset($params['id']) && !empty($params['id'])) {
            $conditon[] = ['send_id', '=', $params['id']];
        }
        if (isset($params['max_id']) && $params['max_id']) {
            $conditon[] = ['id', '<', $params['max_id']];
        }
        $result = BmsVipSendDetail::getListAndTotal($conditon, '*', 'id desc', $params['page'], $params['limit']);

        if ($result['total'] == 0 || !$result['data']) {
            return $result;
        }
        foreach ($result['data'] as &$v) {
            $v['display_vip_level'] = isset(BmsVipSendDetail::$displayVipLevel[$v['vip_level']]) ?
                BmsVipSendDetail::$displayVipLevel[$v['vip_level']] : $v['vip_level'];
        }
        return $result;
    }

    public function getRecordList(array $params): array
    {
        $conditions = [
            ["l.source", '=', XsSendPropCardLog::SOURCE_GIVE],
            ["l.prop_card_type", '=', XsPropCardConfig::TYPE_CAN_SEND_VIP_CARD],
        ];

        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = ["b.bigarea_id", '=', $params['bigarea_id']];
        }
        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ["l.uid", '=', $params['uid']];
        }
        if (isset($params['sender']) && !empty($params['sender'])) {
            $conditions[] = ["l.sender", '=', $params['sender']];
        }
        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])) {
            $conditions[] = ["l.dateline", '>=', strtotime($params['dateline_sdate'])];
        }
        if (isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditions[] = ["l.dateline", '<', strtotime($params['dateline_edate']) + 86400];
        }

        $list = XsSendPropCardLog::getListJoinPropCard($conditions, "l.id desc", $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        foreach ($list['data'] as &$item) {
            $extend = json_decode($item['extend'], true);
            if ($extend) {
                $item['vip_level'] = $extend['level'] ?? '';
                $item['vip_days'] = $extend['days'] ?? '';
            }
            $item['dateline'] = Helper::now($item['dateline']);
        }

        return $list;
    }
}
