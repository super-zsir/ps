<?php

namespace Imee\Service\Operate\User;

use Imee\Comp\Operate\Auth\Models\Cms\CmsModuleUserBigarea;
use Imee\Exception\ApiException;
use Imee\Models\Rpc\PsRpc;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Service\Domain\Service\Ka\StatusService;
use Imee\Service\Helper;
use Imee\Service\StatusService as ServiceStatusService;

class OpenScreenCardService
{
    /** @var PsRpc */
    private $rpc;

    function __construct()
    {
        $this->rpc = new PsRpc();
    }

    private $statusMap = [
        1 => '未使用',
        2 => '审核中',
        3 => '审核通过',
        4 => '审核拒绝',
        5 => '已过期',
        6 => '已失效',
    ];

    private $canSendMap = [
        '0' => '否',
        '1' => '是',
    ];

    private $sourceMap = [
        '0' => '后台下发',
        '1' => '他人赠送',
    ];

    private $reasonMap = [
        1  => '涉黄低俗：包含色情、性暗示内容',
        2  => '涉政：包含政治敏感内容',
        3  => '违法行为：赌博、毒品、枪支、违禁品等违法内容',
        4  => '涉宗教：包含宗教敏感内容',
        5  => '暴力血腥：有暴力、流血、虐待等内容',
        6  => '诈骗/欺骗 ：引导诈骗、诱导、虚假信息',
        7  => '不文明用语：含侮辱、谩骂、歧视性语言',
        8  => '广告推广：包含广告、水印、二维码等推广信息',
        9  => '联系方式：包含Line，手机号码等',
        10 => '违规交易 ： 涉及违法服务、非法售卖行为等',
        11 => '未成年不适：未成年/对未成年人不友好或诱导行为',
        12 => '侵犯隐私：曝露身份证、手机号、住址等隐私信息',
        13 => '图片质量差 ：图片模糊、丑陋、不美观',
        14 => '侵权内容 ： 含未经授权的品牌logo、人物名',
        15 => '其他违规行为：请联系官方运营人员确认',
        16 => '招募主播：引导主播加入公会',
        17 => '币商宣传：为币商宣传',
    ];

    private $typeMap = [
        1 => '静态',
        2 => '动态'
    ];

    public function getList(array $params): array
    {
        $query = [
            'page_num'  => intval($params['page'] ?? 1),
            'page_size' => intval($params['limit'] ?? 10),
        ];
        if (!empty($params['uid']) && is_numeric($params['uid'])) {
            $query['uid'] = intval($params['uid']);
        }
        $adminBigareaIds = CmsModuleUserBigarea::getBigareaList($params['admin_uid'], false);
        if (empty($adminBigareaIds)) {
            return [];
        }
        $adminBigareaIds = array_keys($adminBigareaIds);

        if (!empty($params['user_big_area_id']) && is_numeric($params['user_big_area_id'])) {
            if (!in_array($params['user_big_area_id'], $adminBigareaIds)) {
                return [];
            }
            $query['user_big_area_id_list'] = [intval($params['user_big_area_id'])];
        } else {
            $query['user_big_area_id_list'] = array_map('intval', $adminBigareaIds);
        }
        if (!empty($params['status']) && is_numeric($params['status'])) {
            $query['status'] = intval($params['status']);
        }

        if (!empty($params['start_dateline']) && !empty($params['end_dateline'])) {
            $query['start_dateline'] = strtotime($params['start_dateline']);
            $query['end_dateline'] = strtotime($params['end_dateline']);

            if ($query['start_dateline'] > $query['end_dateline']) {
                throw new ApiException(ApiException::MSG_ERROR, "开始时间不能大于结束时间");
            }
        }

        list($res, $_) = $this->rpc->call(PsRpc::API_OPEN_SCREEN_CARD_LIST, ['json' => $query]);
        if (!isset($res['common']['err_code']) || $res['common']['err_code'] != 0) {
            throw new ApiException(ApiException::MSG_ERROR, $res['common']['err_msg'] ?? '获取开屏卡列表失败');
        }

        foreach ($res['info_list'] as &$value) {
            $value['dateline'] = date('Y-m-d H:i:s', $value['dateline']);
            $value['img_url_full'] = Helper::getHeadUrl($value['img_url']);
            $value['expired_time'] = date('Y-m-d H:i:s', $value['expired_time']);
            $value['can_send'] = (string)$value['can_send'];
            $value['user_big_area_id'] = (string)$value['user_big_area_id'];
            $value['source_uid'] = $value['source_uid'] ?: '-';

            $value['reject_type'] = empty($value['reject_type']) ? '' : (string)$value['reject_type'];
        }

        return ['data' => $res['info_list'], 'total' => $res['total']];
    }

    public function send(array $params): array
    {
        if (!$params) {
            throw new ApiException(ApiException::MSG_ERROR, '参数错误');
        }

        $params = Helper::trimParams($params);

        $adminBigareaIds = CmsModuleUserBigarea::getBigareaList(Helper::getSystemUid(), false);

        if (empty($adminBigareaIds)) {
            throw new ApiException(ApiException::MSG_ERROR, '无大区权限');
        }

        $uids = array_filter(array_column($params, 'uid'), 'intval');
        $uids = array_values(array_unique($uids));
        if (empty($uids)) {
            throw new ApiException(ApiException::MSG_ERROR, '上传数据为空');
        }

        $userBigareas = XsUserBigarea::getUserBigareas($uids);

        $operator = Helper::getSystemUserInfo()['user_name'] ?? '';

        foreach ($params as $key => &$rec) {
            $key = $key + 1;
            if (empty($rec['uid']) || !is_numeric($rec['uid'])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$key}行记录" . '用户id错误');
            }
            $rec['uid'] = intval($rec['uid']);

            if (!isset($userBigareas[$rec['uid']])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$key}行记录" . '用户大区不存在');
            }

