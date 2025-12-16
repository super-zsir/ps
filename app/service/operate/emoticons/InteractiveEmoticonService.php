<?php

namespace Imee\Service\Operate\Emoticons;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsInteractiveEmoticons;
use Imee\Models\Xs\XsInteractiveEmoticonsListed;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class InteractiveEmoticonService
{
    /** @var PsService $rpc */
    private $rpc;

    public function __construct()
    {
        $this->rpc = new PsService();
    }

    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));
        $name = trim(array_get($params, 'name', ''));

        $query = [];
        $id && $query[] = ['id', '=', $id];
        $name && $query[] = ['name_json', 'like', $name];

        $data = XsInteractiveEmoticons::getListAndTotal($query, '*', 'id desc', $page, $limit);

        $logs = BmsOperateLog::getFirstLogList('enteractiveemoticons', array_column($data['data'], 'id'));
        $createLogs = BmsOperateLog::getFirstLogListByAction('enteractiveemoticons', array_column($data['data'], 'id'), 0);

        foreach ($data['data'] as &$rec) {
            $_id = array_get($rec, 'id', 0);
            $_log = array_get($logs, $_id, []);
            $_cLog = array_get($createLogs, $_id, []);

            $_createTime = array_get($rec, 'create_time', 0);
            $rec['create_time'] = $_createTime ? date('Y-m-d H:i:s', $_createTime) : '';
            $rec['create_name'] = array_get($_cLog, 'operate_name', '');
            $rec['operate_name'] = array_get($_log, 'operate_name', '');
            $rec['operate_time'] = array_get($_log, 'created_time', '');
            $rec['operate_time'] = $rec['operate_time'] ? date('Y-m-d H:i:s', $rec['operate_time']) : '';

            $rec['name_show'] = $this->getNameShow($rec['name_json']);
            $rec['icon_url'] = Helper::getHeadUrl($rec['icon'] ?? '');
            $rec['preview_image_url'] = Helper::getHeadUrl($rec['preview_image'] ?? '');

            list($rec['bigarea_id'], $rec['scene']) = $this->getBigareaAndScene($_id);
            if (empty($rec['bigarea_id'])) {
                $rec['state'] = XsInteractiveEmoticons::STATE_DOWN;
            } else {
                $rec['state'] = XsInteractiveEmoticons::STATE_NORMAL;
            }
            $rec['model_id'] = $_id;
        }
        return $data;
    }

    public function add($params): array
    {
        $data = $this->validateAndFormatData($params);
        list($flg, $rec) = $this->rpc->createInteractiveEmoticon($data);
        return [$flg, $flg ? ['id' => $rec, 'after_json' => array_merge($data, ['id' => $rec])] : $rec];
    }

    public function modify($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = XsInteractiveEmoticons::findOne($id);
        if (empty($setting)) {
            return [false, 'ID数据错误'];
        }

        $data = $this->validateAndFormatData($params);
        list($flg, $rec) = $this->rpc->editInteractiveEmoticon($data);;

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => array_merge($setting, $data)] : $rec];
    }


    public function upShelf($params, $isUp = true): array
    {
        $id = (int)array_get($params, 'id');
        $setting = XsInteractiveEmoticons::findOne($id);
        if (empty($setting)) {
            return [false, 'ID数据错误'];
        }
        $listed = [];
        if ($isUp) {
            $bigareaIdArr = array_get($params, 'bigarea_id', []);
            $sceneArr = array_get($params, 'scene', []);
            foreach ($bigareaIdArr as $bigareaId) {
                $listed[] = [
                    'bigarea_id'  => intval($bigareaId),
                    'voice_scene' => in_array(XsInteractiveEmoticons::SCENE_VOICE, $sceneArr) ? 1 : 0,
                    'video_scene' => in_array(XsInteractiveEmoticons::SCENE_VIDEO, $sceneArr) ? 1 : 0,
                    'chat_scene'  => in_array(XsInteractiveEmoticons::SCENE_CHAT, $sceneArr) ? 1 : 0,
                ];
            }
        }
        list($flg, $rec) = $this->rpc->opInteractiveEmoticonListed([
            'interactive_emoticon_id' => $id,
            'listeds'                 => $listed
        ]);
        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => $setting] : $rec];
    }

    private function validateAndFormatData($params): array
    {
        $id = (int)array_get($params, 'id', 0);
        $nameJson = array_get($params, 'name_json', []);
        $type = (int)array_get($params, 'type', 0);
        $icon = trim(array_get($params, 'icon', ''));
        $previewImage = trim(array_get($params, 'preview_image', ''));
        $resultStickers = array_get($params, 'result_stickers', []);

        if (!empty($nameJson['zh_cn'])) {
            $nameJson['cn'] = $nameJson['zh_cn'];
        }

        $data = [
            'type'            => $type,
            'name_json'       => @json_encode($nameJson, JSON_UNESCAPED_UNICODE),
            'icon'            => $icon,
            'preview_image'   => $previewImage,
            'result_stickers' => @json_encode($resultStickers, JSON_UNESCAPED_UNICODE),

        ];
        $id && $data['id'] = $id;

        return $data;
    }


    public function getOptions(): array
    {
        $lan = XsBigarea::getLanguageArr();
        return [
            'type' => StatusService::formatMap(XsInteractiveEmoticons::$typeMap, StatusService::PARAMS_FORMAT),
            'lan'  => StatusService::formatMap($lan, StatusService::PARAMS_FORMAT),
        ];
    }

    public function getInfo($params): array
    {
        $id = (int)array_get($params, 'id', 0);
        if (empty($id)) {
            return [];
        }
        $model = XsInteractiveEmoticons::findOne($id);

        if (!empty($model)) {
            $model['emotion_id'] = $id;
            $nameJson = @json_decode($model['name_json'], true);
            if (empty($nameJson['zh_cn'])) {
                $nameJson['zh_cn'] = $nameJson['cn'] ?? '';
            }

            $model['type'] = (string)$model['type'];
            $model['icon_url'] = Helper::getHeadUrl($model['icon'] ?? '');
            $model['preview_image_url'] = Helper::getHeadUrl($model['preview_image'] ?? '');
            $model['name_json'] = $nameJson;
            $model['result_stickers'] = @json_decode($model['result_stickers'], true);

            $resultStickersArr = [];
            foreach ($model['result_stickers'] as $k => $v) {
                $_url = Helper::getHeadUrl($v);
                $resultStickersArr[] = [
                    'uid'      => '-1-' . $k,
                    'name'     => $v,
                    'status'   => 'done',
                    'url'      => $_url,
                    'response' => ['name' => $v, 'url' => $_url]
                ];

            }
            $model['result_stickers_arr'] = $resultStickersArr;
        }


        return $model;
    }

    public static function getTypeMap($value = null, string $format = '')
    {
        $map = XsInteractiveEmoticons::$typeMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getStateMap($value = null, string $format = '')
    {
        $map = XsInteractiveEmoticons::$stateMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getSceneMap($value = null, string $format = '')
    {
        $map = XsInteractiveEmoticons::$sceneMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    private function getNameShow($nameJson): string
    {
        $nameArr = @json_decode($nameJson, true);
        return isset($nameArr['zh_cn']) ? $nameArr['zh_cn'] : ($nameArr['cn'] ?? '');
//        $str = [];
//        $lan = XsBigarea::getLanguageArr();
//        foreach ($nameArr as $key => $val) {
//            $str[] = sprintf('%s（%s）: %s', isset($lan[$key]) ? $lan[$key] : $key, $key, $val);
//        }
//        return implode('<br/>', $str);
    }

    private function getBigareaAndScene($id): array
    {
        $lists = XsInteractiveEmoticonsListed::getListByWhere([['interactive_emoticon_id', '=', $id]]);
        $bigarea = array_map(function ($v) {
            return (string)$v;
        }, array_column($lists, 'bigarea_id'));

        $demo = isset($lists[0]) ? $lists[0] : [];

        $scene = [];
        if (!empty($demo['voice_scene'])) {
            $scene[] = XsInteractiveEmoticons::SCENE_VOICE;
        }
        if (!empty($demo['video_scene'])) {
            $scene[] = XsInteractiveEmoticons::SCENE_VIDEO;
        }
        if (!empty($demo['chat_scene'])) {
            $scene[] = XsInteractiveEmoticons::SCENE_CHAT;
        }
        return [$bigarea, $scene];
    }

}