<?php

namespace Imee\Service\Operate\Emoticons;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsEmoticonsGroup;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class EmoticonsTagService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $conditions = [];
        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = ['id', '=', $params['id']];
        }
        if (is_numeric($params['pay'] ?? '')) {
            $conditions[] = ['pay', '=', $params['pay']];
        }

        $list = XsEmoticonsGroup::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        $logs = BmsOperateLog::getFirstLogList('emoticonstag', array_column($list['data'], 'id'));
        foreach ($list['data'] as &$item) {
            $item['icon'] = $item['img'];
            $item['img_all'] = Helper::getHeadUrl($item['img']);
            $item['operator'] = $logs[$item['id']]['operate_name'] ?? '-';
            $item['dateline'] = isset($logs[$item['id']]['created_time']) ? Helper::now($logs[$item['id']]['created_time']) : '';

            $item['expire_at'] = $item['expire_at'] > 0 ? Helper::now($item['expire_at']) : '';

            $item['pay_txt'] = XsEmoticonsGroup::$payMap[$item['pay']] ?? '';

            if ($item['pay'] == XsEmoticonsGroup::PAY_ACTIVE) {
                $text = (array)(@json_decode($item['banner_text']));
                $lans = [];
                if (is_array($text)) {
                    foreach ($text as $lan => $txt) {
                        $item["desc_{$lan}"] = $txt;
                        $lans[] = '活动描述-' . (XsEmoticonsGroup::$text[$lan]['name'] ?? '') . '：' . $txt;
                    }
                }

                $item['pay_txt'] = [
                    'type'     => 'manMadeModal',
                    'modal_id' => 'emoticons_lan',
                    'title'    => $item['pay_txt'],
                    'value'    => $item['pay_txt'],
                    'params'   => [
                        'title' => '活动描述',
                        'desc'  => $lans,
                    ]
                ];
            } else if ($item['pay'] == XsEmoticonsGroup::PAY_YES) {

                $text = (array)(@json_decode($item['describe']));
                $lans = [];
                if (is_array($text)) {
                    foreach ($text as $lan => $txt) {
                        $item["tag_{$lan}"] = $txt;
                        $lans[] = '标签描述-' . (XsEmoticonsGroup::$text[$lan]['name'] ?? '') . '：' . $txt;
                    }
                }

                $item['describe_txt'] = [
                    'type'     => 'manMadeModal',
                    'modal_id' => 'emoticons_lan',
                    'title' => $item["tag_zh_cn"] ?? '',
                    'value' => $item["tag_zh_cn"] ?? '',
                    'params'   => [
                        'title' => '标签描述',
                        'desc'  => $lans,
                    ]
                ];

            } else {
                $item['pay_txt'] = [
                    'type'     => 'manMadeModal',
                    'modal_id' => '',
                    'title'    => $item['pay_txt'],
                    'value'    => $item['pay_txt'],
                    'params'   => [
                        'title' => '活动描述',
                        //'desc'  => $lans,
                    ]
                ];
            }
            $item['pay'] = (string)$item['pay'];
            $item['status'] = (string)$item['status'];
        }

        return $list;
    }

    public function add(array $params): array
    {
        $this->validate($params);

        $data = [
            'name'        => $params['name'],
            'icon'        => $params['icon'],
            'pay'         => (int)$params['pay'],
            'expire_at'   => (int)$params['expire_at'],
            'status'      => (int)$params['status'],
            'banner_url'  => $params['banner_url'],
            'banner_text' => $params['banner_text'],
            'describe'    => $params['describe'] ?? '',
        ];
        list($res, $msg) = $this->rpcService->createEmoticonsTag($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $msg, 'after_json' => $data];
    }

    private function validate(array &$params): void
    {
        $params = Helper::trimParams($params);
        $params['banner_text'] = '';

        $pay = $params['pay'];

        if ($pay == XsEmoticonsGroup::PAY_ACTIVE) {
            $bannerText = [];
            foreach (XsEmoticonsGroup::$text as $lan => $item) {
                $value = $params["desc_{$lan}"] ?? '';
                if ($item['required'] && !$value) {
                    throw new ApiException(ApiException::MSG_ERROR, "请填写活动描述-{$item['name']}");
                }

                $bannerText[$lan] = $value;
            }
            $params['banner_text'] = @json_encode($bannerText);

            if (empty($params['banner_url'])) {
                throw new ApiException(ApiException::MSG_ERROR, '请填写活动链接');
            }

            $status = $params['status'] ?? -1;
            if (!is_numeric($status) || !in_array($status, [0, 1])) {
                throw new ApiException(ApiException::MSG_ERROR, '请选择是否可见');
            }

            if ($status == 0 && empty($params['expire_at'])) {
                throw new ApiException(ApiException::MSG_ERROR, '请填写可见有效期');
            }

            $params['expire_at'] = strtotime($params['expire_at'] ?? '');
            if (empty($params['id']) && $params['expire_at'] > 0 && $params['expire_at'] <= time()) {
                throw new ApiException(ApiException::MSG_ERROR, '可见有效期需大于当前时间');
            }
            $params['describe'] = '';
        } else if ($pay == XsEmoticonsGroup::PAY_YES) {
            $bannerText = [];
            foreach (XsEmoticonsGroup::$text as $lan => $item) {
                $value = $params["tag_{$lan}"] ?? '';
                $bannerText[$lan] = $value;
            }
            $params['describe'] = @json_encode($bannerText);
            $params['expire_at'] = 0;
            $params['status'] = 0;
            $params['banner_url'] = '';
            $params['banner_text'] = '';
        } else {
            $params['expire_at'] = 0;
            $params['status'] = 0;
            $params['banner_url'] = '';
            $params['banner_text'] = '';
            $params['describe'] = '';
        }
    }

    public function edit(array $params): array
    {
        $info = XsEmoticonsGroup::findOne($params['id']);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '标签不存在');
        }

        $this->validate($params);

        $data = [
            'id'          => (int)$params['id'],
            'name'        => $params['name'],
            'icon'        => $params['icon'],
            'pay'         => (int)$params['pay'],
            'expire_at'   => (int)$params['expire_at'],
            'status'      => (int)$params['status'],
            'banner_url'  => $params['banner_url'],
            'banner_text' => $params['banner_text'],
            'describe'    => $params['describe'] ?? '',
        ];

        list($res, $msg) = $this->rpcService->updateEmoticonsTag($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $data['id'], 'before_json' => $info, 'after_json' => $data];
    }

    public function delete(int $id): void
    {
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, '标签不存在');
        }

        list($res, $msg) = $this->rpcService->deletedEmoticonsTag($id);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function getPayMap(): array
    {
        $map = XsEmoticonsGroup::$payMap;
        return StatusService::formatMap($map, 'label,value');
    }
}