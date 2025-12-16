<?php

namespace Imee\Service\Operate;

use Imee\Exception\ApiException;
use Imee\Models\Cms\NocodeTest;
use Imee\Service\Helper;

class NocodeTestService
{
    public function getListAndTotal(array $params): array
    {
        $conditions = [];

        $uid = intval($params['uid'] ?? 0);
        $userGender = intval($params['user_gender'] ?? 0);
        $userName = trim($params['user_name'] ?? '');
        $userEmail = trim($params['user_email'] ?? '');
        $page = intval($params['page'] ?? 1);
        $limit = intval($params['limit'] ?? 15);

        $uid && $conditions[] = ['uid', '=', $uid];
        $userGender && $conditions[] = ['user_gender', '=', $userGender];
        $userName && $conditions[] = ['user_name', 'like', $userName];
        $userEmail && $conditions[] = ['user_email', 'like', $userEmail];

        $list = NocodeTest::getListAndTotal($conditions, '*', 'uid desc', $page, $limit);

        foreach($list['data'] as &$item) {
            $item['mp4'] = 'https://oss-dev.partystar.chat/static/gift_big/1060072981.mp4';
            $item['png'] = 'https://oss-dev.partystar.chat/static/gift_big/1060072979.png';
            $item['webp'] = 'https://oss-dev.partystar.chat/file/202511/25111710392194.webp';
            $item['mp3'] = 'https://oss-dev.partystar.chat/upload/music/57e6d35afc70a1e7aef71119a3b6ab9c.mp3';
        }
        
        return $list;
    }

    public function create(array $params): array
    {
        $userName = trim($params['user_name'] ?? '');
        $userEmail = trim($params['user_email'] ?? '');
        $userGender = intval($params['user_gender'] ?? 0);

        $data = [
            'user_name' => $userName,
            'user_email' => $userEmail,
            'user_gender' => $userGender,
        ];

        list($res, $msg) = NocodeTest::add($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['uid' => $msg, 'after_json' => $data];
    }

    public function modify(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $userName = trim($params['user_name'] ?? '');
        $userEmail = trim($params['user_email'] ?? '');
        $userGender = intval($params['user_gender'] ?? 0);

        $data = [
            'user_name' => $userName,
            'user_email' => $userEmail,
            'user_gender' => $userGender,
        ];

        list($res, $msg) = NocodeTest::edit($uid, $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['uid' => $uid, 'after_json' => $data];
    }

    public function delete(array $params)
    {
        $uid = intval($params['uid'] ?? 0);
        $res = NocodeTest::deleteById($uid);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'delete error');
        }

        return ['uid' => $uid, 'after_json' => []];
    }

    public function deleteBatch(array $params): array
    {
        $uids = $params['uids'] ?? [];
        $uids = Helper::formatIds($uids);
        $res = NocodeTest::deleteBatch($uids);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'delete batch error');
        }

        return ['uid' => $uids, 'after_json' => []];
    }

    public function info(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);

        return NocodeTest::findOne($uid);
    }

    public function phone(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $userPhone = trim($params['user_phone'] ?? '');

        $data = [
            'user_phone' => $userPhone
        ];

        list($res, $msg) = NocodeTest::edit($uid, $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['uid' => $uid, 'after_json' => $data];
    }
}