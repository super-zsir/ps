<?php

namespace Imee\Export;

use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Service\Commodity\CommodityService;

class CommodityListExport extends BaseExport
{
    /**
     * @var string[] 表字段/title
     */
    protected static $header = [
        'cid' => '记录id',
        'ocid' => '物品id',
        'name' => '物品中文名称',
        'mark' => '备注',
        'type' => '物品类型',
        'price' => '价格(星球币)',
        'period' => '有效天数',
        'period_hour' => '有效小时数',
        'ext_id' => '物品关联ID',
        'sub_type' => '物品子类型',
        'saling_on_shop' => '商店出售',
        'grant_limit' => '购买限制',
        'state' => '审核状态',
        'can_give' => '能否赠送',
        'can_opened_by_box' => '能否礼盒开出',
        'money_type' => '财富类型',
        'dateline' => '创建时间',
    ];

    public function getTitle(): array
    {
        return array_values($this->getHeader());
    }

    public function export($filePathName, $filterParams)
    {
        $params = $filterParams;
        $keys = array_keys($this->getHeader());

        $this->setFileHeader($filePathName);

        $service = new CommodityService();

        $pageSize = 1000;
        $page = 1;
        while (true) {

            $list = $service->getListAndTotal($params, 'cid desc', $page++, $pageSize);
            if (!$list['total']) {
                break;
            }

            $newArr = [];
            foreach ($list['data'] as $item) {
                $item['state'] = array_get(XsCommodityAdmin::$state, $item['state'], ' - ');
                $item['can_give'] = $item['can_give'] == 1 ? '可以' : '不可以';
                $item['can_opened_by_box'] = $item['can_opened_by_box'] == 1 ? '可以' : '不可以';

                $_temp = [];
                foreach ($keys as $value) {
                    $_v = !empty($item[$value]) ? $item[$value] : '-';
                    $_temp[$value] = is_string($_v) ? htmlspecialchars($_v) : $_v;
                }
                $newArr[] = $_temp;
            }
            $tmpStr = $this->formatCsvTextBatch($newArr);

            file_put_contents($filePathName, htmlspecialchars_decode($tmpStr), FILE_APPEND);
            if (count($list['data']) < $pageSize) {
                break;
            }

            usleep(10 * 1000);
        }
    }

    /**
     * 获取表头
     */
    protected function getHeader(): array
    {
        return self::$header;
    }
}