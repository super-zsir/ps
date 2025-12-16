<?php

namespace Imee\Service\Operate;

use Imee\Models\Xs\XsKtvSong;
use Imee\Service\Helper;

class KtvSongService
{
    public function getListAndTotal($params): array
    {
        $conditions = [];
        if (isset($params['status'])) {
            $conditions[] = ['status', (int)$params['status']];
        }
        if (!empty($params['language'])) {
            $conditions[] = ['language', $params['language']];
        }
        if (!empty($params['name'])) {
            $conditions[] = ['name', 'like', $params['name']];
        }
        if (!empty($params['singer_name'])) {
            $conditions[] = ['singer_name', 'like', $params['singer_name']];
        }
        if (!empty($params['tag'])) {
            $conditions[] = ['tag', 'like', $params['tag']];
        }
        $result = XsKtvSong::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (!$result['data']) {
            return $result;
        }
        foreach ($result['data'] as &$v) {
            $v['dateline'] = $v['dateline'] ? date('Y-m-d H:i:s', $v['dateline']) : '';
            $v['brc_url'] = Helper::getHeadUrl($v['brc']);
            $v['photo_url'] = Helper::getHeadUrl($v['photo']);
            $v['original_mp3_url'] = Helper::getHeadUrl($v['original_mp3']);
            $v['hq_music_url'] = Helper::getHeadUrl($v['hq_music']);
        }
        return $result;
    }

    public function edit($params): array
    {
        $exit = XsKtvSong::findOne($params['id']);
        if (!$exit) {
            return [false, 'id错误'];
        }

        $update = [
            'name'        => $params['name'],
            'singer_name' => $params['singer_name'],
            'brc'         => $params['brc'] ?? '',
            'photo'       => $params['photo'] ?? '',
        ];
        return XsKtvSong::edit($params['id'], $update);
    }

    public function status($id, $status): array
    {
        $exit = XsKtvSong::findOne($id);
        if (!$exit) {
            return [false, 'id错误'];
        }
        if ($exit['status'] == $status) {
            return [false, '状态未变化'];
        }
        return XsKtvSong::edit($id, [
            'status' => $status
        ]);
    }
}