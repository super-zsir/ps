<?php

namespace Imee\Service\Operate\Minicard;

use Imee\Exception\ApiException;
use Imee\Models\Rpc\PsRpc;
use Imee\Models\Xs\XsItemCard;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class MiniCardService
{
    /** 
     * @var PsRpc $rpc
     */
    private $rpc;
    public function __construct()
    {
        $this->rpc = new PsRpc();
    }

    public function getListAndTotal(array $params, int $page, int $limit): array
    {
        $condition = [
            ['type', '=', $params['type']]
        ];
        if (!empty($params['id'])) {
            $condition[] = ['id', '=', $params['id']];
        }
        if (!empty($params['name'])) {
            $condition[] = ['name_json', 'like', $params['name']];
        }

        $data = XsItemCard::getListAndTotal($condition, '*', 'id desc', $page, $limit);
        if (empty($data['data'])) {
            return $data;
        }

        $languages = XsItemCard::$languageMap;
        $fieldArray = XsItemCard::$jsonFieldMap[$params['type']];

        foreach ($data['data'] as &$item) {
            /*$name = '';
            if (!empty($json = @json_decode($item['name_json'], true))) {
                foreach ($languages as $lan => $cname) {
                    $name .= sprintf("<p>%s：%s</p>", $cname, $json[$lan] ?? '');
                }
            }*/
            $json = @json_decode($item['name_json'], true);
            $name = $json['zh_cn'] ?? '';

            $item['name'] = $name;
            $item['icon_url'] = Helper::getHeadUrl($item['icon']);

            $json = @json_decode($item['extend_json'], true);

            foreach ($fieldArray as $field) {
                $item[$field] = $json[$field] ?? '';
                $item[$field . '_url'] = Helper::getHeadUrl($item[$field]);
            }

            $item['create_time'] = $item['create_time'] > 0 ? Helper::now($item['create_time']) : '';
            $item['update_time'] = $item['update_time'] > 0 ? Helper::now($item['update_time']) : '';
        }

        return $data;
    }

    public function create(array $params): array
    {
        $extend = $this->getExtendField($params);
        $data = [
            'id'             => 0,
            'name'           => json_encode($params['name'], JSON_UNESCAPED_UNICODE),
            'icon'           => $params['icon'],
            'description'    => '',
            'item_card_type' => $params['type'],
            'extend_json'    => json_encode($extend),
            'oprater'        => Helper::getSystemUserInfo()['user_name'] ?? '',
        ];
        list($res, $_) = $this->rpc->call(PsRpc::API_MINI_CARD_SET, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return ['id' => $res['common']['id'] ?? ($res['id'] ?? 0), 'after_json' => $data];
        }

        throw new ApiException(ApiException::MSG_ERROR, '接口错误：' . $res['common']['msg']);
    }

    public function modify(array $params): array
    {
        $id = $params['id'];
        if ($id < 1 || !($info = XsItemCard::findOne($id))) {
            throw new ApiException(ApiException::MSG_ERROR, '记录不存在');
        }

        $extend = $this->getExtendField($params);
        $data = [
            'id'             => $id,
            'name'           => json_encode($params['name'], JSON_UNESCAPED_UNICODE),
            'icon'           => $params['icon'],
            'description'    => '',
            'item_card_type' => $params['type'],
            'extend_json'    => json_encode($extend),
            'oprater'        => Helper::getSystemUserInfo()['user_name'] ?? '',
        ];
        list($res, $_) = $this->rpc->call(PsRpc::API_MINI_CARD_SET, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return ['id' => $id, 'before_json' => $info, 'after_json' => $data];
        }

        throw new ApiException(ApiException::MSG_ERROR, '接口错误：' . $res['common']['msg']);
    }

    public function getInfo(int $id, int $type): array
    {
        $rec = XsItemCard::findOne($id);
        if (!$rec) {
            return [];
        }

        $json = @json_decode($rec['extend_json'], true);
        $fieldArray = XsItemCard::$jsonFieldMap[$rec['type']];
        foreach ($fieldArray as $field) {
            $rec[$field] = $json[$field] ?? '';
            $rec[$field . '_url'] = Helper::getHeadUrl($rec[$field]);
        }

        $rec['name'] = (array)@json_decode($rec['name_json'], true);
        $rec['icon_url'] = Helper::getHeadUrl($rec['icon']);

        return $rec;
    }

    /**
     * 获取扩展字段
     * @var array $params
     * @return array
     */
    private function getExtendField(array $params): array
    {
        $extend = [];
        $fieldArray = XsItemCard::$jsonFieldMap[$params['type']];
        foreach ($fieldArray as $field) {
            $extend[$field] = $params[$field] ?? '';
        }
        return $extend;
    }
}