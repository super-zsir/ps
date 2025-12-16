<?php

namespace Imee\Service\Operate\Activity;

use Imee\Comp\Common\Redis\RedisSimple;
use Imee\Exception\ApiException;
use Imee\Models\Config\BbcActNavigationBar;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Models\Xs\XsActHonourWallConfig;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsPkPropCardFirstGiftConfig;
use Imee\Models\Xs\XsTopUpActivity;
use Imee\Models\Xsst\XsstActNavigationBarSearch;
use Imee\Service\Helper;
use Imee\Service\StatusService;

class ActNavigationBarService
{

    const DOMAIN = 'https://page.partystar.chat';
    const DOMAIN_DEV = 'https://dev.partystar.cloud/frontend';

    const LINK = '%s/navigate-template/?aid=%d&clientScreenMode=1';

    protected $redis;

    public function __construct()
    {
        $this->redis = new RedisSimple();
    }

    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $this->updateCache();//更新查询缓存

        $nowTime = time();
        $id = intval(array_get($params, 'id', 0));
        $state = intval(array_get($params, 'state', 0));
        $title = trim($params['title'] ?? '');
        $language = trim($params['language'] ?? '');
        $admin = intval($params['admin'] ?? 0);

        $query = [];
        $id && $query[] = ['id', '=', $id];
        $title && $query[] = ['title', 'like', $title];
        $language && $query[] = ['language', '=', $language];
        $admin && $query[] = ['admin_id', '=', $admin];
        if ($state) {
            switch ($state) {
                case BbcActNavigationBar::STATE_WAIT:
                    $barId = XsstActNavigationBarSearch::getListByWhere([['start_time', '>', $nowTime]], 'bar_id');
                    break;
                case BbcActNavigationBar::STATE_RUNNING:
                    $barId = XsstActNavigationBarSearch::getListByWhere([['start_time', '<=', $nowTime], ['end_time', '>=', $nowTime]], 'bar_id');
                    break;
                case BbcActNavigationBar::STATE_END:
                    $barId = XsstActNavigationBarSearch::getListByWhere([['end_time', '<', $nowTime]], 'bar_id');
                    break;
                default:
                    $barId = [];
                    break;
            }
            if (empty($barId)) {
                $query[] = ['id', 'IN', [0]];
            } else {
                $barId = array_column($barId, 'bar_id');
                $id && $barId[] = $id;
                $query[] = ['id', 'IN', $barId];
            }
        }

        $data = BbcActNavigationBar::getListAndTotal($query, '*', 'id desc', $page, $limit);


        $allId = array_column($data['data'], 'id');

        $searchLists = XsstActNavigationBarSearch::getListByWhere([['bar_id', 'in', $allId]], 'bar_id,start_time,end_time');
        $searchLists = array_column($searchLists, null, 'bar_id');


