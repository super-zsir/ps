<?php

namespace Imee\Service\Operate\Chatroom;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsChatroom;
use Imee\Models\Xs\XsChatroomBackground;
use Imee\Models\Xsst\XsstChatroomBackground;
use Imee\Service\Helper;
use Imee\Service\StatusService;

class ChatroomBackgroundService
{
    /**
     * @var XsChatroomBackground $model
     */
    private $model = XsChatroomBackground::class;

    /**
     * @var XsstChatroomBackground $backGroundModel
     */
    private $backGroundModel = XsstChatroomBackground::class;

    /**
     * @var XsChatroom $chatroomModel
     */
    private $chatroomModel = XsChatroom::class;
    /**
     * @var CmsUser $adminModel
     */
    private $adminModel = CmsUser::class;

    public function getListAndTotal(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = $this->model::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        foreach ($list['data'] as &$item) {
            $item['begin_time'] = $item['begin_time'] ? Helper::now($item['begin_time']) : '';
            $item['end_time'] = $item['end_time'] ? Helper::now($item['end_time']) : '';
            $item['online_time'] = $item['begin_time'] . ' ... ' . $item['end_time'];
            $item['dateline'] = $item['dateline'] ? Helper::now($item['dateline']) : '';
            $item['icon'] = Helper::getHeadUrl(sprintf($this->backGroundModel::ICON_PATH, $item['type']));
            $item['icon2'] = Helper::getHeadUrl(sprintf($this->backGroundModel::ICON2_PATH, $item['type']));
        }

        return $list;
    }

    public function create(array $params): array
    {
        $type = trim($params['type'] ?? '');
        $deleted = intval($params['deleted'] ?? 1);
        $ordering = intval($params['ordering'] ?? 100);
        $language = trim($params['language'] ?? 'zh_cn');
        $beginTime = trim($params['begin_time'] ?? '');
        $endTime = trim($params['end_time'] ?? '');
        $rids = trim($params['rid'] ?? '');

        if ($rids) {
            $roomList = $this->chatroomModel::getInfoBatch(Helper::formatIdString($rids));
            $roomList && $rids = array_column($roomList, 'rid');
        }


        if (!in_array($language, Helper::getSystemUserLanguage())) {
            throw new ApiException(ApiException::MSG_ERROR, '你不能创建当前语言的聊天室背景');
        }

        $data = [
            'type'       => $type,
            'deleted'    => $deleted,
            'ordering'   => $ordering,
            'language'   => $language,
            'dateline'   => time(),
            'app_id'     => APP_ID,
            'begin_time' => $beginTime ? strtotime($beginTime) : 0,
            'end_time'   => $endTime ? strtotime($endTime) : 0,
        ];

        empty($rids) && $rids = [0];

        $addBatch = [];
        foreach ($rids as $rid) {
            $addBatch[] = array_merge($data, ['rid' => $rid]);
        }

        list($res, $msg, $_) = $this->model::addBatch($addBatch);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '添加失败，原因：' . $msg);
        }


        return ['after_json' => array_merge($data, ['rids' => implode(',', $rids)])];
    }

    public function modify(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $type = trim($params['type'] ?? '');
        $deleted = intval($params['deleted'] ?? 1);
        $ordering = intval($params['ordering'] ?? 100);
        $language = trim($params['language'] ?? 'zh_cn');
        $beginTime = trim($params['begin_time'] ?? '');
        $endTime = trim($params['end_time'] ?? '');
        $rid = intval($params['rid'] ?? 0);

        $info = $this->model::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '聊天室背景不存在');
        }

        if ($rid && empty($this->chatroomModel::findOne($rid))) {
            throw new ApiException(ApiException::MSG_ERROR, '聊天室不存在');
        }

        if ($id == 1 && ($deleted == $this->model::DELETED_DELETE || $beginTime || $endTime)) {
            throw new ApiException(ApiException::MSG_ERROR, '默认图片只能修改排序');
        }

        $data = [
            'rid'        => $rid,
            'type'       => $type,
            'deleted'    => $deleted,
            'ordering'   => $ordering,
            'language'   => $language,
            'begin_time' => $beginTime ? strtotime($beginTime) : 0,
            'end_time'   => $endTime ? strtotime($endTime) : 0,
        ];

        list($res, $msg) = $this->model::edit($id, $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '编辑失败，原因：' . $msg);
        }

        return ['id' => $id, 'after_json' => $data, 'before_json' => $info];
    }

    private function getConditions(array $params): array
    {
        $conditions = [
            ['language', 'IN', Helper::getSystemUserLanguage()],
            ['app_id', '=', APP_ID]
        ];

        $id = intval($params['id'] ?? 0);
        $rid = intval($params['rid'] ?? 0);

        $id && $conditions[] = ['id', '=', $id];
        $rid && $conditions[] = ['rid', '=', $rid];

        return $conditions;
    }

    public function getDeletedMap()
    {
        return StatusService::formatMap($this->model::$deletedMap, 'label,value');
    }

    public function getTypeMap(): array
    {
        return StatusService::formatMap($this->backGroundModel::getOptions(), 'label,value');
    }
}