<?php

namespace Imee\Service\Operate\Push;

use Imee\Service\Helper;
use Imee\Service\Rpc\PushRpcService;

/**
 * push文案中台
 */
class PushContentService
{
    /**
     * @var PushRpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PushRpcService();
    }

    public function getList(array $params): array
    {
        if (empty($params['status'])) {
            return [false, '状态筛选项必选', []];
        }
        [$res, $msg, $data] = $this->rpcService->getPushContentList($params);

        if (!$res) {
            return [false, $msg, []];
        }

        foreach ($data['list'] as &$item) {
            $item['update_time'] = Helper::now($item['update_time']);
            $item['picture'] = Helper::getHeadUrl($item['picture']);
            $item['status']  = ((int) $item['status']) + 1;
        }

        return [true, '', $data];
    }

    public function add(array $params): array
    {
        [$vRes, $vMsg] = $this->validation($params);
        $adminId = Helper::getSystemUid();
        $admin = Helper::getAdminName($adminId);
        if ($vRes) {
            return [false, $vMsg, []];
        }
        $data = [
            'app_id' => APP_ID,
            'title' => $params['title'] ?? '',
            'placeholder' => implode(',', $params['placeholder'] ?? []),
            'content' => $params['content'] ?? '',
            'mod' => (int)($params['mod'] ?? 0),
            'status' => (int)($params['status'] ?? 0),
            'creator' => $admin,
            'modifier' => $admin,
            'jump_type' => 1,
        ];

        if ($data['mod'] == '4') {
            $data['picture'] = $params['picture'] ?? '';
            $data['jump_url'] = $params['jump_url'] ?? '';
            unset($data['placeholder']);
            $data['jump_type'] = 2;
        } else if ($data['mod'] == '3') {
            $data['jump_url'] = 'https://partystars.app.link/ps-link/conversationlist?targetId={uid}&conversationType=private';
        }

        [$res, $msg, $id] = $this->rpcService->addPushContent($data);
        if (!$res) {
            return [false, $msg, []];
        }
        return [true, '', ['id' => $id, 'after_json' => $data]];
    }

    public function edit(array $params): array
    {
        [$vRes, $vMsg] = $this->validation($params);
        $adminId = Helper::getSystemUid();
        $admin = Helper::getAdminName($adminId);
        if ($vRes) {
            return [false, $vMsg];
        }
        $data = [
            'id' => (int)($params['id'] ?? 0),
            'app_id' => APP_ID,
            'title' => $params['title'] ?? '',
            'placeholder' => implode(',', $params['placeholder'] ?? []),
            'content' => $params['content'] ?? '',
            'mod' => (int)($params['mod'] ?? 0),
            'status' => (int)($params['status'] ?? 0),
            'modifier' => $admin,
            'jump_type' => 1,
            'update_time' => time()
        ];

        if ($data['mod'] == '4') {
            unset($data['placeholder']);
            $data['picture'] = $params['picture'] ?? '';
            $data['jump_url'] = $params['jump_url'] ?? '';
            $data['jump_type'] = 2;
        }

        [$res, $msg] = $this->rpcService->editPushContent($data);

        if (!$res) {
            return [false, $msg];
        }

        return [true, ['after_json' => $data]];
    }

    public function delete(int $id)
    {
        [$res, $msg] = $this->rpcService->delPushContent($id);
        if (!$res) {
            return [false, $msg];
        }
        return [true, ['after_json' => ['id' => $id]]];
    }

    public function info(int $id)
    {
        [$res, $msg, $data] = $this->rpcService->getPushContent($id);
        if (!$res) {
            return [false, $msg, $data];
        }
        $data['mod'] = (string) $data['mod'];
        $placeholder = array_get($data, 'placeholder', '');
        $data['placeholder'] = array_filter(explode(',', $placeholder));
        return [true, '', $data];
    }

    private function validation(array $params)
    {
        if ($params['mod'] == '4') {
            if (!isset($params['jump_url']) || !isset($params['picture'])) {
                return [true, '关联类型为自定义时，图片和链接必填'];
            }
        }
        return [false, ''];
    }
}