            if (!in_array($userBigareas[$rec['uid']], array_keys($adminBigareaIds))) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$key}行记录" . '无该用户所属大区的权限，无法发放');
            }

            if (empty($rec['num']) || !is_numeric($rec['num'])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$key}行记录" . '发放数量错误');
            }
            $rec['num'] = intval($rec['num']);

            if (empty($rec['type']) || !is_numeric($rec['type'])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$key}行记录" . '发放类型错误');
            }
            $rec['type'] = intval($rec['type']);

            if (empty($rec['effective_hour']) || !is_numeric($rec['effective_hour']) || !in_array($rec['effective_hour'], [6, 12, 24, 36])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$key}行记录" . '有效期错误');
            }
            $rec['effective_hour'] = intval($rec['effective_hour']);
            if (empty($rec['expired_time']) || !is_string($rec['expired_time'])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$key}行记录" . '过期时间错误');
            }
            $rec['expired_time'] = strtotime($rec['expired_time']);
            if (empty($rec['reason'])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$key}行记录" . '发放原因错误');
            }
            if (!isset($rec['can_send']) || !in_array($rec['can_send'], [0, 1])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$key}行记录" . '是否可赠送错误');
            }
            $rec['can_send'] = intval($rec['can_send']);

            foreach ($rec as $k => $_) {
                if (!in_array($k, ['uid', 'num', 'effective_hour', 'expired_time', 'can_send', 'reason', 'type'])) {
                    unset($rec[$k]);
                }
            }
        }

        $params = ['info_list' => array_values($params), 'operator' => $operator];
        list($res, $_) = $this->rpc->call(PsRpc::API_OPEN_SCREEN_CARD_SEND, ['json' => $params]);
        if (!isset($res['common']['err_code']) || $res['common']['err_code'] != 0) {
            throw new ApiException(ApiException::MSG_ERROR, $res['common']['err_msg'] ?? '发放失败');
        }
        return ['id' => 0, 'after_json' => count($params['info_list']) > 500 ? ['count' => count($params['info_list']), 'info_list' => array_chunk($params['info_list'], 500)[0]] : $params];
    }

    public function expire(array $params): array
    {
        if (empty($params['id']) || !is_numeric($params['id'])) {
            throw new ApiException(ApiException::MSG_ERROR, '参数错误');
        }

        $data = [
            'id'       => intval($params['id']),
            'operator' => Helper::getSystemUserInfo()['user_name'] ?? '',
        ];

        list($res, $_) = $this->rpc->call(PsRpc::API_OPEN_SCREEN_CARD_EXPIRE, ['json' => $data]);
        if (!isset($res['common']['err_code']) || $res['common']['err_code'] != 0) {
            throw new ApiException(ApiException::MSG_ERROR, $res['common']['err_msg'] ?? '过期失败');
        }
        return ['id' => $params['id'], 'after_json' => []];
    }

    public function audit(array $params): array
    {
        if (empty($params['id']) || !is_numeric($params['id'])) {
            throw new ApiException(ApiException::MSG_ERROR, '参数错误');
        }

        if (!in_array($params['status'], [3, 4])) {
            throw new ApiException(ApiException::MSG_ERROR, '参数错误');
        }

        $data = [
            'id'          => intval($params['id']),
            'pass'        => intval($params['status']) == 3,
            'reject_type' => intval($params['status']) == 3 ? 0 : intval($params['reject_type'] ?? 0),
            'operator'    => Helper::getSystemUserInfo()['user_name'] ?? '',
        ];

        list($res, $_) = $this->rpc->call(PsRpc::API_OPEN_SCREEN_CARD_AUDIT, ['json' => $data]);
        if (!isset($res['common']['err_code']) || $res['common']['err_code'] != 0) {
            throw new ApiException(ApiException::MSG_ERROR, $res['common']['err_msg'] ?? '审核失败');
        }

        return ['id' => $params['id'], 'after_json' => $data];
    }

    public function getStatusMap(): array
    {
        return StatusService::formatMap($this->statusMap);
    }

    public function getCanSendMap(): array
    {
        return StatusService::formatMap($this->canSendMap);
    }

    public function getAuditStatusMap(): array
    {
        $map = [
            3 => '审核通过',
            4 => '审核拒绝',
        ];
        return StatusService::formatMap($map);
    }

    public function getReasonMap(): array
    {
        return StatusService::formatMap($this->reasonMap);
    }

    public function getSourceMap(): array
    {
        return StatusService::formatMap($this->sourceMap);
    }

    public function getHourMap(): array
    {
        $hourMap = [
            6  => '6小时', 
            12 => '12小时',
            24 => '24小时',
            36 => '36小时',
        ];
        return StatusService::formatMap($hourMap);
    }

    public function getTypeMap(): array
    {
        return StatusService::formatMap($this->typeMap);
    }
}