        foreach ($data['data'] as &$rec) {
            $rec = array_merge($rec, isset($searchLists[$rec['id']]) ? $searchLists[$rec['id']] : []);

            $dateline = array_get($rec, 'dateline', 0);
            $start = array_get($rec, 'start_time', 0);
            $end = array_get($rec, 'end_time', 0);

            $rec['dateline'] = $dateline ? date('Y-m-d H:i:s', $dateline) : '';
            $rec['admin'] = Helper::getAdminName($rec['admin_id'] ?? '');

            if ($start && $end != BbcActNavigationBar::MYSQL_UINT_MAX) {

            }
            $rec['time_show'] = sprintf("%s ~ %s",
                $start > BbcActNavigationBar::START_TIME_MIN ? date('Y-m-d H:i:s', $start) : '现在',
                $end < BbcActNavigationBar::MYSQL_UINT_MAX ? date('Y-m-d H:i:s', $end) : '永久');

            $rec['state'] = BbcActNavigationBar::STATE_END;
            if ($start > $nowTime && $end > $nowTime) {
                $rec['state'] = BbcActNavigationBar::STATE_WAIT;
            } elseif ($start <= $nowTime && $end >= $nowTime) {
                $rec['state'] = BbcActNavigationBar::STATE_RUNNING;
            }

            $domain = ENV == 'dev' ? self::DOMAIN_DEV : self::DOMAIN;
            $link = sprintf(self::LINK, $domain, $rec['id']);
            $rec['page_url'] = [
                'title'        => $link,
                'value'        => $link,
                'type'         => 'url',
                'url'          => $link,
                'resourceType' => 'static'
            ];

        }
        return $data;
    }

    public function getOptions($isActId = false): array
    {
        $actIdMap = [];
        $actIdRealMap = [];
        //1
        $data = BbcTemplateConfig::getListByWhere([
            ['vision_type', '!=', 0], [
                'type', 'NOT IN', [
                    BbcTemplateConfig::TYPE_ONE_PK,
                    BbcTemplateConfig::TYPE_TASK,
                    BbcTemplateConfig::TYPE_GIFT_TASK,
                    BbcTemplateConfig::TYPE_WHEEL_LOTTERY,
                    BbcTemplateConfig::TYPE_MULTI_TASK,
                    BbcTemplateConfig::TYPE_EXCHANGE,
                ]
            ]
        ], 'id ,title', 'id desc');
        $map = [];
        foreach ($data as $k => $v) {
            $map[$v['id']] = sprintf("%s - %s", $v['id'], $v['title']);
        }
        $actIdMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_RANK] = StatusService::formatMap($map);
        $actIdRealMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_RANK] = $map;
        //2
        $data = BbcTemplateConfig::getListByWhere([['type', 'IN', [BbcTemplateConfig::TYPE_TASK, BbcTemplateConfig::TYPE_GIFT_TASK]]], 'id ,title', 'id desc');
        $map = [];
        foreach ($data as $k => $v) {
            $map[$v['id']] = sprintf("%s - %s", $v['id'], $v['title']);
        }
        $actIdMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_TASK] = StatusService::formatMap($map);
        $actIdRealMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_TASK] = $map;
        //3
        $data = BbcTemplateConfig::getListByWhere([['type', 'IN', [BbcTemplateConfig::TYPE_MULTI_TASK]]], 'id ,title', 'id desc');
        $map = [];
        foreach ($data as $k => $v) {
            $map[$v['id']] = sprintf("%s - %s", $v['id'], $v['title']);
        }
        $actIdMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_MUTLI_TASK] = StatusService::formatMap($map);
        $actIdRealMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_MUTLI_TASK] = $map;
        //4
        $data = BbcTemplateConfig::getListByWhere([['type', 'IN', [BbcTemplateConfig::TYPE_WHEEL_LOTTERY]]], 'id ,title', 'id desc');
        $map = [];
        foreach ($data as $k => $v) {
            $map[$v['id']] = sprintf("%s - %s", $v['id'], $v['title']);
        }
        $actIdMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_WHEEL] = StatusService::formatMap($map);
        $actIdRealMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_WHEEL] = $map;
        //5
        $data = XsTopUpActivity::getListByWhere([], 'id ,title', 'id desc');
        $map = [];
        foreach ($data as $k => $v) {
            $map[$v['id']] = sprintf("%s - %s", $v['id'], $v['title']);
        }
        $actIdMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_TOP_UP] = StatusService::formatMap($map);
        $actIdRealMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_TOP_UP] = $map;
        //6
        $allBigarea = XsBigarea::getAllNewBigArea();
        $data = XsPkPropCardFirstGiftConfig::getListByWhere([], 'id ,bigarea_id', 'id desc');
        foreach ($data as &$v) {
            $v['title'] = isset($allBigarea[$v['bigarea_id']]) ? $allBigarea[$v['bigarea_id']] : '';
        }
        $map = [];
        foreach ($data as $k => $vv) {
            $map[$vv['id']] = sprintf("%s - %s", $vv['id'], $vv['title']);
        }
        $actIdMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_FIRST_RECHARGE] = StatusService::formatMap($map);
        $actIdRealMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_FIRST_RECHARGE] = $map;
        //7
        $data = XsActHonourWallConfig::getListByWhere([], 'id ,title', 'id desc');
        $map = [];
        foreach ($data as $k => $vv) {
            $map[$vv['id']] = sprintf("%s - %s", $vv['id'], $vv['title']);
        }
        $actIdMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_HONOUR_WALL] = StatusService::formatMap($map);
        $actIdRealMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_HONOUR_WALL] = $map;
        //8
        $data = BbcTemplateConfig::getListByWhere([['type', 'IN', [BbcTemplateConfig::TYPE_EXCHANGE]]], 'id ,title', 'id desc');
        $map = [];
        foreach ($data as $k => $vv) {
            $map[$vv['id']] = sprintf("%s - %s", $vv['id'], $vv['title']);
        }
        $actIdMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_EXCHANGE] = StatusService::formatMap($map);
        $actIdRealMap[BbcActNavigationBar::ACT_TEMPLATE_TYPE_EXCHANGE] = $map;

        if ($isActId) {
            return $actIdRealMap;
        }

        return [
            'state_map'    => StatusService::formatMap(BbcActNavigationBar::$stateMaps),
            'act_type_map' => StatusService::formatMap(BbcActNavigationBar::$actTypeMaps),
            'language_map' => StatusService::formatMap(Helper::getLanguageArr()),
            'act_id_map'   => $actIdMap,
        ];
    }

    public function getInfo($id): array
    {
        $data = BbcActNavigationBar::findOne($id);

        if (!empty($data)) {
            $visionContent = @json_decode($data['vision_content_json'], true);
            $mainRace = @json_decode($data['main_race_json'], true);
            $attachRace = @json_decode($data['attach_race_json'], true);

            $visionContent['button_img_share_show'] = Helper::getHeadUrl($visionContent['button_img_share'] ?? '');
            $visionContent['button_img_return_show'] = Helper::getHeadUrl($visionContent['button_img_return'] ?? '');

            foreach ($mainRace as &$v) {
                $v['act_type'] = (string)$v['act_type'];
                $v['act_id'] = (string)$v['act_id'];
                $v['start_icon_show'] = Helper::getHeadUrl($v['start_icon'] ?? '');
                $v['end_icon_show'] = Helper::getHeadUrl($v['end_icon'] ?? '');
            }
            foreach ($attachRace as &$v) {
                $v['act_type'] = (string)$v['act_type'];
                $v['act_id'] = (string)$v['act_id'];
                $v['icon_show'] = Helper::getHeadUrl($v['icon'] ?? '');
            }
            $data['vision_content_json'] = $visionContent;
            $data['main_race_json'] = $mainRace;
            $data['attach_race_json'] = $attachRace;
        }

        return $data;
    }

    public function add($params): array
    {
        $data = $this->validateAndFormatData($params);
        list($flg, $rec) = BbcActNavigationBar::add($data);
        $flg && BbcActNavigationBar::updateSearchTime($rec, true);
        return [$flg, $flg ? ['id' => $rec, 'after_json' => array_merge($data, ['id' => $rec])] : $rec];
    }

    public function copy($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = BbcActNavigationBar::findOne($id);
        if (empty($setting)) {
            return [false, 'ID数据错误'];
        }

        unset($setting['id']);
        $setting['dateline'] = time();
        $setting['admin_id'] = intval($setting['admin_id'] ?? 0);

        list($flg, $rec) = BbcActNavigationBar::add($setting);
        $flg && BbcActNavigationBar::updateSearchTime($rec, true);
        return [$flg, $flg ? ['id' => $rec, 'after_json' => array_merge($setting, ['id' => $rec])] : $rec];
    }

    public function modify($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = BbcActNavigationBar::findOne($id);
        if (empty($setting)) {
            return [false, 'ID数据错误'];
        }

        $update = $this->validateAndFormatData($params);
        list($flg, $rec) = BbcActNavigationBar::edit($id, $update);
        $flg && BbcActNavigationBar::updateSearchTime($id, true);

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => array_merge($setting, $update)] : $rec];
    }

    public function delete($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = BbcActNavigationBar::findOne($id);
        if (empty($setting)) {
            return [false, 'ID数据错误'];
        }

        $flg = BbcActNavigationBar::deleteById($id);
        $flg && XsstActNavigationBarSearch::deleteByWhere([['bar_id', '=', $id]]);

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => []] : '删除失败'];
    }

    private function validateAndFormatData($params): array
    {
        $id = (int)array_get($params, 'id', 0);

        $title = trim($params['title'] ?? '');
        $language = trim($params['language'] ?? '');
        $visionContentJson = $params['vision_content_json'] ?? [];
        $mainRaceJson = $params['main_race_json'] ?? [];
        $attachRaceJson = $params['attach_race_json'] ?? [];
        $adminId = intval($params['admin_id'] ?? 0);
        $options = $this->getOptions(true);

        if (count($mainRaceJson) < 1 || count($mainRaceJson) > 50) {
            throw new ApiException(ApiException::MSG_ERROR, '主赛程区域 最少填写1组，最多50组');
        }
        if (count($attachRaceJson) > 3) {
            throw new ApiException(ApiException::MSG_ERROR, '附加玩法区域 最少0组，最多3组');
        }

        unset($visionContentJson['button_img_share_show']);
        unset($visionContentJson['button_img_return_show']);

        $msg = [];
        foreach ($mainRaceJson as $k => $v) {
            unset($mainRaceJson[$k]['start_icon_show']);
            unset($mainRaceJson[$k]['end_icon_show']);
            $mainRaceJson[$k]['act_type'] = intval($v['act_type']);
            $mainRaceJson[$k]['act_id'] = intval($v['act_id']);
            if (!isset($options[$v['act_type']][$v['act_id']])) {
                $msg[] = sprintf('主赛程区域【 %s】活动ID【%s】错误', BbcActNavigationBar::$actTypeMaps[$v['act_type']], $v['act_id']);
            }
        }

        foreach ($attachRaceJson as $k => $v) {
            unset($attachRaceJson[$k]['icon_show']);
            $attachRaceJson[$k]['act_type'] = intval($v['act_type']);
            $attachRaceJson[$k]['act_id'] = intval($v['act_id']);
            if (!isset($options[$v['act_type']][$v['act_id']])) {
                $msg[] = sprintf('附加玩法区域【 %s】活动ID【%s】错误', BbcActNavigationBar::$actTypeMaps[$v['act_type']], $v['act_id']);
            }
        }

        if (!empty($msg)) {
            throw new ApiException(ApiException::MSG_ERROR, implode(";", $msg));
        }

        $data = [
            'title'               => $title,
            'language'            => $language,
            'vision_content_json' => @json_encode($visionContentJson, JSON_UNESCAPED_UNICODE),
            'main_race_json'      => @json_encode($mainRaceJson, JSON_UNESCAPED_UNICODE),
            'attach_race_json'    => @json_encode($attachRaceJson, JSON_UNESCAPED_UNICODE),
            'admin_id'            => $adminId,
        ];
        empty($id) && $data['dateline'] = time();
        return $data;
    }

    public static function getStateMaps($value = null, string $format = '')
    {
        $map = BbcActNavigationBar::$stateMaps;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getActTypeMaps($value = null, string $format = '')
    {
        $map = BbcActNavigationBar::$actTypeMaps;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public function updateCache($limit = 300)
    {
        $key = 'act_navigation_bar_list';
        $time = time() - (ENV == 'dev' ? 60 : 300);

        if ($this->redis->has($key) && $this->redis->get($key) > $time) {
            return;
        }
        BbcActNavigationBar::updateAllSearchTime($limit);
        $this->redis->set($key, time());
    }

}